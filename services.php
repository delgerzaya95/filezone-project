<?php 
session_start();
require_once 'includes/db.php';

// Кирилл үсэгтэй ажиллахад тохиргоо (mbstring extension байгаа эсэхийг шалгах)
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding("UTF-8");
}

// --------------------------------------------------------------------------
// 1. ТУСЛАХ ФУНКЦУУД
// --------------------------------------------------------------------------

// Хугацааны нэгжийг Монгол руу хөрвүүлэх
function deliveryUnitToMn($unit, $time) {
    switch ($unit) {
        case 'hour': return $time . ' цаг';
        case 'day': return $time . ' хоног';
        case 'week': return $time . ' долоо хоног';
        case 'month': return $time . ' сар';
        default: return $time . ' ' . $unit;
    }
}

// Санамсаргүй өнгө сонгох (Аватар байхгүй үед)
function getRandomColorClass($str) {
    $colors = [
        ['bg' => 'bg-red-100', 'text' => 'text-red-600'],
        ['bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
        ['bg' => 'bg-green-100', 'text' => 'text-green-600'],
        ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600'],
        ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600'],
        ['bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
        ['bg' => 'bg-pink-100', 'text' => 'text-pink-600'],
    ];
    // Нэрний уртаас хамаарч өнгө сонгох (Refresh хийхэд өнгө солигдохгүй байхын тулд)
    $index = strlen($str) % count($colors);
    return $colors[$index];
}

// --------------------------------------------------------------------------
// 2. ӨГӨГДӨЛ ТАТАХ
// --------------------------------------------------------------------------

// A. Үйлчилгээний ангиллуудыг татах
try {
    $cat_stmt = $pdo->query("SELECT * FROM service_categories ORDER BY id ASC");
    $service_categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $service_categories = [];
}

// B. Шүүлтүүр болон Эрэмбэлэлтийн тохиргоо
$current_cat_slug = isset($_GET['cat']) ? $_GET['cat'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12; 
$offset = ($page - 1) * $limit;

// Эрэмбэлэх нөхцөл
$order_by = "s.created_at DESC"; // Default
switch ($sort) {
    case 'oldest': $order_by = "s.created_at ASC"; break;
    case 'popular': $order_by = "s.view_count DESC"; break;
    case 'rating': $order_by = "s.rating_avg DESC"; break;
    case 'price_asc': $order_by = "s.price_min ASC"; break;
    case 'price_desc': $order_by = "s.price_min DESC"; break;
}

// C. Үндсэн Query бэлтгэх
// Нэмэлт: u.full_name баганыг нэмж татав
$sql = "SELECT s.*, u.username, u.full_name, u.avatar_url, sc.name as category_name,
        (SELECT COUNT(*) FROM service_reviews WHERE service_id = s.id) as review_count
        FROM services s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN service_categories sc ON s.category_id = sc.id
        WHERE s.status = 'active'";

$params = [];

// Ангиллаар шүүх
if ($current_cat_slug !== 'all') {
    $sql .= " AND sc.slug = :slug";
    $params[':slug'] = $current_cat_slug;
}

// Нийт тоог авах (Pagination)
$count_sql = str_replace(
    "s.*, u.username, u.full_name, u.avatar_url, sc.name as category_name,
        (SELECT COUNT(*) FROM service_reviews WHERE service_id = s.id) as review_count", 
    "COUNT(*)", 
    $sql
);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_services = $count_stmt->fetchColumn();
$total_pages = ceil($total_services / $limit);

// Жинхэнэ өгөгдлийг авах
$sql .= " ORDER BY $order_by LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Хуудасны гарчиг
$page_title = "Үйлчилгээ - Filezone.mn";
include 'includes/header.php'; 
?>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <?php include 'includes/sidebar.php' ?>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
        
        <!-- Breadcrumb -->
        <nav class="flex mb-4 text-xs text-gray-500">
            <ol class="flex items-center space-x-2">
                <li><a href="index.php" class="hover:text-brand-600">Нүүр</a></li>
                <li><span class="text-gray-300">/</span></li>
                <li class="font-medium text-gray-900">Бүх үйлчилгээ</li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    <span class="bg-gradient-to-r from-purple-600 to-brand-600 text-transparent bg-clip-text">Бүх үйлчилгээ</span>
                </h1>
                <p class="text-sm text-gray-500 mt-1">Нийт <?php echo $total_services; ?> мэргэжлийн үйлчилгээ олдлоо.</p>
            </div>
            
            <!-- Sort Dropdown -->
            <form id="sortForm" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="cat" value="<?php echo htmlspecialchars($current_cat_slug); ?>">
                
                <label for="sort" class="text-sm text-gray-500 hidden sm:block">Эрэмбэлэх:</label>
                <select name="sort" id="sort" onchange="document.getElementById('sortForm').submit()" class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-brand-500 focus:border-brand-500 block p-2.5 outline-none cursor-pointer hover:border-gray-300 transition">
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Шинэ нь эхэндээ</option>
                    <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Санал болгох</option>
                    <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Үнэлгээ өндөр</option>
                    <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Үнэ: Багаас их рүү</option>
                    <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Үнэ: Ихээс бага руу</option>
                </select>
            </form>
        </div>

        <!-- Filters (Tabs) -->
        <div class="flex flex-wrap gap-2 mb-8 overflow-x-auto no-scrollbar pb-2">
            <!-- All Button -->
            <a href="services.php?cat=all" class="px-4 py-2 text-sm font-bold rounded-full shadow-md transition-transform hover:scale-105 <?php echo $current_cat_slug == 'all' ? 'bg-brand-600 text-white shadow-brand-500/20' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'; ?>">
                Бүгд
            </a>

            <!-- Dynamic Categories -->
            <?php foreach($service_categories as $cat): ?>
                <?php $isActive = ($current_cat_slug == $cat['slug']); ?>
                <a href="services.php?cat=<?php echo $cat['slug']; ?>" class="px-4 py-2 text-sm font-medium rounded-full transition-colors whitespace-nowrap <?php echo $isActive ? 'bg-brand-50 text-brand-700 border border-brand-500' : 'text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 hover:border-gray-300'; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Services Grid -->
        <?php if(count($services) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                
                <?php foreach($services as $service): ?>
                    <?php 
                        // Data Preparation
                        $image = !empty($service['cover_image']) ? $service['cover_image'] : 'assets/images/service-placeholder.jpg';
                        
                        // User Name Logic
                        $username = !empty($service['full_name']) ? $service['full_name'] : $service['username'];
                        
                        // Avatar Logic
                        $hasAvatar = !empty($service['avatar_url']);
                        $avatarUrl = $service['avatar_url'];
                        
                        // Initials for Placeholder (Safe fallback for missing mbstring)
                        if (function_exists('mb_substr')) {
                            $initials = mb_substr($username, 0, 2);
                            $initials = mb_strtoupper($initials);
                        } else {
                            // UTF-8 aware regex split if mbstring is missing
                            preg_match_all('/./u', $username, $matches);
                            $initials = implode('', array_slice($matches[0] ?? [], 0, 2));
                            $initials = strtoupper($initials); 
                        }
                        
                        $colorClass = getRandomColorClass($username);

                        $rating = number_format($service['rating_avg'], 1);
                        $reviews = $service['review_count'];
                        $time = deliveryUnitToMn($service['delivery_unit'], $service['delivery_time']);
                        $price = number_format($service['price_min']) . '₮';
                    ?>

                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group service-card flex flex-col h-full">
                        <!-- Image & Delivery Time Badge -->
                        <div class="h-40 bg-gray-200 relative overflow-hidden flex-shrink-0">
                            <img src="<?php echo htmlspecialchars($image); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                            <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded text-xs font-bold text-gray-700 shadow-sm">
                                <i class="fas fa-clock text-gray-400 text-[10px] mr-1"></i> <?php echo $time; ?>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-4 pt-2 relative flex-grow flex flex-col">
                            <!-- Avatar & Price -->
                            <div class="flex justify-between items-start mb-2">
                                
                                <!-- Avatar or Placeholder -->
                                <div class="-mt-7">
                                    <?php if ($hasAvatar): ?>
                                        <img src="<?php echo htmlspecialchars($avatarUrl); ?>" class="w-10 h-10 rounded-full border-2 border-white bg-gray-100 object-cover shadow-sm" title="<?php echo htmlspecialchars($username); ?>">
                                    <?php else: ?>
                                        <div class="w-10 h-10 rounded-full border-2 border-white <?php echo $colorClass['bg'] . ' ' . $colorClass['text']; ?> flex items-center justify-center font-bold text-xs shadow-sm" title="<?php echo htmlspecialchars($username); ?>">
                                            <?php echo htmlspecialchars($initials); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="text-right">
                                    <span class="block text-green-600 font-bold text-lg"><?php echo $price; ?></span>
                                </div>
                            </div>

                            <!-- Title -->
                            <h3 class="font-bold text-gray-800 mt-1 line-clamp-2 text-sm leading-relaxed group-hover:text-brand-600 transition-colors flex-grow" title="<?php echo htmlspecialchars($service['title']); ?>">
                                <?php echo htmlspecialchars($service['title']); ?>
                            </h3>

                            <!-- Rating & User -->
                            <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-50 text-xs text-gray-500">
                                <div class="flex items-center gap-1" title="Үнэлгээ">
                                    <i class="fas fa-star text-yellow-400"></i>
                                    <span class="font-bold text-gray-700"><?php echo $rating; ?></span>
                                    <span class="text-gray-400">(<?php echo $reviews; ?>)</span>
                                </div>
                                <div class="flex items-center gap-1.5 group/user cursor-pointer">
                                    <span class="text-gray-400 text-[10px]">by</span>
                                    <span class="truncate max-w-[100px] font-medium text-gray-700 group-hover/user:text-brand-600 transition-colors" title="<?php echo htmlspecialchars($username); ?>">
                                        <?php echo htmlspecialchars($username); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Hover Action Button -->
                            <div class="service-action absolute inset-x-0 bottom-0 p-4 bg-white/95 backdrop-blur opacity-0 translate-y-2 transition-all duration-300 flex items-center justify-center">
                                <a href="service-details.php?id=<?php echo $service['id']; ?>" class="w-full bg-brand-600 text-white text-center py-2 rounded-lg font-medium text-sm hover:bg-brand-700 shadow-lg shadow-brand-500/20">
                                    Дэлгэрэнгүй
                                </a>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <div class="flex justify-center mt-10">
                <nav class="flex items-center gap-2">
                    <!-- Previous -->
                    <?php if($page > 1): ?>
                        <a href="?cat=<?php echo $current_cat_slug; ?>&page=<?php echo $page-1; ?>&sort=<?php echo $sort; ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </a>
                    <?php endif; ?>

                    <!-- Pages -->
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-brand-600 text-white font-medium text-sm shadow-md shadow-brand-500/30"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?cat=<?php echo $current_cat_slug; ?>&page=<?php echo $i; ?>&sort=<?php echo $sort; ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium text-sm transition"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Next -->
                    <?php if($page < $total_pages): ?>
                        <a href="?cat=<?php echo $current_cat_slug; ?>&page=<?php echo $page+1; ?>&sort=<?php echo $sort; ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Empty State -->
            <div class="text-center py-20 bg-gray-50 rounded-2xl border border-dashed border-gray-300">
                <div class="text-6xl text-gray-300 mb-4">
                    <i class="fas fa-search"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-700">Одоогоор үйлчилгээ байхгүй байна</h3>
                <p class="text-gray-500 mt-2">Та өөр ангилал сонгох эсвэл дараа дахин оролдоно уу.</p>
                <a href="services.php?cat=all" class="inline-block mt-6 px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition">
                    Бүх үйлчилгээг харах
                </a>
            </div>
        <?php endif; ?>

    </main>
</div>

<?php include 'includes/footer.php' ?>