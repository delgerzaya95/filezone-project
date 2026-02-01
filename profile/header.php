<?php
// profile/header.php

// Session эхлүүлэх (хэрэв дуудагдаагүй бол)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// db.php-г олох зам (profile фолдероос нэг гарч үндсэн фолдер, дараа нь includes/db.php)
require_once __DIR__ . '/../includes/db.php'; 

// Баазтай холбогдох
if (!isset($conn) && isset($db_host)) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        // Чимээгүй алдаа
    }
}

// Кирилл үсэгтэй ажиллах тохиргоо
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding("UTF-8");
}

// ASSETS PATH DEFINITION
// Web path (browser-д харагдах зам) - Absolute path from domain root is safest
$assets_web_path = '/assets'; 

// System path (server дээр файл байгаа эсэхийг шалгах зам)
// __DIR__ returns /path/to/profile. So ../assets means /path/to/assets
$assets_sys_path = realpath(__DIR__ . '/../assets');
// Fallback if realpath fails (e.g. symlinks or permissions)
if (!$assets_sys_path) {
    $assets_sys_path = __DIR__ . '/../assets';
}

// --- HELPER FUNCTIONS FOR HEADER ---

if (!function_exists('getHeaderRandomColor')) {
    function getHeaderRandomColor($str) {
        $colors = [
            ['bg' => 'bg-red-100', 'text' => 'text-red-600'],
            ['bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
            ['bg' => 'bg-green-100', 'text' => 'text-green-600'],
            ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600'],
            ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600'],
            ['bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
            ['bg' => 'bg-pink-100', 'text' => 'text-pink-600'],
        ];
        $index = strlen((string)$str) % count($colors);
        return $colors[$index];
    }
}

if (!function_exists('getHeaderInitials')) {
    function getHeaderInitials($name) {
        $name = (string)$name;
        if (function_exists('mb_substr')) {
            return mb_strtoupper(mb_substr($name, 0, 2));
        }
        return strtoupper(substr($name, 0, 2));
    }
}

if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $time = strtotime($datetime);
        $diff = time() - $time;
        if ($diff < 60) return 'Дөнгөж сая';
        if ($diff < 3600) return floor($diff / 60) . ' минутын өмнө';
        if ($diff < 86400) return floor($diff / 3600) . ' цагийн өмнө';
        if ($diff < 604800) return floor($diff / 86400) . ' өдрийн өмнө';
        return date('Y-m-d', $time);
    }
}

// Логин хийсэн хэрэглэгчийн мэдээлэл болон Мэдэгдэл
$loggedInUser = null;
$notifications = [];
$unreadCount = 0;

