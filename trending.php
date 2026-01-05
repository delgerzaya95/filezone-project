<?php
session_start();
require_once 'includes/db.php';

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
    $index = strlen((string)$str) % count($colors);
    return $colors[$index];
}

// Эхний үсгүүдийг авах
function getInitials($name) {
    $name = (string)$name;
    if (function_exists('mb_substr')) {
        return mb_strtoupper(mb_substr($name, 0, 2));
    }
    preg_match_all('/./u', $name, $matches);
    return strtoupper(implode('', array_slice($matches[0] ?? [], 0, 2)));
}

// --- DATA FETCHING ---

// Filter Logic
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // all, today, week, month
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Where clause setup based on filter
$where_sql = "f.status = 'approved'";
$params = [];

if ($filter == 'today') {
    $where_sql .= " AND DATE(f.upload_date) = CURDATE()";
} elseif ($filter == 'week') {
    $where_sql .= " AND YEARWEEK(f.upload_date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter == 'month') {
    $where_sql .= " AND MONTH(f.upload_date) = MONTH(CURDATE()) AND YEAR(f.upload_date) = YEAR(CURDATE())";
}

// Count Total for Pagination
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM files f WHERE $where_sql");
$stmt_count->execute($params);
$total_files = $stmt_count->fetchColumn();
$total_pages = ceil($total_files / $limit);

