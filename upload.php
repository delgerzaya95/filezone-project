<?php 
// Session эхлүүлэх
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database холболт
require_once 'includes/db.php';
// Brevo Email Function оруулах
require_once 'api/brevo_email.php';
// VirusTotal Scanner Function оруулах
require_once 'api/virustotal_scanner.php';

// Нэвтрээгүй бол login хуудас руу шилжүүлэх
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// GET параметрээр амжилттай болсон мессежийг харуулах (PRG Pattern)
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "Файл амжилттай илгээгдлээ! Админ шалгасны дараа нийтлэгдэх болно.";
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
// FORM SUBMISSION HANDLER (Updated for Chunked Upload)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $title = trim($_POST['title']);
    $category_id = intval($_POST['category_id']);
    $subcategory_id = intval($_POST['subcategory_id']);
    $child_category_id = isset($_POST['child_category_id']) ? intval($_POST['child_category_id']) : null;
    
    // Үнийг цэвэрлэж авах (Remove commas)
    $priceRaw = str_replace(',', '', $_POST['price']);
    $price = floatval($priceRaw);
    
    $description = trim($_POST['description']);
    
    // Энэ бол Chunk Upload-аас ирсэн файлын зам (api/chunk_upload.php-ээс үүссэн)
    $uploaded_temp_path = isset($_POST['uploaded_temp_path']) ? $_POST['uploaded_temp_path'] : '';
    $uploaded_original_name = isset($_POST['uploaded_original_name']) ? $_POST['uploaded_original_name'] : '';

    if (empty($title) || empty($category_id) || empty($subcategory_id) || empty($uploaded_temp_path)) {
        $error = "Файлын гарчиг, ангилал болон үндсэн файлыг заавал оруулна уу.";
    } else {
        try {
            // Файл байгаа эсэхийг шалгах
            $real_temp_path = str_replace('../', '', $uploaded_temp_path);
            
            // Аюулгүй байдлын үүднээс temp path дотор хэрэглэгчийн ID байгаа эсэхийг шалгах
            if (strpos($real_temp_path, "uploads/temp/{$user_id}/") === false) {
                 throw new Exception("Файлын зам буруу байна. (Security Check Failed)");
            }

            if (!file_exists($real_temp_path)) {
                if (!file_exists(str_replace('../', '', $real_temp_path))) {
                     throw new Exception("Файл олдсонгүй. Дахин хуулна уу.");
                }
            }

            $file_size = filesize($real_temp_path);
            $file_ext = strtolower(pathinfo($uploaded_original_name, PATHINFO_EXTENSION));
            
            $allowed_types = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','jpg','png','zip','rar','exe','mp3','mp4'];
            $file_type_db = in_array($file_ext, $allowed_types) ? $file_ext : 'other';

            // VirusTotal Check
            if (in_array($file_ext, ['exe', 'zip', 'rar'])) {
                $scanResult = scanFileWithVirusTotal($real_temp_path);
                if (!$scanResult['safe']) {
                    unlink($real_temp_path); // Delete unsafe file
                    throw new Exception("Аюулгүй байдлын шалгалт: " . $scanResult['message']);
                }
            }

            $pdo->beginTransaction();

            // 1. Insert File Record
            $stmt = $pdo->prepare("INSERT INTO files (user_id, category_id, subcategory_id, child_category_id, title, description, file_type, file_size, price, status, upload_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$user_id, $category_id, $subcategory_id, $child_category_id ?: null, $title, $description, $file_type_db, $file_size, $price]);
            $file_id = $pdo->lastInsertId();

            // 2. Move File to Final Destination
            $upload_dir = "uploads/files/{$user_id}/{$file_id}/";
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception("Хавтас үүсгэж чадсангүй.");
                }
            }

            // Original нэрээр нь хадгалах
            $final_filename = basename($uploaded_original_name);
            $final_filename = str_replace(' ', '_', $final_filename); // Space-ийг солих
            $final_path = $upload_dir . $final_filename;

            if (rename($real_temp_path, $final_path)) {
                $update_stmt = $pdo->prepare("UPDATE files SET file_url = ? WHERE id = ?");
                $update_stmt->execute([$final_path, $file_id]);
            } else {
                throw new Exception("Файл зөөхөд алдаа гарлаа.");
            }

            // 3. Handle Cover Images (Standard Upload is fine for small images)
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
                            $order_index = $key + 1;
                            $prev_stmt = $pdo->prepare("INSERT INTO file_previews (file_id, preview_url, order_index) VALUES (?, ?, ?)");
                            $prev_stmt->execute([$file_id, $cover_path, $order_index]);
                            $uploaded_count++;
                        }
                    }
                }
            }

            $pdo->commit();
            sendEmailNotification($title);
            
            header("Location: upload.php?success=1");
            exit;

        } catch (Exception $e) {
            // АЛДАА ЗАСАВ: Гүйлгээ идэвхтэй эсэхийг шалгаж байж rollBack хийнэ
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Алдаа гарлаа: " . $e->getMessage();
            // Clean up temp file if exists
            if (isset($real_temp_path) && file_exists($real_temp_path)) {
                unlink($real_temp_path);
            }
        }
    }
}

