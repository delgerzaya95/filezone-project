<?php
// DB холболт байгаа эсэхийг шалгах, байхгүй бол оруулах
if (!isset($pdo)) {
    require_once __DIR__ . '/db.php';
}

// Одоогийн хуудасны нэрийг авах
$current_page = basename($_SERVER['PHP_SELF']);

/**
 * Цэс идэвхтэй эсэхийг шалгах функц
 */
function getSidebarLinkClass($page_names) {
    global $current_page;
    
    $isActive = false;
    if (is_array($page_names)) {
        if (in_array($current_page, $page_names)) $isActive = true;
    } else {
        if ($current_page === $page_names) $isActive = true;
    }

    if ($isActive) {
        return 'text-brand-600 bg-brand-50 font-semibold';
    } else {
        return 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium';
    }
}

function getSidebarIconClass($page_names) {
    global $current_page;
    $isActive = false;
    if (is_array($page_names)) {
        if (in_array($current_page, $page_names)) $isActive = true;
    } else {
        if ($current_page === $page_names) $isActive = true;
    }
    return $isActive ? 'text-brand-600' : 'text-gray-400 group-hover:text-gray-500';
}

// -----------------------------------------------------------
// DATA FETCHING (Өгөгдөл татах)
// -----------------------------------------------------------

// 1. Файлын ангилалуудыг татах
try {
    $stmt_cats = $pdo->prepare("SELECT * FROM categories WHERE type = 'file' ORDER BY id ASC");
    $stmt_cats->execute();
    $file_categories = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $file_categories = []; // Алдаа гарвал хоосон массив
}

// 2. Үйлчилгээний (Freelance) ангилалуудыг татах
try {
    $stmt_serv_cats = $pdo->prepare("SELECT * FROM service_categories ORDER BY id ASC");
    $stmt_serv_cats->execute();
    $service_categories = $stmt_serv_cats->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $service_categories = [];
}
?>

