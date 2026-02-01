<?php
// profile/settings.php
session_start();
include '../includes/db.php';

// Check DB Connection
if (!isset($conn)) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
}

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = '';
$error = '';

// --- 1. HANDLE PROFILE UPDATE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    // FIX: trim(null) Deprecated алдаанаас сэргийлж '?? ""' нэмэв
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    
    // Update Query
    $upd_sql = "UPDATE users SET full_name = ?, phone = ?, location = ? WHERE id = ?";
    $stmt = $conn->prepare($upd_sql);
    $stmt->bind_param("sssi", $full_name, $phone, $location, $user_id);

    if ($stmt->execute()) {
        $msg = "Мэдээлэл амжилттай шинэчлэгдлээ.";
    } else {
        $error = "Алдаа гарлаа: " . $conn->error;
    }
}

// --- 2. HANDLE PASSWORD CHANGE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'change_password') {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $error = "Бүх талбарыг бөглөнө үү.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "Шинэ нууц үг тохирохгүй байна.";
    } elseif (strlen($new_pass) < 6) {
        $error = "Нууц үг дор хаяж 6 оронтой байх ёстой.";
    } else {
        // Verify current password
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if ($res && password_verify($current_pass, $res['password'])) {
            // Update Password
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $upd_pass_sql = "UPDATE users SET password = ? WHERE id = ?";
            $upd_stmt = $conn->prepare($upd_pass_sql);
            $upd_stmt->bind_param("si", $hashed_pass, $user_id);
            
            if ($upd_stmt->execute()) {
                $msg = "Нууц үг амжилттай солигдлоо.";
            } else {
                $error = "Нууц үг солиход алдаа гарлаа.";
            }
        } else {
            $error = "Одоогийн нууц үг буруу байна.";
        }
    }
}

// --- 3. FETCH USER DATA ---
$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

$username = $user_data['username'] ?? 'User';
$email = $user_data['email'] ?? '';

// Avatar Logic
$db_avatar = $user_data['avatar_url'] ?? '';
$avatar = "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=random&color=fff";
if (!empty($db_avatar)) {
    if (strpos($db_avatar, 'http') === 0) {
        $avatar = $db_avatar;
    } elseif (file_exists('../' . $db_avatar)) {
        $avatar = '../' . $db_avatar;
    }
}

$pageTitle = "Тохиргоо";
include 'header.php';
?>

<!-- Styles -->
<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
        
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Хувийн тохиргоо</h1>
        </div>

        <?php if($msg): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative">
                <i class="fas fa-check-circle mr-2"></i> <span class="block sm:inline"><?php echo $msg; ?></span>
            </div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative">
                <i class="fas fa-exclamation-circle mr-2"></i> <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column: Profile Edit -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Personal Info -->
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                    <h3 class="font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">Хувийн мэдээлэл</h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Хэрэглэгчийн нэр</label>
                                <input type="text" value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" disabled class="w-full border border-gray-300 rounded-lg p-2.5 bg-gray-100 text-gray-500 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Имэйл хаяг</label>
                                <input type="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" disabled class="w-full border border-gray-300 rounded-lg p-2.5 bg-gray-100 text-gray-500 cursor-not-allowed">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Бүтэн нэр</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Утасны дугаар</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Байршил / Хаяг</label>
                            <input type="text" name="location" value="<?php echo htmlspecialchars($user_data['location'] ?? ''); ?>" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Улаанбаатар, Монгол">
                        </div>

                        <div class="flex justify-end mt-6">
                            <button type="submit" class="bg-blue-600 text-white font-medium py-2 px-6 rounded-lg hover:bg-blue-700 transition shadow-sm">
                                Хадгалах
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            <!-- Right Column: Password & Security -->
            <div class="lg:col-span-1 space-y-6">
                
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                    <h3 class="font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">Нууц үг солих</h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Одоогийн нууц үг</label>
                            <input type="password" name="current_password" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Шинэ нууц үг</label>
                            <input type="password" name="new_password" required minlength="6" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Шинэ нууц үг давтах</label>
                            <input type="password" name="confirm_password" required minlength="6" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>

                        <button type="submit" class="w-full bg-gray-800 text-white font-medium py-2 px-4 rounded-lg hover:bg-gray-900 transition shadow-sm">
                            Нууц үг шинэчлэх
                        </button>
                    </form>
                </div>

                <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-4">
                    <h4 class="text-sm font-bold text-yellow-800 mb-2"><i class="fas fa-shield-alt mr-1"></i> Аюулгүй байдал</h4>
                    <p class="text-xs text-yellow-700 leading-relaxed">
                        Та нууц үгээ бусадтай хуваалцахгүй байхыг анхаарна уу. Бид таны нууц үгийг хэзээ ч асуухгүй. Хэрэв сэжигтэй үйлдэл ажиглагдвал даруй нууц үгээ солино уу.
                    </p>
                </div>

            </div>

        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>