<?php
// profile/order_details.php
session_start();
require_once '../includes/db.php';

// Notifications
if (file_exists('../includes/notifications.php')) {
    require_once '../includes/notifications.php';
}
// Brevo Email Handler
if (file_exists('../admin/api/brevo_admin.php')) {
    require_once '../admin/api/brevo_admin.php';
}

// Database Connection Check
if (!isset($pdo)) {
    if(isset($conn)) { die("Database connection error: PDO is required."); }
    die("Database connection failed.");
}

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --------------------------------------------------------------------------
// 1. User Info Logic (Sidebar-д зориулсан)
// --------------------------------------------------------------------------
$stmt_u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_u->execute([$user_id]);
$user_data = $stmt_u->fetch(PDO::FETCH_ASSOC);
$username = $user_data['username'] ?? 'User';
$email = $user_data['email'] ?? '';
$user_bio = $user_data['bio'] ?? '';

// Skills
$skills_array = [];
try {
    $stmt_s = $pdo->prepare("SELECT skill_name as name, skill_level as level FROM user_skills WHERE user_id = ?");
    $stmt_s->execute([$user_id]);
    $skills_array = $stmt_s->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Avatar
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
// 2. AJAX HANDLERS (Order Actions)
// --------------------------------------------------------------------------

if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $ord_id = intval($_POST['order_id']);

    // Common Checks
    $stmt = $pdo->prepare("SELECT id, seller_id, buyer_id, price, status, service_id, ordered_at FROM service_orders WHERE id = ?");
    $stmt->execute([$ord_id]);
    $ord = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ord) {
        echo json_encode(['success' => false, 'message' => 'Захиалга олдсонгүй.']);
        exit;
    }

    // --- A. START WORKING (Ажил эхлүүлэх) ---
    if ($_POST['action'] == 'start_work') {
        if ($ord['seller_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Танд эрх байхгүй.']);
            exit;
        }
        if ($ord['status'] != 'pending') {
            echo json_encode(['success' => false, 'message' => 'Захиалга хүлээгдэж буй төлөвт биш байна.']);
            exit;
        }

        try {
            $upd = $pdo->prepare("UPDATE service_orders SET status = 'active' WHERE id = ?");
            $upd->execute([$ord_id]);

            if(function_exists('sendNotification')) {
                sendNotification($pdo, $ord['buyer_id'], 'info', "Захиалга #{$ord_id}: Гүйцэтгэгч ажлаа эхлүүллээ.", "profile/order_details.php?id={$ord_id}");
            }

            if (function_exists('notifyOrderStarted')) {
                $stmt_b = $pdo->prepare("SELECT u.email, u.username, s.title FROM users u JOIN services s ON s.id = ? WHERE u.id = ?");
                $stmt_b->execute([$ord['service_id'], $ord['buyer_id']]);
                $buyer_info = $stmt_b->fetch(PDO::FETCH_ASSOC);

                if ($buyer_info) {
                    notifyOrderStarted($buyer_info['email'], $buyer_info['username'], $buyer_info['title'], $ord_id);
                }
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // --- B. DELIVER ORDER (Ажил хүлээлгэн өгөх) ---
    if ($_POST['action'] == 'deliver_order') {
        if ($ord['seller_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Танд эрх байхгүй.']);
            exit;
        }
        if ($ord['status'] != 'active') {
            echo json_encode(['success' => false, 'message' => 'Захиалга идэвхтэй биш байна (Эхлээд "Ажиллаж эхлэх" товчийг дарна уу).']);
            exit;
        }

        try {
            $upd = $pdo->prepare("UPDATE service_orders SET status = 'delivered', delivered_at = NOW() WHERE id = ?");
            $upd->execute([$ord_id]);

            if(function_exists('sendNotification')) {
                sendNotification($pdo, $ord['buyer_id'], 'success', "Таны захиалга (#{$ord_id}) хийгдэж дууслаа! Та ажлаа шалгаад хүлээн авна уу.", "profile/order_details.php?id={$ord_id}");
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // --- C. REQUEST REVISION (Засвар хүсэх) ---
    if ($_POST['action'] == 'request_revision') {
        if ($ord['buyer_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Танд эрх байхгүй.']);
            exit;
        }
        if ($ord['status'] != 'delivered') {
            echo json_encode(['success' => false, 'message' => 'Зөвхөн хүлээлгэн өгсөн захиалгад засвар хүсэх боломжтой.']);
            exit;
        }
        $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
        if (empty($reason)) { echo json_encode(['success' => false, 'message' => 'Шалтгаанаа бичнэ үү.']); exit; }

        try {
            $upd = $pdo->prepare("UPDATE service_orders SET status = 'active', delivered_at = NULL WHERE id = ?");
            $upd->execute([$ord_id]);

            if(function_exists('sendNotification')) {
                sendNotification($pdo, $ord['seller_id'], 'warning', "Захиалга (#{$ord_id}) дээр засвар хийх хүсэлт ирлээ: {$reason}", "profile/order_details.php?id={$ord_id}");
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]); }
        exit;
    }

    // --- D. COMPLETE ORDER (Захиалга дуусгах - Мөнгө шилжих) ---
    if ($_POST['action'] == 'complete_order') {
        if ($ord['buyer_id'] != $user_id) { echo json_encode(['success' => false, 'message' => 'Танд эрх байхгүй.']); exit; }
        if ($ord['status'] != 'delivered') { echo json_encode(['success' => false, 'message' => 'Ажил хүлээлгэн өгөөгүй байна.']); exit; }

        try {
            $pdo->beginTransaction();
            $upd = $pdo->prepare("UPDATE service_orders SET status = 'completed', completed_at = NOW() WHERE id = ?");
            $upd->execute([$ord_id]);

            $sub = $pdo->prepare("UPDATE users SET pending_balance = pending_balance - ? WHERE id = ?");
            $sub->execute([$ord['price'], $ord['seller_id']]);
            $add = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $add->execute([$ord['price'], $ord['seller_id']]);

            if(function_exists('sendNotification')) {
                sendNotification($pdo, $ord['seller_id'], 'success', "Баяр хүргэе! Захиалга #{$ord_id} амжилттай хаагдлаа. Төлбөр орлоо.", "profile/wallet.php");
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) { $pdo->rollBack(); echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]); }
        exit;
    }

    // --- E. MANUAL CANCEL (24 цаг хэтэрсэн үед хэрэглэгч гараар цуцлах) ---
    if ($_POST['action'] == 'cancel_order_inactive') {
        if ($ord['buyer_id'] != $user_id) { echo json_encode(['success' => false, 'message' => 'Танд эрх байхгүй.']); exit; }
        if ($ord['status'] != 'pending') { echo json_encode(['success' => false, 'message' => 'Захиалга ажиллагаанд орсон байна.']); exit; }

        try {
            $pdo->beginTransaction();
            $upd = $pdo->prepare("UPDATE service_orders SET status = 'cancelled' WHERE id = ?");
            $upd->execute([$ord_id]);

            $net_amount = $ord['price'] * 0.90;
            $pdo->prepare("UPDATE users SET pending_balance = pending_balance - ? WHERE id = ?")->execute([$net_amount, $ord['seller_id']]);
            $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$ord['price'], $ord['buyer_id']]);

            $tx_num = 'REF-' . date('Ymd') . '-' . rand(1000, 9999);
            $pdo->prepare("INSERT INTO user_transactions (transaction_number, user_id, type, amount, description, transaction_date) VALUES (?, ?, 'deposit', ?, ?, NOW())")->execute([$tx_num, $ord['buyer_id'], $ord['price'], "Захиалга цуцлалт (#{$ord_id}) - Буцаан олголт"]);

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) { $pdo->rollBack(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit;
    }
}

