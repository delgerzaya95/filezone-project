<?php
session_start();
require_once '../includes/db.php';
// МЭЙЛ API ДУУДАХ
require_once 'api/brevo_admin.php'; 

// Админ эрх шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --------------------------------------------------------------------------
// AJAX HANDLERS
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // 1. GET SERVICE DETAILS (Modal-д харуулах)
    if (isset($_POST['action']) && $_POST['action'] === 'get_details') {
        $id = intval($_POST['id']);
        try {
            $sql = "SELECT s.*, u.username, u.email, u.phone, u.avatar_url, sc.name as category_name
                    FROM services s
                    LEFT JOIN users u ON s.user_id = u.id
                    LEFT JOIN service_categories sc ON s.category_id = sc.id
                    WHERE s.id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($service) {
                $service['price_min_fmt'] = number_format($service['price_min']) . '₮';
                $service['price_max_fmt'] = $service['price_max'] ? number_format($service['price_max']) . '₮' : '-';
                $service['created_at_fmt'] = date('Y-m-d H:i', strtotime($service['created_at']));
                
                if (empty($service['avatar_url']) || !file_exists('../' . $service['avatar_url'])) {
                    $service['avatar_url'] = 'https://ui-avatars.com/api/?name=' . urlencode($service['username']) . '&background=random&color=fff';
                } else {
                    $service['avatar_url'] = '../' . $service['avatar_url'];
                }

                if (!empty($service['cover_image'])) {
                    $service['cover_image'] = '../' . $service['cover_image'];
                }

                echo json_encode(['success' => true, 'data' => $service]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Үйлчилгээ олдсонгүй.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // 2. UPDATE STATUS
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $id = intval($_POST['id']);
        $status = $_POST['status'];
        $reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;

        try {
            // Мэйл илгээхийн тулд мэдээлэл татах
            $stmtInfo = $pdo->prepare("
                SELECT s.title, s.user_id, s.status as old_status, u.email, u.username 
                FROM services s 
                JOIN users u ON s.user_id = u.id 
                WHERE s.id = ?
            ");
            $stmtInfo->execute([$id]);
            $serviceInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            if (!$serviceInfo) {
                echo json_encode(['success' => false, 'message' => 'Үйлчилгээ олдсонгүй.']);
                exit;
            }

            // Update DB
            $sql = "UPDATE services SET status = ?, rejection_reason = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$status, $reason, $id]);

            // Email Notification
            if ($status !== $serviceInfo['old_status']) {
                if ($status === 'active') {
                    notifyServiceApproved($serviceInfo['email'], $serviceInfo['username'], $serviceInfo['title'], $id);
                } elseif ($status === 'rejected') {
                    notifyServiceRejected($serviceInfo['email'], $serviceInfo['username'], $serviceInfo['title'], $reason);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Төлөв амжилттай шинэчлэгдлээ.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Алдаа: ' . $e->getMessage()]);
        }
        exit;
    }
}

// --------------------------------------------------------------------------
// PAGE DATA FETCHING & FILTERING
// --------------------------------------------------------------------------

// Filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Query Construction
$where_clauses = ["1=1"];
$params = [];

// 1. Text Search
if (!empty($search)) {
    $where_clauses[] = "(s.title LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// 2. Status Filter
if (!empty($status_filter)) {
    $where_clauses[] = "s.status = ?";
    $params[] = $status_filter;
}

// 3. Category Filter
if ($category_filter > 0) {
    $where_clauses[] = "s.category_id = ?";
    $params[] = $category_filter;
}

// 4. Date Range Filter
if (!empty($date_from)) {
    $where_clauses[] = "DATE(s.created_at) >= ?";
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $where_clauses[] = "DATE(s.created_at) <= ?";
    $params[] = $date_to;
}

$where_sql = implode(' AND ', $where_clauses);

// Count Query
$count_sql = "SELECT COUNT(*) FROM services s LEFT JOIN users u ON s.user_id = u.id WHERE $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Main Query
$sql = "SELECT s.*, u.username, u.avatar_url, sc.name as category_name 
        FROM services s 
        LEFT JOIN users u ON s.user_id = u.id 
        LEFT JOIN service_categories sc ON s.category_id = sc.id
        WHERE $where_sql 
        ORDER BY FIELD(s.status, 'pending', 'active', 'paused', 'rejected', 'deleted'), s.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

// Pending count for badge
$pending_count = $pdo->query("SELECT COUNT(*) FROM services WHERE status = 'pending'")->fetchColumn();

// Categories for dropdown
$categories = $pdo->query("SELECT id, name FROM service_categories ORDER BY name ASC")->fetchAll();
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
    <script src="js/tailwind-config.js"></script>
    <style>
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow-x: hidden; overflow-y: visible !important; }
        .prose img { border-radius: 8px; max-width: 100%; }
        .prose ul { list-style-type: disc; padding-left: 20px; }
    </style>
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
                    <span class="text-xs font-medium text-yellow-700 bg-yellow-100 px-3 py-1 rounded-full border border-yellow-200 animate-pulse">
                        Хүлээгдэж буй: <?php echo $pending_count; ?>
                    </span>
                    <?php endif; ?>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                
                <!-- Expanded Filters Bar -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        
                        <!-- Search -->
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase">Хайлт</label>
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Үйлчилгээ, хэрэглэгчийн нэр, мэйл..." class="pl-10 pr-4 py-2 w-full border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase">Төлөв</label>
                            <select name="status" class="w-full border border-slate-300 rounded-lg text-sm px-3 py-2 bg-white text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Бүх төлөв</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>⏳ Хүлээгдэж буй</option>
                                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>✅ Идэвхтэй</option>
                                <option value="paused" <?php echo $status_filter == 'paused' ? 'selected' : ''; ?>>⏸ Түр зогссон</option>
                                <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>❌ Татгалзсан</option>
                                <option value="deleted" <?php echo $status_filter == 'deleted' ? 'selected' : ''; ?>>🗑 Устгагдсан</option>
                            </select>
                        </div>

                        <!-- Category Filter -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase">Ангилал</label>
                            <select name="category" class="w-full border border-slate-300 rounded-lg text-sm px-3 py-2 bg-white text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Бүх ангилал</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
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

                        <!-- Action Buttons -->
                        <div class="col-span-1 md:col-span-2 flex items-end gap-2">
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition shadow-sm font-medium">
                                <i class="fas fa-filter mr-1"></i> Шүүх
                            </button>
                            <a href="services.php" class="px-4 py-2 text-slate-600 bg-white border border-slate-300 rounded-lg text-sm hover:bg-slate-50 transition font-medium">
                                <i class="fas fa-undo mr-1"></i> Цэвэрлэх
                            </a>
                            <a href="add_service.php" class="ml-auto px-4 py-2 text-white bg-green-600 hover:bg-green-700 border border-green-600 rounded-lg text-sm transition font-medium">
                                <i class="fas fa-plus mr-1"></i> Нэмэх
                            </a>
                        </div>
                    </form>
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
                                    <th class="px-6 py-4 font-semibold">Үнэ</th>
                                    <th class="px-6 py-4 font-semibold">Төлөв</th>
                                    <th class="px-6 py-4 font-semibold text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (count($services) > 0): ?>
                                    <?php foreach ($services as $service): 
                                        $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($service['username']) . '&background=random&color=fff';
                                        if (!empty($service['avatar_url']) && file_exists('../' . $service['avatar_url'])) {
                                            $avatar = '../' . $service['avatar_url'];
                                        }
                                        $cover = !empty($service['cover_image']) && file_exists('../' . $service['cover_image']) ? '../' . $service['cover_image'] : '../assets/images/service-placeholder.jpg';
                                    ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 w-1/3">
                                            <div class="flex items-start gap-3">
                                                <img src="<?php echo $cover; ?>" class="w-12 h-12 rounded-lg object-cover border border-slate-200 bg-gray-100 shrink-0">
                                                <div>
                                                    <p class="text-sm font-bold text-slate-800 line-clamp-2 cursor-pointer hover:text-indigo-600" onclick="openServiceModal(<?php echo $service['id']; ?>)">
                                                        <?php echo htmlspecialchars($service['title']); ?>
                                                    </p>
                                                    <p class="text-xs text-slate-500 mt-1"><?php echo date('Y-m-d H:i', strtotime($service['created_at'])); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <img src="<?php echo $avatar; ?>" class="w-6 h-6 rounded-full border border-slate-200">
                                                <span class="text-sm text-slate-700"><?php echo htmlspecialchars($service['username']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 rounded text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                                <?php echo htmlspecialchars($service['category_name'] ?? 'Ангилалгүй'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-bold text-slate-700">
                                            <?php echo number_format($service['price_min']); ?>₮
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if($service['status'] == 'active'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">Идэвхтэй</span>
                                            <?php elseif($service['status'] == 'pending'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200 animate-pulse">Хүлээгдэж буй</span>
                                            <?php elseif($service['status'] == 'paused'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">Түр зогссон</span>
                                            <?php elseif($service['status'] == 'rejected'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">Татгалзсан</span>
                                            <?php elseif($service['status'] == 'deleted'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-200 text-slate-500 border border-slate-300">Устгагдсан</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button onclick="openServiceModal(<?php echo $service['id']; ?>)" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                                Дэлгэрэнгүй
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">Үйлчилгээ олдсонгүй.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-4 flex justify-end gap-2">
                    <?php for($i = 1; $i <= $total_pages; $i++): 
                        $query_params = $_GET;
                        $query_params['page'] = $i;
                        $link = '?' . http_build_query($query_params);
                    ?>
                        <a href="<?php echo $link; ?>" class="px-3 py-1 text-sm border rounded <?php echo $i == $page ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <!-- SERVICE DETAILS MODAL -->
    <div id="serviceModal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        
        <div class="modal-container bg-white w-11/12 md:max-w-4xl mx-auto rounded-xl shadow-xl z-50 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="flex justify-between items-center px-6 py-4 border-b bg-gray-50">
                <p class="text-lg font-bold text-slate-800">Үйлчилгээний дэлгэрэнгүй</p>
                <div class="modal-close cursor-pointer text-slate-500 hover:text-slate-800" onclick="closeModal()">
                    <i class="fas fa-times text-xl"></i>
                </div>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-6 bg-white" id="modalContent">
                <div class="flex justify-center items-center h-40">
                    <i class="fas fa-spinner fa-spin text-3xl text-indigo-500"></i>
                </div>
            </div>

            <!-- Footer (Actions) -->
            <div class="flex justify-between items-center px-6 py-4 border-t bg-gray-50" id="modalFooter">
                <button onclick="updateStatus('deleted')" class="text-red-600 hover:text-red-800 text-sm font-medium"><i class="fas fa-trash-alt mr-1"></i> Устгах</button>
                <div class="flex gap-3">
                    <button onclick="showRejectInput()" class="px-4 py-2 bg-white border border-red-300 text-red-600 rounded-lg hover:bg-red-50 font-medium transition" id="btnReject">Татгалзах</button>
                    <button onclick="updateStatus('active')" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition shadow-sm" id="btnApprove">Зөвшөөрөх</button>
                </div>
            </div>
            
            <!-- Reject Reason Input (Hidden by default) -->
            <div id="rejectInputArea" class="hidden px-6 py-4 bg-red-50 border-t border-red-100">
                <label class="block text-sm font-medium text-red-800 mb-2">Татгалзах шалтгаан:</label>
                <textarea id="rejectReasonText" class="w-full border border-red-300 rounded-lg p-2 text-sm focus:ring-red-500 focus:border-red-500" rows="3" placeholder="Яагаад татгалзаж байгааг бичнэ үү..."></textarea>
                <div class="flex justify-end gap-2 mt-3">
                    <button onclick="hideRejectInput()" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-200 rounded">Болих</button>
                    <button onclick="updateStatus('rejected')" class="px-3 py-1.5 text-xs bg-red-600 text-white rounded hover:bg-red-700 font-bold">Илгээх</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('serviceModal');
        const content = document.getElementById('modalContent');
        let currentServiceId = 0;

        function openServiceModal(id) {
            currentServiceId = id;
            modal.classList.remove('opacity-0', 'pointer-events-none');
            document.body.classList.add('modal-active');
            
            // Reset UI
            content.innerHTML = '<div class="flex justify-center items-center h-40"><i class="fas fa-spinner fa-spin text-3xl text-indigo-500"></i></div>';
            document.getElementById('rejectInputArea').classList.add('hidden');
            document.getElementById('modalFooter').classList.remove('hidden');

            const formData = new FormData();
            formData.append('action', 'get_details');
            formData.append('id', id);

            fetch('services.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    const d = res.data;
                    let statusBadge = '';
                    if(d.status === 'pending') statusBadge = '<span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">Хүлээгдэж буй</span>';
                    else if(d.status === 'active') statusBadge = '<span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Идэвхтэй</span>';
                    else if(d.status === 'paused') statusBadge = '<span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">Түр зогссон</span>';
                    else if(d.status === 'rejected') statusBadge = '<span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">Татгалзсан</span>';
                    else if(d.status === 'deleted') statusBadge = '<span class="bg-slate-200 text-slate-500 px-2 py-1 rounded text-xs">Устгагдсан</span>';

                    let coverHtml = d.cover_image ? `<img src="${d.cover_image}" class="w-full h-48 object-cover rounded-xl mb-4 border border-slate-200">` : '';

                    content.innerHTML = `
                        <div class="flex flex-col lg:flex-row gap-6">
                            <div class="flex-1">
                                ${coverHtml}
                                <div class="flex justify-between items-start mb-2">
                                    <h2 class="text-xl font-bold text-slate-800">${d.title}</h2>
                                    ${statusBadge}
                                </div>
                                <div class="text-sm text-slate-500 mb-4 flex gap-4">
                                    <span><i class="fas fa-folder mr-1"></i> ${d.category_name || 'Ангилалгүй'}</span>
                                    <span><i class="far fa-clock mr-1"></i> ${d.created_at_fmt}</span>
                                </div>
                                
                                <div class="prose prose-sm max-w-none text-slate-600 bg-slate-50 p-4 rounded-lg border border-slate-100 mb-4">
                                    ${d.description}
                                </div>
                                
                                ${d.rejection_reason ? `<div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg text-sm mb-4"><strong>Татгалзсан шалтгаан:</strong> ${d.rejection_reason}</div>` : ''}
                            </div>
                            
                            <div class="w-full lg:w-72 space-y-4">
                                <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                                    <h3 class="font-bold text-slate-800 mb-3">Үнэ & Хугацаа</h3>
                                    <div class="flex justify-between mb-2">
                                        <span class="text-slate-500 text-sm">Доод үнэ:</span>
                                        <span class="font-bold text-indigo-600">${d.price_min_fmt}</span>
                                    </div>
                                    <div class="flex justify-between mb-2">
                                        <span class="text-slate-500 text-sm">Дээд үнэ:</span>
                                        <span class="font-bold text-slate-700">${d.price_max_fmt}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 text-sm">Гүйцэтгэх:</span>
                                        <span class="font-medium text-slate-700">${d.delivery_time} ${d.delivery_unit}</span>
                                    </div>
                                </div>

                                <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                                    <h3 class="font-bold text-slate-800 mb-3">Үйлчилгээ үзүүлэгч</h3>
                                    <div class="flex items-center gap-3">
                                        <img src="${d.avatar_url}" class="w-10 h-10 rounded-full border">
                                        <div>
                                            <p class="text-sm font-bold text-slate-700">${d.username}</p>
                                            <p class="text-xs text-slate-500">${d.email}</p>
                                            <p class="text-xs text-slate-500">${d.phone || 'Утасгүй'}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    content.innerHTML = `<div class="text-center text-red-500 py-10">${res.message}</div>`;
                }
            })
            .catch(err => {
                content.innerHTML = `<div class="text-center text-red-500 py-10">Сүлжээний алдаа гарлаа.</div>`;
            });
        }

        function closeModal() {
            modal.classList.add('opacity-0', 'pointer-events-none');
            document.body.classList.remove('modal-active');
        }

        function showRejectInput() {
            document.getElementById('modalFooter').classList.add('hidden');
            document.getElementById('rejectInputArea').classList.remove('hidden');
        }

        function hideRejectInput() {
            document.getElementById('rejectInputArea').classList.add('hidden');
            document.getElementById('modalFooter').classList.remove('hidden');
        }

        function updateStatus(status) {
            let reason = '';
            if(status === 'rejected') {
                reason = document.getElementById('rejectReasonText').value;
                if(!reason) { alert('Татгалзах шалтгаанаа бичнэ үү.'); return; }
            }
            
            if(status === 'deleted' && !confirm('Энэ үйлчилгээг устгахдаа итгэлтэй байна уу?')) return;

            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('id', currentServiceId);
            formData.append('status', status);
            if(reason) formData.append('reason', reason);

            fetch('services.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert('Алдаа: ' + res.message);
                }
            })
            .catch(() => alert('Сүлжээний алдаа.'));
        }

        document.querySelector('.modal-overlay').addEventListener('click', closeModal);
    </script>
</body>
</html>