<?php 
// Session эхлүүлэх
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database холболт
require_once 'includes/db.php';

// Нэвтрээгүй бол login хуудас руу шилжүүлэх
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Server-side settings for large file uploads (try to override if allowed)
@ini_set('upload_max_filesize', '150M');
@ini_set('post_max_size', '160M');
@ini_set('max_execution_time', '300'); // 5 minutes

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// GET параметрээр амжилттай болсон мессежийг харуулах (PRG Pattern)
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "Файл амжилттай илгээгдлээ! Админ шалгасны дараа нийтлэгдэх болно.";
}

// --- HELPER FUNCTIONS ---

// 1. VirusTotal API Function
function scanFileWithVirusTotal($filePath) {
    // АНХААР: Энд өөрийн жинхэнэ VirusTotal API түлхүүрээ оруулна уу
    $apiKey = 'cdc146ebcef3bd0c72509bf392d8ff28490bbf583073ecfcf894cb830f842765'; 
    $fileHash = hash_file('sha256', $filePath);
    
    $url = "https://www.virustotal.com/api/v3/files/" . $fileHash;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-apikey: " . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $result = json_decode($response, true);
        $stats = $result['data']['attributes']['last_analysis_stats'];
        if ($stats['malicious'] > 0) {
            return ['safe' => false, 'message' => 'Вирус илэрлээ! (Detection: ' . $stats['malicious'] . ')'];
        }
        return ['safe' => true, 'message' => 'Цэвэр файл.'];
    } elseif ($httpCode == 404) {
        return ['safe' => true, 'message' => 'Шинэ файл (VirusTotal дээр бүртгэлгүй).'];
    } else {
        // API limit хүрсэн эсвэл бусад алдаа гарвал зөвшөөрөх (эсвэл хориглох таны сонголт)
        return ['safe' => true, 'message' => 'API шалгалт алгаслаа.'];
    }
}

// 2. Brevo Email Function (Server-Side)
function sendEmailNotification($fileName) {
    // АНХААР: Энд өөрийн жинхэнэ Brevo API түлхүүрээ оруулна уу
    $apiKey = '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'; 
    $url = 'https://api.brevo.com/v3/smtp/email';

    // Анхаарах: no-reply@filezone.mn хаяг Brevo дээр баталгаажсан байх ёстой
    $data = [
        'sender' => ['name' => 'Filezone System', 'email' => 'no-reply@filezone.mn'],
        'to' => [['email' => 'info@filezone.mn', 'name' => 'Admin']],
        'subject' => 'Шинэ файл upload хийгдлээ!',
        'htmlContent' => "
            <html>
                <body>
                    <h2>Шинэ файл системд нэмэгдлээ.</h2>
                    <p>Сайн байна уу,</p>
                    <p>Filezone.mn платформ дээр шинэ файл амжилттай upload хийгдлээ.</p>
                    <p><strong>Файлын нэр:</strong> " . htmlspecialchars($fileName) . "</p>
                    <p>Та админ хэсгээр нэвтэрч, файлыг шалгаж баталгаажуулна уу.</p>
                    <br>
                    <a href='https://filezone.mn/admin/login.php' style='background-color: #4F46E5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Админ руу нэвтрэх</a>
                </body>
            </html>
        "
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'api-key: ' . $apiKey,
        'content-type: application/json'
    ]);

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        error_log("Brevo Curl Error: " . curl_error($ch));
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode >= 400) {
            error_log("Brevo API Error ($httpCode): " . $response);
        }
    }
    curl_close($ch);
}

