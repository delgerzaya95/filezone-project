<?php
// profile/my_orders.php
session_start();
require_once '../includes/db.php';

// Check DB Connection (PDO fallback check)
if (!isset($pdo)) {
    if (isset($conn)) {
        die("Database connection error: PDO not found. Please ensure db.php provides \$pdo.");
    } else {
        die("Database connection failed.");
    }
}

// Нэвтрээгүй бол login руу буцаах
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --------------------------------------------------------------------------
// USER INFO & AVATAR LOGIC (Added to match my_services.php design)
// --------------------------------------------------------------------------

// 1. User Info
$stmt_u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_u->execute([$user_id]);
$user_data = $stmt_u->fetch(PDO::FETCH_ASSOC);
$username = $user_data['username'] ?? 'User';
$email = $user_data['email'] ?? '';
$user_bio = $user_data['bio'] ?? '';

// 2. Skills (Optional, but good for consistency if sidebar uses it)
$skills_array = [];
try {
    $stmt_s = $pdo->prepare("SELECT skill_name as name, skill_level as level FROM user_skills WHERE user_id = ?");
    $stmt_s->execute([$user_id]);
    $skills_array = $stmt_s->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $legacy_skills = $user_data['skills'] ?? '';
    $decoded = json_decode($legacy_skills, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $skills_array = $decoded;
    }
}

// 3. Avatar Logic
$db_avatar = $user_data['avatar_url'];
$avatar = "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=random&color=fff";

if (!empty($db_avatar)) {
    if (strpos($db_avatar, 'http') === 0) {
        $avatar = $db_avatar;
    } else {
        if (file_exists('../' . $db_avatar)) {
            $avatar = '../' . $db_avatar;
        }
    }
}

// --------------------------------------------------------------------------
// ORDERS LOGIC
// --------------------------------------------------------------------------

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
// Шинэ параметр: type ('purchased' эсвэл 'received')
$order_type = isset($_GET['type']) ? $_GET['type'] : 'purchased'; 

// --- 1. COUNT QUERIES (Тоог гаргах) ---

// A. Main Tab Counts (Purchased vs Received)
$stmt_count_purchased = $pdo->prepare("SELECT COUNT(*) FROM service_orders WHERE buyer_id = ?");
$stmt_count_purchased->execute([$user_id]);
$count_purchased = $stmt_count_purchased->fetchColumn();

$stmt_count_received = $pdo->prepare("SELECT COUNT(*) FROM service_orders WHERE seller_id = ?");
$stmt_count_received->execute([$user_id]);
$count_received = $stmt_count_received->fetchColumn();

// B. Status Counts based on selected type
$status_counts = [
    'all' => 0,
    'active' => 0,
    'delivered' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'pending' => 0 
];

$count_sql = "SELECT status, COUNT(*) as cnt FROM service_orders WHERE ";
if ($order_type == 'received') {
    $count_sql .= "seller_id = ?";
} else {
    $count_sql .= "buyer_id = ?";
}
$count_sql .= " GROUP BY status";

$stmt_status = $pdo->prepare($count_sql);
$stmt_status->execute([$user_id]);
$rows = $stmt_status->fetchAll(PDO::FETCH_ASSOC);

$total_count = 0;
foreach ($rows as $row) {
    $status_counts[$row['status']] = $row['cnt'];
    $total_count += $row['cnt'];
}
$status_counts['all'] = $total_count;


// --- Helper: Status Badge ---
function getOrderStatusBadge($status) {
    switch($status) {
        case 'pending': return '<span class="px-2 py-1 text-[10px] font-bold uppercase bg-yellow-100 text-yellow-700 rounded-full">Хүлээгдэж буй</span>';
        case 'active': return '<span class="px-2 py-1 text-[10px] font-bold uppercase bg-blue-100 text-blue-700 rounded-full">Идэвхтэй</span>';
        case 'delivered': return '<span class="px-2 py-1 text-[10px] font-bold uppercase bg-purple-100 text-purple-700 rounded-full">Хүлээлгэн өгсөн</span>';
        case 'completed': return '<span class="px-2 py-1 text-[10px] font-bold uppercase bg-green-100 text-green-700 rounded-full">Дууссан</span>';
        case 'cancelled': return '<span class="px-2 py-1 text-[10px] font-bold uppercase bg-red-100 text-red-700 rounded-full">Цуцлагдсан</span>';
        default: return '<span class="px-2 py-1 text-[10px] font-bold uppercase bg-gray-100 text-gray-500 rounded-full">' . htmlspecialchars($status) . '</span>';
    }
}

// --- Query Builder ---
$sql = "SELECT 
            so.*, 
            s.title as service_title, 
            s.cover_image, 
            s.delivery_time, 
            s.delivery_unit,
            buyer.username as buyer_username,
            buyer.full_name as buyer_fullname,
            buyer.avatar_url as buyer_avatar,
            seller.username as seller_username,
            seller.full_name as seller_fullname,
            seller.avatar_url as seller_avatar
        FROM service_orders so
        JOIN services s ON so.service_id = s.id
        JOIN users buyer ON so.buyer_id = buyer.id
        JOIN users seller ON so.seller_id = seller.id
        WHERE ";

