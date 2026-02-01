<?php
// profile/wallet.php
session_start();
include '../includes/db.php';
// Brevo API функцийг агуулсан файлыг дуудах
require_once '../api/brevo_email.php';

// Check DB Connection
if (!isset($conn)) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
}

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = '';
$error = '';

// --- 1. HANDLE WITHDRAWAL REQUEST ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'withdraw') {
    $amount = floatval($_POST['amount']);
    $method = $_POST['method']; // bank, qpay, etc.
    $bank_name = isset($_POST['bank_name']) ? trim($_POST['bank_name']) : '';
    $account_name = isset($_POST['account_name']) ? trim($_POST['account_name']) : '';
    $details = trim($_POST['details']); // Account number
    
    // Check balance & user info
    $sql_user_info = "SELECT balance, username, email FROM users WHERE id = ?";
    $stmt_user_info = $conn->prepare($sql_user_info);
    $stmt_user_info->bind_param("i", $user_id);
    $stmt_user_info->execute();
    $user_res = $stmt_user_info->get_result()->fetch_assoc();
    
    $current_balance = $user_res['balance'];
    $current_username = $user_res['username'];

    if ($amount < 20000) {
        $error = "Татах доод хэмжээ 20,000₮ байна.";
    } elseif ($amount > $current_balance) {
        $error = "Үлдэгдэл хүрэлцэхгүй байна.";
    } elseif (empty($details)) {
        $error = "Дансны дугаараа оруулна уу.";
    } elseif ($method == 'bank' && (empty($bank_name) || empty($account_name))) {
        $error = "Банкны нэр болон данс эзэмшигчийн нэрийг оруулна уу.";
    } else {
        // Start Transaction
        $conn->begin_transaction();
        try {
            // 1. Deduct balance
            $new_balance = $current_balance - $amount;
            $upd_bal = "UPDATE users SET balance = ? WHERE id = ?";
            $stmt_upd = $conn->prepare($upd_bal);
            $stmt_upd->bind_param("di", $new_balance, $user_id);
            $stmt_upd->execute();

            // 2. Create Withdrawal Request
            $txn_num = 'REQ-' . date('Ymd') . '-' . rand(1000, 9999);
            // Updated INSERT to include bank_name and account_name
            $ins_req = "INSERT INTO withdrawal_requests (transaction_number, user_id, amount, method, bank_name, account_name, details, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt_req = $conn->prepare($ins_req);
            $stmt_req->bind_param("sidssss", $txn_num, $user_id, $amount, $method, $bank_name, $account_name, $details);
            $stmt_req->execute();

            // 3. Log Transaction
            $log_txn = "INSERT INTO user_transactions (transaction_number, user_id, type, amount, description) VALUES (?, ?, 'withdrawal', ?, ?)";
            $desc = "Мөнгө татах хүсэлт ({$method}): " . $details;
            $stmt_log = $conn->prepare($log_txn);
            $stmt_log->bind_param("sids", $txn_num, $user_id, $amount, $desc);
            $stmt_log->execute();

            $conn->commit();
            $msg = "Мөнгө татах хүсэлт амжилттай илгээгдлээ. Админ баталгаажуулсны дараа шилжих болно.";

            // 4. Send Email Notification to Admin via Brevo
            // Тохиргоо: Админы имэйл хаяг
            $admin_email = "giamia999@gmail.com"; // Эсвэл өөр админ мэйл
            
            // API файлаас функцээ дуудна
            if (function_exists('sendWithdrawalNotificationEmail')) {
                sendWithdrawalNotificationEmail($admin_email, $current_username, $amount, $method, $bank_name, $account_name, $details, $txn_num);
            }

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Алдаа гарлаа: " . $e->getMessage();
        }
    }
}

// --- 2. FETCH USER DATA (For display) ---
// Re-fetch to show updated balance
$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

$username = $user_data['username'] ?? 'User';
$email = $user_data['email'] ?? '';
$balance = $user_data['balance'] ?? 0;
$pending_balance = $user_data['pending_balance'] ?? 0;

// Avatar
$db_avatar = $user_data['avatar_url'];
$avatar = "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=random&color=fff";
if (!empty($db_avatar)) {
    if (strpos($db_avatar, 'http') === 0) {
        $avatar = $db_avatar;
    } elseif (file_exists('../' . $db_avatar)) {
        $avatar = '../' . $db_avatar;
    }
}

// --- 3. FETCH TRANSACTIONS (History) ---
$sql_txns = "SELECT * FROM user_transactions WHERE user_id = ? ORDER BY transaction_date DESC LIMIT 20";
$stmt_txns = $conn->prepare($sql_txns);
$stmt_txns->bind_param("i", $user_id);
$stmt_txns->execute();
$transactions = $stmt_txns->get_result();

// --- 4. FETCH WITHDRAWAL REQUESTS ---
$sql_reqs = "SELECT * FROM withdrawal_requests WHERE user_id = ? ORDER BY request_date DESC LIMIT 10";
$stmt_reqs = $conn->prepare($sql_reqs);
$stmt_reqs->bind_param("i", $user_id);
$stmt_reqs->execute();
$withdrawals = $stmt_reqs->get_result();

