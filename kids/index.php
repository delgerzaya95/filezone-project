<?php
// Filezone Kids - Main Page
// Path: filezone.mn/kids/index.php

// DB Connection & Session start (Included in header, but require db logic here if needed before header)
require_once __DIR__ . '/../includes/db.php'; // Main DB for Auth
require_once __DIR__ . '/db_kids.php';        // Kids DB for Materials

$page_title = "Filezone Kids - Хүүхдийн хөгжил, хэвлэх материалууд";

// Include KIDS specific header
include 'header.php'; 

// --- HELPER FUNCTION: Map Category Slug to Style ---
function getKidCatStyle($slug) {
    $styles = [
        'math'    => ['class' => 'cat-card-math', 'icon' => '🧮', 'name' => 'Тоо бодох', 'color' => 'text-orange-500', 'bg' => 'bg-orange-50'],
        'writing' => ['class' => 'cat-card-write', 'icon' => '✏️', 'name' => 'Бичих', 'color' => 'text-green-500', 'bg' => 'bg-green-50'],
        'logic'   => ['class' => 'cat-card-logic', 'icon' => '🧩', 'name' => 'Логик', 'color' => 'text-indigo-500', 'bg' => 'bg-indigo-50'],
        'art'     => ['class' => 'cat-card-art', 'icon' => '🎨', 'name' => 'Урлаг', 'color' => 'text-pink-500', 'bg' => 'bg-pink-50'],
        'cutting' => ['class' => 'cat-card-cut', 'icon' => '✂️', 'name' => 'Хайчлах', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50'],
        'game'    => ['class' => 'cat-card-game', 'icon' => '🎲', 'name' => 'Тоглоом', 'color' => 'text-purple-500', 'bg' => 'bg-purple-50'],
    ];
    return $styles[$slug] ?? ['class' => 'cat-card-math', 'icon' => '📄', 'name' => 'Бусад', 'color' => 'text-gray-500', 'bg' => 'bg-gray-50'];
}

// --- FETCH LATEST MATERIALS ---
try {
    // JOIN with categories table to get the slug and name
    // ШИНЭЧЛЭЛ: category_id ашиглан kids_categories хүснэгтээс slug-ийг авч байна
    $stmt = $pdo_kids->prepare("
        SELECT m.*, c.slug as category_slug, c.name as category_name 
        FROM kids_materials m
        LEFT JOIN kids_categories c ON m.category_id = c.id
        WHERE m.status = 'active' 
        ORDER BY m.created_at DESC 
        LIMIT 4
    ");
    $stmt->execute();
    $latest_materials = $stmt->fetchAll();
} catch (PDOException $e) {
    $latest_materials = [];
    error_log("Error fetching kids materials: " . $e->getMessage());
}
?>

<!-- Kids Styles (Additional local styles) -->
<style>
    .bg-clouds {
        background-color: #e0f7fa;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .cat-card-math { background: #FFF3E0; border-color: #FFE0B2; color: #FF9800; }
    .cat-card-write { background: #E8F5E9; border-color: #C8E6C9; color: #4CAF50; }
    .cat-card-logic { background: #E8EAF6; border-color: #C5CAE9; color: #3F51B5; }
    .cat-card-art { background: #FCE4EC; border-color: #F8BBD0; color: #E91E63; }
    .cat-card-game { background: #F3E5F5; border-color: #E1BEE7; color: #9C27B0; }
    .cat-card-cut { background: #E3F2FD; border-color: #BBDEFB; color: #2196F3; }

    .paper-shadow {
        box-shadow: 2px 4px 15px rgba(0,0,0,0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .paper-shadow:hover {
        transform: translateY(-4px);
        box-shadow: 4px 8px 20px rgba(0,0,0,0.12);
    }
    .dashed-border {
        background-image: url("data:image/svg+xml,%3csvg width='100%25' height='100%25' xmlns='http://www.w3.org/2000/svg'%3e%3crect width='100%25' height='100%25' fill='none' rx='16' ry='16' stroke='%2333CBCCFF' stroke-width='3' stroke-dasharray='10%2c 10' stroke-dashoffset='0' stroke-linecap='square'/%3e%3c/svg%3e");
        border-radius: 16px;
    }
</style>

<!-- 1. HERO SECTION -->
<div class="bg-clouds pt-12 pb-24 relative overflow-hidden">
    <!-- Decor -->
    <div class="absolute top-10 left-10 text-yellow-400 animate-bounce delay-1000"><i class="fas fa-star fa-2x"></i></div>
    <div class="absolute bottom-20 right-20 text-pink-400 animate-bounce"><i class="fas fa-heart fa-2x"></i></div>
    
    <div class="container mx-auto px-4 text-center relative z-10">
        <span class="inline-block py-1 px-3 rounded-full bg-white text-purple-600 text-sm font-bold mb-4 shadow-sm">🚀 2-8 насныханд зориулав</span>
        <h1 class="text-4xl md:text-6xl font-extrabold text-gray-800 font-title mb-6 leading-tight">
            Тоглох аргаар <br>
            <span class="text-[#33cbcc]">Суралцацгаая!</span>
        </h1>
        <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">
            Хүүхдийн сэтгэхүй хөгжүүлэх, гарын жижиг булчин сайжруулах, тоо бодох чадварыг нэмэгдүүлэх 500+ ажлын хуудсууд.
        </p>

        <!-- Search Form in Hero -->
        <form action="browse.php" method="GET" class="max-w-xl mx-auto bg-white p-2 rounded-full shadow-xl flex items-center border-2 border-yellow-300 transform transition focus-within:scale-105">
            <input type="text" name="q" placeholder="Хүүхдэдээ юу хайж байна вэ?" class="w-full px-6 py-3 rounded-full focus:outline-none text-gray-700 font-kids text-lg placeholder-gray-400">
            <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 text-white rounded-full p-3 px-6 shadow-md transition font-bold">
                Хайх
            </button>
        </form>
    </div>
</div>

<!-- 2. AGE QUICK LINKS (Overlapping Hero) -->
<div class="container mx-auto px-4 -mt-12 relative z-20 mb-16">
    <div class="bg-white rounded-2xl shadow-lg p-6 grid grid-cols-2 md:grid-cols-5 gap-4 text-center border border-gray-100">
        <a href="browse.php?age=2-3" class="p-3 rounded-xl bg-gray-50 hover:bg-yellow-100 transition group">
            <div class="text-2xl mb-1 group-hover:scale-110 transition">🐣</div>
            <div class="font-bold text-gray-600 group-hover:text-yellow-600">2-3 Нас</div>
        </a>
        <a href="browse.php?age=3-4" class="p-3 rounded-xl bg-gray-50 hover:bg-purple-100 transition group">
            <div class="text-2xl mb-1 group-hover:scale-110 transition">🐥</div>
            <div class="font-bold text-gray-600 group-hover:text-purple-600">3-4 Нас</div>
        </a>
        <a href="browse.php?age=4-5" class="p-3 rounded-xl bg-gray-50 hover:bg-pink-100 transition group">
            <div class="text-2xl mb-1 group-hover:scale-110 transition">🐰</div>
            <div class="font-bold text-gray-600 group-hover:text-pink-600">4-5 Нас</div>
        </a>
        <a href="browse.php?age=school_prep" class="p-3 rounded-xl bg-gray-50 hover:bg-green-100 transition group">
            <div class="text-2xl mb-1 group-hover:scale-110 transition">🎒</div>
            <div class="font-bold text-gray-600 group-hover:text-green-600">Сургуульд бэлтгэх</div>
        </a>
        <a href="browse.php?age=grade-1" class="p-3 rounded-xl bg-gray-50 hover:bg-blue-100 transition group">
            <div class="text-2xl mb-1 group-hover:scale-110 transition">🏫</div>
            <div class="font-bold text-gray-600 group-hover:text-blue-600">1-р анги</div>
        </a>
    </div>
</div>

<!-- 3. BROWSE BY SKILL (Categories) -->
<div class="container mx-auto px-4 py-8">
    <h2 class="text-3xl font-extrabold text-gray-800 font-title mb-8 text-center">
        <span class="text-yellow-400">★</span> Ур чадвараар хайх
    </h2>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
        <a href="browse.php?cat=writing" class="group">
            <div class="cat-card-write h-32 rounded-2xl border-2 flex flex-col items-center justify-center shadow-sm group-hover:shadow-md group-hover:-translate-y-1 transition">
                <span class="text-4xl mb-2">✏️</span>
                <span class="font-bold">Бичих</span>
            </div>
        </a>
        <a href="browse.php?cat=math" class="group">
            <div class="cat-card-math h-32 rounded-2xl border-2 flex flex-col items-center justify-center shadow-sm group-hover:shadow-md group-hover:-translate-y-1 transition">
                <span class="text-4xl mb-2">🧮</span>
                <span class="font-bold">Тоо бодох</span>
            </div>
        </a>
        <a href="browse.php?cat=logic" class="group">
            <div class="cat-card-logic h-32 rounded-2xl border-2 flex flex-col items-center justify-center shadow-sm group-hover:shadow-md group-hover:-translate-y-1 transition">
                <span class="text-4xl mb-2">🧩</span>
                <span class="font-bold">Логик</span>
            </div>
        </a>
        <a href="browse.php?cat=cutting" class="group">
            <div class="cat-card-cut h-32 rounded-2xl border-2 flex flex-col items-center justify-center shadow-sm group-hover:shadow-md group-hover:-translate-y-1 transition">
                <span class="text-4xl mb-2">✂️</span>
                <span class="font-bold">Хайчлах</span>
            </div>
        </a>
        <a href="browse.php?cat=art" class="group">
            <div class="cat-card-art h-32 rounded-2xl border-2 flex flex-col items-center justify-center shadow-sm group-hover:shadow-md group-hover:-translate-y-1 transition">
                <span class="text-4xl mb-2">🎨</span>
                <span class="font-bold">Урлаг</span>
            </div>
        </a>
        <a href="browse.php?cat=game" class="group">
            <div class="cat-card-game h-32 rounded-2xl border-2 flex flex-col items-center justify-center shadow-sm group-hover:shadow-md group-hover:-translate-y-1 transition">
                <span class="text-4xl mb-2">🎲</span>
                <span class="font-bold">Тоглоом</span>
            </div>
        </a>
    </div>
</div>

<!-- 4. LATEST MATERIALS (DYNAMIC) -->
<div class="container mx-auto px-4 py-16">
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Шинээр нэмэгдсэн</h2>
        <a href="browse.php" class="text-[#33cbcc] font-bold hover:underline">Бүгдийг үзэх &rarr;</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
        
        <?php if(!empty($latest_materials)): ?>
            <?php foreach($latest_materials as $item): ?>
                <?php 
                    // Get styles based on category (slug одоо JOIN-оор орж ирнэ)
                    $style = getKidCatStyle($item['category_slug']);
                    
                    // Handle image path (Relative to kids folder)
                    // If DB stores 'uploads/kids/...' we need '../uploads/kids/...'
                    $imgSrc = !empty($item['cover_image']) ? '../' . $item['cover_image'] : null;
                    
                    // Display Price or Free
                    $isPremium = $item['is_premium'];
                    $priceText = $isPremium ? 'Premium' : 'Үнэгүй';
                    $priceClass = $isPremium ? 'text-yellow-500' : 'text-green-500';
                    $borderClass = $isPremium ? 'border-yellow-300' : 'border-gray-100';
                ?>

                <a href="material.php?id=<?php echo $item['id']; ?>" class="paper-shadow bg-white rounded-xl overflow-hidden border <?php echo $borderClass; ?> group block relative flex flex-col h-full">
                    
                    <?php if($isPremium): ?>
                        <div class="absolute top-2 right-2 bg-yellow-400 text-white text-[9px] font-bold px-2 py-0.5 rounded shadow-sm z-10">
                            <i class="fas fa-crown mr-1"></i> PREMIUM
                        </div>
                    <?php endif; ?>

                    <!-- Image / Preview -->
                    <div class="aspect-[210/297] bg-gray-50 relative p-4 flex items-center justify-center">
                        <?php if($imgSrc && file_exists(__DIR__ . '/../' . $item['cover_image'])): ?>
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="w-full h-full object-cover shadow-sm border border-gray-100">
                        <?php else: ?>
                            <!-- Fallback Placeholder -->
                            <div class="w-full h-full bg-white border border-gray-200 p-2 flex flex-col items-center justify-center relative <?php echo $style['bg']; ?>">
                                <div class="text-4xl mb-2"><?php echo $style['icon']; ?></div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-widest"><?php echo htmlspecialchars($style['name']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Content -->
                    <div class="p-3 flex-grow flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm mb-1 group-hover:text-[#33cbcc] line-clamp-2 leading-tight">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </h3>
                        </div>
                        
                        <div class="flex justify-between text-xs text-gray-500 mt-2">
                            <span class="flex items-center gap-1">
                                <?php echo $style['icon']; ?> <?php echo htmlspecialchars($style['name']); ?>
                            </span>
                            <span class="<?php echo $priceClass; ?> font-bold">
                                <?php echo $priceText; ?>
                            </span>
                        </div>
                    </div>
                </a>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full text-center py-10 bg-gray-50 rounded-xl dashed-border">
                <p class="text-gray-500 font-bold">Одоогоор материал оруулаагүй байна.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- 5. PROMOTION BANNER -->
<div class="container mx-auto px-4 pb-16">
    <div class="dashed-border bg-gradient-to-r from-purple-50 to-pink-50 p-8 md:p-12 flex flex-col md:flex-row items-center justify-between gap-8">
        <div class="md:w-2/3">
            <h3 class="text-2xl md:text-3xl font-extrabold text-gray-800 font-title mb-4">Та багш эсвэл эцэг эх үү?</h3>
            <p class="text-gray-600 mb-6">
                Filezone Kids Premium эрхтэй болсноор та хүүхдийн хөгжлийн бүх материалыг хязгааргүй татаж авах боломжтой.
            </p>
            <a href="payment.php" class="inline-block bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-8 rounded-xl shadow transition">
                Premium эрх авах - 19,900₮
            </a>
        </div>
        <div class="md:w-1/3 flex justify-center">
            <div class="text-8xl animate-bounce">👑</div>
        </div>
    </div>
</div>

<?php 
// Include KIDS specific footer
include 'footer.php'; 
?>