// --------------------------------------------------------------------------
// 3. MAIN PAGE LOGIC & AUTO-ACTIONS
// --------------------------------------------------------------------------

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($order_id <= 0) { header("Location: my_orders.php"); exit(); }

$stmt = $pdo->prepare("
    SELECT so.*, s.title as service_title, s.cover_image, s.delivery_time, s.delivery_unit,
        buyer.id as buyer_id_real, buyer.username as buyer_username, buyer.full_name as buyer_fullname, 
        buyer.phone as buyer_phone, buyer.email as buyer_email, buyer.avatar_url as buyer_avatar,
        seller.id as seller_id_real, seller.username as seller_username, seller.full_name as seller_fullname, 
        seller.phone as seller_phone, seller.email as seller_email, seller.avatar_url as seller_avatar
    FROM service_orders so
    JOIN services s ON so.service_id = s.id
    JOIN users buyer ON so.buyer_id = buyer.id
    JOIN users seller ON so.seller_id = seller.id
    WHERE so.id = ? AND (so.buyer_id = ? OR so.seller_id = ?)
");
$stmt->execute([$order_id, $user_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) { die("Захиалга олдсонгүй."); }

// --- FETCH REPORT STATUS ---
$has_report = false;
$report_status = '';
$report_data = null;
$stmt_rep = $pdo->prepare("SELECT * FROM order_reports WHERE order_id = ? ORDER BY id DESC LIMIT 1");
$stmt_rep->execute([$order_id]);
if ($stmt_rep->rowCount() > 0) {
    $report_data = $stmt_rep->fetch(PDO::FETCH_ASSOC);
    $has_report = true;
    $report_status = $report_data['status'];
}

// --- [LOGIC 1] AUTO-CANCEL IF NOT STARTED IN 24 HOURS ---
if ($order['status'] == 'pending') {
    $ordered_time = strtotime($order['ordered_at']);
    $start_deadline = $ordered_time + (24 * 60 * 60); // 24 hours
    
    if (time() > $start_deadline) {
        try {
            $pdo->beginTransaction();
            $stmt_chk = $pdo->prepare("SELECT status, price, seller_id, buyer_id FROM service_orders WHERE id = ? FOR UPDATE");
            $stmt_chk->execute([$order_id]);
            $curr = $stmt_chk->fetch(PDO::FETCH_ASSOC);

            if ($curr && $curr['status'] == 'pending') {
                $pdo->prepare("UPDATE service_orders SET status = 'cancelled' WHERE id = ?")->execute([$order_id]);

                $net_amount = $curr['price'] * 0.90; 
                $pdo->prepare("UPDATE users SET pending_balance = pending_balance - ? WHERE id = ?")->execute([$net_amount, $curr['seller_id']]);
                $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$curr['price'], $curr['buyer_id']]);

                $tx_num = 'AUTOCNL-' . date('Ymd') . '-' . rand(1000, 9999);
                $pdo->prepare("INSERT INTO user_transactions (transaction_number, user_id, type, amount, description, transaction_date) VALUES (?, ?, 'deposit', ?, ?, NOW())")->execute([$tx_num, $curr['buyer_id'], $curr['price'], "Захиалга #{$order_id} цуцлалт (Автомат)"]);

                if(function_exists('sendNotification')) {
                    sendNotification($pdo, $curr['seller_id'], 'error', "Захиалга #{$order_id} 24 цагийн дотор эхлээгүй тул автоматаар цуцлагдлаа.", "profile/service_orders.php");
                    sendNotification($pdo, $curr['buyer_id'], 'info', "Захиалга #{$order_id} автоматаар цуцлагдаж, төлбөр буцаан олгогдлоо.", "profile/wallet.php");
                }

                if (function_exists('notifyOrderAutoCancelled')) {
                    notifyOrderAutoCancelled($order['buyer_email'], $order['buyer_username'], $order['service_title'], $order_id, "Гүйцэтгэгч 24 цагийн дотор ажиллаж эхлээгүй.");
                }

                $pdo->commit();
                $order['status'] = 'cancelled';
            } else {
                $pdo->rollBack();
            }
        } catch (Exception $e) { $pdo->rollBack(); }
    }
}

