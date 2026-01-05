<?php
// Одоогийн хуудасны нэрийг авах
$current_page = basename($_SERVER['PHP_SELF']);

/**
 * Цэс идэвхтэй эсэхийг шалгах функц
 * @param string|array $page_names Файлын нэр (эсвэл нэрс)
 * @return string Tailwind classes
 */
function getLinkClass($page_names) {
    global $current_page;
    
    $isActive = false;
    if (is_array($page_names)) {
        if (in_array($current_page, $page_names)) $isActive = true;
    } else {
        if ($current_page === $page_names) $isActive = true;
    }

    // Идэвхтэй үеийн загвар vs Идэвхгүй үеийн загвар
    if ($isActive) {
        return 'bg-slate-800 text-white border-r-4 border-indigo-500 shadow-lg';
    } else {
        return 'text-slate-400 hover:bg-slate-800 hover:text-white hover:border-r-4 hover:border-slate-700';
    }
}

function getIconClass($page_names) {
    global $current_page;
    $isActive = false;
    if (is_array($page_names)) {
        if (in_array($current_page, $page_names)) $isActive = true;
    } else {
        if ($current_page === $page_names) $isActive = true;
    }
    return $isActive ? 'text-indigo-400' : 'text-slate-500 group-hover:text-indigo-400';
}
?>

<!-- SIDEBAR -->
<!-- Зассан хэсэг: 'fixed left-0 top-0 h-screen' классуудыг авч, flex бүтэцтэй нийцүүлэв -->
<aside id="sidebar" class="bg-slate-900 text-slate-400 w-64 flex-shrink-0 hidden md:flex flex-col transition-all duration-300 z-30 shadow-2xl overflow-y-auto no-scrollbar">
    
    <!-- Brand -->
    <div class="h-16 flex items-center px-6 border-b border-slate-800 bg-slate-950 sticky top-0 z-20">
        <a href="index.php" class="flex items-center gap-3">
            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-indigo-500/30">
                F
            </div>
            <span class="text-xl font-bold text-white tracking-tight">Filezone <span class="text-xs text-indigo-400 uppercase ml-1 font-mono">Admin</span></span>
        </a>
    </div>

    <!-- Navigation -->
    <div class="flex-1 py-6 space-y-1">
        
        <!-- SECTION: DASHBOARD -->
        <p class="px-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Үндсэн</p>
        
        <a href="index.php" class="<?php echo getLinkClass('index.php'); ?> flex items-center px-6 py-3 text-sm font-medium group transition-all duration-200">
            <i class="fas fa-tachometer-alt w-5 text-center mr-3 <?php echo getIconClass('index.php'); ?>"></i>
            Хяналтын самбар
        </a>

        <!-- SECTION: CONTENT -->
        <p class="px-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 mt-6">Контент Удирдлага</p>

        <a href="files.php" class="<?php echo getLinkClass('files.php'); ?> flex items-center px-6 py-3 text-sm font-medium group transition-all duration-200">
            <i class="fas fa-folder-open w-5 text-center mr-3 <?php echo getIconClass('files.php'); ?>"></i>
            Файлууд
        </a>
        
        <a href="categories.php" class="<?php echo getLinkClass('categories.php'); ?> flex items-center px-6 py-3 text-sm font-medium group transition-all duration-200">
            <i class="fas fa-tags w-5 text-center mr-3 <?php echo getIconClass('categories.php'); ?>"></i>
            Файлын ангилал
        </a>

        <a href="services.php" class="<?php echo getLinkClass('services.php'); ?> flex items-center px-6 py-3 text-sm font-medium group transition-all duration-200">
            <i class="fas fa-briefcase w-5 text-center mr-3 <?php echo getIconClass('services.php'); ?>"></i>
            Үйлчилгээнүүд
        </a>

        <a href="service_categories.php" class="<?php echo getLinkClass('service_categories.php'); ?> flex items-center px-6 py-3 text-sm font-medium group transition-all duration-200">
            <i class="fas fa-layer-group w-5 text-center mr-3 <?php echo getIconClass('service_categories.php'); ?>"></i>
            Үйлчилгээ ангилал
        </a>

        <!-- SECTION: ORDERS & REPORTS -->
        <p class="px-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 mt-6">Захиалга & Хяналт</p>

        <a href="service_orders.php" class="<?php echo getLinkClass('service_orders.php'); ?> flex items-center px-6 py-3 text-sm font-medium group transition-all duration-200">
            <i class="fas fa-shopping-cart w-5 text-center mr-3 <?php echo getIconClass('service_orders.php'); ?>"></i>
            Захиалгууд
        </a>

        <a href="reports.php" class="<?php echo getLinkClass('reports.php'); ?> flex items-center px-6 py-3 text-sm font-medium group transition-all duration-200">
            <i class="fas fa-exclamation-circle w-5 text-center mr-3 <?php echo getIconClass('reports.php'); ?>"></i>
            Зөрчил мэдээлэл
        </a>

        <a href="comments.php" class="<?php echo getLinkClass('comments.php'); ?> flex items-center px-6 py-3 text-sm font-medium group transition-all duration-200">
            <i class="fas fa-comments w-5 text-center mr-3 <?php echo getIconClass('comments.php'); ?>"></i>
            Сэтгэгдлүүд
        </a>

        <!-- SECTION: USERS & SETTINGS -->
        <p class="px-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 mt-6">Систем</p>

        <a href="users.php" class="<?php echo getLinkClass(['users.php', 'user-details.php']); ?> flex items-center px-6 py-3 text-sm font-medium group transition-all duration-200">
            <i class="fas fa-users w-5 text-center mr-3 <?php echo getIconClass(['users.php', 'user-details.php']); ?>"></i>
            Хэрэглэгчид
        </a>

        <a href="finance.php" class="<?php echo getLinkClass('finance.php'); ?> flex items-center px-6 py-3 text-sm font-medium group transition-all duration-200">
            <i class="fas fa-wallet w-5 text-center mr-3 <?php echo getIconClass('finance.php'); ?>"></i>
            Санхүү
        </a>

        <a href="settings.php" class="<?php echo getLinkClass('settings.php'); ?> flex items-center px-6 py-3 text-sm font-medium group transition-all duration-200">
            <i class="fas fa-cog w-5 text-center mr-3 <?php echo getIconClass('settings.php'); ?>"></i>
            Тохиргоо
        </a>
        
        <!-- Documentation Link (Optional) -->
        <a href="#" class="flex items-center px-6 py-3 text-sm font-medium text-slate-500 hover:text-white group transition-colors mt-4">
            <i class="fas fa-book w-5 text-center mr-3 group-hover:text-indigo-400"></i>
            Гарын авлага
        </a>

    </div>

    <!-- User Info (Bottom Sticky) -->
    <div class="border-t border-slate-800 p-4 bg-slate-950 sticky bottom-0 z-20">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 p-[2px]">
                <div class="w-full h-full rounded-full bg-slate-900 flex items-center justify-center text-xs font-bold text-white">
                    AD
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate">Администратор</p>
                <p class="text-xs text-slate-500 truncate">admin@filezone.mn</p>
            </div>
            <a href="logout.php" class="text-slate-500 hover:text-red-400 transition p-2 rounded-lg hover:bg-slate-800" title="Гарах">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>