<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бүртгүүлэх - Filezone.mn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/tailwind-config.js"></script>
</head>
<body class="text-gray-700 antialiased font-sans bg-pattern min-h-screen flex flex-col">

    <!-- 1. Top Bar -->
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
    
    <!-- 2. Navbar -->
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

    <!-- 3. Main Register Content -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-8">
        
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden relative">
            
            <!-- Top Decoration -->
            <div class="h-2 bg-gradient-to-r from-brand-500 to-indigo-600"></div>

            <div class="p-8">
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Шинэ бүртгэл үүсгэх</h1>
                    <p class="text-sm text-gray-500">Filezone-д нэгдэж, файл татаж, зарж эхлээрэй.</p>
                </div>

                <!-- Register Form -->
                <form action="register_process.php" method="POST" class="space-y-4">
                    
                    <!-- Username Input -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1.5">Хэрэглэгчийн нэр</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-user text-sm"></i>
                            </span>
                            <input type="text" id="username" name="username" required placeholder="User123" 
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        </div>
                    </div>

                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Имэйл хаяг</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-envelope text-sm"></i>
                            </span>
                            <input type="email" id="email" name="email" required placeholder="name@example.com" 
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Нууц үг</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-lock text-sm"></i>
                            </span>
                            <input type="password" id="password" name="password" required placeholder="••••••••" 
                                class="w-full pl-10 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        </div>
                    </div>

                    <!-- Confirm Password Input -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1.5">Нууц үг давтах</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-lock text-sm"></i>
                            </span>
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••" 
                                class="w-full pl-10 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        </div>
                    </div>

                    <!-- Terms Checkbox -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="terms" name="terms" type="checkbox" required class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-brand-300 text-brand-600 cursor-pointer">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="terms" class="font-light text-gray-500 cursor-pointer">Би <a href="terms.php" class="font-medium text-brand-600 hover:underline">Үйлчилгээний нөхцөл</a> болон <a href="privacy.php" class="font-medium text-brand-600 hover:underline">Нууцлалын бодлогыг</a> зөвшөөрч байна</label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-2.5 rounded-lg shadow-lg shadow-brand-500/30 transition-all transform hover:-translate-y-0.5 mt-2">
                        Бүртгүүлэх
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

                <!-- Google Signup -->
                <div class="mb-6">
                    <button class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium text-gray-700">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-4 h-4" alt="Google">
                        Google-ээр бүртгүүлэх
                    </button>
                </div>

                <!-- Login Link -->
                <div class="text-center text-sm text-gray-600">
                    Аль хэдийн бүртгэлтэй юу? 
                    <a href="login.php" class="font-bold text-brand-600 hover:text-brand-700 hover:underline">Нэвтрэх</a>
                </div>

            </div>
        </div>

    </main>

    <!-- 4. Simple Footer -->
    <footer class="bg-white border-t border-gray-200 py-6">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-xs text-gray-400">&copy; 2025 Filezone.mn. Бүх эрх хуулиар хамгаалагдсан.</p>
        </div>
    </footer>
    <script src="assets/js/main.js"></script>
</body>
</html>