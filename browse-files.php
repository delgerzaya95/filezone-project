<?php
// 1. Database connection
require_once 'includes/db.php';

// Хуудасны гарчиг
$page_title = "Файл хайх - Filezone.mn";

// Header оруулах
include 'includes/header.php';

// --- HELPER FUNCTIONS (Index-тэй ижил) ---
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

// --- FILTER PARAMETERS ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_slug = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$file_type = isset($_GET['type']) ? trim($_GET['type']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12; // Нэг хуудсанд харуулах тоо
$offset = ($page - 1) * $limit;

// --- BUILD QUERY ---
$where_clauses = ["f.status = 'approved'"];
$params = [];

// 1. Search Filter
if (!empty($search)) {
    $where_clauses[] = "(f.title LIKE :search OR f.description LIKE :search)";
    $params[':search'] = "%$search%";
}

// 2. Category Filter
$category_name_display = "Бүх файл";
if (!empty($category_slug)) {
    // Get category ID first
    $cat_stmt = $pdo->prepare("SELECT id, name FROM categories WHERE slug = :slug");
    $cat_stmt->execute([':slug' => $category_slug]);
    $cat_row = $cat_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cat_row) {
        $where_clauses[] = "f.category_id = :cat_id";
        $params[':cat_id'] = $cat_row['id'];
        $category_name_display = $cat_row['name'];
    }
}

// 3. File Type Filter
if (!empty($file_type)) {
    $where_clauses[] = "f.file_type = :type";
    $params[':type'] = $file_type;
}

// 4. Sort Order
$order_by = "f.upload_date DESC"; // Default
switch ($sort) {
    case 'popular': $order_by = "f.download_count DESC"; break;
    case 'price_asc': $order_by = "f.price ASC"; break;
    case 'price_desc': $order_by = "f.price DESC"; break;
    case 'oldest': $order_by = "f.upload_date ASC"; break;
}

$where_sql = implode(' AND ', $where_clauses);

// --- EXECUTE QUERIES ---

// Count Total (For Pagination)
$count_sql = "SELECT COUNT(*) FROM files f WHERE $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_files = $count_stmt->fetchColumn();
$total_pages = ceil($total_files / $limit);

