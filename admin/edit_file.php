<?php
session_start();

// Database холболт
require_once '../includes/db.php';

// Админ эрх шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ID шалгах
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$file_id = intval($_GET['id']);
$message = '';
$error = '';

// Хадгалах үйлдэл (POST хүсэлт)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $status = $_POST['status'];
    // Ангилал болон бусад талбаруудыг шаардлагатай бол нэмнэ
    // $category_id = $_POST['category_id'];

    try {
        $sql = "UPDATE files SET title = ?, description = ?, price = ?, status = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $description, $price, $status, $file_id]);
        
        $message = "Файлын мэдээлэл амжилттай шинэчлэгдлээ!";
    } catch (PDOException $e) {
        $error = "Алдаа гарлаа: " . $e->getMessage();
    }
}

// Файлын мэдээллийг татах
try {
    $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ?");
    $stmt->execute([$file_id]);
    $file = $stmt->fetch();

    if (!$file) {
        die("Файл олдсонгүй.");
    }
} catch (PDOException $e) {
    die("Өгөгдлийн баазтай холбогдоход алдаа гарлаа.");
}

// Ангиллуудыг татах (Dropdown-д харуулахын тулд)
try {
    $categories_stmt = $pdo->query("SELECT * FROM categories");
    $categories = $categories_stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Файл засах - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
</head>
<body class="font-sans text-slate-800 antialiased bg-slate-50">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- HEADER -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-10 shadow-sm">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden text-slate-500 hover:text-slate-700 focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl font-bold text-slate-800">Файл засах</h1>
                </div>
                
                <div class="flex items-center gap-4">
                    <a href="index.php" class="text-slate-500 hover:text-indigo-600 font-medium text-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Буцах
                    </a>
                </div>
            </header>

            <!-- SCROLLABLE CONTENT -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                
                <div class="max-w-4xl mx-auto">
                    
                    <?php if($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $message; ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-slate-800">Файлын мэдээлэл</h3>
                            <span class="text-xs font-mono text-slate-400">ID: <?php echo $file['id']; ?></span>
                        </div>
                        
                        <form method="POST" action="" class="p-6 space-y-6">
                            
                            <!-- Title -->
                            <div>
                                <label for="title" class="block text-sm font-medium text-slate-700 mb-1">Файлын нэр (Title)</label>
                                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($file['title']); ?>" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" required>
                            </div>

                            <!-- Description -->
                            <div>
                                <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Тайлбар (Description)</label>
                                <textarea name="description" id="description" rows="4" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"><?php echo htmlspecialchars($file['description']); ?></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Price -->
                                <div>
                                    <label for="price" class="block text-sm font-medium text-slate-700 mb-1">Үнэ (Price)</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">₮</span>
                                        </div>
                                        <input type="number" name="price" id="price" value="<?php echo htmlspecialchars($file['price']); ?>" class="w-full rounded-lg border-slate-300 border pl-7 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                                    </div>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Төлөв (Status)</label>
                                    <select name="status" id="status" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white transition-all">
                                        <option value="pending" <?php echo $file['status'] == 'pending' ? 'selected' : ''; ?>>Хүлээгдэж буй (Pending)</option>
                                        <option value="approved" <?php echo $file['status'] == 'approved' ? 'selected' : ''; ?>>Зөвшөөрсөн (Approved)</option>
                                        <option value="rejected" <?php echo $file['status'] == 'rejected' ? 'selected' : ''; ?>>Татгалзсан (Rejected)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Category (Optional - Uncomment if needed) -->
                            <!--
                            <div>
                                <label for="category_id" class="block text-sm font-medium text-slate-700 mb-1">Ангилал</label>
                                <select name="category_id" id="category_id" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white">
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $file['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            -->

                            <!-- Read-only Info -->
                            <div class="bg-slate-50 p-4 rounded-lg border border-slate-100 text-sm text-slate-600 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span class="font-semibold">Файлын төрөл:</span> <?php echo htmlspecialchars($file['file_type']); ?>
                                </div>
                                <div>
                                    <span class="font-semibold">Хэмжээ:</span> <?php echo number_format($file['file_size'] / 1024 / 1024, 2); ?> MB
                                </div>
                                <div>
                                    <span class="font-semibold">Хуулсан огноо:</span> <?php echo $file['upload_date']; ?>
                                </div>
                                <div>
                                    <span class="font-semibold">Татаж авсан:</span> <?php echo $file['download_count']; ?>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                                <a href="index.php" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors font-medium">
                                    Буцах
                                </a>
                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium shadow-sm">
                                    <i class="fas fa-save mr-1"></i> Хадгалах
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