// --- [LOGIC 2] AUTO-COMPLETE IF DELIVERED > 3 DAYS ---
if ($order['status'] == 'delivered' && !empty($order['delivered_at']) && (!$has_report || $report_status == 'dismissed')) {
    $delivered_time = strtotime($order['delivered_at']);
    $auto_complete_time = $delivered_time + (3 * 24 * 60 * 60); // 72 hours
    if (time() > $auto_complete_time) {
        try {
            $pdo->beginTransaction();
            $stmt_chk = $pdo->prepare("SELECT status, price, seller_id, buyer_id FROM service_orders WHERE id = ? FOR UPDATE");
            $stmt_chk->execute([$order_id]);
            $curr = $stmt_chk->fetch(PDO::FETCH_ASSOC);

            if ($curr && $curr['status'] == 'delivered') {
                $pdo->prepare("UPDATE service_orders SET status = 'completed', completed_at = NOW() WHERE id = ?")->execute([$order_id]);
                
                $sub = $pdo->prepare("UPDATE users SET pending_balance = pending_balance - ? WHERE id = ?");
                $sub->execute([$curr['price'], $curr['seller_id']]);
                $add = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $add->execute([$curr['price'], $curr['seller_id']]);

                if(function_exists('sendNotification')) {
                    sendNotification($pdo, $curr['seller_id'], 'success', "Захиалга #{$order_id} автоматаар хаагдлаа (3 хоног). Төлбөр орлоо.", "profile/wallet.php");
                    sendNotification($pdo, $curr['buyer_id'], 'info', "Захиалга #{$order_id} автоматаар хаагдлаа.", "profile/order_details.php?id={$order_id}");
                }
                $pdo->commit();
                $order['status'] = 'completed';
                $order['completed_at'] = date('Y-m-d H:i:s');
            } else {
                $pdo->rollBack();
            }
        } catch (Exception $e) { $pdo->rollBack(); }
    }
}

$is_buyer = ($order['buyer_id_real'] == $user_id);
$is_seller = ($order['seller_id_real'] == $user_id);

// Counterpart Data
if ($is_buyer) {
    $other_name = !empty($order['seller_fullname']) ? $order['seller_fullname'] : $order['seller_username'];
    $other_phone = $order['seller_phone'];
    $other_email = $order['seller_email'];
    $other_role = 'Гүйцэтгэгч';
    $raw_avatar = $order['seller_avatar'];
} else {
    $other_name = !empty($order['buyer_fullname']) ? $order['buyer_fullname'] : $order['buyer_username'];
    $other_phone = $order['buyer_phone'];
    $other_email = $order['buyer_email'];
    $other_role = 'Захиалагч';
    $raw_avatar = $order['buyer_avatar'];
}

$other_avatar = "https://ui-avatars.com/api/?name=" . urlencode($other_name) . "&background=random&color=fff";
if (!empty($raw_avatar)) {
    if (strpos($raw_avatar, 'http') === 0) {
        $other_avatar = $raw_avatar;
    } else {
        if (file_exists('../' . $raw_avatar)) {
            $other_avatar = '../' . $raw_avatar;
        }
    }
}

// Deadlines & Timestamps
$ordered_date = new DateTime($order['ordered_at']);
$deadline = clone $ordered_date;
$unit_map = ['hour' => 'hours', 'day' => 'days', 'week' => 'weeks', 'month' => 'months'];
$deadline->modify('+' . $order['delivery_time'] . ' ' . ($unit_map[$order['delivery_unit']] ?? 'days'));

// Timers for JS
$js_start_deadline = $ordered_date->getTimestamp() + (24 * 60 * 60);
$js_auto_complete = 0;
if($order['status'] == 'delivered' && !empty($order['delivered_at'])) {
    $js_auto_complete = strtotime($order['delivered_at']) + (3 * 24 * 60 * 60);
}

function getStatusBadgeLarge($status) {
    switch($status) {
        case 'pending': return '<span class="px-4 py-2 rounded-full text-sm font-bold bg-yellow-100 text-yellow-700 border border-yellow-200"><i class="fas fa-clock mr-2"></i> Хүлээгдэж байна</span>';
        case 'active': return '<span class="px-4 py-2 rounded-full text-sm font-bold bg-blue-100 text-blue-700 border border-blue-200"><i class="fas fa-spinner fa-spin mr-2"></i> Хийгдэж байна</span>';
        case 'delivered': return '<span class="px-4 py-2 rounded-full text-sm font-bold bg-purple-100 text-purple-700 border border-purple-200"><i class="fas fa-gift mr-2"></i> Хүлээлгэн өгсөн</span>';
        case 'completed': return '<span class="px-4 py-2 rounded-full text-sm font-bold bg-green-100 text-green-700 border border-green-200"><i class="fas fa-check-circle mr-2"></i> Дууссан</span>';
        case 'cancelled': return '<span class="px-4 py-2 rounded-full text-sm font-bold bg-red-100 text-red-700 border border-red-200"><i class="fas fa-ban mr-2"></i> Цуцлагдсан</span>';
        default: return $status;
    }
}

