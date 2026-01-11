<?php
session_start();
require_once '../includes/db.php';
require_once '../kids/db_kids.php';

// Админ эрх шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: kids.php");
    exit;
}

$id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Fetch Material Data
$stmt = $pdo_kids->prepare("SELECT * FROM kids_materials WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Материал олдсонгүй.");
}

// Fetch Previews
$stmt_prev = $pdo_kids->prepare("SELECT * FROM kids_material_previews WHERE material_id = ? ORDER BY sort_order ASC");
$stmt_prev->execute([$id]);
$previews = $stmt_prev->fetchAll(PDO::FETCH_ASSOC);

// Fetch Categories
$categories = $pdo_kids->query("SELECT * FROM kids_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. DELETE PREVIEWS (Хэрэв сонгосон бол)
    if (isset($_POST['delete_previews']) && is_array($_POST['delete_previews'])) {
        foreach ($_POST['delete_previews'] as $p_id) {
            $p_id = intval($p_id);
            // Файлын замыг авч устгах
            $stmt_get_path = $pdo_kids->prepare("SELECT image_path FROM kids_material_previews WHERE id = ? AND material_id = ?");
            $stmt_get_path->execute([$p_id, $id]);
            $path_row = $stmt_get_path->fetch(PDO::FETCH_ASSOC);
            
            if ($path_row) {
                // Remove leading uploads/ if present in DB relative path, otherwise check ../
                // DB path: uploads/kids/...
                $full_path = '../' . $path_row['image_path'];
                if (file_exists($full_path)) {
                    unlink($full_path);
                }
                // Баазаас устгах
                $del_stmt = $pdo_kids->prepare("DELETE FROM kids_material_previews WHERE id = ?");
                $del_stmt->execute([$p_id]);
            }
        }
    }

    $title = trim($_POST['title']);
    $category_id = intval($_POST['category_id']);
    $target_age = isset($_POST['target_age']) ? trim($_POST['target_age']) : '';
    $description = $_POST['description']; // TinyMCE
    $page_count = isset($_POST['page_count']) ? intval($_POST['page_count']) : 0;
    $is_premium = isset($_POST['is_premium']) ? 1 : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';
    // Price removed or default 0
    $price = 0.00;
    
    $file_size_text = isset($_POST['file_size_text']) ? trim($_POST['file_size_text']) : $item['file_size_text'];

    // Chunk Upload Paths
    $uploaded_temp_path = isset($_POST['uploaded_temp_path']) ? $_POST['uploaded_temp_path'] : '';
    $uploaded_original_name = isset($_POST['uploaded_original_name']) ? $_POST['uploaded_original_name'] : '';

    // Хавтас бэлтгэх
    $base_dir = "../uploads/kids/" . $id . "/";
    $previews_dir = $base_dir . "previews/";
    if (!is_dir($base_dir)) mkdir($base_dir, 0777, true);
    if (!is_dir($previews_dir)) mkdir($previews_dir, 0777, true);

    try {
        $pdo_kids->beginTransaction();

        // 2. MAIN FILE UPDATE (Chunk Upload Logic)
        $db_file_path = $item['file_path'];
        $db_file_type = $item['file_type']; // Assuming column exists based on add_kids
        $db_file_size = $item['file_size']; // Assuming column exists

        if (!empty($uploaded_temp_path) && !empty($uploaded_original_name)) {
            // New file uploaded via Chunk
            $real_temp_path = str_replace('../', '', $uploaded_temp_path);
            $backend_temp_path = '../' . $real_temp_path;

            if (file_exists($backend_temp_path)) {
                $final_filename = basename($uploaded_original_name);
                $final_filename = str_replace(' ', '_', $final_filename);
                $final_path = $base_dir . $final_filename;

                // Remove old file
                if ($db_file_path && file_exists('../' . $db_file_path)) {
                    unlink('../' . $db_file_path);
                }

                if (rename($backend_temp_path, $final_path)) {
                    $db_file_path = "uploads/kids/$id/" . $final_filename;
                    $db_file_size = filesize($final_path);
                    $db_file_type = strtolower(pathinfo($final_filename, PATHINFO_EXTENSION)); // Simple extension check
                    
                    // Auto size text update if empty
                    if(empty($_POST['file_size_text'])) {
                        if ($db_file_size >= 1048576) $file_size_text = number_format($db_file_size / 1048576, 1) . ' MB';
                        else $file_size_text = number_format($db_file_size / 1024, 0) . ' KB';
                    }
                }
            }
        } elseif (isset($_FILES['main_file']) && $_FILES['main_file']['error'] == 0) {
            // Fallback for small file upload via standard input (optional, mainly covered by chunk logic now)
            $ext = pathinfo($_FILES['main_file']['name'], PATHINFO_EXTENSION);
            $new_name = 'file_' . time() . '.' . $ext;
            $destination = $base_dir . $new_name;
            
            if ($db_file_path && file_exists('../' . $db_file_path)) unlink('../' . $db_file_path);
            
            if (move_uploaded_file($_FILES['main_file']['tmp_name'], $destination)) {
                $db_file_path = "uploads/kids/$id/" . $new_name;
                $db_file_size = filesize($destination);
                $db_file_type = $ext;
                
                if(empty($file_size_text)) {
                    if ($db_file_size >= 1048576) $file_size_text = number_format($db_file_size / 1048576, 1) . ' MB';
                    else $file_size_text = number_format($db_file_size / 1024, 0) . ' KB';
                }
            }
        }

        // 3. COVER IMAGE UPDATE
        $db_cover_path = $item['cover_image'];
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
            $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $new_cover_name = 'cover_' . time() . '.' . $ext;
            $destination = $base_dir . $new_cover_name;
            
            if ($db_cover_path && file_exists('../' . $db_cover_path)) unlink('../' . $db_cover_path);
            
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $destination)) {
                $db_cover_path = "uploads/kids/$id/" . $new_cover_name;
            }
        }

        // 4. ADD NEW PREVIEWS
        if (isset($_FILES['previews'])) {
            $total_files = count($_FILES['previews']['name']);
            $insert_preview_stmt = $pdo_kids->prepare("INSERT INTO kids_material_previews (material_id, image_path, sort_order) VALUES (?, ?, ?)");
            
            // Get current max sort order
            $current_count_stmt = $pdo_kids->prepare("SELECT MAX(sort_order) FROM kids_material_previews WHERE material_id = ?");
            $current_count_stmt->execute([$id]);
            $max_order = $current_count_stmt->fetchColumn() ?: 0;

            for ($i = 0; $i < $total_files; $i++) {
                if ($_FILES['previews']['error'][$i] == 0) {
                    $p_ext = pathinfo($_FILES['previews']['name'][$i], PATHINFO_EXTENSION);
                    $p_new_name = uniqid('prev_') . '.' . $p_ext;
                    $p_destination = $previews_dir . $p_new_name;
                    
                    if (move_uploaded_file($_FILES['previews']['tmp_name'][$i], $p_destination)) {
                        $p_db_path = "uploads/kids/$id/previews/" . $p_new_name;
                        $max_order++;
                        $insert_preview_stmt->execute([$id, $p_db_path, $max_order]);
                    }
                }
            }
        }

        // 5. UPDATE DATABASE
        // Note: Assuming 'file_type' and 'file_size' columns exist based on add_kids logic
        $sql = "UPDATE kids_materials SET 
                category_id=?, title=?, target_age=?, description=?, 
                is_premium=?, price=?, file_path=?, cover_image=?, 
                file_size_text=?, page_count=?, status=?, file_type=?, file_size=?
                WHERE id=?";
        $stmt = $pdo_kids->prepare($sql);
        $stmt->execute([
            $category_id, $title, $target_age, $description, 
            $is_premium, $price, $db_file_path, $db_cover_path, 
            $file_size_text, $page_count, $status, $db_file_type, $db_file_size, $id
        ]);
        
        $pdo_kids->commit();
        $message = "Амжилттай шинэчлэгдлээ!";
        
        // Refresh item data
        $stmt = $pdo_kids->prepare("SELECT * FROM kids_materials WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Refresh previews
        $stmt_prev->execute([$id]);
        $previews = $stmt_prev->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $pdo_kids->rollBack();
        $error = "Алдаа: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KIDS Засах - FileZone Admin</title>
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
                    <h1 class="text-xl font-bold text-slate-800">Материал Засах</h1>
                </div>
                <div class="flex items-center gap-3">
                    <a href="kids.php" class="text-slate-500 hover:text-indigo-600 font-medium text-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Буцах
                    </a>
                </div>
            </header>

            <!-- Main Body -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                
                <!-- Processing Overlay -->
                <div id="processingOverlay" class="fixed inset-0 bg-white/90 backdrop-blur-sm z-50 hidden flex flex-col items-center justify-center">
                    <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-brand-600 mb-4"></div>
                    <h2 class="text-xl font-bold text-gray-800 animate-pulse">Шинэчилж байна...</h2>
                    <p class="text-gray-500 mt-2 text-sm">Түр хүлээнэ үү.</p>
                </div>

                <div class="max-w-4xl mx-auto">
                    
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

                    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-slate-800">Материал ID: <?php echo $id; ?></h3>
                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Status: <?php echo ucfirst($item['status']); ?></span>
                        </div>
                        
                        <form method="POST" action="" enctype="multipart/form-data" id="editForm" class="p-6 space-y-6">
                            
                            <!-- Hidden Fields for Chunk Upload -->
                            <input type="hidden" name="uploaded_temp_path" id="uploaded_temp_path">
                            <input type="hidden" name="uploaded_original_name" id="uploaded_original_name">

                            <!-- Current File Info Box -->
                            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                                        <i class="fas fa-file-alt text-lg"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">Одоогийн файл</p>
                                        <a href="../<?php echo $item['file_path']; ?>" target="_blank" class="text-xs text-blue-600 hover:underline break-all">
                                            <?php echo basename($item['file_path']); ?>
                                        </a>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-mono bg-white px-2 py-1 rounded border block mb-1">
                                        <?php echo $item['file_size_text']; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- FILE REPLACE ZONE -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Файлыг солих (Сонголттой)</label>
                                <div class="bg-slate-50 border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:bg-indigo-50 hover:border-indigo-300 transition cursor-pointer group relative" id="drop-zone">
                                    <input type="file" id="file-upload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="handleFileSelect(this)">
                                    <div class="w-12 h-12 bg-white text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm group-hover:scale-110 transition duration-300">
                                        <i class="fas fa-cloud-upload-alt text-xl"></i>
                                    </div>
                                    <p class="text-sm text-slate-600 mb-1" id="drop-zone-text">Шинэ файл чирж оруулах эсвэл дарж сонгоно уу</p>
                                    <p class="text-xs text-slate-400">Хуучин файлыг дарж устгаад шинийг хадгална.</p>
                                </div>
                                
                                <!-- Progress Bar -->
                                <div id="progress-container" class="hidden mt-3 bg-white p-3 rounded-lg border border-slate-200 shadow-sm">
                                    <div class="flex justify-between mb-1">
                                        <span class="text-xs font-medium text-slate-700" id="progress-filename">File...</span>
                                        <span class="text-xs font-bold text-indigo-600" id="progress-percent">0%</span>
                                    </div>
                                    <div class="w-full bg-slate-200 rounded-full h-2">
                                        <div id="progress-bar" class="bg-indigo-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1 text-center" id="progress-status">Хуулж байна...</p>
                                </div>
                            </div>

                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Гарчиг</label>
                                <input type="text" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Ангилал</label>
                                    <select name="category_id" class="w-full rounded-lg border-slate-300 border px-3 py-2 bg-white">
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo $item['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Нас</label>
                                    <select name="target_age" class="w-full rounded-lg border-slate-300 border px-3 py-2 bg-white">
                                        <?php
                                        $ages = ['2-3', '3-4', '4-5', 'school_prep', 'grade-1', 'all'];
                                        foreach($ages as $age) {
                                            $sel = $item['target_age'] == $age ? 'selected' : '';
                                            echo "<option value='$age' $sel>$age</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Premium Checkbox & Status -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 flex items-center">
                                    <input type="checkbox" id="is_premium" name="is_premium" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" <?php echo $item['is_premium'] ? 'checked' : ''; ?>>
                                    <label for="is_premium" class="ml-2 text-sm font-medium text-slate-800">Төлбөртэй (Premium)</label>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Статус</label>
                                    <select name="status" class="w-full rounded-lg border-slate-300 border px-3 py-2 bg-white">
                                        <option value="active" <?php echo $item['status']=='active'?'selected':''; ?>>Active</option>
                                        <option value="inactive" <?php echo $item['status']=='inactive'?'selected':''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Page Count & Size -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Хуудасны тоо</label>
                                    <input type="number" name="page_count" value="<?php echo $item['page_count']; ?>" class="w-full rounded-lg border-slate-300 border px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Хэмжээ (Текстээр)</label>
                                    <input type="text" name="file_size_text" value="<?php echo htmlspecialchars($item['file_size_text']); ?>" class="w-full rounded-lg border-slate-300 border px-3 py-2">
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Тайлбар</label>
                                <textarea name="description" id="description" rows="10" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm"><?php echo htmlspecialchars($item['description']); ?></textarea>
                            </div>

                            <!-- Cover Image -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Ковер зураг</label>
                                <div class="flex items-center gap-4 mt-2">
                                    <div class="w-24 h-24 bg-gray-100 rounded-lg border border-slate-200 overflow-hidden shrink-0">
                                        <?php if($item['cover_image']): ?>
                                            <img src="../<?php echo $item['cover_image']; ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-slate-400"><i class="fas fa-image text-2xl"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <input type="file" name="cover_image" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                        <p class="text-xs text-slate-500 mt-1">Солих бол шинээр сонгоно уу.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview Images -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Нэмэлт зургууд (Previews)</label>
                                
                                <!-- Existing Previews -->
                                <?php if (count($previews) > 0): ?>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                        <?php foreach ($previews as $prev): ?>
                                            <div class="relative group border rounded-lg overflow-hidden bg-white">
                                                <img src="../<?php echo $prev['image_path']; ?>" class="w-full h-24 object-cover">
                                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                                    <label class="flex items-center gap-2 text-white text-xs cursor-pointer select-none">
                                                        <input type="checkbox" name="delete_previews[]" value="<?php echo $prev['id']; ?>" class="w-4 h-4 text-red-600 rounded cursor-pointer">
                                                        Устгах
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="text-xs text-red-500 mb-4">* Устгах зургуудыг чагтлаад "Хадгалах" дарна уу.</p>
                                <?php endif; ?>

                                <!-- Upload New -->
                                <div class="border-2 border-dashed border-slate-300 rounded-lg p-4 bg-slate-50">
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Шинээр зураг нэмэх</label>
                                    <input type="file" name="previews[]" multiple class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept="image/*">
                                </div>
                            </div>
                            
                            <hr class="border-slate-100">

                            <div class="flex justify-end gap-3 pt-2">
                                <a href="kids.php" class="px-6 py-2.5 bg-white border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition">Болих</a>
                                <button type="button" id="saveBtn" onclick="handleFormSubmit()" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/30 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all">
                                    Хадгалах
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- SCRIPTS -->
    <script>
        const currentUserId = <?php echo json_encode($user_id); ?>;

        // CHUNK UPLOAD LOGIC
        let selectedFile = null;
        const CHUNK_SIZE = 2 * 1024 * 1024; 

        function handleFileSelect(input) {
            if (input.files && input.files[0]) {
                selectedFile = input.files[0];
                document.getElementById('drop-zone-text').textContent = "Сонгогдсон: " + selectedFile.name;
                document.getElementById('drop-zone').classList.add('bg-indigo-50', 'border-indigo-300');
            }
        }

        async function handleFormSubmit() {
            const title = document.querySelector('input[name="title"]').value;
            
            if (!title) { alert("Гарчиг заавал оруулна уу."); return; }

            const btn = document.getElementById('saveBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Уншиж байна...';

            // 1. If New File Selected -> Upload it first
            if (selectedFile) {
                document.getElementById('progress-container').classList.remove('hidden');
                document.getElementById('progress-filename').textContent = selectedFile.name;
                
                const totalChunks = Math.ceil(selectedFile.size / CHUNK_SIZE);
                const fileId = Date.now().toString(36) + Math.random().toString(36).substr(2);

                try {
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

                        await uploadChunk(formData, i, totalChunks);
                    }
                } catch (error) {
                    alert("Файл хуулахад алдаа: " + error);
                    btn.disabled = false;
                    btn.textContent = "Хадгалах";
                    return;
                }
            } else {
                // No new file, just submit form
                document.getElementById('editForm').submit();
            }
        }

        function uploadChunk(formData, index, total) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                // Path needs to go up to api folder
                xhr.open('POST', '../api/chunk_upload.php', true);
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.status === 'chunk_uploaded' || response.status === 'done') {
                                const percent = Math.round(((index + 1) / total) * 100);
                                document.getElementById('progress-bar').style.width = percent + '%';
                                document.getElementById('progress-percent').textContent = percent + '%';

                                if (response.status === 'done') {
                                    document.getElementById('uploaded_temp_path').value = response.tempPath;
                                    document.getElementById('uploaded_original_name').value = response.originalName;
                                    // Show processing overlay and submit
                                    document.getElementById('processingOverlay').classList.remove('hidden');
                                    document.getElementById('editForm').submit();
                                }
                                resolve();
                            } else {
                                reject(response.message);
                            }
                        } catch (e) {
                            reject("JSON Error");
                        }
                    } else {
                        reject("HTTP Error");
                    }
                };
                xhr.onerror = function() { reject("Network Error"); };
                xhr.send(formData);
            });
        }
    </script>
</body>
</html>