// Data Fetching
$cats = $pdo->query("SELECT * FROM categories WHERE type = 'file' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$subcats = $pdo->query("SELECT * FROM subcategories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$child_cats = $pdo->query("SELECT * FROM child_category ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Файл оруулах - Filezone.mn";
include 'includes/header.php'; 
?>

<!-- TinyMCE Script -->
<?php include 'api/tinymce_loader.php'; ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
      initFilezoneEditor('#description');
  });
</script>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    <!-- Sidebar -->
    <aside class="hidden lg:block w-64 flex-shrink-0 py-6 pr-6 h-[calc(100vh-64px)] sticky top-16 overflow-y-auto no-scrollbar">
        <div class="space-y-1 mb-6">
            <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Үндсэн</h3>
            <a href="index.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-home w-5 text-center"></i> Нүүр хуудас
            </a>
            <a href="browse-files.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-folder-open w-5 text-center"></i> Файлууд
            </a>
        </div>
        <div class="space-y-1 mb-8">
            <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Миний цэс</h3>
            <a href="profile/dashboard.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-chart-pie w-5 text-center"></i> Хяналтын самбар
            </a>
            <a href="profile/my_files.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-folder w-5 text-center"></i> Миний файлууд
            </a>
            <a href="profile/my_services.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-briefcase w-5 text-center"></i> Миний үйлчилгээ
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0 relative">
        
        <!-- Processing Overlay (Final Step) -->
        <div id="processingOverlay" class="fixed inset-0 bg-white/90 backdrop-blur-sm z-50 hidden flex flex-col items-center justify-center">
            <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-brand-600 mb-4"></div>
            <h2 class="text-xl font-bold text-gray-800 animate-pulse">Боловсруулж байна...</h2>
            <p class="text-gray-500 mt-2 text-sm">Файлыг баталгаажуулж хадгалж байна.</p>
        </div>

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Шинэ файл оруулах</h1>
            <p class="text-sm text-gray-500">Та өөрийн бүтээл, бие даалт, дипломын ажлаа зарж орлого олоорой.</p>
        </div>

        <?php if($message): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Амжилттай!</strong>
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Алдаа!</strong>
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Main Form -->
                <form action="upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                    
                    <!-- Chunk Upload Fields (Hidden) -->
                    <input type="hidden" name="uploaded_temp_path" id="uploaded_temp_path">
                    <input type="hidden" name="uploaded_original_name" id="uploaded_original_name">

                    <!-- DROP ZONE -->
                    <div class="bg-white border-2 border-dashed border-brand-300 rounded-2xl p-8 text-center hover:bg-brand-50/50 transition cursor-pointer group relative mb-6" id="drop-zone">
                        <input type="file" id="file-upload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="handleFileSelect(this)" required>
                        
                        <div class="w-16 h-16 bg-brand-100 text-brand-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition duration-300">
                            <i class="fas fa-cloud-upload-alt text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2" id="drop-zone-text">Файлаа энд чирж оруулах</h3>
                        <p class="text-sm text-gray-500 mb-4" id="drop-zone-sub">Эсвэл дарж файлаа сонгоно уу</p>
                        <p class="text-xs text-gray-400">Зөвшөөрөгдөх: PDF, DOCX, ZIP, MP4 (Max 150MB+)</p>
                    </div>

                    <!-- Progress Bar (Initially Hidden) -->
                    <div id="progress-container" class="hidden bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6">
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700" id="progress-filename">File...</span>
                            <span class="text-sm font-bold text-brand-600" id="progress-percent">0%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div id="progress-bar" class="bg-brand-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2 text-center" id="progress-status">Хуулж байна...</p>
                    </div>

                    <div id="file-info" class="hidden bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6">
                        <div class="flex justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-file-alt text-brand-600"></i>
                                <span class="text-sm font-medium text-gray-700" id="file-name-display"></span>
                            </div>
                            <span class="text-sm font-bold text-green-600"><i class="fas fa-check-circle"></i> Бэлэн</span>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <h3 class="font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Файлын мэдээлэл</h3>
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Файлын гарчиг <span class="text-red-500">*</span></label>
                                <input type="text" name="title" required class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition" placeholder="Жишээ: Санхүүгийн тайлангийн загвар 2025">
                            </div>

                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Үндсэн ангилал <span class="text-red-500">*</span></label>
                                    <select name="category_id" id="categorySelect" required onchange="updateSubcategories()" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none bg-white">
                                        <option value="">Сонгоно уу...</option>
                                        <?php foreach($cats as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>">
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Дэд ангилал <span class="text-red-500">*</span></label>
                                    <select name="subcategory_id" id="subcategorySelect" required onchange="updateChildCategories()" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none bg-white disabled:bg-gray-50 disabled:text-gray-400" disabled>
                                        <option value="">Эхлээд үндсэн ангилал сонгоно уу</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Дэдийн дэд ангилал (Байгаа бол)</label>
                                    <select name="child_category_id" id="childCategorySelect" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none bg-white disabled:bg-gray-50 disabled:text-gray-400" disabled>
                                        <option value="">---</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Үнэ (MNT) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <!-- Changed input type to text for formatting -->
                                    <input type="text" name="price" id="priceInput" required class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition" placeholder="5,000" oninput="formatPrice(this)">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-xs">₮</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">0 гэж бичвэл "Үнэгүй" болно.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Дэлгэрэнгүй тайлбар</label>
                                <textarea name="description" id="description" rows="10" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition" placeholder="Файлын агуулга, хуудасны тоо, онцлог талуудыг бичнэ үү..."></textarea>
                            </div>

                            <!-- Cover Image Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Нүүр зураг (Max 5)
                                    <span class="text-xs text-gray-400 font-normal ml-2">Чирж дарааллыг солино уу</span>
                                </label>
                                <div class="space-y-3">
                                    <input type="file" name="cover_image[]" id="cover-upload-storage" class="hidden" multiple>
                                    <input type="file" id="cover-upload-picker" class="hidden" accept="image/*" multiple onchange="handleCoverSelect(this)">
                                    <label for="cover-upload-picker" class="inline-flex items-center gap-2 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-50 cursor-pointer shadow-sm">
                                        <i class="fas fa-plus"></i> Зураг нэмэх
                                    </label>
                                    <div id="preview-gallery" class="grid grid-cols-2 md:grid-cols-5 gap-3"></div>
                                </div>
                            </div>

                            <hr class="border-gray-100">

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="terms" type="checkbox" required class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="terms" class="font-medium text-gray-700">Би зөвшөөрч байна</label>
                                    <p class="text-gray-500 text-xs">
                                        Энэ файл нь миний оюуны өмч мөн бөгөөд бусдын зохиогчийн эрхийг зөрчөөгүй болно.
                                        <a href="#" onclick="openTermsModal(event)" class="text-brand-600 hover:text-brand-700 underline ml-1">Үйлчилгээний нөхцөл</a> унших.
                                    </p>
                                </div>
                            </div>

                            <div class="flex gap-4 pt-2">
                                <button type="button" id="startUploadBtn" onclick="startChunkUpload()" class="flex-1 bg-brand-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-brand-500/30 hover:bg-brand-700 hover:-translate-y-0.5 transition-all">
                                    Нийтлэх
                                </button>
                                <button type="button" onclick="history.back()" class="px-6 py-3 border border-gray-300 text-gray-600 font-bold rounded-xl hover:bg-gray-50 transition">
                                    Болих
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="lg:col-span-1">
                <!-- Tips Sidebar (Restored) -->
                <div class="bg-yellow-50 border border-yellow-100 rounded-2xl p-6 sticky top-24">
                    <h3 class="font-bold text-yellow-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-lightbulb"></i> Зөвлөмж
                    </h3>
                    <ul class="space-y-3 text-sm text-yellow-800">
                        <li class="flex gap-2">
                            <i class="fas fa-check-circle mt-0.5 text-yellow-600"></i>
                            <span>Ангилалаа зөв сонгосноор таны файл хайлтад хурдан илэрнэ.</span>
                        </li>
                        <li class="flex gap-2">
                            <i class="fas fa-check-circle mt-0.5 text-yellow-600"></i>
                            <span>Тайлбар хэсэгт файлын давуу талыг сайн бичээрэй.</span>
                        </li>
                        <li class="flex gap-2">
                            <i class="fas fa-check-circle mt-0.5 text-yellow-600"></i>
                            <span>Үнээ бодитой тогтоох нь борлуулалтыг нэмэгдүүлнэ.</span>
                        </li>
                        <li class="flex gap-2">
                            <i class="fas fa-check-circle mt-0.5 text-yellow-600"></i>
                            <span>Зохиогчийн эрхийн зөрчилтэй файл устгагдахыг анхаарна уу.</span>
                        </li>
                        <li class="flex gap-2">
                            <i class="fas fa-shield-alt mt-0.5 text-yellow-600"></i>
                            <span>Аюулгүй байдлын үүднээс оруулсан бүх файлыг систем автоматаар вирус шалгадаг болохыг анхаарна уу.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php' ?>
    </main>
</div>

<!-- Scripts -->
<script>
    // --- Price Formatting Logic ---
    function formatPrice(input) {
        // Remove non-numeric chars except comma (temporarily, but usually comma is the separator we add)
        // Actually, best is to remove everything that is not a digit
        let value = input.value.replace(/\D/g, "");
        
        if (value === "") {
            input.value = "";
            return;
        }
        
        // Convert to integer
        let numValue = parseInt(value, 10);
        
        // Format with commas
        input.value = numValue.toLocaleString('en-US');
    }

    // --- Existing Helper Logic (Subcats, Covers, Modals) ---
    const subcats = <?php echo json_encode($subcats); ?>;
    const childCats = <?php echo json_encode($child_cats); ?>;
    // Current User ID from PHP Session for extra JS validation if needed
    const currentUserId = <?php echo json_encode($user_id); ?>;
    
    function openTermsModal(e) { e.preventDefault(); document.getElementById('termsModal').classList.remove('hidden'); }
    function closeTermsModal() { document.getElementById('termsModal').classList.add('hidden'); }

    function updateSubcategories() {
        const catSelect = document.getElementById('categorySelect');
        const subSelect = document.getElementById('subcategorySelect');
        const childSelect = document.getElementById('childCategorySelect');
        const selectedCatId = catSelect.value;
        subSelect.innerHTML = '<option value="">Сонгоно уу...</option>';
        childSelect.innerHTML = '<option value="">---</option>';
        childSelect.disabled = true;
        if (selectedCatId) {
            const filteredSubcats = subcats.filter(sub => sub.category_id == selectedCatId);
            if (filteredSubcats.length > 0) {
                filteredSubcats.forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub.id;
                    option.textContent = sub.name;
                    subSelect.appendChild(option);
                });
                subSelect.disabled = false;
            } else {
                subSelect.innerHTML = '<option value="">Энэ ангилалд дэд ангилал алга</option>';
                subSelect.disabled = true;
            }
        } else {
            subSelect.innerHTML = '<option value="">Эхлээд үндсэн ангилал сонгоно уу</option>';
            subSelect.disabled = true;
        }
    }

    function updateChildCategories() {
        const subSelect = document.getElementById('subcategorySelect');
        const childSelect = document.getElementById('childCategorySelect');
        const selectedSubId = subSelect.value;
        childSelect.innerHTML = '<option value="">Сонгоно уу...</option>';
        if (selectedSubId) {
            const filteredChildCats = childCats.filter(child => child.subcategory_id == selectedSubId);
            if (filteredChildCats.length > 0) {
                filteredChildCats.forEach(child => {
                    const option = document.createElement('option');
                    option.value = child.id;
                    option.textContent = child.name;
                    childSelect.appendChild(option);
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

    // Cover Image Logic
    let uploadedImages = [];
    function handleCoverSelect(input) {
        const files = Array.from(input.files);
        if (uploadedImages.length + files.length > 5) { alert("Та дээд тал нь 5 зураг оруулах боломжтой."); return; }
        files.forEach(file => { if (uploadedImages.length < 5) uploadedImages.push(file); });
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
                div.className = 'relative w-full h-24 bg-gray-100 rounded-lg border border-gray-200 overflow-hidden group';
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

    // --- CHUNK UPLOAD LOGIC (NEW) ---
    
    let selectedFile = null;
    const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB chunks

    function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            selectedFile = input.files[0];
            document.getElementById('file-name-display').textContent = selectedFile.name;
            document.getElementById('drop-zone-text').textContent = "Файл сонгогдсон";
            document.getElementById('drop-zone').classList.add('bg-green-50', 'border-green-300');
        }
    }

    async function startChunkUpload() {
        const title = document.querySelector('input[name="title"]').value;
        const cat = document.getElementById('categorySelect').value;
        const subcat = document.getElementById('subcategorySelect').value;
        // Clean price for validation
        const priceInput = document.getElementById('priceInput');
        const price = priceInput.value.replace(/,/g, ''); 
        const terms = document.getElementById('terms').checked;

        if(!selectedFile) { alert('Та үндсэн файлаа сонгоно уу!'); return; }
        if(!title || !cat || !subcat || price === '') { alert('Бүх талбарыг бөглөнө үү!'); return; }
        if(!terms) { alert('Үйлчилгээний нөхцөлийг зөвшөөрнө үү!'); return; }

        // Also update hidden field or ensure backend cleans it (Backend already does)
        
        // UI Setup
        document.getElementById('startUploadBtn').disabled = true;
        document.getElementById('startUploadBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Уншиж байна...';
        document.getElementById('progress-container').classList.remove('hidden');
        document.getElementById('progress-filename').textContent = selectedFile.name;

        const totalChunks = Math.ceil(selectedFile.size / CHUNK_SIZE);
        const fileId = Date.now().toString(36) + Math.random().toString(36).substr(2); // Unique ID for this session

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
            formData.append('user_id', currentUserId); // Also sending User ID explicitly

            try {
                await uploadChunk(formData, i, totalChunks);
            } catch (error) {
                alert('Файл хуулахад алдаа гарлаа: ' + error);
                document.getElementById('startUploadBtn').disabled = false;
                document.getElementById('startUploadBtn').textContent = 'Дахин оролдох';
                return;
            }
        }
    }

    function uploadChunk(formData, index, total) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/chunk_upload.php', true);
            
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
                                // Finalize Upload
                                document.getElementById('uploaded_temp_path').value = response.tempPath;
                                document.getElementById('uploaded_original_name').value = response.originalName;
                                
                                // Show Final Processing Overlay
                                document.getElementById('processingOverlay').classList.remove('hidden');
                                
                                // Submit the Main Form to PHP to save DB record
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

<!-- Terms Modal (Restored) -->
<div id="termsModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeTermsModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Үйлчилгээний нөхцөл</h3>
                <div class="mt-4 text-sm text-gray-500 h-64 overflow-y-auto space-y-2 border p-2 rounded bg-gray-50">
                    <p><strong>1. Ерөнхий нөхцөл</strong><br>Filezone.mn нь хэрэглэгчдэд цахим файл солилцох, худалдаалах боломжийг олгодог платформ юм. Хэрэглэгч нь энэхүү үйлчилгээг ашиглахдаа Монгол Улсын хууль тогтоомжийг дагаж мөрдөх үүрэгтэй.</p>
                    <p><strong>2. Зохиогчийн эрх</strong><br>Хэрэглэгч нь зөвхөн өөрийн бүтээсэн эсвэл албан ёсны зөвшөөрөлтэй файлуудыг нийтлэх үүрэгтэй. Бусдын оюуны өмчийг зөвшөөрөлгүй ашиглахыг хатуу хориглоно.</p>
                    <p><strong>3. Хориглох зүйлс</strong><br>Хууль бус, садар самуун, хүчирхийлэл сурталчилсан, эсвэл бусдын нэр хүндэд халдсан агуулгатай файл оруулахыг хориглоно.</p>
                    <p><strong>4. Хариуцлага</strong><br>Платформ нь хэрэглэгчийн оруулсан файлын агуулгад хариуцлага хүлээхгүй. Хэрэглэгч өөрийн оруулсан файлтай холбоотой аливаа маргаан, хариуцлагыг бие даан хариуцна.</p>
                    <p><strong>5. Төлбөр тооцоо</strong><br>Файл борлуулсан орлогоос шимтгэл суутгагдах бөгөөд үлдэгдэл дүнг хэрэглэгч хүсэлт гарган авах боломжтой.</p>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeTermsModal()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-brand-600 text-base font-medium text-white hover:bg-brand-700 sm:ml-3 sm:w-auto sm:text-sm">Ойлголоо</button>
            </div>
        </div>
    </div>
</div>