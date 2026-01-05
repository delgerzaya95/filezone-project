<?php
session_start();
require_once '../includes/db.php';

// Админ эрх шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// Ангилал татах (service_categories хүснэгтээс)
try {
    // Хэрэв service_categories хүснэгт байхгүй бол алдаа өгч магадгүй тул try-catch дотор хийв
    $categories = $pdo->query("SELECT id, name FROM service_categories ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    $error = "Ангилал татахад алдаа гарлаа (service_categories хүснэгт үүссэн эсэхийг шалгана уу): " . $e->getMessage();
}

// Үйлчилгээ нэмэх (POST request)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Үндсэн мэдээлэл авах
    $title = trim($_POST['title']);
    $category_id = intval($_POST['category_id']);
    
    // Үнэ (Range)
    $price_min = floatval($_POST['price_min']);
    $price_max = !empty($_POST['price_max']) ? floatval($_POST['price_max']) : null;
    
    // Хугацаа & Засвар
    $delivery_time = intval($_POST['delivery_time']);
    $delivery_unit = $_POST['delivery_unit']; // hour, day, week, month
    $revision_count = intval($_POST['revision_count']); // 0 = no revision
    
    // Тайлбар & Шаардлага
    $description = $_POST['description']; // HTML content from TinyMCE
    $requirements = $_POST['requirements'];
    
    $user_id = $_SESSION['user_id']; 

    // FAQ Data
    $faq_questions = isset($_POST['faq_questions']) ? $_POST['faq_questions'] : [];
    $faq_answers = isset($_POST['faq_answers']) ? $_POST['faq_answers'] : [];

    // Зургийн дараалал (SortableJS-ээс ирсэн JSON string)
    // Жнь: ["0", "2", "1"] - Эхний зураг индекс 0, дараах нь 2 гэх мэт
    $image_order = isset($_POST['image_order']) ? json_decode($_POST['image_order'], true) : [];

    // Validation
    if (empty($title) || empty($price_min) || empty($category_id)) {
        $error = "Гарчиг, Үнэ (Эхлэх) болон Ангилал заавал бөглөх шаардлагатай.";
    }

    // Зургийн тоог шалгах (Макс 5)
    $image_count = 0;
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $image_count = count($_FILES['images']['name']);
    }
    
    if ($image_count > 5) {
        $error = "Та дээд тал нь 5 зураг оруулах боломжтой.";
    }

    if (empty($error)) {
        try {
            $pdo->beginTransaction();
            // SQL: Insert into services
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

            // ---------------------------------------------------------
            // 2. FAQ (Түгээмэл асуулт) хадгалах
            // ---------------------------------------------------------
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

            // ---------------------------------------------------------
            // 3. Зураг хуулах & service_previews рүү хадгалах
            // ---------------------------------------------------------
            if ($image_count > 0) {
                // Хадгалах зам: uploads/service/{user_id}/{service_id}/
                $upload_base_dir = '../uploads/service/';
                $service_dir_path = $upload_base_dir . $user_id . '/' . $service_id . '/';

                // Хавтас үүсгэх
                if (!is_dir($service_dir_path)) {
                    mkdir($service_dir_path, 0777, true);
                }

                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $uploaded_files_map = []; // index => db_path

                // Файлуудыг физик байдлаар хуулах
                foreach ($_FILES['images']['name'] as $key => $filename) {
                    $tmp_name = $_FILES['images']['tmp_name'][$key];
                    $file_error = $_FILES['images']['error'][$key];
                    $file_size = $_FILES['images']['size'][$key];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    if ($file_error === 0 && in_array($ext, $allowed) && $file_size <= 5 * 1024 * 1024) { // Max 5MB
                        $new_filename = uniqid('img_', true) . '.' . $ext;
                        $destination = $service_dir_path . $new_filename;
                        
                        // DB-д хадгалах зам (relative path)
                        $db_path = 'uploads/service/' . $user_id . '/' . $service_id . '/' . $new_filename;

                        if (move_uploaded_file($tmp_name, $destination)) {
                            // Key-ээр нь хадгалж авна (Frontend-ээс ирэх дараалалтай тааруулахын тулд)
                            $uploaded_files_map[$key] = $db_path;
                        }
                    }
                }

                // DB руу дарааллын дагуу хадгалах
                if (!empty($uploaded_files_map)) {
                    $order_counter = 1; // 1-ээс эхэлнэ
                    $first_image_path = null;

                    // Хэрэв image_order ирсэн бол тэр дарааллаар, үгүй бол энгийн дарааллаар
                    // image_order нь файлын оролтын индексүүдийг агуулна (жнь: [2, 0, 1])
                    $loop_order = !empty($image_order) ? $image_order : array_keys($uploaded_files_map);

                    $stmt_img = $pdo->prepare("INSERT INTO service_previews (service_id, preview_url, order_index) VALUES (?, ?, ?)");

                    foreach ($loop_order as $file_index) {
                        // $file_index нь string байж магадгүй тул int болгож шалгана
                        if (isset($uploaded_files_map[$file_index])) {
                            $path = $uploaded_files_map[$file_index];
                            
                            $stmt_img->execute([$service_id, $path, $order_counter]);

                            if ($order_counter === 1) {
                                $first_image_path = $path; // Эхний зургийг үндсэн зураг (cover) болгоно
                            }
                            $order_counter++;
                        }
                    }

                    // 4. Үндсэн services хүснэгтийн cover_image-ийг update хийх (Backward compatibility)
                    if ($first_image_path) {
                        try {
                            $update_sql = "UPDATE services SET cover_image = ? WHERE id = ?";
                            $stmt_update = $pdo->prepare($update_sql);
                            $stmt_update->execute([$first_image_path, $service_id]);
                        } catch (Exception $ex) {
                            // Хэрэв cover_image багана байхгүй бол алдааг үл тооно
                        }
                    }
                }
            }

            $pdo->commit();
            $_SESSION['message'] = "Үйлчилгээ амжилттай нэмэгдлээ. Төлөв: Хүлээгдэж буй.";
            header("Location: services.php");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Баазад хадгалахад алдаа гарлаа: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Үйлчилгээ нэмэх - FileZone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- TinyMCE Text Editor -->
    <script src="https://cdn.tiny.cloud/1/g492qv0cyczptbbzcso4exirfkhg3l20o9z13ujy2i0arcw5/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
    
    <!-- SortableJS for drag and drop -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

    <script>
      tinymce.init({
        selector: '#description',
        plugins: 'emoticons lists link image code preview charmap',
        toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | emoticons charmap | link | code',
        height: 300,
        menubar: false,
        entity_encoding: "raw" // Эможи болон тусгай тэмдэгтүүдийг зөв хадгалах
      });
    </script>
    <script src="js/tailwind-config.js"></script>
    
    <style>
        .image-preview-item {
            transition: all 0.3s ease;
            position: relative;
        }
        .image-preview-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .sortable-ghost {
            opacity: 0.5;
            background: #f3f4f6;
        }
        .index-badge {
            z-index: 10;
        }
    </style>
</head>
<body class="font-sans text-slate-800 antialiased bg-slate-50">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- HEADER -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shadow-sm z-10">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden text-slate-500 hover:text-slate-700 focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl font-bold text-slate-800">Үйлчилгээ нэмэх</h1>
                </div>
                
                <div class="flex items-center gap-4">
                    <a href="services.php" class="text-slate-500 hover:text-indigo-600 font-medium text-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Буцах
                    </a>
                </div>
            </header>

            <!-- SCROLLABLE CONTENT -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                
                <div class="max-w-5xl mx-auto">
                    
                    <?php if($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                            <h3 class="text-lg font-bold text-slate-800">Шинэ үйлчилгээний мэдээлэл</h3>
                        </div>
                        
                        <form method="POST" action="" enctype="multipart/form-data" class="p-6 space-y-8" id="serviceForm">
                            
                            <!-- 1. Basic Info -->
                            <div class="space-y-4">
                                <h4 class="text-md font-semibold text-slate-700 border-b pb-2">Үндсэн мэдээлэл</h4>
                                
                                <div>
                                    <label for="title" class="block text-sm font-medium text-slate-700 mb-1">Үйлчилгээний нэр (Гарчиг)</label>
                                    <input type="text" name="title" id="title" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" required placeholder="Жнь: Би танд мэргэжлийн лого бүтээж өгнө">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="category_id" class="block text-sm font-medium text-slate-700 mb-1">Ангилал (Service Category)</label>
                                        <select name="category_id" id="category_id" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white transition-all" required>
                                            <option value="">Сонгох...</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if(empty($categories)): ?>
                                            <p class="text-xs text-red-500 mt-1">Ангилал олдсонгүй. Эхлээд service_categories дээр ангилал нэмнэ үү.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Pricing & Delivery -->
                            <div class="space-y-4">
                                <h4 class="text-md font-semibold text-slate-700 border-b pb-2">Үнэ болон Хугацаа</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Эхлэх үнэ (₮)</label>
                                        <input type="number" name="price_min" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all" required min="0" placeholder="50000">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Дуусах үнэ (₮) - Заавал биш</label>
                                        <input type="number" name="price_max" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all" min="0" placeholder="100000 (Хоосон байж болно)">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Гүйцэтгэх хугацаа (Тоо)</label>
                                        <input type="number" name="delivery_time" id="delivery_time" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all" required min="1" value="1">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Нэгж</label>
                                        <select name="delivery_unit" class="w-full rounded-lg border-slate-300 border px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                                            <option value="hour">Цаг</option>
                                            <option value="day" selected>Хоног</option>
                                            <option value="week">Долоо хоног</option>
                                            <option value="month">Сар</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Засвар хийх тоо (Revisions)</label>
                                        <input type="number" name="revision_count" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all" min="0" value="0" placeholder="0 = Засваргүй">
                                        <p class="text-xs text-gray-500 mt-1">Хэдэн удаа үнэгүй засварлах вэ?</p>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Details & Media -->
                            <div class="space-y-4">
                                <h4 class="text-md font-semibold text-slate-700 border-b pb-2">Дэлгэрэнгүй & Зураг</h4>

                                <!-- Images Upload Area -->
                                <div class="border-2 border-dashed border-slate-300 rounded-lg p-6 bg-slate-50">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Зураг (Дээд тал нь 5 зураг)</label>
                                    
                                    <div class="flex items-center justify-center w-full mb-4">
                                        <label for="images" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-white hover:bg-gray-50 transition-colors">
                                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                                <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Зураг хуулах</span> эсвэл чирж оруулна уу</p>
                                                <p class="text-xs text-gray-500">JPG, PNG, WEBP (Max 5MB)</p>
                                            </div>
                                            <input type="file" name="images[]" id="images" accept="image/*" multiple class="hidden">
                                        </label>
                                    </div>

                                    <p class="text-xs text-gray-500 mb-2 italic">* Зургуудыг хулганаар чирж дарааллыг өөрчлөх боломжтой. Эхний зураг Нүүр зураг (Cover) болно.</p>

                                    <!-- Image Preview Container -->
                                    <div id="image-preview-container" class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                        <!-- Previews will be injected here by JS -->
                                    </div>
                                    <input type="hidden" name="image_order" id="image_order">
                                </div>

                                <!-- Description (Rich Text) -->
                                <div>
                                    <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Дэлгэрэнгүй тайлбар</label>
                                    <textarea name="description" id="description" class="w-full rounded-lg border-slate-300 border px-3 py-2"></textarea>
                                </div>

                                <!-- Requirements -->
                                <div>
                                    <label for="requirements" class="block text-sm font-medium text-slate-700 mb-1">Захиалагчаас шаардах зүйлс</label>
                                    <textarea name="requirements" id="requirements" rows="3" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Ажил эхлэхэд юу хэрэгтэй вэ? Жнь: Логоны өнгө, компанийн нэр..."></textarea>
                                </div>
                            </div>

                            <!-- 4. FAQs -->
                            <div class="space-y-4">
                                <div class="flex justify-between items-center border-b pb-2">
                                    <h4 class="text-md font-semibold text-slate-700">Түгээмэл асуулт хариулт (FAQ)</h4>
                                    <button type="button" onclick="addFaqRow()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                                        <i class="fas fa-plus-circle"></i> Асуулт нэмэх
                                    </button>
                                </div>
                                
                                <div id="faq-container" class="space-y-3">
                                    <!-- Dynamic FAQ Rows will appear here -->
                                </div>
                                <p class="text-xs text-gray-500">Хэрэглэгчид үйлчилгээний талаар байнга асуудаг асуултуудыг энд оруулна уу.</p>
                            </div>

                            <!-- Buttons -->
                            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                                <a href="services.php" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors font-medium">
                                    Цуцлах
                                </a>
                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium shadow-sm">
                                    <i class="fas fa-plus mr-1"></i> Үйлчилгээ нэмэх
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

            </main>
        </div>
    </div>
    
    <script src="js/script.js"></script>
    <script>
        // --- Image Handling ---
        const imageInput = document.getElementById('images');
        const previewContainer = document.getElementById('image-preview-container');
        const imageOrderInput = document.getElementById('image_order');

        imageInput.addEventListener('change', function(event) {
            const files = Array.from(event.target.files);
            
            if (files.length > 5) {
                alert('Та дээд тал нь 5 зураг сонгох боломжтой.');
                this.value = ''; 
                previewContainer.innerHTML = '';
                return;
            }

            previewContainer.innerHTML = ''; 
            
            // Initialize order array based on file index
            const initialOrder = files.map((_, index) => index);
            imageOrderInput.value = JSON.stringify(initialOrder);

            files.forEach((file, index) => {
                if (!file.type.startsWith('image/')) return;

                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'image-preview-item relative group aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200 cursor-move shadow-sm';
                    div.setAttribute('data-index', index); // Keep track of original file index

                    div.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-all"></div>
                        <div class="absolute top-1 left-1 bg-white bg-opacity-90 rounded px-1.5 py-0.5 text-xs font-bold text-gray-700 index-badge border border-gray-200">
                            #${index + 1}
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-[10px] p-1 text-center opacity-0 group-hover:opacity-100 transition-opacity">
                            Чирж зөөнө үү
                        </div>
                    `;
                    previewContainer.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        });

        // Initialize SortableJS
        if(document.getElementById('image-preview-container')) {
            new Sortable(previewContainer, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function (evt) {
                    // Re-calculate order based on DOM position
                    const items = previewContainer.querySelectorAll('.image-preview-item');
                    const newOrder = Array.from(items).map(item => item.getAttribute('data-index'));
                    
                    // Update hidden input with JSON string of indices
                    imageOrderInput.value = JSON.stringify(newOrder);

                    // Update visual badges #1, #2...
                    items.forEach((item, idx) => {
                        item.querySelector('.index-badge').textContent = '#' + (idx + 1);
                        if (idx === 0) {
                            item.querySelector('.index-badge').textContent = 'Cover';
                            item.querySelector('.index-badge').classList.add('text-green-600');
                        } else {
                            item.querySelector('.index-badge').classList.remove('text-green-600');
                        }
                    });
                }
            });
        }

        // --- FAQ Handling ---
        function addFaqRow() {
            const container = document.getElementById('faq-container');
            const div = document.createElement('div');
            div.className = 'bg-gray-50 p-4 rounded-lg border border-gray-200 relative group transition-all hover:border-indigo-200';
            div.innerHTML = `
                <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 bg-white rounded-full p-1 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity">
                    <i class="fas fa-times"></i>
                </button>
                <div class="mb-3">
                    <input type="text" name="faq_questions[]" placeholder="Асуулт (Жнь: Лого хэдэн хувилбартай вэ?)" class="w-full border-gray-300 rounded-md shadow-sm py-2 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>
                <div>
                    <textarea name="faq_answers[]" rows="2" placeholder="Хариулт" class="w-full border-gray-300 rounded-md shadow-sm py-2 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500" required></textarea>
                </div>
            `;
            container.appendChild(div);
        }
    </script>
</body>
</html>