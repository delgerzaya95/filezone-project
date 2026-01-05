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

// 1. Approve Service
if (isset($_GET['approve_id'])) {
    $id = intval($_GET['approve_id']);
    try {
        // Status-ийг 'active' болгож, rejection_reason-ийг NULL болгоно
        $stmt = $pdo->prepare("UPDATE services SET status = 'active', rejection_reason = NULL WHERE id = ?");
        $stmt->execute([$id]);
        
        // Шалгах: Хэрэв мөр өөрчлөгдөөгүй бол (магадгүй ID байхгүй)
        if ($stmt->rowCount() > 0) {
            $_SESSION['message'] = "Үйлчилгээ амжилттай нийтлэгдлээ!";
        } else {
            $_SESSION['error'] = "Өөрчлөлт орсонгүй. ID буруу эсвэл аль хэдийн идэвхтэй байна.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "SQL Алдаа (Approve): " . $e->getMessage();
    }
    header("Location: services.php");
    exit;
}

// 2. Reject Service
if (isset($_GET['reject_id'])) {
    $id = intval($_GET['reject_id']);
    // URL decode хийж, хоосон зайг арилгана. Хоосон байвал NULL биш хоосон string эсвэл анхдагч текст оруулж болно.
    $reason = isset($_GET['reason']) ? trim(urldecode($_GET['reason'])) : ''; 

    try {
        // Status-ийг 'rejected' болгож, шалтгааныг бичнэ.
        // АНХААР: Баазын 'status' багана ENUM('active', 'paused', 'deleted', 'pending', 'rejected') байх ёстой.
        $stmt = $pdo->prepare("UPDATE services SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['message'] = "Үйлчилгээг татгалзлаа.";
        } else {
            // ID олдоогүй эсвэл утга өөрчлөгдөөгүй
             $_SESSION['error'] = "Өөрчлөлт орсонгүй. Магадгүй аль хэдийн татгалзсан эсвэл ID буруу.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "SQL Алдаа (Reject): " . $e->getMessage();
    }
    header("Location: services.php");
    exit;
}

// 3. Delete Service
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    try {
        $stmt = $pdo->prepare("UPDATE services SET status = 'deleted' WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['message'] = "Үйлчилгээ устгагдлаа.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Алдаа: " . $e->getMessage();
    }
    header("Location: services.php");
    exit;
}

// --------------------------------------------------------------------------
// DATA FETCHING
// --------------------------------------------------------------------------

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clauses = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(s.title LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_clauses[] = "s.status = ?";
    $params[] = $status_filter;
}

if ($category_filter > 0) {
    $where_clauses[] = "s.category_id = ?";
    $params[] = $category_filter;
}

$where_sql = implode(' AND ', $where_clauses);

$count_sql = "SELECT COUNT(*) FROM services s LEFT JOIN users u ON s.user_id = u.id WHERE $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Fetch Data
// category_name-ийг service_categories хүснэгтээс авах (Хэрэв байхгүй бол NULL)
// category_id-аар join хийх нь зөв (өмнө нь category_slug байсан бол одоо id болсон)
$sql = "SELECT s.*, u.username, u.avatar_url, sc.name as category_name 
        FROM services s 
        LEFT JOIN users u ON s.user_id = u.id 
        LEFT JOIN service_categories sc ON s.category_id = sc.id
        WHERE $where_sql 
        ORDER BY s.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

// Pending count
$pending_count = $pdo->query("SELECT COUNT(*) FROM services WHERE status = 'pending'")->fetchColumn();

// Categories for filter
try {
    $categories = $pdo->query("SELECT id, name FROM service_categories ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Үйлчилгээ - FileZone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
    <script>
        // Татгалзах үед шалтгаан асуух функц
        function rejectService(id) {
            // Prompt цонх харуулах
            const reason = prompt("Татгалзах шалтгаанаа бичнэ үү (Жишээ нь: Шаардлага хангахгүй байна):");
            
            // Хэрэв Cancel дараагүй бол (reason нь null биш)
            if (reason !== null) { 
                // URL encode хийж тусгай тэмдэгтүүдийг зөв дамжуулах
                const encodedReason = encodeURIComponent(reason);
                window.location.href = "?reject_id=" + id + "&reason=" + encodedReason;
            }
        }
    </script>
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
                    <h1 class="text-xl font-bold text-slate-800">Үйлчилгээний модератор</h1>
                </div>
                <div class="flex items-center gap-3">
                    <?php if($pending_count > 0): ?>
                    <span class="text-xs font-medium text-yellow-700 bg-yellow-100 px-3 py-1 rounded-full border border-yellow-200">
                        Хүлээгдэж буй: <?php echo $pending_count; ?>
                    </span>
                    <?php endif; ?>
                    <div class="relative ml-2">
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

                <!-- Filters Bar -->
                <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <form method="GET" class="flex flex-col md:flex-row gap-4 flex-1">
                        <!-- Search -->
                        <div class="relative flex-1 max-w-md">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Үйлчилгээний нэр эсвэл хэрэглэгчээр хайх..." class="pl-10 pr-4 py-2 w-full border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        
                        <!-- Status Filter -->
                        <select name="status" class="border border-slate-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-slate-600">
                            <option value="">Бүх төлөв</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Хүлээгдэж буй</option>
                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Идэвхтэй</option>
                            <option value="paused" <?php echo $status_filter == 'paused' ? 'selected' : ''; ?>>Зогссон</option>
                            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Татгалзсан</option>
                            <option value="deleted" <?php echo $status_filter == 'deleted' ? 'selected' : ''; ?>>Устгагдсан</option>
                        </select>

                        <!-- Category Filter (By ID) -->
                        <select name="category" class="border border-slate-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-slate-600">
                            <option value="">Бүх ангилал</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition">Шүүх</button>
                    </form>
                    
                    <div class="flex items-center gap-2">
                        <a href="services.php" class="p-2 text-slate-500 hover:text-slate-700 border border-slate-300 rounded-lg bg-white" title="Шинэчлэх">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                        <a href="add_service.php" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg flex items-center shadow-sm transition-colors text-sm">
                            <i class="fas fa-plus mr-2"></i> Нэмэх
                        </a>
                    </div>
                </div>

                <!-- Services Table -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                    <th class="px-6 py-4 font-semibold">Үйлчилгээ</th>
                                    <th class="px-6 py-4 font-semibold">Үзүүлэгч</th>
                                    <th class="px-6 py-4 font-semibold">Ангилал</th>
                                    <th class="px-6 py-4 font-semibold">Үнэ (Эхлэх)</th>
                                    <th class="px-6 py-4 font-semibold">Төлөв</th>
                                    <th class="px-6 py-4 font-semibold text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (count($services) > 0): ?>
                                    <?php foreach ($services as $service): ?>
                                    <?php
                                        // Avatar logic
                                        $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($service['username']) . '&background=random&color=fff';
                                        if (!empty($service['avatar_url']) && file_exists('../' . $service['avatar_url'])) {
                                            $avatar = '../' . $service['avatar_url'];
                                        }
                                        
                                        // Cover Image
                                        $cover = '../assets/images/service-placeholder.jpg';
                                        if (!empty($service['cover_image']) && file_exists('../' . $service['cover_image'])) {
                                            $cover = '../' . $service['cover_image'];
                                        }
                                        
                                        $cat_display = !empty($service['category_name']) ? $service['category_name'] : 'Ангилалгүй';
                                    ?>
                                    <tr id="service-row-<?php echo $service['id']; ?>" class="hover:bg-slate-50 transition-colors group">
                                        <td class="px-6 py-4 w-1/3">
                                            <div class="flex items-start gap-3">
                                                <img src="<?php echo $cover; ?>" alt="" class="w-12 h-12 rounded-lg object-cover border border-slate-200 flex-shrink-0 bg-gray-100">
                                                <div>
                                                    <a href="../service-details.php?id=<?php echo $service['id']; ?>" target="_blank" class="text-sm font-bold text-slate-800 line-clamp-2 hover:text-indigo-600" title="<?php echo htmlspecialchars($service['title']); ?>">
                                                        <?php echo htmlspecialchars($service['title']); ?>
                                                    </a>
                                                    <p class="text-xs text-slate-500 mt-1"><?php echo date('Y-m-d H:i', strtotime($service['created_at'])); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <img src="<?php echo $avatar; ?>" alt="" class="w-6 h-6 rounded-full border border-slate-200">
                                                <span class="text-sm text-slate-700 font-medium"><?php echo htmlspecialchars($service['username']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                                <?php echo htmlspecialchars($cat_display); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-bold text-slate-700">
                                            <?php echo number_format($service['price_min']); ?>₮
                                        </td>
                                        <td class="px-6 py-4 service-status-cell">
                                            <?php if($service['status'] == 'active'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Идэвхтэй
                                                </span>
                                            <?php elseif($service['status'] == 'pending'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200 animate-pulse">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Хүлээгдэж буй
                                                </span>
                                            <?php elseif($service['status'] == 'paused'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span> Зогссон
                                                </span>
                                            <?php elseif($service['status'] == 'rejected'): ?>
                                                <div class="flex flex-col items-start gap-1">
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Татгалзсан
                                                    </span>
                                                    <?php if(!empty($service['rejection_reason'])): ?>
                                                        <span class="text-[10px] text-red-500 max-w-[150px] truncate" title="<?php echo htmlspecialchars($service['rejection_reason']); ?>">
                                                            <?php echo htmlspecialchars($service['rejection_reason']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Устгагдсан
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <!-- View Details -->
                                                <a href="../service-details.php?id=<?php echo $service['id']; ?>" target="_blank" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded hover:bg-indigo-50 transition" title="Дэлгэрэнгүй үзэх">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                <?php if($service['status'] == 'pending'): ?>
                                                    <!-- Approve -->
                                                    <a href="?approve_id=<?php echo $service['id']; ?>" class="p-1.5 text-green-500 hover:text-green-700 bg-green-50 hover:bg-green-100 rounded transition border border-green-200" title="Зөвшөөрөх" onclick="return confirm('Энэ үйлчилгээг нийтлэх үү?')">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <!-- Reject -->
                                                    <button onclick="rejectService(<?php echo $service['id']; ?>)" class="p-1.5 text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 rounded transition border border-red-200" title="Татгалзах">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php elseif($service['status'] == 'rejected'): ?>
                                                    <!-- Re-Approve (Татгалзсан үйлчилгээг буцааж зөвшөөрөх) -->
                                                    <a href="?approve_id=<?php echo $service['id']; ?>" class="p-1.5 text-green-500 hover:text-green-700 bg-green-50 hover:bg-green-100 rounded transition border border-green-200" title="Буцааж зөвшөөрөх" onclick="return confirm('Энэ үйлчилгээг дахин нийтлэх үү?')">
                                                        <i class="fas fa-redo"></i>
                                                    </a>
                                                <?php elseif($service['status'] == 'active'): ?>
                                                    <!-- Deactivate/Reject -->
                                                    <button onclick="rejectService(<?php echo $service['id']; ?>)" class="p-1.5 text-slate-400 hover:text-orange-600 rounded hover:bg-orange-50 transition" title="Татгалзах (Нуух)">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <!-- Delete -->
                                                <a href="?delete_id=<?php echo $service['id']; ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50 transition" title="Устгах" onclick="return confirm('Анхаар! Энэ үйлчилгээг устгах уу?')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                            Үйлчилгээ олдсонгүй.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <div class="bg-white px-6 py-4 border-t border-slate-200 flex items-center justify-between">
                        <span class="text-sm text-slate-500">Нийт <?php echo $total_rows; ?> үйлчилгээнээс <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total_rows); ?> харагдаж байна</span>
                        <div class="flex items-center gap-1">
                            <?php if($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>" class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 text-slate-600">Өмнөх</a>
                            <?php endif; ?>

                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>" class="px-3 py-1 text-sm border <?php echo $i == $page ? 'border-indigo-500 bg-indigo-50 text-indigo-600 font-medium' : 'border-slate-300 rounded hover:bg-slate-50 text-slate-600'; ?> rounded">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>" class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 text-slate-600">Дараах</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>