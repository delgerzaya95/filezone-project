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
// ACTION HANDLERS (Үйлдлүүд)
// --------------------------------------------------------------------------

// Хэрэглэгч устгах
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    if ($delete_id == $_SESSION['user_id']) {
        $_SESSION['error'] = "Та өөрийн бүртгэлийг устгах боломжгүй!";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$delete_id]);
            $_SESSION['message'] = "Хэрэглэгч амжилттай устгагдлаа!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Алдаа: " . $e->getMessage();
        }
    }
    header("Location: users.php");
    exit;
}

// Төлөв өөрчлөх (Active / Suspended / Banned)
if (isset($_GET['status_id']) && isset($_GET['new_status'])) {
    $status_id = intval($_GET['status_id']);
    $new_status = $_GET['new_status'];
    
    // Зөвшөөрөгдсөн утгууд мөн эсэхийг шалгах
    $allowed_statuses = ['active', 'suspended', 'banned'];
    
    if (in_array($new_status, $allowed_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $status_id]);
            $_SESSION['message'] = "Төлөв амжилттай шинэчлэгдлээ!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Алдаа: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Буруу төлөв сонгогдсон байна.";
    }
    header("Location: users.php");
    exit;
}

// --------------------------------------------------------------------------
// BACKEND LOGIC (Data Fetching)
// --------------------------------------------------------------------------

$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Query бэлдэх
$where_clauses = ["1=1"];
$params = [];