$pageTitle = "Хэтэвч & Татах";
include 'header.php';
?>

<!-- Styles -->
<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .tab-btn.active { border-bottom: 2px solid #2563eb; color: #2563eb; font-weight: 600; }
    .tab-btn { color: #6b7280; font-weight: 500; transition: all 0.2s; }
    .tab-btn:hover { color: #1f2937; }
</style>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
        
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Хэтэвч & Татах</h1>
        </div>

        <?php if($msg): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative">
                <i class="fas fa-check-circle mr-2"></i> <span class="block sm:inline"><?php echo $msg; ?></span>
            </div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative">
                <i class="fas fa-exclamation-circle mr-2"></i> <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <!-- Balance Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Available Balance -->
            <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-blue-100 text-sm font-medium mb-1">Боломжит үлдэгдэл</p>
                    <h2 class="text-3xl font-bold mb-4"><?php echo number_format($balance); ?>₮</h2>
                    <button onclick="document.getElementById('withdraw-form').scrollIntoView({behavior: 'smooth'})" class="bg-white text-blue-700 px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-blue-50 transition">
                        <i class="fas fa-arrow-down mr-1"></i> Мөнгө татах
                    </button>
                </div>
                <div class="absolute right-0 bottom-0 opacity-10 transform translate-y-1/4 translate-x-1/4">
                    <i class="fas fa-wallet text-9xl"></i>
                </div>
            </div>

            <!-- Pending Balance -->
            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium mb-1">Хүлээгдэж буй орлого</p>
                        <h2 class="text-3xl font-bold text-gray-900 mb-2"><?php echo number_format($pending_balance); ?>₮</h2>
                        <p class="text-xs text-gray-400">Гүйлгээ баталгаажсаны дараа таны үндсэн данс руу орно.</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-50 text-yellow-600 rounded-full flex items-center justify-center text-xl">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column: Transactions & Withdraw History -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <button onclick="switchTab('transactions')" id="btn-transactions" class="tab-btn active py-4 px-1 border-b-2 font-medium text-sm">
                            Гүйлгээний түүх
                        </button>
                        <button onclick="switchTab('withdrawals')" id="btn-withdrawals" class="tab-btn py-4 px-1 border-b-2 border-transparent font-medium text-sm">
                            Таталтын түүх
                        </button>
                    </nav>
                </div>

                <!-- 1. Transactions List -->
                <div id="tab-transactions" class="block">
                    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                        <?php if ($transactions->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3">Огноо</th>
                                        <th class="px-6 py-3">Тайлбар</th>
                                        <th class="px-6 py-3">Төрөл</th>
                                        <th class="px-6 py-3 text-right">Дүн</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php while($txn = $transactions->fetch_assoc()): 
                                        $is_positive = in_array($txn['type'], ['deposit', 'sale']);
                                        $amount_class = $is_positive ? 'text-green-600' : 'text-red-600';
                                        $sign = $is_positive ? '+' : '-';
                                        
                                        $type_labels = [
                                            'deposit' => 'Орлого', 'withdrawal' => 'Зарлага', 
                                            'purchase' => 'Худалдан авалт', 'sale' => 'Борлуулалт'
                                        ];
                                    ?>
                                    <tr class="bg-white hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                            <?php echo date('Y-m-d H:i', strtotime($txn['transaction_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 font-medium text-gray-900 max-w-xs truncate" title="<?php echo htmlspecialchars($txn['description']); ?>">
                                            <?php echo htmlspecialchars($txn['description']); ?>
                                            <div class="text-[10px] text-gray-400 mt-0.5"><?php echo $txn['transaction_number']; ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-[10px] uppercase rounded-full <?php echo $is_positive ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'; ?>">
                                                <?php echo $type_labels[$txn['type']] ?? $txn['type']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-bold <?php echo $amount_class; ?>">
                                            <?php echo $sign . number_format($txn['amount']); ?>₮
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <div class="p-8 text-center text-gray-500">Гүйлгээ хийгдээгүй байна.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 2. Withdrawals List -->
                <div id="tab-withdrawals" class="hidden">
                    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                        <?php if ($withdrawals->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-6 py-3">Огноо</th>
                                        <th class="px-6 py-3">Данс</th>
                                        <th class="px-6 py-3">Статус</th>
                                        <th class="px-6 py-3 text-right">Дүн</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php while($req = $withdrawals->fetch_assoc()): 
                                        $status_colors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800'
                                        ];
                                        $status_labels = [
                                            'pending' => 'Хүлээгдэж буй',
                                            'completed' => 'Шилжсэн',
                                            'rejected' => 'Татгалзсан'
                                        ];
                                    ?>
                                    <tr class="bg-white hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                            <?php echo date('Y-m-d', strtotime($req['request_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-gray-900">
                                            <div class="font-medium"><?php echo htmlspecialchars($req['bank_name'] ?? $req['method']); ?></div>
                                            <div class="text-gray-500"><?php echo htmlspecialchars($req['details']); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-[10px] uppercase rounded-full font-bold <?php echo $status_colors[$req['status']] ?? 'bg-gray-100'; ?>">
                                                <?php echo $status_labels[$req['status']] ?? $req['status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900">
                                            <?php echo number_format($req['amount']); ?>₮
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <div class="p-8 text-center text-gray-500">Түүх байхгүй байна.</div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Right Column: Withdraw Form -->
            <div class="lg:col-span-1">
                <div id="withdraw-form" class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm sticky top-24">
                    <h3 class="font-bold text-gray-900 mb-4">Мөнгө татах хүсэлт</h3>
                    
                    <?php if ($balance < 20000): ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center">
                                <i class="fas fa-info-circle text-yellow-500 mr-2"></i>
                                <p class="text-sm text-yellow-700">
                                    Мөнгө татах доод хэмжээ: <strong>20,000₮</strong>. Таны дансны үлдэгдэл хүрэлцэхгүй байна.
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="withdraw">
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Татах дүн</label>
                                <div class="relative">
                                    <input type="number" name="amount" min="20000" max="<?php echo $balance; ?>" required class="w-full border border-gray-300 rounded-lg p-2.5 pl-3 pr-10 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="20000">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-500 text-sm">₮</div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Доод хэмжээ: 20,000₮</p>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Шилжүүлэх хэлбэр</label>
                                <select name="method" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                                    <option value="bank">Банкны данс</option>
                                </select>
                            </div>

                            <!-- Additional Fields for Bank -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Банкны нэр</label>
                                <select name="bank_name" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white" required>
                                    <option value="">Сонгоно уу...</option>
                                    <option value="Хаан банк">Хаан банк</option>
                                    <option value="Төрийн банк">Төрийн банк</option>
                                    <option value="Голомт банк">Голомт банк</option>
                                    <option value="Худалдаа хөгжлийн банк">Худалдаа хөгжлийн банк</option>
                                    <option value="Хас банк">Хас банк</option>
                                    <option value="Ард кредит">Ард кредит</option>
                                    <option value="Тээвэр хөгжлийн банк">Тээвэр хөгжлийн банк</option>
                                    <option value="Богд банк">Богд банк</option>
                                    <option value="Капитрон банк">Капитрон банк</option>
                                    <option value="Чингис хаан банк">Чингис хаан банк</option>
                                    <option value="Үндэсний хөрөнгө оруулалтын банк">Үндэсний хөрөнгө оруулалтын банк</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Дансны дугаар</label>
                                <input type="text" name="details" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="1234567890">
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Данс эзэмшигчийн нэр</label>
                                <input type="text" name="account_name" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Таны бүтэн нэр">
                            </div>

                            <button type="submit" class="w-full bg-purple-600 text-white font-bold py-3 rounded-lg hover:bg-purple-700 transition shadow-lg shadow-purple-500/30">
                                Хүсэлт илгээх
                            </button>
                        </form>
                    <?php endif; ?>

                    <div class="mt-6 bg-blue-50 p-4 rounded-lg border border-blue-100">
                        <h4 class="text-xs font-bold text-blue-800 mb-2 flex items-center"><i class="fas fa-info-circle mr-1"></i> Санамж</h4>
                        <ul class="text-xs text-blue-700 space-y-1 list-disc pl-4">
                            <li>Мөнгө татах хүсэлт ажлын 24 цагийн дотор шийдвэрлэгдэнэ.</li>
                            <li>Баярын өдрүүдэд гүйлгээ саатах магадлалтай.</li>
                            <li>Зөвхөн өөрийн нэр дээрх данс руу татах боломжтой.</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
function switchTab(tabId) {
    document.getElementById('tab-transactions').classList.add('hidden');
    document.getElementById('tab-withdrawals').classList.add('hidden');
    document.getElementById('tab-transactions').classList.remove('block');
    document.getElementById('tab-withdrawals').classList.remove('block');

    document.getElementById('btn-transactions').classList.remove('active', 'border-b-2', 'border-blue-600', 'text-blue-600');
    document.getElementById('btn-withdrawals').classList.remove('active', 'border-b-2', 'border-blue-600', 'text-blue-600');
    document.getElementById('btn-transactions').classList.add('border-transparent', 'text-gray-500');
    document.getElementById('btn-withdrawals').classList.add('border-transparent', 'text-gray-500');

    document.getElementById('tab-' + tabId).classList.remove('hidden');
    document.getElementById('tab-' + tabId).classList.add('block');
    
    const activeBtn = document.getElementById('btn-' + tabId);
    activeBtn.classList.add('active', 'border-b-2', 'border-blue-600', 'text-blue-600');
    activeBtn.classList.remove('border-transparent', 'text-gray-500');
}
</script>

<?php include '../includes/footer.php'; ?>