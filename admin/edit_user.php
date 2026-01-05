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
    header("Location: users.php");
    exit;
}

$user_id = intval($_GET['id']);
$message = '';
$error = '';

// Хадгалах үйлдэл (POST хүсэлт)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    // Бусад талбаруудыг нэмж болно (full_name, phone, etc.)
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';

    try {
        $sql = "UPDATE users SET username = ?, email = ?, role = ?, status = ?, full_name = ?, phone = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $email, $role, $status, $full_name, $phone, $user_id]);
        
        $message = "Хэрэглэгчийн мэдээлэл амжилттай шинэчлэгдлээ!";
    } catch (PDOException $e) {
        $error = "Алдаа гарлаа: " . $e->getMessage();
    }
}

// Хэрэглэгчийн мэдээллийг татах
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        die("Хэрэглэгч олдсонгүй.");
    }
} catch (PDOException $e) {
    die("Өгөгдлийн баазтай холбогдоход алдаа гарлаа.");
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Хэрэглэгч засах - Admin Panel</title>
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
                    <h1 class="text-xl font-bold text-slate-800">Хэрэглэгч засах</h1>
                </div>
                
                <div class="flex items-center gap-4">
                    <a href="users.php" class="text-slate-500 hover:text-indigo-600 font-medium text-sm">
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
                            <h3 class="text-lg font-bold text-slate-800">Хэрэглэгчийн мэдээлэл</h3>
                            <span class="text-xs font-mono text-slate-400">ID: <?php echo $user['id']; ?></span>
                        </div>
                        
                        <form method="POST" action="" class="p-6 space-y-6">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Username -->
                                <div>
                                    <label for="username" class="block text-sm font-medium text-slate-700 mb-1">Нэвтрэх нэр (Username)</label>
                                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" required>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Имэйл</label>
                                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" required>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Full Name -->
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-slate-700 mb-1">Бүтэн нэр</label>
                                    <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Утасны дугаар</label>
                                    <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Role -->
                                <div>
                                    <label for="role" class="block text-sm font-medium text-slate-700 mb-1">Үүрэг (Role)</label>
                                    <select name="role" id="role" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white transition-all">
                                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Админ (Admin)</option>
                                        <option value="moderator" <?php echo $user['role'] == 'moderator' ? 'selected' : ''; ?>>Редактор (Editor)</option>
                                        <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>Хэрэглэгч (User)</option>
                                    </select>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Төлөв (Status)</label>
                                    <select name="status" id="status" class="w-full rounded-lg border-slate-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white transition-all">
                                        <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Идэвхтэй (Active)</option>
                                        <option value="suspended" <?php echo $user['status'] == 'suspended' ? 'selected' : ''; ?>>Түр хаасан (Suspended)</option>
                                        <option value="banned" <?php echo $user['status'] == 'banned' ? 'selected' : ''; ?>>Бандуулсан (Banned)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Read-only Info -->
                            <div class="bg-slate-50 p-4 rounded-lg border border-slate-100 text-sm text-slate-600 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span class="font-semibold">Данс:</span> <?php echo number_format($user['balance'] ?? 0); ?>₮
                                </div>
                                <div>
                                    <span class="font-semibold">Бүртгүүлсэн огноо:</span> <?php echo $user['created_at']; ?>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                                <a href="users.php" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors font-medium">
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