<?php
session_start();
require_once '../includes/db.php';
require_once '../kids/db_kids.php'; // KIDS Database connection

// Админ эрх шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// Ангилал татах
$categories = $pdo_kids->query("SELECT * FROM kids_categories ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $category_id = intval($_POST['category_id']);
    $target_age = $_POST['target_age'];
    $description = $_POST['description'];
    $page_count = intval($_POST['page_count']);
    $is_premium = isset($_POST['is_premium']) ? 1 : 0;
    $price = 0.00; // Төлбөрийн логик хасагдсан тул 0
    $file_size_text = $_POST['file_size_text'];

    // Validation
    if (empty($title) || empty($category_id)) {
        $error = "Гарчиг болон Ангилал заавал шаардлагатай.";
    } 
    // Шинэ бүртгэл үед файл заавал байх ёстой (гэхдээ ID хэрэгтэй тул эхлээд шалгана)
    elseif (empty($_FILES['main_file']['name'])) {
        $error = "Үндсэн файл (PDF/ZIP) оруулах шаардлагатай.";
    } 
    else {
        try {
            // 1. Эхлээд баазад мэдээллээ оруулаад ID-гаа авна (File path-уудыг түр хоосон орхино)
            $pdo_kids->beginTransaction();

            $sql = "INSERT INTO kids_materials (category_id, title, target_age, description, is_premium, price, file_path, cover_image, file_size_text, page_count, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, '', '', ?, ?, 'active', NOW())";
            $stmt = $pdo_kids->prepare($sql);
            $stmt->execute([
                $category_id, $title, $target_age, $description, 
                $is_premium, $price, $file_size_text, $page_count
            ]);
            
            $material_id = $pdo_kids->lastInsertId();

            // 2. Хавтас үүсгэх: uploads/kids/{id}/ болон uploads/kids/{id}/previews/
            $base_dir = "../uploads/kids/" . $material_id . "/";
            $previews_dir = $base_dir . "previews/";

            if (!is_dir($base_dir)) mkdir($base_dir, 0777, true);
            if (!is_dir($previews_dir)) mkdir($previews_dir, 0777, true);

            $db_file_path = '';
            $db_cover_path = '';

            // 3. Үндсэн файл хуулах
            if (isset($_FILES['main_file']) && $_FILES['main_file']['error'] == 0) {
                $ext = pathinfo($_FILES['main_file']['name'], PATHINFO_EXTENSION);
                // Файлын нэрийг 'file.ext' эсвэл анхны нэрээр нь хадгалж болно. Энд 'file.ext' ашиглав.
                $new_file_name = 'file.' . $ext; 
                $destination = $base_dir . $new_file_name;
                
                if (move_uploaded_file($_FILES['main_file']['tmp_name'], $destination)) {
                    $db_file_path = "uploads/kids/$material_id/" . $new_file_name;
                    
                    // Хэмжээ тооцох (хэрэв гараар оруулаагүй бол)
                    if(empty($file_size_text)) {
                        $bytes = filesize($destination);
                        if ($bytes >= 1048576) $file_size_text = number_format($bytes / 1048576, 1) . ' MB';
                        else $file_size_text = number_format($bytes / 1024, 0) . ' KB';
                    }
                }
            }

            // 4. Ковер зураг хуулах
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
                $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                $new_cover_name = 'cover.' . $ext;
                $destination = $base_dir . $new_cover_name;
                
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $destination)) {
                    $db_cover_path = "uploads/kids/$material_id/" . $new_cover_name;
                }
            }

            // 5. Баазыг үндсэн файл болон коверын замаар шинэчлэх
            $update_sql = "UPDATE kids_materials SET file_path = ?, cover_image = ?, file_size_text = ? WHERE id = ?";
            $update_stmt = $pdo_kids->prepare($update_sql);
            $update_stmt->execute([$db_file_path, $db_cover_path, $file_size_text, $material_id]);

            // 6. Preview зургууд хуулах
            if (isset($_FILES['previews'])) {
                $total_files = count($_FILES['previews']['name']);
                $insert_preview_sql = "INSERT INTO kids_material_previews (material_id, image_path, sort_order) VALUES (?, ?, ?)";
                $insert_preview_stmt = $pdo_kids->prepare($insert_preview_sql);

                for ($i = 0; $i < $total_files; $i++) {
                    if ($_FILES['previews']['error'][$i] == 0) {
                        $p_ext = pathinfo($_FILES['previews']['name'][$i], PATHINFO_EXTENSION);
                        $p_new_name = uniqid('prev_') . '.' . $p_ext;
                        $p_destination = $previews_dir . $p_new_name;
                        
                        if (move_uploaded_file($_FILES['previews']['tmp_name'][$i], $p_destination)) {
                            $db_preview_path = "uploads/kids/$material_id/previews/" . $p_new_name;
                            // Sort order can be just $i + 1
                            $insert_preview_stmt->execute([$material_id, $db_preview_path, $i + 1]);
                        }
                    }
                }
            }

            $pdo_kids->commit();
            $_SESSION['message'] = "KIDS Материал амжилттай нэмэгдлээ!";
            header("Location: kids.php");
            exit;

        } catch (PDOException $e) {
            $pdo_kids->rollBack();
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
    <title>KIDS Материал Нэмэх - FileZone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
</head>
<body class="font-sans text-slate-800 antialiased bg-slate-50">

    <div class="flex h-screen overflow-hidden">
        
        <?php include 'sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shadow-sm z-10">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden text-slate-500"><i class="fas fa-bars text-xl"></i></button>
                    <h1 class="text-xl font-bold text-slate-800">Материал нэмэх</h1>
                </div>
                <a href="kids.php" class="text-slate-500 hover:text-indigo-600 font-medium text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Буцах
                </a>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                <div class="max-w-3xl mx-auto">
                    
                    <?php if($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <?php echo $error; ?>
                    </div>
                    <?php endif; ?>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-slate-800">Шинэ материалын мэдээлэл</h3>
                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">DB: filezone_kids</span>
                        </div>
                        
                        <form method="POST" action="" enctype="multipart/form-data" class="p-6 space-y-6">
                            
                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Гарчиг</label>
                                <input type="text" name="title" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required placeholder="Жнь: Тоо бодож сурцгаая">
                            </div>

                            <!-- Category & Age -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Ангилал</label>
                                    <select name="category_id" class="w-full rounded-lg border-slate-300 border px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                        <option value="">Сонгох...</option>
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Насны ангилал</label>
                                    <select name="target_age" class="w-full rounded-lg border-slate-300 border px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="2-3">2-3 нас</option>
                                        <option value="3-4">3-4 нас</option>
                                        <option value="4-5">4-5 нас</option>
                                        <option value="school_prep">Сургуулийн бэлтгэл</option>
                                        <option value="grade-1">1-р анги</option>
                                        <option value="all">Бүх нас</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Premium Only Checkbox -->
                            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                                <div class="flex items-center">
                                    <input type="checkbox" id="is_premium" name="is_premium" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                    <label for="is_premium" class="ml-2 text-sm font-medium text-slate-800">Төлбөртэй материал (Premium)</label>
                                </div>
                                <p class="text-xs text-slate-500 mt-2 ml-6">Сонговол хэрэглэгч эрх авч байж үзнэ, сонгохгүй бол үнэгүй.</p>
                            </div>

                            <!-- File & Cover -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Үндсэн файл (PDF/ZIP)</label>
                                    <input type="file" name="main_file" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept=".pdf,.zip,.rar" required>
                                    <p class="text-xs text-gray-500 mt-1">Хадгалах зам: uploads/kids/{id}/file.zip</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Ковер зураг (Main Image)</label>
                                    <input type="file" name="cover_image" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept="image/*">
                                    <p class="text-xs text-gray-500 mt-1">Хадгалах зам: uploads/kids/{id}/cover.jpg</p>
                                </div>
                            </div>

                            <!-- Additional Previews -->
                            <div class="border-2 border-dashed border-slate-300 rounded-lg p-4 bg-slate-50">
                                <label class="block text-sm font-medium text-slate-700 mb-2">Нэмэлт зургууд (Preview Images)</label>
                                <input type="file" name="previews[]" multiple class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/*">
                                <p class="text-xs text-gray-500 mt-1">Олон зураг сонгож болно. Хадгалах зам: uploads/kids/{id}/previews/</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Хуудасны тоо</label>
                                    <input type="number" name="page_count" class="w-full rounded-lg border-slate-300 border px-3 py-2" value="1">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Хэмжээ (Текстээр, Жнь: 5MB)</label>
                                    <input type="text" name="file_size_text" class="w-full rounded-lg border-slate-300 border px-3 py-2" placeholder="Хоосон орхивол автоматаар тооцно">
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Тайлбар</label>
                                <textarea name="description" rows="3" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                            </div>

                            <!-- Submit -->
                            <div class="flex items-center justify-end pt-4 border-t border-slate-100">
                                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium shadow-sm">
                                    Хадгалах
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>