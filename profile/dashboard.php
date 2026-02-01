<?php
// profile/dashboard.php
session_start();

// 1. Include paths (Going UP one level)
require_once '../includes/db.php';

// Check DB Connection
if (!isset($conn)) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- FETCH USER DATA ---
$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

$username = $user_data['username'] ?? 'User';
$email = $user_data['email'] ?? '';
$balance = $user_data['balance'] ?? 0;
$user_level_key = $user_data['level'] ?? 'new';

// Avatar Logic
$db_avatar = $user_data['avatar_url'];
$avatar = "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=random&color=fff";
if (!empty($db_avatar)) {
    if (strpos($db_avatar, 'http') === 0) {
        $avatar = $db_avatar;
    } else {
        // We are in 'profile/', uploads are in '../uploads/'
        // DB stores 'uploads/...'
        if (file_exists('../' . $db_avatar)) {
            $avatar = '../' . $db_avatar;
        }
    }
}

// --- STATS (NET INCOME CALCULATION) ---
// Шимтгэл хассан цэвэр орлогыг тооцоолох (Default 10% commission assumed if not stored)
// Хэрэв гүйлгээ тус бүр дээр шимтгэл хадгалагддаг бол `t.net_amount` гэх мэт багана ашиглах хэрэгтэй.
// Одоогоор нийт дүнгээс 10% хасаж харуулъя (Жишээ нь 10%).
$COMMISSION_RATE = 0.10; // 10% Platform fee

$sql_income = "SELECT SUM(t.amount) as total FROM transactions t JOIN files f ON t.file_id = f.id WHERE f.user_id = ? AND t.status = 'success'";
$stmt = $conn->prepare($sql_income);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$gross_income = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Цэвэр орлого (Net Income) = Нийт орлого * (1 - 0.10)
$net_income = $gross_income * (1 - $COMMISSION_RATE);

$sql_files_count = "SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND status = 'approved'";
$stmt = $conn->prepare($sql_files_count);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$active_files = $stmt->get_result()->fetch_assoc()['cnt'];

$sql_downloads = "SELECT SUM(download_count) as total FROM files WHERE user_id = ?";
$stmt = $conn->prepare($sql_downloads);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_downloads = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

$sql_orders = "SELECT COUNT(*) as cnt FROM service_orders WHERE seller_id = ? AND status IN ('pending', 'active')";
$stmt = $conn->prepare($sql_orders);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$active_orders = $stmt->get_result()->fetch_assoc()['cnt'];

// --- LEVEL ---
$levels = [
    'new' => ['name' => 'Шинэ хэрэглэгч', 'min_income' => 0, 'next' => 'level_1'],
    'level_1' => ['name' => 'Идэвхтэй борлуулагч', 'min_income' => 100000, 'next' => 'level_2'],
    'level_2' => ['name' => 'Туршлагатай борлуулагч', 'min_income' => 500000, 'next' => 'pro_seller'],
    'pro_seller' => ['name' => 'Pro Борлуулагч', 'min_income' => 1000000, 'next' => null],
];
$current_level_info = $levels[$user_level_key] ?? $levels['new'];
$current_level_name = $current_level_info['name'];
$next_level_key = $current_level_info['next'];
$progress_percent = 0; $needed_amount = 0; $next_level_name = "";

// Level progression based on GROSS or NET? Usually Gross Sales Volume. Let's use Gross.
if ($next_level_key && isset($levels[$next_level_key])) {
    $next_level_target = $levels[$next_level_key]['min_income'];
    $next_level_name = $levels[$next_level_key]['name'];
    $current_level_min = $current_level_info['min_income'];
    $level_range = $next_level_target - $current_level_min;
    $current_progress = $gross_income - $current_level_min; // Using Gross Income for Leveling
    
    $progress_percent = ($level_range > 0) ? ($current_progress / $level_range) * 100 : 100;
    $progress_percent = max(0, min(100, $progress_percent));
    $needed_amount = max(0, $next_level_target - $gross_income);
} else {
    $progress_percent = 100;
    $next_level_name = "Дээд түвшин";
}

// --- RECENT SALES ---
$sql_sales = "SELECT 'file' as type, f.title, t.amount, t.transaction_date, u.username FROM transactions t JOIN files f ON t.file_id = f.id JOIN users u ON t.user_id = u.id WHERE f.user_id = ? AND t.status = 'success' ORDER BY t.transaction_date DESC LIMIT 3";
$stmt = $conn->prepare($sql_sales);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_sales = $stmt->get_result();

// --- MY FILES (Top 5) ---
$sql_files = "SELECT id, title, file_type, price, download_count FROM files WHERE user_id = ? ORDER BY upload_date DESC LIMIT 5";
$stmt = $conn->prepare($sql_files);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_files = $stmt->get_result();