$params = [];

// Төрлөөр нь ялгах
if ($order_type == 'received') {
    // Надад ирсэн захиалгууд (Би гүйцэтгэгч)
    $sql .= "so.seller_id = ?";
    $params[] = $user_id;
    $pageTitle = "Ирсэн захиалгууд";
} else {
    // Миний хийсэн захиалгууд (Би худалдан авагч)
    $sql .= "so.buyer_id = ?";
    $params[] = $user_id;
    $pageTitle = "Миний захиалгууд";
}

if ($status_filter != 'all') {
    $sql .= " AND so.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY so.ordered_at DESC";

// Execute
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$current_page = 'my_orders.php';
include 'header.php'; 
?>

<!-- Styles -->
<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .fade-in { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Захиалгын удирдлага</h1>
                <p class="text-sm text-gray-500 mt-1">
                    <?php echo ($order_type == 'received') ? 'Танд ирсэн ажлын захиалгууд.' : 'Таны худалдан авсан үйлчилгээнүүд.'; ?>
                </p>
            </div>
            
            <?php if(isset($_GET['purchase']) && $_GET['purchase'] == 'success'): ?>
                <div id="success-toast" class="bg-green-100 text-green-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 animate-bounce-slow shadow-sm border border-green-200">
                    <i class="fas fa-check-circle"></i> Захиалга амжилттай үүслээ!
                </div>
                <script>setTimeout(() => document.getElementById('success-toast').remove(), 5000);</script>
            <?php endif; ?>
        </div>

        <!-- Main Tabs (Purchased vs Received) -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <a href="?type=purchased" class="<?php echo ($order_type == 'purchased') ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                    <i class="fas fa-shopping-bag"></i> Миний захиалсан
                    <?php if($count_purchased > 0): ?>
                        <span class="bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs ml-1"><?php echo $count_purchased; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?type=received" class="<?php echo ($order_type == 'received') ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                    <i class="fas fa-inbox"></i> Ирсэн захиалга
                    <?php if($count_received > 0): ?>
                        <span class="<?php echo ($order_type == 'received') ? 'bg-brand-100 text-brand-600' : 'bg-gray-100 text-gray-600'; ?> py-0.5 px-2 rounded-full text-xs ml-1"><?php echo $count_received; ?></span>
                    <?php endif; ?>
                </a>
            </nav>
        </div>

        <!-- Status Filters -->
        <div class="bg-white border border-gray-200 rounded-xl p-1 mb-6 inline-flex overflow-x-auto max-w-full shadow-sm">
            <?php 
            $tabs = [
                'all' => 'Бүгд', 
                'active' => 'Идэвхтэй', 
                'pending' => 'Хүлээгдэж буй', // Added pending
                'delivered' => 'Хүлээлгэн өгсөн',
                'completed' => 'Дууссан', 
                'cancelled' => 'Цуцлагдсан'
            ];
            foreach($tabs as $key => $label): 
                $count = isset($status_counts[$key]) ? $status_counts[$key] : 0;
                $isActive = $status_filter == $key;
                
                $cls = $isActive ? 'bg-gray-100 text-gray-900 font-bold shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50';
                
                // Active count color
                $countBadge = '';
                if ($count > 0) {
                    $badgeColor = $isActive ? 'bg-white text-gray-900' : 'bg-gray-200 text-gray-600';
                    if ($key == 'active') $badgeColor = $isActive ? 'bg-blue-200 text-blue-800' : 'bg-blue-100 text-blue-600';
                    if ($key == 'pending') $badgeColor = $isActive ? 'bg-yellow-200 text-yellow-800' : 'bg-yellow-100 text-yellow-600';
                    
                    $countBadge = '<span class="ml-2 '.$badgeColor.' py-0.5 px-1.5 rounded-md text-[10px] font-bold">'.$count.'</span>';
                }
            ?>
            <a href="?type=<?php echo $order_type; ?>&status=<?php echo $key; ?>" class="px-4 py-2 rounded-lg text-sm transition-all whitespace-nowrap flex items-center <?php echo $cls; ?>">
                <?php echo $label; ?>
                <?php echo $countBadge; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Orders List Table -->
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <?php if (count($orders) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3">Үйлчилгээ</th>
                            <th class="px-6 py-3">
                                <?php echo ($order_type == 'received') ? 'Захиалагч' : 'Гүйцэтгэгч'; ?>
                            </th>
                            <th class="px-6 py-3">Үнэ</th>
                            <th class="px-6 py-3">Огноо</th>
                            <th class="px-6 py-3">Статус</th>
                            <th class="px-6 py-3 text-right">Үйлдэл</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach($orders as $order): 
                            // Image logic
                            $img = 'https://placehold.co/150x150?text=No+Image';
                            if (!empty($order['cover_image'])) {
                                if (strpos($order['cover_image'], 'http') === 0) {
                                    $img = $order['cover_image'];
                                } else {
                                    $local_path = '../' . $order['cover_image'];
                                    if (file_exists($local_path)) {
                                        $img = $local_path;
                                    }
                                }
                            }
                            
                            // Determine user info to display based on type
                            if ($order_type == 'received') {
                                // Show Buyer info
                                $other_user_name = !empty($order['buyer_fullname']) ? $order['buyer_fullname'] : $order['buyer_username'];
                                $other_user_avatar = !empty($order['buyer_avatar']) ? '../' . $order['buyer_avatar'] : "https://ui-avatars.com/api/?name=" . urlencode($other_user_name) . "&background=random&color=fff";
                                if (strpos($order['buyer_avatar'] ?? '', 'http') === 0) $other_user_avatar = $order['buyer_avatar'];
                            } else {
                                // Show Seller info
                                $other_user_name = !empty($order['seller_fullname']) ? $order['seller_fullname'] : $order['seller_username'];
                                $other_user_avatar = !empty($order['seller_avatar']) ? '../' . $order['seller_avatar'] : "https://ui-avatars.com/api/?name=" . urlencode($other_user_name) . "&background=random&color=fff";
                                if (strpos($order['seller_avatar'] ?? '', 'http') === 0) $other_user_avatar = $order['seller_avatar'];
                            }

                            // ШИНЭЧИЛСЭН: Дэлгэрэнгүй товчийг order_details.php руу холбох
                            $order_detail_url = 'order_details.php?id=' . $order['id'];
                        ?>
                        <tr class="bg-white hover:bg-gray-50 transition group cursor-pointer" onclick="window.location.href='<?php echo $order_detail_url; ?>'">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <a href="<?php echo $order_detail_url; ?>" class="w-16 h-12 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden border border-gray-200 block">
                                        <img src="<?php echo htmlspecialchars($img); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    </a>
                                    <div class="min-w-0 max-w-xs">
                                        <a href="<?php echo $order_detail_url; ?>" class="text-sm font-medium text-gray-900 truncate block hover:text-blue-600 transition" title="<?php echo htmlspecialchars($order['service_title']); ?>">
                                            <?php echo htmlspecialchars($order['service_title']); ?>
                                        </a>
                                        <span class="text-xs text-gray-500">ID: #<?php echo $order['id']; ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <img src="<?php echo htmlspecialchars($other_user_avatar); ?>" class="w-6 h-6 rounded-full object-cover border border-gray-200">
                                    <span class="text-gray-700 font-medium truncate max-w-[120px]"><?php echo htmlspecialchars($other_user_name); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900">
                                <?php echo number_format($order['price']); ?>₮
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-600">
                                <?php echo date('Y-m-d', strtotime($order['ordered_at'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo getOrderStatusBadge($order['status']); ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2" onclick="event.stopPropagation();">
                                    <!-- Common Action: Message -->
                                    <?php if($order['status'] == 'active' || $order['status'] == 'pending'): ?>
                                        <a href="#" class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition" title="Зурвас бичих"><i class="far fa-envelope"></i></a>
                                    <?php endif; ?>

                                    <!-- Specific Actions based on Type -->
                                    <?php if ($order_type == 'received'): ?>
                                        <!-- SELLER ACTIONS -->
                                        <?php if($order['status'] == 'active'): ?>
                                            <button onclick="deliverOrder(<?php echo $order['id']; ?>)" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition" title="Ажил хүлээлгэн өгөх"><i class="fas fa-box-open"></i></button>
                                        <?php endif; ?>
                                        
                                    <?php else: ?>
                                        <!-- BUYER ACTIONS -->
                                        <?php if($order['status'] == 'delivered'): ?>
                                            <button onclick="confirmOrder(<?php echo $order['id']; ?>)" class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition" title="Хүлээн авах"><i class="fas fa-check"></i></button>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <a href="<?php echo $order_detail_url; ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Дэлгэрэнгүй"><i class="fas fa-external-link-alt"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                        <?php echo ($order_type == 'received') ? '<i class="fas fa-inbox"></i>' : '<i class="fas fa-shopping-bag"></i>'; ?>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Захиалга олдсонгүй</h3>
                    <p class="text-gray-500 text-sm mt-1 mb-6">
                        <?php echo ($order_type == 'received') ? 'Танд одоогоор ирсэн захиалга байхгүй байна.' : 'Та одоогоор ямар нэгэн үйлчилгээ захиалаагүй байна.'; ?>
                    </p>
                    <?php if($order_type == 'purchased'): ?>
                        <a href="../services.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-search mr-2"></i> Үйлчилгээ хайх
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- Scripts -->
<script>
// Buyer confirms order completion
function confirmOrder(orderId) {
    if(confirm('Та энэ ажлыг хүлээн авч, захиалгыг дуусгахдаа итгэлтэй байна уу?')) {
        alert('Энэ функц хөгжүүлэлтийн шатанд байна.');
    }
}

// Seller marks order as delivered
function deliverOrder(orderId) {
    if(confirm('Та ажлыг хийж дуусаад хүлээлгэн өгөх гэж байна уу?')) {
        alert('Энэ функц хөгжүүлэлтийн шатанд байна.');
    }
}
</script>

<?php include '../includes/footer.php'; ?>