<?php
// 1. Database болон Google тохиргоог дуудах
// Хэрэв танд google_config.php байхгүй бол доорх мөрийг түр коммент болгоод туршиж болно.
if (file_exists('includes/google_config.php')) {
    require_once 'includes/google_config.php';
}
require_once 'includes/db.php';

// Хэрэв хэрэглэгч аль хэдийн нэвтэрсэн бол Профайл руу шилжүүлэх
if (isLoggedIn()) {
    header('Location: profile.php');
    exit;
}

$error = '';

// -------------------------------------------------------------------------
// 2. GOOGLE LOGIN HANDLER (Google-ээс буцаж ирэхэд ажиллах хэсэг)
// -------------------------------------------------------------------------
if (isset($_GET['code']) && isset($google_client)) {
    try {
        $token = $google_client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (!isset($token['error'])) {
            $google_client->setAccessToken($token['access_token']);
            $_SESSION['access_token'] = $token['access_token'];

            // Google-ээс хэрэглэгчийн мэдээллийг авах
            $google_service = new Google_Service_Oauth2($google_client);
            $google_account_info = $google_service->userinfo->get();
            
            $google_id = $google_account_info->id;
            $email = $google_account_info->email;
            $name = $google_account_info->name;
            $picture = $google_account_info->picture;

            // Database-ээс шалгах
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                // Хэрэглэгч бүртгэлтэй бол: Google ID-г нь шинэчилж нэвтрүүлнэ
                if (empty($user['google_id'])) {
                    $update = $pdo->prepare("UPDATE users SET google_id = :gid, avatar_url = :avatar WHERE id = :id");
                    $update->execute([
                        ':gid' => $google_id,
                        ':avatar' => $picture,
                        ':id' => $user['id']
                    ]);
                }
                
                // Session үүсгэх
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['avatar'] = $user['avatar_url'];
                
                header('Location: profile.php');
                exit;
            } else {
                // Хэрэглэгч бүртгэлгүй бол: Шинээр бүртгэнэ
                // Username-ийг имэйлийн эхний хэсгээр үүсгэх
                $username = explode('@', $email)[0];
                
                // Давхардсан username байгаа эсэхийг шалгах
                $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = :username");
                $checkUser->execute([':username' => $username]);
                if ($checkUser->rowCount() > 0) {
                    $username .= rand(100, 999); // Давхардвал тоо залгана
                }

                $insert = $pdo->prepare("INSERT INTO users (username, email, password, google_id, full_name, avatar_url, role, is_verified) VALUES (:username, :email, :password, :gid, :fullname, :avatar, 'user', 1)");
                $insert->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT), // Санамсаргүй нууц үг
                    ':gid' => $google_id,
                    ':fullname' => $name,
                    ':avatar' => $picture,
                ]);

                $newUserId = $pdo->lastInsertId();

                // Session үүсгэх
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'user';
                $_SESSION['avatar'] = $picture;

                header('Location: profile.php');
                exit;
            }
        }
    } catch (Exception $e) {
        $error = "Google-ээр нэвтрэхэд алдаа гарлаа. Дахин оролдоно уу.";
    }
}

