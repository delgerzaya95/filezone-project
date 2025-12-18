<?php
// Session эхлүүлэх ба DB холболт (хэрэв дуудагдаагүй бол)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// db.php-г олох зам
require_once __DIR__ . '/db.php'; 
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Filezone.mn - Дижитал файлын сан'; ?></title>

    <!-- FAVICON: Header дээрх Logo-той ижил SVG Icon -->
    <link rel="icon" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImciIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMTAwJSIgeTI9IjEwMCUiPjxzdG9wIG9mZnNldD0iMCUiIHN0b3AtY29sb3I9IiM0ZjQ2ZTUiLz48c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiM3ZTIyY2UiLz48L2xpbmVhckdyYWRpZW50PjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgcng9IjI1IiBmaWxsPSJ1cmwoI2cpIi8+PHRleHQgeD0iNTAiIHk9IjcwIiBmb250LWZhbWlseT0ic2Fucy1zZXJpZiIgZm9udC13ZWlnaHQ9ImJvbGQiIGZvbnQtc2l6ZT0iNjAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IndoaXRlIj5GPC90ZXh0Pjwvc3ZnPg==" type="image/svg+xml">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Tailwind Config -->
    <script src="assets/js/tailwind-config.js"></script>
</head>
<body class="text-gray-700 antialiased font-sans bg-gray-50 flex flex-col min-h-screen">

    <!-- 1. Top Bar -->
    <div class="bg-slate-900 text-gray-400 text-[10px] sm:text-xs py-2 border-b border-slate-800">
        <div class="w-full max-w-7xl mx-auto px-4 flex justify-between items-center">
            <span class="hidden sm:block">Монголын хамгийн том дижитал файлын сан & фрийланс платформ</span>
            <span class="sm:hidden">Filezone.mn</span>
            <div class="flex gap-4">
                <a href="help.php" class="hover:text-white transition-colors">Тусламж</a>
                <span class="text-slate-700">|</span>
                <a href="contact.php" class="hover:text-white transition-colors">Холбоо барих</a>
            </div>
        </div>
    </div>
    
    <!-- 2. Navbar -->
    <nav class="bg-white/90 backdrop-blur-md border-b border-gray-200 sticky top-0 z-40 transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                
                <!-- Left Side: Logo & Menu -->
                <div class="flex items-center gap-8">
                    <!-- Logo -->
                    <a href="index.php" class="flex items-center gap-2 group">
                        <div class="w-9 h-9 bg-gradient-to-br from-brand-600 to-purple-700 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-brand-500/30 group-hover:scale-105 transition-transform duration-200">
                            F
                        </div>
                        <span class="font-bold text-xl tracking-tight text-gray-900 group-hover:text-brand-600 transition-colors">Filezone</span>
                    </a>
                </div>
                <!-- Desktop Search -->
                        <div class="hidden md:block w-full max-w-md relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" placeholder="Хайх: Диплом, Гэрээ, Орчуулга..." 
                                class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all shadow-sm">
                        </div>

                <!-- Right Side: Actions -->
                <div class="flex items-center gap-3 sm:gap-4">
                    
                    <!-- Upload Button (Always visible, Yellow Style) -->
                    <a href="upload.php" class="hidden md:flex items-center gap-2 bg-gradient-to-r from-orange-500 to-red-500 text-white px-5 py-2 rounded-full font-bold text-sm shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50 transform hover:-translate-y-0.5 transition-all btn-shine">
                            <i class="fas fa-cloud-upload-alt"></i>
                            Файл оруулах
                        </a>
                    <?php if (isLoggedIn()): ?>
                        <!-- LOGGED IN STATE -->
                        
                        <!-- Notifications -->
                        <button class="w-9 h-9 rounded-full bg-gray-50 text-gray-500 hover:text-brand-600 hover:bg-brand-50 flex items-center justify-center transition-colors relative">
                            <i class="far fa-bell text-lg"></i>
                            <span class="absolute top-2 right-2.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                        </button>

                        <!-- User Menu Dropdown -->
                        <div class="relative group" id="userMenu">
                            <button class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-full hover:bg-gray-100 transition-colors focus:outline-none">
                                <img src="<?php echo !empty($_SESSION['avatar']) ? htmlspecialchars($_SESSION['avatar']) : 'assets/images/default-avatar.png'; ?>" 
                                     alt="Profile" 
                                     class="w-8 h-8 rounded-full object-cover border border-gray-200">
                                <span class="hidden sm:block text-sm font-medium text-gray-700 max-w-[100px] truncate">
                                    <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                                </span>
                                <i class="fas fa-chevron-down text-xs text-gray-400 hidden sm:block"></i>
                            </button>

                            <!-- Dropdown Content (Fixed: Added pt-2 for bridge) -->
                            <div class="absolute right-0 top-full pt-2 w-56 hidden group-hover:block z-50">
                                <!-- Menu Box -->
                                <div class="bg-white rounded-xl shadow-xl border border-gray-100 py-2 animate-fade-in-down">
                                    <div class="px-4 py-2 border-b border-gray-50">
                                        <p class="text-xs text-gray-500">Нэвтэрсэн:</p>
                                        <p class="text-sm font-bold text-gray-900 truncate"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></p>
                                    </div>
                                    
                                    <a href="profile.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                        <i class="far fa-user w-4"></i> Хувийн хуудас
                                    </a>
                                    <a href="profile.php?tab=wallet" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                        <i class="fas fa-wallet w-4"></i> Хэтэвч
                                    </a>
                                    <a href="add_service.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                        <i class="fas fa-briefcase w-4"></i> Үйлчилгээ нэмэх
                                    </a>
                                    <a href="profile.php?tab=settings" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                        <i class="fas fa-cog w-4"></i> Тохиргоо
                                    </a>
                                    
                                    <div class="border-t border-gray-50 mt-1 pt-1">
                                        <a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                            <i class="fas fa-sign-out-alt w-4"></i> Гарах
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- GUEST STATE -->
                        <a href="login.php" class="text-sm font-medium text-gray-600 hover:text-brand-600 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors hidden sm:block">
                            Нэвтрэх
                        </a>
                        <a href="register.php" class="bg-gray-900 hover:bg-gray-800 text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow-lg shadow-gray-500/30 transition-all hover:-translate-y-0.5 flex items-center gap-2">
                            <i class="fas fa-user-plus text-xs"></i>
                            <span class="hidden sm:inline">Бүртгүүлэх</span>
                            <span class="sm:hidden">Эхлэх</span>
                        </a>
                    <?php endif; ?>

                    <!-- Mobile Menu Button -->
                    <button class="md:hidden w-9 h-9 flex items-center justify-center text-gray-600 hover:bg-gray-100 rounded-lg ml-1">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Search (Visible only on small screens) -->
        <div class="lg:hidden border-t border-gray-100 p-4 bg-white/50 backdrop-blur-sm hidden">
             <div class="relative w-full">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" class="w-full bg-gray-100 text-gray-900 text-sm rounded-lg pl-10 pr-4 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500/20" placeholder="Хайх...">
            </div>
        </div>
    </nav>