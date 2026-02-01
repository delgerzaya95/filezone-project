<?php
// add_service.php (Root directory) - Final Version with Profile Check & Email Notification

// Session эхлүүлэх
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
// Mэйл илгээх файлыг оруулж ирэх (Brevo API)
require_once 'api/brevo_email.php';

// Нэвтрээгүй бол login хуудас руу шилжүүлэх
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// --------------------------------------------------------------------------
// 1. ХЭРЭГЛЭГЧИЙН МЭДЭЭЛЛИЙГ ШАЛГАХ (Profile Check)
// --------------------------------------------------------------------------
$stmt_user = $pdo->prepare("SELECT full_name, phone, location FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$current_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

// Мэдээлэл дутуу эсэхийг шалгах (Нэр эсвэл Утас байхгүй бол дутуу гэж үзнэ)
$is_profile_incomplete = empty($current_user['full_name']) || empty($current_user['phone']);

// --------------------------------------------------------------------------
// 2. ХЭРЭГЛЭГЧИЙН МЭДЭЭЛЭЛ ШИНЭЧЛЭХ (Handle Profile Update)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $new_fullname = trim($_POST['full_name']);
    $new_phone = trim($_POST['phone']);
    $new_location = trim($_POST['location']); // Хаяг (Optional)

    if (empty($new_fullname) || empty($new_phone)) {
        $error = "Та жинхэнэ нэр болон утасны дугаараа заавал оруулах шаардлагатай.";
    } else {
        try {
            $update_sql = "UPDATE users SET full_name = ?, phone = ?, location = ? WHERE id = ?";
            $stmt_upd = $pdo->prepare($update_sql);
            $stmt_upd->execute([$new_fullname, $new_phone, $new_location, $user_id]);
            
            // Амжилттай болсон бол хуудсыг дахин ачаална (Ингэснээр үйлчилгээ нэмэх хэсэг нээгдэнэ)
            header("Location: add_service.php?profile_updated=1");
            exit;
        } catch (PDOException $e) {
            $error = "Мэдээлэл хадгалахад алдаа гарлаа: " . $e->getMessage();
        }
    }
}

// --------------------------------------------------------------------------
// 3. ҮЙЛЧИЛГЭЭ НЭМЭХ HANDLER (Зөвхөн мэдээлэл бүрэн үед ажиллана)
// --------------------------------------------------------------------------

// Helper function to re-array $_FILES
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

