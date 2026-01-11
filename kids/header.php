<?php
// Filezone Kids - Custom Header
// Path: filezone.mn/kids/header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Үндсэн сайтын баазтай холбогдох (Хэрэглэгч нэвтрэх, Session шалгахад хэрэгтэй)
require_once __DIR__ . '/../includes/db.php'; 

// 2. Kids хэсгийн тусдаа баазтай холбогдох (Материал, Ангилал татахад хэрэгтэй)
require_once __DIR__ . '/db_kids.php';

// --- HELPER FUNCTIONS FOR HEADER ---

// Random color generator
if (!function_exists('getKidsHeaderRandomColor')) {
    function getKidsHeaderRandomColor($str) {
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

// Initials generator
if (!function_exists('getKidsHeaderInitials')) {
    function getKidsHeaderInitials($name) {
        $name = (string)$name;
        if (function_exists('mb_substr')) {
            return mb_strtoupper(mb_substr($name, 0, 2));
        }
        return strtoupper(substr($name, 0, 2));
    }
}

$kidsUser = null;
if (isset($_SESSION['user_id'])) {
    // Try to fetch latest user data
    try {
        $stmt_kids_user = $pdo->prepare("SELECT username, avatar_url as avatar, email FROM users WHERE id = ?");
        $stmt_kids_user->execute([$_SESSION['user_id']]);
        $db_user = $stmt_kids_user->fetch(PDO::FETCH_ASSOC);
        
        if ($db_user) {
            $avatarUrl = null;
            if (!empty($db_user['avatar'])) {
                // If it's a URL (e.g. Google login) or internal path
                if (filter_var($db_user['avatar'], FILTER_VALIDATE_URL)) {
                    $avatarUrl = $db_user['avatar'];
                } else {
                    // Internal path: Ensure it's relative to kids folder (../)
                    // Remove leading slash if exists to avoid double slash issues
                    $cleanPath = ltrim($db_user['avatar'], '/');
                    $avatarUrl = '../' . $cleanPath;
                }
            }

            $kidsUser = [
                'id' => $_SESSION['user_id'],
                'name' => $db_user['username'],
                'email' => $db_user['email'],
                'avatar' => $avatarUrl
            ];
        }
    } catch (PDOException $e) {
        // Fallback to session
        $kidsUser = [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['username'] ?? 'User',
            'avatar' => $_SESSION['avatar'] ?? null
        ];
    }

    if ($kidsUser) {
        $kidsUser['initials'] = getKidsHeaderInitials($kidsUser['name']);
        $kidsUser['color'] = getKidsHeaderRandomColor($kidsUser['name']);
    }
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Filezone Kids'; ?></title>
    
    <!-- FAVICON -->
    <link rel="icon" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImciIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMTAwJSIgeTI9IjEwMCUiPjxzdG9wIG9mZnNldD0iMCUiIHN0b3AtY29sb3I9IiM0ZjQ2ZTUiLz48c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiM3ZTIyY2UiLz48L2xpbmVhckdyYWRpZW50PjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgcng9IjI1IiBmaWxsPSJ1cmwoI2cpIi8+PHRleHQgeD0iNTAiIHk9IjcwIiBmb250LWZhbWlseT0ic2Fucy1zZXJpZiIgZm9udC13ZWlnaHQ9ImJvbGQiIGZvbnQtc2l6ZT0iNjAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IndoaXRlIj5GPC90ZXh0Pjwvc3ZnPg==" type="image/svg+xml">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Quicksand:wght@500;600;700&family=Indie+Flower&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons & Tailwind -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../assets/js/tailwind-config.js"></script>

    <style>
        /* Base fonts for Kids section */
        .font-kids { font-family: 'Quicksand', sans-serif; }
        .font-title { font-family: 'Nunito', sans-serif; }
        
        /* Animation for dropdown */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-down {
            animation: fadeInDown 0.2s ease-out forwards;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Dashed Border Utility */
        .dashed-border {
            background-image: url("data:image/svg+xml,%3csvg width='100%25' height='100%25' xmlns='http://www.w3.org/2000/svg'%3e%3crect width='100%25' height='100%25' fill='none' rx='16' ry='16' stroke='%2333CBCCFF' stroke-width='3' stroke-dasharray='12%2c 12' stroke-dashoffset='0' stroke-linecap='square'/%3e%3c/svg%3e");
            border-radius: 16px;
        }
    </style>
</head>
<body class="font-kids bg-gray-50 text-gray-800 flex flex-col min-h-screen">

<!-- KIDS NAVIGATION -->
<nav class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-50 border-b-4 border-[#33cbcc]">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-20">
            
            <!-- Left Side: Logo & Back Link -->
            <div class="flex items-center gap-6">
                <!-- Back to Main Site -->
                <a href="../index.php" class="text-gray-400 hover:text-gray-600 transition flex items-center gap-1 text-sm font-bold" title="Үндсэн сайт руу буцах">
                    <i class="fas fa-arrow-left"></i> <span class="hidden md:inline">Filezone</span>
                </a>
                
                <!-- Kids Logo (Main Site Style + Kids Badge) -->
                <a href="index.php" class="flex items-center gap-2 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-[#33cbcc] to-purple-600 rounded-xl flex items-center justify-center text-white font-extrabold text-2xl shadow-lg shadow-cyan-500/30 group-hover:scale-105 transition-transform duration-200 font-title">
                        F
                    </div>
                    <div class="flex flex-col justify-center">
                        <div class="flex items-center gap-1">
                            <span class="font-bold text-xl tracking-tight text-gray-900 group-hover:text-[#33cbcc] transition-colors font-title leading-none">Filezone</span>
                            <span class="bg-yellow-400 text-white text-[10px] px-1.5 py-0.5 rounded-md font-bold uppercase tracking-wide transform -rotate-6 shadow-sm leading-none">Kids</span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Center: Search Bar (Main Site Style) -->
            <div class="hidden md:block flex-grow max-w-lg mx-8">
                <form action="browse.php" method="GET" class="relative group">
                    <input type="text" name="q" placeholder="Дасгал, тоглоом хайх..." 
                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>"
                           class="w-full pl-5 pr-12 py-2.5 bg-gray-50 border border-gray-200 rounded-full text-sm font-bold text-gray-600 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-[#33cbcc] focus:ring-4 focus:ring-[#33cbcc]/20 transition-all shadow-sm">
                    <button type="submit" class="absolute inset-y-0 right-0 px-4 text-gray-400 hover:text-[#33cbcc] transition-colors rounded-r-full bg-transparent">
                        <i class="fas fa-search text-lg"></i>
                    </button>
                </form>
            </div>

            <!-- Right Side: User Menu -->
            <div class="flex items-center gap-3">
                
                <!-- Mobile Search Toggle -->
                <button onclick="document.getElementById('mobileSearchKids').classList.toggle('hidden')" class="md:hidden text-gray-500 hover:text-[#33cbcc] p-2 rounded-lg hover:bg-gray-100 transition">
                    <i class="fas fa-search fa-lg"></i>
                </button>

                <?php if($kidsUser): ?>
                    <!-- User Dropdown (Kids Context) -->
                    <div class="relative group">
                        <button class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-full hover:bg-gray-100 transition-colors focus:outline-none border border-transparent hover:border-gray-200">
                            
                            <?php if (!empty($kidsUser['avatar'])): ?>
                                <!-- Has Avatar Image -->
                                <img src="<?php echo htmlspecialchars($kidsUser['avatar']); ?>" 
                                     class="w-9 h-9 rounded-full object-cover border-2 border-white shadow-sm" 
                                     alt="User">
                            <?php else: ?>
                                <!-- No Avatar - Show Initials with Random Color (Same as Main Site) -->
                                <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm border-2 border-white shadow-sm <?php echo $kidsUser['color']['bg'] . ' ' . $kidsUser['color']['text']; ?>">
                                    <?php echo $kidsUser['initials']; ?>
                                </div>
                            <?php endif; ?>
                            
                            <span class="hidden sm:block text-sm font-bold text-gray-700 max-w-[100px] truncate font-title">
                                <?php echo htmlspecialchars($kidsUser['name']); ?>
                            </span>
                            <i class="fas fa-chevron-down text-xs text-gray-400 hidden sm:block"></i>
                        </button>

                        <!-- Dropdown Content -->
                        <div class="absolute right-0 top-full pt-2 w-56 hidden group-hover:block z-50">
                            <div class="bg-white rounded-xl shadow-xl border border-gray-100 py-2 animate-fade-in-down overflow-hidden">
                                <div class="px-4 py-3 border-b border-gray-50 bg-[#e0f7fa]/30">
                                    <p class="text-xs text-gray-500 uppercase font-bold">Kids Account</p>
                                    <p class="text-sm font-bold text-gray-900 truncate"><?php echo htmlspecialchars($kidsUser['name']); ?></p>
                                </div>
                                
                                <!-- Link to KIDS profile -->
                                <a href="profile.php" class="flex items-center gap-3 px-4 py-2.5 text-sm font-bold text-gray-700 hover:bg-[#e0f7fa] hover:text-[#00acc1] transition-colors">
                                    <i class="fas fa-child w-5 text-center text-[#33cbcc]"></i> Миний булан
                                </a>
                                <a href="profile.php?tab=downloads" class="flex items-center gap-3 px-4 py-2.5 text-sm font-bold text-gray-700 hover:bg-[#e0f7fa] hover:text-[#00acc1] transition-colors">
                                    <i class="fas fa-download w-5 text-center text-purple-500"></i> Татсан материалууд
                                </a>
                                <a href="profile.php?tab=saved" class="flex items-center gap-3 px-4 py-2.5 text-sm font-bold text-gray-700 hover:bg-[#e0f7fa] hover:text-[#00acc1] transition-colors">
                                    <i class="fas fa-heart w-5 text-center text-pink-500"></i> Хадгалсан
                                </a>
                                
                                <div class="border-t border-gray-100 mt-1 pt-1">
                                    <!-- Log out directs to main logout logic -->
                                    <a href="../logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm font-bold text-red-500 hover:bg-red-50 transition-colors">
                                        <i class="fas fa-sign-out-alt w-5 text-center"></i> Гарах
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Guest State -->
                    <div class="flex items-center gap-2">
                        <a href="../login.php?redirect=kids" class="text-sm font-bold text-gray-600 hover:text-[#33cbcc] px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors hidden sm:block font-title">
                            Нэвтрэх
                        </a>
                        <a href="../register.php?redirect=kids" class="bg-[#33cbcc] hover:bg-[#2bb5b6] text-white text-sm font-bold px-5 py-2.5 rounded-full shadow-md shadow-cyan-200 transition-all hover:-translate-y-0.5 flex items-center gap-2 font-title">
                            <i class="fas fa-rocket text-xs"></i>
                            <span>Бүртгүүлэх</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mobile Search (Hidden by default) -->
    <div id="mobileSearchKids" class="hidden md:hidden border-t border-gray-100 p-4 bg-white/95 backdrop-blur-sm shadow-md absolute w-full z-40">
         <form action="browse.php" method="GET" class="relative w-full">
            <input type="text" name="q" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm font-bold rounded-xl pl-4 pr-10 py-3 focus:outline-none focus:bg-white focus:border-[#33cbcc] focus:ring-2 focus:ring-[#33cbcc]/50" placeholder="Дасгал хайх...">
            <button type="submit" class="absolute inset-y-0 right-0 px-4 text-gray-400 hover:text-[#33cbcc]">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
</nav>