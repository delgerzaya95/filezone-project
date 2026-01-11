<?php
// Filezone Kids - Profile Page
// Path: filezone.mn/kids/profile.php

// 1. DB Connections
require_once __DIR__ . '/../includes/db.php'; // Main DB (Auth)
require_once __DIR__ . '/db_kids.php';        // Kids DB (Data)

$page_title = "Миний булан - Filezone Kids";

// Kids header-ийг дуудна
include 'header.php';

// Хэрэглэгч нэвтрээгүй бол нэвтрэх хуудас руу шилжүүлэх
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?redirect=kids/profile.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// --- 2. CHECK PREMIUM STATUS (LIFETIME UPDATE) ---
$is_premium = false;
try {
    // end_date IS NULL гэдэг нь насан туршийн эрх гэсэн үг
    $stmt_sub = $pdo_kids->prepare("
        SELECT id FROM kids_subscriptions 
        WHERE user_id = ? 
        AND status = 'active' 
        AND (end_date IS NULL OR end_date > NOW()) 
        LIMIT 1
    ");
    $stmt_sub->execute([$user_id]);
    if ($stmt_sub->fetch()) {
        $is_premium = true;
    }
} catch (Exception $e) { /* Silent fail */ }

// --- 3. HELPER FUNCTIONS ---
function getActiveTabClass($tabName, $currentTab) {
    return $tabName === $currentTab 
        ? 'bg-[#33cbcc] text-white shadow-md transform scale-105 ring-2 ring-offset-2 ring-[#33cbcc]' 
        : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-100';
}

function getProfileAgeLabel($age) {
    $labels = [
        '2-3' => '2-3 Нас', '3-4' => '3-4 Нас', '4-5' => '4-5 Нас',
        'school_prep' => 'Сургуульд бэлтгэх', 'preschool' => 'Сургуульд бэлтгэх',
        'grade-1' => '1-р анги', 'all' => 'Бүх нас'
    ];
    return $labels[$age] ?? $age;
}

// --- 4. FETCH DATA BASED ON TAB ---
$my_downloads = [];
$my_saved = [];
$recent_download = null;
$recommended_items = [];

try {
    if ($current_tab == 'dashboard') {
        // Fetch Last Download
        $stmt_last = $pdo_kids->prepare("
            SELECT m.*, c.name as cat_name, c.color_theme, c.icon_class
            FROM kids_downloads kd
            JOIN kids_materials m ON kd.material_id = m.id
            LEFT JOIN kids_categories c ON m.category_id = c.id
            WHERE kd.user_id = ?
            ORDER BY kd.downloaded_at DESC
            LIMIT 1
        ");
        $stmt_last->execute([$user_id]);
        $recent_download = $stmt_last->fetch(PDO::FETCH_ASSOC);

        // Fetch Random Recommendations
        $stmt_rec = $pdo_kids->prepare("
            SELECT m.*, c.name as cat_name, c.color_theme, c.icon_class
            FROM kids_materials m
            LEFT JOIN kids_categories c ON m.category_id = c.id
            WHERE m.status = 'active'
            ORDER BY RAND()
            LIMIT 2
        ");
        $stmt_rec->execute();
        $recommended_items = $stmt_rec->fetchAll();
    }
    
    if ($current_tab == 'downloads') {
        // Fetch All Downloads
        $stmt_dl = $pdo_kids->prepare("
            SELECT m.*, c.name as cat_name, c.color_theme, c.icon_class, kd.downloaded_at
            FROM kids_downloads kd
            JOIN kids_materials m ON kd.material_id = m.id
            LEFT JOIN kids_categories c ON m.category_id = c.id
            WHERE kd.user_id = ?
            GROUP BY m.id 
            ORDER BY kd.downloaded_at DESC
        ");
        $stmt_dl->execute([$user_id]);
        $my_downloads = $stmt_dl->fetchAll();
    }

    if ($current_tab == 'saved') {
        // Fetch Saved Items
        $stmt_sv = $pdo_kids->prepare("
            SELECT m.*, c.name as cat_name, c.color_theme, c.icon_class, ks.saved_at
            FROM kids_saved_materials ks
            JOIN kids_materials m ON ks.material_id = m.id
            LEFT JOIN kids_categories c ON m.category_id = c.id
            WHERE ks.user_id = ?
            ORDER BY ks.saved_at DESC
        ");
        $stmt_sv->execute([$user_id]);
        $my_saved = $stmt_sv->fetchAll();
    }

} catch (PDOException $e) {
    // Handle error gracefully
}
?>

<div class="font-kids bg-gray-50 min-h-screen pb-20">
    
    <!-- Profile Header / Cover Area -->
    <div class="bg-[#e0f7fa] pt-8 pb-20 relative overflow-hidden">
        <!-- Decor -->
        <div class="absolute top-5 left-10 text-yellow-400 animate-pulse delay-700"><i class="fas fa-star fa-2x"></i></div>
        <div class="absolute bottom-10 right-10 text-pink-400 animate-bounce"><i class="fas fa-heart fa-2x"></i></div>
        <div class="absolute top-10 right-20 text-blue-300 opacity-50"><i class="fas fa-cloud fa-3x"></i></div>

        <div class="container mx-auto px-4 relative z-10 flex flex-col md:flex-row items-center gap-6">
            <!-- Avatar -->
            <div class="relative group">
                <?php if (!empty($kidsUser['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($kidsUser['avatar']); ?>" class="w-28 h-28 md:w-36 md:h-36 rounded-full border-4 border-white shadow-lg object-cover bg-white" alt="Profile">
                <?php else: ?>
                    <div class="w-28 h-28 md:w-36 md:h-36 rounded-full border-4 border-white shadow-lg flex items-center justify-center text-5xl font-bold <?php echo $kidsUser['color']['bg'] . ' ' . $kidsUser['color']['text']; ?>">
                        <?php echo $kidsUser['initials']; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Edit button -->
                <a href="../profile/settings.php" class="absolute bottom-0 right-0 bg-white p-2.5 rounded-full shadow-md text-gray-500 hover:text-[#33cbcc] transition border border-gray-100" title="Зураг солих">
                    <i class="fas fa-camera"></i>
                </a>
            </div>
            
            <!-- Info -->
            <div class="text-center md:text-left flex flex-col items-center md:items-start">
                <h1 class="text-3xl md:text-4xl font-extrabold text-gray-800 font-title mb-1">
                    Сайн уу, <span class="text-[#33cbcc]"><?php echo htmlspecialchars($kidsUser['name']); ?>!</span> 👋
                </h1>
                
                <!-- Premium Status Indicator -->
                <div class="flex items-center gap-3 mt-1 mb-3">
                    <?php if($is_premium): ?>
                        <span class="bg-gradient-to-r from-yellow-400 to-yellow-600 text-white text-sm font-bold px-3 py-1 rounded-full shadow-sm flex items-center gap-1.5 transform hover:scale-105 transition cursor-help" title="Таньд насан туршийн эрх байна">
                            <i class="fas fa-crown text-yellow-100"></i> Насан туршийн эрх
                        </span>
                    <?php else: ?>
                        <span class="bg-gray-200 text-gray-600 text-xs font-bold px-3 py-1 rounded-full border border-gray-300">
                            Энгийн эрх
                        </span>
                        <a href="premium.php" class="text-sm text-[#33cbcc] font-bold hover:text-teal-600 hover:underline flex items-center gap-1 transition">
                            <i class="fas fa-arrow-up"></i> Premium авах
                        </a>
                    <?php endif; ?>
                </div>

                <p class="text-gray-600 font-medium text-lg bg-white/50 px-4 py-1 rounded-full inline-block">Өнөөдөр ямар шинэ зүйл сурах вэ?</p>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs (Floating) -->
    <div class="container mx-auto px-4 -mt-10 relative z-20 mb-8">
        <div class="bg-white/80 backdrop-blur-sm p-2 rounded-2xl shadow-lg inline-flex flex-wrap gap-2 justify-center w-full md:w-auto border border-white">
            <a href="?tab=dashboard" class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2 <?php echo getActiveTabClass('dashboard', $current_tab); ?>">
                <i class="fas fa-home"></i> <span class="hidden sm:inline">Миний булан</span>
            </a>
            <a href="?tab=downloads" class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2 <?php echo getActiveTabClass('downloads', $current_tab); ?>">
                <i class="fas fa-download"></i> <span class="hidden sm:inline">Татсан материалууд</span>
            </a>
            <a href="?tab=saved" class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2 <?php echo getActiveTabClass('saved', $current_tab); ?>">
                <i class="fas fa-heart"></i> <span class="hidden sm:inline">Хадгалсан</span>
            </a>
        </div>
    </div>

    <!-- Content Area -->
    <div class="container mx-auto px-4">
        
        <?php if ($current_tab == 'dashboard'): ?>
            <!-- DASHBOARD CONTENT -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Column: Main Activity -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- Section: Recent Activity -->
                    <div class="bg-white rounded-3xl p-8 shadow-sm border border-gray-100 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-yellow-50 rounded-bl-full -mr-10 -mt-10 z-0"></div>
                        <h2 class="text-2xl font-extrabold text-gray-800 mb-6 relative z-10 font-title flex items-center gap-2">
                            <span class="bg-yellow-100 text-yellow-500 p-2 rounded-lg text-xl"><i class="fas fa-history"></i></span> 
                            Сүүлд татсан
                        </h2>
                        
                        <?php if($recent_download): ?>
                            <a href="material.php?id=<?php echo $recent_download['id']; ?>" class="flex items-start gap-4 p-4 rounded-2xl bg-gray-50 border border-gray-200 hover:border-yellow-300 hover:bg-yellow-50/50 transition group">
                                <div class="w-20 h-20 bg-white rounded-xl border border-gray-200 flex items-center justify-center text-4xl shadow-sm text-<?php echo $recent_download['color_theme']; ?>-400">
                                    <i class="<?php echo $recent_download['icon_class']; ?>"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800 group-hover:text-[#33cbcc] transition mb-1"><?php echo htmlspecialchars($recent_download['title']); ?></h3>
                                    <p class="text-sm text-gray-500 mb-2 line-clamp-1"><?php echo htmlspecialchars($recent_download['cat_name']); ?> • <?php echo getProfileAgeLabel($recent_download['target_age']); ?></p>
                                    <span class="text-xs font-bold text-[#33cbcc] bg-white px-2 py-1 rounded border border-[#33cbcc]/30">
                                        Үргэлжлүүлэх &rarr;
                                    </span>
                                </div>
                            </a>
                        <?php else: ?>
                            <div class="text-center py-10 border-2 border-dashed border-gray-200 rounded-2xl bg-gray-50/50">
                                <div class="inline-block p-4 rounded-full bg-gray-100 mb-3 text-gray-300">
                                    <i class="far fa-folder-open text-3xl"></i>
                                </div>
                                <p class="text-gray-500 font-medium mb-4">Одоогоор татсан материал алга байна.</p>
                                <a href="browse.php" class="inline-block bg-[#33cbcc] hover:bg-[#2bb5b6] text-white font-bold py-2 px-6 rounded-full transition shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                    Дасгал хайх
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Section: Recommended -->
                    <div class="bg-white rounded-3xl p-8 shadow-sm border border-gray-100">
                        <h2 class="text-2xl font-extrabold text-gray-800 mb-6 font-title flex items-center gap-2">
                            <span class="bg-pink-100 text-pink-500 p-2 rounded-lg text-xl"><i class="fas fa-thumbs-up"></i></span> 
                            Танд санал болгох
                        </h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?php foreach($recommended_items as $item): ?>
                                <a href="material.php?id=<?php echo $item['id']; ?>" class="flex items-center gap-4 p-4 rounded-2xl bg-gray-50 border border-gray-100 hover:border-<?php echo $item['color_theme']; ?>-300 hover:bg-<?php echo $item['color_theme']; ?>-50 transition group cursor-pointer">
                                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition shadow-sm border border-gray-100 text-<?php echo $item['color_theme']; ?>-400">
                                        <i class="<?php echo $item['icon_class']; ?>"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-gray-800 group-hover:text-<?php echo $item['color_theme']; ?>-600 transition text-sm md:text-base truncate"><?php echo htmlspecialchars($item['title']); ?></h4>
                                        <p class="text-xs text-gray-500 font-bold uppercase mt-1 tracking-wide"><?php echo htmlspecialchars($item['cat_name']); ?></p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Banner / Promo -->
                <div class="space-y-6">
                    <!-- Welcome/Start Banner -->
                    <div class="bg-gradient-to-br from-[#33cbcc] to-teal-600 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden group">
                        <div class="absolute -right-6 -bottom-6 text-white opacity-10 group-hover:opacity-20 transition transform group-hover:scale-110 duration-500"><i class="fas fa-rocket fa-8x"></i></div>
                        
                        <div class="relative z-10">
                            <h3 class="text-2xl font-extrabold mb-2 font-title">Шинэ зүйл сурахад бэлэн үү?</h3>
                            <p class="text-teal-50 mb-6 font-medium">500 гаруй сонирхолтой дасгалууд таныг хүлээж байна.</p>
                            <a href="browse.php" class="block w-full bg-white text-[#33cbcc] text-center font-bold py-3 rounded-xl hover:bg-teal-50 transition shadow-md">
                                Эхлэх
                            </a>
                        </div>
                    </div>

                    <?php if(!$is_premium): ?>
                    <!-- Premium Promo (Show only if NOT premium) -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border-2 border-dashed border-yellow-300 text-center relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-yellow-300 via-yellow-500 to-yellow-300"></div>
                        <div class="w-14 h-14 bg-yellow-100 text-yellow-500 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl shadow-sm">
                            <i class="fas fa-crown"></i>
                        </div>
                        <h3 class="font-bold text-gray-800 mb-2 text-lg">Premium эрхтэй юу?</h3>
                        <p class="text-sm text-gray-500 mb-4 px-2">Ердөө 1 удаа төлөөд насан туршдаа бүх материалыг татах эрхтэй.</p>
                        <a href="premium.php" class="text-yellow-600 font-bold hover:text-yellow-700 hover:underline text-sm flex items-center justify-center gap-1">
                            Дэлгэрэнгүй <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($current_tab == 'downloads'): ?>
            <!-- DOWNLOADS CONTENT -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 min-h-[400px] p-8">
                <h2 class="text-2xl font-extrabold text-gray-800 mb-8 font-title flex items-center gap-3 border-b border-gray-100 pb-4">
                    <span class="bg-purple-100 text-purple-500 p-2 rounded-lg"><i class="fas fa-download"></i></span> Татсан материалууд
                </h2>
                
                <?php if(empty($my_downloads)): ?>
                    <!-- Empty State -->
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="w-40 h-40 bg-gray-50 rounded-full flex items-center justify-center mb-6 relative">
                            <i class="fas fa-file-download text-6xl text-gray-300"></i>
                            <div class="absolute bottom-2 right-2 bg-white p-2 rounded-full shadow-sm">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-700 mb-2 font-title">Хоосон байна</h3>
                        <p class="text-gray-500 max-w-md mb-8">Та одоогоор ямар нэгэн материал татаж аваагүй байна. Дасгалуудаа татаж аваад эндээс хялбар олоорой.</p>
                        <a href="browse.php" class="bg-[#33cbcc] hover:bg-[#2bb5b6] text-white px-8 py-3 rounded-full font-bold shadow-md transition transform hover:-translate-y-1 flex items-center gap-2">
                            <i class="fas fa-search"></i> Материал хайх
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Downloads Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach($my_downloads as $item): ?>
                            <div class="border border-gray-200 rounded-2xl p-4 hover:shadow-md transition bg-white flex flex-col h-full">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-14 h-14 bg-<?php echo $item['color_theme']; ?>-100 text-<?php echo $item['color_theme']; ?>-500 rounded-xl flex items-center justify-center text-2xl">
                                        <i class="<?php echo $item['icon_class']; ?>"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800 line-clamp-1"><?php echo htmlspecialchars($item['title']); ?></h4>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($item['cat_name']); ?></p>
                                    </div>
                                </div>
                                <div class="mt-auto pt-4 border-t border-gray-50 flex justify-between items-center">
                                    <span class="text-xs text-gray-400 font-medium">
                                        <i class="far fa-clock"></i> <?php echo date('Y-m-d', strtotime($item['downloaded_at'])); ?>
                                    </span>
                                    <a href="material.php?id=<?php echo $item['id']; ?>" class="text-[#33cbcc] text-sm font-bold hover:underline">
                                        Дахин татах
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($current_tab == 'saved'): ?>
            <!-- SAVED CONTENT -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 min-h-[400px] p-8">
                <h2 class="text-2xl font-extrabold text-gray-800 mb-8 font-title flex items-center gap-3 border-b border-gray-100 pb-4">
                    <span class="bg-pink-100 text-pink-500 p-2 rounded-lg"><i class="fas fa-heart"></i></span> Хадгалсан материалууд
                </h2>

                <?php if(empty($my_saved)): ?>
                    <!-- Empty State -->
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="w-40 h-40 bg-pink-50 rounded-full flex items-center justify-center mb-6 relative">
                            <i class="far fa-heart text-6xl text-pink-200"></i>
                            <div class="absolute top-0 right-0 animate-bounce">
                                <i class="fas fa-heart text-pink-400 text-xl"></i>
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-700 mb-2 font-title">Хадгалсан зүйл алга</h3>
                        <p class="text-gray-500 max-w-md mb-8">Таалагдсан дасгалаа зүрхэн дээр дарж хадгалаарай. Дараа нь хэзээ ч хамаагүй хурдан олох боломжтой.</p>
                        <a href="browse.php" class="bg-pink-500 hover:bg-pink-600 text-white px-8 py-3 rounded-full font-bold shadow-md transition transform hover:-translate-y-1 flex items-center gap-2">
                            <i class="fas fa-compass"></i> Хайж эхлэх
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Saved Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach($my_saved as $item): ?>
                            <div class="border border-gray-200 rounded-2xl p-4 hover:shadow-md transition bg-white flex flex-col h-full relative group">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-14 h-14 bg-<?php echo $item['color_theme']; ?>-100 text-<?php echo $item['color_theme']; ?>-500 rounded-xl flex items-center justify-center text-2xl">
                                        <i class="<?php echo $item['icon_class']; ?>"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800 line-clamp-1"><?php echo htmlspecialchars($item['title']); ?></h4>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($item['cat_name']); ?></p>
                                    </div>
                                </div>
                                <div class="mt-auto pt-4 border-t border-gray-50 flex justify-between items-center">
                                    <span class="text-xs text-gray-400 font-medium">
                                        <i class="far fa-calendar-alt"></i> <?php echo date('Y-m-d', strtotime($item['saved_at'])); ?>
                                    </span>
                                    <a href="material.php?id=<?php echo $item['id']; ?>" class="text-pink-500 text-sm font-bold hover:underline">
                                        Үзэх
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php 
// Include Kids footer
include 'footer.php'; 
?>