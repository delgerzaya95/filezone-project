<?php 
session_start();
require_once '../includes/db.php';

// Админ эрх шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// GET параметрээр амжилттай болсон мессежийг харуулах
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "Файл амжилттай нийтлэгдлээ!";
}

// --- HELPER FUNCTIONS ---
function reArrayFiles(&$file_post) {
    $file_ary = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);
    for ($i=0; $i<$file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = $file_post[$key][$i];
        }
    }
    return $file_ary;
}

// --------------------------------------------------------------------------
// FORM SUBMISSION HANDLER
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $title = trim($_POST['title']);
    $category_id = intval($_POST['category_id']);
    $subcategory_id = intval($_POST['subcategory_id']);
    $child_category_id = isset($_POST['child_category_id']) && !empty($_POST['child_category_id']) ? intval($_POST['child_category_id']) : null;
    
    // Үнийг цэвэрлэх (comma-г арилгах)
    $priceRaw = str_replace(',', '', $_POST['price']);
    $price = floatval($priceRaw);
    
    $description = trim($_POST['description']);
    
    // Chunk Upload-аас ирсэн замууд
    $uploaded_temp_path = isset($_POST['uploaded_temp_path']) ? $_POST['uploaded_temp_path'] : '';
    $uploaded_original_name = isset($_POST['uploaded_original_name']) ? $_POST['uploaded_original_name'] : '';

    if (empty($title) || empty($category_id) || empty($subcategory_id) || empty($uploaded_temp_path)) {
        $error = "Файлын гарчиг, ангилал болон файлыг заавал оруулна уу.";
    } else {
        try {
            // 1. Файл байгаа эсэхийг шалгах (Админ фолдероос нэг түвшин дээш гарах)
            $real_temp_path = str_replace('../', '', $uploaded_temp_path);
            $backend_temp_path = '../' . $real_temp_path; // admin/..

            // Аюулгүй байдлын үүднээс temp path дотор хэрэглэгчийн ID байгаа эсэхийг шалгах
            if (strpos($real_temp_path, "uploads/temp/{$user_id}/") === false) {
                 throw new Exception("Файлын зам буруу байна. (Security Check Failed)");
            }

            if (!file_exists($backend_temp_path)) {
                 throw new Exception("Файл олдсонгүй. Дахин хуулна уу.");
            }

            $file_size = filesize($backend_temp_path);
            $file_ext = strtolower(pathinfo($uploaded_original_name, PATHINFO_EXTENSION));
            
            // Админд төрлийн хязгаарлалт байхгүй, гэхдээ DB-д хадгалахад хэрэгтэй
            $allowed_types = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','jpg','png','zip','rar','exe','mp3','mp4'];
            $file_type_db = in_array($file_ext, $allowed_types) ? $file_ext : 'other';

            $pdo->beginTransaction();

            // 2. Insert File Record (Status = APPROVED)
            $stmt = $pdo->prepare("INSERT INTO files (user_id, category_id, subcategory_id, child_category_id, title, description, file_type, file_size, price, status, upload_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', NOW())");
            $stmt->execute([$user_id, $category_id, $subcategory_id, $child_category_id, $title, $description, $file_type_db, $file_size, $price]);
            $file_id = $pdo->lastInsertId();

            // 3. Move File to Final Destination
            $upload_dir = "../uploads/files/{$user_id}/{$file_id}/";
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception("Хавтас үүсгэж чадсангүй.");
                }
            }

            $final_filename = basename($uploaded_original_name);
            $final_filename = str_replace(' ', '_', $final_filename); 
            $final_path = $upload_dir . $final_filename;

            if (rename($backend_temp_path, $final_path)) {
                // DB update (хадгалахдаа ../ -гүй хадгална)
                $db_file_path = "uploads/files/{$user_id}/{$file_id}/" . $final_filename;
                $update_stmt = $pdo->prepare("UPDATE files SET file_url = ? WHERE id = ?");
                $update_stmt->execute([$db_file_path, $file_id]);
            } else {
                throw new Exception("Файл зөөхөд алдаа гарлаа.");
            }

            // 4. Handle Cover Images
            if (isset($_FILES['cover_image']) && !empty($_FILES['cover_image']['name'][0])) {
                $preview_dir = $upload_dir . "previews/";
                if (!is_dir($preview_dir)) {
                    mkdir($preview_dir, 0777, true);
                }

                $img_files = reArrayFiles($_FILES['cover_image']);
                $uploaded_count = 0;

                foreach ($img_files as $key => $img) {
                    if ($uploaded_count >= 5) break; 
                    if ($img['error'] === UPLOAD_ERR_OK) {
                        $cover_new_name = uniqid() . '_preview_' . $key . '.' . pathinfo($img['name'], PATHINFO_EXTENSION);
                        $cover_path = $preview_dir . $cover_new_name;
                        
                        if (move_uploaded_file($img['tmp_name'], $cover_path)) {
                            // DB path
                            $db_cover_path = "uploads/files/{$user_id}/{$file_id}/previews/" . $cover_new_name;
                            
                            $prev_stmt = $pdo->prepare("INSERT INTO file_previews (file_id, preview_url, order_index) VALUES (?, ?, ?)");
                            $prev_stmt->execute([$file_id, $db_cover_path, $uploaded_count + 1]);
                            $uploaded_count++;
                        }
                    }
                }
            }

            $pdo->commit();
            
            // No Email Notification needed for admin upload
            
            header("Location: file_upload.php?success=1");
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Алдаа гарлаа: " . $e->getMessage();
            // Clean up temp file
            if (isset($backend_temp_path) && file_exists($backend_temp_path)) {
                unlink($backend_temp_path);
            }
        }
    }
}

