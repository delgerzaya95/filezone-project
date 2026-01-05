<?php
session_start();

// Database холболт
require_once '../includes/db.php';

// Админ эрх шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --------------------------------------------------------------------------
// ACTION HANDLERS
// --------------------------------------------------------------------------

// 1. Approve
if (isset($_POST['approve_comment'])) {
    $comment_id = intval($_POST['comment_id']);
    try {
        $stmt = $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
        $stmt->execute([$comment_id]);
        $_SESSION['message'] = "Сэтгэгдэл зөвшөөрөгдлөө.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Алдаа: " . $e->getMessage();
    }
    header("Location: comments.php");
    exit;
}

// 2. Reject
if (isset($_POST['reject_comment'])) {
    $comment_id = intval($_POST['comment_id']);
    try {
        $stmt = $pdo->prepare("UPDATE comments SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$comment_id]);
        $_SESSION['message'] = "Сэтгэгдэл татгалзагдлаа.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Алдаа: " . $e->getMessage();
    }
    header("Location: comments.php");
    exit;
}

// 3. Delete
if (isset($_POST['delete_comment'])) {
    $comment_id = intval($_POST['comment_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $_SESSION['message'] = "Сэтгэгдэл устгагдлаа.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Алдаа: " . $e->getMessage();
    }
    header("Location: comments.php");
    exit;
}

// 4. Reply
if (isset($_POST['add_reply'])) {
    $parent_id = intval($_POST['comment_id']);
    $reply_text = $_POST['reply_text'];
    $admin_id = $_SESSION['user_id']; // Админы ID

    try {
        // Эцэг сэтгэгдлээс file_id-г авах
        $stmt = $pdo->prepare("SELECT file_id FROM comments WHERE id = ?");
        $stmt->execute([$parent_id]);
        $file_id = $stmt->fetchColumn();

        if ($file_id) {
            $stmt = $pdo->prepare("INSERT INTO comments (user_id, file_id, comment, parent_comment_id, status, comment_date) VALUES (?, ?, ?, ?, 'approved', NOW())");
            $stmt->execute([$admin_id, $file_id, $reply_text, $parent_id]);
            $_SESSION['message'] = "Хариу амжилттай илгээгдлээ.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Алдаа: " . $e->getMessage();
    }
    header("Location: comments.php");
    exit;
}

// --------------------------------------------------------------------------
// DATA FETCHING (Search, Filter, Pagination)
// --------------------------------------------------------------------------

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clauses = ["c.parent_comment_id IS NULL"]; // Зөвхөн үндсэн сэтгэгдлүүдийг эхэлж авна
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(c.comment LIKE ? OR u.username LIKE ? OR f.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_clauses[] = "c.status = ?";
    $params[] = $status_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// Тоолох
$count_sql = "SELECT COUNT(*) 
              FROM comments c 
              LEFT JOIN users u ON c.user_id = u.id 
              LEFT JOIN files f ON c.file_id = f.id 
              WHERE $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Сэтгэгдэл татах
$sql = "SELECT c.*, u.username, u.full_name, u.avatar_url, f.title as file_title 
        FROM comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        LEFT JOIN files f ON c.file_id = f.id 
        WHERE $where_sql 
        ORDER BY c.comment_date DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$comments = $stmt->fetchAll();

// Статистик
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
    'approved' => $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn(),
    'replies' => $pdo->query("SELECT COUNT(*) FROM comments WHERE parent_comment_id IS NOT NULL")->fetchColumn()
];

// Хариу сэтгэгдлийг татах функц
function get_replies($pdo, $parent_id) {
    $stmt = $pdo->prepare("SELECT c.*, u.username, u.avatar_url 
                           FROM comments c 
                           LEFT JOIN users u ON c.user_id = u.id 
                           WHERE c.parent_comment_id = ? 
                           ORDER BY c.comment_date ASC");
    $stmt->execute([$parent_id]);
    return $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сэтгэгдэл - FileZone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
</head>
<body class="font-sans text-slate-800 antialiased bg-slate-50">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- HEADER -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shadow-sm z-10">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden text-slate-500"><i class="fas fa-bars text-xl"></i></button>
                    <h1 class="text-xl font-bold text-slate-800">Сэтгэгдлийн удирдлага</h1>
                </div>
                <!-- Notifications -->
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <i class="fas fa-bell text-slate-500 text-xl"></i>
                        <?php if($stats['pending'] > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-4 h-4 text-xs flex items-center justify-center">
                            <?php echo $stats['pending']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-slate-700">Админ</span>
                    </div>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                
                <!-- Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $_SESSION['message']; ?></span>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Нийт сэтгэгдэл</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['total']); ?></p>
                        </div>
                        <div class="bg-blue-50 text-blue-600 p-3 rounded-full"><i class="fas fa-comments text-xl"></i></div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Зөвшөөрөгдсөн</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['approved']); ?></p>
                        </div>
                        <div class="bg-green-50 text-green-600 p-3 rounded-full"><i class="fas fa-check-circle text-xl"></i></div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Хариу сэтгэгдэл</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['replies']); ?></p>
                        </div>
                        <div class="bg-purple-50 text-purple-600 p-3 rounded-full"><i class="fas fa-reply text-xl"></i></div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Хүлээгдэж буй</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['pending']); ?></p>
                        </div>
                        <div class="bg-yellow-50 text-yellow-600 p-3 rounded-full"><i class="fas fa-clock text-xl"></i></div>
                    </div>
                </div>

                <!-- Filters & Comments List -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
                    
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <h3 class="text-lg font-bold text-slate-800">Сэтгэгдлүүд</h3>
                        <form method="GET" class="flex flex-1 md:flex-none gap-2 w-full md:w-auto">
                            <div class="relative flex-1 md:w-64">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Хайх..." class="pl-10 pr-4 py-2 w-full border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <select name="status" onchange="this.form.submit()" class="border border-slate-300 rounded-lg text-sm px-3 py-2 bg-white text-slate-700 focus:ring-2 focus:ring-indigo-500">
                                <option value="">Бүгд</option>
                                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Зөвшөөрөгдсөн</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Хүлээгдэж буй</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Хасагдсан</option>
                            </select>
                        </form>
                    </div>

                    <div class="space-y-4">
                        <?php if (count($comments) > 0): ?>
                            <?php foreach ($comments as $comment): ?>
                            <?php
                                // Avatar fallback
                                $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($comment['username']) . '&background=random&color=fff';
                                if (!empty($comment['avatar_url']) && file_exists('../' . $comment['avatar_url'])) {
                                    $avatar = '../' . $comment['avatar_url'];
                                }
                            ?>
                            <div class="border border-slate-200 rounded-lg p-4 hover:bg-slate-50 transition-colors">
                                <div class="flex items-start gap-4">
                                    <img src="<?php echo $avatar; ?>" alt="User" class="w-10 h-10 rounded-full border border-slate-200 object-cover">
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start mb-1">
                                            <div>
                                                <h4 class="font-semibold text-slate-800"><?php echo htmlspecialchars($comment['full_name'] ?: $comment['username']); ?></h4>
                                                <p class="text-xs text-slate-500">
                                                    Файл: <span class="font-medium text-indigo-600"><?php echo htmlspecialchars($comment['file_title']); ?></span>
                                                </p>
                                            </div>
                                            <div class="flex flex-col items-end gap-1">
                                                <span class="text-xs text-slate-400"><?php echo date('Y-m-d H:i', strtotime($comment['comment_date'])); ?></span>
                                                <?php if ($comment['status'] === 'approved'): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Approved</span>
                                                <?php elseif ($comment['status'] === 'pending'): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Rejected</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <p class="text-slate-700 text-sm mt-2 mb-3 bg-white p-2 rounded border border-slate-100">
                                            <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                        </p>

                                        <div class="flex items-center gap-3">
                                            <button onclick="openReplyModal(<?php echo $comment['id']; ?>)" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium flex items-center gap-1">
                                                <i class="fas fa-reply"></i> Хариулах
                                            </button>

                                            <?php if ($comment['status'] !== 'approved'): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <button type="submit" name="approve_comment" class="text-green-600 hover:text-green-800 text-xs font-medium flex items-center gap-1">
                                                        <i class="fas fa-check"></i> Зөвшөөрөх
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($comment['status'] !== 'rejected'): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <button type="submit" name="reject_comment" class="text-orange-600 hover:text-orange-800 text-xs font-medium flex items-center gap-1">
                                                        <i class="fas fa-times"></i> Татгалзах
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <form method="POST" class="inline" onsubmit="return confirm('Устгах уу?');">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                <button type="submit" name="delete_comment" class="text-red-600 hover:text-red-800 text-xs font-medium flex items-center gap-1">
                                                    <i class="fas fa-trash"></i> Устгах
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Replies -->
                                        <?php 
                                        $replies = get_replies($pdo, $comment['id']);
                                        if (count($replies) > 0): 
                                        ?>
                                            <div class="mt-4 ml-4 pl-4 border-l-2 border-slate-200 space-y-3">
                                                <?php foreach ($replies as $reply): ?>
                                                <?php 
                                                    $rAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($reply['username']) . '&background=random&color=fff';
                                                    if (!empty($reply['avatar_url']) && file_exists('../' . $reply['avatar_url'])) $rAvatar = '../' . $reply['avatar_url'];
                                                ?>
                                                <div class="flex items-start gap-3">
                                                    <img src="<?php echo $rAvatar; ?>" class="w-6 h-6 rounded-full object-cover">
                                                    <div class="flex-1">
                                                        <div class="flex justify-between">
                                                            <span class="text-xs font-semibold text-slate-700"><?php echo htmlspecialchars($reply['username']); ?></span>
                                                            <span class="text-xs text-slate-400"><?php echo date('Y-m-d H:i', strtotime($reply['comment_date'])); ?></span>
                                                        </div>
                                                        <p class="text-xs text-slate-600 mt-1"><?php echo htmlspecialchars($reply['comment']); ?></p>
                                                        
                                                        <form method="POST" class="mt-1 text-right" onsubmit="return confirm('Хариуг устгах уу?');">
                                                            <input type="hidden" name="comment_id" value="<?php echo $reply['id']; ?>">
                                                            <button type="submit" name="delete_comment" class="text-red-400 hover:text-red-600 text-[10px]">Устгах</button>
                                                        </form>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-8 text-slate-500">Сэтгэгдэл олдсонгүй.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <div class="mt-6 flex justify-center gap-2">
                        <?php if($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="px-3 py-1 border border-slate-300 rounded text-sm text-slate-600 hover:bg-slate-50">Өмнөх</a>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="px-3 py-1 border <?php echo $i == $page ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-300 text-slate-600 hover:bg-slate-50'; ?> rounded text-sm"><?php echo $i; ?></a>
                        <?php endfor; ?>

                        <?php if($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="px-3 py-1 border border-slate-300 rounded text-sm text-slate-600 hover:bg-slate-50">Дараах</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>

            </main>
        </div>
    </div>

    <!-- Reply Modal -->
    <div id="replyModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeReplyModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form method="POST" action="">
                    <input type="hidden" name="add_reply" value="1">
                    <input type="hidden" name="comment_id" id="reply_comment_id">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Хариу бичих</h3>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Таны хариу</label>
                            <textarea name="reply_text" id="reply_text" rows="4" class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Илгээх</button>
                        <button type="button" onclick="closeReplyModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Болих</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function openReplyModal(commentId) {
            document.getElementById('reply_comment_id').value = commentId;
            document.getElementById('replyModal').classList.remove('hidden');
            setTimeout(() => document.getElementById('reply_text').focus(), 100);
        }

        function closeReplyModal() {
            document.getElementById('replyModal').classList.add('hidden');
        }
    </script>
</body>
</html>