// Fetch Files
$sql = "
    SELECT f.*, u.username, u.avatar_url, c.name as category_name
    FROM files f
    JOIN users u ON f.user_id = u.id
    LEFT JOIN categories c ON f.category_id = c.id
    WHERE $where_sql
    ORDER BY $order_by
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch All Categories for Filter Dropdown
$all_cats = $pdo->query("SELECT * FROM categories WHERE type = 'file' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <?php include 'includes/sidebar.php' ?>

    <!-- Main Content -->
    <main class="flex-1 py-6 min-w-0 px-4 lg:px-8">
        
        <!-- Page Header & Search -->
        <div class="mb-8">
            <nav class="flex text-xs text-gray-500 mb-2">
                <a href="index.php" class="hover:text-brand-600">Нүүр</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900 font-medium">Файлын сан</span>
            </nav>
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <?php echo htmlspecialchars($category_name_display); ?>
                        <span class="text-sm font-normal text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full"><?php echo $total_files; ?></span>
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Танд хэрэгтэй бүх төрлийн бичиг баримт, загварууд.</p>
                </div>

                <!-- Mobile Search Toggle (Optional) or Inline Search -->
            </div>
        </div>

        <!-- Filters Bar -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6 shadow-sm sticky top-[70px] z-20">
            <form action="browse-files.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                
                <!-- Keep search query if exists -->
                <?php if(!empty($search)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <?php endif; ?>

                <!-- Category Filter -->
                <div class="relative">
                    <select name="cat" onchange="this.form.submit()" class="w-full pl-3 pr-8 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 text-gray-700 appearance-none cursor-pointer">
                        <option value="">Бүх ангилал</option>
                        <?php foreach($all_cats as $cat): ?>
                            <option value="<?php echo $cat['slug']; ?>" <?php echo $category_slug == $cat['slug'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-3 text-gray-400 text-xs pointer-events-none"></i>
                </div>

                <!-- File Type Filter -->
                <div class="relative">
                    <select name="type" onchange="this.form.submit()" class="w-full pl-3 pr-8 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 text-gray-700 appearance-none cursor-pointer">
                        <option value="">Бүх төрөл</option>
                        <option value="docx" <?php echo $file_type == 'docx' ? 'selected' : ''; ?>>Word (DOCX)</option>
                        <option value="pdf" <?php echo $file_type == 'pdf' ? 'selected' : ''; ?>>PDF</option>
                        <option value="pptx" <?php echo $file_type == 'pptx' ? 'selected' : ''; ?>>PowerPoint (PPTX)</option>
                        <option value="xlsx" <?php echo $file_type == 'xlsx' ? 'selected' : ''; ?>>Excel (XLSX)</option>
                        <option value="zip" <?php echo $file_type == 'zip' ? 'selected' : ''; ?>>Архив (ZIP/RAR)</option>
                    </select>
                    <i class="fas fa-filter absolute right-3 top-3 text-gray-400 text-xs pointer-events-none"></i>
                </div>

                <!-- Sort Filter -->
                <div class="relative">
                    <select name="sort" onchange="this.form.submit()" class="w-full pl-3 pr-8 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 text-gray-700 appearance-none cursor-pointer">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Шинээр нэмэгдсэн</option>
                        <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Их татагдсан</option>
                        <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Үнэ: Багаас их рүү</option>
                        <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Үнэ: Ихээс бага руу</option>
                    </select>
                    <i class="fas fa-sort-amount-down absolute right-3 top-3 text-gray-400 text-xs pointer-events-none"></i>
                </div>

                <!-- Search Input (In Filter Bar for easy access) -->
                <div class="relative">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Хайх..." class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 text-gray-700">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
                </div>

            </form>
        </div>

        <!-- Files Grid -->
        <?php if (!empty($files)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php foreach($files as $file): ?>
                    <?php 
                        $style = getFileStyle($file['file_type']);
                        $price_display = $file['price'] == 0 ? 'Үнэгүй' : number_format($file['price']) . '₮';
                        $price_class = $file['price'] == 0 ? 'text-green-600' : 'text-gray-900';
                    ?>
                    <!-- File Card (Matching Index Design) -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-lg transition-all duration-300 group relative flex flex-col h-full">
                        
                        <!-- Header: Icon & Type -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-lg <?php echo $style['bg'] . ' ' . $style['text']; ?> flex items-center justify-center text-xl shadow-sm">
                                <i class="<?php echo $style['icon']; ?>"></i>
                            </div>
                            <span class="bg-gray-100 text-gray-500 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wide">
                                <?php echo htmlspecialchars($file['file_type']); ?>
                            </span>
                        </div>

                        <!-- Title -->
                        <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm min-h-[40px] leading-relaxed">
                            <a href="file-details.php?id=<?php echo $file['id']; ?>">
                                <?php echo htmlspecialchars($file['title']); ?>
                            </a>
                        </h3>

                        <!-- Category -->
                        <div class="mb-4">
                            <a href="browse-files.php?cat=<?php echo $file['category_id']; ?>" class="text-xs text-gray-500 hover:text-brand-600 bg-gray-50 px-2 py-0.5 rounded border border-gray-100 inline-block">
                                <i class="fas fa-folder-open mr-1"></i> <?php echo htmlspecialchars($file['category_name'] ?? 'Бусад'); ?>
                            </a>
                        </div>

                        <!-- Spacer to push footer down -->
                        <div class="flex-grow"></div>

                        <!-- Info Row -->
                        <div class="flex items-center gap-2 text-xs text-gray-400 mb-3 pt-2 border-t border-gray-50 border-dashed">
                            <span><i class="fas fa-download mr-1"></i> <?php echo number_format($file['download_count']); ?></span>
                            <span>•</span>
                            <span class="truncate max-w-[80px]" title="<?php echo htmlspecialchars($file['username']); ?>"><i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($file['username']); ?></span>
                            <span class="ml-auto"><?php echo date('Y/m/d', strtotime($file['upload_date'])); ?></span>
                        </div>

                        <!-- Footer: Price & Action -->
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm <?php echo $price_class; ?>">
                                <?php echo $price_display; ?>
                            </span>
                            
                            <a href="file-details.php?id=<?php echo $file['id']; ?>" class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-brand-600 hover:text-white transition-all shadow-sm">
                                <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="mt-10 flex justify-center">
                <nav class="flex items-center gap-1 bg-white p-1 rounded-xl shadow-sm border border-gray-200">
                    
                    <!-- Prev Button -->
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </a>
                    <?php else: ?>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-300 cursor-not-allowed">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </span>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <?php 
                    $range = 2; // Number of pages to show around current page
                    for ($i = 1; $i <= $total_pages; $i++): 
                        if ($i == 1 || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)):
                    ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition <?php echo $i == $page ? 'bg-brand-600 text-white shadow-md shadow-brand-500/30' : 'text-gray-600 hover:bg-gray-100'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php elseif (($i == $page - $range - 1) || ($i == $page + $range + 1)): ?>
                        <span class="w-9 h-9 flex items-center justify-center text-gray-400">...</span>
                    <?php endif; endfor; ?>

                    <!-- Next Button -->
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </a>
                    <?php else: ?>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-300 cursor-not-allowed">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </span>
                    <?php endif; ?>

                </nav>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Empty State -->
            <div class="text-center py-16 bg-white rounded-2xl border border-gray-200 border-dashed">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                    <i class="fas fa-search text-3xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Илэрц олдсонгүй</h3>
                <p class="text-gray-500 text-sm max-w-sm mx-auto mb-6">
                    Та хайлтын нөхцөлөө өөрчлөөд дахин оролдоно уу эсвэл бүх шүүлтүүрийг арилгана уу.
                </p>
                <a href="browse-files.php" class="inline-block px-6 py-2 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                    Шүүлтүүр арилгах
                </a>
            </div>
        <?php endif; ?>

    </main>
</div>

<?php include 'includes/footer.php' ?>