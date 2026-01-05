<?php
session_start();

// Database холболт
require_once '../includes/db.php';

// Админ эрх шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// --------------------------------------------------------------------------
// ACTION HANDLERS
// --------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. ШИНЭ ЗАХИАЛГА ҮҮСГЭХ
    if (isset($_POST['create_order'])) {
        $service_id = intval($_POST['service_id']);
        $buyer_id = intval($_POST['buyer_id']);
        $custom_price = !empty($_POST['price']) ? floatval($_POST['price']) : null;
        $status = $_POST['status'];

        if ($service_id && $buyer_id) {
            try {
                // Үйлчилгээний мэдээллийг татах (Seller ID болон Үнэ авах)
                $stmt = $pdo->prepare("SELECT user_id as seller_id, price_min FROM services WHERE id = ?");
                $stmt->execute([$service_id]);
                $service = $stmt->fetch();

                if ($service) {
                    $seller_id = $service['seller_id'];
                    $price = $custom_price ? $custom_price : $service['price_min']; // Хэрэв үнэ гараар оруулаагүй бол үндсэн үнийг авна

                    // Өөрийнхөө үйлчилгээг өөрөө захиалахыг хориглох (optional)
                    if ($buyer_id == $seller_id) {
                        $error = "Хэрэглэгч өөрийн үйлчилгээг захиалах боломжгүй.";
                    } else {
                        $sql = "INSERT INTO service_orders (service_id, buyer_id, seller_id, price, status, ordered_at) 
                                VALUES (?, ?, ?, ?, ?, NOW())";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$service_id, $buyer_id, $seller_id, $price, $status]);
                        
                        $message = "Захиалга амжилттай үүсгэгдлээ.";
                    }
                } else {
                    $error = "Сонгосон үйлчилгээ олдсонгүй.";
                }
            } catch (PDOException $e) {
                $error = "Захиалга үүсгэхэд алдаа гарлаа: " . $e->getMessage();
            }
        } else {
            $error = "Үйлчилгээ болон Захиалагчийг сонгоно уу.";
        }
    }

    // 2. ЗАХИАЛГА УСТГАХ
    if (isset($_POST['delete_order'])) {
        $id = intval($_POST['id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM service_orders WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Захиалга устгагдлаа.";
        } catch (PDOException $e) {
            $error = "Устгахад алдаа гарлаа: " . $e->getMessage();
        }
    }

    // 3. ТӨЛӨВ ӨӨРЧЛӨХ (Update Status)
    if (isset($_POST['update_status'])) {
        $id = intval($_POST['order_id']);
        $new_status = $_POST['status'];
        
        try {
            // Төлөвөөс хамаарч цагийг шинэчлэх
            $time_column = "";
            if ($new_status == 'delivered') {
                $time_column = ", delivered_at = NOW()";
            } elseif ($new_status == 'completed') {
                $time_column = ", completed_at = NOW()";
            }

            $sql = "UPDATE service_orders SET status = ? $time_column WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$new_status, $id]);
            $message = "Захиалгын төлөв шинэчлэгдлээ.";
        } catch (PDOException $e) {
            $error = "Төлөв өөрчлөхөд алдаа гарлаа: " . $e->getMessage();
        }
    }
}

// --------------------------------------------------------------------------
// DATA FETCHING
// --------------------------------------------------------------------------

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clauses = ["1=1"];
$params = [];

