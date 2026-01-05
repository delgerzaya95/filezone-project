<?php
session_start();
require_once 'includes/db.php';

// --------------------------------------------------------------------------
// 1. ТУСЛАХ ФУНКЦУУД БОЛОН ТОХИРГОО
// --------------------------------------------------------------------------

// Категориудад өнгө оноох
$category_colors_map = [
    'education' => 'blue',
    'business' => 'green',
    'graphic' => 'purple',
    'technology' => 'indigo',
    'games' => 'red',
    'self-improvement' => 'yellow',
    'forkids' => 'pink',
    'default' => 'gray'
];

// Файлын төрлөөс хамаарч өнгө, айкон авах
function getFileStyle($type) {
    switch ($type) {
        case 'pdf': return ['color' => 'red', 'icon' => 'fas fa-file-pdf'];
        case 'docx': 
        case 'doc': return ['color' => 'blue', 'icon' => 'fas fa-file-word'];
        case 'xlsx': 
        case 'xls': return ['color' => 'green', 'icon' => 'fas fa-file-excel'];
        case 'pptx': 
        case 'ppt': return ['color' => 'orange', 'icon' => 'fas fa-file-powerpoint'];
        case 'zip': 
        case 'rar': return ['color' => 'yellow', 'icon' => 'fas fa-file-archive'];
        case 'jpg': 
        case 'png': 
        case 'jpeg': return ['color' => 'purple', 'icon' => 'fas fa-file-image'];
        case 'psd': 
        case 'ai': return ['color' => 'pink', 'icon' => 'fas fa-file-invoice'];
        default: return ['color' => 'gray', 'icon' => 'fas fa-file'];
    }
}

// --------------------------------------------------------------------------
// 2. ӨГӨГДӨЛ ТАТАХ (Queries)
// --------------------------------------------------------------------------

// A. Бүх категорийг татах
try {
    $cat_stmt = $pdo->prepare("SELECT * FROM categories WHERE type = 'file' ORDER BY id ASC");
    $cat_stmt->execute();
    $all_categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching categories: " . $e->getMessage());
}

// B. Идэвхтэй категорийг тодорхойлох
$current_slug = isset($_GET['cat']) ? $_GET['cat'] : 'all'; // Default to 'all'
$active_category = null;

if ($current_slug === 'all') {
    // "Бүгд" сонголт
    $active_category = [
        'id' => 0,
        'name' => 'Бүх ангилал',
        'slug' => 'all',
        'icon_class' => 'fas fa-layer-group',
        'type' => 'file'
    ];
} else {
    foreach ($all_categories as $cat) {
        if ($cat['slug'] == $current_slug) {
            $active_category = $cat;
            break;
        }
    }
    // Хэрэв олдохгүй бол 'all' руу буцаах
    if (!$active_category) {
        $active_category = [
            'id' => 0,
            'name' => 'Бүх ангилал',
            'slug' => 'all',
            'icon_class' => 'fas fa-layer-group',
            'type' => 'file'
        ];
        $current_slug = 'all';
    }
}

