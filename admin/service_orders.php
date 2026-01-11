<?php
session_start();
require_once '../includes/db.php';

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

    // 1. GET ORDER DETAILS (With Chat & Timeline)
    if (isset($_POST['action']) && $_POST['action'] === 'get_order_details') {
        $id = intval($_POST['id']);
        try {
            // A. Order Info
            $sql = "SELECT so.*, 
                           s.title as service_title, s.cover_image,
                           buyer.username as buyer_name, buyer.email as buyer_email, buyer.phone as buyer_phone,
                           seller.username as seller_name, seller.email as seller_email, seller.phone as seller_phone
                    FROM service_orders so
                    LEFT JOIN services s ON so.service_id = s.id
                    LEFT JOIN users buyer ON so.buyer_id = buyer.id
                    LEFT JOIN users seller ON so.seller_id = seller.id
                    WHERE so.id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                // Formatting
                $order['price_fmt'] = number_format($order['price']) . '₮';
                $order['ordered_at_fmt'] = date('Y-m-d H:i', strtotime($order['ordered_at']));
                $order['cover_image'] = !empty($order['cover_image']) ? '../' . $order['cover_image'] : '../assets/images/service-placeholder.jpg';

                // B. Chat Messages (Communication History)
                $msg_sql = "SELECT m.*, u.username, u.role
                            FROM order_messages m
                            LEFT JOIN users u ON m.sender_id = u.id
                            WHERE m.order_id = ?
                            ORDER BY m.created_at ASC";
                $msg_stmt = $pdo->prepare($msg_sql);
                $msg_stmt->execute([$id]);
                $messages = $msg_stmt->fetchAll(PDO::FETCH_ASSOC);

                // Format messages
                foreach ($messages as &$msg) {
                    $msg['created_at_fmt'] = date('m-d H:i', strtotime($msg['created_at']));
                    $msg['file_url'] = !empty($msg['file_url']) ? '../' . $msg['file_url'] : null;
                }
                $order['messages'] = $messages;

                echo json_encode(['success' => true, 'data' => $order]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Захиалга олдсонгүй.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // 2. UPDATE ORDER STATUS (Admin Intervention)
    if (isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
        $id = intval($_POST['id']);
        $status = $_POST['status']; // active, completed, cancelled
        
        // Маргаан шийдвэрлэх логик (Энд зөвхөн статус өөрчилж байна. Санхүүгийн гүйлгээг transactions дээр тусад нь бүртгэх ёстой)
        // Жишээ нь: Cancelled = Мөнгө буцаалт, Completed = Мөнгө шилжүүлэг
        
        try {
            $time_col = "";
            if ($status === 'completed') $time_col = ", completed_at = NOW()";
            if ($status === 'delivered') $time_col = ", delivered_at = NOW()";

            $sql = "UPDATE service_orders SET status = ? $time_col WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$status, $id]);

            echo json_encode(['success' => true, 'message' => 'Захиалгын төлөв шинэчлэгдлээ.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Алдаа: ' . $e->getMessage()]);
        }
        exit;
    }
}

// --------------------------------------------------------------------------
// PAGE DATA FETCHING
// --------------------------------------------------------------------------

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clauses = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(so.id LIKE ? OR s.title LIKE ? OR buyer.username LIKE ? OR seller.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_clauses[] = "so.status = ?";
    $params[] = $status_filter;
}

$where_sql = implode(' AND ', $where_clauses);

$count_sql = "SELECT COUNT(*) 
              FROM service_orders so 
              LEFT JOIN services s ON so.service_id = s.id 
              LEFT JOIN users buyer ON so.buyer_id = buyer.id 
              LEFT JOIN users seller ON so.seller_id = seller.id 
              WHERE $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT so.*, 
               s.title as service_title, s.cover_image,
               buyer.username as buyer_name, 
               seller.username as seller_name 
        FROM service_orders so 
        LEFT JOIN services s ON so.service_id = s.id 
        LEFT JOIN users buyer ON so.buyer_id = buyer.id 
        LEFT JOIN users seller ON so.seller_id = seller.id 
        WHERE $where_sql 
        ORDER BY so.ordered_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Stats
$stats = [
    'pending' => $pdo->query("SELECT COUNT(*) FROM service_orders WHERE status = 'pending'")->fetchColumn(),
    'active' => $pdo->query("SELECT COUNT(*) FROM service_orders WHERE status = 'active'")->fetchColumn(),
    'dispute' => 0 // Future implementation
];
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Захиалгууд - FileZone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
    <style>
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow-x: hidden; overflow-y: visible !important; }
        .chat-bubble { max-width: 80%; padding: 10px 14px; border-radius: 12px; position: relative; font-size: 0.9rem; }
        .chat-left { background: #f3f4f6; color: #1f2937; border-bottom-left-radius: 2px; }
        .chat-right { background: #e0e7ff; color: #3730a3; border-bottom-right-radius: 2px; margin-left: auto; }
        .chat-meta { font-size: 0.7rem; margin-top: 4px; opacity: 0.7; }
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
                    <h1 class="text-xl font-bold text-slate-800">Захиалгын удирдлага</h1>
                </div>
                <div class="flex items-center gap-3">
                    <?php if($stats['pending'] > 0): ?>
                    <span class="text-xs font-medium text-yellow-700 bg-yellow-100 px-3 py-1 rounded-full border border-yellow-200 animate-pulse">
                        Хүлээгдэж буй: <?php echo $stats['pending']; ?>
                    </span>
                    <?php endif; ?>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                
                <!-- Filters -->
                <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <form method="GET" class="flex flex-col md:flex-row gap-4 flex-1">
                        <div class="relative flex-1 max-w-md">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Захиалгын ID, Үйлчилгээ, Хэрэглэгч..." class="pl-10 pr-4 py-2 w-full border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        
                        <select name="status" class="border border-slate-300 rounded-lg text-sm px-3 py-2 bg-white text-slate-600">
                            <option value="">Бүх төлөв</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending (Хүлээгдэж буй)</option>
                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active (Явагдаж буй)</option>
                            <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered (Хүлээлгэж өгсөн)</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed (Дууссан)</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled (Цуцлагдсан)</option>
                        </select>

                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition">Шүүх</button>
                    </form>
                    
                    <a href="service_orders.php" class="p-2 text-slate-500 hover:text-slate-700 border border-slate-300 rounded-lg bg-white" title="Шинэчлэх">
                        <i class="fas fa-sync-alt"></i>
                    </a>
                </div>

                <!-- Orders Table -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                    <th class="px-6 py-4 font-semibold">ID</th>
                                    <th class="px-6 py-4 font-semibold">Үйлчилгээ</th>
                                    <th class="px-6 py-4 font-semibold">Захиалагч / Гүйцэтгэгч</th>
                                    <th class="px-6 py-4 font-semibold">Үнэ</th>
                                    <th class="px-6 py-4 font-semibold">Төлөв</th>
                                    <th class="px-6 py-4 font-semibold text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (count($orders) > 0): ?>
                                    <?php foreach ($orders as $order): 
                                        $cover = !empty($order['cover_image']) && file_exists('../' . $order['cover_image']) ? '../' . $order['cover_image'] : '../assets/images/service-placeholder.jpg';
                                    ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 text-sm font-mono text-slate-500">#<?php echo $order['id']; ?></td>
                                        <td class="px-6 py-4 w-1/3">
                                            <div class="flex items-start gap-3">
                                                <img src="<?php echo $cover; ?>" class="w-10 h-10 rounded-lg object-cover border border-slate-200 bg-gray-100 shrink-0">
                                                <div>
                                                    <p class="text-sm font-bold text-slate-800 line-clamp-1" title="<?php echo htmlspecialchars($order['service_title']); ?>">
                                                        <?php echo htmlspecialchars($order['service_title']); ?>
                                                    </p>
                                                    <p class="text-xs text-slate-500 mt-0.5"><?php echo date('Y-m-d H:i', strtotime($order['ordered_at'])); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-xs text-slate-600">
                                                <div class="flex items-center gap-1 mb-1"><span class="w-12 text-slate-400">Buyer:</span> <span class="font-medium"><?php echo htmlspecialchars($order['buyer_name']); ?></span></div>
                                                <div class="flex items-center gap-1"><span class="w-12 text-slate-400">Seller:</span> <span class="font-medium text-indigo-600"><?php echo htmlspecialchars($order['seller_name']); ?></span></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-bold text-slate-700">
                                            <?php echo number_format($order['price']); ?>₮
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php 
                                                $status_classes = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                                    'active' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                    'delivered' => 'bg-purple-100 text-purple-800 border-purple-200',
                                                    'completed' => 'bg-green-100 text-green-800 border-green-200',
                                                    'cancelled' => 'bg-red-100 text-red-800 border-red-200'
                                                ];
                                                $s_class = $status_classes[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border <?php echo $s_class; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button onclick="openOrderModal(<?php echo $order['id']; ?>)" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded transition">
                                                Хянах
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">Захиалга олдсонгүй.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="mt-4 flex justify-end gap-2 px-6 pb-4">
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>" class="px-3 py-1 text-sm border rounded <?php echo $i == $page ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <!-- ORDER DETAILS & CHAT MODAL -->
    <div id="orderModal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        
        <div class="modal-container bg-white w-11/12 md:max-w-5xl mx-auto rounded-xl shadow-xl z-50 overflow-hidden flex flex-col h-[85vh]">
            <!-- Header -->
            <div class="flex justify-between items-center px-6 py-4 border-b bg-gray-50">
                <div>
                    <p class="text-lg font-bold text-slate-800">Захиалга #<span id="modalOrderId"></span></p>
                    <p class="text-xs text-slate-500">Админ хяналтын цонх</p>
                </div>
                <div class="modal-close cursor-pointer text-slate-500 hover:text-slate-800" onclick="closeModal()">
                    <i class="fas fa-times text-xl"></i>
                </div>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-hidden flex flex-col md:flex-row">
                
                <!-- Left: Order Info & Actions -->
                <div class="w-full md:w-1/3 bg-white border-r border-slate-200 overflow-y-auto p-6">
                    <div id="orderInfoContent" class="space-y-6">
                        <!-- Content loaded via AJAX -->
                        <div class="flex justify-center py-10"><i class="fas fa-spinner fa-spin text-2xl text-indigo-500"></i></div>
                    </div>

                    <!-- Admin Actions -->
                    <div class="mt-8 pt-6 border-t border-slate-100">
                        <h4 class="text-xs font-bold text-slate-400 uppercase mb-3">Маргаан шийдвэрлэх / Удирдах</h4>
                        <div class="space-y-2">
                            <button onclick="updateOrderStatus('completed')" class="w-full text-left px-4 py-3 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition flex items-center justify-between group">
                                <span class="text-sm font-medium">Захиалгыг дуусгах</span>
                                <i class="fas fa-check-circle opacity-0 group-hover:opacity-100"></i>
                            </button>
                            <p class="text-[10px] text-slate-400 mb-2 px-1">Мөнгийг гүйцэтгэгчид шилжүүлнэ.</p>

                            <button onclick="updateOrderStatus('cancelled')" class="w-full text-left px-4 py-3 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition flex items-center justify-between group">
                                <span class="text-sm font-medium">Захиалгыг цуцлах</span>
                                <i class="fas fa-ban opacity-0 group-hover:opacity-100"></i>
                            </button>
                            <p class="text-[10px] text-slate-400 px-1">Мөнгийг захиалагчид буцаана.</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Chat History -->
                <div class="w-full md:w-2/3 bg-slate-50 flex flex-col">
                    <div class="px-6 py-3 border-b border-slate-200 bg-white flex justify-between items-center">
                        <h3 class="font-bold text-slate-700 flex items-center gap-2"><i class="far fa-comments"></i> Харилцан яриа</h3>
                        <span class="text-xs text-slate-400 bg-slate-100 px-2 py-1 rounded">Зөвхөн унших эрхтэй</span>
                    </div>
                    
                    <div id="chatContainer" class="flex-1 overflow-y-auto p-6 space-y-4">
                        <!-- Chat messages loaded via AJAX -->
                        <div class="text-center text-slate-400 text-sm mt-10">Түүх байхгүй байна.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('orderModal');
        let currentOrderId = 0;

        function openOrderModal(id) {
            currentOrderId = id;
            document.getElementById('modalOrderId').innerText = id;
            modal.classList.remove('opacity-0', 'pointer-events-none');
            document.body.classList.add('modal-active');
            
            loadOrderDetails(id);
        }

        function closeModal() {
            modal.classList.add('opacity-0', 'pointer-events-none');
            document.body.classList.remove('modal-active');
        }

        function loadOrderDetails(id) {
            const formData = new FormData();
            formData.append('action', 'get_order_details');
            formData.append('id', id);

            fetch('service_orders.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    const d = res.data;
                    
                    // 1. Render Order Info
                    let statusColor = d.status === 'active' ? 'text-blue-600 bg-blue-50' : (d.status === 'completed' ? 'text-green-600 bg-green-50' : 'text-slate-600 bg-slate-100');
                    
                    document.getElementById('orderInfoContent').innerHTML = `
                        <div>
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase ${statusColor} mb-2">${d.status}</span>
                            <h2 class="text-lg font-bold text-slate-800 leading-tight mb-1">${d.service_title}</h2>
                            <p class="text-sm text-slate-500">${d.ordered_at_fmt}</p>
                        </div>
                        
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-slate-500">Үнэ</span>
                                <span class="text-lg font-bold text-indigo-600">${d.price_fmt}</span>
                            </div>
                            <div class="h-px bg-slate-200 my-2"></div>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-xs text-slate-400 uppercase">Захиалагч</p>
                                    <p class="font-medium text-slate-700">${d.buyer_name}</p>
                                    <p class="text-xs text-slate-500">${d.buyer_email}</p>
                                    <p class="text-xs text-slate-500">${d.buyer_phone || '-'}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 uppercase">Гүйцэтгэгч</p>
                                    <p class="font-medium text-slate-700">${d.seller_name}</p>
                                    <p class="text-xs text-slate-500">${d.seller_email}</p>
                                    <p class="text-xs text-slate-500">${d.seller_phone || '-'}</p>
                                </div>
                            </div>
                        </div>

                        ${d.requirements_submitted ? `
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase mb-2">Шаардлага / Тайлбар</p>
                            <div class="text-sm text-slate-600 bg-white border border-slate-200 p-3 rounded-lg max-h-32 overflow-y-auto">
                                ${d.requirements_submitted}
                            </div>
                        </div>` : ''}
                    `;

                    // 2. Render Chat
                    const chatContainer = document.getElementById('chatContainer');
                    if (d.messages && d.messages.length > 0) {
                        chatContainer.innerHTML = d.messages.map(msg => {
                            const isBuyer = msg.sender_id == d.buyer_id;
                            const bubbleClass = isBuyer ? 'chat-left' : 'chat-right';
                            const roleLabel = isBuyer ? 'Захиалагч' : 'Гүйцэтгэгч';
                            
                            let fileHtml = '';
                            if (msg.file_url) {
                                const ext = msg.file_url.split('.').pop().toLowerCase();
                                if(['jpg','jpeg','png'].includes(ext)) {
                                    fileHtml = `<a href="${msg.file_url}" target="_blank"><img src="${msg.file_url}" class="mt-2 rounded-lg max-w-[200px] max-h-32 object-cover border border-slate-300"></a>`;
                                } else {
                                    fileHtml = `<a href="${msg.file_url}" target="_blank" class="mt-2 flex items-center gap-2 text-indigo-600 bg-white p-2 rounded border text-xs"><i class="fas fa-paperclip"></i> Файл татах</a>`;
                                }
                            }

                            return `
                                <div class="w-full flex flex-col ${isBuyer ? 'items-start' : 'items-end'}">
                                    <div class="chat-bubble ${bubbleClass}">
                                        <p>${msg.message}</p>
                                        ${fileHtml}
                                    </div>
                                    <div class="chat-meta text-slate-400">
                                        <span class="font-bold">${msg.username}</span> (${roleLabel}) • ${msg.created_at_fmt}
                                    </div>
                                </div>
                            `;
                        }).join('');
                    } else {
                        chatContainer.innerHTML = '<div class="flex h-full items-center justify-center text-slate-400 flex-col"><i class="far fa-comments text-4xl mb-2 opacity-50"></i><p>Харилцан яриа эхлээгүй байна.</p></div>';
                    }
                } else {
                    alert(res.message);
                }
            })
            .catch(() => alert('Сүлжээний алдаа.'));
        }

        function updateOrderStatus(status) {
            let msg = status === 'completed' ? 
                'Захиалгыг ДУУСГАХ уу? Мөнгө гүйцэтгэгчид шилжинэ.' : 
                'Захиалгыг ЦУЦЛАХ уу? Мөнгө захиалагчид буцна.';
            
            if(!confirm(msg)) return;

            const formData = new FormData();
            formData.append('action', 'update_order_status');
            formData.append('id', currentOrderId);
            formData.append('status', status);

            fetch('service_orders.php', { method: 'POST', body: formData })
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

        // Close modal on overlay click
        document.querySelector('.modal-overlay').addEventListener('click', closeModal);
    </script>
</body>
</html>