// --- NOTIFICATIONS ---
$notifications_data = [];
$sql_notif = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($sql_notif);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $notif_result = $stmt->get_result();
    if ($notif_result && $notif_result->num_rows > 0) {
        while($row = $notif_result->fetch_assoc()) {
            $notifications_data[] = $row;
        }
    }
}

// Icon Helper
function getFileIcon($type) {
    $type = strtolower($type);
    $icons = [
        'pdf' => 'fa-file-pdf text-red-500', 
        'doc' => 'fa-file-word text-blue-500', 
        'docx' => 'fa-file-word text-blue-500',
        'xls' => 'fa-file-excel text-green-500', 
        'xlsx' => 'fa-file-excel text-green-500', 
        'ppt' => 'fa-file-powerpoint text-orange-500', 
        'pptx' => 'fa-file-powerpoint text-orange-500',
        'zip' => 'fa-file-archive text-yellow-600', 
        'rar' => 'fa-file-archive text-yellow-600',
        'jpg' => 'fa-file-image text-purple-500', 
        'jpeg' => 'fa-file-image text-purple-500',
        'png' => 'fa-file-image text-purple-500',
        'mp3' => 'fa-file-audio text-pink-500',
        'mp4' => 'fa-file-video text-red-600',
        'other' => 'fa-file text-gray-400'
    ];
    return $icons[$type] ?? 'fa-file text-gray-400';
}

date_default_timezone_set('Asia/Ulaanbaatar');
$hour = date('H');
$greeting = ($hour < 12) ? "Өглөөний мэнд" : (($hour < 18) ? "Өдрийн мэнд" : "Оройн мэнд");
$sub_greeting = "Өнөөдрийн ажлын бүтээмж хэр байна даа?";
$pageTitle = "Хяналтын самбар - " . htmlspecialchars($username);

// Use the NEW PROFILE HEADER
include 'header.php'; 
?>

<!-- JavaScript for Avatar Upload -->
<script>
function uploadAvatar(input) {
    if (input.files && input.files[0]) {
        let formData = new FormData(document.getElementById('avatar-form'));
        let img = document.getElementById('user-avatar');
        let oldSrc = img.src;
        img.style.opacity = '0.5';

        // URL points to profile/upload_avatar.php
        fetch('upload_avatar.php', { method: 'POST', body: formData })
        .then(res => res.text().then(text => {
            try { return JSON.parse(text); } catch (e) { console.error("Non-JSON:", text); throw new Error("Server Error"); }
        }))
        .then(data => {
            if(data.success) { 
                // data.new_avatar_url returns 'uploads/...' (DB Path)
                // We need '../uploads/...' to display it here
                img.src = '../' + data.new_avatar_url + '?t=' + new Date().getTime(); 
            }
            else { alert(data.error || data.message); img.src = oldSrc; }
            img.style.opacity = '1';
        })
        .catch(err => { console.error(err); alert('Upload Failed'); img.src = oldSrc; img.style.opacity = '1'; });
    }
}

function toggleModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    if (modal.classList.contains('hidden')) {
        modal.classList.remove('hidden');
    } else {
        modal.classList.add('hidden');
    }
}
</script>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
        
        <!-- DASHBOARD HEADER -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 fade-in">
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><?php echo $greeting; ?>, <?php echo htmlspecialchars($username); ?>!</h1>
                <p class="text-sm text-gray-500"><?php echo $sub_greeting; ?></p>
            </div>
            <div class="flex gap-3">
                <a href="../upload.php" class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm"><i class="fas fa-plus mr-1"></i> Файл нэмэх</a>
                <a href="../add_service.php" class="bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm shadow-blue-500/30"><i class="fas fa-briefcase mr-1"></i> Үйлчилгээ нэмэх</a>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8 fade-in">
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden group">
                <div class="flex justify-between items-start mb-4 relative z-10">
                    <div>
                        <p class="text-gray-500 text-xs uppercase font-medium">Цэвэр орлого</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-1"><?php echo number_format($net_income); ?>₮</h3>
                        <p class="text-[10px] text-gray-400 mt-1" title="Шимтгэл хасагдаагүй дүн: <?php echo number_format($gross_income); ?>₮">
                            Нийт борлуулалт: <?php echo number_format($gross_income); ?>₮
                        </p>
                    </div>
                    <div class="w-10 h-10 bg-green-100 text-green-600 rounded-lg flex items-center justify-center"><i class="fas fa-wallet"></i></div>
                </div>
                <div class="text-xs text-green-600 font-medium flex items-center gap-1"><i class="fas fa-check-circle"></i> Шимтгэл хасагдсан</div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <div class="flex justify-between items-start mb-4">
                    <div><p class="text-gray-500 text-xs uppercase font-medium">Идэвхтэй файл</p><h3 class="text-2xl font-bold text-gray-900 mt-1"><?php echo $active_files; ?></h3></div>
                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center"><i class="fas fa-file-alt"></i></div>
                </div>
                <div class="text-xs text-gray-500">Нийт таталт: <span class="font-bold text-gray-800"><?php echo $total_downloads; ?></span></div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <div class="flex justify-between items-start mb-4">
                    <div><p class="text-gray-500 text-xs uppercase font-medium">Идэвхтэй захиалга</p><h3 class="text-2xl font-bold text-gray-900 mt-1"><?php echo $active_orders; ?></h3></div>
                    <div class="w-10 h-10 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center"><i class="fas fa-tasks"></i></div>
                </div>
                <div class="text-xs text-orange-500 font-medium flex items-center gap-1"><i class="fas fa-clock"></i> Шалгах шаардлагатай</div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 p-5 rounded-xl text-white shadow-lg relative overflow-hidden">
                <div class="flex justify-between items-start mb-4 relative z-10">
                    <div><p class="text-gray-400 text-xs uppercase font-medium">Үлдэгдэл</p><h3 class="text-2xl font-bold text-white mt-1"><?php echo number_format($balance); ?>₮</h3></div>
                    <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center"><i class="fas fa-coins text-yellow-400"></i></div>
                </div>
                <a href="wallet.php" class="block w-full text-center bg-white/10 hover:bg-white/20 text-white text-xs font-bold py-2 rounded-lg transition border border-white/10">Мөнгө татах</a>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 fade-in">
            <div class="lg:col-span-2 space-y-6">
                <!-- Recent Sales -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h3 class="font-bold text-gray-900 text-sm">Сүүлийн борлуулалт (Цэвэр ашиг)</h3>
                        <a href="wallet.php" class="text-xs font-medium text-brand-600 hover:text-brand-700">Бүгдийг үзэх</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <?php if ($recent_sales->num_rows > 0): while($sale = $recent_sales->fetch_assoc()): 
                            // Individual sale net amount (10% fee)
                            $net_sale_amount = $sale['amount'] * (1 - $COMMISSION_RATE);
                        ?>
                        <div class="p-4 flex items-center gap-4 hover:bg-gray-50 transition">
                            <div class="w-10 h-10 bg-green-50 text-green-600 rounded-lg flex items-center justify-center flex-shrink-0 font-bold text-xs uppercase"><i class="fas fa-file-invoice-dollar"></i></div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($sale['title']); ?></h4>
                                <p class="text-xs text-gray-500"><?php echo date('Y-m-d H:i', strtotime($sale['transaction_date'])); ?> • <?php echo htmlspecialchars($sale['username']); ?></p>
                            </div>
                            <div class="text-right">
                                <span class="block font-bold text-green-600 text-sm">+<?php echo number_format($net_sale_amount); ?>₮</span>
                                <span class="text-[10px] text-gray-400 line-through"><?php echo number_format($sale['amount']); ?>₮</span>
                            </div>
                        </div>
                        <?php endwhile; else: ?><div class="p-6 text-center text-gray-500 text-sm">Одоогоор борлуулалт хийгдээгүй байна.</div><?php endif; ?>
                    </div>
                </div>

                <!-- My Files (Short list Widget) -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h3 class="font-bold text-gray-900 text-sm">Миний файлууд</h3>
                        <div class="flex gap-2">
                            <a href="my_files.php" class="text-xs font-medium text-gray-500 hover:text-gray-900">Бүгдийг үзэх</a>
                            <a href="../upload.php" class="text-xs font-medium text-blue-600 hover:text-blue-700">+ Шинэ</a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr><th class="px-6 py-3">Файлын нэр</th><th class="px-6 py-3">Үнэ</th><th class="px-6 py-3">Таталт</th><th class="px-6 py-3 text-right">Үйлдэл</th></tr>
                            </thead>
                            <tbody>
                                <?php if ($my_files->num_rows > 0): while($file = $my_files->fetch_assoc()): ?>
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap flex items-center gap-3">
                                        <div class="w-8 h-8 rounded bg-gray-100 flex items-center justify-center flex-shrink-0 text-lg">
                                            <i class="<?php echo getFileIcon($file['file_type']); ?>"></i>
                                        </div>
                                        <span class="truncate max-w-xs" title="<?php echo htmlspecialchars($file['title']); ?>"><?php echo htmlspecialchars($file['title']); ?></span>
                                    </td>
                                    <td class="px-6 py-4"><?php echo ($file['price'] > 0) ? number_format($file['price']) . '₮' : '<span class="text-green-600">Үнэгүй</span>'; ?></td>
                                    <td class="px-6 py-4"><?php echo $file['download_count']; ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="edit_file.php?id=<?php echo $file['id']; ?>" class="text-blue-600 hover:text-blue-900 font-medium">Засах</a>
                                            <a href="delete_file.php?id=<?php echo $file['id']; ?>" class="text-red-600 hover:text-red-900 font-medium" onclick="return confirm('Устгахдаа итгэлтэй байна уу?');">Устгах</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?><tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Файл оруулаагүй байна.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Account Level -->
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                    <h3 class="font-bold text-gray-900 text-sm mb-4">Дансны төлөв</h3>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-500">Түвшин</span>
                        <span class="text-sm font-bold text-brand-600 uppercase"><?php echo $current_level_name; ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5 mb-4 relative">
                        <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-1000" style="width: <?php echo $progress_percent; ?>%"></div>
                    </div>
                    <?php if ($needed_amount > 0): ?>
                        <p class="text-xs text-gray-500 mb-6">Дараагийн түвшин <strong>(<?php echo $next_level_name; ?>)</strong>-д хүрэхэд <span class="text-brand-600 font-bold"><?php echo number_format($needed_amount); ?>₮</span>-ийн орлого дутуу байна.</p>
                    <?php else: ?>
                        <p class="text-xs text-green-600 mb-6 font-medium"><i class="fas fa-check-circle mr-1"></i> Баяр хүргэе! Та дээд түвшинд байна.</p>
                    <?php endif; ?>
                    <button onclick="toggleModal('levelModal')" class="w-full border border-gray-200 text-gray-600 text-xs font-bold py-2 rounded-lg hover:bg-gray-50 transition">Дэлгэрэнгүй үзэх</button>
                </div>

                <!-- Notifications -->
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                    <h3 class="font-bold text-gray-900 text-sm mb-4">Мэдэгдэл</h3>
                    <div class="space-y-4">
                        <?php if (!empty($notifications_data)): 
                            foreach($notifications_data as $notif): 
                            $is_read = $notif['is_read'];
                            $icon_bg = $is_read ? 'bg-gray-200' : 'bg-blue-100 text-blue-600';
                            $msg_color = $is_read ? 'text-gray-600' : 'text-gray-900 font-medium';
                        ?>
                        <div class="flex gap-3">
                            <div class="w-8 h-8 <?php echo $icon_bg; ?> rounded-full flex items-center justify-center flex-shrink-0 text-xs">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div>
                                <p class="text-xs <?php echo $msg_color; ?>"><?php echo htmlspecialchars($notif['message']); ?></p>
                                <span class="text-[10px] text-gray-400"><?php echo date('m-d H:i', strtotime($notif['created_at'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                            <div class="text-center py-4 text-gray-400 text-xs">
                                <i class="far fa-bell-slash text-xl mb-2 block"></i>
                                Одоогоор шинэ мэдэгдэл алга.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal -->
<div id="levelModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white w-11/12 md:max-w-md mx-auto rounded-xl shadow-lg z-50 overflow-y-auto">
        <div class="py-4 text-left px-6">
            <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                <p class="text-lg font-bold text-gray-900">Хэрэглэгчийн түвшин</p>
                <div class="cursor-pointer z-50 p-2 text-gray-500 hover:text-gray-700" onclick="toggleModal('levelModal')"><i class="fas fa-times"></i></div>
            </div>
            <div class="my-5 space-y-4">
                <p class="text-sm text-gray-600 mb-4">Таны нийт орлого: <span class="font-bold text-brand-600"><?php echo number_format($gross_income); ?>₮</span></p>
                <?php foreach($levels as $key => $lvl): 
                    $is_current = ($key === $user_level_key);
                    $border_class = $is_current ? 'border-brand-500 ring-1 ring-brand-500 bg-brand-50' : 'border-gray-200';
                ?>
                <div class="border rounded-lg p-3 <?php echo $border_class; ?> relative">
                    <?php if($is_current): ?><span class="absolute top-0 right-0 bg-brand-600 text-white text-[10px] px-2 py-0.5 rounded-bl-lg font-bold">Та энд байна</span><?php endif; ?>
                    <div class="flex justify-between items-center">
                        <div><h4 class="font-bold text-sm text-gray-900"><?php echo $lvl['name']; ?></h4><p class="text-xs text-gray-500">Шаардлага: <?php echo number_format($lvl['min_income']); ?>₮</p></div>
                        <div class="text-gray-300"><i class="fas fa-star"></i></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="flex justify-end pt-2"><button onclick="toggleModal('levelModal')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">Хаах</button></div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>