// 1. Text Search
if (!empty($search)) {
    $where_clauses[] = "(username LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// 2. Role Filter
if (!empty($role_filter)) {
    $where_clauses[] = "role = ?";
    $params[] = $role_filter;
}

// 3. Status Filter
if (!empty($status_filter)) {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
}

// 4. Date Range Filter (join_date)
if (!empty($date_from)) {
    $where_clauses[] = "DATE(join_date) >= ?";
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $where_clauses[] = "DATE(join_date) <= ?";
    $params[] = $date_to;
}

$where_sql = implode(' AND ', $where_clauses);

// Тоолох
$count_sql = "SELECT COUNT(*) FROM users WHERE $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Татах
$sql = "SELECT * FROM users WHERE $where_sql ORDER BY join_date DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Filezone Admin</title>
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

        <!-- MAIN CONTENT -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- HEADER -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shadow-sm z-10">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden text-slate-500"><i class="fas fa-bars text-xl"></i></button>
                    <h1 class="text-xl font-bold text-slate-800">Хэрэглэгчийн удирдлага</h1>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="alert('Шинэ хэрэглэгч нэмэх цонх (Demo)')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                        <i class="fas fa-user-plus"></i> <span class="hidden sm:inline">Хэрэглэгч нэмэх</span>
                    </button>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6">
                
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

                <!-- Filters Bar (Expanded Grid) -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        
                        <!-- Search -->
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase">Хайлт</label>
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Нэр, Имэйл эсвэл Утас..." class="pl-10 pr-4 py-2 w-full border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        
                        <!-- Role Filter -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase">Үүрэг</label>
                            <select name="role" class="w-full border border-slate-300 rounded-lg text-sm px-3 py-2 bg-white text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Бүх үүрэг</option>
                                <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Администратор</option>
                                <option value="moderator" <?php echo $role_filter == 'moderator' ? 'selected' : ''; ?>>Модератор</option>
                                <option value="user" <?php echo $role_filter == 'user' ? 'selected' : ''; ?>>Хэрэглэгч</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase">Төлөв</label>
                            <select name="status" class="w-full border border-slate-300 rounded-lg text-sm px-3 py-2 bg-white text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Бүх төлөв</option>
                                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>✅ Идэвхтэй</option>
                                <option value="suspended" <?php echo $status_filter == 'suspended' ? 'selected' : ''; ?>>⏸ Түр хаасан</option>
                                <option value="banned" <?php echo $status_filter == 'banned' ? 'selected' : ''; ?>>❌ Бандуулсан</option>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase">Эхлэх огноо</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-600">
                        </div>

                        <!-- Date To -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase">Дуусах огноо</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-600">
                        </div>

                        <!-- Actions -->
                        <div class="col-span-1 md:col-span-2 flex items-end gap-2">
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition shadow-sm font-medium">
                                <i class="fas fa-filter mr-1"></i> Шүүх
                            </button>
                            <a href="users.php" class="px-4 py-2 text-slate-600 bg-white border border-slate-300 rounded-lg text-sm hover:bg-slate-50 transition font-medium">
                                <i class="fas fa-undo mr-1"></i> Цэвэрлэх
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Users Table -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                    <th class="px-6 py-4 w-10">
                                        <input type="checkbox" class="form-checkbox text-indigo-600 rounded">
                                    </th>
                                    <th class="px-6 py-4 font-semibold">Хэрэглэгч</th>
                                    <th class="px-6 py-4 font-semibold">Үүрэг</th>
                                    <th class="px-6 py-4 font-semibold">Бүртгүүлсэн</th>
                                    <th class="px-6 py-4 font-semibold">Төлөв</th>
                                    <th class="px-6 py-4 font-semibold text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (count($users) > 0): ?>
                                    <?php foreach ($users as $user): ?>
                                    <?php
                                        // IMPROVED AVATAR LOGIC
                                        $avatarSrc = 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=random&color=fff';
                                        
                                        if (!empty($user['avatar_url'])) {
                                            // Check if it's a full URL (Google/Facebook/External)
                                            if (strpos($user['avatar_url'], 'http') === 0) {
                                                $avatarSrc = $user['avatar_url'];
                                            } 
                                            // Else assume it's a local file
                                            elseif (file_exists('../' . $user['avatar_url'])) {
                                                $avatarSrc = '../' . $user['avatar_url'];
                                            }
                                        }
                                        
                                        $userData = htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <tr id="user-row-<?php echo $user['id']; ?>" class="hover:bg-slate-50 transition-colors group">
                                        <td class="px-6 py-4">
                                            <input type="checkbox" class="form-checkbox text-indigo-600 rounded">
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-4">
                                                <img class="h-10 w-10 rounded-full border border-slate-200 object-cover" src="<?php echo $avatarSrc; ?>" alt="<?php echo htmlspecialchars($user['username']); ?>">
                                                <div>
                                                    <div class="text-sm font-semibold text-slate-900 user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                                    <div class="text-xs text-slate-500 user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="user-role inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : ($user['role'] === 'moderator' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-800'); ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-slate-600"><?php echo date('Y-m-d', strtotime($user['join_date'])); ?></span>
                                        </td>
                                        <td class="px-6 py-4 user-status-cell">
                                            <?php if($user['status'] == 'active'): ?>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Идэвхтэй
                                                </span>
                                            <?php elseif($user['status'] == 'suspended'): ?>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-orange-50 text-orange-700 border border-orange-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span> Түр хаасан
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Бандуулсан
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                
                                                <!-- NEW: EMAIL BUTTON -->
                                                <a href="send_email.php?email=<?php echo urlencode($user['email']); ?>&name=<?php echo urlencode($user['username']); ?>" class="p-1.5 text-slate-400 hover:text-green-600 rounded hover:bg-green-50 transition" title="Мэйл илгээх">
                                                    <i class="fas fa-envelope"></i>
                                                </a>

                                                <button onclick='openViewUserModal(<?php echo $userData; ?>)' class="p-1.5 text-slate-400 hover:text-blue-600 rounded hover:bg-blue-50 transition" title="Дэлгэрэнгүй харах">
                                                    <i class="fas fa-eye"></i>
                                                </button>

                                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded hover:bg-indigo-50 transition" title="Засах">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                
                                                <?php if($user['status'] == 'active'): ?>
                                                    <!-- Active User: Show Suspend and Ban options -->
                                                    <a href="?status_id=<?php echo $user['id']; ?>&new_status=suspended" class="p-1.5 text-slate-400 hover:text-orange-600 rounded hover:bg-orange-50 transition" title="Түр хаах (Suspend)" onclick="return confirm('Энэ хэрэглэгчийг Түр хаах уу?')">
                                                        <i class="fas fa-pause-circle"></i>
                                                    </a>
                                                    <a href="?status_id=<?php echo $user['id']; ?>&new_status=banned" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50 transition" title="Бандах" onclick="return confirm('Энэ хэрэглэгчийг BAN хийх үү?')">
                                                        <i class="fas fa-ban"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <!-- Suspended/Banned User: Show Activate option -->
                                                    <a href="?status_id=<?php echo $user['id']; ?>&new_status=active" class="p-1.5 text-slate-400 hover:text-green-600 rounded hover:bg-green-50 transition" title="Идэвхжүүлэх" onclick="return confirm('Энэ хэрэглэгчийг идэвхжүүлэх үү?')">
                                                        <i class="fas fa-check-circle"></i>
                                                    </a>
                                                    <?php if($user['status'] == 'suspended'): ?>
                                                        <a href="?status_id=<?php echo $user['id']; ?>&new_status=banned" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50 transition" title="Бандах" onclick="return confirm('Энэ хэрэглэгчийг BAN хийх үү?')">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <a href="?delete_id=<?php echo $user['id']; ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50 transition" title="Устгах" onclick="return confirm('Анхаар! Хэрэглэгчийг устгавал бүх мэдээлэл устана. Үргэлжлүүлэх үү?')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                            Хэрэглэгч олдсонгүй.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <div class="bg-white px-6 py-4 border-t border-slate-200 flex items-center justify-between">
                        <span class="text-sm text-slate-500">Нийт <?php echo $total_rows; ?> хэрэглэгчээс <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total_rows); ?> харагдаж байна</span>
                        <div class="flex items-center gap-1">
                            <?php 
                                // Pagination links
                                $query_params = $_GET;
                                function buildPageLink($page, $params) {
                                    $params['page'] = $page;
                                    return '?' . http_build_query($params);
                                }
                            ?>

                            <?php if($page > 1): ?>
                                <a href="<?php echo buildPageLink($page - 1, $query_params); ?>" class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 text-slate-600">Өмнөх</a>
                            <?php endif; ?>

                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="<?php echo buildPageLink($i, $query_params); ?>" class="px-3 py-1 text-sm border <?php echo $i == $page ? 'border-indigo-500 bg-indigo-50 text-indigo-600 font-medium' : 'border-slate-300 rounded hover:bg-slate-50 text-slate-600'; ?> rounded">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if($page < $total_pages): ?>
                                <a href="<?php echo buildPageLink($page + 1, $query_params); ?>" class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 text-slate-600">Дараах</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <!-- View User Details Modal -->
    <div id="viewUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeViewUserModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-start">
                        <h3 class="text-xl leading-6 font-bold text-gray-900 mb-4" id="view-modal-title">Хэрэглэгчийн дэлгэрэнгүй</h3>
                        <button onclick="closeViewUserModal()" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Profile Image & Basic Info -->
                        <div class="md:col-span-1 flex flex-col items-center text-center p-4 bg-gray-50 rounded-lg">
                            <img id="viewUserAvatar" src="" alt="Avatar" class="h-24 w-24 rounded-full object-cover border-4 border-white shadow-md mb-3">
                            <h4 id="viewUsername" class="text-lg font-bold text-gray-800 break-all"></h4>
                            <span id="viewUserRole" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mt-1"></span>
                            <div class="mt-4 w-full">
                                <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Дансны үлдэгдэл</div>
                                <div id="viewUserBalance" class="text-xl font-bold text-green-600"></div>
                                <div id="viewUserPendingBalance" class="text-sm text-gray-500 mt-1">Хүлээгдэж буй: 0₮</div>
                            </div>
                        </div>

                        <!-- Detailed Info -->
                        <div class="md:col-span-2 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase">Бүтэн нэр</label>
                                    <div id="viewFullName" class="text-sm font-medium text-gray-900 mt-1 border-b border-gray-100 pb-1"></div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase">Имэйл</label>
                                    <div id="viewEmail" class="text-sm font-medium text-gray-900 mt-1 border-b border-gray-100 pb-1 break-all"></div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase">Утас</label>
                                    <div id="viewPhone" class="text-sm font-medium text-gray-900 mt-1 border-b border-gray-100 pb-1"></div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase">Бүртгүүлсэн огноо</label>
                                    <div id="viewJoinDate" class="text-sm font-medium text-gray-900 mt-1 border-b border-gray-100 pb-1"></div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase">Түвшин (Level)</label>
                                    <div id="viewLevel" class="text-sm font-medium text-gray-900 mt-1 border-b border-gray-100 pb-1"></div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase">Сүүлд идэвхтэй</label>
                                    <div id="viewLastActive" class="text-sm font-medium text-gray-900 mt-1 border-b border-gray-100 pb-1"></div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Ур чадвар (Skills)</label>
                                <div id="viewSkills" class="mt-1 flex flex-wrap gap-1"></div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Товч намтар (Bio)</label>
                                <p id="viewBio" class="text-sm text-gray-600 mt-1 bg-gray-50 p-2 rounded"></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="closeViewUserModal()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Хаах</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
    function openViewUserModal(user) {
        document.getElementById('viewUsername').textContent = user.username || '-';
        document.getElementById('viewUserRole').textContent = user.role || 'User';
        
        // Correct Avatar Logic for Modal (Matches PHP logic)
        let avatarSrc = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.username) + '&background=random&color=fff';
        
        if (user.avatar_url && user.avatar_url.trim() !== '') {
            if (user.avatar_url.startsWith('http')) {
                avatarSrc = user.avatar_url; // Use external URL as is
            } else {
                avatarSrc = '../' + user.avatar_url; // Prepend path for local files
            }
        }
        document.getElementById('viewUserAvatar').src = avatarSrc;

        if (user.full_name && user.full_name.trim() !== "") {
            document.getElementById('viewFullName').textContent = user.full_name;
        } else {
            document.getElementById('viewFullName').textContent = user.username || '-';
        }
        
        document.getElementById('viewEmail').textContent = user.email || '-';
        document.getElementById('viewPhone').textContent = user.phone || 'Бүртгэлгүй';
        
        const joinDate = user.join_date ? new Date(user.join_date).toLocaleDateString('mn-MN') : '-';
        document.getElementById('viewJoinDate').textContent = joinDate;
        
        document.getElementById('viewLastActive').textContent = user.last_active ? new Date(user.last_active).toLocaleString('mn-MN') : 'Мэдэгдэхгүй';

        const balance = user.balance ? new Intl.NumberFormat('mn-MN').format(user.balance) : '0';
        document.getElementById('viewUserBalance').textContent = balance + '₮';
        
        const pending = user.pending_balance ? new Intl.NumberFormat('mn-MN').format(user.pending_balance) : '0';
        document.getElementById('viewUserPendingBalance').textContent = 'Хүлээгдэж буй: ' + pending + '₮';

        document.getElementById('viewLevel').textContent = user.level || 'Beginner';
        document.getElementById('viewBio').textContent = user.bio || 'Тайлбар алга.';

        const skillsContainer = document.getElementById('viewSkills');
        skillsContainer.innerHTML = '';
        if (user.skills) {
            const skills = user.skills.split(',');
            skills.forEach(skill => {
                const span = document.createElement('span');
                span.className = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800';
                span.textContent = skill.trim();
                skillsContainer.appendChild(span);
            });
        } else {
            skillsContainer.textContent = '-';
        }

        document.getElementById('viewUserModal').classList.remove('hidden');
    }

    function closeViewUserModal() {
        document.getElementById('viewUserModal').classList.add('hidden');
    }
    </script>
</body>
</html>