// Search Filter
if (!empty($search)) {
    $where_clauses[] = "(so.id LIKE ? OR s.title LIKE ? OR buyer.username LIKE ? OR seller.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Status Filter
if (!empty($status_filter)) {
    $where_clauses[] = "so.status = ?";
    $params[] = $status_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// Count Total
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

// Fetch Orders
$sql = "SELECT so.*, 
               s.title as service_title, s.cover_image,
               buyer.username as buyer_name, buyer.avatar_url as buyer_avatar,
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

// Fetch Services & Users for Modal (Dropdowns)
$services_list = $pdo->query("SELECT id, title, price_min, user_id FROM services WHERE status = 'active' ORDER BY created_at DESC")->fetchAll();
$users_list = $pdo->query("SELECT id, username, email FROM users ORDER BY username ASC")->fetchAll();

// Statistics
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM service_orders")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM service_orders WHERE status = 'pending'")->fetchColumn(),
    'active' => $pdo->query("SELECT COUNT(*) FROM service_orders WHERE status = 'active'")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM service_orders WHERE status = 'completed'")->fetchColumn(),
    'revenue' => $pdo->query("SELECT SUM(price) FROM service_orders WHERE status = 'completed'")->fetchColumn()
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
                    <h1 class="text-xl font-bold text-slate-800">Захиалгын удирдлага</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-slate-700">Админ</span>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                
                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $message; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Нийт захиалга</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['total']); ?></p>
                        </div>
                        <div class="bg-blue-50 text-blue-600 p-3 rounded-full"><i class="fas fa-shopping-cart text-xl"></i></div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Идэвхтэй (Active)</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['active']); ?></p>
                        </div>
                        <div class="bg-indigo-50 text-indigo-600 p-3 rounded-full"><i class="fas fa-spinner text-xl"></i></div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Амжилттай</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['completed']); ?></p>
                        </div>
                        <div class="bg-green-50 text-green-600 p-3 rounded-full"><i class="fas fa-check-circle text-xl"></i></div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Нийт орлого (Дууссан)</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['revenue'] ?? 0); ?>₮</p>
                        </div>
                        <div class="bg-yellow-50 text-yellow-600 p-3 rounded-full"><i class="fas fa-coins text-xl"></i></div>
                    </div>
                </div>

                <!-- Filters & Table -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
                    
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <div class="flex flex-col md:flex-row gap-4 w-full md:w-auto">
                            <form method="GET" class="flex gap-2 w-full">
                                <div class="relative flex-1 md:w-64">
                                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
                                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ID, Үйлчилгээ, Хэрэглэгч..." class="pl-10 pr-4 py-2 w-full border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                </div>
                                <select name="status" class="border border-slate-300 rounded-lg text-sm px-3 py-2 bg-white text-slate-700 focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Бүх төлөв</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">Шүүх</button>
                            </form>
                        </div>

                        <!-- Create Order Button -->
                        <button onclick="openCreateModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 shadow-sm transition">
                            <i class="fas fa-plus"></i> Захиалга үүсгэх
                        </button>
                    </div>

                    <!-- Orders Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                    <th class="px-6 py-4 font-semibold">ID</th>
                                    <th class="px-6 py-4 font-semibold">Үйлчилгээ</th>
                                    <th class="px-6 py-4 font-semibold">Захиалагч (Buyer)</th>
                                    <th class="px-6 py-4 font-semibold">Гүйцэтгэгч (Seller)</th>
                                    <th class="px-6 py-4 font-semibold">Үнэ</th>
                                    <th class="px-6 py-4 font-semibold">Төлөв</th>
                                    <th class="px-6 py-4 font-semibold text-right">Огноо</th>
                                    <th class="px-6 py-4 font-semibold text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (count($orders) > 0): ?>
                                    <?php foreach ($orders as $order): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 text-sm font-mono text-slate-500">#<?php echo $order['id']; ?></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded bg-slate-100 flex-shrink-0 overflow-hidden border border-slate-200">
                                                    <?php 
                                                        $cover = !empty($order['cover_image']) && file_exists('../' . $order['cover_image']) 
                                                            ? '../' . $order['cover_image'] 
                                                            : '../assets/images/service-placeholder.jpg';
                                                    ?>
                                                    <img src="<?php echo $cover; ?>" class="w-full h-full object-cover">
                                                </div>
                                                <span class="text-sm font-medium text-slate-800 line-clamp-1 max-w-[150px]" title="<?php echo htmlspecialchars($order['service_title']); ?>">
                                                    <?php echo htmlspecialchars($order['service_title']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            <?php echo htmlspecialchars($order['buyer_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            <?php echo htmlspecialchars($order['seller_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-bold text-slate-700">
                                            <?php echo number_format($order['price']); ?>₮
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php 
                                                $status_classes = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'active' => 'bg-blue-100 text-blue-800',
                                                    'delivered' => 'bg-purple-100 text-purple-800',
                                                    'completed' => 'bg-green-100 text-green-800',
                                                    'cancelled' => 'bg-red-100 text-red-800'
                                                ];
                                                $s_class = $status_classes[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $s_class; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm text-slate-500">
                                            <?php echo date('Y-m-d', strtotime($order['ordered_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button onclick='openEditStatusModal(<?php echo json_encode($order); ?>)' class="p-1.5 text-slate-400 hover:text-indigo-600 rounded hover:bg-indigo-50 transition" title="Төлөв өөрчлөх">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="inline" onsubmit="return confirm('Энэ захиалгыг устгах уу?');">
                                                    <input type="hidden" name="delete_order" value="1">
                                                    <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50 transition" title="Устгах">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center text-slate-500">Захиалга олдсонгүй.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <div class="mt-4 flex justify-center gap-2">
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="px-3 py-1 border <?php echo $i == $page ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-300 text-slate-600 hover:bg-slate-50'; ?> rounded text-sm">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <!-- Create Order Modal -->
    <div id="createOrderModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('createOrderModal')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form method="POST" action="">
                    <input type="hidden" name="create_order" value="1">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Шинэ захиалга үүсгэх</h3>
                        <div class="space-y-4">
                            <!-- Service Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Үйлчилгээ сонгох</label>
                                <select name="service_id" id="service_select" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required onchange="updatePricePlaceholder(this)">
                                    <option value="">Сонгох...</option>
                                    <?php foreach ($services_list as $srv): ?>
                                        <option value="<?php echo $srv['id']; ?>" data-price="<?php echo $srv['price_min']; ?>">
                                            <?php echo htmlspecialchars($srv['title']) . ' (' . number_format($srv['price_min']) . '₮)'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Buyer Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Захиалагч (Buyer)</label>
                                <select name="buyer_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                    <option value="">Сонгох...</option>
                                    <?php foreach ($users_list as $usr): ?>
                                        <option value="<?php echo $usr['id']; ?>"><?php echo htmlspecialchars($usr['username']) . ' (' . htmlspecialchars($usr['email']) . ')'; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Price Override -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Үнэ (Хоосон орхивол үндсэн үнээр)</label>
                                <input type="number" name="price" id="order_price" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Үндсэн үнэ: 0₮">
                            </div>

                            <!-- Initial Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Эхлэх Төлөв</label>
                                <select name="status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="pending">Pending</option>
                                    <option value="active" selected>Active (Эхлүүлэх)</option>
                                    <option value="completed">Completed (Дууссан)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Үүсгэх</button>
                        <button type="button" onclick="closeModal('createOrderModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Болих</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('statusModal')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full">
                <form method="POST" action="">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="order_id" id="edit_order_id">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Төлөв өөрчлөх</h3>
                        <p class="text-sm text-gray-500 mb-4">Захиалга #<span id="display_order_id"></span></p>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Төлөв</label>
                                <select name="status" id="edit_order_status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Хадгалах</button>
                        <button type="button" onclick="closeModal('statusModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Болих</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function openCreateModal() {
            document.getElementById('createOrderModal').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function updatePricePlaceholder(select) {
            const selectedOption = select.options[select.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            const input = document.getElementById('order_price');
            if (price) {
                input.placeholder = "Үндсэн үнэ: " + new Intl.NumberFormat().format(price) + "₮";
            } else {
                input.placeholder = "Үндсэн үнэ: 0₮";
            }
        }

        function openEditStatusModal(order) {
            document.getElementById('edit_order_id').value = order.id;
            document.getElementById('display_order_id').textContent = order.id;
            document.getElementById('edit_order_status').value = order.status;
            document.getElementById('statusModal').classList.remove('hidden');
        }
    </script>
</body>
</html>