if (isset($_SESSION['user_id'])) {
    if (isset($conn)) {
        $h_sql = "SELECT username, email, avatar_url FROM users WHERE id = ?";
        $h_stmt = $conn->prepare($h_sql);
        $h_stmt->bind_param("i", $_SESSION['user_id']);
        $h_stmt->execute();
        $h_res = $h_stmt->get_result();
        $h_row = $h_res->fetch_assoc();

        if ($h_row) {
            $loggedInUser = [
                'id' => $_SESSION['user_id'],
                'name' => $h_row['username'],
                'email' => $h_row['email'],
                'avatar' => $h_row['avatar_url']
            ];
        }
    } else {
        $loggedInUser = [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['username'] ?? 'User',
            'email' => $_SESSION['email'] ?? '',
            'avatar' => $_SESSION['avatar'] ?? null,
        ];
    }

    $loggedInUser['initials'] = getHeaderInitials($loggedInUser['name']);
    $loggedInUser['color'] = getHeaderRandomColor($loggedInUser['name']);

    // Avatar Path Correction for Profile Folder
    if (!empty($loggedInUser['avatar'])) {
        if (strpos($loggedInUser['avatar'], 'http') !== 0) {
            $loggedInUser['avatar'] = '../' . $loggedInUser['avatar'];
        }
    }

    // Мэдэгдэл татах
    if (isset($conn)) {
        $notif_sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
        $notif_stmt = $conn->prepare($notif_sql);
        $notif_stmt->bind_param("i", $loggedInUser['id']);
        $notif_stmt->execute();
        $notif_res = $notif_stmt->get_result();
        while ($row = $notif_res->fetch_assoc()) {
            $notifications[] = $row;
        }

        // Уншаагүй мэдэгдлийн тоо
        $count_sql = "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $loggedInUser['id']);
        $count_stmt->execute();
        $unreadCount = $count_stmt->get_result()->fetch_assoc()['cnt'];
    }
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Filezone.mn - Дижитал файлын сан'; ?></title>

    <link rel="icon" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImciIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMTAwJSIgeTI9IjEwMCUiPjxzdG9wIG9mZnNldD0iMCUiIHN0b3AtY29sb3I9IiM0ZjQ2ZTUiLz48c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiM3ZTIyY2UiLz48L2xpbmVhckdyYWRpZW50PjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgcng9IjI1IiBmaWxsPSJ1cmwoI2cpIi8+PHRleHQgeD0iNTAiIHk9IjcwIiBmb250LWZhbWlseT0ic2Fucy1zZXJpZiIgZm9udC13ZWlnaHQ9ImJvbGQiIGZvbnQtc2l6ZT0iNjAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IndoaXRlIj5GPC90ZXh0Pjwvc3ZnPg==" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS - Using absolute path check logic -->
    <?php
        $cssFile = '/css/style.css';
        $cssWeb = $assets_web_path . $cssFile;
        $cssSys = $assets_sys_path . $cssFile;
        $cssVer = file_exists($cssSys) ? filemtime($cssSys) : time();
    ?>
    <link rel="stylesheet" href="<?php echo $cssWeb; ?>?v=<?php echo $cssVer; ?>">
    
    <!-- Tailwind Config - Using absolute path check logic -->
    <?php
        $jsConfig = '/js/tailwind-config.js';
        $jsConfigWeb = $assets_web_path . $jsConfig;
        $jsConfigSys = $assets_sys_path . $jsConfig;
        $jsConfigVer = file_exists($jsConfigSys) ? filemtime($jsConfigSys) : time();
    ?>
    <script src="<?php echo $jsConfigWeb; ?>?v=<?php echo $jsConfigVer; ?>"></script>

    <style>
        .notif-scroll::-webkit-scrollbar { width: 6px; }
        .notif-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .notif-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .notif-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .animate-fade-in-down { animation: fadeInDown 0.2s ease-out; }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="text-gray-700 antialiased font-sans bg-gray-50 flex flex-col min-h-screen">

    <!-- 1. Top Bar -->
    <div class="bg-slate-900 text-gray-400 text-[10px] sm:text-xs py-2 border-b border-slate-800">
        <div class="w-full max-w-7xl mx-auto px-4 flex justify-between items-center">
            <span class="hidden sm:block">Монголын хамгийн том дижитал файлын сан & фрийланс платформ</span>
            <span class="sm:hidden">Filezone.mn</span>
            <div class="flex gap-4">
                <a href="../help.php" class="hover:text-white transition-colors">Тусламж</a>
                <span class="text-slate-700">|</span>
                <a href="../contact.php" class="hover:text-white transition-colors">Холбоо барих</a>
            </div>
        </div>
    </div>
    
    <!-- 2. Navbar -->
    <nav class="bg-white/90 backdrop-blur-md border-b border-gray-200 sticky top-0 z-40 transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                
                <!-- Left Side: Logo & Menu -->
                <div class="flex items-center gap-8">
                    <a href="../index.php" class="flex items-center gap-2 group">
                        <div class="w-9 h-9 bg-gradient-to-br from-brand-600 to-purple-700 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-brand-500/30 group-hover:scale-105 transition-transform duration-200">
                            F
                        </div>
                        <span class="font-bold text-xl tracking-tight text-gray-900 group-hover:text-brand-600 transition-colors">Filezone</span>
                    </a>
                    
                    <div class="hidden md:flex gap-6">
                        <a href="../index.php" class="text-sm font-medium text-gray-600 hover:text-brand-600 transition-colors">Нүүр</a>
                        <a href="../services.php" class="text-sm font-medium text-gray-600 hover:text-brand-600 transition-colors">Үйлчилгээ</a>
                        <a href="../browse-files.php" class="text-sm font-medium text-gray-600 hover:text-brand-600 transition-colors">Файлууд</a>
                    </div>
                </div>

                <!-- Desktop Search -->
                <form action="../search.php" method="GET" class="hidden md:block w-full max-w-md relative mx-4 group">
                    <input type="text" name="q" placeholder="Хайх: Диплом, Гэрээ, Орчуулга..." 
                        value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>"
                        class="w-full pl-4 pr-12 py-2 bg-gray-50 border border-gray-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all shadow-sm">
                    <button type="submit" class="absolute inset-y-0 right-0 px-4 text-gray-400 hover:text-brand-600 transition-colors rounded-r-full focus:outline-none group-focus-within:text-brand-500 bg-transparent border-l border-gray-200 h-full">
                        <i class="fas fa-search"></i>
                    </button>
                </form>

                <!-- Right Side: Actions -->
                <div class="flex items-center gap-3 sm:gap-4">
                    
                    <a href="../add_service.php" class="hidden lg:flex items-center gap-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-4 py-2 rounded-full font-bold text-xs shadow-lg shadow-purple-500/30 hover:shadow-purple-500/50 transform hover:-translate-y-0.5 transition-all btn-shine whitespace-nowrap">
                        <i class="fas fa-briefcase"></i>
                        Үйлчилгээ
                    </a>

                    <a href="../upload.php" class="hidden md:flex items-center gap-2 bg-gradient-to-r from-orange-500 to-red-500 text-white px-4 py-2 rounded-full font-bold text-xs shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50 transform hover:-translate-y-0.5 transition-all btn-shine whitespace-nowrap">
                        <i class="fas fa-cloud-upload-alt"></i>
                        Файл оруулах
                    </a>

                    <?php if ($loggedInUser): ?>
                        <!-- LOGGED IN STATE -->
                        
                        <!-- Notifications Dropdown -->
                        <div class="relative group" id="notifMenu">
                            <button onclick="document.getElementById('notifDropdown').classList.toggle('hidden')" class="w-9 h-9 rounded-full bg-gray-50 text-gray-500 hover:text-brand-600 hover:bg-brand-50 flex items-center justify-center transition-colors relative focus:outline-none">
                                <i class="far fa-bell text-lg"></i>
                                <?php if ($unreadCount > 0): ?>
                                    <span id="notifBadge" class="absolute top-0 right-0 w-4 h-4 bg-red-500 rounded-full border-2 border-white flex items-center justify-center text-[9px] text-white font-bold">
                                        <?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?>
                                    </span>
                                <?php endif; ?>
                            </button>

                            <!-- Notification Dropdown Content -->
                            <div id="notifDropdown" class="hidden absolute right-0 top-full pt-2 w-80 z-50">
                                <div class="bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-down">
                                    <div class="px-4 py-3 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                                        <h3 class="font-bold text-sm text-gray-800">Мэдэгдэл</h3>
                                        <a href="../notifications.php" class="text-xs text-brand-600 hover:text-brand-700 font-medium">Бүгдийг үзэх</a>
                                    </div>
                                    
                                    <div class="max-h-80 overflow-y-auto notif-scroll">
                                        <?php if (count($notifications) > 0): ?>
                                            <?php foreach ($notifications as $notif): ?>
                                                <?php 
                                                    $isReadClass = $notif['is_read'] ? 'opacity-60 bg-white' : 'bg-brand-50/30 border-l-2 border-brand-500'; 
                                                    $icon = 'fas fa-info-circle text-blue-500';
                                                    if (isset($notif['type']) && $notif['type'] == 'success') $icon = 'fas fa-check-circle text-green-500';
                                                    if (isset($notif['type']) && $notif['type'] == 'warning') $icon = 'fas fa-exclamation-triangle text-yellow-500';
                                                    if (isset($notif['type']) && $notif['type'] == 'error') $icon = 'fas fa-times-circle text-red-500';
                                                    
                                                    // ЗАМЫН ЗАСВАР (LINK FIX): 
                                                    $rawLink = $notif['link'] ?? '#';
                                                    $fixedLink = (strpos($rawLink, 'http') === 0) ? $rawLink : '../' . $rawLink;
                                                ?>
                                                <a href="#" onclick="markAsRead(<?php echo $notif['id']; ?>, '<?php echo htmlspecialchars($fixedLink); ?>')" class="block px-4 py-3 hover:bg-gray-50 transition border-b border-gray-50 last:border-0 <?php echo $isReadClass; ?>">
                                                    <div class="flex gap-3">
                                                        <div class="mt-1 flex-shrink-0">
                                                            <i class="<?php echo $icon; ?>"></i>
                                                        </div>
                                                        <div>
                                                            <p class="text-sm text-gray-800 leading-snug <?php echo $notif['is_read'] ? '' : 'font-medium'; ?>">
                                                                <?php echo htmlspecialchars($notif['message']); ?>
                                                            </p>
                                                            <p class="text-xs text-gray-400 mt-1"><?php echo timeAgo($notif['created_at']); ?></p>
                                                        </div>
                                                    </div>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="px-4 py-8 text-center">
                                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-400">
                                                    <i class="far fa-bell-slash text-xl"></i>
                                                </div>
                                                <p class="text-sm text-gray-500">Мэдэгдэл алга байна.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Menu Dropdown -->
                        <div class="relative group" id="userMenu">
                            <button class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-full hover:bg-gray-100 transition-colors focus:outline-none">
                                <?php if (!empty($loggedInUser['avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars($loggedInUser['avatar']); ?>" 
                                         alt="Profile" 
                                         class="w-8 h-8 rounded-full object-cover border border-gray-200">
                                <?php else: ?>
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs border border-gray-200 <?php echo $loggedInUser['color']['bg'] . ' ' . $loggedInUser['color']['text']; ?>">
                                        <?php echo $loggedInUser['initials']; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <span class="hidden sm:block text-sm font-medium text-gray-700 max-w-[100px] truncate">
                                    <?php echo htmlspecialchars($loggedInUser['name']); ?>
                                </span>
                                <i class="fas fa-chevron-down text-xs text-gray-400 hidden sm:block"></i>
                            </button>

                            <!-- Dropdown Content -->
                            <div class="absolute right-0 top-full pt-2 w-56 hidden group-hover:block z-50">
                                <div class="bg-white rounded-xl shadow-xl border border-gray-100 py-2 animate-fade-in-down">
                                    <div class="px-4 py-2 border-b border-gray-50">
                                        <p class="text-xs text-gray-500">Нэвтэрсэн:</p>
                                        <p class="text-sm font-bold text-gray-900 truncate"><?php echo htmlspecialchars($loggedInUser['email']); ?></p>
                                    </div>
                                    
                                    <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                        <i class="far fa-user w-4"></i> Хувийн хуудас
                                    </a>
                                    <a href="wallet.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                        <i class="fas fa-wallet w-4"></i> Хэтэвч
                                    </a>
                                    
                                    <!-- Mobile/Tablet only links -->
                                    <a href="../add_service.php" class="lg:hidden flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                        <i class="fas fa-briefcase w-4"></i> Үйлчилгээ нэмэх
                                    </a>
                                    <a href="../upload.php" class="md:hidden flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                        <i class="fas fa-cloud-upload-alt w-4"></i> Файл оруулах
                                    </a>

                                    <a href="settings.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                        <i class="fas fa-cog w-4"></i> Тохиргоо
                                    </a>
                                    
                                    <div class="border-t border-gray-50 mt-1 pt-1">
                                        <a href="../logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                            <i class="fas fa-sign-out-alt w-4"></i> Гарах
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- GUEST STATE -->
                        <a href="../login.php" class="text-sm font-medium text-gray-600 hover:text-brand-600 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors hidden sm:block">
                            Нэвтрэх
                        </a>
                        <a href="../register.php" class="bg-gray-900 hover:bg-gray-800 text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow-lg shadow-gray-500/30 transition-all hover:-translate-y-0.5 flex items-center gap-2">
                            <i class="fas fa-user-plus text-xs"></i>
                            <span class="hidden sm:inline">Бүртгүүлэх</span>
                            <span class="sm:hidden">Эхлэх</span>
                        </a>
                    <?php endif; ?>

                    <button class="md:hidden w-9 h-9 flex items-center justify-center text-gray-600 hover:bg-gray-100 rounded-lg ml-1" onclick="document.getElementById('mobileSearch').classList.toggle('hidden')">
                        <i class="fas fa-search text-lg"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <div id="mobileSearch" class="hidden md:hidden border-t border-gray-100 p-4 bg-white/50 backdrop-blur-sm">
             <form action="../search.php" method="GET" class="relative w-full">
                <input type="text" name="q" class="w-full bg-gray-100 text-gray-900 text-sm rounded-lg pl-4 pr-10 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500/20" placeholder="Хайх...">
                <button type="submit" class="absolute inset-y-0 right-0 px-3 text-gray-400">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </nav>

    <!-- Notification Logic Script -->
    <script>
        document.addEventListener('click', function(event) {
            const notifMenu = document.getElementById('notifMenu');
            const notifDropdown = document.getElementById('notifDropdown');
            if (notifMenu && notifDropdown && !notifMenu.contains(event.target)) {
                notifDropdown.classList.add('hidden');
            }
        });

        function markAsRead(id, link) {
            fetch('../api/mark_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id
            }).then(() => {
                window.location.href = link;
            }).catch(err => {
                console.error(err);
                window.location.href = link;
            });
        }
    </script>
    
    <!-- MAIN JS LINKED HERE FOR ALL PROFILE PAGES -->
    <?php
        // Using System Path Logic for Main.js as well
        $mainJsFile = '/js/main.js';
        $mainJsWeb = $assets_web_path . $mainJsFile;
        $mainJsSys = $assets_sys_path . $mainJsFile;
        $mainJsVer = file_exists($mainJsSys) ? filemtime($mainJsSys) : time();
    ?>
    <script src="<?php echo $mainJsWeb; ?>?v=<?php echo $mainJsVer; ?>"></script>