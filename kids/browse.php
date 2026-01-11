<?php
// Filezone Kids - Browse Materials
// Path: filezone.mn/kids/browse.php

// 1. DB Connections
require_once __DIR__ . '/../includes/db.php'; // Main DB (Auth)
require_once __DIR__ . '/db_kids.php';        // Kids DB (Materials)

$page_title = "Материал хайх - Filezone Kids";

// Use KIDS header
include 'header.php'; 

// --- FILTER LOGIC ---
$current_cat = isset($_GET['cat']) ? $_GET['cat'] : 'all';
$current_age = isset($_GET['age']) ? $_GET['age'] : 'all';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// --- BUILD SQL QUERY ---
$sql = "SELECT m.*, 
               c.name as cat_name, 
               c.slug as cat_slug, 
               c.icon_class, 
               c.color_theme 
        FROM kids_materials m
        LEFT JOIN kids_categories c ON m.category_id = c.id
        WHERE m.status = 'active'";

$params = [];

// Filter by Category
if ($current_cat !== 'all') {
    $sql .= " AND c.slug = :cat";
    $params[':cat'] = $current_cat;
}

// Filter by Age
if ($current_age !== 'all') {
    $sql .= " AND m.target_age = :age";
    $params[':age'] = $current_age;
}

// Filter by Search
if (!empty($search_query)) {
    $sql .= " AND m.title LIKE :q";
    $params[':q'] = "%" . $search_query . "%";
}

$sql .= " ORDER BY m.created_at DESC";

try {
    $stmt = $pdo_kids->prepare($sql);
    $stmt->execute($params);
    $materials = $stmt->fetchAll();
} catch (PDOException $e) {
    $materials = [];
    // error_log($e->getMessage()); // Production дээр алдааг log руу бичнэ
}

// Helper for Age Labels (Display friendly text)
function getAgeLabel($age) {
    $labels = [
        '2-3' => '2-3 Нас',
        '3-4' => '3-4 Нас',
        '4-5' => '4-5 Нас',
        'preschool' => 'Сургуульд бэлтгэх',
        'school_prep' => 'Сургуульд бэлтгэх',
        'grade-1' => '1-р анги',
        'all' => 'Бүх нас'
    ];
    return $labels[$age] ?? $age;
}
?>

