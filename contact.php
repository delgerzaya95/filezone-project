<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Холбоо барих - Filezone.mn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/tailwind-config.js"></script>
</head>
<body class="text-gray-700 antialiased font-sans flex flex-col min-h-screen">

    <!-- 1. Top Bar -->
    <div class="bg-slate-800 text-gray-400 text-[10px] sm:text-xs py-1.5 border-b border-slate-700">
        <div class="w-full max-w-7xl mx-auto px-4 flex justify-between items-center">
            <span>Монголын дижитал файлын сан</span>
            <div class="flex gap-3">
                <a href="guides.php" class="hover:text-white transition-colors">Тусламж</a>
                <span class="hidden sm:inline">|</span>
                <a href="contact.php" class="text-white font-medium">Холбоо барих</a>
            </div>
        </div>
    </div>
    
    <!-- 2. Navbar -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center flex-1 gap-4 lg:gap-8">
                    <a href="index.php" class="flex-shrink-0 flex items-center gap-2">
                        <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-brand-500/30">F</div>
                        <span class="font-bold text-xl tracking-tight text-gray-900 hidden sm:block">Filezone</span>
                    </a>
                </div>

                <!-- Right Side Actions -->
                <div class="flex items-center gap-4">
                    <a href="upload.php" class="hidden md:flex items-center gap-2 bg-gradient-to-r from-orange-500 to-red-500 text-white px-5 py-2 rounded-full font-bold text-sm shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50 transform hover:-translate-y-0.5 transition-all btn-shine">
                        <i class="fas fa-cloud-upload-alt"></i>
                        Файл оруулах
                    </a>

                    <div class="h-8 w-px bg-gray-200 hidden md:block mx-1"></div>

                    <button class="md:hidden p-2 text-gray-500 hover:bg-gray-100 rounded-lg" onclick="toggleMenu()">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    
                    <div class="hidden md:flex items-center gap-3">
                        <a href="login.php" class="text-gray-500 hover:text-brand-600 font-semibold text-sm px-3 py-2 transition-colors">Нэвтрэх</a>
                        <a href="register.php" class="bg-slate-700 hover:bg-slate-800 text-white text-sm font-bold px-5 py-2.5 rounded-lg transition-all shadow-sm border border-slate-700">
                            Бүртгүүлэх
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex flex-1 max-w-7xl mx-auto w-full">
        
        <!-- Sidebar Navigation -->
        <aside class="hidden lg:block w-64 flex-shrink-0 py-6 pr-6 h-[calc(100vh-64px)] sticky top-16 overflow-y-auto no-scrollbar">
            <!-- Mini Upload CTA -->
            <div class="mb-6 p-4 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 text-white shadow-xl">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                        <i class="fas fa-wallet text-yellow-400"></i>
                    </div>
                    <span class="font-bold text-sm">Мөнгө олох уу?</span>
                </div>
                <p class="text-xs text-gray-300 mb-3 leading-relaxed">Хэрэггүй файлаа устгах биш, бусдад зарж орлого ол!</p>
                <a href="upload.php" class="block w-full text-center bg-white text-gray-900 py-1.5 rounded-lg text-xs font-bold hover:bg-gray-100 transition">
                    Эхлэх &rarr;
                </a>
            </div>

            <!-- Menu Links -->
            <div class="space-y-1 mb-6">
                <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Үндсэн</h3>
                <a href="index.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-home w-5 text-center"></i> Нүүр хуудас
                </a>
                <a href="browse-files.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-folder-open w-5 text-center"></i> Файлууд
                </a>
            </div>

             <!-- Help Menu Active -->
             <div class="space-y-1 mb-8">
                <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Тусламж</h3>
                <a href="guides.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-question-circle w-5 text-center"></i> Түгээмэл асуулт
                </a>
                <a href="contact.php" class="flex items-center gap-3 px-3 py-2 text-brand-600 bg-brand-50 rounded-lg font-medium group">
                    <i class="fas fa-envelope w-5 text-center"></i> Холбоо барих
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
            
            <!-- Page Header -->
            <div class="mb-10 text-center lg:text-left">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Бидэнтэй холбогдох</h1>
                <p class="text-gray-500">Танд асуух зүйл байна уу? Бид танд туслахад үргэлж бэлэн.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
                
                <!-- Contact Info Column -->
                <div class="lg:col-span-1 space-y-6">
                    
                    <!-- Info Card 1 -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-start gap-4">
                        <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 mb-1">Имэйл хаяг</h3>
                            <p class="text-sm text-gray-500 mb-2">Ажлын өдрүүдэд 24 цагийн дотор хариу өгнө.</p>
                            <a href="mailto:info@filezone.mn" class="text-brand-600 font-medium hover:underline">info@filezone.mn</a>
                        </div>
                    </div>

                    <!-- Info Card 2 -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-start gap-4">
                        <div class="w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 mb-1">Утас</h3>
                            <p class="text-sm text-gray-500 mb-2">Дав-Баасан, 09:00 - 18:00</p>
                            <a href="tel:+97688881234" class="text-brand-600 font-medium hover:underline">+976 8888-1234</a>
                        </div>
                    </div>

                    <!-- Social Media Card -->
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 p-6 rounded-2xl text-white shadow-lg">
                        <h3 class="font-bold mb-4">Биднийг дагаарай</h3>
                        <div class="flex gap-4">
                            <a href="#" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-brand-600 transition">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-pink-600 transition">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-blue-400 transition">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Quick Help -->
                    <div class="bg-orange-50 border border-orange-100 p-6 rounded-2xl">
                        <h3 class="font-bold text-orange-800 mb-2 flex items-center gap-2">
                            <i class="fas fa-lightbulb"></i> Зөвлөмж
                        </h3>
                        <p class="text-sm text-orange-700 mb-4">Та асуулт асуухаасаа өмнө манай "Түгээмэл асуулт" хэсгийг шалгаж үзээрэй.</p>
                        <a href="guides.php" class="text-sm font-bold text-orange-600 hover:text-orange-800 underline">Тусламж цэс рүү очих &rarr;</a>
                    </div>

                </div>

                <!-- Contact Form Column -->
                <div class="lg:col-span-2">
                    <div class="bg-white p-6 md:p-8 rounded-2xl border border-gray-200 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Зурвас илгээх</h2>
                        
                        <form action="send_message.php" method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Таны нэр</label>
                                    <input type="text" id="name" name="name" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all" placeholder="Нэрээ оруулна уу">
                                </div>
                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Имэйл хаяг</label>
                                    <input type="email" id="email" name="email" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all" placeholder="name@example.com">
                                </div>
                            </div>

                            <!-- Subject -->
                            <div>
                                <label for="subject" class="block text-sm font-medium text-gray-700 mb-1.5">Сэдэв</label>
                                <select id="subject" name="subject" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all text-gray-600">
                                    <option value="">Сэдвээ сонгоно уу...</option>
                                    <option value="payment">Төлбөр тооцоо</option>
                                    <option value="account">Бүртгэл & Нэвтрэх</option>
                                    <option value="file">Файл оруулах/Татах</option>
                                    <option value="service">Үйлчилгээтэй холбоотой</option>
                                    <option value="other">Бусад</option>
                                </select>
                            </div>

                            <!-- Message -->
                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-1.5">Зурвас</label>
                                <textarea id="message" name="message" rows="6" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all" placeholder="Асуух зүйлээ дэлгэрэнгүй бичнэ үү..."></textarea>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="w-full sm:w-auto bg-brand-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-brand-500/30 hover:bg-brand-700 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-paper-plane"></i> Илгээх
                            </button>
                        </form>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="mt-auto border-t border-gray-200 py-8 text-center bg-white rounded-t-2xl">
                <p class="text-sm text-gray-500 mb-2">&copy; 2025 Filezone.mn. Бүх эрх хуулиар хамгаалагдсан.</p>
                <div class="flex justify-center gap-4 text-xs text-gray-400">
                    <a href="terms.php" class="hover:text-gray-600">Үйлчилгээний нөхцөл</a>
                    <span>•</span>
                    <a href="privacy.php" class="hover:text-gray-600">Нууцлалын бодлого</a>
                </div>
            </div>

        </main>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu-overlay" class="fixed inset-0 bg-gray-900/50 z-40 hidden" onclick="toggleMenu()"></div>
    
    <!-- Mobile Menu Sidebar -->
    <div id="mobile-menu" class="fixed inset-y-0 left-0 w-64 bg-white z-50 transform -translate-x-full transition-transform duration-300 ease-in-out border-r border-gray-200">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <span class="font-bold text-lg text-gray-800">Цэс</span>
            <button onclick="toggleMenu()" class="text-gray-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-4">
            <a href="upload.php" class="block w-full text-center py-2.5 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-lg font-bold shadow-lg">
                <i class="fas fa-cloud-upload-alt mr-2"></i> Файл оруулах
            </a>

            <div class="grid grid-cols-2 gap-3">
                <a href="login.php" class="block w-full text-center py-2.5 border border-gray-200 rounded-xl font-semibold text-gray-600 hover:bg-gray-50">Нэвтрэх</a>
                <a href="register.php" class="block w-full text-center py-2.5 bg-slate-700 text-white rounded-xl font-bold hover:bg-slate-800 shadow-sm">Бүртгүүлэх</a>
            </div>
            
            <hr class="border-gray-100">

            <a href="index.php" class="flex items-center gap-3 text-gray-600 font-medium">
                <i class="fas fa-home w-5 text-center text-gray-400"></i> Нүүр хуудас
            </a>
            <a href="guides.php" class="flex items-center gap-3 text-gray-600 font-medium">
                <i class="fas fa-question-circle w-5 text-center text-gray-400"></i> Тусламж
            </a>
            <a href="contact.php" class="flex items-center gap-3 text-brand-600 font-medium">
                <i class="fas fa-envelope w-5 text-center"></i> Холбоо барих
            </a>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('mobile-menu');
            const overlay = document.getElementById('mobile-menu-overlay');
            
            if (menu.classList.contains('-translate-x-full')) {
                menu.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                menu.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
    </script>
</body>
</html>