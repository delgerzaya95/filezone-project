<?php
session_start();
require_once '../includes/db.php';
require_once '../kids/db_kids.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: kids.php");
    exit;
}

$id = intval($_GET['id']);
$message = '';
$error = '';

// Fetch Material Data
$stmt = $pdo_kids->prepare("SELECT * FROM kids_materials WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    die("Материал олдсонгүй.");
}

// Fetch Previews
$stmt_prev = $pdo_kids->prepare("SELECT * FROM kids_material_previews WHERE material_id = ? ORDER BY sort_order ASC");
$stmt_prev->execute([$id]);
$previews = $stmt_prev->fetchAll();

$categories = $pdo_kids->query("SELECT * FROM kids_categories ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. DELETE PREVIEWS (Хэрэв сонгосон бол)
    if (isset($_POST['delete_previews'])) {
        foreach ($_POST['delete_previews'] as $p_id) {
            $p_id = intval($p_id);
            // Файлыг устгахын тулд замыг авна
            $stmt_get_path = $pdo_kids->prepare("SELECT image_path FROM kids_material_previews WHERE id = ? AND material_id = ?");
            $stmt_get_path->execute([$p_id, $id]);
            $path_row = $stmt_get_path->fetch();
            
            if ($path_row) {
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
    $target_age = $_POST['target_age'];
    $description = $_POST['description'];
    $page_count = intval($_POST['page_count']);
    $is_premium = isset($_POST['is_premium']) ? 1 : 0;
    $status = $_POST['status'];
    $file_size_text = $_POST['file_size_text'];

    // Хавтас бэлтгэх (Хуучин бүтэцтэй байвал шинээр үүсгэх)
    $base_dir = "../uploads/kids/" . $id . "/";
    $previews_dir = $base_dir . "previews/";
    if (!is_dir($base_dir)) mkdir($base_dir, 0777, true);
    if (!is_dir($previews_dir)) mkdir($previews_dir, 0777, true);

    // 2. Update Cover (uploads/kids/{id}/cover.ext)
    $cover_path = $item['cover_image'];
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $new_name = 'cover_' . time() . '.' . $ext; // time() нэмсэн нь cache асуудлаас сэргийлэх
        $destination = $base_dir . $new_name;
        
        // Хуучин файлыг устгах (хэрэв байгаа бол)
        if ($cover_path && file_exists('../' . $cover_path)) {
            unlink('../' . $cover_path);
        }

        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $destination)) {
            $cover_path = "uploads/kids/$id/" . $new_name;
        }
    }

    // 3. Update Main File (uploads/kids/{id}/file.ext)
    $file_path = $item['file_path'];
    if (isset($_FILES['main_file']) && $_FILES['main_file']['error'] == 0) {
        $ext = pathinfo($_FILES['main_file']['name'], PATHINFO_EXTENSION);
        $new_name = 'file_' . time() . '.' . $ext;
        $destination = $base_dir . $new_name;

        // Хуучин файлыг устгах
        if ($file_path && file_exists('../' . $file_path)) {
            unlink('../' . $file_path);
        }

        if (move_uploaded_file($_FILES['main_file']['tmp_name'], $destination)) {
            $file_path = "uploads/kids/$id/" . $new_name;
            // Auto size update
            if(empty($file_size_text)) {
                $bytes = filesize($destination);
                if ($bytes >= 1048576) $file_size_text = number_format($bytes / 1048576, 1) . ' MB';
                else $file_size_text = number_format($bytes / 1024, 0) . ' KB';
            }
        }
    }

    // 4. ADD NEW PREVIEWS
    if (isset($_FILES['previews'])) {
        $total_files = count($_FILES['previews']['name']);
        $insert_preview_stmt = $pdo_kids->prepare("INSERT INTO kids_material_previews (material_id, image_path, sort_order) VALUES (?, ?, ?)");
        
        // Одоо байгаа зургийн тоог авах (дараалал үүсгэхэд хэрэгтэй)
        $current_count_stmt = $pdo_kids->prepare("SELECT MAX(sort_order) FROM kids_material_previews WHERE material_id = ?");
        $current_count_stmt->execute([$id]);
        $max_order = $current_count_stmt->fetchColumn() ?: 0;

        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['previews']['error'][$i] == 0) {
                $p_ext = pathinfo($_FILES['previews']['name'][$i], PATHINFO_EXTENSION);
                $p_new_name = uniqid('prev_') . '.' . $p_ext;
                $p_destination = $previews_dir . $p_new_name;
                
                if (move_uploaded_file($_FILES['previews']['tmp_name'][$i], $p_destination)) {
                    $db_preview_path = "uploads/kids/$id/previews/" . $p_new_name;
                    $max_order++;
                    $insert_preview_stmt->execute([$id, $db_preview_path, $max_order]);
                }
            }
        }
    }

    try {
        $sql = "UPDATE kids_materials SET 
                category_id=?, title=?, target_age=?, description=?, 
                is_premium=?, file_path=?, cover_image=?, 
                file_size_text=?, page_count=?, status=? 
                WHERE id=?";
        $stmt = $pdo_kids->prepare($sql);
        $stmt->execute([
            $category_id, $title, $target_age, $description, 
            $is_premium, $file_path, $cover_path, 
            $file_size_text, $page_count, $status, $id
        ]);
        
        $_SESSION['message'] = "Амжилттай шинэчлэгдлээ!";
        header("Location: edit_kids.php?id=$id"); // Refresh to show new images
        exit;
    } catch (PDOException $e) {
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
</head>
<body class="font-sans text-slate-800 antialiased bg-slate-50">

    <div class="flex h-screen overflow-hidden">
        <?php include 'sidebar.php'; ?>
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shadow-sm z-10">
                <h1 class="text-xl font-bold text-slate-800">Материал Засах</h1>
                <a href="kids.php" class="text-slate-500 hover:text-indigo-600 font-medium text-sm">Буцах</a>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    
                    <?php if($message): ?><div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo $message; ?></div><?php endif; ?>
                    <?php if($error): ?><div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Гарчиг</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" class="w-full rounded-lg border-slate-300 border px-3 py-2" required>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Ангилал</label>
                                <select name="category_id" class="w-full rounded-lg border-slate-300 border px-3 py-2">
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $item['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Нас</label>
                                <select name="target_age" class="w-full rounded-lg border-slate-300 border px-3 py-2">
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

                        <!-- Premium Checkbox -->
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <div class="flex items-center">
                                <input type="checkbox" id="is_premium" name="is_premium" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" <?php echo $item['is_premium'] ? 'checked' : ''; ?>>
                                <label for="is_premium" class="ml-2 text-sm font-medium text-slate-800">Төлбөртэй (Premium)</label>
                            </div>
                        </div>

                        <!-- Main File & Cover -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-slate-100 pb-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Үндсэн файл (PDF/ZIP)</label>
                                <input type="file" name="main_file" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <p class="text-xs text-gray-500 mt-1 truncate">Одоо: <?php echo htmlspecialchars($item['file_path']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Ковер зураг</label>
                                <input type="file" name="cover_image" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <?php if($item['cover_image']): ?>
                                    <img src="../<?php echo $item['cover_image']; ?>" class="h-20 mt-2 rounded border object-cover">
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Preview Images Section -->
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <h4 class="text-sm font-bold text-slate-800 mb-3">Нэмэлт зургууд (Previews)</h4>
                            
                            <!-- Display Existing Previews -->
                            <?php if (count($previews) > 0): ?>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                    <?php foreach ($previews as $prev): ?>
                                        <div class="relative group border rounded-lg overflow-hidden bg-white">
                                            <img src="../<?php echo $prev['image_path']; ?>" class="w-full h-24 object-cover">
                                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                                <label class="flex items-center gap-2 text-white text-xs cursor-pointer">
                                                    <input type="checkbox" name="delete_previews[]" value="<?php echo $prev['id']; ?>" class="w-4 h-4 text-red-600 rounded">
                                                    Устгах
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <p class="text-xs text-red-500 mb-4">* Устгах зургуудыг чагтлаад "Шинэчлэх" дарна уу.</p>
                            <?php else: ?>
                                <p class="text-xs text-gray-500 mb-4 italic">Нэмэлт зураг байхгүй байна.</p>
                            <?php endif; ?>

                            <!-- Upload New Previews -->
                            <label class="block text-xs font-medium text-slate-600 mb-1">Шинээр зураг нэмэх</label>
                            <input type="file" name="previews[]" multiple class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/*">
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Хуудасны тоо</label>
                                <input type="number" name="page_count" value="<?php echo $item['page_count']; ?>" class="w-full rounded-lg border-slate-300 border px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Статус</label>
                                <select name="status" class="w-full rounded-lg border-slate-300 border px-3 py-2">
                                    <option value="active" <?php echo $item['status']=='active'?'selected':''; ?>>Active</option>
                                    <option value="inactive" <?php echo $item['status']=='inactive'?'selected':''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Тайлбар</label>
                            <textarea name="description" rows="3" class="w-full rounded-lg border-slate-300 border px-3 py-2"><?php echo htmlspecialchars($item['description']); ?></textarea>
                        </div>
                        
                        <input type="hidden" name="file_size_text" value="<?php echo $item['file_size_text']; ?>">

                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 w-full md:w-auto">
                            Мэдээллийг шинэчлэх
                        </button>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>