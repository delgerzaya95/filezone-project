<?php 
// Database connection
require_once 'includes/db.php';

// Хуудасны гарчиг
$page_title = "Нүүр хуудас - Filezone.mn";

// Header оруулах
include 'includes/header.php'; 

// --- HELPER FUNCTIONS ---

// Файлын төрлөөс хамаарч загвар авах
function getFileStyle($type) {
    switch ($type) {
        case 'pdf': return ['color' => 'red', 'icon' => 'fas fa-file-pdf', 'bg' => 'bg-red-50', 'text' => 'text-red-600'];
        case 'docx': 
        case 'doc': return ['color' => 'blue', 'icon' => 'fas fa-file-word', 'bg' => 'bg-blue-50', 'text' => 'text-blue-600'];
        case 'xlsx': 
        case 'xls': return ['color' => 'green', 'icon' => 'fas fa-file-excel', 'bg' => 'bg-green-50', 'text' => 'text-green-600'];
        case 'pptx': 
        case 'ppt': return ['color' => 'orange', 'icon' => 'fas fa-file-powerpoint', 'bg' => 'bg-orange-50', 'text' => 'text-orange-600'];
        case 'zip': 
        case 'rar': return ['color' => 'yellow', 'icon' => 'fas fa-file-archive', 'bg' => 'bg-yellow-50', 'text' => 'text-yellow-600'];
        default: return ['color' => 'gray', 'icon' => 'fas fa-file-alt', 'bg' => 'bg-gray-50', 'text' => 'text-gray-600'];
    }
}

// --- DATA FETCHING ---

// 1. Ангиллуудыг татах (LIMIT хассан - Бүгдийг дуудна)
$cat_stmt = $pdo->prepare("SELECT * FROM categories WHERE type = 'file' ORDER BY id ASC");
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Өнгөний сонголтууд (Visual effect for categories)
$category_colors = ['blue', 'green', 'purple', 'orange', 'red', 'teal', 'indigo', 'pink'];