// Ангилал татах
try {
    $categories = $pdo->query("SELECT id, name FROM service_categories ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action']) && !$is_profile_incomplete) {
    // 1. Үндсэн мэдээлэл авах
    $title = trim($_POST['title']);
    $category_id = intval($_POST['category_id']);
    
    // Үнэ
    $price_min = floatval($_POST['price_min']);
    $price_max = !empty($_POST['price_max']) ? floatval($_POST['price_max']) : null;
    
    // Хугацаа & Засвар
    $delivery_time = intval($_POST['delivery_time']);
    $delivery_unit = $_POST['delivery_unit']; 
    $revision_count = intval($_POST['revision_count']); 
    
    // Тайлбар
    $description = $_POST['description']; 
    $requirements = $_POST['requirements'];
    
    // FAQ Data
    $faq_questions = isset($_POST['faq_questions']) ? $_POST['faq_questions'] : [];
    $faq_answers = isset($_POST['faq_answers']) ? $_POST['faq_answers'] : [];

    // Validation
    if (empty($title) || empty($price_min) || empty($category_id) || empty($description)) {
        $error = "Гарчиг, Ангилал, Үнэ болон Тайлбарыг заавал бөглөх шаардлагатай.";
    }

    // Зургийн тоог шалгах
    $image_count = 0;
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        // Хоосон файл ирсэн эсэхийг шалгах
        if ($_FILES['images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $image_count = count($_FILES['images']['name']);
        }
    }
    
    if ($image_count > 5) {
        $error = "Та дээд тал нь 5 зураг оруулах боломжтой.";
    }

    if (empty($error)) {
        try {
            $pdo->beginTransaction();

            // 1. Insert into services
            $sql = "INSERT INTO services (
                        user_id, title, category_id, 
                        price_min, price_max, delivery_time, delivery_unit, revision_count, 
                        description, requirements, status, created_at
                    ) VALUES (
                        ?, ?, ?, 
                        ?, ?, ?, ?, ?, 
                        ?, ?, 'pending', NOW()
                    )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $user_id, $title, $category_id, 
                $price_min, $price_max, $delivery_time, $delivery_unit, $revision_count, 
                $description, $requirements
            ]);
            
            $service_id = $pdo->lastInsertId();

            // 2. FAQ хадгалах
            if (!empty($faq_questions)) {
                $faq_sql = "INSERT INTO service_faqs (service_id, question, answer) VALUES (?, ?, ?)";
                $faq_stmt = $pdo->prepare($faq_sql);
                
                for ($i = 0; $i < count($faq_questions); $i++) {
                    $q = trim($faq_questions[$i]);
                    $a = trim($faq_answers[$i]);
                    if (!empty($q) && !empty($a)) {
                        $faq_stmt->execute([$service_id, $q, $a]);
                    }
                }
            }

            // 3. Зураг хуулах & service_previews
            if ($image_count > 0) {
                $rel_base_dir = 'uploads/service/';
                $abs_base_dir = __DIR__ . '/' . $rel_base_dir;

                // Үндсэн uploads хавтас байхгүй бол үүсгэх
                if (!is_dir($abs_base_dir)) {
                    if (!mkdir($abs_base_dir, 0755, true)) {
                         error_log("Failed to create base directory: " . $abs_base_dir);
                    }
                }

                // Хэрэглэгчийн ID-аар хавтас үүсгэх
                $service_dir_suffix = $user_id . '/' . $service_id . '/';
                $target_dir_abs = $abs_base_dir . $service_dir_suffix;
                $target_dir_rel = $rel_base_dir . $service_dir_suffix;

                if (!is_dir($target_dir_abs)) {
                    if (!mkdir($target_dir_abs, 0777, true)) {
                        error_log("Failed to create directory: " . $target_dir_abs);
                        throw new Exception("Зураг хадгалах хавтас үүсгэж чадсангүй. Админтай холбогдоно уу.");
                    }
                }

                // AVIF нэмсэн
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
                $order_counter = 1;
                $first_success_image = null; // Cover image-д зориулсан хувьсагч

                $img_files = reArrayFiles($_FILES['images']);

                foreach ($img_files as $file) {
                    if ($order_counter > 5) break;

                    $tmp_name = $file['tmp_name'];
                    $file_error = $file['error'];
                    $file_size = $file['size'];
                    $filename = $file['name'];
                    
                    if (empty($filename)) continue;

                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    // Validation check
                    if ($file_error === 0 && in_array($ext, $allowed) && $file_size <= 10 * 1024 * 1024) { // 10MB limit
                        $new_filename = uniqid('img_', true) . '.' . $ext;
                        $destination = $target_dir_abs . $new_filename;
                        $db_path = $target_dir_rel . $new_filename;

                        if (move_uploaded_file($tmp_name, $destination)) {
                            // Insert into service_previews
                            $stmt_img = $pdo->prepare("INSERT INTO service_previews (service_id, preview_url, order_index) VALUES (?, ?, ?)");
                            $stmt_img->execute([$service_id, $db_path, $order_counter]);

                            if ($first_success_image === null) {
                                $first_success_image = $db_path;
                            }
                            
                            $order_counter++;
                        } else {
                            error_log("Failed to move file to: " . $destination);
                        }
                    } else {
                        // Detailed Error Logging
                        $error_msg = "File upload failed for '$filename'. ";
                        if (!in_array($ext, $allowed)) {
                            $error_msg .= "Invalid extension: '$ext'. Allowed: " . implode(', ', $allowed);
                        } elseif ($file_size > 10 * 1024 * 1024) {
                            $error_msg .= "File too large: $file_size bytes.";
                        } elseif ($file_error !== 0) {
                            $error_msg .= "PHP Upload Error Code: $file_error.";
                        }
                        error_log($error_msg);
                    }
                }

                // 4. Update cover_image
                if ($first_success_image) {
                    $update_sql = "UPDATE services SET cover_image = ? WHERE id = ?";
                    $stmt_update = $pdo->prepare($update_sql);
                    $stmt_update->execute([$first_success_image, $service_id]);
                }
            }

            $pdo->commit();

            // ------------------------------------------
            // 5. АДМИН РУУ МЭЙЛ ИЛГЭЭХ (Шинэ хэсэг)
            // ------------------------------------------
            try {
                // Хэрэглэгчийн нэрийг татаж авах
                $user_sql = "SELECT username, email FROM users WHERE id = ?";
                $user_stmt = $pdo->prepare($user_sql);
                $user_stmt->execute([$user_id]);
                $user_info = $user_stmt->fetch();
                $providerName = $user_info['username'] ?? 'User ID: ' . $user_id;

                // Brevo функц дуудах (api/brevo_email.php дотор байх ёстой)
                if (function_exists('sendNewServiceNotification')) {
                    sendNewServiceNotification('delgerzaya95@gmail.com', $title, $providerName, $service_id);
                } else {
                    error_log("Warning: sendNewServiceNotification function not found.");
                }
            } catch (Exception $mailError) {
                // Мэйл илгээхэд алдаа гарсан ч процессыг зогсоохгүй
                error_log("Mail notification failed: " . $mailError->getMessage());
            }
            
            // Success redirect
            header("Location: profile/my_services.php?success=created");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Системийн алдаа: " . $e->getMessage();
            error_log($e->getMessage());
        }
    }
}

