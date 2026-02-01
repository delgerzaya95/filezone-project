<?php
session_start();

// 1. Include paths
require_once '../includes/db.php';
require_once '../api/brevo_email.php';
require_once '../api/virustotal_scanner.php';

// Нэвтрээгүй бол нэвтрэх хуудас руу
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$file_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

// --- USER INFO FETCHING ---
$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql_user);
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

$username = $user_data['username'] ?? 'User';
$email = $user_data['email'] ?? '';

// Avatar Logic
$db_avatar = $user_data['avatar_url'];
$avatar = "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=random&color=fff";
if (!empty($db_avatar)) {
    if (strpos($db_avatar, 'http') === 0) {
        $avatar = $db_avatar;
    } else {
        if (file_exists('../' . $db_avatar)) {
            $avatar = '../' . $db_avatar;
        }
    }
}

// 2. Файлын мэдээллийг татах
$sql = "SELECT * FROM files WHERE id = ? AND user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$file_id, $user_id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    die("Файл олдсонгүй эсвэл танд засах эрх байхгүй.");
}

// 3. Ангиллуудыг татах
$categories = $pdo->query("SELECT * FROM categories WHERE type = 'file' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$subcategories = $pdo->query("SELECT * FROM subcategories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$child_categories = $pdo->query("SELECT * FROM child_category ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// 4. Одоо байгаа зургуудыг татах
$previews = $pdo->prepare("SELECT * FROM file_previews WHERE file_id = ? ORDER BY order_index ASC");
$previews->execute([$file_id]);
$existing_previews = $previews->fetchAll(PDO::FETCH_ASSOC);

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

// 5. UPDATE PROCESS
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $title = trim($_POST['title']);
    $priceRaw = str_replace(',', '', $_POST['price']);
    $price = floatval($priceRaw);
    
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']);
    $subcategory_id = intval($_POST['subcategory_id']);
    $child_category_id = isset($_POST['child_category_id']) && !empty($_POST['child_category_id']) ? intval($_POST['child_category_id']) : null;

    // Type of upload (file replace or link replace)
    $upload_type = isset($_POST['upload_type']) ? $_POST['upload_type'] : ''; 

    // Chunk Upload-аас ирсэн шинэ файлын зам
    $uploaded_temp_path = isset($_POST['uploaded_temp_path']) ? $_POST['uploaded_temp_path'] : '';
    $uploaded_original_name = isset($_POST['uploaded_original_name']) ? $_POST['uploaded_original_name'] : '';
    
    // External Link
    $external_link = isset($_POST['external_link']) ? trim($_POST['external_link']) : '';

    if (empty($title)) {
        $error = "Файлын гарчиг хоосон байна.";
    } else {
        try {
            $pdo->beginTransaction();
            $new_file_uploaded = false;

            // --- 1. IMAGE DELETION LOGIC ---
            if (isset($_POST['delete_previews']) && is_array($_POST['delete_previews'])) {
                foreach ($_POST['delete_previews'] as $del_id) {
                    $del_id = intval($del_id);
                    $stmt_get_img = $pdo->prepare("SELECT preview_url FROM file_previews WHERE id = ? AND file_id = ?");
                    $stmt_get_img->execute([$del_id, $file_id]);
                    $img_row = $stmt_get_img->fetch(PDO::FETCH_ASSOC);
                    
                    if ($img_row) {
                        $path_to_del = '../' . $img_row['preview_url'];
                        if (file_exists($path_to_del)) {
                            unlink($path_to_del);
                        }
                        $stmt_del_img = $pdo->prepare("DELETE FROM file_previews WHERE id = ?");
                        $stmt_del_img->execute([$del_id]);
                    }
                }
            }

            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM file_previews WHERE file_id = ?");
            $stmt_count->execute([$file_id]);
            $current_image_count = $stmt_count->fetchColumn();


            // --- 2. MAIN FILE / LINK UPDATE LOGIC ---
            
            // CASE A: Changing to External Link
            if ($upload_type === 'link' && !empty($external_link)) {
                if (!filter_var($external_link, FILTER_VALIDATE_URL)) {
                    throw new Exception("Зөв гадаад холбоос (URL) оруулна уу.");
                }

                // If switching from File to Link, delete old file
                if ($file['is_external'] == 0 && !empty($file['file_url'])) {
                    $old_file_path = '../' . $file['file_url'];
                    if (file_exists($old_file_path) && is_file($old_file_path)) {
                        unlink($old_file_path);
                    }
                }

                $update_sql = "UPDATE files SET is_external = 1, external_link = ?, file_url = NULL, file_size = 0, file_type = 'link', status = 'pending', upload_date = NOW() WHERE id = ?";
                $stmt_link = $pdo->prepare($update_sql);
                $stmt_link->execute([$external_link, $file_id]);
                
                $new_file_uploaded = true; // Trigger admin notification
            } 
            // CASE B: Changing to File (Chunk Upload)
            elseif ($upload_type === 'file' && !empty($uploaded_temp_path)) {
                $new_file_uploaded = true;
                
                $real_temp_path = str_replace('../', '', $uploaded_temp_path);
                $backend_temp_path = '../' . $real_temp_path; 

                if (!file_exists($backend_temp_path)) {
                     throw new Exception("Шинэ файл олдсонгүй. Дахин хуулна уу.");
                }

                // Virus Scan
                $file_ext = strtolower(pathinfo($uploaded_original_name, PATHINFO_EXTENSION));
                if (in_array($file_ext, ['exe', 'zip', 'rar'])) {
                    $scanResult = scanFileWithVirusTotal($backend_temp_path);
                    if (!$scanResult['safe']) {
                        unlink($backend_temp_path);
                        throw new Exception("Аюулгүй байдлын шалгалт: " . $scanResult['message']);
                    }
                }

                // Move File
                $upload_dir = "../uploads/files/{$user_id}/{$file_id}/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $final_filename = basename($uploaded_original_name);
                $final_filename = str_replace(' ', '_', $final_filename);
                $final_path = $upload_dir . $final_filename;
                
                // Old file delete (if exists)
                if (!empty($file['file_url'])) {
                    $old_file_path = '../' . $file['file_url'];
                    if (file_exists($old_file_path) && is_file($old_file_path)) {
                        unlink($old_file_path);
                    }
                }

                if (rename($backend_temp_path, $final_path)) {
                    $db_file_path = "uploads/files/{$user_id}/{$file_id}/" . $final_filename;
                    $file_size = filesize($final_path);
                    
                    // STATUS UPDATE: Шинэ файл орсон тул 'pending' болгоно
                    $file_update_sql = "UPDATE files SET is_external = 0, external_link = NULL, file_url = ?, file_type = ?, file_size = ?, status = 'pending', upload_date = NOW() WHERE id = ?";
                    $stmt_f = $pdo->prepare($file_update_sql);
                    $stmt_f->execute([$db_file_path, $file_ext, $file_size, $file_id]);
                } else {
                    throw new Exception("Файл зөөхөд алдаа гарлаа.");
                }
            }

            // --- 3. GENERAL INFO UPDATE ---
            $update_sql = "UPDATE files SET title = ?, price = ?, description = ?, category_id = ?, subcategory_id = ?, child_category_id = ? WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$title, $price, $description, $category_id, $subcategory_id, $child_category_id, $file_id]);

            // --- 4. NEW IMAGES UPLOAD ---
            if (isset($_FILES['cover_image']) && !empty($_FILES['cover_image']['name'][0])) {
                $preview_dir = "../uploads/files/{$user_id}/{$file_id}/previews/";
                if (!is_dir($preview_dir)) {
                    mkdir($preview_dir, 0777, true);
                }

                $img_files = reArrayFiles($_FILES['cover_image']);
                
                foreach ($img_files as $key => $img) {
                    if ($current_image_count >= 5) break; 

                    if ($img['error'] === UPLOAD_ERR_OK) {
                        $cover_new_name = uniqid() . '_preview_' . ($current_image_count + 1) . '.' . pathinfo($img['name'], PATHINFO_EXTENSION);
                        $cover_path = $preview_dir . $cover_new_name;
                        
                        if (move_uploaded_file($img['tmp_name'], $cover_path)) {
                            $db_cover_path = "uploads/files/{$user_id}/{$file_id}/previews/" . $cover_new_name;
                            $prev_stmt = $pdo->prepare("INSERT INTO file_previews (file_id, preview_url, order_index) VALUES (?, ?, ?)");
                            $prev_stmt->execute([$file_id, $db_cover_path, $current_image_count + 1]);
                            $current_image_count++;
                        }
                    }
                }
            }

            $pdo->commit();
            
            // Send Admin Notification if File/Link changed
            if ($new_file_uploaded) {
                $changeType = ($upload_type === 'link') ? "Гадаад холбоос шинэчлэгдсэн" : "Файл шинэчлэгдсэн";
                sendEmailNotification($title . " (" . $changeType . " - Шалгах шаардлагатай)");
                $message = "Амжилттай! Файлын өгөгдөл шинэчлэгдлээ. Админ шалгасны дараа дахин нийтлэгдэх болно.";
            } else {
                $message = "Мэдээлэл амжилттай хадгалагдлаа.";
            }
            
            // Refresh Data
            $stmt->execute([$file_id, $user_id]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            $previews->execute([$file_id]);
            $existing_previews = $previews->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Алдаа гарлаа: " . $e->getMessage();
        }
    }
}

// HEADER
$pageTitle = "Файл засах - " . htmlspecialchars($file['title']);
include 'header.php'; 
?>

<!-- TinyMCE Loader -->
<?php include '../api/tinymce_loader.php'; ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
      initFilezoneEditor('#description');
  });