// 2. Санал болгож буй үйлчилгээ (Featured Services)
$serv_stmt = $pdo->prepare("
    SELECT s.*, u.username, u.avatar_url, 
    (SELECT AVG(rating) FROM service_reviews WHERE service_id = s.id) as avg_rating
    FROM services s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.status = 'active' 
    ORDER BY s.rating_avg DESC, s.created_at DESC 
    LIMIT 6
");
$serv_stmt->execute();
$featured_services = $serv_stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Эрэлттэй файлууд (Trending)
// file_previews хүснэгтээс preview_url-ийг subquery ашиглан татаж байна
$trend_stmt = $pdo->prepare("
    SELECT f.*, u.username, u.avatar_url,
    (SELECT preview_url FROM file_previews WHERE file_id = f.id ORDER BY order_index ASC LIMIT 1) as preview_image
    FROM files f 
    JOIN users u ON f.user_id = u.id 
    WHERE f.status = 'approved' 
    ORDER BY RAND() 
    LIMIT 3
");
$trend_stmt->execute();
$trending_files = $trend_stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Шинэ файлууд (New Files)
// file_previews хүснэгтээс preview_url-ийг subquery ашиглан татаж байна
$new_stmt = $pdo->prepare("
    SELECT f.*, u.username, u.avatar_url,
    (SELECT preview_url FROM file_previews WHERE file_id = f.id ORDER BY order_index ASC LIMIT 1) as preview_image
    FROM files f 
    JOIN users u ON f.user_id = u.id 
    WHERE f.status = 'approved' 
    ORDER BY f.upload_date DESC 
    LIMIT 8
");
$new_stmt->execute();
$new_files = $new_stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Хамгийн их татагдсан (Most Downloaded)
// file_previews хүснэгтээс preview_url-ийг subquery ашиглан татаж байна
$top_stmt = $pdo->prepare("
    SELECT f.*, u.username, u.avatar_url,
    (SELECT preview_url FROM file_previews WHERE file_id = f.id ORDER BY order_index ASC LIMIT 1) as preview_image
    FROM files f 
    JOIN users u ON f.user_id = u.id 
    WHERE f.status = 'approved' 
    ORDER BY f.download_count DESC 
    LIMIT 6
");
$top_stmt->execute();
$most_downloaded_files = $top_stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <?php include 'includes/sidebar.php' ?>

    <!-- Main Content -->
    <main class="flex-1 py-6 min-w-0">
        
        <!-- ============================================== -->
        <!-- START: BETA NOTIFICATION BANNER -->
        <!-- ============================================== -->
        <div id="beta-notification" class="mx-4 lg:mx-0 mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 relative shadow-sm rounded-r-lg">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-hammer text-yellow-500 text-xl mt-0.5"></i>
                </div>
                <div class="ml-3 pr-8">
                    <h3 class="text-sm font-bold text-yellow-800 uppercase tracking-wide">
                        Туршилтын хувилбар (Beta)
                    </h3>
                    <div class="mt-1 text-sm text-yellow-700 leading-relaxed">
                        <p>
                            Энэхүү веб сайт нь одоогоор хөгжүүлэлтийн шатанд явж байгаа бөгөөд <strong>удахгүй бүрэн ашиглалтанд орно.</strong> 
                            Та бүртгүүлж, системтэй танилцах боломжтой ч зарим нэг алдаа гарч болзошгүйг анхаарна уу. 
                            <strong>Анхааруулга: Энд одоогоор байгаа контентууд бодит биш тест файл шүү.</strong>
                            Бид системийг 100% найдвартай болгохоор ажиллаж байна.
                        </p>
                    </div>
                </div>
                <div class="absolute top-0 right-0 pt-3 pr-3">
                    <button type="button" onclick="document.getElementById('beta-notification').remove()" class="inline-flex rounded-md p-1.5 text-yellow-500 hover:bg-yellow-100 focus:outline-none transition-colors">
                        <span class="sr-only">Хаах</span>
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Hero Section -->
        <div class="relative w-full mb-8 overflow-hidden rounded-2xl bg-indigo-900 text-white shadow-xl mx-4 lg:mx-0">
            <div class="absolute inset-0 w-full h-full bg-gradient-to-br from-indigo-900 via-purple-900 to-blue-900"></div>
            
            <!-- Motion Graphics -->
            <div class="absolute top-0 -left-4 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
            <div class="absolute top-0 -right-4 w-72 h-72 bg-yellow-500 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000" style="animation-delay: 2s;"></div>
            <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000" style="animation-delay: 4s;"></div>
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px;"></div>

            <div class="relative z-10 px-6 py-10 md:py-12 text-center">
                <span class="inline-block py-1 px-3 rounded-full bg-white/10 text-xs font-semibold mb-3 border border-white/20 backdrop-blur-sm shadow-sm">
                    🚀 Filezone 2.0
                </span>
                
                <h1 class="text-3xl md:text-4xl font-bold mb-3 leading-tight drop-shadow-md">
                    Бүгдийг нэг дороос.
                </h1>
                
                <p class="text-indigo-100 mb-6 max-w-xl mx-auto text-sm md:text-base opacity-90">
                    12,000+ бэлэн файл татах, эсвэл мэргэжлийн хүмүүсээр хүссэн ажлаа хийлгэх боломжтой Монголын хамгийн том дижитал платформ.
                </p>
                
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="browse-files.php" class="bg-white text-indigo-900 px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-indigo-50 transition shadow-lg flex items-center transform hover:-translate-y-0.5">
                        <i class="fas fa-search mr-2"></i> Хайх
                    </a>
                    <a href="upload.php" class="bg-gradient-to-r from-orange-500 to-red-500 text-white border border-transparent px-6 py-2.5 rounded-xl font-bold text-sm transition shadow-lg shadow-orange-500/30 flex items-center transform hover:-translate-y-0.5 btn-shine">
                        <i class="fas fa-plus mr-2"></i> Файл зарах
                    </a>
                </div>
            </div>
        </div>

        <!-- Categories -->
        <div class="mx-4 lg:mx-0 mb-10">
            <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Төрлөөр хайх</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $index => $cat): ?>
                        <?php 
                            // Check if category is "forkids"
                            if ($cat['slug'] === 'forkids'):
                        ?>
                            <!-- ХҮҮХДИЙН БУЛАН (Тусгай загвар) -->
                            <a href="kids/index.php" class="bg-white p-4 rounded-xl border border-pink-100 shadow-sm hover:shadow-md hover:border-pink-200 transition text-center group h-full flex flex-col justify-center items-center relative overflow-hidden">
                                <div class="absolute inset-0 bg-pink-50 opacity-50 z-0"></div>
                                <div class="relative z-10">
                                    <div class="w-10 h-10 mx-auto bg-white rounded-full flex items-center justify-center text-pink-500 mb-2 group-hover:scale-110 transition shadow-sm">
                                        <i class="fas fa-child text-lg"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-700 line-clamp-2 leading-tight"><?php echo htmlspecialchars($cat['name']); ?></span>
                                </div>
                            </a>
                        <?php else: ?>
                            <?php 
                                $color = $category_colors[$index % count($category_colors)];
                            ?>
                            <!-- Энгийн ангилал -->
                            <a href="browse-files.php?cat=<?php echo htmlspecialchars($cat['slug']); ?>" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:border-<?php echo $color; ?>-200 transition text-center group h-full flex flex-col justify-center items-center">
                                <div class="w-10 h-10 mx-auto bg-<?php echo $color; ?>-50 rounded-full flex items-center justify-center text-<?php echo $color; ?>-600 mb-2 group-hover:scale-110 transition">
                                    <i class="<?php echo htmlspecialchars($cat['icon_class'] ?? 'fas fa-folder'); ?> text-lg"></i>
                                </div>
                                <span class="text-xs font-bold text-gray-700 line-clamp-2 leading-tight"><?php echo htmlspecialchars($cat['name']); ?></span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <a href="categories.php" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:border-gray-300 transition text-center group h-full flex flex-col justify-center items-center">
                    <div class="w-10 h-10 mx-auto bg-gray-100 rounded-full flex items-center justify-center text-gray-600 mb-2 group-hover:scale-110 transition">
                        <i class="fas fa-th-large text-lg"></i>
                    </div>
                    <span class="text-xs font-bold text-gray-700">Бүгдийг харах</span>
                </a>

            </div>
        </div>

        <!-- FEATURED SERVICES -->
        <?php if (!empty($featured_services)): ?>
        <div class="mb-10 mx-4 lg:mx-0">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span class="w-1.5 h-6 bg-purple-600 rounded-full"></span>
                    Санал болгож буй үйлчилгээ
                </h2>
                <a href="services.php" class="text-sm font-medium text-brand-600 hover:text-brand-700">Бүгд &rarr;</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <?php foreach($featured_services as $srv): ?>
                    <?php 
                        $image = !empty($srv['cover_image']) ? htmlspecialchars($srv['cover_image']) : 'assets/images/service-placeholder.jpg';
                        $avatar = !empty($srv['avatar_url']) ? htmlspecialchars($srv['avatar_url']) : 'assets/images/default-avatar.png';
                        
                        $unit_map = ['hour' => 'цаг', 'day' => 'хоног', 'week' => 'долоо хоног', 'month' => 'сар'];
                        $time_text = $srv['delivery_time'] . ' ' . ($unit_map[$srv['delivery_unit']] ?? $srv['delivery_unit']);
                        $price_display = number_format($srv['price_min']) . '₮';
                    ?>
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group service-card">
                        <div class="h-36 bg-gray-200 relative overflow-hidden">
                            <img src="<?php echo $image; ?>" alt="Service" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded text-xs font-bold text-gray-700 shadow-sm">
                                <i class="fas fa-clock text-gray-400 text-[10px] mr-1"></i> <?php echo $time_text; ?>
                            </div>
                        </div>
                        <div class="p-4 pt-2 relative">
                            <div class="flex justify-between items-start">
                                <!-- Avatar Display -->
                                <img src="<?php echo $avatar; ?>" class="w-10 h-10 rounded-full border-2 border-white bg-gray-100 -mt-7 object-cover shadow-sm">
                                <div class="text-right">
                                    <span class="block text-green-600 font-bold text-lg"><?php echo $price_display; ?></span>
                                </div>
                            </div>
                            <h3 class="font-bold text-gray-800 mt-2 line-clamp-2 text-sm h-10 leading-relaxed group-hover:text-brand-600 transition-colors">
                                <a href="service-details.php?id=<?php echo $srv['id']; ?>">
                                    <?php echo htmlspecialchars($srv['title']); ?>
                                </a>
                            </h3>
                            <div class="service-action absolute inset-x-0 bottom-0 p-4 bg-white/95 backdrop-blur opacity-0 translate-y-2 transition-all duration-300 flex items-center justify-center">
                                <a href="service-details.php?id=<?php echo $srv['id']; ?>" class="w-full bg-brand-600 text-white text-center py-2 rounded-lg font-medium text-sm hover:bg-brand-700 shadow-lg shadow-brand-500/20">
                                    Дэлгэрэнгүй
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Add Service Promo -->
                <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center text-center p-6 hover:border-brand-500 hover:bg-brand-50 transition cursor-pointer group h-full min-h-[250px]">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mb-4 shadow-sm group-hover:scale-110 transition duration-300">
                        <i class="fas fa-plus text-2xl text-brand-600"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-2 text-lg">Үйлчилгээ нэмэх</h3>
                    <p class="text-sm text-gray-500 mb-6 px-4">Та өөрийн чадвараа ашиглан орлого олоорой. Бүртгүүлэхэд үнэгүй.</p>
                    <a href="add_service.php" class="px-6 py-2 bg-brand-600 text-white text-sm font-bold rounded-lg hover:bg-brand-700 transition shadow-lg shadow-brand-500/20">
                        Эхлэх
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- SECTION 1: TRENDING FILES (Эрэлттэй) -->
        <?php if (!empty($trending_files)): ?>
        <div class="mx-4 lg:mx-0 mb-10">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-star text-yellow-400"></i>
                    Эрэлттэй файлууд
                </h2>
                <a href="browse-files.php?sort=popular" class="text-sm font-medium text-brand-600 hover:text-brand-700">Бүгд &rarr;</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($trending_files as $file): ?>
                    <?php 
                        $style = getFileStyle($file['file_type']);
                        $price_display = $file['price'] == 0 ? 'Үнэгүй' : number_format($file['price']) . '₮';
                        $user_avatar = !empty($file['avatar_url']) ? htmlspecialchars($file['avatar_url']) : 'assets/images/default-avatar.png';
                        // PREVIEW IMAGE CHECK
                        $has_preview = !empty($file['preview_image']); 
                        $preview_url = $has_preview ? htmlspecialchars($file['preview_image']) : '';
                    ?>
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 group overflow-hidden flex flex-col h-full">
                        <!-- Preview Section -->
                        <div class="h-40 w-full relative bg-gray-50 flex items-center justify-center overflow-hidden">
                            <?php if($has_preview): ?>
                                <img src="<?php echo $preview_url; ?>" alt="Preview" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <div class="absolute inset-0 bg-black/10 group-hover:bg-black/0 transition"></div>
                            <?php else: ?>
                                <div class="w-16 h-16 <?php echo $style['bg'] . ' ' . $style['text']; ?> rounded-2xl flex items-center justify-center shadow-inner">
                                    <i class="<?php echo $style['icon']; ?> text-3xl"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Type Badge -->
                            <div class="absolute top-3 right-3">
                                <span class="bg-white/90 backdrop-blur text-gray-800 text-xs font-bold px-2.5 py-1 rounded-lg shadow-sm">
                                    <?php echo htmlspecialchars($file['file_type']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-4 flex-1 flex flex-col">
                            <h3 class="font-bold text-gray-900 mb-2 line-clamp-2 text-base leading-snug group-hover:text-brand-600 transition-colors">
                                <a href="file-details.php?id=<?php echo $file['id']; ?>"><?php echo htmlspecialchars($file['title']); ?></a>
                            </h3>
                            
                            <div class="flex items-center gap-2 mb-4 mt-auto">
                                <img src="<?php echo $user_avatar; ?>" class="w-6 h-6 rounded-full object-cover border border-gray-200" alt="User">
                                <span class="text-xs text-gray-500 font-medium truncate"><?php echo htmlspecialchars($file['username']); ?></span>
                            </div>

                            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                <span class="text-sm font-bold text-brand-600 bg-brand-50 px-2 py-1 rounded">
                                    <?php echo $price_display; ?>
                                </span>
                                <div class="flex items-center text-xs text-gray-400 gap-3">
                                    <span title="Татагдсан"><i class="fas fa-download mr-1"></i> <?php echo number_format($file['download_count']); ?></span>
                                    <span title="Үзсэн"><i class="fas fa-eye mr-1"></i> <?php echo number_format($file['view_count'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- SECTION 2: MOST DOWNLOADED (Хамгийн их татагдсан) -->
        <?php if (!empty($most_downloaded_files)): ?>
        <div class="mx-4 lg:mx-0 mb-10">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-fire text-orange-500"></i>
                    Хамгийн их татагдсан
                </h2>
                <a href="browse-files.php?sort=popular" class="text-sm font-medium text-brand-600 hover:text-brand-700">Бүгд &rarr;</a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach($most_downloaded_files as $index => $file): ?>
                    <?php 
                        $style = getFileStyle($file['file_type']);
                        $rank = $index + 1;
                        $rankColor = $index == 0 ? 'bg-orange-500' : ($index == 1 ? 'bg-gray-400' : ($index == 2 ? 'bg-yellow-600' : 'bg-gray-300'));
                        $price_display = $file['price'] == 0 ? 'Үнэгүй' : number_format($file['price']) . '₮';
                        $user_avatar = !empty($file['avatar_url']) ? htmlspecialchars($file['avatar_url']) : 'assets/images/default-avatar.png';
                        // Preview Check
                        $has_preview = !empty($file['preview_image']); 
                        $preview_url = $has_preview ? htmlspecialchars($file['preview_image']) : '';
                    ?>
                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm relative hover:shadow-md transition group">
                         <div class="absolute top-0 right-0 <?php echo $rankColor; ?> text-white text-[10px] font-bold px-2 py-1 rounded-bl-lg z-10">TOP <?php echo $rank; ?></div>
                        
                        <div class="flex gap-4 items-start">
                            <!-- Image or Icon -->
                            <div class="w-16 h-16 flex-shrink-0 rounded-lg overflow-hidden bg-gray-50 flex items-center justify-center border border-gray-100 relative">
                                <?php if($has_preview): ?>
                                    <img src="<?php echo $preview_url; ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="<?php echo $style['bg'] . ' ' . $style['text']; ?> w-full h-full flex items-center justify-center">
                                        <i class="<?php echo $style['icon']; ?> text-2xl"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-gray-800 text-sm line-clamp-1 group-hover:text-brand-600 mb-1">
                                    <a href="file-details.php?id=<?php echo $file['id']; ?>"><?php echo htmlspecialchars($file['title']); ?></a>
                                </h3>
                                
                                <div class="flex items-center gap-1.5 mb-2">
                                    <img src="<?php echo $user_avatar; ?>" class="w-4 h-4 rounded-full">
                                    <span class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($file['username']); ?></span>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-brand-600 font-bold text-sm"><?php echo $price_display; ?></span>
                                    <span class="text-xs text-gray-400"><i class="fas fa-download"></i> <?php echo number_format($file['download_count']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- SECTION 3: NEW FILES (Шинэ) -->
        <?php if (!empty($new_files)): ?>
        <div class="mx-4 lg:mx-0 mb-10">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span class="w-1.5 h-6 bg-brand-600 rounded-full"></span>
                    Шинэ файлууд
                </h2>
                <a href="browse-files.php" class="text-sm font-medium text-brand-600 hover:text-brand-700">Бүгд &rarr;</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach($new_files as $file): ?>
                    <?php 
                        $style = getFileStyle($file['file_type']);
                        $price_display = $file['price'] == 0 ? 'Үнэгүй' : number_format($file['price']) . '₮';
                        $price_class = $file['price'] == 0 ? 'text-green-600' : '';
                        $user_avatar = !empty($file['avatar_url']) ? htmlspecialchars($file['avatar_url']) : 'assets/images/default-avatar.png';
                        // Preview Check
                        $has_preview = !empty($file['preview_image']); 
                        $preview_url = $has_preview ? htmlspecialchars($file['preview_image']) : '';
                    ?>
                    <div class="bg-white rounded-xl border border-gray-200 p-0 overflow-hidden hover:shadow-md transition-shadow group relative flex flex-col h-full">
                        <!-- Preview Top (Small) -->
                        <div class="h-24 w-full bg-gray-50 flex items-center justify-center overflow-hidden relative border-b border-gray-100">
                             <?php if($has_preview): ?>
                                <img src="<?php echo $preview_url; ?>" class="w-full h-full object-cover group-hover:scale-105 transition">
                            <?php else: ?>
                                <div class="<?php echo $style['bg'] . ' ' . $style['text']; ?> w-10 h-10 rounded-lg flex items-center justify-center">
                                    <i class="<?php echo $style['icon']; ?> text-xl"></i>
                                </div>
                            <?php endif; ?>
                            <span class="absolute top-2 right-2 bg-white/80 backdrop-blur text-gray-600 text-[10px] font-bold px-1.5 py-0.5 rounded uppercase border border-white">
                                <?php echo htmlspecialchars($file['file_type']); ?>
                            </span>
                        </div>

                        <div class="p-3 flex-1 flex flex-col">
                            <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                                <a href="file-details.php?id=<?php echo $file['id']; ?>">
                                    <?php echo htmlspecialchars($file['title']); ?>
                                </a>
                            </h3>
                            
                            <div class="flex items-center gap-2 text-xs text-gray-500 mb-3 mt-auto">
                                <img src="<?php echo $user_avatar; ?>" class="w-5 h-5 rounded-full object-cover border border-gray-200" alt="User">
                                <span class="truncate max-w-[80px]"><?php echo htmlspecialchars($file['username']); ?></span>
                            </div>

                            <div class="flex items-center justify-between pt-2 border-t border-gray-50">
                                <span class="font-bold text-gray-900 text-sm <?php echo $price_class; ?>">
                                    <?php echo $price_display; ?>
                                </span>
                                <span class="text-xs text-gray-400"><i class="fas fa-download mr-0.5"></i> <?php echo $file['download_count']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Featured Collections (Static Banner) -->
        <div class="mx-4 lg:mx-0 mb-16">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 text-white shadow-2xl p-8 md:p-12">
                <div class="absolute top-0 right-0 w-64 h-64 bg-brand-500 rounded-full blur-[100px] opacity-20"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-purple-500 rounded-full blur-[100px] opacity-20"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
                    <div class="md:w-1/2 space-y-6">
                        <h2 class="text-2xl md:text-3xl font-bold leading-tight">
                            Танд зарах боломжтой файл байна уу?
                        </h2>
                        <p class="text-slate-300 text-sm md:text-base leading-relaxed">
                            Компьютерт тань хэрэггүй хэвтэж байгаа файлуудаа (бие даалт, диплом, тайлан, загвар) бусдад хэрэгтэй байдлаар зарж, нэмэлт орлого олох боломжтой.
                        </p>
                        <ul class="space-y-3 text-sm text-slate-300">
                            <li class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-full bg-green-500/20 text-green-400 flex items-center justify-center text-xs">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span>70-80% -ийн өндөр шимтгэл</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-full bg-green-500/20 text-green-400 flex items-center justify-center text-xs">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span>Хүссэн үедээ мөнгөө татах</span>
                            </li>
                        </ul>
                        <div class="pt-2">
                            <a href="upload.php" class="inline-flex items-center gap-2 bg-white text-slate-900 px-6 py-3 rounded-xl font-bold text-sm hover:bg-gray-100 transition shadow-lg btn-shine">
                                <i class="fas fa-cloud-upload-alt"></i> Файл оруулж эхлэх
                            </a>
                        </div>
                    </div>
                    
                    <div class="md:w-1/2 flex justify-center relative">
                        <div class="relative w-64 h-64 animate-[float_6s_ease-in-out_infinite]">
                            <div class="absolute top-0 right-4 w-48 bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-xl transform rotate-6 shadow-xl">
                                <div class="h-2 w-16 bg-white/30 rounded mb-2"></div>
                                <div class="h-16 w-full bg-white/10 rounded mb-3"></div>
                                <div class="flex justify-between items-center">
                                    <div class="h-2 w-8 bg-white/30 rounded"></div>
                                    <div class="h-4 w-12 bg-green-400/80 rounded"></div>
                                </div>
                            </div>
                            <div class="absolute top-8 left-0 w-48 bg-white p-4 rounded-xl shadow-2xl transform -rotate-3 z-10">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-500 flex items-center justify-center">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </div>
                                    <div>
                                        <div class="h-2 w-20 bg-gray-200 rounded mb-1"></div>
                                        <div class="h-2 w-12 bg-gray-100 rounded"></div>
                                    </div>
                                </div>
                                <div class="flex justify-between items-end border-t border-gray-100 pt-3">
                                    <span class="text-xs text-gray-400">Орлого</span>
                                    <span class="text-lg font-bold text-gray-800">+150,000₮</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<?php include 'includes/footer.php' ?>