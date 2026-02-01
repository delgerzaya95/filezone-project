<?php
session_start();
require_once '../includes/db.php';
// Brevo Email Handler оруулж ирэх
if (file_exists('../admin/api/brevo_admin.php')) {
    require_once '../admin/api/brevo_admin.php';
}

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    header("Location: my_orders.php");
    exit();
}

// Захиалга байгаа эсэх болон хэрэглэгч хамааралтай эсэхийг шалгах
$stmt = $pdo->prepare("
    SELECT so.id, so.status, s.title as service_title 
    FROM service_orders so 
    JOIN services s ON so.service_id = s.id
    WHERE so.id = ? AND (so.buyer_id = ? OR so.seller_id = ?)
");
$stmt->execute([$order_id, $user_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Захиалга олдсонгүй эсвэл танд эрх байхгүй байна.");
}

// Хэрэглэгчийн мэдээллийг авах (Мэйл явуулахад нэр нь хэрэгтэй)
$stmt_u = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt_u->execute([$user_id]);
$current_user = $stmt_u->fetch(PDO::FETCH_ASSOC);
$current_username = $current_user['username'] ?? 'User';

// Form Submit Handler
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reason = trim($_POST['reason']);
    $description = trim($_POST['description']);

    if (empty($reason) || empty($description)) {
        $msg = "Та бүх талбарыг бөглөнө үү.";
        $msg_type = "error";
    } else {
        // Өмнө нь энэ захиалга дээр гомдол гаргасан эсэхийг шалгах (pending төлөвтэй)
        $check = $pdo->prepare("SELECT id FROM order_reports WHERE order_id = ? AND reporter_id = ? AND status = 'pending'");
        $check->execute([$order_id, $user_id]);
        
        if ($check->rowCount() > 0) {
            $msg = "Та энэ захиалга дээр аль хэдийн гомдол илгээсэн байна. Админ шалгаж байна.";
            $msg_type = "warning";
        } else {
            try {
                // order_reports хүснэгт рүү бичих
                $stmt = $pdo->prepare("INSERT INTO order_reports (order_id, reporter_id, reason, description, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$order_id, $user_id, $reason, $description]);
                
                $msg = "Таны гомдлыг хүлээн авлаа. Админ удахгүй шалгаж шийдвэрлэх болно.";
                $msg_type = "success";
                
                // Админ руу мэйл илгээх
                if (function_exists('notifyAdminDispute') && defined('SENDER_EMAIL')) {
                    // SENDER_EMAIL-ийг админы мэйл гэж үзэж илгээж байна. 
                    // Хэрэв админы мэйл өөр бол энд шууд бичиж болно.
                    notifyAdminDispute(SENDER_EMAIL, $current_username, $order_id, $reason, $description);
                }
                
            } catch (Exception $e) {
                $msg = "Алдаа гарлаа: " . $e->getMessage();
                $msg_type = "error";
            }
        }
    }
}

$pageTitle = "Маргаан үүсгэх - Order #$order_id";
include 'header.php';
?>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 py-8 px-4 lg:px-8 min-w-0 bg-gray-50">
        <div class="max-w-2xl mx-auto">
            
            <a href="order_details.php?id=<?php echo $order_id; ?>" class="text-sm text-gray-500 hover:text-brand-600 mb-4 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Захиалга руу буцах
            </a>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <div class="border-b border-gray-100 pb-6 mb-6">
                    <h1 class="text-2xl font-bold text-red-600 flex items-center gap-2">
                        <i class="fas fa-gavel"></i> Маргаан үүсгэх / Гомдол мэдээлэх
                    </h1>
                    <p class="text-gray-500 mt-2">
                        Захиалга <strong>#<?php echo $order_id; ?></strong> (<?php echo htmlspecialchars($order['service_title']); ?>)
                    </p>
                </div>

                <?php if ($msg): ?>
                    <div class="p-4 rounded-lg mb-6 <?php echo $msg_type == 'success' ? 'bg-green-50 text-green-700' : ($msg_type == 'warning' ? 'bg-yellow-50 text-yellow-700' : 'bg-red-50 text-red-700'); ?>">
                        <?php echo $msg; ?>
                    </div>
                    <?php if ($msg_type == 'success'): ?>
                        <div class="text-center">
                            <a href="order_details.php?id=<?php echo $order_id; ?>" class="text-brand-600 font-bold hover:underline">Захиалга руу буцах</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($msg_type != 'success'): ?>
                <form method="POST" class="space-y-6">
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-1"></i> 
                        Таны илгээсэн гомдлыг админ баг шалгаад 24-48 цагийн дотор шийдвэрлэж, тантай холбогдох болно. 
                        Энэ хугацаанд төлбөр "Filezone" системд найдвартай хадгалагдана.
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Гомдлын шалтгаан</label>
                        <select name="reason" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition" required>
                            <option value="">Сонгоно уу...</option>
                            <option value="seller_unresponsive">Гүйцэтгэгч хариу өгөхгүй байна</option>
                            <option value="poor_quality">Чанарын шаардлага хангахгүй байна</option>
                            <option value="late_delivery">Хугацаа хэтэрсэн</option>
                            <option value="incomplete_work">Ажил дутуу хийгдсэн</option>
                            <option value="scam_attempt">Луйврын шинжтэй үйлдэл</option>
                            <option value="other">Бусад</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Дэлгэрэнгүй тайлбар</label>
                        <textarea name="description" rows="5" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition" placeholder="Асуудлын талаар дэлгэрэнгүй бичнэ үү..." required></textarea>
                    </div>

                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-red-500/30">
                        Гомдол илгээх
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>