<aside class="hidden lg:block w-64 flex-shrink-0 py-6 pr-6 h-[calc(100vh-64px)] sticky top-16 overflow-y-auto no-scrollbar">
    
    <!-- Mini Upload CTA -->
    <div class="mb-6 p-4 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 text-white shadow-xl relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-16 h-16 bg-white/10 rounded-full blur-xl animate-pulse"></div>
        <div class="flex items-center gap-3 mb-2 relative z-10">
            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                <i class="fas fa-wallet text-yellow-400"></i>
            </div>
            <span class="font-bold text-sm">Мөнгө олох уу?</span>
        </div>
        <p class="text-xs text-gray-300 mb-3 leading-relaxed relative z-10">Хэрэггүй файлаа устгах биш, бусдад зарж орлого ол!</p>
        
        <!-- Updated Link to help.php -->
        <a href="help.php" class="block w-full text-center bg-white text-gray-900 py-1.5 rounded-lg text-xs font-bold hover:bg-gray-100 transition relative z-10 shadow-sm">
            Эхлэх &rarr;
        </a>
    </div>

    <!-- Main Menu -->
    <div class="space-y-1 mb-8">
        <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Үндсэн</h3>
        
        <a href="index.php" class="<?php echo getSidebarLinkClass('index.php'); ?> flex items-center gap-3 px-3 py-2 rounded-lg transition-colors group">
            <i class="fas fa-home w-5 text-center <?php echo getSidebarIconClass('index.php'); ?>"></i> Нүүр хуудас
        </a>
        
        <a href="categories.php" class="<?php echo getSidebarLinkClass('categories.php'); ?> flex items-center gap-3 px-3 py-2 rounded-lg transition-colors group">
            <i class="fas fa-th-large w-5 text-center <?php echo getSidebarIconClass('categories.php'); ?>"></i> Ангилал
        </a>

        <a href="services.php" class="<?php echo getSidebarLinkClass(['services.php', 'service-details.php']); ?> flex items-center gap-3 px-3 py-2 rounded-lg transition-colors group">
            <i class="fas fa-briefcase w-5 text-center <?php echo getSidebarIconClass(['services.php', 'service-details.php']); ?>"></i> Үйлчилгээнүүд
        </a>

        <a href="trending.php" class="<?php echo getSidebarLinkClass('trending.php'); ?> flex items-center gap-3 px-3 py-2 rounded-lg transition-colors group">
            <i class="fas fa-fire w-5 text-center text-orange-500"></i> Эрэлттэй
        </a>

        <a href="browse-files.php" class="<?php echo getSidebarLinkClass(['browse-files.php', 'file-details.php']); ?> flex items-center gap-3 px-3 py-2 rounded-lg transition-colors group">
            <i class="fas fa-clock w-5 text-center text-blue-500"></i> Шинэ файлууд
        </a>
    </div>

    <!-- File Categories Section (Dynamic) -->
    <div class="space-y-1 mb-8">
        <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Файл Ангилалууд</h3>
        
        <?php if (!empty($file_categories)): ?>
            <?php foreach($file_categories as $cat): ?>
                <?php 
                    // Icon-д өнгө оноох (Hardcoded style-ийн оронд энгийн logic ашиглав)
                    $icon_color = 'text-gray-400 group-hover:text-gray-500';
                    if (strpos($cat['slug'], 'education') !== false) $icon_color = 'text-brand-500';
                    elseif (strpos($cat['slug'], 'business') !== false) $icon_color = 'text-emerald-500';
                    elseif (strpos($cat['slug'], 'technology') !== false) $icon_color = 'text-indigo-500';
                    elseif (strpos($cat['slug'], 'graphic') !== false) $icon_color = 'text-purple-500';
                ?>
                <a href="categories.php?cat=<?php echo htmlspecialchars($cat['slug']); ?>" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors group">
                    <i class="<?php echo htmlspecialchars($cat['icon_class'] ?? 'fas fa-folder'); ?> w-5 text-center <?php echo $icon_color; ?>"></i> 
                    <span class="truncate"><?php echo htmlspecialchars($cat['name']); ?></span>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="px-3 text-xs text-gray-400">Ангилал олдсонгүй.</p>
        <?php endif; ?>
    </div>

    <!-- Services Categories (Freelance) (Dynamic) -->
    <div class="space-y-1 mb-8">
        <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Freelance</h3>
        
        <?php if (!empty($service_categories)): ?>
            <?php foreach($service_categories as $scat): ?>
                <?php 
                    // Background colors for service icons
                    $bg_color = 'bg-gray-100 text-gray-600';
                    if (strpos($scat['slug'], 'design') !== false) $bg_color = 'bg-purple-100 text-purple-600 group-hover:bg-purple-600 group-hover:text-white';
                    elseif (strpos($scat['slug'], 'programming') !== false) $bg_color = 'bg-blue-100 text-blue-600 group-hover:bg-blue-600 group-hover:text-white';
                    elseif (strpos($scat['slug'], 'marketing') !== false) $bg_color = 'bg-green-100 text-green-600 group-hover:bg-green-600 group-hover:text-white';
                    elseif (strpos($scat['slug'], 'translation') !== false) $bg_color = 'bg-orange-100 text-orange-600 group-hover:bg-orange-600 group-hover:text-white';
                    else $bg_color = 'bg-indigo-100 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white';
                ?>
                <a href="services.php?cat=<?php echo htmlspecialchars($scat['slug']); ?>" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium group transition-colors">
                    <div class="w-5 h-5 rounded flex items-center justify-center transition <?php echo $bg_color; ?>">
                        <i class="<?php echo htmlspecialchars($scat['icon_class'] ?? 'fas fa-briefcase'); ?> text-xs"></i>
                    </div>
                    <span class="truncate"><?php echo htmlspecialchars($scat['name']); ?></span>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="px-3 text-xs text-gray-400">Үйлчилгээ олдсонгүй.</p>
        <?php endif; ?>
    </div>

    <!-- Platforms -->
    <div class="space-y-1 mb-8">
        <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Платформууд</h3>
        
        <a href="kids/" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors group">
            <div class="w-5 h-5 rounded bg-pink-100 text-pink-500 flex items-center justify-center group-hover:bg-pink-500 group-hover:text-white transition">
                <i class="fas fa-shapes text-xs"></i>
            </div>
            Filezone Kids
        </a>

        <a href="Tools/index.html" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors group">
            <div class="w-5 h-5 rounded bg-indigo-100 text-indigo-500 flex items-center justify-center group-hover:bg-indigo-500 group-hover:text-white transition">
                <i class="fas fa-toolbox text-xs"></i>
            </div>
            Онлайн хэрэгсэл
        </a>
    </div>
</aside>