// -------------------------------------------------------------------------
// 3. NORMAL LOGIN HANDLER (Имэйл, нууц үгээр нэвтрэх)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['email']); // Имэйл эсвэл Username
    $password = $_POST['password'];

    if (empty($login_id) || empty($password)) {
        $error = "Та нэвтрэх мэдээллээ бүрэн оруулна уу.";
    } else {
        try {
            // Имэйл эсвэл Username-ээр хайх
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email OR username = :username LIMIT 1");
            $stmt->execute([':email' => $login_id, ':username' => $login_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Амжилттай нэвтэрлээ
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['avatar'] = $user['avatar_url'];
                
                // Сүүлд нэвтэрсэн цагийг шинэчлэх
                $updateStmt = $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = :id");
                $updateStmt->execute([':id' => $user['id']]);

                header('Location: profile.php');
                exit;
            } else {
                $error = "Имэйл эсвэл нууц үг буруу байна.";
            }
        } catch (PDOException $e) {
            $error = "Системийн алдаа гарлаа.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Нэвтрэх - Filezone.mn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/tailwind-config.js"></script>
</head>
<body class="text-gray-700 antialiased font-sans bg-pattern min-h-screen flex flex-col">

    <!-- Top Bar -->
    <div class="bg-slate-800 text-gray-400 text-[10px] sm:text-xs py-1.5 border-b border-slate-700">
        <div class="w-full max-w-7xl mx-auto px-4 flex justify-between items-center">
            <span>Монголын дижитал файлын сан</span>
            <div class="flex gap-3">
                <a href="guides.php" class="hover:text-white transition-colors">Тусламж</a>
                <span class="hidden sm:inline">|</span>
                <a href="contact.php" class="hover:text-white transition-colors">Холбоо барих</a>
            </div>
        </div>
    </div>
    
    <!-- Navbar -->
    <nav class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-brand-500/30">F</div>
                    <span class="font-bold text-xl tracking-tight text-gray-900">Filezone</span>
                </a>

                <!-- Right Side: Back to Home -->
                <a href="index.php" class="text-sm font-medium text-gray-500 hover:text-brand-600 flex items-center gap-2 transition-colors">
                    <i class="fas fa-arrow-left"></i> Буцах
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-8">
        
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden relative">
            
            <!-- Top Decoration -->
            <div class="h-2 bg-gradient-to-r from-brand-500 to-indigo-600"></div>

            <div class="p-8">
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Нэвтрэх</h1>
                    <p class="text-sm text-gray-500">Тавтай морилно уу! Та бүртгэлтэй хаягаараа нэвтэрнэ үү.</p>
                </div>

                <!-- Error Message -->
                <?php if (!empty($error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg p-3 mb-4 flex items-center gap-2 animate-pulse">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="space-y-4">
                    
                    <!-- Email/Username Input -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Имэйл эсвэл Нэвтрэх нэр</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-envelope text-sm"></i>
                            </span>
                            <input type="text" id="email" name="email" required placeholder="name@example.com эсвэл Username" 
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <label for="password" class="block text-sm font-medium text-gray-700">Нууц үг</label>
                            <a href="forgot-password.php" class="text-xs font-medium text-brand-600 hover:text-brand-700 hover:underline">Нууц үгээ мартсан?</a>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-lock text-sm"></i>
                            </span>
                            <input type="password" id="password" name="password" required placeholder="••••••••" 
                                class="w-full pl-10 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        </div>
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-brand-300 text-brand-600 cursor-pointer">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-500 cursor-pointer">Намайг сана</label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-2.5 rounded-lg shadow-lg shadow-brand-500/30 transition-all transform hover:-translate-y-0.5 mt-2">
                        Нэвтрэх
                    </button>
                </form>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-xs">
                        <span class="px-2 bg-white text-gray-400">эсвэл</span>
                    </div>
                </div>

                <!-- Google Login Button -->
                <div class="mb-6">
                    <?php if(isset($google_client)): ?>
                        <a href="<?php echo $google_client->createAuthUrl(); ?>" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium text-gray-700">
                            <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-4 h-4" alt="Google">
                            Google-ээр нэвтрэх
                        </a>
                    <?php else: ?>
                        <!-- Хэрэв Google Config дуудагдаагүй бол товчийг идэвхгүй харуулах -->
                        <button class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 border border-gray-200 rounded-lg text-sm font-medium text-gray-400 cursor-not-allowed">
                            <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-4 h-4 opacity-50" alt="Google">
                            Google (Тохиргоо хийгдээгүй)
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Register Link -->
                <div class="text-center text-sm text-gray-600">
                    Бүртгэлгүй юу? 
                    <a href="register.php" class="font-bold text-brand-600 hover:text-brand-700 hover:underline">Бүртгүүлэх</a>
                </div>

            </div>
        </div>

    </main>

    <!-- Simple Footer -->
    <footer class="bg-white border-t border-gray-200 py-6">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-xs text-gray-400">&copy; 2025 Filezone.mn. Бүх эрх хуулиар хамгаалагдсан.</p>
        </div>
    </footer>
    <script src="assets/js/main.js"></script>
</body>
</html>