<?php
// Одоогийн хуудасны нэрийг авах (Жишээ нь: index.php, categories.php)
$current_page = basename($_SERVER['PHP_SELF']);

// Идэвхтэй болон идэвхгүй үеийн загварын класс
$active_class = "text-brand-600 bg-brand-50";
$inactive_class = "text-gray-600 hover:bg-gray-100 hover:text-gray-900";
?>

<!-- Sidebar Navigation -->
<aside class="hidden lg:block w-64 flex-shrink-0 py-6 pr-6 h-[calc(100vh-64px)] sticky top-16 overflow-y-auto no-scrollbar">
    
    <!-- Mini Upload CTA in Sidebar -->
    <div class="mb-6 p-4 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 text-white shadow-xl relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-16 h-16 bg-white/10 rounded-full blur-xl animate-pulse"></div>
        <div class="flex items-center gap-3 mb-2 relative z-10">
            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                <i class="fas fa-wallet text-yellow-400"></i>
            </div>
            <span class="font-bold text-sm">Мөнгө олох уу?</span>
        </div>
        <p class="text-xs text-gray-300 mb-3 leading-relaxed relative z-10">Хэрэггүй файлаа устгах биш, бусдад зарж орлого ол!</p>
        <a href="upload.php" class="block w-full text-center bg-white text-gray-900 py-1.5 rounded-lg text-xs font-bold hover:bg-gray-100 transition relative z-10">
            Эхлэх &rarr;
        </a>
    </div>

    <!-- Main Menu -->
    <div class="space-y-1 mb-8">
        <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Үндсэн</h3>
        
        <!-- Нүүр хуудас -->
        <a href="index.php" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?php echo ($current_page == 'index.php') ? $active_class : $inactive_class; ?>">
            <i class="fas fa-home w-5 text-center"></i> Нүүр хуудас
        </a>
        
        <!-- Ангилал (Categories) -->
        <a href="categories.php" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?php echo ($current_page == 'categories.php') ? $active_class : $inactive_class; ?>">
            <i class="fas fa-th-large w-5 text-center <?php echo ($current_page == 'categories.php') ? '' : 'text-indigo-500'; ?>"></i> Ангилал
        </a>

        <!-- Үйлчилгээнүүд (Services) -->
        <a href="services.php" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?php echo ($current_page == 'services.php') ? $active_class : $inactive_class; ?>">
            <i class="fas fa-briefcase w-5 text-center <?php echo ($current_page == 'services.php') ? '' : 'text-teal-500'; ?>"></i> Үйлчилгээнүүд
        </a>

        <!-- Эрэлттэй -->
        <a href="trending.php" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?php echo ($current_page == 'trending.php') ? $active_class : $inactive_class; ?>">
            <i class="fas fa-fire w-5 text-center text-orange-500"></i> Эрэлттэй
        </a>

        <!-- Шинэ файлууд -->
        <a href="browse-files.php" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?php echo ($current_page == 'browse-files.php') ? $active_class : $inactive_class; ?>">
            <i class="fas fa-clock w-5 text-center text-blue-500"></i> Шинэ файлууд
        </a>
    </div>

    <!-- NEW SECTION: File Categories -->
    <div class="space-y-1 mb-8">
        <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Файл Ангилалууд</h3>
        
        <a href="category.php?id=diploma" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
            <i class="fas fa-graduation-cap w-5 text-center text-brand-500"></i> Диплом & Судалгаа
        </a>
        <a href="category.php?id=legal" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
            <i class="fas fa-file-contract w-5 text-center text-emerald-500"></i> Гэрээ & Эрх зүй
        </a>
        <a href="category.php?id=forms" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
            <i class="fas fa-file-invoice w-5 text-center text-amber-500"></i> Маягт & Загвар
        </a>
        <a href="category.php?id=books" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
            <i class="fas fa-book w-5 text-center text-rose-500"></i> Ном & Товхимол
        </a>
    </div>

    <!-- Services Menu (Freelance) -->
    <div class="space-y-1 mb-8">
        <div class="flex items-center justify-between px-3 mb-2">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Freelance</h3>
        </div>
        <!-- Жич: Хэрэв services.php дотор категориудаар шүүдэг бол доорх логикийг мөн сайжруулж болно -->
        <a href="services.php?cat=design" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium group">
            <div class="w-5 h-5 rounded bg-purple-100 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition">
                <i class="fas fa-pen-nib text-xs"></i>
            </div>
            Дизайн & График
        </a>
        <a href="services.php?cat=dev" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium group">
            <div class="w-5 h-5 rounded bg-blue-100 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition">
                <i class="fas fa-code text-xs"></i>
            </div>
            Вэб хөгжүүлэлт
        </a>
        <a href="services.php?cat=video" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium group">
            <div class="w-5 h-5 rounded bg-green-100 text-green-600 flex items-center justify-center group-hover:bg-green-600 group-hover:text-white transition">
                <i class="fas fa-video text-xs"></i>
            </div>
            Видео & Анимэйшн
        </a>
    </div>

    <!-- Resources & Platforms -->
    <div class="space-y-1 mb-8">
        <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Платформууд</h3>
        
        <!-- Filezone Kids -->
        <a href="Kids/index.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors group">
            <div class="w-5 h-5 rounded bg-pink-100 text-pink-500 flex items-center justify-center group-hover:bg-pink-500 group-hover:text-white transition">
                <i class="fas fa-shapes text-xs"></i>
            </div>
            Filezone Kids
        </a>

        <!-- Online Tools -->
        <a href="Tools/index.html" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors group">
            <div class="w-5 h-5 rounded bg-indigo-100 text-indigo-500 flex items-center justify-center group-hover:bg-indigo-500 group-hover:text-white transition">
                <i class="fas fa-toolbox text-xs"></i>
            </div>
            Онлайн хэрэгсэл
        </a>
    </div>
</aside>