<!-- Local Styles (Header already includes fonts) -->
<style>
    .paper-shadow {
        box-shadow: 2px 4px 15px rgba(0,0,0,0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .paper-shadow:hover {
        transform: translateY(-4px);
        box-shadow: 4px 8px 20px rgba(0,0,0,0.12);
    }
    /* Active Filters */
    .filter-btn.active {
        background-color: #33cbcc;
        color: white;
        border-color: #33cbcc;
        box-shadow: 0 4px 6px -1px rgba(51, 203, 204, 0.3);
    }
    /* Scissor animation */
    @keyframes cut {
        0% { transform: translateY(0); }
        50% { transform: translateY(10px); }
        100% { transform: translateY(0); }
    }
    .scissor-anim { animation: cut 2s infinite ease-in-out; }
</style>

<div class="font-kids bg-gray-50 min-h-screen">
    
    <!-- Filters Area -->
    <div class="bg-white shadow-sm border-b border-gray-100 py-4 sticky top-[80px] z-40 transition-all">
        <div class="container mx-auto px-4">
            
            <!-- Age Filters -->
            <div class="flex flex-wrap gap-2 items-center mb-3">
                <span class="text-sm font-bold text-gray-400 mr-2 uppercase tracking-wide text-[10px]">Насаар шүүх:</span>
                <?php 
                $ages = ['all' => 'Бүгд', '2-3' => '2-3', '3-4' => '3-4', '4-5' => '4-5', 'school_prep' => 'Сургуульд бэлтгэх', 'grade-1' => '1-р анги'];
                foreach($ages as $key => $label): 
                ?>
                    <a href="?cat=<?php echo $current_cat; ?>&age=<?php echo $key; ?>&q=<?php echo urlencode($search_query); ?>" 
                       class="filter-btn px-4 py-1.5 rounded-full text-xs font-bold border border-gray-200 text-gray-600 hover:bg-gray-100 transition-all <?php echo $current_age == $key ? 'active' : ''; ?>">
                       <?php echo $label; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Category Filters -->
            <div class="flex flex-wrap gap-2 items-center">
                <span class="text-sm font-bold text-gray-400 mr-2 uppercase tracking-wide text-[10px]">Төрлөөр шүүх:</span>
                <?php 
                // Categories logic: name => slug
                $cats = ['all' => 'Бүгд', 'writing' => 'Бичих', 'math' => 'Тоо бодох', 'logic' => 'Логик', 'art' => 'Урлаг', 'cutting' => 'Хайчлах', 'game' => 'Тоглоом'];
                foreach($cats as $slug => $name): 
                ?>
                    <a href="?age=<?php echo $current_age; ?>&cat=<?php echo $slug; ?>&q=<?php echo urlencode($search_query); ?>" 
                       class="filter-btn px-4 py-1.5 rounded-full text-xs font-bold border border-gray-200 text-gray-600 hover:bg-gray-100 transition-all <?php echo $current_cat == $slug ? 'active' : ''; ?>">
                       <?php echo $name; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Search Result Message -->
            <?php if(!empty($search_query)): ?>
                <div class="mt-4 text-sm text-gray-500">
                    "<strong><?php echo htmlspecialchars($search_query); ?></strong>" илэрц: <?php echo count($materials); ?> материал олдлоо.
                    <a href="browse.php" class="text-[#33cbcc] font-bold hover:underline ml-2">Шүүлтүүрийг арилгах</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        
        <?php if(empty($materials)): ?>
            <div class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border-2 border-dashed border-gray-200">
                <div class="text-6xl mb-4 grayscale opacity-50">😕</div>
                <h3 class="text-2xl font-bold text-gray-600 mb-2 font-title">Материал олдсонгүй</h3>
                <p class="text-gray-500 mb-6">Та өөр шүүлтүүр сонгох эсвэл хайлтаа өөрчлөөд үзнэ үү.</p>
                <a href="browse.php" class="bg-[#33cbcc] text-white px-8 py-3 rounded-full font-bold shadow-lg shadow-cyan-200 hover:bg-[#2bb5b6] transition transform hover:-translate-y-1">
                    Бүх материалыг харах
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach($materials as $item): ?>
                    <?php 
                        // Data Preparation
                        $isPremium = $item['is_premium'];
                        $priceText = $isPremium ? 'Premium' : 'Үнэгүй';
                        $priceClass = $isPremium ? 'text-yellow-500' : 'text-green-500';
                        $borderClass = $isPremium ? 'border-yellow-300 ring-2 ring-yellow-100' : 'border-gray-200';
                        
                        // Image Path Logic
                        $imgSrc = !empty($item['cover_image']) ? '../' . $item['cover_image'] : null;
                        
                        // Styles based on DB
                        $color = $item['color_theme'] ?? 'gray';
                        $iconClass = $item['icon_class'] ?? 'fas fa-file';
                        
                        // Dynamic Backgrounds based on category for placeholder
                        $bgClass = 'bg-' . $color . '-50';
                    ?>

                    <a href="material.php?id=<?php echo $item['id']; ?>" class="paper-shadow bg-white rounded-xl overflow-hidden border <?php echo $borderClass; ?> group block relative flex flex-col h-full hover:border-<?php echo $color; ?>-300 transition-colors">
                        
                        <?php if($isPremium): ?>
                            <div class="absolute top-2 right-2 bg-yellow-400 text-white text-[9px] font-bold px-2 py-0.5 rounded shadow-sm z-10 font-title tracking-wide">
                                <i class="fas fa-crown mr-0.5"></i> PREMIUM
                            </div>
                        <?php endif; ?>

                        <!-- PREVIEW AREA -->
                        <div class="aspect-[210/297] bg-gray-50 relative p-4 flex flex-col items-center justify-center border-b border-gray-100 group-hover:bg-gray-100 transition-colors">
                            
                            <?php if($imgSrc && file_exists(__DIR__ . '/../' . $item['cover_image'])): ?>
                                <!-- Real Image Cover -->
                                <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="w-full h-full object-cover shadow-sm border border-gray-200 rounded-sm">
                            <?php else: ?>
                                <!-- Simulated A4 Paper Placeholder (If no image) -->
                                <div class="w-full h-full bg-white border border-gray-200 p-3 relative shadow-inner flex items-center justify-center <?php echo $bgClass; ?>">
                                    
                                    <!-- Dynamic Icon based on content -->
                                    <div class="text-center text-<?php echo $color; ?>-300">
                                        <i class="<?php echo $iconClass; ?> fa-4x mb-2 opacity-80 group-hover:scale-110 transition-transform duration-300"></i>
                                    </div>
                                    
                                    <!-- Decorative Elements based on Category -->
                                    <?php if($item['cat_slug'] == 'math'): ?>
                                        <div class="absolute top-2 left-2 text-xs font-bold text-orange-300 font-kids">1 + 2 = ?</div>
                                    <?php elseif($item['cat_slug'] == 'writing'): ?>
                                        <div class="absolute top-2 left-2 text-2xl font-hand text-green-300">A a</div>
                                        <div class="absolute bottom-4 left-0 w-full border-t border-dashed border-green-200"></div>
                                    <?php elseif($item['cat_slug'] == 'cutting'): ?>
                                        <div class="absolute bottom-2 right-2 text-blue-300 scissor-anim"><i class="fas fa-cut"></i></div>
                                        <div class="absolute w-full border-b-2 border-dashed border-blue-200 top-1/2 transform -rotate-12"></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- INFO AREA -->
                        <div class="p-3 flex-grow flex flex-col justify-between">
                            <div>
                                <div class="flex flex-wrap gap-1 mb-2">
                                    <span class="text-[10px] uppercase font-bold bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-600 px-2 py-0.5 rounded">
                                        <?php echo htmlspecialchars($item['cat_name']); ?>
                                    </span>
                                    <span class="text-[10px] font-bold bg-gray-100 text-gray-500 px-2 py-0.5 rounded">
                                        <?php echo getAgeLabel($item['target_age']); ?>
                                    </span>
                                </div>
                                <h3 class="font-bold text-gray-800 text-sm md:text-base leading-tight mb-2 group-hover:text-[#33cbcc] transition font-title line-clamp-2">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </h3>
                            </div>
                            
                            <!-- Updated Footer Section: Matches index.php style -->
                            <div class="flex justify-between items-center mt-auto pt-2 border-t border-gray-50">
                                <span class="<?php echo $priceClass; ?> font-bold text-xs">
                                    <?php if($isPremium): ?><i class="fas fa-crown text-[10px] mr-0.5"></i><?php endif; ?>
                                    <?php echo $priceText; ?>
                                </span>
                                <span class="text-gray-400 text-xs font-semibold flex items-center gap-1">
                                    <i class="fas fa-download text-[10px]"></i> <?php echo number_format($item['download_count']); ?>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>