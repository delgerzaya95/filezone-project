<?php
// Одоогийн хуудасны нэрийг авах
$current_page = basename($_SERVER['PHP_SELF']);

/**
 * Цэс идэвхтэй эсэхийг шалгах функц
 * @param string $page_name Файлын нэр (Ex: admin-dashboard.php)
 * @return string 'active' эсвэл хоосон
 */
function isActive($page_name) {
    global $current_page;
    return $current_page === $page_name ? 'active' : '';
}

/**
 * Icon-ны өнгийг идэвхтэй эсэхээс хамаарч солих
 * (Хэрэв CSS-ээр шийдээгүй бол энэ хэрэг болно, гэхдээ style.css шийдэж байгаа)
 */
?>

<!-- SIDEBAR -->
<aside id="sidebar" class="bg-sidebar text-slate-400 w-64 flex-shrink-0 hidden md:flex flex-col transition-all duration-300 z-30 shadow-xl h-screen flex left-0 top-0">
    <!-- Brand -->
    <div class="h-16 flex items-center px-6 border-b border-slate-800 bg-slate-900">
        <a href="admin-dashboard.php" class="flex items-center gap-3">
            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                F
            </div>
            <span class="text-xl font-bold text-white tracking-tight">Filezone <span class="text-xs text-indigo-400 uppercase ml-1">Admin</span></span>
        </a>
    </div>

    <!-- Navigation -->
    <div class="flex-1 overflow-y-auto py-4">
        <nav class="space-y-1">
            <p class="px-6 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 mt-2">Үндсэн</p>
            
            <a href="index.php" class="sidebar-link <?php echo isActive('index.php'); ?> flex items-center px-6 py-3 text-sm font-medium hover:text-white group transition-colors">
                <i class="fas fa-chart-pie mr-3 group-hover:text-indigo-400"></i>
                Хяналтын самбар
            </a>
            <a href="files.php" class="sidebar-link <?php echo isActive('files.php'); ?> flex items-center px-6 py-3 text-sm font-medium hover:text-white group transition-colors">
                <i class="fas fa-folder mr-3 group-hover:text-indigo-400"></i>
                Файлууд
            </a>
            <a href="users.php" class="sidebar-link <?php echo isActive('users.php'); ?> flex items-center px-6 py-3 text-sm font-medium hover:text-white group transition-colors">
                <i class="fas fa-users mr-3 group-hover:text-indigo-400"></i>
                Хэрэглэгчид
            </a>

            <p class="px-6 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 mt-6">Контент</p>
            
            <a href="services.php" class="sidebar-link <?php echo isActive('services.php'); ?> flex items-center px-6 py-3 text-sm font-medium hover:text-white group transition-colors">
                <i class="fas fa-concierge-bell mr-3 group-hover:text-indigo-400"></i>
                Үйлчилгээ
            </a>
            <a href="categories.php" class="sidebar-link <?php echo isActive('categories.php'); ?> flex items-center px-6 py-3 text-sm font-medium hover:text-white group transition-colors">
                <i class="fas fa-list mr-3 group-hover:text-indigo-400"></i>
                Ангилал
            </a>
            <a href="comments.php" class="sidebar-link <?php echo isActive('comments.php'); ?> flex items-center px-6 py-3 text-sm font-medium hover:text-white group transition-colors">
                <i class="fas fa-comments mr-3 group-hover:text-indigo-400"></i>
                Сэтгэгдлүүд
            </a>
            <a href="#" class="sidebar-link flex items-center px-6 py-3 text-sm font-medium hover:text-white group transition-colors">
                <i class="fas fa-book mr-3 group-hover:text-indigo-400"></i>
                Заавар (Coming Soon)
            </a>

            <p class="px-6 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 mt-6">Санхүү & Тохиргоо</p>

            <a href="finance.php" class="sidebar-link <?php echo isActive('finance.php'); ?> flex items-center px-6 py-3 text-sm font-medium hover:text-white group transition-colors">
                <i class="fas fa-wallet mr-3 group-hover:text-indigo-400"></i>
                Санхүү
            </a>
            <a href="settings.php" class="sidebar-link <?php echo isActive('settings.php'); ?> flex items-center px-6 py-3 text-sm font-medium hover:text-white group transition-colors">
                <i class="fas fa-cog mr-3 group-hover:text-indigo-400"></i>
                Тохиргоо
            </a>
        </nav>
    </div>

    <!-- User Info (Bottom) -->
    <div class="border-t border-slate-800 p-4 bg-slate-900">
        <div class="flex items-center gap-3">
            <img src="https://ui-avatars.com/api/?name=Admin&background=6366f1&color=fff" class="w-9 h-9 rounded-full border border-slate-600">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate">Администратор</p>
                <p class="text-xs text-slate-500 truncate">admin@filezone.mn</p>
            </div>
            <a href="logout.php" class="text-slate-500 hover:text-white transition" title="Гарах">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>