// C. Дэд категоруудыг татах (Зөвхөн тодорхой ангилал сонгосон үед)
$subcategories = [];
if ($current_slug !== 'all' && $active_category) {
    $sub_stmt = $pdo->prepare("SELECT * FROM subcategories WHERE category_id = ?");
    $sub_stmt->execute([$active_category['id']]);
    $subcategories = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// D. Файлуудыг татах (Filter, Sort, Pagination)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12; 
$offset = ($page - 1) * $limit;

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$sub_id = isset($_GET['sub']) ? (int)$_GET['sub'] : null;

// Эрэмбэлэх
$order_by = "f.upload_date DESC"; 
switch ($sort) {
    case 'oldest': $order_by = "f.upload_date ASC"; break;
    case 'popular': $order_by = "f.view_count DESC"; break;
    case 'price_asc': $order_by = "f.price ASC"; break;
    case 'price_desc': $order_by = "f.price DESC"; break;
    case 'rating': $order_by = "avg_rating DESC"; break;
}

// Query бэлтгэх
$sql = "SELECT f.*, u.username, u.avatar_url,
        (SELECT AVG(rating) FROM ratings WHERE file_id = f.id) as avg_rating,
        (SELECT COUNT(*) FROM ratings WHERE file_id = f.id) as review_count
        FROM files f
        JOIN users u ON f.user_id = u.id
        WHERE f.status = 'approved'";

$params = [];

// Хэрэв 'all' биш бол category шүүлтүүр нэмнэ
if ($current_slug !== 'all') {
    $sql .= " AND f.category_id = :cat_id";
    $params[':cat_id'] = $active_category['id'];
}

// Subcategory шүүлтүүр
if ($sub_id) {
    $sql .= " AND f.subcategory_id = :sub_id";
    $params[':sub_id'] = $sub_id;
}

// Нийт тоог авах
$count_stmt = $pdo->prepare(str_replace("f.*, u.username, u.avatar_url,
        (SELECT AVG(rating) FROM ratings WHERE file_id = f.id) as avg_rating,
        (SELECT COUNT(*) FROM ratings WHERE file_id = f.id) as review_count", "COUNT(*)", $sql));
$count_stmt->execute($params);
$total_files = $count_stmt->fetchColumn();
$total_pages = ceil($total_files / $limit);

// Файлуудыг авах
$sql .= " ORDER BY $order_by LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$active_files = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = $active_category['name'] . " - Filezone.mn";
include 'includes/header.php'; 
?>

<div class="w-full max-w-[1400px] mx-auto flex items-start">
    
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php' ?>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-8 min-w-0">
        
        <!-- Breadcrumb -->
        <nav class="flex mb-4 text-xs text-gray-500">
            <ol class="flex items-center space-x-2">
                <li><a href="index.php" class="hover:text-brand-600">Нүүр</a></li>
                <li><span class="text-gray-300">/</span></li>
                <li><a href="categories.php" class="hover:text-brand-600">Бүх ангилал</a></li>
                <li><span class="text-gray-300">/</span></li>
                <li class="font-medium text-gray-900"><?php echo htmlspecialchars($active_category['name']); ?></li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    <?php if($active_category['icon_class']): ?>
                        <i class="<?php echo $active_category['icon_class']; ?> text-brand-600"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($active_category['name']); ?>
                </h1>
                <p class="text-sm text-gray-500 mt-1">Нийт <?php echo $total_files; ?> файл олдлоо.</p>
            </div>
            
            <!-- Sort Dropdown -->
            <form id="sortForm" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="cat" value="<?php echo htmlspecialchars($current_slug); ?>">
                <?php if($sub_id): ?><input type="hidden" name="sub" value="<?php echo $sub_id; ?>"><?php endif; ?>
                
                <label for="sort" class="text-sm text-gray-500 hidden sm:block">Эрэмбэлэх:</label>
                <select name="sort" id="sort" onchange="document.getElementById('sortForm').submit()" class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-brand-500 focus:border-brand-500 block p-2.5 outline-none cursor-pointer hover:border-gray-300 transition">
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Шинэ нь эхэндээ</option>
                    <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Хуучин нь эхэндээ</option>
                    <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Хамгийн их үзсэн</option>
                    <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Үнэлгээ өндөр</option>
                    <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Үнэ: Багаас их рүү</option>
                    <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Үнэ: Ихээс бага руу</option>
                </select>
            </form>
        </div>

        <!-- MAIN CATEGORIES GRID -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <!-- "Бүгд" Option -->
            <?php 
                $isAllActive = ($current_slug === 'all');
                $allBgClass = $isAllActive 
                    ? "border-gray-500 ring-2 ring-gray-500 ring-offset-2 bg-gray-50" 
                    : "bg-white border-gray-200 hover:border-gray-300 hover:shadow-md";
                $allIconBg = $isAllActive ? "bg-gray-600 text-white" : "bg-gray-100 text-gray-600";
            ?>
            <a href="categories.php?cat=all" class="<?php echo $allBgClass; ?> p-4 rounded-2xl border transition-all duration-300 text-center group h-full flex flex-col items-center justify-center">
                <div class="w-12 h-12 rounded-full <?php echo $allIconBg; ?> flex items-center justify-center mb-3 text-xl group-hover:scale-110 transition-transform duration-300 shadow-sm">
                    <i class="fas fa-layer-group"></i>
                </div>
                <span class="text-sm font-bold text-gray-800 group-hover:text-gray-600 transition-colors">
                    Бүгд
                </span>
            </a>

            <!-- Other Categories -->
            <?php foreach($all_categories as $cat): ?>
                <?php 
                    $isActive = ($current_slug == $cat['slug']);
                    $catColor = $category_colors_map[$cat['slug']] ?? $category_colors_map['default'];
                    
                    $bgClass = $isActive 
                        ? "border-{$catColor}-500 ring-2 ring-{$catColor}-500 ring-offset-2 bg-{$catColor}-50" 
                        : "bg-white border-gray-200 hover:border-{$catColor}-300 hover:shadow-md";
                    
                    $iconBg = $isActive 
                        ? "bg-{$catColor}-500 text-white" 
                        : "bg-{$catColor}-50 text-{$catColor}-600";
                ?>
                <a href="categories.php?cat=<?php echo $cat['slug']; ?>" class="<?php echo $bgClass; ?> p-4 rounded-2xl border transition-all duration-300 text-center group h-full flex flex-col items-center justify-center">
                    <div class="w-12 h-12 rounded-full <?php echo $iconBg; ?> flex items-center justify-center mb-3 text-xl group-hover:scale-110 transition-transform duration-300 shadow-sm">
                        <i class="<?php echo $cat['icon_class']; ?>"></i>
                    </div>
                    <span class="text-sm font-bold text-gray-800 group-hover:text-<?php echo $catColor; ?>-600 transition-colors">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- SUBCATEGORIES PILLS (Hide if 'all' is selected or empty) -->
        <?php if(!empty($subcategories) && $current_slug !== 'all'): ?>
        <div class="flex flex-wrap gap-2 mb-8 overflow-x-auto no-scrollbar pb-2">
            <a href="categories.php?cat=<?php echo $current_slug; ?>" class="px-4 py-2 text-sm font-bold rounded-full shadow-md transition-transform hover:scale-105 <?php echo !$sub_id ? 'bg-gray-800 text-white' : 'bg-white text-gray-600 border border-gray-200'; ?>">
                Бүгд
            </a>
            <?php foreach($subcategories as $sub): ?>
                <?php $isSubActive = ($sub_id == $sub['id']); ?>
                <a href="categories.php?cat=<?php echo $current_slug; ?>&sub=<?php echo $sub['id']; ?>" class="px-4 py-2 text-sm font-medium border rounded-full transition-colors whitespace-nowrap <?php echo $isSubActive ? 'bg-brand-50 border-brand-500 text-brand-700' : 'text-gray-600 bg-white border-gray-200 hover:bg-gray-50 hover:border-gray-300'; ?>">
                    <?php echo htmlspecialchars($sub['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- FILE GRID -->
        <?php if(count($active_files) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                
                <?php foreach($active_files as $file): ?>
                    <?php 
                        $style = getFileStyle($file['file_type']);
                        $fileColor = $style['color'];
                        $fileIcon = $style['icon'];
                        $username = $file['username'] ? $file['username'] : 'Админ';
                    ?>
                    
                    <!-- NEW CARD DESIGN -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-all duration-300 group relative flex flex-col h-full">
                        
                        <!-- Top Row: Icon & Badge -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-lg bg-<?php echo $fileColor; ?>-50 text-<?php echo $fileColor; ?>-600 flex items-center justify-center text-xl">
                                <i class="<?php echo $fileIcon; ?>"></i>
                            </div>
                            <span class="bg-gray-100 text-gray-600 text-xs font-bold px-2 py-1 rounded uppercase tracking-wide">
                                <?php echo htmlspecialchars($file['file_type']); ?>
                            </span>
                        </div>
                        
                        <!-- Title -->
                        <h3 class="font-bold text-gray-900 mb-2 line-clamp-2 text-sm group-hover:text-brand-600 transition-colors flex-grow">
                            <a href="file-details.php?id=<?php echo $file['id']; ?>" class="hover:underline decoration-brand-600">
                                <?php echo htmlspecialchars($file['title']); ?>
                            </a>
                        </h3>
                        
                        <!-- Meta Info -->
                        <div class="flex items-center gap-2 text-xs text-gray-500 mb-4">
                            <span class="flex items-center gap-1" title="Таталт">
                                <i class="fas fa-download text-gray-400"></i> <?php echo $file['download_count']; ?>
                            </span>
                            <span>•</span>
                            <span class="truncate max-w-[100px]" title="<?php echo htmlspecialchars($username); ?>">
                                <?php echo htmlspecialchars($username); ?>
                            </span>
                        </div>
                        
                        <!-- Bottom Row: Price & Action -->
                        <div class="flex items-center justify-between pt-3 border-t border-gray-100 mt-auto">
                            <span class="font-bold <?php echo ($file['price'] == 0) ? 'text-green-600' : 'text-gray-900'; ?>">
                                <?php echo ($file['price'] == 0) ? 'Үнэгүй' : number_format($file['price']) . '₮'; ?>
                            </span>
                            
                            <a href="file-details.php?id=<?php echo $file['id']; ?>" class="text-gray-400 hover:text-brand-600 transition-all hover:bg-brand-50 rounded-full p-2" title="Дэлгэрэнгүй үзэх">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                    </div>
                    <!-- END NEW CARD -->

                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <div class="flex justify-center mt-10">
                <nav class="flex items-center gap-2">
                    <!-- Previous -->
                    <?php if($page > 1): ?>
                        <a href="?cat=<?php echo $current_slug; ?>&page=<?php echo $page-1; ?>&sort=<?php echo $sort; ?><?php echo $sub_id ? '&sub='.$sub_id : ''; ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </a>
                    <?php endif; ?>

                    <!-- Pages -->
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-brand-600 text-white font-medium text-sm shadow-md shadow-brand-500/30"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?cat=<?php echo $current_slug; ?>&page=<?php echo $i; ?>&sort=<?php echo $sort; ?><?php echo $sub_id ? '&sub='.$sub_id : ''; ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium text-sm transition"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Next -->
                    <?php if($page < $total_pages): ?>
                        <a href="?cat=<?php echo $current_slug; ?>&page=<?php echo $page+1; ?>&sort=<?php echo $sort; ?><?php echo $sub_id ? '&sub='.$sub_id : ''; ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition">
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
                    <i class="fas fa-folder-open"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-700">Одоогоор файл байхгүй байна</h3>
                <p class="text-gray-500 mt-2">Энэ ангилалд хараахан файл нийтлэгдээгүй байна.</p>
                <a href="index.php" class="inline-block mt-6 px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition">
                    Нүүр хуудас руу буцах
                </a>
            </div>
        <?php endif; ?>

    </main>
</div>

<?php include 'includes/footer.php'; ?>