</script>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0 relative">
        
        <!-- Processing Overlay -->
        <div id="processingOverlay" class="fixed inset-0 bg-white/90 backdrop-blur-sm z-50 hidden flex flex-col items-center justify-center" style="z-index: 9999;">
            <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-brand-600 mb-4"></div>
            <h2 class="text-xl font-bold text-gray-800 animate-pulse">Боловсруулж байна...</h2>
            <p class="text-gray-500 mt-2 text-sm">Өгөгдлийг хадгалж байна.</p>
        </div>

        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Файл засах</h1>
                <p class="text-sm text-gray-500">ID: #<?php echo $file['id']; ?></p>
            </div>
            <a href="my_files.php" class="text-sm text-gray-600 hover:text-blue-600 font-medium">
                <i class="fas fa-arrow-left mr-1"></i> Буцах
            </a>
        </div>

        <?php if($message): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative">
                <strong class="font-bold">Амжилттай!</strong> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative">
                <strong class="font-bold">Алдаа!</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                
                <form action="" method="POST" enctype="multipart/form-data" id="editForm">
                    
                    <!-- Chunk Upload Fields (Hidden) -->
                    <input type="hidden" name="uploaded_temp_path" id="uploaded_temp_path">
                    <input type="hidden" name="uploaded_original_name" id="uploaded_original_name">
                    
                    <!-- Deleted Images Container -->
                    <div id="deleted-images-container"></div>

                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <div class="space-y-6">
                            
                            <!-- Current File Info -->
                            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                                        <i class="fas <?php echo ($file['is_external'] == 1) ? 'fa-link' : 'fa-file-alt'; ?> text-lg"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-gray-900">Одоогийн файл / Холбоос</p>
                                        <?php if($file['is_external'] == 1): ?>
                                            <a href="<?php echo htmlspecialchars($file['external_link']); ?>" target="_blank" class="text-xs text-blue-600 hover:underline break-all block truncate max-w-xs">
                                                <?php echo htmlspecialchars($file['external_link']); ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="../<?php echo $file['file_url']; ?>" target="_blank" class="text-xs text-blue-600 hover:underline break-all block truncate max-w-xs">
                                                <?php echo basename($file['file_url']); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <?php if($file['is_external'] == 0): ?>
                                    <span class="text-xs font-mono bg-white px-2 py-1 rounded border">
                                        <?php echo round($file['file_size'] / 1024 / 1024, 2); ?> MB
                                    </span>
                                    <?php endif; ?>
                                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full 
                                        <?php echo $file['status'] === 'approved' ? 'bg-green-100 text-green-700' : ($file['status'] === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'); ?>">
                                        <?php echo $file['status']; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- FILE REPLACE ZONE (TOGGLE) -->
                            <div class="border border-gray-200 rounded-xl p-4">
                                <h3 class="text-sm font-bold text-gray-900 mb-3">Файл солих / Холбоос өөрчлөх</h3>
                                
                                <div class="flex gap-4 mb-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="upload_type" value="none" checked onclick="toggleUploadType()" class="text-blue-600">
                                        <span class="text-sm text-gray-700">Өөрчлөхгүй</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="upload_type" value="file" onclick="toggleUploadType()" class="text-blue-600">
                                        <span class="text-sm text-gray-700">Файл хуулах</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="upload_type" value="link" onclick="toggleUploadType()" class="text-blue-600">
                                        <span class="text-sm text-gray-700">Гадаад холбоос</span>
                                    </label>
                                </div>

                                <!-- 1. File Upload Area -->
                                <div id="file-upload-area" class="hidden">
                                    <div class="bg-white border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:bg-gray-50 transition cursor-pointer group relative" id="drop-zone">
                                        <input type="file" id="file-upload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="handleFileSelect(this)">
                                        <div class="w-10 h-10 bg-gray-100 text-gray-500 rounded-full flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition duration-300">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <p class="text-sm text-gray-600" id="drop-zone-text">Шинэ файл сонгох</p>
                                    </div>
                                    <!-- Progress Bar -->
                                    <div id="progress-container" class="hidden mt-3 bg-white p-3 rounded-lg border border-gray-200">
                                        <div class="flex justify-between mb-1">
                                            <span class="text-xs font-medium text-gray-700" id="progress-filename">File...</span>
                                            <span class="text-xs font-bold text-brand-600" id="progress-percent">0%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                        </div>
                                        <p class="text-xs text-gray-400 mt-1 text-center" id="progress-status">Хуулж байна...</p>
                                    </div>
                                </div>

                                <!-- 2. External Link Area -->
                                <div id="link-upload-area" class="hidden">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Шинэ холбоос (URL)</label>
                                    <input type="url" name="external_link" id="external_link" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none" placeholder="https://drive.google.com/...">
                                </div>

                                <p class="text-xs text-orange-500 mt-2 hidden" id="status-warning">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Анхаар: Файл эсвэл холбоос өөрчилбөл статус "Pending" болно.
                                </p>
                            </div>

                            <hr class="border-gray-100">

                            <!-- Basic Info -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Файлын гарчиг <span class="text-red-500">*</span></label>
                                <input type="text" name="title" value="<?php echo htmlspecialchars($file['title']); ?>" required class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            </div>

                            <!-- Categories Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Ангилал</label>
                                    <select name="category_id" id="categorySelect" required onchange="updateSubcategories()" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $file['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Дэд ангилал</label>
                                    <select name="subcategory_id" id="subcategorySelect" required onchange="updateChildCategories()" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                                        <?php foreach($subcategories as $sub): ?>
                                            <?php if($sub['category_id'] == $file['category_id']): ?>
                                                <option value="<?php echo $sub['id']; ?>" <?php echo $sub['id'] == $file['subcategory_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($sub['name']); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Дэдийн дэд ангилал</label>
                                    <select name="child_category_id" id="childCategorySelect" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                                        <option value="">Сонгоно уу...</option>
                                        <?php foreach($child_categories as $child): ?>
                                            <?php if($child['subcategory_id'] == $file['subcategory_id']): ?>
                                                <option value="<?php echo $child['id']; ?>" <?php echo $child['id'] == $file['child_category_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($child['name']); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Үнэ (MNT)</label>
                                <div class="relative">
                                    <input type="text" name="price" id="priceInput" value="<?php echo number_format($file['price']); ?>" required class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none" oninput="formatPrice(this)">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-xs">₮</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Тайлбар</label>
                                <textarea name="description" id="description" rows="10" class="w-full border border-gray-300 rounded-lg p-3 text-sm"><?php echo htmlspecialchars($file['description']); ?></textarea>
                            </div>

                            <!-- Images -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Зураг (Max 5)
                                    <span id="image-counter" class="text-xs font-normal text-gray-500 ml-1"><?php echo count($existing_previews); ?>/5</span>
                                </label>
                                
                                <!-- Existing Images List -->
                                <div class="grid grid-cols-5 gap-3 mb-4" id="existing-images-grid">
                                    <?php foreach($existing_previews as $prev): ?>
                                        <div class="relative group aspect-square rounded-lg overflow-hidden border border-gray-200">
                                            <img src="../<?php echo $prev['preview_url']; ?>" class="w-full h-full object-cover">
                                            <!-- Delete Button -->
                                            <button type="button" onclick="deleteExistingImage(<?php echo $prev['id']; ?>, this)" class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition shadow-sm" title="Устгах">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Upload New Images -->
                                <div id="new-image-upload-area" class="<?php echo count($existing_previews) >= 5 ? 'hidden' : ''; ?>">
                                    <div class="space-y-3">
                                        <input type="file" name="cover_image[]" id="cover-upload-storage" class="hidden" multiple>
                                        <input type="file" id="cover-upload-picker" class="hidden" accept="image/*" multiple onchange="handleCoverSelect(this)">
                                        <label for="cover-upload-picker" class="inline-flex items-center gap-2 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-50 cursor-pointer shadow-sm">
                                            <i class="fas fa-plus"></i> Шинэ зураг нэмэх
                                        </label>
                                        <div id="preview-gallery" class="grid grid-cols-2 md:grid-cols-5 gap-3"></div>
                                    </div>
                                </div>
                                <p id="max-image-warning" class="text-xs text-orange-500 mt-2 <?php echo count($existing_previews) >= 5 ? '' : 'hidden'; ?>">Зургийн хязгаарт хүрсэн байна.</p>
                            </div>

                            <div class="pt-4 flex items-center justify-end gap-3">
                                <a href="my_files.php" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Цуцлах</a>
                                <button type="button" id="saveBtn" onclick="handleFormSubmit()" class="px-6 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-all">
                                    Хадгалах
                                </button>
                            </div>

                        </div>
                    </div>
                </form>
            </div>
            
            <div class="lg:col-span-1">
                <div class="bg-yellow-50 border border-yellow-100 rounded-2xl p-6 sticky top-24">
                    <h3 class="font-bold text-yellow-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-lightbulb"></i> Санамж
                    </h3>
                    <ul class="space-y-3 text-sm text-yellow-800">
                        <li class="flex gap-2">
                            <i class="fas fa-info-circle mt-0.5 text-yellow-600"></i>
                            <span>Файл эсвэл холбоосыг өөрчилбөл Админ дахин баталгаажуулна.</span>
                        </li>
                        <li class="flex gap-2">
                            <i class="fas fa-check-circle mt-0.5 text-yellow-600"></i>
                            <span>Зөвхөн тайлбар, үнэ, зураг засахад статус өөрчлөгдөхгүй.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <?php include '../includes/footer.php'; ?>
    </main>
</div>

<!-- JAVASCRIPT LOGIC -->
<script>
    // Data for dynamic select
    const subcats = <?php echo json_encode($subcategories); ?>;
    const childCats = <?php echo json_encode($child_categories); ?>;
    const currentUserId = <?php echo json_encode($user_id); ?>;
    let currentImageCount = <?php echo count($existing_previews); ?>;

    // Toggle Upload Types
    function toggleUploadType() {
        const type = document.querySelector('input[name="upload_type"]:checked').value;
        const fileArea = document.getElementById('file-upload-area');
        const linkArea = document.getElementById('link-upload-area');
        const warning = document.getElementById('status-warning');

        if (type === 'file') {
            fileArea.classList.remove('hidden');
            linkArea.classList.add('hidden');
            warning.classList.remove('hidden');
        } else if (type === 'link') {
            fileArea.classList.add('hidden');
            linkArea.classList.remove('hidden');
            warning.classList.remove('hidden');
        } else {
            fileArea.classList.add('hidden');
            linkArea.classList.add('hidden');
            warning.classList.add('hidden');
        }
    }

    // Price Formatting
    function formatPrice(input) {
        let value = input.value.replace(/\D/g, "");
        if (value === "") { input.value = ""; return; }
        let numValue = parseInt(value, 10);
        input.value = numValue.toLocaleString('en-US');
    }

    // Category Logic
    function updateSubcategories() {
        const catSelect = document.getElementById('categorySelect');
        const subSelect = document.getElementById('subcategorySelect');
        const childSelect = document.getElementById('childCategorySelect');
        
        const selectedCatId = catSelect.value;
        subSelect.innerHTML = '<option value="">Сонгоно уу...</option>';
        childSelect.innerHTML = '<option value="">Сонгоно уу...</option>';
        
        if (selectedCatId) {
            const filtered = subcats.filter(s => s.category_id == selectedCatId);
            filtered.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name;
                subSelect.appendChild(opt);
            });
        }
    }

    function updateChildCategories() {
        const subSelect = document.getElementById('subcategorySelect');
        const childSelect = document.getElementById('childCategorySelect');
        const selectedSubId = subSelect.value;
        childSelect.innerHTML = '<option value="">Сонгоно уу...</option>';
        
        if (selectedSubId) {
            const filtered = childCats.filter(c => c.subcategory_id == selectedSubId);
            filtered.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name;
                childSelect.appendChild(opt);
            });
        }
    }

    // Delete Existing Image Logic
    function deleteExistingImage(id, btn) {
        if(!confirm('Энэ зургийг устгах уу? (Хадгалах товч дарсны дараа устана)')) return;
        
        // Add hidden input to form
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_previews[]';
        input.value = id;
        document.getElementById('deleted-images-container').appendChild(input);
        
        // Remove from view
        const container = btn.closest('.relative');
        container.remove();
        
        // Update counters
        currentImageCount--;
        document.getElementById('image-counter').innerText = currentImageCount + '/5';
        
        if (currentImageCount < 5) {
            document.getElementById('new-image-upload-area').classList.remove('hidden');
            document.getElementById('max-image-warning').classList.add('hidden');
        }
    }

    // CHUNK UPLOAD LOGIC
    let selectedFile = null;
    const CHUNK_SIZE = 2 * 1024 * 1024; 

    function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            selectedFile = input.files[0];
            document.getElementById('drop-zone-text').textContent = "Сонгогдсон: " + selectedFile.name;
            document.getElementById('drop-zone').classList.add('bg-blue-50', 'border-blue-300');
        }
    }

    async function handleFormSubmit() {
        const title = document.querySelector('input[name="title"]').value;
        const price = document.getElementById('priceInput').value;
        const uploadType = document.querySelector('input[name="upload_type"]:checked').value;
        
        if (!title || !price) { alert("Гарчиг болон үнийг оруулна уу."); return; }

        if (uploadType === 'link') {
            const link = document.getElementById('external_link').value;
            if (!link) { alert("Холбоосоо оруулна уу."); return; }
        }

        const btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Уншиж байна...';

        // 1. If File Selected (and upload type is file) -> Upload it first
        if (uploadType === 'file' && selectedFile) {
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
                    formData.append('user_id', currentUserId); // Auth check

                    await uploadChunk(formData, i, totalChunks);
                }
            } catch (error) {
                alert("Файл хуулахад алдаа: " + error);
                btn.disabled = false;
                btn.textContent = "Хадгалах";
                return;
            }
        } else {
            // No new file or link change, just submit form
            document.getElementById('processingOverlay').classList.remove('hidden');
            document.getElementById('editForm').submit();
        }
    }

    function uploadChunk(formData, index, total) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
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

    // Cover Image Logic
    let uploadedImages = [];
    function handleCoverSelect(input) {
        const files = Array.from(input.files);
        files.forEach(file => uploadedImages.push(file));
        
        renderGallery();
        updateInputFiles();
        
        const total = currentImageCount + uploadedImages.length;
        if (total >= 5) {
             document.getElementById('max-image-warning').classList.remove('hidden');
        }
        
        input.value = ''; 
    }
    
    function renderGallery() {
        const gallery = document.getElementById('preview-gallery');
        gallery.innerHTML = '';
        uploadedImages.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative w-full h-24 bg-gray-100 rounded-lg overflow-hidden group';
                div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">
                                 <button type="button" onclick="removeNewImage(${index})" class="absolute top-1 right-1 bg-red-500 text-white w-5 h-5 rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition">&times;</button>`;
                gallery.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
    }
    
    function removeNewImage(index) {
        uploadedImages.splice(index, 1);
        renderGallery();
        updateInputFiles();
    }

    function updateInputFiles() {
        const dataTransfer = new DataTransfer();
        uploadedImages.forEach(file => { dataTransfer.items.add(file); });
        document.getElementById('cover-upload-storage').files = dataTransfer.files;
    }
</script>

</body>
</html>