$page_title = "Үйлчилгээ нэмэх - Filezone.mn";
include 'includes/header.php';
?>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <!-- Sidebar Navigation -->
    <aside class="hidden lg:block w-64 flex-shrink-0 py-6 pr-6 h-[calc(100vh-64px)] sticky top-16 overflow-y-auto no-scrollbar">
        <div class="space-y-1 mb-6">
            <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Үндсэн</h3>
            <a href="index.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-home w-5 text-center"></i> Нүүр хуудас
            </a>
            <a href="browse-files.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-folder-open w-5 text-center"></i> Файлууд
            </a>
            <a href="profile/dashboard.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-user w-5 text-center"></i> Профайл
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Үйлчилгээ нэмэх</h1>
            <p class="text-sm text-gray-500">Та өөрийн чадвараа ашиглан бусдад үйлчилгээ үзүүлж мөнгө олоорой.</p>
        </div>

        <?php if($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Алдаа!</strong>
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['profile_updated'])): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Амжилттай!</strong>
                <span class="block sm:inline">Таны мэдээлэл шинэчлэгдлээ. Одоо үйлчилгээгээ нэмнэ үү.</span>
            </div>
        <?php endif; ?>

        <!-- LOGIC: CHECK IF PROFILE IS COMPLETE -->
        <?php if ($is_profile_incomplete): ?>
            <!-- ======================================================= -->
            <!-- PROFILE COMPLETION FORM (Shown if info is missing)      -->
            <!-- ======================================================= -->
            <div class="max-w-2xl mx-auto">
                <div class="bg-white border-l-4 border-yellow-400 shadow-md rounded-r-xl p-6 md:p-8">
                    <div class="flex items-start mb-6">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 text-yellow-600">
                                <i class="fas fa-user-shield text-xl"></i>
                            </span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-gray-900">Мэдээллээ гүйцээнэ үү</h3>
                            <p class="text-sm text-gray-600 mt-1">
                                Үйлчилгээ нийтлэхийн тулд та өөрийн жинхэнэ нэр болон холбоо барих дугаараа оруулах шаардлагатай. Энэ нь захиалагч нарт итгэл төрүүлэхэд чухал юм.
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="add_service.php" class="space-y-5">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Овог Нэр (Жинхэнэ нэр) <span class="text-red-500">*</span></label>
                            <input type="text" name="full_name" required value="<?php echo htmlspecialchars($current_user['full_name'] ?? ''); ?>" 
                                   class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none" 
                                   placeholder="Жишээ: Бат-Эрдэнэ Болд">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Утасны дугаар <span class="text-red-500">*</span></label>
                            <input type="text" name="phone" required value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>" 
                                   class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none" 
                                   placeholder="Жишээ: 99112233">
                            <p class="text-xs text-gray-500 mt-1">Бид таны дугаарыг бусдад харагдуулахгүй, зөвхөн админ холбогдох зорилгоор ашиглана.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Хаяг байршил (Сонголтоор)</label>
                            <input type="text" name="location" value="<?php echo htmlspecialchars($current_user['location'] ?? ''); ?>" 
                                   class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 outline-none" 
                                   placeholder="Жишээ: Улаанбаатар, Сүхбаатар дүүрэг">
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full bg-yellow-500 text-white font-bold py-3 rounded-xl shadow-lg shadow-yellow-500/30 hover:bg-yellow-600 transition transform hover:-translate-y-0.5">
                                Хадгалах & Үргэлжлүүлэх
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <!-- ======================================================= -->
            <!-- ADD SERVICE FORM (Shown only if profile is complete)    -->
            <!-- ======================================================= -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Column: Service Form -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <h3 class="font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Үйлчилгээний мэдээлэл</h3>
                        
                        <form method="POST" action="add_service.php" enctype="multipart/form-data" class="space-y-6" id="serviceForm">
                            
                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Үйлчилгээний гарчиг <span class="text-red-500">*</span></label>
                                <input type="text" name="title" required class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition" placeholder="Жишээ: Би танд мэргэжлийн түвшинд лого бүтээж өгнө">
                                <p class="text-xs text-gray-400 mt-1">Товч бөгөөд тодорхой байх хэрэгтэй. "Би ... хийж өгнө" гэсэн хэлбэрээр бичвэл зүгээр.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Category -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Ангилал <span class="text-red-500">*</span></label>
                                    <select name="category_id" required class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none bg-white">
                                        <option value="">Сонгоно уу...</option>
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <!-- Delivery Time -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Гүйцэтгэх хугацаа <span class="text-red-500">*</span></label>
                                    <div class="flex gap-2">
                                        <input type="number" name="delivery_time" required min="1" value="1" class="w-1/3 border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none">
                                        <select name="delivery_unit" class="w-2/3 border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none bg-white">
                                            <option value="hour">Цаг</option>
                                            <option value="day" selected>Хоног</option>
                                            <option value="week">Долоо хоног</option>
                                            <option value="month">Сар</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Price -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Үнэ (MNT) <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="number" name="price_min" required min="5000" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition" placeholder="30000">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-xs">₮</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Хамгийн доод үнэ: 5,000₮</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Дээд үнэ (Сонголтоор)</label>
                                    <div class="relative">
                                        <input type="number" name="price_max" min="5000" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition" placeholder="50000">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-xs">₮</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Засвар хийх тоо (Revisions)</label>
                                <input type="number" name="revision_count" min="0" value="0" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition" placeholder="0 = Засваргүй">
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Дэлгэрэнгүй тайлбар <span class="text-red-500">*</span></label>
                                <textarea name="description" id="description" rows="6" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition"></textarea>
                            </div>

                            <!-- Requirements -->
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                <label class="block text-sm font-bold text-blue-800 mb-1.5">Захиалагчаас шаардагдах зүйлс</label>
                                <p class="text-xs text-blue-600 mb-2">Ажлыг эхлүүлэхийн тулд танд захиалагчаас юу хэрэгтэй вэ? (Жишээ нь: Компанийн нэр, өнгөний сонголт, текст г.м)</p>
                                <textarea name="requirements" rows="3" class="w-full border border-blue-200 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Надад дараах мэдээллүүдийг илгээнэ үү..."></textarea>
                            </div>

                            <!-- Images Upload with Drag & Drop -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Зураг (Дээд тал нь 5 зураг) <span class="text-red-500">*</span>
                                    <span class="text-xs text-purple-600 font-normal ml-2 bg-purple-50 px-2 py-0.5 rounded border border-purple-100">Зөөж байрлуулах боломжтой</span>
                                </label>
                                
                                <div class="flex flex-col items-start gap-4">
                                    <!-- Hidden input for actual file submission -->
                                    <input type="file" name="images[]" id="images-storage" class="hidden" multiple accept="image/*">
                                    
                                    <!-- Visible input for selecting files -->
                                    <input type="file" id="images-picker" accept="image/*" multiple class="hidden" onchange="handleServiceImageSelect(this)">
                                    
                                    <label for="images-picker" class="w-full h-32 bg-gray-50 rounded-xl border-2 border-dashed border-gray-300 flex flex-col items-center justify-center text-gray-400 cursor-pointer hover:bg-gray-100 transition">
                                        <i class="fas fa-cloud-upload-alt text-3xl mb-2"></i>
                                        <p class="text-xs">Зураг сонгох (JPG, PNG, AVIF)</p>
                                        <p class="text-[10px] text-gray-400 mt-1">Чирж дарааллыг солих боломжтой</p>
                                    </label>
                                    
                                    <!-- Preview Container -->
                                    <div id="image-preview-container" class="grid grid-cols-2 md:grid-cols-5 gap-3 w-full">
                                        <!-- JS will fill this -->
                                    </div>
                                </div>
                            </div>

                            <hr class="border-gray-100">

                            <!-- FAQ Section -->
                            <div>
                                <div class="flex justify-between items-center mb-3">
                                    <label class="block text-sm font-medium text-gray-700">Түгээмэл асуулт хариулт (FAQ)</label>
                                    <button type="button" onclick="addFaqRow()" class="text-xs text-purple-600 font-bold hover:text-purple-800">+ Асуулт нэмэх</button>
                                </div>
                                <div id="faq-container" class="space-y-3">
                                    <!-- Dynamic Rows -->
                                </div>
                            </div>

                            <hr class="border-gray-100">

                            <!-- Terms -->
                            <div class="flex items-start bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <div class="flex items-center h-5">
                                    <input id="terms" type="checkbox" required disabled class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="terms" class="font-medium text-gray-700">Үйлчилгээний нөхцөл</label>
                                    <p class="text-gray-500 text-xs">
                                        Би <a href="#" onclick="openTermsModal(event)" class="text-purple-600 hover:text-purple-800 font-bold underline decoration-purple-300 underline-offset-2">Үйлчилгээний гэрээ</a>-тэй танилцаж, бүрэн хүлээн зөвшөөрч байна.
                                        <span class="block text-[10px] text-red-500 mt-1">* Та гэрээтэй танилцсаны дараа зөвшөөрөх боломжтой.</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="flex gap-4 pt-2">
                                <button type="submit" class="flex-1 bg-purple-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-purple-500/30 hover:bg-purple-700 hover:-translate-y-0.5 transition-all">
                                    Үйлчилгээ нийтлэх
                                </button>
                                <button type="button" onclick="history.back()" class="px-6 py-3 border border-gray-300 text-gray-600 font-bold rounded-xl hover:bg-gray-50 transition">
                                    Болих
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Tips -->
                <div class="lg:col-span-1">
                    <div class="bg-purple-50 border border-purple-100 rounded-2xl p-6 sticky top-24">
                        <h3 class="font-bold text-purple-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-star"></i> Амжилттай зарах зөвлөмж
                        </h3>
                        <ul class="space-y-4 text-sm text-purple-800">
                            <li class="flex gap-3">
                                <span class="w-6 h-6 bg-purple-200 text-purple-700 rounded-full flex items-center justify-center flex-shrink-0 font-bold text-xs">1</span>
                                <span><strong>Гарчиг:</strong> Товч бөгөөд тодорхой байх. Үйлчлүүлэгч юу авах вэ гэдгээ шууд ойлгох ёстой.</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="w-6 h-6 bg-purple-200 text-purple-700 rounded-full flex items-center justify-center flex-shrink-0 font-bold text-xs">2</span>
                                <span><strong>Зураг:</strong> Чанартай, анхаарал татахуйц зураг ашиглах нь борлуулалтыг 30% нэмэгдүүлдэг.</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="w-6 h-6 bg-purple-200 text-purple-700 rounded-full flex items-center justify-center flex-shrink-0 font-bold text-xs">3</span>
                                <span><strong>Үнэ:</strong> Зах зээлийн ханшийг судалж, өрсөлдөхүйц үнэ тавих.</span>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Terms Modal -->
