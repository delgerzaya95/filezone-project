<?php
// Session эхлүүлэх
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database холболтыг дуудах (admin хавтаснаас нэг буцаж үндсэн includes руу орно)
// Хэрэв зам буруу болбол өөрийн бүтцэд тааруулж засаарай.
require_once '../includes/db.php';

// Хэрэв аль хэдийн Админ эрхээр нэвтэрсэн бол Dashboard руу шилжүүлэх
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Та нэвтрэх мэдээллээ бүрэн оруулна уу.";
    } else {
        try {
            // Зөвхөн 'admin' эрхтэй хэрэглэгчийг хайна
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (email = :email OR username = :username) AND role = 'admin' LIMIT 1");
            $stmt->execute([
                ':email' => $email,
                ':username' => $email
            ]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Амжилттай нэвтэрлээ
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['email'] = $admin['email'];
                $_SESSION['role'] = 'admin'; // Session дээр role-г admin гэж хадгална
                $_SESSION['avatar'] = $admin['avatar_url'];

                // Сүүлд нэвтэрсэн цагийг шинэчлэх
                $updateStmt = $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = :id");
                $updateStmt->execute([':id' => $admin['id']]);

                // Dashboard руу шилжүүлэх
                header('Location: index.php');
                exit;
            } else {
                // Хэрэв хэрэглэгч олдсон ч admin биш бол, эсвэл нууц үг буруу бол
                $error = "Админ эрхгүй эсвэл мэдээлэл буруу байна.";
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
    <title>Админ Нэвтрэх - Filezone</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            900: '#1e1b4b', // Dark Indigo
                            800: '#312e81',
                            600: '#4f46e5',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-900 text-gray-200 font-sans min-h-screen flex items-center justify-center">

    <div class="w-full max-w-sm p-6 bg-slate-800 rounded-xl shadow-2xl border border-slate-700">
        
        <div class="text-center mb-8">
            <div class="w-12 h-12 bg-brand-600 rounded-lg flex items-center justify-center text-white font-bold text-2xl mx-auto mb-4 shadow-lg shadow-brand-600/50">
                A
            </div>
            <h1 class="text-xl font-bold text-white">Админ удирдлага</h1>
            <p class="text-sm text-slate-400 mt-1">Filezone системийн удирдлагын хэсэг</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-400 text-sm rounded-lg p-3 mb-6 text-center">
                <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-5">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5 uppercase tracking-wider">Нэвтрэх нэр / Имэйл</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" name="email" required 
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-900 border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600 transition-colors placeholder-slate-600"
                        placeholder="Admin username">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5 uppercase tracking-wider">Нууц үг</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" required 
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-900 border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600 transition-colors placeholder-slate-600"
                        placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="w-full bg-brand-600 hover:bg-brand-500 text-white font-medium py-2.5 rounded-lg transition-all transform hover:-translate-y-0.5 shadow-lg shadow-brand-600/30">
                Нэвтрэх
            </button>
        </form>

        <div class="mt-8 text-center border-t border-slate-700 pt-4">
            <a href="../index.php" class="text-xs text-slate-500 hover:text-white transition-colors flex items-center justify-center gap-2">
                <i class="fas fa-arrow-left"></i> Үндсэн сайт руу буцах
            </a>
        </div>
    </div>

</body>
</html>