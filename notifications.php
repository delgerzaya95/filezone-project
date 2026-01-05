<?php
session_start();
require_once 'includes/db.php';

// Нэвтрээгүй бол нүүр хуудас руу буцаах
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. БҮГДИЙГ УНШСАН ГЭЖ ТЭМДЭГЛЭХ
if (isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header("Location: notifications.php?success=read_all");
    exit;
}

// 2. ШҮҮЛТҮҮР & PAGINATION
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15; // Хуудсанд харуулах тоо
$offset = ($page - 1) * $limit;

$where_sql = "user_id = ?";
$params = [$user_id];

if ($filter == 'unread') {
    $where_sql .= " AND is_read = 0";
}

// Нийт тоог авах
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE $where_sql");
$stmt_count->execute($params);
$total_notifs = $stmt_count->fetchColumn();
$total_pages = ceil($total_notifs / $limit);

// Мэдэгдлүүдийг татах
$sql = "SELECT * FROM notifications WHERE $where_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Хугацааг тооцоолох функц (Header-тэй ижил, гэхдээ энд дахин ашиглах)
function timeAgoLocal($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Дөнгөж сая';
    if ($diff < 3600) return floor($diff / 60) . ' минутын өмнө';
    if ($diff < 86400) return floor($diff / 3600) . ' цагийн өмнө';
    if ($diff < 604800) return floor($diff / 86400) . ' өдрийн өмнө';
    return date('Y-m-d H:i', $time);
}

$page_title = "Мэдэгдлүүд - Filezone.mn";
include 'includes/header.php';
?>

<div class="flex flex-col min-h-screen bg-gray-50">
    <div class="flex-1 max-w-4xl mx-auto w-full py-8 px-4">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Мэдэгдлүүд</h1>
                <p class="text-sm text-gray-500 mt-1">Танд ирсэн бүх мэдэгдлийн түүх.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <form method="POST" class="inline">
                    <button type="submit" name="mark_all_read" class="text-sm text-brand-600 hover:text-brand-700 font-medium bg-white border border-gray-200 px-4 py-2 rounded-lg hover:bg-gray-50 transition shadow-sm">
                        <i class="fas fa-check-double mr-1"></i> Бүгдийг уншсан
                    </button>
                </form>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex border-b border-gray-200 mb-6">
            <a href="?filter=all" class="px-4 py-2 text-sm font-medium border-b-2 transition-colors <?php echo $filter == 'all' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                Бүгд
            </a>
            <a href="?filter=unread" class="px-4 py-2 text-sm font-medium border-b-2 transition-colors <?php echo $filter == 'unread' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                Уншаагүй
            </a>
        </div>

        <!-- Notifications List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <?php if (count($notifications) > 0): ?>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($notifications as $notif): ?>
                        <?php 
                            // Icon Logic
                            $iconClass = 'bg-blue-100 text-blue-600';
                            $icon = 'fa-info-circle';
                            
                            if ($notif['type'] == 'success') {
                                $iconClass = 'bg-green-100 text-green-600';
                                $icon = 'fa-check-circle';
                            } elseif ($notif['type'] == 'warning') {
                                $iconClass = 'bg-yellow-100 text-yellow-600';
                                $icon = 'fa-exclamation-triangle';
                            } elseif ($notif['type'] == 'error') {
                                $iconClass = 'bg-red-100 text-red-600';
                                $icon = 'fa-times-circle';
                            } elseif ($notif['type'] == 'order') {
                                $iconClass = 'bg-purple-100 text-purple-600';
                                $icon = 'fa-shopping-cart';
                            }

                            // Read Status Style
                            $bgClass = $notif['is_read'] ? 'bg-white hover:bg-gray-50' : 'bg-brand-50/20 hover:bg-brand-50/40 border-l-4 border-brand-500 pl-3';
                        ?>
                        
                        <div class="p-4 flex gap-4 transition-colors relative group <?php echo $bgClass; ?>">
                            <!-- Icon -->
                            <div class="flex-shrink-0 mt-1">
                                <div class="w-10 h-10 rounded-full <?php echo $iconClass; ?> flex items-center justify-center">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start mb-1">
                                    <p class="text-sm font-medium text-gray-900 truncate pr-4">
                                        <?php echo ucfirst($notif['type']); ?>
                                    </p>
                                    <span class="text-xs text-gray-400 whitespace-nowrap">
                                        <?php echo timeAgoLocal($notif['created_at']); ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 leading-relaxed mb-2 <?php echo $notif['is_read'] ? '' : 'font-semibold'; ?>">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </p>
                                
                                <?php if (!empty($notif['link'])): ?>
                                    <a href="<?php echo htmlspecialchars($notif['link']); ?>" onclick="markAsReadPage(<?php echo $notif['id']; ?>, event)" class="inline-flex items-center text-xs font-medium text-brand-600 hover:text-brand-700">
                                        Дэлгэрэнгүй үзэх <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- Delete Button (Optional, hidden by default, shown on hover) -->
                            <!-- <button class="absolute top-4 right-4 text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-trash-alt"></i>
                            </button> -->
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex items-center justify-between sm:px-6">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Нийт <span class="font-medium"><?php echo $total_notifs; ?></span> илэрцээс 
                                    <span class="font-medium"><?php echo $offset + 1; ?></span> - 
                                    <span class="font-medium"><?php echo min($offset + $limit, $total_notifs); ?></span> харагдаж байна
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Previous</span>
                                            <i class="fas fa-chevron-left h-5 w-5 flex items-center justify-center"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <a href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $i == $page ? 'text-brand-600 bg-brand-50 z-10 border-brand-500' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Next</span>
                                            <i class="fas fa-chevron-right h-5 w-5 flex items-center justify-center"></i>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Empty State -->
                <div class="text-center py-16">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                        <i class="far fa-bell-slash text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Мэдэгдэл алга байна</h3>
                    <p class="text-gray-500 mt-1">Танд одоогоор харуулах шинэ мэдээлэл байхгүй байна.</p>
                    <a href="index.php" class="mt-6 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                        Нүүр хуудас руу буцах
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Script for marking as read on this page -->
<script>
    function markAsReadPage(id, event) {
        // Prevent default only if it's not a direct navigation (optional logic)
        // Here we just fire the request and let the browser navigate
        
        fetch('api/mark_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        });
        // The link href will handle the redirection
    }
</script>