<div id="termsModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeTermsModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 border-b pb-3 mb-4 flex items-center gap-2" id="modal-title">
                            <i class="fas fa-file-contract text-purple-600"></i> Filezone.mn Үйлчилгээ үзүүлэгчийн гэрээ
                        </h3>
                        <div class="mt-2 text-sm text-gray-600 h-96 overflow-y-auto pr-4 space-y-4 text-justify">
                            <p class="italic text-xs text-gray-500 mb-2">Сүүлд шинэчлэгдсэн: 2025-10-25</p>
                            
                            <h4 class="font-bold text-gray-800">1. НИЙТЛЭГ ҮНДЭСЛЭЛ</h4>
                            <p>1.1. Энэхүү "Үйлчилгээ үзүүлэгчийн гэрээ" (цаашид "Гэрээ" гэх) нь Filezone.mn платформ (цаашид "Платформ" гэх) болон тус платформ дээр үйлчилгээ, бараа бүтээгдэхүүнээ санал болгож буй хувь хүн, хуулийн этгээд (цаашид "Нийлүүлэгч" гэх) хоорондын харилцааг зохицуулна.</p>
                            <p>1.2. Нийлүүлэгч нь энэхүү гэрээг зөвшөөрснөөр Платформын үйлчилгээний нөхцөл, нууцлалын бодлогыг бүрэн хүлээн зөвшөөрсөнд тооцно.</p>

                            <h4 class="font-bold text-gray-800">2. НИЙЛҮҮЛЭГЧИЙН ЭРХ, ҮҮРЭГ</h4>
                            <ul class="list-disc pl-5 space-y-1">
                                <li><strong>Үнэн зөв мэдээлэл:</strong> Нийлүүлэгч нь өөрийн ур чадвар, туршлага, санал болгож буй үйлчилгээний талаар үнэн зөв, бодит мэдээллийг нийтлэх үүрэгтэй.</li>
                                <li><strong>Чанарын баталгаа:</strong> Захиалагчтай тохиролцсон хугацаанд, чанарын өндөр түвшинд ажлыг гүйцэтгэж хүлээлгэн өгөх үүрэгтэй.</li>
                                <li><strong>Нууцлал:</strong> Захиалагчийн өгсөн мэдээлэл, файл, санааг гуравдагч этгээдэд задруулахгүй, хувийн зорилгоор ашиглахгүй байх үүрэгтэй.</li>
                                <li><strong>Зохиогчийн эрх:</strong> Бусдын зохиогчийн эрхэд халдсан, хулгайлсан, хууль бус контент нийтлэхийг хатуу хориглоно.</li>
                                <li><strong>Харилцаа:</strong> Захиалагчтай соёлтой, хүндэтгэлтэй харилцаж, Платформын мессеж хэсгээр харилцааг баримтжуулах үүрэгтэй. Платформоос гадуур төлбөр тооцоо хийхийг санал болгохыг хориглоно.</li>
                            </ul>

                            <h4 class="font-bold text-gray-800">3. ТӨЛБӨР ТООЦОО БА ШИМТГЭЛ</h4>
                            <ul class="list-disc pl-5 space-y-1">
                                <li><strong>Үйлчилгээний шимтгэл:</strong> Платформ нь зуучлалын болон техникийн үйлчилгээний хөлс болгон нийт дүнгээс тодорхой хувийн шимтгэл (одоогоор 10-20%) суутган авна.</li>
                                <li><strong>Төлбөр шилжүүлэх:</strong> Захиалга "Амжилттай дууссан" төлөвт шилжсэний дараа ажлын хөлс Нийлүүлэгчийн "Хэтэвч"-д орно. Нийлүүлэгч хүссэн үедээ мөнгө татах хүсэлт гаргаж болно.</li>
                                <li><strong>Буцаан олголт:</strong> Хэрэв Нийлүүлэгч ажлаа хугацаанд нь гүйцэтгээгүй, чанарын шаардлага хангаагүй тохиолдолд Захиалагчид төлбөрийг бүрэн буюу хэсэгчлэн буцаан олгох эрхийг Платформ эдэлнэ.</li>
                            </ul>

                            <h4 class="font-bold text-gray-800">4. ХОРИГЛОХ ЗҮЙЛС</h4>
                            <p>Дараах төрлийн үйлчилгээ, контентыг нийтлэхийг хориглоно:</p>
                            <ul class="list-disc pl-5 space-y-1">
                                <li>Монгол Улсын хууль тогтоомж зөрчсөн.</li>
                                <li>Садар самуун, хүчирхийлэл сурталчилсан.</li>
                                <li>Бусдын нэр хүндэд халдсан, гүтгэсэн.</li>
                                <li>Сургуулийн дүн засах, хуурамч бичиг баримт үйлдэх зэрэг хууль бус үйлдэл.</li>
                            </ul>

                            <h4 class="font-bold text-gray-800">5. ГЭРЭЭГ ЦУЦЛАХ</h4>
                            <p>Нийлүүлэгч нь энэхүү гэрээ болон Платформын дүрмийг зөрчсөн тохиолдолд Платформ нь урьдчилан сануулгагүйгээр үйлчилгээг зогсоох, хаягийг хаах, хэтэвчин дэх үлдэгдлийг царцаах эрхтэй.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm" onclick="acceptTerms()">
                    Хүлээн зөвшөөрч байна
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeTermsModal()">
                    Хаах
                </button>
            </div>
        </div>
    </div>
