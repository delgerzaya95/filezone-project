<?php
// profile/edit_service.php
// Updated: Uses local header/sidebar, service_categories, AVIF support

session_start();
include '../includes/db.php'; // Go up one level

// Check DB Connection
if (!isset($conn)) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
}

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: my_services.php");
    exit();
}

$service_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$msg = '';
$error = '';

// 1. Үйлчилгээний мэдээлэл татах
$sql = "SELECT * FROM services WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $service_id, $user_id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();

if (!$service) {
    die("Service not found or permission denied.");
}

// 2. Ангилал татах (service_categories хүснэгтээс)
$categories = [];
try {
    $cat_sql = "SELECT id, name FROM service_categories ORDER BY name ASC";
    $cat_res = $conn->query($cat_sql);
    if ($cat_res) {
        while($row = $cat_res->fetch_assoc()) {
            $categories[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Category fetch error: " . $e->getMessage());
}

// 3. Зургууд татах (service_previews)
$images = [];
$img_sql = "SELECT * FROM service_previews WHERE service_id = ? ORDER BY order_index ASC";
$img_stmt = $conn->prepare($img_sql);
$img_stmt->bind_param("i", $service_id);
$img_stmt->execute();
$img_res = $img_stmt->get_result();
while($row = $img_res->fetch_assoc()) {
    $images[] = $row;
}

// 4. FAQ татах
$faqs = [];
$faq_sql = "SELECT * FROM service_faqs WHERE service_id = ?";
$faq_stmt = $conn->prepare($faq_sql);
$faq_stmt->bind_param("i", $service_id);
$faq_stmt->execute();
$faq_res = $faq_stmt->get_result();
while($row = $faq_res->fetch_assoc()) {
    $faqs[] = $row;
}

// --------------------------------------------------------------------------
// FORM SUBMISSION HANDLER
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Basic Info
    $title = trim($_POST['title']);
    $category_id = intval($_POST['category_id']);
    $price_min = floatval($_POST['price_min']);
    $price_max = !empty($_POST['price_max']) ? floatval($_POST['price_max']) : NULL;
    $delivery_time = intval($_POST['delivery_time']);
    $delivery_unit = $_POST['delivery_unit'];
    $revision_count = intval($_POST['revision_count']);
    $description = $_POST['description'];
    $requirements = $_POST['requirements'];
    
    // Validation
    if (empty($title) || empty($price_min) || empty($delivery_time) || empty($description)) {
        $error = "Заавал бөглөх талбаруудыг бөглөнө үү.";
    } else {
        // Start Transaction
        $conn->begin_transaction();
        
        try {
            // 1. Update Service Info
            $update_sql = "UPDATE services SET 
                title=?, category_id=?, price_min=?, price_max=?, 
                delivery_time=?, delivery_unit=?, revision_count=?, 
                description=?, requirements=?, updated_at=NOW() 
                WHERE id=? AND user_id=?";
            
            $stmt_upd = $conn->prepare($update_sql);
            $stmt_upd->bind_param("siddisissii", 
                $title, $category_id, $price_min, $price_max, 
                $delivery_time, $delivery_unit, $revision_count, 
                $description, $requirements, $service_id, $user_id
            );
            $stmt_upd->execute();

            // 2. Handle Image Deletions
            if (isset($_POST['deleted_images']) && !empty($_POST['deleted_images'])) {
                $deleted_ids = explode(',', $_POST['deleted_images']);
                foreach ($deleted_ids as $del_id) {
                    $del_id = intval($del_id);
                    // Get path to delete file
                    $path_sql = "SELECT preview_url FROM service_previews WHERE id = ? AND service_id = ?";
                    $path_stmt = $conn->prepare($path_sql);
                    $path_stmt->bind_param("ii", $del_id, $service_id);
                    $path_stmt->execute();
                    $path_res = $path_stmt->get_result();
                    if ($row = $path_res->fetch_assoc()) {
                        // Delete file (adjust path to go up to root)
                        $file_path = '../' . $row['preview_url']; 
                        if (file_exists($file_path)) {
                            @unlink($file_path);
                        }
                        // Delete DB record
                        $del_db_sql = "DELETE FROM service_previews WHERE id = ?";
                        $del_db_stmt = $conn->prepare($del_db_sql);
                        $del_db_stmt->bind_param("i", $del_id);
                        $del_db_stmt->execute();
                    }
                }
            }

            // 3. Handle New Image Uploads
            // Re-organize $_FILES
            $new_images = [];
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $file_post = $_FILES['images'];
                $file_count = count($file_post['name']);
                $file_keys = array_keys($file_post);
                for ($i=0; $i<$file_count; $i++) {
                    $file_ary = [];
                    foreach ($file_keys as $key) {
                        $file_ary[$key] = $file_post[$key][$i];
                    }
                    $new_images[] = $file_ary;
                }
            }

            // Directory Setup (path relative to this script)
            $upload_base_dir = '../uploads/service/'; 
            $service_dir_path = $upload_base_dir . $user_id . '/' . $service_id . '/';
            
            if (!is_dir($upload_base_dir)) {
                if (!mkdir($upload_base_dir, 0755, true)) {
                    error_log("Failed to create base directory: " . $upload_base_dir);
                }
            }
            if (!is_dir($service_dir_path)) {
                if (!mkdir($service_dir_path, 0777, true)) {
                    error_log("Failed to create service directory: " . $service_dir_path);
                    throw new Exception("Зураг хадгалах хавтас үүсгэж чадсангүй.");
                }
            }

            // Current max order index
            $max_order_sql = "SELECT MAX(order_index) as max_idx FROM service_previews WHERE service_id = ?";
            $max_stmt = $conn->prepare($max_order_sql);
            $max_stmt->bind_param("i", $service_id);
            $max_stmt->execute();
            $max_row = $max_stmt->get_result()->fetch_assoc();
            $current_order = ($max_row['max_idx'] ?? 0) + 1;

            // Updated Allowed Extensions (AVIF included)
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'avif'];

            foreach ($new_images as $file) {
                $tmp_name = $file['tmp_name'];
                $file_error = $file['error'];
                $file_size = $file['size'];
                $filename = $file['name'];
                
                if (empty($filename)) continue;

                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                // 10MB Limit
                if ($file_error === 0 && in_array($ext, $allowed) && $file_size <= 10 * 1024 * 1024) { 
                    $new_filename = uniqid('img_', true) . '.' . $ext;
                    $destination = $service_dir_path . $new_filename;
                    
                    // DB path (stored relative to root, no ../)
                    $db_path = 'uploads/service/' . $user_id . '/' . $service_id . '/' . $new_filename;

                    if (move_uploaded_file($tmp_name, $destination)) {
                        $ins_img_sql = "INSERT INTO service_previews (service_id, preview_url, order_index) VALUES (?, ?, ?)";
                        $ins_stmt = $conn->prepare($ins_img_sql);
                        $ins_stmt->bind_param("isi", $service_id, $db_path, $current_order);
                        $ins_stmt->execute();
                        $current_order++;
                    } else {
                        error_log("Failed to move file to: " . $destination);
                    }
                } else {
                    // Logging
                    $error_msg = "File upload failed for '$filename' in Edit. ";
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

            // 4. Update Cover Image (Logic: use first available image)
            $cover_sql = "SELECT preview_url FROM service_previews WHERE service_id = ? ORDER BY order_index ASC LIMIT 1";
            $cover_stmt = $conn->prepare($cover_sql);
            $cover_stmt->bind_param("i", $service_id);
            $cover_stmt->execute();
            $cover_res = $cover_stmt->get_result();
            if ($cover_row = $cover_res->fetch_assoc()) {
                $new_cover = $cover_row['preview_url'];
                $upd_cover_sql = "UPDATE services SET cover_image = ? WHERE id = ?";
                $upd_cover_stmt = $conn->prepare($upd_cover_sql);
                $upd_cover_stmt->bind_param("si", $new_cover, $service_id);
                $upd_cover_stmt->execute();
            }

            // 5. Update FAQs (Delete all and re-insert logic)
            $del_faq_sql = "DELETE FROM service_faqs WHERE service_id = ?";
            $del_faq_stmt = $conn->prepare($del_faq_sql);
            $del_faq_stmt->bind_param("i", $service_id);
            $del_faq_stmt->execute();

            if (isset($_POST['faq_questions']) && !empty($_POST['faq_questions'])) {
                $ins_faq_sql = "INSERT INTO service_faqs (service_id, question, answer) VALUES (?, ?, ?)";
                $ins_faq_stmt = $conn->prepare($ins_faq_sql);
                
                $qs = $_POST['faq_questions'];
                $as = $_POST['faq_answers'];
                
                for ($i = 0; $i < count($qs); $i++) {
                    $q = trim($qs[$i]);
                    $a = trim($as[$i]);
                    if (!empty($q) && !empty($a)) {
                        $ins_faq_stmt->bind_param("iss", $service_id, $q, $a);
                        $ins_faq_stmt->execute();
                    }
                }
            }

            $conn->commit();
            $msg = "Үйлчилгээ амжилттай шинэчлэгдлээ.";
            
            // Refresh Data for display
            $stmt->execute();
            $service = $stmt->get_result()->fetch_assoc();
            
            $images = [];
            $img_stmt->execute();
            $img_res = $img_stmt->get_result();
            while($row = $img_res->fetch_assoc()) $images[] = $row;

            $faqs = [];
            $faq_stmt->execute();
            $faq_res = $faq_stmt->get_result();
            while($row = $faq_res->fetch_assoc()) $faqs[] = $row;

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Алдаа гарлаа: " . $e->getMessage();
            error_log($e->getMessage());
        }
    }
}

$page_title = "Үйлчилгээ засах - Filezone.mn";
include 'header.php'; // Local header file
?>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Үйлчилгээ засах</h1>
                <p class="text-sm text-gray-500">Үйлчилгээний мэдээллээ шинэчлэх</p>
            </div>
            <a href="my_services.php" class="text-sm text-gray-600 hover:text-gray-900 font-medium flex items-center gap-1">
                <i class="fas fa-arrow-left"></i> Буцах
            </a>
        </div>

        <?php if($msg): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Амжилттай!</strong>
                <span class="block sm:inline"><?php echo $msg; ?></span>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Алдаа!</strong>
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Form -->
            <div class="lg:col-span-2">
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                    <form method="POST" action="" enctype="multipart/form-data" id="editServiceForm" class="space-y-6">
                        
                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Үйлчилгээний гарчиг <span class="text-red-500">*</span></label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($service['title']); ?>" required class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Category -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Ангилал <span class="text-red-500">*</span></label>
                                <select name="category_id" required class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none bg-white">
                                    <option value="">Сонгоно уу...</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($service['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Delivery Time -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Гүйцэтгэх хугацаа <span class="text-red-500">*</span></label>
                                <div class="flex gap-2">
                                    <input type="number" name="delivery_time" value="<?php echo $service['delivery_time']; ?>" required min="1" class="w-1/3 border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none">
                                    <select name="delivery_unit" class="w-2/3 border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none bg-white">
                                        <option value="hour" <?php echo ($service['delivery_unit'] == 'hour') ? 'selected' : ''; ?>>Цаг</option>
                                        <option value="day" <?php echo ($service['delivery_unit'] == 'day') ? 'selected' : ''; ?>>Хоног</option>
                                        <option value="week" <?php echo ($service['delivery_unit'] == 'week') ? 'selected' : ''; ?>>Долоо хоног</option>
                                        <option value="month" <?php echo ($service['delivery_unit'] == 'month') ? 'selected' : ''; ?>>Сар</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Price -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Үнэ (MNT) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="number" name="price_min" value="<?php echo $service['price_min']; ?>" required min="5000" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"><span class="text-gray-500 text-xs">₮</span></div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Дээд үнэ (Сонголтоор)</label>
                                <div class="relative">
                                    <input type="number" name="price_max" value="<?php echo $service['price_max']; ?>" min="5000" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"><span class="text-gray-500 text-xs">₮</span></div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Засвар хийх тоо</label>
                            <input type="number" name="revision_count" value="<?php echo $service['revision_count']; ?>" min="0" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition">
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Дэлгэрэнгүй тайлбар <span class="text-red-500">*</span></label>
                            <textarea name="description" id="description" rows="6" class="w-full border border-gray-300 rounded-lg p-3 text-sm"><?php echo htmlspecialchars($service['description']); ?></textarea>
                        </div>

                        <!-- Requirements -->
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                            <label class="block text-sm font-bold text-blue-800 mb-1.5">Захиалагчаас шаардагдах зүйлс</label>
                            <p class="text-xs text-blue-600 mb-2">Ажлыг эхлүүлэхийн тулд танд захиалагчаас юу хэрэгтэй вэ? (Жишээ нь: Компанийн нэр, өнгөний сонголт, текст г.м)</p>
                            <textarea name="requirements" rows="3" class="w-full border border-blue-200 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"><?php echo htmlspecialchars($service['requirements']); ?></textarea>
                        </div>

                        <!-- Images Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Зураг (Дээд тал нь 5 зураг)
                                <span class="text-xs text-purple-600 font-normal ml-2 bg-purple-50 px-2 py-0.5 rounded border border-purple-100">Шинээр нэмэх боломжтой</span>
                            </label>
                            
                            <div class="flex flex-col items-start gap-4">
                                <!-- Hidden Inputs -->
                                <input type="file" name="images[]" id="images-storage" class="hidden" multiple accept="image/*">
                                <input type="hidden" name="deleted_images" id="deleted_images" value="">
                                
                                <!-- Visible Trigger -->
                                <input type="file" id="images-picker" accept="image/*" multiple class="hidden" onchange="handleNewImages(this)">
                                
                                <!-- Drag & Drop Area -->
                                <label for="images-picker" class="w-full h-24 bg-gray-50 rounded-xl border-2 border-dashed border-gray-300 flex flex-col items-center justify-center text-gray-400 cursor-pointer hover:bg-gray-100 transition">
                                    <i class="fas fa-plus text-2xl mb-1"></i>
                                    <p class="text-xs">Шинэ зураг нэмэх (JPG, PNG, AVIF)</p>
                                </label>
                                
                                <!-- Preview Container -->
                                <div id="image-preview-container" class="grid grid-cols-2 md:grid-cols-5 gap-3 w-full">
                                    
                                    <!-- Existing Images from DB -->
                                    <?php foreach($images as $idx => $img): 
                                        // Adjust image path: if it starts with uploads/, prepend ../
                                        $img_url = (strpos($img['preview_url'], 'http') === 0) ? $img['preview_url'] : '../' . $img['preview_url'];
                                    ?>
                                    <div class="image-preview-item relative group aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200 cursor-move shadow-sm" data-db-id="<?php echo $img['id']; ?>">
                                        <img src="<?php echo htmlspecialchars($img_url); ?>" class="w-full h-full object-cover pointer-events-none" onerror="this.src='https://placehold.co/150x150?text=No+Image'">
                                        <div class="absolute top-1 left-1 bg-white bg-opacity-90 rounded px-1.5 py-0.5 text-xs font-bold text-gray-700 index-badge border border-gray-200 shadow-sm z-10">
                                            <?php echo ($idx === 0) ? 'Cover' : '#' . ($idx + 1); ?>
                                        </div>
                                        <button type="button" onclick="markImageForDeletion(this, <?php echo $img['id']; ?>)" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition shadow-sm z-10 hover:bg-red-600">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <?php endforeach; ?>

                                    <!-- New images will be appended here by JS -->
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
                                <?php foreach($faqs as $faq): ?>
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 relative group transition hover:border-purple-200">
                                    <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 transition-colors"><i class="fas fa-times"></i></button>
                                    <input type="text" name="faq_questions[]" value="<?php echo htmlspecialchars($faq['question']); ?>" placeholder="Асуулт" class="w-full border-gray-300 rounded-lg mb-2 text-sm p-2 focus:ring-2 focus:ring-purple-500 outline-none" required>
                                    <textarea name="faq_answers[]" rows="2" placeholder="Хариулт" class="w-full border-gray-300 rounded-lg text-sm p-2 focus:ring-2 focus:ring-purple-500 outline-none" required><?php echo htmlspecialchars($faq['answer']); ?></textarea>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-4 pt-4 border-t border-gray-100">
                            <button type="submit" class="flex-1 bg-purple-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-purple-500/30 hover:bg-purple-700 hover:-translate-y-0.5 transition-all">
                                Хадгалах
                            </button>
                            <a href="my_services.php" class="px-6 py-3 border border-gray-300 text-gray-600 font-bold rounded-xl hover:bg-gray-50 transition flex items-center justify-center">
                                Болих
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column: Tips -->
            <div class="lg:col-span-1">
                <div class="bg-purple-50 border border-purple-100 rounded-2xl p-6 sticky top-24">
                    <h3 class="font-bold text-purple-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-lightbulb"></i> Зөвлөмж
                    </h3>
                    <ul class="space-y-4 text-sm text-purple-800">
                        <li class="flex gap-3">
                            <span class="w-6 h-6 bg-purple-200 text-purple-700 rounded-full flex items-center justify-center flex-shrink-0 font-bold text-xs">1</span>
                            <span><strong>Зураг:</strong> Чанартай, тод зураг сонгох нь үйлчлүүлэгчдийг татах гол түлхүүр юм.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 bg-purple-200 text-purple-700 rounded-full flex items-center justify-center flex-shrink-0 font-bold text-xs">2</span>
                            <span><strong>Тайлбар:</strong> Үйлчилгээгээ дэлгэрэнгүй, ойлгомжтой тайлбарлаарай.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 bg-purple-200 text-purple-700 rounded-full flex items-center justify-center flex-shrink-0 font-bold text-xs">3</span>
                            <span><strong>FAQ:</strong> Үйлчлүүлэгчдээс байнга ирдэг асуултуудад хариулт бэлдсэнээр та цагаа хэмнэх болно.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Scripts -->
<script src="https://cdn.tiny.cloud/1/g492qv0cyczptbbzcso4exirfkhg3l20o9z13ujy2i0arcw5/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
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

  // --- Image Handling ---
  let newServiceImages = []; 
  let deletedImageIds = [];

  // Form Submit Event Handler
  document.getElementById('editServiceForm').addEventListener('submit', function(e) {
      updateServiceInputFiles();
  });

  function handleNewImages(input) {
      const files = Array.from(input.files);
      const container = document.getElementById('image-preview-container');
      
      // Count valid images in DOM (excluding those marked for deletion if implemented in real-time, but here we just count elements)
      const currentCount = container.querySelectorAll('.image-preview-item').length;

      if (currentCount + files.length > 5) {
          alert('Та нийт 5 хүртэлх зураг оруулах боломжтой. Хуучин зургуудаас устгана уу.');
          input.value = '';
          return;
      }

      files.forEach(file => {
          newServiceImages.push(file);
          const arrayIndex = newServiceImages.length - 1; 
          
          const reader = new FileReader();
          reader.onload = function(e) {
              const div = document.createElement('div');
              div.className = 'image-preview-item relative group aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200 cursor-move shadow-sm new-image';
              div.setAttribute('data-new-index', arrayIndex);
              
              div.innerHTML = `
                  <img src="${e.target.result}" class="w-full h-full object-cover pointer-events-none">
                  <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-all"></div>
                  <div class="absolute top-1 left-1 bg-white bg-opacity-90 rounded px-1.5 py-0.5 text-xs font-bold text-gray-700 index-badge border border-gray-200 shadow-sm z-10">New</div>
                  <button type="button" onclick="removeNewImage(this, ${arrayIndex})" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition shadow-sm z-10 hover:bg-red-600">
                      <i class="fas fa-times"></i>
                  </button>
              `;
              container.appendChild(div);
              updateBadges();
          }
          reader.readAsDataURL(file);
      });

      updateServiceInputFiles();
      input.value = '';
  }

  function markImageForDeletion(btn, id) {
      if(confirm('Энэ зургийг устгах уу?')) {
          deletedImageIds.push(id);
          document.getElementById('deleted_images').value = deletedImageIds.join(',');
          btn.closest('.image-preview-item').remove();
          updateBadges();
      }
  }

  function removeNewImage(btn, index) {
      if (newServiceImages[index]) {
          newServiceImages[index] = null; // Mark as deleted
      }
      btn.closest('.image-preview-item').remove();
      updateServiceInputFiles();
      updateBadges();
  }

  function updateServiceInputFiles() {
      const dataTransfer = new DataTransfer();
      newServiceImages.forEach(file => {
          if (file !== null) {
              dataTransfer.items.add(file);
          }
      });
      document.getElementById('images-storage').files = dataTransfer.files;
  }

  function updateBadges() {
      const badges = document.querySelectorAll('.index-badge');
      badges.forEach((badge, index) => {
          // If it's a new image, keep the "New" badge to avoid confusion, or update all to numbers
          // For edit mode, keeping "New" helps user distinguish. 
          // If you prefer numbers:
          // badge.innerHTML = (index === 0) ? '<span class="text-purple-600">Cover</span>' : '#' + (index + 1);
          
          if (!badge.innerText.includes('New')) {
             badge.innerHTML = (index === 0) ? '<span class="text-purple-600">Cover</span>' : '#' + (index + 1);
          }
      });
  }

  // Sortable
  const previewContainer = document.getElementById('image-preview-container');
  new Sortable(previewContainer, {
      animation: 150,
      ghostClass: 'sortable-ghost',
      onEnd: function () {
          updateBadges();
      }
  });

  // --- FAQ Logic ---
  function addFaqRow() {
      const container = document.getElementById('faq-container');
      const div = document.createElement('div');
      div.className = 'bg-gray-50 p-3 rounded-lg border border-gray-200 relative group transition hover:border-purple-200 fade-in';
      div.innerHTML = `
          <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 transition-colors"><i class="fas fa-times"></i></button>
          <input type="text" name="faq_questions[]" placeholder="Асуулт" class="w-full border-gray-300 rounded-lg mb-2 text-sm p-2 focus:ring-2 focus:ring-purple-500 outline-none" required>
          <textarea name="faq_answers[]" rows="2" placeholder="Хариулт" class="w-full border-gray-300 rounded-lg text-sm p-2 focus:ring-2 focus:ring-purple-500 outline-none" required></textarea>
      `;
      container.appendChild(div);
  }
</script>

<?php include '../includes/footer.php'; ?>