// Function to reorganize $_FILES array
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
    $child_category_id = isset($_POST['child_category_id']) ? intval($_POST['child_category_id']) : null;
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);

    if (empty($title) || empty($category_id) || empty($subcategory_id) || !isset($_FILES['main_file']) || $_FILES['main_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Файлын гарчиг, ангилал (дэд ангилал) болон үндсэн файлыг заавал оруулна уу.";
        // Check for specific upload errors
        if (isset($_FILES['main_file']) && $_FILES['main_file']['error'] == UPLOAD_ERR_INI_SIZE) {
            $error = "Файлын хэмжээ хэт том байна (Серверийн хязгаар).";
        }
    } else {
        try {
            $maxFileSize = 150 * 1024 * 1024; // 150MB
            if ($_FILES['main_file']['size'] > $maxFileSize) {
                throw new Exception("Файлын хэмжээ хэтэрсэн байна. (Дээд хэмжээ: 150MB)");
            }

            $pdo->beginTransaction();

            $file_tmp = $_FILES['main_file']['tmp_name'];
            $file_name = $_FILES['main_file']['name'];
            $file_size = $_FILES['main_file']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_types = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','jpg','png','zip','rar','exe','mp3','mp4'];
            $file_type_db = in_array($file_ext, $allowed_types) ? $file_ext : 'other';

            // VirusTotal Check (зөвхөн сэжигтэй өргөтгөлүүдэд)
            if (in_array($file_ext, ['exe', 'zip', 'rar'])) {
                $scanResult = scanFileWithVirusTotal($file_tmp);
                if (!$scanResult['safe']) {
                    throw new Exception("Аюулгүй байдлын шалгалт: " . $scanResult['message']);
                }
            }

            // 1. Insert File Record with Category IDs
            $stmt = $pdo->prepare("INSERT INTO files (user_id, category_id, subcategory_id, child_category_id, title, description, file_type, file_size, price, status, upload_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$user_id, $category_id, $subcategory_id, $child_category_id ?: null, $title, $description, $file_type_db, $file_size, $price]);
            $file_id = $pdo->lastInsertId();

            // 2. Create Directory & Move File
            // Зам: uploads/files/{user_id}/{file_id}/
            $upload_dir = "uploads/files/{$user_id}/{$file_id}/";
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception("Үндсэн файлын хавтас үүсгэж чадсангүй.");
                }
            }

            $new_filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file_name);
            $file_path = $upload_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $file_path)) {
                $update_stmt = $pdo->prepare("UPDATE files SET file_url = ? WHERE id = ?");
                $update_stmt->execute([$file_path, $file_id]);
            } else {
                throw new Exception("Файл хуулахад алдаа гарлаа.");
            }

            // 3. Handle Cover Images (Multiple & Reorder)
            // Зам: uploads/files/{user_id}/{file_id}/previews/
            // Note: We use $_FILES['cover_image'] directly with reArrayFiles
            if (isset($_FILES['cover_image']) && !empty($_FILES['cover_image']['name'][0])) {
                
                $preview_dir = $upload_dir . "previews/"; // folder доторх folder
                if (!is_dir($preview_dir)) {
                    if (!mkdir($preview_dir, 0777, true)) {
                        error_log("Failed to create preview directory: " . $preview_dir);
                    }
                }

                $img_files = reArrayFiles($_FILES['cover_image']);
                $uploaded_count = 0;

                foreach ($img_files as $key => $img) {
                    if ($uploaded_count >= 5) break; 

                    if ($img['error'] === UPLOAD_ERR_OK) {
                        $cover_new_name = uniqid() . '_preview_' . $key . '.' . pathinfo($img['name'], PATHINFO_EXTENSION);
                        $cover_path = $preview_dir . $cover_new_name;

                        if (move_uploaded_file($img['tmp_name'], $cover_path)) {
                            $order_index = $key + 1; // 1, 2, 3...
                            $prev_stmt = $pdo->prepare("INSERT INTO file_previews (file_id, preview_url, order_index) VALUES (?, ?, ?)");
                            $prev_stmt->execute([$file_id, $cover_path, $order_index]);
                            $uploaded_count++;
                        } else {
                            error_log("Failed to move preview file: " . $img['tmp_name'] . " to " . $cover_path);
                        }
                    } else {
                        error_log("Error in file upload array: " . $img['error']);
                    }
                }
            } else {
                error_log("No cover images found in POST request.");
            }

            $pdo->commit();
            
            // Send Email Notification (Server-Side)
            sendEmailNotification($title);
            
            // Post/Redirect/Get (PRG) Pattern - Refresh хийхэд дахин submit хийгдэхээс сэргийлнэ
            header("Location: upload.php?success=1");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Алдаа гарлаа: " . $e->getMessage();
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
<script src="https://cdn.tiny.cloud/1/ynh1rnqvsvfamly2llpevnxusnk5dr6fpbxh72ajfvferijv/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
<script>
  tinymce.init({
    selector: '#description',
    height: 300,
    plugins: 'emoticons lists link',
    toolbar: 'undo redo | bold italic underline | bullist numlist | emoticons | link',
    menubar: false,
    statusbar: false,
    content_style: 'body { font-family:Inter,sans-serif; font-size:14px }'
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
            <a href="profile.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-chart-pie w-5 text-center"></i> Хяналтын самбар
            </a>
            <a href="my-files.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-folder w-5 text-center"></i> Миний файлууд
            </a>
            <a href="my-services.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-briefcase w-5 text-center"></i> Миний үйлчилгээ
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0 relative">
        
        <!-- Loading Overlay (Hidden by default) -->
        <div id="loadingOverlay" class="fixed inset-0 bg-white/80 backdrop-blur-sm z-50 hidden flex flex-col items-center justify-center">
            <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-brand-600 mb-4"></div>
            <h2 class="text-xl font-bold text-gray-800 animate-pulse">Уншиж байна...</h2>
            <p class="text-gray-500 mt-2 text-sm">Файлыг сервер рүү хуулж байна, түр хүлээнэ үү.</p>
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
                
                <form action="upload.php" method="POST" enctype="multipart/form-data" id="uploadForm" onsubmit="return handleFormSubmit()">
                    
                    <div class="bg-white border-2 border-dashed border-brand-300 rounded-2xl p-8 text-center hover:bg-brand-50/50 transition cursor-pointer group relative mb-6" id="drop-zone">
                        <input type="file" name="main_file" id="file-upload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="handleFileSelect(this)" required>
                        
                        <div class="w-16 h-16 bg-brand-100 text-brand-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition duration-300">
                            <i class="fas fa-cloud-upload-alt text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2" id="drop-zone-text">Файлаа энд чирж оруулах</h3>
                        <p class="text-sm text-gray-500 mb-4" id="drop-zone-sub">Эсвэл дарж файлаа сонгоно уу</p>
                        <p class="text-xs text-gray-400">Зөвшөөрөгдөх: PDF, DOCX, XLSX, PPTX, ZIP (Max 150MB)</p>
                    </div>

                    <div id="file-info" class="hidden bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6">
                        <div class="flex justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-file-alt text-brand-600"></i>
                                <span class="text-sm font-medium text-gray-700" id="file-name-display"></span>
                            </div>
                            <span class="text-sm font-bold text-green-600">Сонгогдсон</span>
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
                                    <input type="number" name="price" required min="0" step="100" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition" placeholder="5000">
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

                            <!-- Cover Image Upload (Multiple with Reorder) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Нүүр зураг (Max 5)
                                    <span class="text-xs text-gray-400 font-normal ml-2">Чирж дарааллыг солино уу</span>
                                </label>
                                
                                <div class="space-y-3">
                                    <!-- Hidden Input for Submission (The Storage) -->
                                    <input type="file" name="cover_image[]" id="cover-upload-storage" class="hidden" multiple>
                                    
                                    <!-- Visible Input for Selection (The Picker) -->
                                    <input type="file" id="cover-upload-picker" class="hidden" accept="image/*" multiple onchange="handleCoverSelect(this)">
                                    
                                    <!-- Button -->
                                    <label for="cover-upload-picker" class="inline-flex items-center gap-2 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-50 cursor-pointer shadow-sm">
                                        <i class="fas fa-plus"></i> Зураг нэмэх
                                    </label>

                                    <!-- Preview Container -->
                                    <div id="preview-gallery" class="grid grid-cols-2 md:grid-cols-5 gap-3">
                                        <!-- Previews will be injected here via JS -->
                                    </div>
                                    
                                    <p class="text-xs text-gray-500">Борлуулалтад нүүр зураг чухал нөлөөтэй. (JPG, PNG)</p>
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
                                <button type="submit" id="submitBtn" class="flex-1 bg-brand-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-brand-500/30 hover:bg-brand-700 hover:-translate-y-0.5 transition-all">
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
                    </ul>
                </div>
            </div>

        </div>

        <?php include 'includes/footer.php' ?>
    </main>
</div>

<!-- Terms Modal -->
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

<script>
    const subcats = <?php echo json_encode($subcats); ?>;
    const childCats = <?php echo json_encode($child_cats); ?>;

    // --- Terms Modal Logic ---
    function openTermsModal(e) {
        e.preventDefault();
        document.getElementById('termsModal').classList.remove('hidden');
    }
    function closeTermsModal() {
        document.getElementById('termsModal').classList.add('hidden');
    }

    // --- Loading UI Logic ---
    function handleFormSubmit() {
        // Show loading overlay
        document.getElementById('loadingOverlay').classList.remove('hidden');
        
        // Disable submit button to prevent double submission
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Уншиж байна...';
        
        return true; // Allow form submission
    }

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

    function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            var fileName = input.files[0].name;
            var fileSize = input.files[0].size;
            var maxFileSize = 150 * 1024 * 1024; // 150MB

            if (fileSize > maxFileSize) {
                alert("Файлын хэмжээ хэтэрсэн байна. (Дээд хэмжээ: 150MB)");
                input.value = ""; // Clear input
                document.getElementById('file-info').classList.add('hidden');
                document.getElementById('drop-zone').classList.remove('bg-green-50', 'border-green-300');
                document.getElementById('drop-zone-text').textContent = "Файлаа энд чирж оруулах";
                document.getElementById('drop-zone-text').classList.remove('text-green-700');
                return;
            }

            document.getElementById('file-name-display').textContent = fileName;
            document.getElementById('file-info').classList.remove('hidden');
            
            document.getElementById('drop-zone').classList.add('bg-green-50', 'border-green-300');
            document.getElementById('drop-zone-text').textContent = "Файл сонгогдсон";
            document.getElementById('drop-zone-text').classList.add('text-green-700');
        }
    }

    // MULTIPLE IMAGE HANDLING WITH REORDERING
    let uploadedImages = []; // Store File objects

    function handleCoverSelect(input) {
        const files = Array.from(input.files);
        if (uploadedImages.length + files.length > 5) {
            alert("Та дээд тал нь 5 зураг оруулах боломжтой.");
            return;
        }

        // Add new files to array
        files.forEach(file => {
            if (uploadedImages.length < 5) {
                uploadedImages.push(file);
            }
        });

        renderGallery();
        updateInputFiles(); // This updates the HIDDEN input used for submission
        
        // Clear the picker input so same file can be selected again
        input.value = ''; 
    }

    function renderGallery() {
        const gallery = document.getElementById('preview-gallery');
        gallery.innerHTML = '';

        uploadedImages.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative w-full h-24 bg-gray-100 rounded-lg border border-gray-200 overflow-hidden group cursor-move';
                div.draggable = true;
                div.dataset.index = index;

                // Drag Events
                div.addEventListener('dragstart', handleDragStart);
                div.addEventListener('dragover', handleDragOver);
                div.addEventListener('drop', handleDrop);
                div.addEventListener('dragend', handleDragEnd);

                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-full h-full object-cover';
                
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition';
                removeBtn.innerHTML = '&times;';
                removeBtn.onclick = function() { removeImage(index); };

                // Number badge
                const badge = document.createElement('span');
                badge.className = 'absolute bottom-1 left-1 bg-black/50 text-white text-[10px] px-1.5 py-0.5 rounded';
                badge.innerText = '#' + (index + 1);

                div.appendChild(img);
                div.appendChild(removeBtn);
                div.appendChild(badge);
                gallery.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
    }

    function removeImage(index) {
        uploadedImages.splice(index, 1);
        renderGallery();
        updateInputFiles();
    }

    function updateInputFiles() {
        const dataTransfer = new DataTransfer();
        uploadedImages.forEach(file => {
            dataTransfer.items.add(file);
        });
        // IMPORTANT: Updating the storage input which has name="cover_image[]"
        document.getElementById('cover-upload-storage').files = dataTransfer.files;
    }

    // Drag & Drop Logic
    let dragSrcIndex = null;

    function handleDragStart(e) {
        dragSrcIndex = parseInt(this.dataset.index);
        this.style.opacity = '0.4';
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.innerHTML);
    }

    function handleDragOver(e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        e.dataTransfer.dropEffect = 'move';
        return false;
    }

    function handleDrop(e) {
        e.preventDefault(); 
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        
        const targetDiv = e.target.closest('div[data-index]');
        if (!targetDiv) return false;

        const dropTargetIndex = parseInt(targetDiv.dataset.index);

        if (dragSrcIndex !== dropTargetIndex && dragSrcIndex !== null && !isNaN(dragSrcIndex)) {
            
            // Move item in array
            const itemToMove = uploadedImages[dragSrcIndex];
            
            // 1. Remove from old index
            uploadedImages.splice(dragSrcIndex, 1);
            
            // 2. Insert at new index
            uploadedImages.splice(dropTargetIndex, 0, itemToMove);
            
            renderGallery();
            updateInputFiles();
        }
        return false;
    }

    function handleDragEnd(e) {
        this.style.opacity = '1';
        let items = document.querySelectorAll('#preview-gallery > div');
        items.forEach(function (item) {
            item.style.opacity = '1';
        });
        dragSrcIndex = null;
    }
</script>