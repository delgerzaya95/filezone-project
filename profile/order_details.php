<?php
// profile/order_details.php
session_start();
require_once '../includes/db.php';
// Notifications файл байгаа эсэхийг шалгаад дуудна
if (file_exists('../includes/notifications.php')) {
    require_once '../includes/notifications.php';
}

// Check DB
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
// 1. User Info & Sidebar Logic
// --------------------------------------------------------------------------
$stmt_u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_u->execute([$user_id]);
$user_data = $stmt_u->fetch(PDO::FETCH_ASSOC);
$username = $user_data['username'] ?? 'User';
$email = $user_data['email'] ?? '';
$user_bio = $user_data['bio'] ?? '';

// Skills logic
$skills_array = [];
try {
    $stmt_s = $pdo->prepare("SELECT skill_name as name, skill_level as level FROM user_skills WHERE user_id = ?");
    $stmt_s->execute([$user_id]);
    $skills_array = $stmt_s->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Avatar Logic
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
    $stmt = $pdo->prepare("SELECT id, seller_id, buyer_id, price, status, service_id FROM service_orders WHERE id = ?");
    $stmt->execute([$ord_id]);
    $ord = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ord) {
        echo json_encode(['success' => false, 'message' => 'Захиалга олдсонгүй.']);
        exit;
    }

    // A. DELIVER ORDER
    if ($_POST['action'] == 'deliver_order') {
        if ($ord['seller_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Танд эрх байхгүй.']);
            exit;
        }
        if ($ord['status'] != 'active' && $ord['status'] != 'pending') {
            echo json_encode(['success' => false, 'message' => 'Захиалга идэвхтэй биш байна.']);
            exit;
        }

        try {
            $upd = $pdo->prepare("UPDATE service_orders SET status = 'delivered', delivered_at = NOW() WHERE id = ?");
            $upd->execute([$ord_id]);

            // Notification
            if(function_exists('sendNotification')) {
                sendNotification($pdo, $ord['buyer_id'], 'success', "Таны захиалга (#{$ord_id}) хийгдэж дууслаа! Та ажлаа шалгаад хүлээн авна уу.", "profile/order_details.php?id={$ord_id}");
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // B. COMPLETE ORDER
    if ($_POST['action'] == 'complete_order') {
        if ($ord['buyer_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Танд эрх байхгүй.']);
            exit;
        }
        if ($ord['status'] != 'delivered') {
            echo json_encode(['success' => false, 'message' => 'Ажил хүлээлгэн өгөөгүй байна.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $upd = $pdo->prepare("UPDATE service_orders SET status = 'completed', completed_at = NOW() WHERE id = ?");
            $upd->execute([$ord_id]);

            $sub = $pdo->prepare("UPDATE users SET pending_balance = pending_balance - ? WHERE id = ?");
            $sub->execute([$ord['price'], $ord['seller_id']]);
            
            $add = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $add->execute([$ord['price'], $ord['seller_id']]);

            if(function_exists('sendNotification')) {
                sendNotification($pdo, $ord['seller_id'], 'success', "Баяр хүргэе! Захиалга #{$ord_id} амжилттай хаагдлаа. Төлбөр шилжлээ.", "profile/wallet.php");
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// --------------------------------------------------------------------------
// 3. MAIN PAGE LOGIC
// --------------------------------------------------------------------------

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    header("Location: my_orders.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT 
        so.*, 
        s.title as service_title, 
        s.cover_image, 
        s.delivery_time, 
        s.delivery_unit,
        buyer.id as buyer_id_real,
        buyer.username as buyer_username, 
        buyer.full_name as buyer_fullname, 
        buyer.phone as buyer_phone,
        buyer.email as buyer_email,
        buyer.avatar_url as buyer_avatar,
        seller.id as seller_id_real,
        seller.username as seller_username, 
        seller.full_name as seller_fullname, 
        seller.phone as seller_phone,
        seller.email as seller_email,
        seller.avatar_url as seller_avatar
    FROM service_orders so
    JOIN services s ON so.service_id = s.id
    JOIN users buyer ON so.buyer_id = buyer.id
    JOIN users seller ON so.seller_id = seller.id
    WHERE so.id = ? AND (so.buyer_id = ? OR so.seller_id = ?)
");
$stmt->execute([$order_id, $user_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Захиалга олдсонгүй.");
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

// Deadlines
$ordered_date = new DateTime($order['ordered_at']);
$deadline = clone $ordered_date;
$unit_map = ['hour' => 'hours', 'day' => 'days', 'week' => 'weeks', 'month' => 'months'];
$deadline->modify('+' . $order['delivery_time'] . ' ' . ($unit_map[$order['delivery_unit']] ?? 'days'));
$now = new DateTime();
$is_late = ($now > $deadline && $order['status'] == 'active');

// Badge Helper
function getStatusBadgeLarge($status) {
    switch($status) {
        case 'active': return '<span class="px-4 py-2 rounded-full text-sm font-bold bg-blue-100 text-blue-700 border border-blue-200"><i class="fas fa-spinner fa-spin mr-2"></i> Идэвхтэй</span>';
        case 'delivered': return '<span class="px-4 py-2 rounded-full text-sm font-bold bg-purple-100 text-purple-700 border border-purple-200"><i class="fas fa-gift mr-2"></i> Хүлээлгэн өгсөн</span>';
        case 'completed': return '<span class="px-4 py-2 rounded-full text-sm font-bold bg-green-100 text-green-700 border border-green-200"><i class="fas fa-check-circle mr-2"></i> Дууссан</span>';
        default: return '<span class="px-4 py-2 rounded-full text-sm font-bold bg-gray-100 text-gray-700">' . ucfirst($status) . '</span>';
    }
}

// Buyer Review Logic
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

        <!-- ACTION BAR -->
        <?php if($is_buyer && $order['status'] == 'delivered'): ?>
            <div class="bg-purple-50 border border-purple-200 rounded-xl p-6 mb-8 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
                <div>
                    <h3 class="font-bold text-purple-900 text-lg mb-1"><i class="fas fa-gift mr-2"></i> Ажил хүлээлгэн өгсөн байна!</h3>
                    <p class="text-purple-700 text-sm">Гүйцэтгэгч ажлаа дуусгасан байна. Та ажлаа шалгаад "Хүлээн авах" товчийг дарж баталгаажуулна уу.</p>
                </div>
                <button onclick="confirmOrderAction('complete_order', 'Та ажлыг шалгаж дуусаад хүлээн авахдаа итгэлтэй байна уу?')" class="bg-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-purple-700 transition shadow-lg shadow-purple-500/30 whitespace-nowrap">
                    <i class="fas fa-check-circle mr-2"></i> Хүлээн авах & Дуусгах
                </button>
            </div>
        <?php endif; ?>

        <?php if($is_seller): ?>
            <?php if($order['status'] == 'active' || $order['status'] == 'pending'): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
                    <div>
                        <h3 class="font-bold text-blue-900 text-lg mb-1"><i class="fas fa-tools mr-2"></i> Ажил хийгдэж байна</h3>
                        <p class="text-blue-700 text-sm">Та ажлаа бүрэн дуусгасан бол "Хүлээлгэн өгөх" товчийг дарна уу.</p>
                    </div>
                    <button onclick="confirmOrderAction('deliver_order', 'Та ажлаа бүрэн дуусгаад хүлээлгэн өгөх гэж байна уу?')" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-500/30 whitespace-nowrap">
                        <i class="fas fa-paper-plane mr-2"></i> Ажил хүлээлгэн өгөх
                    </button>
                </div>
            <?php elseif($order['status'] == 'delivered'): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8 flex items-center gap-4 shadow-sm">
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600 flex-shrink-0">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-yellow-900 text-lg">Захиалагчийг хүлээж байна</h3>
                        <p class="text-yellow-700 text-sm">Та ажлаа хүлээлгэн өгсөн байна. Захиалагч ажлыг шалгаж баталгаажуулсны дараа төлбөр таны дансанд орно.</p>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>


        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- LEFT: Timeline, Service & CHAT -->
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

                <!-- CHAT SYSTEM START -->
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
                    <div class="p-4 bg-gray-50 border-t border-gray-200">
                        <!-- Preview Area for selected file -->
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
                </div>
                <!-- CHAT SYSTEM END -->

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

            <!-- RIGHT: Contact & Legal & Timeline -->
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

                <!-- Timeline (Moved to Right for better layout) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="font-bold text-gray-900 mb-6">Захиалгын явц</h3>
                    <div class="relative">
                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                        
                        <!-- Ordered -->
                        <div class="relative flex items-start mb-8 group">
                            <div class="absolute left-0 w-8 h-8 flex items-center justify-center bg-green-500 rounded-full border-4 border-white z-10"><i class="fas fa-check text-white text-xs"></i></div>
                            <div class="ml-12">
                                <h4 class="text-sm font-bold text-gray-900">Захиалга үүссэн</h4>
                                <p class="text-xs text-gray-500 mt-1"><?php echo date('Y-m-d H:i', strtotime($order['ordered_at'])); ?></p>
                            </div>
                        </div>

                        <!-- Active -->
                        <div class="relative flex items-start mb-8 group">
                            <?php $s2 = ($order['status'] != 'pending') ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-500'; if($order['status']=='completed'||$order['status']=='delivered') $s2='bg-green-500 text-white'; ?>
                            <div class="absolute left-0 w-8 h-8 flex items-center justify-center <?php echo $s2; ?> rounded-full border-4 border-white z-10"><i class="fas fa-spinner text-xs"></i></div>
                            <div class="ml-12">
                                <h4 class="text-sm font-bold text-gray-900">Гүйцэтгэж байна</h4>
                                <p class="text-xs text-gray-600 mt-1">Дуусах хугацаа: <?php echo $deadline->format('Y-m-d'); ?></p>
                            </div>
                        </div>

                        <!-- Delivered -->
                        <div class="relative flex items-start mb-8 group">
                            <?php $s3 = ($order['status']=='delivered'||$order['status']=='completed') ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-500'; ?>
                            <div class="absolute left-0 w-8 h-8 flex items-center justify-center <?php echo $s3; ?> rounded-full border-4 border-white z-10"><i class="fas fa-gift text-xs"></i></div>
                            <div class="ml-12">
                                <h4 class="text-sm font-bold text-gray-900">Хүлээлгэн өгсөн</h4>
                                <?php if($order['delivered_at']): ?>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo date('Y-m-d H:i', strtotime($order['delivered_at'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Completed -->
                        <div class="relative flex items-start group">
                            <?php $s4 = ($order['status']=='completed') ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-500'; ?>
                            <div class="absolute left-0 w-8 h-8 flex items-center justify-center <?php echo $s4; ?> rounded-full border-4 border-white z-10"><i class="fas fa-check-circle text-xs"></i></div>
                            <div class="ml-12">
                                <h4 class="text-sm font-bold text-gray-900">Дууссан</h4>
                                <?php if($order['completed_at']): ?>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo date('Y-m-d H:i', strtotime($order['completed_at'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Legal / Contract Card (Restored) -->
                <div class="bg-orange-50 rounded-xl border border-orange-200 p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-3 text-orange-800">
                        <i class="fas fa-file-contract text-lg"></i>
                        <h3 class="font-bold text-sm">Харилцагчийн Гэрээ & Санамж</h3>
                    </div>
                    
                    <div class="text-xs text-orange-900/80 space-y-2 leading-relaxed">
                        <p><strong>1. Төлбөрийн нөхцөл:</strong> Төлбөр захиалга эхлэх үед Filezone системд түр байршина. Захиалагч ажлыг хүлээн авч баталгаажуулсны дараа гүйцэтгэгчид шилжинэ.</p>
                        <p><strong>2. Хугацаа:</strong> Гүйцэтгэгч заасан хугацаандаа ажлыг хүлээлгэн өгөх үүрэгтэй. Хугацаа хэтэрвэл захиалагч цуцлах эрхтэй.</p>
                        <p><strong>3. Маргаан шийдвэрлэх:</strong> Ажил чанарын шаардлага хангахгүй бол захиалагч засвар хийлгэх эсвэл админд хандаж маргаан үүсгэх эрхтэй.</p>
                    </div>

                    <div id="full-terms" class="hidden mt-3 pt-3 border-t border-orange-200 text-xs text-orange-900/80 space-y-2">
                        <p><strong>4. Зохиогчийн эрх:</strong> Ажил хүлээлгэн өгч, төлбөр төлөгдсөнөөр оюуны өмчийн эрх захиалагчид бүрэн шилжинэ.</p>
                        <p><strong>5. Нууцлал:</strong> Талууд бие биенийхээ хувийн мэдээлэл болон ажлын явцад олж авсан мэдээллийг гуравдагч этгээдэд задруулахгүй байх үүрэгтэй.</p>
                        <p><strong>6. Хориглох зүйл:</strong> Filezone платформоос гадуур гүйлгээ хийхийг хатуу хориглоно. Гадуур хийсэн гүйлгээнд Filezone хариуцлага хүлээхгүй.</p>
                        <div class="mt-2 text-right">
                            <a href="../terms.php" target="_blank" class="underline font-bold">Үйлчилгээний нөхцөл дэлгэрэнгүй</a>
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
            // Check size (10MB)
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

        // UI Feedback
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        sendBtn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('order_id', orderId);
        formData.append('message', message);
        if (hasFile) {
            formData.append('attachment', fileInput.files[0]);
        }

        // Clear UI immediately
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