</div>

<!-- TinyMCE Script -->
<script src="https://cdn.tiny.cloud/1/g492qv0cyczptbbzcso4exirfkhg3l20o9z13ujy2i0arcw5/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
<!-- SortableJS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

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

  // --- Image Handling with Drag & Drop ---
  let serviceImages = []; // Array to store File objects

  // Form Submit Event Handler - CRITICAL FIX
  // Энэ хэсэг нь form submit хийх үед hidden input-г JS array-аар шинэчилж байгааг баталгаажуулна.
  const serviceForm = document.getElementById('serviceForm');
  if(serviceForm) {
      serviceForm.addEventListener('submit', function(e) {
          updateServiceInputFiles();
          // Хэрэв зураг байхгүй бол сануулга өгч болно (optional)
          if (serviceImages.length === 0) {
              // alert("Зураг оруулна уу!"); // Хэрэв хүсвэл
          }
      });
  }

  function handleServiceImageSelect(input) {
      const files = Array.from(input.files);
      if (serviceImages.length + files.length > 5) {
          alert('Та дээд тал нь 5 зураг сонгох боломжтой.');
          input.value = '';
          return;
      }

      files.forEach(file => {
          if (serviceImages.length < 5) {
              serviceImages.push(file);
          }
      });

      renderServiceImages();
      updateServiceInputFiles();
      input.value = ''; 
  }

  function renderServiceImages() {
      const container = document.getElementById('image-preview-container');
      container.innerHTML = '';

      serviceImages.forEach((file, index) => {
          const reader = new FileReader();
          reader.onload = function(e) {
              const div = document.createElement('div');
              div.className = 'image-preview-item relative group aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200 cursor-move shadow-sm';
              div.setAttribute('data-index', index);

              div.innerHTML = `
                  <img src="${e.target.result}" class="w-full h-full object-cover pointer-events-none">
                  <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-all"></div>
                  
                  <div class="absolute top-1 left-1 bg-white bg-opacity-90 rounded px-1.5 py-0.5 text-xs font-bold text-gray-700 index-badge border border-gray-200 shadow-sm z-10">
                      ${index === 0 ? '<span class="text-purple-600">Cover</span>' : '#' + (index + 1)}
                  </div>
                  
                  <button type="button" onclick="removeServiceImage(${index})" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition shadow-sm z-10 hover:bg-red-600">
                      <i class="fas fa-times"></i>
                  </button>
                  
                  <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-[10px] p-1 text-center opacity-0 group-hover:opacity-100 transition-opacity">
                      Чирж зөөнө үү
                  </div>
              `;
              container.appendChild(div);
          }
          reader.readAsDataURL(file);
      });
  }

  function removeServiceImage(index) {
      serviceImages.splice(index, 1);
      renderServiceImages();
      updateServiceInputFiles();
  }

  function updateServiceInputFiles() {
      const dataTransfer = new DataTransfer();
      serviceImages.forEach(file => {
          dataTransfer.items.add(file);
      });
      // Update the hidden input that actually gets submitted
      const fileInput = document.getElementById('images-storage');
      if(fileInput) {
          fileInput.files = dataTransfer.files;
          console.log("Files ready for upload:", fileInput.files.length); // Debug log
      }
  }

  // Initialize SortableJS
  const previewContainer = document.getElementById('image-preview-container');
  if(previewContainer) {
      new Sortable(previewContainer, {
          animation: 150,
          ghostClass: 'sortable-ghost',
          onEnd: function (evt) {
              // Reorder the array based on new DOM order
              const newOrder = [];
              const items = previewContainer.querySelectorAll('.image-preview-item');
              
              items.forEach(item => {
                  const oldIndex = parseInt(item.getAttribute('data-index'));
                  newOrder.push(serviceImages[oldIndex]);
              });

              serviceImages = newOrder;
              
              // Re-render to update badges (#1, Cover, etc)
              renderServiceImages();
              updateServiceInputFiles();
          }
      });
  }

  // --- FAQ Logic ---
  function addFaqRow() {
      const container = document.getElementById('faq-container');
      const div = document.createElement('div');
      div.className = 'bg-gray-50 p-3 rounded-lg border border-gray-200 relative group transition hover:border-purple-200';
      div.innerHTML = `
          <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 transition-colors"><i class="fas fa-times"></i></button>
          <input type="text" name="faq_questions[]" placeholder="Асуулт (Жнь: Лого хэдэн хувилбартай вэ?)" class="w-full border-gray-300 rounded-lg mb-2 text-sm p-2 focus:ring-2 focus:ring-purple-500 outline-none" required>
          <textarea name="faq_answers[]" rows="2" placeholder="Хариулт" class="w-full border-gray-300 rounded-lg text-sm p-2 focus:ring-2 focus:ring-purple-500 outline-none" required></textarea>
      `;
      container.appendChild(div);
  }

  // --- Terms Modal Logic ---
  function openTermsModal(e) {
      e.preventDefault();
      document.getElementById('termsModal').classList.remove('hidden');
  }

  function closeTermsModal() {
      document.getElementById('termsModal').classList.add('hidden');
  }

  function acceptTerms() {
      const checkbox = document.getElementById('terms');
      if(checkbox) {
          checkbox.disabled = false;
          checkbox.checked = true;
      }
      closeTermsModal();
  }
</script>

<?php include 'includes/footer.php'; ?>