// Data Fetching
$cats = $pdo->query("SELECT * FROM categories WHERE type = 'file' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$subcats = $pdo->query("SELECT * FROM subcategories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$child_cats = $pdo->query("SELECT * FROM child_category ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ файл оруулах - Filezone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
    
    <!-- TinyMCE -->
    <?php include '../api/tinymce_loader.php'; ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
          initFilezoneEditor('#description');
      });
    </script>
</head>
<body class="font-sans text-slate-800 antialiased bg-slate-50">

<div class="flex h-screen overflow-hidden">
    
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shadow-sm z-10">
            <div class="flex items-center gap-4">
                <button id="mobileMenuBtn" class="md:hidden text-slate-500"><i class="fas fa-bars text-xl"></i></button>
                <h1 class="text-xl font-bold text-slate-800">Шинэ файл оруулах</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="files.php" class="text-slate-500 hover:text-slate-700 text-sm font-medium">
                    <i class="fas fa-arrow-left mr-1"></i> Буцах
                </a>
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
            
            <!-- Processing Overlay -->
            <div id="processingOverlay" class="fixed inset-0 bg-white/90 backdrop-blur-sm z-50 hidden flex flex-col items-center justify-center">
                <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-brand-600 mb-4"></div>
                <h2 class="text-xl font-bold text-gray-800 animate-pulse">Боловсруулж байна...</h2>
                <p class="text-gray-500 mt-2 text-sm">Файлыг серверт хуулж байна.</p>
            </div>

            <?php if($message): ?>
                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg shadow-sm">
                    <strong class="font-bold"><i class="fas fa-check-circle mr-2"></i></strong> <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg shadow-sm">
                    <strong class="font-bold"><i class="fas fa-exclamation-circle mr-2"></i></strong> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    
                    <form action="" method="POST" enctype="multipart/form-data" id="uploadForm">
                        
                        <!-- Chunk Upload Fields (Hidden) -->
                        <input type="hidden" name="uploaded_temp_path" id="uploaded_temp_path">
                        <input type="hidden" name="uploaded_original_name" id="uploaded_original_name">

                        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                            <div class="p-6 border-b border-slate-100">
                                <h3 class="font-bold text-slate-800">Үндсэн мэдээлэл</h3>
                            </div>
                            <div class="p-6 space-y-6">
                                
                                <!-- File Upload Zone -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Файл сонгох <span class="text-red-500">*</span></label>
                                    <div class="bg-slate-50 border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:bg-indigo-50 hover:border-indigo-300 transition cursor-pointer group relative" id="drop-zone">
                                        <input type="file" id="file-upload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="handleFileSelect(this)" required>
                                        <div class="w-16 h-16 bg-white text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm group-hover:scale-110 transition duration-300">
                                            <i class="fas fa-cloud-upload-alt text-2xl"></i>
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-800 mb-2" id="drop-zone-text">Файлаа энд чирж оруулах</h3>
                                        <p class="text-sm text-slate-500">Бүх төрлийн файл зөвшөөрнө (Хэмжээ хязгааргүй)</p>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div id="progress-container" class="hidden mt-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                                        <div class="flex justify-between mb-2">
                                            <span class="text-sm font-medium text-slate-700" id="progress-filename">File...</span>
                                            <span class="text-sm font-bold text-indigo-600" id="progress-percent">0%</span>
                                        </div>
                                        <div class="w-full bg-slate-200 rounded-full h-2.5">
                                            <div id="progress-bar" class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-2 text-center" id="progress-status">Хуулж байна...</p>
                                    </div>
                                </div>

                                <!-- Title -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Файлын гарчиг <span class="text-red-500">*</span></label>
                                    <input type="text" name="title" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" placeholder="Жишээ: Санхүүгийн тайлангийн загвар">
                                </div>

                                <!-- Categories -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Үндсэн ангилал <span class="text-red-500">*</span></label>
                                        <select name="category_id" id="categorySelect" required onchange="updateSubcategories()" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                                            <option value="">Сонгоно уу...</option>
                                            <?php foreach($cats as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Дэд ангилал <span class="text-red-500">*</span></label>
                                        <select name="subcategory_id" id="subcategorySelect" required onchange="updateChildCategories()" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white disabled:bg-slate-100 disabled:text-slate-400" disabled>
                                            <option value="">Эхлээд үндсэн ангилал</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Дэдийн дэд ангилал</label>
                                        <select name="child_category_id" id="childCategorySelect" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white disabled:bg-slate-100 disabled:text-slate-400" disabled>
                                            <option value="">---</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Price -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Үнэ (MNT)</label>
                                    <div class="relative">
                                        <input type="text" name="price" id="priceInput" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none pl-4" placeholder="5,000" oninput="formatPrice(this)">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-slate-500 text-xs">₮</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">0 гэж бичвэл "Үнэгүй" болно.</p>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Дэлгэрэнгүй тайлбар</label>
                                    <textarea name="description" id="description" rows="10" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm"></textarea>
                                </div>

                                <!-- Cover Images -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Нүүр зураг (Max 5)</label>
                                    <div class="space-y-3">
                                        <input type="file" name="cover_image[]" id="cover-upload-storage" class="hidden" multiple>
                                        <input type="file" id="cover-upload-picker" class="hidden" accept="image/*" multiple onchange="handleCoverSelect(this)">
                                        <label for="cover-upload-picker" class="inline-flex items-center gap-2 bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-50 cursor-pointer shadow-sm">
                                            <i class="fas fa-image"></i> Зураг нэмэх
                                        </label>
                                        <div id="preview-gallery" class="grid grid-cols-2 md:grid-cols-5 gap-3"></div>
                                    </div>
                                </div>

                                <hr class="border-slate-100">

                                <div class="flex justify-end gap-3">
                                    <a href="files.php" class="px-6 py-2.5 bg-white border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition">Болих</a>
                                    <button type="button" id="startUploadBtn" onclick="startChunkUpload()" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/30 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all">
                                        Нийтлэх (Шууд зөвшөөрөх)
                                    </button>
                                </div>

                            </div>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-1">
                    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-6 sticky top-6">
                        <h3 class="font-bold text-indigo-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-shield-alt"></i> Админ горим
                        </h3>
                        <ul class="space-y-3 text-sm text-indigo-800">
                            <li class="flex gap-2">
                                <i class="fas fa-check-circle mt-0.5 text-indigo-600"></i>
                                <span>Файл шууд "Approved" төлөвтэй орно.</span>
                            </li>
                            <li class="flex gap-2">
                                <i class="fas fa-check-circle mt-0.5 text-indigo-600"></i>
                                <span>VirusTotal шалгалт хийгдэхгүй.</span>
                            </li>
                            <li class="flex gap-2">
                                <i class="fas fa-check-circle mt-0.5 text-indigo-600"></i>
                                <span>Файлын хэмжээнд хязгаарлалт байхгүй.</span>
                            </li>
                            <li class="flex gap-2">
                                <i class="fas fa-check-circle mt-0.5 text-indigo-600"></i>
                                <span>Имэйл мэдэгдэл илгээгдэхгүй.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- SCRIPTS -->
<script>
    // --- Data ---
    const subcats = <?php echo json_encode($subcats); ?>;
    const childCats = <?php echo json_encode($child_cats); ?>;
    const currentUserId = <?php echo json_encode($user_id); ?>;

    // --- Price Format ---
    function formatPrice(input) {
        let value = input.value.replace(/\D/g, "");
        if (value === "") { input.value = ""; return; }
        let numValue = parseInt(value, 10);
        input.value = numValue.toLocaleString('en-US');
    }

    // --- Category Logic ---
    function updateSubcategories() {
        const catSelect = document.getElementById('categorySelect');
        const subSelect = document.getElementById('subcategorySelect');
        const childSelect = document.getElementById('childCategorySelect');
        
        const selectedCatId = catSelect.value;
        subSelect.innerHTML = '<option value="">Сонгоно уу...</option>';
        childSelect.innerHTML = '<option value="">---</option>';
        childSelect.disabled = true;

        if (selectedCatId) {
            const filtered = subcats.filter(s => s.category_id == selectedCatId);
            if(filtered.length > 0) {
                filtered.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.name;
                    subSelect.appendChild(opt);
                });
                subSelect.disabled = false;
            } else {
                subSelect.innerHTML = '<option value="">Дэд ангилал алга</option>';
                subSelect.disabled = true;
            }
        } else {
            subSelect.innerHTML = '<option value="">Эхлээд үндсэн ангилал</option>';
            subSelect.disabled = true;
        }
    }

    function updateChildCategories() {
        const subSelect = document.getElementById('subcategorySelect');
        const childSelect = document.getElementById('childCategorySelect');
        const selectedSubId = subSelect.value;
        childSelect.innerHTML = '<option value="">Сонгоно уу...</option>';
        
        if (selectedSubId) {
            const filtered = childCats.filter(c => c.subcategory_id == selectedSubId);
            if(filtered.length > 0) {
                filtered.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    childSelect.appendChild(opt);
                });
                childSelect.disabled = false;
            } else {
                childSelect.innerHTML = '<option value="">---</option>';
                childSelect.disabled = true;
            }
        } else {
            childSelect.disabled = true;
        }
    }

    // --- Cover Image Logic ---
    let uploadedImages = [];
    function handleCoverSelect(input) {
        const files = Array.from(input.files);
        if (uploadedImages.length + files.length > 5) { alert("Та дээд тал нь 5 зураг оруулах боломжтой."); return; }
        files.forEach(file => uploadedImages.push(file));
        renderGallery();
        updateInputFiles();
        input.value = ''; 
    }
    function renderGallery() {
        const gallery = document.getElementById('preview-gallery');
        gallery.innerHTML = '';
        uploadedImages.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative w-full h-24 bg-gray-100 rounded-lg overflow-hidden group border border-slate-200';
                div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">
                                 <button type="button" onclick="removeImage(${index})" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition">&times;</button>`;
                gallery.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
    }
    function removeImage(index) { uploadedImages.splice(index, 1); renderGallery(); updateInputFiles(); }
    function updateInputFiles() {
        const dataTransfer = new DataTransfer();
        uploadedImages.forEach(file => { dataTransfer.items.add(file); });
        document.getElementById('cover-upload-storage').files = dataTransfer.files;
    }

    // --- CHUNK UPLOAD LOGIC ---
    let selectedFile = null;
    const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB chunks

    function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            selectedFile = input.files[0];
            document.getElementById('drop-zone-text').textContent = "Сонгогдсон: " + selectedFile.name;
            document.getElementById('drop-zone').classList.add('bg-indigo-50', 'border-indigo-300');
        }
    }

    async function startChunkUpload() {
        const title = document.querySelector('input[name="title"]').value;
        const cat = document.getElementById('categorySelect').value;
        const subcat = document.getElementById('subcategorySelect').value;
        const price = document.getElementById('priceInput').value;

        if(!selectedFile) { alert('Файлаа сонгоно уу!'); return; }
        if(!title || !cat || !subcat || price === '') { alert('Бүх талбарыг бөглөнө үү!'); return; }

        // UI Setup
        const btn = document.getElementById('startUploadBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Уншиж байна...';
        document.getElementById('progress-container').classList.remove('hidden');
        document.getElementById('progress-filename').textContent = selectedFile.name;

        const totalChunks = Math.ceil(selectedFile.size / CHUNK_SIZE);
        const fileId = Date.now().toString(36) + Math.random().toString(36).substr(2);

        for (let i = 0; i < totalChunks; i++) {
            const start = i * CHUNK_SIZE;
            const end = Math.min(selectedFile.size, start + CHUNK_SIZE);
            const chunk = selectedFile.slice(start, end);

            const formData = new FormData();
            formData.append('file', chunk);
            formData.append('fileName', selectedFile.name);
            formData.append('chunkIndex', i);
            formData.append('totalChunks', totalChunks);
            formData.append('fileId', fileId);
            formData.append('user_id', currentUserId);

            try {
                await uploadChunk(formData, i, totalChunks);
            } catch (error) {
                alert('Файл хуулахад алдаа гарлаа: ' + error);
                btn.disabled = false;
                btn.textContent = 'Дахин оролдох';
                return;
            }
        }
    }

    function uploadChunk(formData, index, total) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            // Path to API (admin folder inside, so ../api)
            xhr.open('POST', '../api/chunk_upload.php', true);
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.status === 'chunk_uploaded' || response.status === 'done') {
                            // Update Progress
                            const percent = Math.round(((index + 1) / total) * 100);
                            document.getElementById('progress-bar').style.width = percent + '%';
                            document.getElementById('progress-percent').textContent = percent + '%';
                            document.getElementById('progress-status').textContent = `${index + 1}/${total} хэсэг хуулагдлаа...`;

                            if (response.status === 'done') {
                                document.getElementById('uploaded_temp_path').value = response.tempPath;
                                document.getElementById('uploaded_original_name').value = response.originalName;
                                // Show Final Processing Overlay
                                document.getElementById('processingOverlay').classList.remove('hidden');
                                // Submit Main Form
                                document.getElementById('uploadForm').submit();
                            }
                            resolve();
                        } else {
                            reject(response.message);
                        }
                    } catch (e) {
                        reject("JSON Error: " + e.message);
                    }
                } else {
                    reject("HTTP Error: " + xhr.status);
                }
            };
            
            xhr.onerror = function() { reject("Network Error"); };
            xhr.send(formData);
        });
    }
</script>

</body>
</html>