// Review Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review']) && $is_buyer && $order['status'] == 'completed') {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    $chk = $pdo->prepare("SELECT id FROM service_reviews WHERE service_id = ? AND user_id = ?");
    $chk->execute([$order['service_id'], $user_id]);
    if ($chk->rowCount() == 0) {
        $ins = $pdo->prepare("INSERT INTO service_reviews (service_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $ins->execute([$order['service_id'], $user_id, $rating, $comment]);
        $success_msg = "Үнэлгээ амжилттай илгээгдлээ!";
    }
}

$pageTitle = "Захиалга #" . $order_id;
include 'header.php';
?>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 py-8 px-4 lg:px-8 min-w-0 bg-gray-50">
        
        <!-- Header -->
        <div class="mb-6">
            <a href="my_orders.php" class="text-sm text-gray-500 hover:text-brand-600 mb-2 inline-flex items-center"><i class="fas fa-arrow-left mr-1"></i> Буцах</a>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <h1 class="text-2xl font-bold text-gray-900">Захиалга #<?php echo $order_id; ?></h1>
                <?php echo getStatusBadgeLarge($order['status']); ?>
            </div>
        </div>

        <?php if(isset($success_msg)): ?>
            <div class="bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
        <?php endif; ?>

        <!-- REPORT STATUS BANNER -->
        <?php if($has_report): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8 shadow-sm">
                <?php if($report_status == 'pending'): ?>
                    <h3 class="font-bold text-yellow-800 text-lg mb-2"><i class="fas fa-exclamation-triangle mr-2"></i> Админ шалгаж байна</h3>
                    <p class="text-yellow-700 text-sm">
                        Энэ захиалга дээр гомдол үүссэн тул админ шалгаж байна. Шийдвэр гартал автомат үйлдлүүд (72 цагийн хаалт г.м) түр зогсоно.
                    </p>
                <?php elseif($report_status == 'resolved'): ?>
                    <h3 class="font-bold text-green-800 text-lg mb-2"><i class="fas fa-check-circle mr-2"></i> Маргаан шийдвэрлэгдсэн</h3>
                    <p class="text-green-700 text-sm">Админ энэ маргааныг шийдвэрлэсэн байна.</p>
                    <?php if(!empty($report_data['admin_note'])): ?>
                        <div class="mt-2 p-3 bg-white border border-green-200 rounded text-sm text-gray-700">
                            <strong>Админы тэмдэглэл:</strong> <?php echo htmlspecialchars($report_data['admin_note']); ?>
                        </div>
                    <?php endif; ?>
                <?php elseif($report_status == 'dismissed'): ?>
                    <h3 class="font-bold text-gray-800 text-lg mb-2"><i class="fas fa-times-circle mr-2"></i> Гомдол цуцлагдсан</h3>
                    <p class="text-gray-600 text-sm">Админ гомдлыг үндэслэлгүй гэж үзэн цуцалсан байна. Захиалга хэвийн үргэлжилнэ.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ACTION BAR -->
        
        <!-- 1. SELLER ACTIONS -->
        <?php if($is_seller): ?>
            
            <?php if($order['status'] == 'pending'): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
                    <div>
                        <h3 class="font-bold text-yellow-900 text-lg mb-1"><i class="fas fa-hourglass-start mr-2"></i> Шинэ захиалга ирлээ!</h3>
                        <p class="text-yellow-800 text-sm">Та <strong>24 цагийн дотор</strong> "Ажиллаж эхлэх" товчийг дарах шаардлагатай. Эс бөгөөс захиалга автоматаар цуцлагдана.</p>
                        <p class="text-xs text-red-600 font-bold mt-2" id="start-timer">Тооцоолж байна...</p>
                    </div>
                    <button onclick="confirmOrderAction('start_work', 'Та ажлыг эхлүүлэхдээ итгэлтэй байна уу?')" class="bg-brand-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-brand-700 transition shadow-lg shadow-brand-500/30 whitespace-nowrap">
                        <i class="fas fa-play mr-2"></i> Ажиллаж эхлэх
                    </button>
                </div>
            <?php endif; ?>

            <?php if($order['status'] == 'active'): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
                    <div>
                        <h3 class="font-bold text-blue-900 text-lg mb-1"><i class="fas fa-tools mr-2"></i> Ажил хийгдэж байна</h3>
                        <p class="text-blue-700 text-sm">Та ажлаа бүрэн дуусгасан бол "Хүлээлгэн өгөх" товчийг дарна уу.</p>
                    </div>
                    <button onclick="confirmOrderAction('deliver_order', 'Та ажлаа бүрэн дуусгаад хүлээлгэн өгөх гэж байна уу?')" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-500/30 whitespace-nowrap">
                        <i class="fas fa-paper-plane mr-2"></i> Ажил хүлээлгэн өгөх
                    </button>
                </div>
            <?php endif; ?>

            <?php if($order['status'] == 'delivered'): ?>
                <div class="bg-purple-50 border border-purple-200 rounded-xl p-6 mb-8 shadow-sm">
                    <h3 class="font-bold text-purple-900 text-lg"><i class="fas fa-check-double mr-2"></i> Хүлээлгэн өгсөн</h3>
                    <p class="text-purple-700 text-sm mt-1">Захиалагч ажлыг шалгаж байна.</p>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <!-- 2. BUYER ACTIONS -->
        <?php if($is_buyer): ?>
            
            <?php if($order['status'] == 'pending'): ?>
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 mb-8 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg mb-1"><i class="fas fa-user-clock mr-2"></i> Гүйцэтгэгч эхлэхийг хүлээж байна</h3>
                        <p class="text-gray-600 text-sm">Гүйцэтгэгч 24 цагийн дотор ажлыг эхлүүлэх ёстой.</p>
                        <p class="text-xs text-red-600 font-bold mt-2" id="start-timer">Тооцоолж байна...</p>
                    </div>
                    <?php if(time() > $js_start_deadline): ?>
                    <button onclick="confirmOrderAction('cancel_order_inactive', 'Та захиалгыг цуцалж, мөнгөө буцаан авах уу?')" class="bg-red-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-red-700 transition shadow-lg shadow-red-500/30 whitespace-nowrap">
                        Цуцлах
                    </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if($order['status'] == 'active'): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8 shadow-sm">
                    <h3 class="font-bold text-blue-900 text-lg mb-1"><i class="fas fa-cog fa-spin mr-2"></i> Гүйцэтгэгч ажиллаж байна</h3>
                    <p class="text-blue-700 text-sm">Ажил эхэлсэн байна. Товлосон хугацаанд хүлээлгэн өгнө.</p>
                </div>
            <?php endif; ?>

            <?php if($order['status'] == 'delivered'): ?>
                <div class="bg-white border border-gray-200 rounded-xl p-6 mb-8 shadow-sm">
                    <h3 class="font-bold text-gray-900 text-lg mb-2"><i class="fas fa-gift text-purple-600 mr-2"></i> Ажил хүлээлгэн өгсөн байна!</h3>
                    
                    <?php if($has_report && $report_status == 'pending'): ?>
                        <div class="bg-yellow-50 p-4 rounded text-yellow-800 text-sm">
                            <i class="fas fa-lock mr-2"></i> Таны гомдлыг админ шалгаж байна. Шийдвэр гартал үйлдэл хийх боломжгүй.
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600 text-sm mb-4">
                            Гүйцэтгэгч ажлаа дуусгасан байна. Та ажлыг шалгаад сэтгэл ханамжтай байвал "Хүлээн авах" товчийг дарна уу. 
                            <br>
                            <span class="text-orange-600 font-medium bg-orange-50 px-2 py-0.5 rounded mt-2 inline-block border border-orange-100">
                                <i class="fas fa-clock mr-1"></i> <span id="auto-complete-timer">Тооцоолж байна...</span>
                            </span>
                        </p>
                        
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button onclick="confirmOrderAction('complete_order', 'Та ажлыг шалгаж дуусаад хүлээн авахдаа итгэлтэй байна уу?')" class="flex-1 bg-green-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-700 transition shadow-lg shadow-green-500/30">
                                <i class="fas fa-check-circle mr-2"></i> Хүлээн авах & Дуусгах
                            </button>
                            <button onclick="requestRevision()" class="flex-1 bg-white text-gray-700 border border-gray-300 px-6 py-3 rounded-lg font-bold hover:bg-gray-50 hover:text-gray-900 transition">
                                <i class="fas fa-sync-alt mr-2 text-orange-500"></i> Засвар хүсэх
                            </button>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <a href="report_order.php?id=<?php echo $order_id; ?>" class="text-xs text-red-500 hover:text-red-700 underline font-medium">Асуудал гарсан уу? Админд маргаан үүсгэх</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <!-- GRID LAYOUT -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- LEFT: Service & CHAT -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Service Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex gap-4 items-start">
                    <?php 
                        $img = !empty($order['cover_image']) ? '../' . $order['cover_image'] : 'https://placehold.co/100x75?text=No+Image';
                        if (strpos($order['cover_image'] ?? '', 'http') === 0) $img = $order['cover_image'];
                    ?>
                    <img src="<?php echo htmlspecialchars($img); ?>" class="w-24 h-16 object-cover rounded-lg border border-gray-100">
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg"><?php echo htmlspecialchars($order['service_title']); ?></h3>
                        <p class="text-sm text-gray-500 mt-1">Үнэ: <?php echo number_format($order['price']); ?>₮</p>
                        <a href="../service-details.php?id=<?php echo $order['service_id']; ?>" class="text-xs text-brand-600 hover:underline mt-2 inline-block">Үйлчилгээг үзэх</a>
                    </div>
                </div>

                <!-- CHAT SYSTEM -->
                <div id="chatSection" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-[500px]">
                    <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <img src="<?php echo $other_avatar; ?>" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                                <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 text-sm"><?php echo htmlspecialchars($other_name); ?></h3>
                                <p class="text-xs text-gray-500">Чат</p>
                            </div>
                        </div>
                        <span class="text-xs bg-white border border-gray-200 px-2 py-1 rounded text-gray-500">
                            <i class="fas fa-lock mr-1"></i> Аюулгүй чат
                        </span>
                    </div>

                    <!-- Chat Messages Area -->
                    <div id="chatBox" class="flex-1 p-6 overflow-y-auto bg-white custom-scrollbar space-y-4">
                        <div class="flex flex-col items-center justify-center h-full text-gray-400">
                            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                            <p class="text-sm">Зурвас уншиж байна...</p>
                        </div>
                    </div>

                    <!-- Chat Input -->
                    <?php if($order['status'] != 'cancelled'): ?>
                    <div class="p-4 bg-gray-50 border-t border-gray-200">
                        <div id="filePreview" class="hidden px-4 py-2 mb-2 bg-white border border-blue-200 rounded-lg flex items-center justify-between shadow-sm">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-file-alt text-blue-500"></i>
                                <span id="previewName" class="text-sm text-gray-700 truncate max-w-[200px]">filename.jpg</span>
                            </div>
                            <button type="button" id="removeFileBtn" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
                        </div>

                        <form id="chatForm" class="flex gap-2 relative" enctype="multipart/form-data">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            
                            <!-- Hidden File Input -->
                            <input type="file" id="fileInput" name="attachment" class="hidden" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.zip,.rar,.txt">
                            
                            <!-- Paperclip Button -->
                            <button type="button" id="attachBtn" class="p-3 text-gray-400 hover:text-gray-600 transition rounded-xl hover:bg-gray-200" title="Файл илгээх">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            
                            <input type="text" id="messageInput" class="flex-1 bg-white border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition shadow-sm" placeholder="Зурвас бичих..." autocomplete="off">
                            
                            <button type="submit" id="sendBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-6 rounded-xl font-bold shadow-lg shadow-blue-500/30 transition transform active:scale-95 flex items-center justify-center">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="p-4 bg-red-50 border-t border-red-200 text-center text-red-600 text-sm font-medium">
                        Энэ захиалга цуцлагдсан тул чат хаагдсан.
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Review Form -->
                <?php if($is_buyer && $order['status'] == 'completed'): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="font-bold text-gray-900 mb-4">Сэтгэгдэл үлдээх</h3>
                    <?php 
                        $chk = $pdo->prepare("SELECT id FROM service_reviews WHERE service_id = ? AND user_id = ?");
                        $chk->execute([$order['service_id'], $user_id]);
                        if($chk->rowCount() > 0): 
                    ?>
                        <p class="text-sm text-gray-500 italic">Та энэ захиалгад сэтгэгдэл үлдээсэн байна.</p>
                    <?php else: ?>
                        <form method="POST">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Үнэлгээ</label>
                                <div class="flex gap-2 text-2xl text-gray-300" id="star-rating">
                                    <i class="fas fa-star cursor-pointer hover:text-yellow-400 transition" data-val="1"></i>
                                    <i class="fas fa-star cursor-pointer hover:text-yellow-400 transition" data-val="2"></i>
                                    <i class="fas fa-star cursor-pointer hover:text-yellow-400 transition" data-val="3"></i>
                                    <i class="fas fa-star cursor-pointer hover:text-yellow-400 transition" data-val="4"></i>
                                    <i class="fas fa-star cursor-pointer hover:text-yellow-400 transition" data-val="5"></i>
                                </div>
                                <input type="hidden" name="rating" id="ratingInput" value="5">
                            </div>
                            <textarea name="comment" rows="3" class="w-full border border-gray-300 rounded-lg p-3 text-sm mb-4" placeholder="Таны сэтгэгдэл..."></textarea>
                            <button type="submit" name="submit_review" class="bg-brand-600 text-white px-6 py-2 rounded-lg text-sm font-bold hover:bg-brand-700">Илгээх</button>
                        </form>
                        <script>
                            document.querySelectorAll('#star-rating i').forEach(star => {
                                star.addEventListener('click', function() {
                                    const val = this.getAttribute('data-val');
                                    document.getElementById('ratingInput').value = val;
                                    document.querySelectorAll('#star-rating i').forEach(s => {
                                        s.classList.remove('text-yellow-400'); s.classList.add('text-gray-300');
                                        if(s.getAttribute('data-val') <= val) { s.classList.remove('text-gray-300'); s.classList.add('text-yellow-400'); }
                                    });
                                });
                            });
                        </script>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT: Timeline & Info -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Contact Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="font-bold text-gray-900 mb-4"><?php echo $other_role; ?>-ийн мэдээлэл</h3>
                    <div class="flex items-center gap-3 mb-4">
                        <img src="<?php echo htmlspecialchars($other_avatar); ?>" class="w-12 h-12 rounded-full object-cover border border-gray-100">
                        <div>
                            <p class="font-bold text-gray-900"><?php echo htmlspecialchars($other_name); ?></p>
                            <span class="text-xs text-gray-500"><?php echo $other_role; ?></span>
                        </div>
                    </div>
                    <?php if(!empty($other_phone)): ?>
                        <div class="flex items-center gap-3 mb-2 text-sm text-gray-600">
                            <div class="w-8 h-8 bg-green-50 text-green-600 rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas fa-phone-alt"></i></div>
                            <span class="font-medium"><?php echo htmlspecialchars($other_phone); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="flex items-center gap-3 text-sm text-gray-600">
                        <div class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas fa-envelope"></i></div>
                        <span class="font-medium truncate"><?php echo htmlspecialchars($other_email); ?></span>
                    </div>
                    <hr class="my-4 border-gray-100">
                    <button onclick="document.getElementById('chatSection').scrollIntoView({behavior: 'smooth'})" class="w-full bg-brand-600 text-white py-2.5 rounded-lg text-sm font-bold hover:bg-brand-700 transition">
                        <i class="far fa-comment-dots mr-2"></i> Чатлах
                    </button>
                </div>

                <!-- Timeline -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="font-bold text-gray-900 mb-6">Захиалгын явц</h3>
                    <div class="relative">
                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                        
                        <!-- Ordered -->
                        <div class="relative flex items-start mb-8 group">
                            <div class="absolute left-0 w-8 h-8 flex items-center justify-center bg-green-500 rounded-full border-4 border-white z-10"><i class="fas fa-check text-white text-xs"></i></div>
                            <div class="ml-12">
                                <h4 class="text-sm font-bold text-gray-900">Захиалга үүссэн</h4>
                                <p class="text-xs text-gray-500 mt-1"><?php echo date('m-d H:i', strtotime($order['ordered_at'])); ?></p>
                            </div>
                        </div>

                        <!-- Active (Started) -->
                        <div class="relative flex items-start mb-8 group">
                            <?php $s2 = ($order['status'] != 'pending') ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-500'; if($order['status']=='completed'||$order['status']=='delivered'||$order['status']=='cancelled') $s2='bg-green-500 text-white'; ?>
                            <div class="absolute left-0 w-8 h-8 flex items-center justify-center <?php echo $s2; ?> rounded-full border-4 border-white z-10"><i class="fas fa-play text-xs"></i></div>
                            <div class="ml-12">
                                <h4 class="text-sm font-bold text-gray-900">Ажил эхэлсэн</h4>
                                <?php if($order['status'] == 'active'): ?>
                                    <p class="text-xs text-gray-600 mt-1">Дуусах: <?php echo $deadline->format('Y-m-d'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Delivered -->
                        <div class="relative flex items-start mb-8 group">
                            <?php $s3 = ($order['status']=='delivered'||$order['status']=='completed') ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-500'; ?>
                            <div class="absolute left-0 w-8 h-8 flex items-center justify-center <?php echo $s3; ?> rounded-full border-4 border-white z-10"><i class="fas fa-gift text-xs"></i></div>
                            <div class="ml-12">
                                <h4 class="text-sm font-bold text-gray-900">Хүлээлгэн өгсөн</h4>
                                <?php if($order['delivered_at']): ?>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo date('m-d H:i', strtotime($order['delivered_at'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Completed/Cancelled -->
                        <div class="relative flex items-start group">
                            <?php 
                                $s4 = 'bg-gray-300 text-gray-500'; 
                                $final_title = 'Дууссан';
                                $final_icon = 'fa-check-circle';
                                if($order['status']=='completed') { $s4 = 'bg-green-500 text-white'; }
                                if($order['status']=='cancelled') { $s4 = 'bg-red-500 text-white'; $final_title = 'Цуцлагдсан'; $final_icon = 'fa-ban'; }
                            ?>
                            <div class="absolute left-0 w-8 h-8 flex items-center justify-center <?php echo $s4; ?> rounded-full border-4 border-white z-10"><i class="fas <?php echo $final_icon; ?> text-xs"></i></div>
                            <div class="ml-12">
                                <h4 class="text-sm font-bold text-gray-900"><?php echo $final_title; ?></h4>
                                <?php if($order['completed_at']): ?>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo date('Y-m-d', strtotime($order['completed_at'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Legal / Contract Card -->
                <div class="bg-orange-50 rounded-xl border border-orange-200 p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-3 text-orange-800">
                        <i class="fas fa-file-contract text-lg"></i>
                        <h3 class="font-bold text-sm">Харилцагчийн Гэрээ</h3>
                    </div>
                    
                    <div class="text-xs text-orange-900/80 space-y-2 leading-relaxed">
                        <p><strong>1. Төлбөр:</strong> Төлбөр захиалга эхлэх үед Filezone системд түр байршина.</p>
                        <p><strong>2. Хугацаа:</strong> Гүйцэтгэгч 24 цагийн дотор ажлыг эхлүүлэх үүрэгтэй.</p>
                        <p><strong>3. Цуцлалт:</strong> 24 цагт эхлээгүй бол автомат цуцлагдана. Хүлээлгэн өгсөн ажилд 72 цагийн дотор хариу өгөхгүй бол автомат баталгаажна.</p>
                        <p><strong>4. Гомдол:</strong> Маргаантай асуудлыг админд мэдэгдэж шийдвэрлүүлнэ.</p>
                    </div>

                    <div id="full-terms" class="hidden mt-3 pt-3 border-t border-orange-200 text-xs text-orange-900/80 space-y-2">
                        <p><strong>5. Зохиогчийн эрх:</strong> Төлбөр төлөгдсөнөөр эрх захиалагчид шилжинэ.</p>
                        <p><strong>6. Нууцлал:</strong> Хувийн мэдээллийг задруулахыг хориглоно.</p>
                        <div class="mt-2 text-right">
                            <a href="../terms.php" target="_blank" class="underline font-bold">Дэлгэрэнгүй</a>
                        </div>
                    </div>

                    <button onclick="document.getElementById('full-terms').classList.toggle('hidden'); this.textContent = this.textContent === 'Дэлгэрэнгүй унших' ? 'Хураах' : 'Дэлгэрэнгүй унших';" class="block w-full mt-3 text-xs text-orange-700 font-bold hover:text-orange-900 text-center border-t border-orange-200 pt-2 transition-colors">
                        Дэлгэрэнгүй унших
                    </button>
                </div>

            </div>
        </div>
    </main>
</div>

<!-- SCRIPTS -->
<script>
// --- Countdown Timer Logic ---
function updateCountdowns() {
    const now = Math.floor(Date.now() / 1000);
    
    // 1. Start Deadline (24 hours)
    const startDeadline = <?php echo $js_start_deadline; ?>;
    const startElem = document.getElementById('start-timer');
    if (startElem) {
        const diff = startDeadline - now;
        if (diff > 0) {
            const h = Math.floor(diff / 3600);
            const m = Math.floor((diff % 3600) / 60);
            const s = diff % 60;
            startElem.textContent = `Үлдсэн хугацаа: ${h}ц ${m}м ${s}с`;
            if (h < 1) startElem.classList.add('text-red-600', 'animate-pulse');
        } else {
            startElem.textContent = "Хугацаа дууссан! Тун удахгүй цуцлагдана.";
        }
    }

    // 2. Auto Complete (72 hours)
    const completeDeadline = <?php echo $js_auto_complete; ?>;
    const completeElem = document.getElementById('auto-complete-timer');
    if (completeElem && completeDeadline > 0) {
        const diff = completeDeadline - now;
        if (diff > 0) {
            const h = Math.floor(diff / 3600);
            const m = Math.floor((diff % 3600) / 60);
            completeElem.textContent = `Хариу өгөхгүй бол ${h}ц ${m}м дараа хаагдана.`;
        } else {
            completeElem.textContent = "Тун удахгүй автоматаар хаагдана.";
        }
    }
}
setInterval(updateCountdowns, 1000);
updateCountdowns();

// --- Order Actions Logic ---
async function confirmOrderAction(action, msg) {
    if(!confirm(msg)) return;
    try {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('order_id', <?php echo $order_id; ?>);
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            alert("Амжилттай!");
            location.reload();
        } else {
            alert("Алдаа: " + (result.message || "Unknown error"));
        }
    } catch (error) {
        console.error(error);
        alert("Network error.");
    }
}

// --- Request Revision Logic ---
async function requestRevision() {
    const reason = prompt("Засвар хийлгэх шалтгаанаа дэлгэрэнгүй бичнэ үү:");
    if (reason === null) return; 
    if (reason.trim() === "") {
        alert("Шалтгаанаа бичнэ үү.");
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'request_revision');
        formData.append('order_id', <?php echo $order_id; ?>);
        formData.append('reason', reason);
        
        const response = await fetch(window.location.href, { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            alert("Засвар хийх хүсэлт илгээгдлээ. Гүйцэтгэгч танд хариу өгөх болно.");
            sendChatMessage("ЗАСВАР ХҮСЭЛТ: " + reason);
            setTimeout(() => location.reload(), 1000);
        } else {
            alert("Алдаа: " + (result.message || "Unknown error"));
        }
    } catch (error) {
        console.error(error);
        alert("Сүлжээний алдаа.");
    }
}

// Helper to send chat message
function sendChatMessage(text) {
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('order_id', <?php echo $order_id; ?>);
    formData.append('message', text);
    fetch('../api/chat.php', { method: 'POST', body: formData });
}

// --- Chat Logic ---
document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('chatBox');
    const chatForm = document.getElementById('chatForm');
    const msgInput = document.getElementById('messageInput');
    const fileInput = document.getElementById('fileInput');
    const attachBtn = document.getElementById('attachBtn');
    const filePreview = document.getElementById('filePreview');
    const previewName = document.getElementById('previewName');
    const removeFileBtn = document.getElementById('removeFileBtn');
    const sendBtn = document.getElementById('sendBtn');
    const orderId = document.querySelector('input[name="order_id"]').value;
    
    let isUserScrolledUp = false;

    // Scroll detection
    chatBox.addEventListener('scroll', () => {
        if (chatBox.scrollTop + chatBox.clientHeight < chatBox.scrollHeight - 50) {
            isUserScrolledUp = true;
        } else {
            isUserScrolledUp = false;
        }
    });

    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function loadMessages() {
        const formData = new FormData();
        formData.append('action', 'get_messages');
        formData.append('order_id', orderId);

        fetch('../api/chat.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (chatBox.innerHTML !== data.html) {
                    chatBox.innerHTML = data.html;
                    if (!isUserScrolledUp) {
                        scrollToBottom();
                    }
                }
            }
        });
    }

    // --- File Handling ---
    attachBtn.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            if (file.size > 10 * 1024 * 1024) {
                alert("Файлын хэмжээ хэт том байна (Max 10MB).");
                this.value = '';
                return;
            }
            previewName.textContent = file.name;
            filePreview.classList.remove('hidden');
            msgInput.placeholder = "Файл тайлбар (сонголттой)...";
        }
    });

    removeFileBtn.addEventListener('click', () => {
        fileInput.value = '';
        filePreview.classList.add('hidden');
        msgInput.placeholder = "Зурвас бичих...";
    });

    // --- Send Message ---
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = msgInput.value.trim();
        const hasFile = fileInput.files.length > 0;

        if (!message && !hasFile) return;

        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        sendBtn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('order_id', orderId);
        formData.append('message', message);
        if (hasFile) {
            formData.append('attachment', fileInput.files[0]);
        }

        msgInput.value = ''; 
        fileInput.value = '';
        filePreview.classList.add('hidden');
        msgInput.placeholder = "Зурвас бичих...";

        fetch('../api/chat.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            sendBtn.disabled = false;

            if (data.success) {
                loadMessages();
                scrollToBottom();
            } else {
                alert('Алдаа: ' + data.error);
            }
        })
        .catch(err => {
            console.error(err);
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            sendBtn.disabled = false;
            alert("Сүлжээний алдаа гарлаа.");
        });
    });

    // Initial load & Polling
    loadMessages();
    setInterval(loadMessages, 3000);
});
</script>

<?php include '../includes/footer.php'; ?>