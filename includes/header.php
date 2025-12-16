<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filezone.mn - Файлын сан & Үйлчилгээ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/tailwind-config.js"></script>
</head>
<body class="text-gray-700 antialiased font-sans">

    <div class="min-h-screen flex flex-col">

            <!-- 1. Top Bar -->
        <div class="bg-slate-800 text-gray-400 text-[10px] sm:text-xs py-1.5 border-b border-slate-700">
            <div class="w-full max-w-[1400px] mx-auto px-4 flex justify-between items-center">
                <span>Монголын дижитал файлын сан</span>
                <div class="flex gap-3">
                    <a href="guides.php" class="hover:text-white">Тусламж</a>
                    <span class="hidden sm:inline">|</span>
                    <a href="contact.php" class="hover:text-white">Холбоо барих</a>
                </div>
            </div>
        </div>
        
        <!-- Navbar -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Logo & Search -->
                    <div class="flex items-center flex-1 gap-4 lg:gap-8">
                        <a href="index.php" class="flex-shrink-0 flex items-center gap-2">
                            <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-brand-500/30">F</div>
                            <span class="font-bold text-xl tracking-tight text-gray-900 hidden sm:block">Filezone</span>
                        </a>

                        <!-- Desktop Search -->
                        <div class="hidden md:block w-full max-w-md relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" placeholder="Хайх: Диплом, Гэрээ, Орчуулга..." 
                                class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all shadow-sm">
                        </div>
                    </div>

                    <!-- Right Side Actions -->
                    <div class="flex items-center gap-4">

                        <!-- NEW: Prominent Upload Button (Desktop) -->
                        <a href="upload.php" class="hidden md:flex items-center gap-2 bg-gradient-to-r from-orange-500 to-red-500 text-white px-5 py-2 rounded-full font-bold text-sm shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50 transform hover:-translate-y-0.5 transition-all btn-shine">
                            <i class="fas fa-cloud-upload-alt"></i>
                            Файл оруулах
                        </a>

                        <div class="h-8 w-px bg-gray-200 hidden md:block mx-1"></div>

                        <button class="md:hidden p-2 text-gray-500 hover:bg-gray-100 rounded-lg" onclick="toggleMenu()">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        
                        <!-- Guest View -->
                        <div class="hidden md:flex items-center gap-3">
                            <a href="login.php" class="text-gray-500 hover:text-brand-600 font-semibold text-sm px-3 py-2 transition-colors">Нэвтрэх</a>
                            <a href="register.php" class="bg-slate-700 hover:bg-slate-800 text-white text-sm font-bold px-5 py-2.5 rounded-lg transition-all shadow-sm border border-slate-700">
                                Бүртгүүлэх
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Search -->
            <div class="md:hidden px-4 pb-3 border-t border-gray-100 pt-3">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" placeholder="Хайх..." class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm">
                </div>
            </div>
        </nav>