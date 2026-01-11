<?php
session_start();
// Үндсэн баазтай холбогдох (Users table-ээс мэдээлэл авахад хэрэгтэй)
require_once '../includes/db.php';
// KIDS баазтай холбогдох
require_once '../kids/db_kids.php';

// Админ эрх шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --------------------------------------------------------------------------
// 1. ACTION HANDLERS (Материал устгах)
// --------------------------------------------------------------------------
function deleteFolder($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? deleteFolder("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    try {
        $folder_path = "../uploads/kids/" . $id;
        if (file_exists($folder_path)) {
            deleteFolder($folder_path);
        }
        $stmt = $pdo_kids->prepare("DELETE FROM kids_materials WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['message'] = "Материал болон холбогдох файлууд устгагдлаа.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Алдаа: " . $e->getMessage();
    }
    header("Location: kids.php");
    exit;
}

// --------------------------------------------------------------------------
// 2. STATISTICS FETCHING
// --------------------------------------------------------------------------
try {
    // A. General Stats
    $total_views = $pdo_kids->query("SELECT SUM(view_count) FROM kids_materials")->fetchColumn() ?: 0;
    $total_downloads = $pdo_kids->query("SELECT COUNT(*) FROM kids_downloads")->fetchColumn() ?: 0;
    $total_subs = $pdo_kids->query("SELECT COUNT(*) FROM kids_subscriptions WHERE status = 'active'")->fetchColumn() ?: 0;
    $total_revenue = $pdo_kids->query("SELECT SUM(price_paid) FROM kids_subscriptions")->fetchColumn() ?: 0;

    // B. Recent Downloads (with Material Info)
    // Хэрэглэгчийн мэдээллийг дараа нь PHP-ээр Main DB-ээс авна
    $dl_sql = "SELECT d.*, m.title as material_title, m.cover_image 
               FROM kids_downloads d 
               LEFT JOIN kids_materials m ON d.material_id = m.id 
               ORDER BY d.downloaded_at DESC LIMIT 50";
    $recent_downloads = $pdo_kids->query($dl_sql)->fetchAll();

    // C. Subscriptions
    $sub_sql = "SELECT * FROM kids_subscriptions ORDER BY created_at DESC LIMIT 50";
    $subscriptions = $pdo_kids->query($sub_sql)->fetchAll();

    // D. Fetch User Details from Main DB
    // Хоёр өөр бааз тул JOIN хийх боломжгүй, ID-уудыг цуглуулаад Main DB-ээс хайна.
    $user_ids = [];
    foreach ($recent_downloads as $rd) $user_ids[] = $rd['user_id'];
    foreach ($subscriptions as $s) $user_ids[] = $s['user_id'];
    $user_ids = array_unique(array_filter($user_ids));

    $users_map = [];
    if (!empty($user_ids)) {
        $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
        $u_stmt = $pdo->prepare("SELECT id, username, email, avatar_url FROM users WHERE id IN ($placeholders)");
        $u_stmt->execute(array_values($user_ids));
        // ID-аар нь array key болгож авна
        while ($row = $u_stmt->fetch(PDO::FETCH_ASSOC)) {
            $users_map[$row['id']] = $row;
        }
    }

} catch (Exception $e) {
    $error = "Data fetch error: " . $e->getMessage();
}

// --------------------------------------------------------------------------
// 3. MATERIALS LIST FETCHING (Existing Logic)
// --------------------------------------------------------------------------
$search = $_GET['search'] ?? '';
$cat_filter = $_GET['category'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clauses = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(m.title LIKE ?)";
    $params[] = "%$search%";
}
if (!empty($cat_filter)) {
    $where_clauses[] = "m.category_id = ?";
    $params[] = $cat_filter;
}
$where_sql = implode(' AND ', $where_clauses);

// Counts
$count_sql = "SELECT COUNT(*) FROM kids_materials m WHERE $where_sql";
$stmt = $pdo_kids->prepare($count_sql);
$stmt->execute($params);
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// List
$sql = "SELECT m.*, c.name as category_name, c.icon_class 
        FROM kids_materials m 
        LEFT JOIN kids_categories c ON m.category_id = c.id
        WHERE $where_sql 
        ORDER BY m.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo_kids->prepare($sql);
$stmt->execute($params);
$materials = $stmt->fetchAll();

$categories = $pdo_kids->query("SELECT * FROM kids_categories ORDER BY name ASC")->fetchAll();

// Helper to get user info safely
function getUserInfo($uid, $map) {
    if (isset($map[$uid])) {
        return $map[$uid];
    }
    return ['username' => 'Unknown (ID:'.$uid.')', 'email' => '', 'avatar_url' => ''];
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KIDS Удирдлага - FileZone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
    <style>
        .tab-btn.active {
            border-bottom: 2px solid #4F46E5;
            color: #4F46E5;
            font-weight: 600;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body class="font-sans text-slate-800 antialiased bg-slate-50">

    <div class="flex h-screen overflow-hidden">
        
        <?php include 'sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- HEADER -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shadow-sm z-10">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden text-slate-500"><i class="fas fa-bars text-xl"></i></button>
                    <h1 class="text-xl font-bold text-slate-800">KIDS Контент Удирдлага</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded-full font-bold">KIDS DB Connected</span>
                    <span class="text-sm font-medium text-slate-700">Админ</span>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                    </div>
                <?php endif; ?>

                <!-- STATS CARDS -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <!-- Views -->
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wide">Нийт үзэлт</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-1"><?php echo number_format($total_views); ?></h3>
                        </div>
                        <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-eye"></i>
                        </div>
                    </div>
                    <!-- Downloads -->
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wide">Нийт таталт</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-1"><?php echo number_format($total_downloads); ?></h3>
                        </div>
                        <div class="w-10 h-10 bg-green-50 text-green-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-download"></i>
                        </div>
                    </div>
                    <!-- Active Subs -->
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wide">Идэвхтэй эрх</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-1"><?php echo number_format($total_subs); ?></h3>
                        </div>
                        <div class="w-10 h-10 bg-purple-50 text-purple-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-crown"></i>
                        </div>
                    </div>
                    <!-- Revenue -->
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wide">KIDS Орлого</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-1"><?php echo number_format($total_revenue); ?>₮</h3>
                        </div>
                        <div class="w-10 h-10 bg-yellow-50 text-yellow-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-coins"></i>
                        </div>
                    </div>
                </div>

                <!-- TABS -->
                <div class="border-b border-slate-200 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <button onclick="switchTab('materials')" id="tab-materials" class="tab-btn active whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-slate-600 hover:text-slate-800 border-transparent">
                            Материалууд
                        </button>
                        <button onclick="switchTab('downloads')" id="tab-downloads" class="tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-slate-600 hover:text-slate-800 border-transparent">
                            Таталтын түүх
                        </button>
                        <button onclick="switchTab('subscriptions')" id="tab-subscriptions" class="tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-slate-600 hover:text-slate-800 border-transparent">
                            Эрхийн бүртгэл
                        </button>
                    </nav>
                </div>

                <!-- TAB 1: MATERIALS (Existing) -->
                <div id="content-materials" class="tab-content active">
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <form method="GET" class="flex flex-col md:flex-row gap-4 flex-1">
                            <div class="relative flex-1 max-w-md">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Гарчигаар хайх..." class="pl-10 pr-4 py-2 w-full border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <select name="category" class="border border-slate-300 rounded-lg text-sm px-3 py-2 bg-white text-slate-600" onchange="this.form.submit()">
                                <option value="">Бүх ангилал</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $cat_filter == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition">Шүүх</button>
                        </form>
                        <a href="add_kids.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 shadow-sm">
                            <i class="fas fa-plus"></i> <span class="hidden sm:inline">Шинэ материал нэмэх</span>
                        </a>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                        <th class="px-6 py-4 font-semibold">Гарчиг</th>
                                        <th class="px-6 py-4 font-semibold">Ангилал</th>
                                        <th class="px-6 py-4 font-semibold">Статистик</th>
                                        <th class="px-6 py-4 font-semibold">Төлбөр</th>
                                        <th class="px-6 py-4 font-semibold text-right">Үйлдэл</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($materials as $item): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded bg-slate-100 border border-slate-200 overflow-hidden shrink-0">
                                                    <?php if(!empty($item['cover_image'])): ?>
                                                        <img src="../<?php echo htmlspecialchars($item['cover_image']); ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center text-slate-400"><i class="fas fa-image"></i></div>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="text-sm font-semibold text-slate-800"><?php echo htmlspecialchars($item['title']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-slate-100 text-slate-600">
                                                <i class="<?php echo $item['icon_class'] ?? 'fas fa-folder'; ?>"></i> <?php echo htmlspecialchars($item['category_name'] ?? '-'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-xs text-slate-500 flex gap-3">
                                                <span title="Үзэлт"><i class="fas fa-eye text-slate-400 mr-1"></i> <?php echo number_format($item['view_count']); ?></span>
                                                <span title="Таталт"><i class="fas fa-download text-slate-400 mr-1"></i> <?php echo number_format($item['download_count']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($item['is_premium']): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-indigo-100 text-indigo-700">Premium</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-700">Free</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="edit_kids.php?id=<?php echo $item['id']; ?>" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded bg-slate-50"><i class="fas fa-pen"></i></a>
                                                <a href="?delete_id=<?php echo $item['id']; ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded bg-slate-50" onclick="return confirm('Устгах уу? БҮХ ФАЙЛ УСТАНА!')"><i class="fas fa-trash-alt"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if($total_pages > 1): ?>
                        <div class="bg-white px-6 py-4 border-t border-slate-200 flex justify-between items-center">
                            <span class="text-xs text-slate-500">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                            <div class="flex gap-1">
                                <?php for($i=1; $i<=$total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>" class="px-2 py-1 text-xs border rounded <?php echo $i==$page?'bg-indigo-50 border-indigo-500 text-indigo-600':''; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TAB 2: DOWNLOAD HISTORY -->
                <div id="content-downloads" class="tab-content">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                            <h3 class="text-sm font-bold text-slate-700 uppercase">Сүүлийн таталтууд</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                        <th class="px-6 py-4 font-semibold">Хэрэглэгч</th>
                                        <th class="px-6 py-4 font-semibold">Материал</th>
                                        <th class="px-6 py-4 font-semibold text-right">Огноо</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($recent_downloads as $dl): ?>
                                    <?php $u = getUserInfo($dl['user_id'], $users_map); ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <img src="<?php echo !empty($u['avatar_url']) ? '../'.$u['avatar_url'] : 'https://ui-avatars.com/api/?name='.$u['username']; ?>" class="w-8 h-8 rounded-full bg-slate-200">
                                                <div>
                                                    <p class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($u['username']); ?></p>
                                                    <p class="text-xs text-slate-500"><?php echo htmlspecialchars($u['email']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <img src="../<?php echo htmlspecialchars($dl['cover_image']); ?>" class="w-8 h-8 rounded object-cover border border-slate-200">
                                                <span class="text-sm text-slate-700"><?php echo htmlspecialchars($dl['material_title']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm text-slate-500">
                                            <?php echo date('Y-m-d H:i', strtotime($dl['downloaded_at'])); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($recent_downloads)): ?>
                                        <tr><td colspan="3" class="px-6 py-4 text-center text-slate-500">Одоогоор таталт байхгүй байна.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: SUBSCRIPTIONS -->
                <div id="content-subscriptions" class="tab-content">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                            <h3 class="text-sm font-bold text-slate-700 uppercase">Эрхийн бүртгэл (Subscriptions)</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                        <th class="px-6 py-4 font-semibold">Хэрэглэгч</th>
                                        <th class="px-6 py-4 font-semibold">Төлөвлөгөө</th>
                                        <th class="px-6 py-4 font-semibold">Төлсөн дүн</th>
                                        <th class="px-6 py-4 font-semibold">Статус</th>
                                        <th class="px-6 py-4 font-semibold text-right">Эхэлсэн</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($subscriptions as $sub): ?>
                                    <?php $u = getUserInfo($sub['user_id'], $users_map); ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <img src="<?php echo !empty($u['avatar_url']) ? '../'.$u['avatar_url'] : 'https://ui-avatars.com/api/?name='.$u['username']; ?>" class="w-8 h-8 rounded-full bg-slate-200">
                                                <div>
                                                    <p class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($u['username']); ?></p>
                                                    <p class="text-xs text-slate-500"><?php echo htmlspecialchars($u['email']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-indigo-100 text-indigo-700 uppercase">
                                                <?php echo htmlspecialchars($sub['plan_type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-bold text-slate-700">
                                            <?php echo number_format($sub['price_paid']); ?>₮
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if($sub['status'] == 'active'): ?>
                                                <span class="text-green-600 text-xs font-bold bg-green-50 px-2 py-1 rounded">Active</span>
                                            <?php else: ?>
                                                <span class="text-red-600 text-xs font-bold bg-red-50 px-2 py-1 rounded"><?php echo ucfirst($sub['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm text-slate-500">
                                            <?php echo date('Y-m-d', strtotime($sub['start_date'])); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($subscriptions)): ?>
                                        <tr><td colspan="5" class="px-6 py-4 text-center text-slate-500">Бүртгэл олдсонгүй.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
    
    <script src="js/script.js"></script>
    <script>
        function switchTab(tabId) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('active', 'border-indigo-500', 'text-indigo-600');
                el.classList.add('border-transparent');
            });

            // Show selected
            document.getElementById('content-' + tabId).classList.add('active');
            const btn = document.getElementById('tab-' + tabId);
            btn.classList.add('active');
            btn.classList.remove('border-transparent');
        }
    </script>
</body>
</html>