// Fetch Trending Files
$sql = "
    SELECT f.*, u.username, u.avatar_url,
    (SELECT AVG(rating) FROM ratings WHERE file_id = f.id) as avg_rating,
    (SELECT COUNT(*) FROM ratings WHERE file_id = f.id) as review_count
    FROM files f
    JOIN users u ON f.user_id = u.id
    WHERE $where_sql
    ORDER BY f.download_count DESC, f.view_count DESC
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trending_files = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Эрэлттэй файлууд - Filezone.mn";
include 'includes/header.php';
?>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="flex-1 w-full min-w-0 p-4 lg:p-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <span class="p-2 bg-orange-100 text-orange-500 rounded-lg">
                    <i class="fas fa-fire"></i>
                </span>
                Эрэлттэй файлууд
            </h1>
            <p class="text-gray-500 mt-2 ml-12 text-sm">Хамгийн олон удаа татагдсан, өндөр үнэлгээтэй файлууд.</p>
        </div>

        <!-- Time Filter Tabs -->
        <div class="flex gap-2 mb-8 overflow-x-auto pb-2 no-scrollbar">
            <a href="?filter=all" class="px-4 py-2 text-sm font-medium rounded-full whitespace-nowrap shadow-sm transition-all <?php echo $filter == 'all' ? 'bg-gray-900 text-white transform scale-105' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300'; ?>">
                Бүгд
            </a>
            <a href="?filter=today" class="px-4 py-2 text-sm font-medium rounded-full whitespace-nowrap shadow-sm transition-all <?php echo $filter == 'today' ? 'bg-gray-900 text-white transform scale-105' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300'; ?>">
                Өнөөдөр
            </a>
            <a href="?filter=week" class="px-4 py-2 text-sm font-medium rounded-full whitespace-nowrap shadow-sm transition-all <?php echo $filter == 'week' ? 'bg-gray-900 text-white transform scale-105' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300'; ?>">
                Энэ 7 хоног
            </a>
            <a href="?filter=month" class="px-4 py-2 text-sm font-medium rounded-full whitespace-nowrap shadow-sm transition-all <?php echo $filter == 'month' ? 'bg-gray-900 text-white transform scale-105' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300'; ?>">
                Энэ сар
            </a>
        </div>

        <!-- Trending Grid -->
        <?php if (count($trending_files) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <?php foreach ($trending_files as $index => $file): ?>
                    <?php 
                        $style = getFileStyle($file['file_type']);
                        $overall_rank = ($page - 1) * $limit + $index + 1;
                        
                        // Rank Badge Logic
                        $rankBadge = '';
                        if ($overall_rank == 1) {
                            $rankBadge = '<div class="absolute -top-3 -left-3 w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 text-white font-bold text-lg rounded-xl flex items-center justify-center shadow-lg z-10 border-2 border-white">1</div>';
                        } elseif ($overall_rank == 2) {
                            $rankBadge = '<div class="absolute -top-3 -left-3 w-10 h-10 bg-gradient-to-br from-gray-300 to-gray-400 text-white font-bold text-lg rounded-xl flex items-center justify-center shadow-md z-10 border-2 border-white">2</div>';
                        } elseif ($overall_rank == 3) {
                            $rankBadge = '<div class="absolute -top-3 -left-3 w-10 h-10 bg-gradient-to-br from-orange-300 to-yellow-600 text-white font-bold text-lg rounded-xl flex items-center justify-center shadow-md z-10 border-2 border-white">3</div>';
                        }

                        // User Avatar Logic
                        $hasAvatar = !empty($file['avatar_url']);
                        $initials = getInitials($file['username']);
                        $colorClass = getRandomColorClass($file['username']);
                        
                        $rating = $file['avg_rating'] ? number_format($file['avg_rating'], 1) : '0.0';
                        $price_display = $file['price'] == 0 ? 'Үнэгүй' : number_format($file['price']) . '₮';
                        $price_class = $file['price'] == 0 ? 'text-green-600' : 'text-brand-600';
                    ?>

                    <!-- Card -->
                    <div class="group relative bg-white rounded-2xl border border-gray-200 hover:shadow-xl transition-all duration-300 hover:-translate-y-1 flex flex-col h-full">
                        
                        <?php echo $rankBadge; ?>
                        
                        <div class="p-5 flex-grow">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3 w-full">
                                    <div class="w-12 h-12 rounded-xl <?php echo $style['bg'] . ' ' . $style['text']; ?> flex-shrink-0 flex items-center justify-center text-xl font-bold">
                                        <i class="<?php echo $style['icon']; ?>"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="font-bold text-gray-900 line-clamp-1 group-hover:text-brand-600 transition" title="<?php echo htmlspecialchars($file['title']); ?>">
                                            <a href="file-details.php?id=<?php echo $file['id']; ?>">
                                                <?php echo htmlspecialchars($file['title']); ?>
                                            </a>
                                        </h3>
                                        <p class="text-xs text-gray-500 uppercase tracking-wide"><?php echo htmlspecialchars($file['file_type']); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2 mb-4 text-xs text-gray-500 bg-gray-50 p-2 rounded-lg justify-between">
                                <span class="flex items-center gap-1 text-orange-500 font-semibold">
                                    <i class="fas fa-star"></i> <?php echo $rating; ?>
                                </span>
                                <span class="w-px h-3 bg-gray-300"></span>
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-download"></i> <?php echo number_format($file['download_count']); ?> татсан
                                </span>
                            </div>

                            <div class="flex items-center justify-between mt-auto pt-2 border-t border-gray-100">
                                <div class="flex items-center gap-2">
                                    <?php if ($hasAvatar): ?>
                                        <img src="<?php echo htmlspecialchars($file['avatar_url']); ?>" alt="User" class="w-6 h-6 rounded-full border border-gray-200 object-cover">
                                    <?php else: ?>
                                        <div class="w-6 h-6 rounded-full border border-gray-200 flex items-center justify-center text-[9px] font-bold <?php echo $colorClass['bg'] . ' ' . $colorClass['text']; ?>">
                                            <?php echo $initials; ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="text-xs font-medium text-gray-600 truncate max-w-[80px]"><?php echo htmlspecialchars($file['username']); ?></span>
                                </div>
                                <span class="<?php echo $price_class; ?> font-bold text-sm">
                                    <?php echo $price_display; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="mt-8 flex justify-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            &larr; Өмнөх
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>" class="px-4 py-2 border <?php echo $i == $page ? 'border-brand-600 bg-brand-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'; ?> rounded-lg text-sm font-medium transition">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Дараах &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Empty State -->
            <div class="text-center py-16 bg-white rounded-2xl border border-dashed border-gray-300">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                    <i class="fas fa-file-invoice-dollar text-3xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">Файл олдсонгүй</h3>
                <p class="text-gray-500 mt-1">Одоогоор энэ ангилалд файл байхгүй байна.</p>
                <a href="index.php" class="mt-6 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                    Нүүр хуудас руу буцах
                </a>
            </div>
        <?php endif; ?>

    </main>
</div>

<?php include 'includes/footer.php'; ?>