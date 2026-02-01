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
// HELPER FUNCTIONS (Туслах функцууд)
// --------------------------------------------------------------------------

/**
 * Гүйлгээний өвөрмөц дугаар үүсгэх
 * Format: PREFIX-YYYYMMDD-UNIQID (Илүү найдвартай)
 */
function generateTransactionNumber($prefix = 'TRX') {
    $dateStr = date('Ymd');
    // uniqid() ашиглан давхцахгүй ID үүсгэх + санамсаргүй тоо
    $uniqueStr = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    return $prefix . '-' . $dateStr . '-' . $uniqueStr;
}

// --------------------------------------------------------------------------
// ACTION HANDLERS
// --------------------------------------------------------------------------

// 1. UPDATE STATUS (Төлөв өөрчлөх & Хадгалах)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $req_id = intval($_POST['request_id']);
    $new_status = $_POST['status']; // pending, completed, rejected

    try {
        $pdo->beginTransaction();

        // Одоогийн мэдээллийг авах
        $stmt = $pdo->prepare("SELECT user_id, amount, status, transaction_number FROM withdrawal_requests WHERE id = ?");
        $stmt->execute([$req_id]);
        $request = $stmt->fetch();

        if ($request) {
            $old_status = $request['status'];
            $user_id = $request['user_id'];
            $amount = $request['amount'];
            
            // Хэрэв transaction_number хоосон байвал ID-г ашиглая
            $req_num = !empty($request['transaction_number']) ? $request['transaction_number'] : ('REQ-OLD-' . $req_id);

            // Төлөв өөрчлөгдсөн эсэхийг шалгах
            if ($old_status !== $new_status) {
                
                // A. ТАТГАЛЗАХ (Pending/Completed -> Rejected) -> Мөнгө буцаах (Refund)
                // Хэрэглэгчийн хүсэлтийг цуцалж байгаа тул мөнгийг буцааж Balance руу хийнэ.
                if ($new_status === 'rejected' && $old_status !== 'rejected') {
                    $desc = "Буцаалт: Хүсэлт " . $req_num . " цуцлагдсан";
                    $tx_num = generateTransactionNumber('REF'); // REF - Refund
                    
                    // Гүйлгээний түүх (User Transactions)
                    $refundStmt = $pdo->prepare("INSERT INTO user_transactions (user_id, type, amount, description, transaction_date, transaction_number) VALUES (?, 'deposit', ?, ?, NOW(), ?)");
                    $refundStmt->execute([$user_id, $amount, $desc, $tx_num]);

                    // Үлдэгдэл нэмэх
                    $balStmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                    $balStmt->execute([$amount, $user_id]);
                }

                // B. ТАТГАЛЗСАНЫГ БУЦААХ (Rejected -> Pending/Completed) -> Мөнгө дахин татах
                // "Татгалзсан" төлвөөс буцааж байгаа бол хэрэглэгчид мөнгийг нь буцаагаад өгчихсөн байгаа. 
                // Тиймээс дахин Balance-аас нь хасах хэрэгтэй.
                elseif ($old_status === 'rejected' && $new_status !== 'rejected') {
                    
                    // Хэрэглэгчийн үлдэгдэл хүрэлцэхгүй байх эрсдэлийг энд шалгаж болно (Сонголттой)
                    /*
                    $checkBal = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
                    $checkBal->execute([$user_id]);
                    if($checkBal->fetchColumn() < $amount) {
                         throw new Exception("Хэрэглэгчийн үлдэгдэл хүрэлцэхгүй байна.");
                    }
                    */

                    $desc = "Залруулга: Хүсэлт " . $req_num . " сэргээгдсэн";
                    $tx_num = generateTransactionNumber('COR'); // COR - Correction
                    
                    // Гүйлгээний түүх (Хасалт)
                    $deductStmt = $pdo->prepare("INSERT INTO user_transactions (user_id, type, amount, description, transaction_date, transaction_number) VALUES (?, 'withdrawal', ?, ?, NOW(), ?)");
                    $deductStmt->execute([$user_id, $amount, $desc, $tx_num]);

                    // Үлдэгдэл хасах
                    $balStmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                    $balStmt->execute([$amount, $user_id]);
                }

                // 2. Төлөвийг шинэчлэх (UPDATE withdrawal_requests)
                // Хэрэв "Completed" болж байвал processed_date-ийг шинэчилнэ.
                if ($new_status === 'completed') {
                    $updateStmt = $pdo->prepare("UPDATE withdrawal_requests SET status = ?, processed_date = NOW() WHERE id = ?");
                    $updateStmt->execute([$new_status, $req_id]);
                } else {
                    // Бусад төлөв рүү шилжиж байвал processed_date-ийг NULL болгох эсвэл хэвээр үлдээх
                    $updateStmt = $pdo->prepare("UPDATE withdrawal_requests SET status = ? WHERE id = ?");
                    $updateStmt->execute([$new_status, $req_id]);
                }

                $_SESSION['message'] = "Хүсэлт " . $req_num . " амжилттай шинэчлэгдлээ.";
            } else {
                $_SESSION['message'] = "Төлөв өөрчлөгдөөгүй байна (Яг ижил төлөв сонгосон байна).";
            }
        } else {
            $_SESSION['error'] = "Хүсэлт олдсонгүй (ID: $req_id).";
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Алдаа гарлаа: " . $e->getMessage();
    }

    // Redirect to clear POST data
    header("Location: finance.php");
    exit;
}

// --------------------------------------------------------------------------
// DATA FETCHING
// --------------------------------------------------------------------------

// 1. Statistics
$revenue_sql = "SELECT SUM(amount) FROM transactions WHERE status = 'success'";
$total_revenue = $pdo->query($revenue_sql)->fetchColumn();

$pending_w_sql = "SELECT SUM(amount) as total_amount, COUNT(*) as total_count FROM withdrawal_requests WHERE status = 'pending'";
$pending_w = $pdo->query($pending_w_sql)->fetch(PDO::FETCH_ASSOC);

$paid_sql = "SELECT SUM(amount) FROM withdrawal_requests WHERE status = 'completed'";
$total_paid = $pdo->query($paid_sql)->fetchColumn();

// 2. Withdrawals List
$w_sql = "SELECT w.*, u.username, u.avatar_url, u.email 
          FROM withdrawal_requests w 
          LEFT JOIN users u ON w.user_id = u.id 
          ORDER BY w.id DESC";
$withdrawals = $pdo->query($w_sql)->fetchAll();

// 3. Transactions List
$t_sql = "SELECT t.*, u.username 
          FROM user_transactions t 
          LEFT JOIN users u ON t.user_id = u.id 
          ORDER BY t.transaction_date DESC 
          LIMIT 100";
$transactions = $pdo->query($t_sql)->fetchAll();

?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Санхүүгийн удирдлага - Filezone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
    <style>
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow-x: hidden; overflow-y: visible !important; }
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
                    <h1 class="text-xl font-bold text-slate-800">Санхүүгийн удирдлага</h1>
                </div>
                <div class="flex items-center gap-3">
                    <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                        <i class="fas fa-file-export"></i> <span class="hidden sm:inline">Тайлан татах</span>
                    </button>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                
                <!-- Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i><?php echo $_SESSION['message']; ?></span>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><i class="fas fa-exclamation-triangle mr-2"></i><?php echo $_SESSION['error']; ?></span>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Financial Overview Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Нийт орлого</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-1"><?php echo number_format($total_revenue ?? 0); ?>₮</h3>
                            <p class="text-xs text-green-600 mt-1">Системийн нийт орлого</p>
                        </div>
                        <div class="w-12 h-12 bg-green-50 text-green-600 rounded-full flex items-center justify-center text-xl"><i class="fas fa-coins"></i></div>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Хүлээгдэж буй зарлага</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-1"><?php echo number_format($pending_w['total_amount'] ?? 0); ?>₮</h3>
                            <p class="text-xs text-slate-400 mt-1"><?php echo $pending_w['total_count'] ?? 0; ?> хүсэлт</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-50 text-yellow-600 rounded-full flex items-center justify-center text-xl"><i class="fas fa-hourglass-half"></i></div>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Нийт төлсөн</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-1"><?php echo number_format($total_paid ?? 0); ?>₮</h3>
                            <p class="text-xs text-slate-400 mt-1">Амжилттай татсан</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center text-xl"><i class="fas fa-check-double"></i></div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-slate-200 mb-6">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button onclick="switchTab('withdrawals')" id="tab-withdrawals" class="tab-btn active whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-indigo-600 border-indigo-500 transition-colors">
                            Татсан мөнгө (Withdrawals)
                        </button>
                        <button onclick="switchTab('transactions')" id="tab-transactions" class="tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-slate-500 border-transparent hover:text-slate-700 hover:border-slate-300 transition-colors">
                            Гүйлгээний түүх (Transactions)
                        </button>
                    </nav>
                </div>

                <!-- Tab Content: Withdrawals -->
                <div id="content-withdrawals" class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Мөнгө татах хүсэлтүүд</h3>
                            <?php if($pending_w['total_count'] > 0): ?>
                                <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded"><?php echo $pending_w['total_count']; ?> Шинэ</span>
                            <?php endif; ?>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                        <th class="px-6 py-4 font-semibold">Гүйлгээний №</th> 
                                        <th class="px-6 py-4 font-semibold">Хэрэглэгч</th>
                                        <th class="px-6 py-4 font-semibold">Дүн</th>
                                        <th class="px-6 py-4 font-semibold">Дансны мэдээлэл</th>
                                        <th class="px-6 py-4 font-semibold">Төлөв</th>
                                        <th class="px-6 py-4 font-semibold text-right">Үйлдэл</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (count($withdrawals) > 0): ?>
                                        <?php foreach ($withdrawals as $w): ?>
                                        <?php 
                                            $displayId = !empty($w['transaction_number']) ? $w['transaction_number'] : ('REQ-OLD-'.$w['id']);
                                            $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($w['username'] ?? 'User') . '&background=random&color=fff';
                                            if (!empty($w['avatar_url']) && file_exists('../' . $w['avatar_url'])) {
                                                $avatar = '../' . $w['avatar_url'];
                                            }
                                        ?>
                                        <tr id="withdrawal-<?php echo $w['id']; ?>" class="hover:bg-slate-50 transition-colors">
                                            <td class="px-6 py-4">
                                                <span class="font-mono text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-1 rounded border border-indigo-100 select-all">
                                                    <?php echo htmlspecialchars($displayId); ?>
                                                </span>
                                                <div class="text-xs text-slate-400 mt-1" title="Үүсгэсэн огноо">
                                                    <i class="far fa-clock mr-1"></i><?php echo isset($w['created_at']) ? date('Y-m-d H:i', strtotime($w['created_at'])) : (isset($w['request_date']) ? date('Y-m-d H:i', strtotime($w['request_date'])) : '-'); ?>
                                                </div>
                                                <?php if(!empty($w['processed_date'])): ?>
                                                    <div class="text-xs text-green-600 mt-0.5" title="Шийдвэрлэсэн огноо">
                                                        <i class="fas fa-check-double mr-1"></i><?php echo date('Y-m-d H:i', strtotime($w['processed_date'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <img src="<?php echo $avatar; ?>" class="w-8 h-8 rounded-full border border-slate-200">
                                                    <div>
                                                        <span class="text-sm font-medium text-slate-700 block"><?php echo htmlspecialchars($w['username'] ?? 'Unknown'); ?></span>
                                                        <span class="text-xs text-slate-400"><?php echo htmlspecialchars($w['email'] ?? ''); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 font-bold text-slate-800"><?php echo number_format($w['amount']); ?>₮</td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-slate-700 font-medium"><?php echo htmlspecialchars($w['bank_name'] ?? $w['method']); ?></div>
                                                <div class="text-xs text-slate-500 font-mono"><?php echo htmlspecialchars($w['details'] ?? $w['account_name']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 status-cell">
                                                <?php if($w['status'] == 'completed'): ?>
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Шилжүүлсэн
                                                    </span>
                                                <?php elseif($w['status'] == 'pending'): ?>
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 animate-pulse">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Хүлээгдэж буй
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Татгалзсан
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button onclick="openEditModal(<?php echo $w['id']; ?>, '<?php echo $w['status']; ?>', <?php echo $w['amount']; ?>, '<?php echo addslashes($w['username'] ?? 'User'); ?>', '<?php echo $displayId; ?>')" 
                                                        class="bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 px-3 py-1.5 rounded-md text-sm font-medium transition shadow-sm inline-flex items-center gap-2">
                                                    <i class="fas fa-edit text-slate-400"></i> Засах
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">Хүсэлт олдсонгүй.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Transactions -->
                <div id="content-transactions" class="hidden space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Хэрэглэгчийн гүйлгээнүүд (User Transactions)</h3>
                            <input type="text" placeholder="Transaction No хайх..." class="text-xs border border-slate-300 rounded px-2 py-1 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                        <th class="px-6 py-4 font-semibold">Гүйлгээний №</th>
                                        <th class="px-6 py-4 font-semibold">Хэрэглэгч</th>
                                        <th class="px-6 py-4 font-semibold">Төрөл</th>
                                        <th class="px-6 py-4 font-semibold">Тайлбар</th>
                                        <th class="px-6 py-4 font-semibold">Дүн</th>
                                        <th class="px-6 py-4 font-semibold">Огноо</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (count($transactions) > 0): ?>
                                        <?php foreach ($transactions as $t): ?>
                                        <?php 
                                            $displayTxId = !empty($t['transaction_number']) ? $t['transaction_number'] : ('TRX-OLD-'.$t['id']);
                                        ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-6 py-4 text-sm font-mono text-slate-600">
                                                <span class="text-xs font-semibold bg-slate-100 text-slate-600 px-2 py-1 rounded border border-slate-200 select-all">
                                                    <?php echo htmlspecialchars($displayTxId); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-700 font-medium"><?php echo htmlspecialchars($t['username'] ?? 'Deleted User'); ?></td>
                                            <td class="px-6 py-4 text-sm">
                                                <?php if($t['type'] == 'deposit' || $t['type'] == 'sale'): ?>
                                                    <span class="text-green-600 bg-green-50 px-2 py-0.5 rounded text-xs font-bold"><?php echo strtoupper($t['type']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-red-600 bg-red-50 px-2 py-0.5 rounded text-xs font-bold"><?php echo strtoupper($t['type']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-600 truncate max-w-xs" title="<?php echo htmlspecialchars($t['description']); ?>">
                                                <?php echo htmlspecialchars($t['description']); ?>
                                            </td>
                                            <td class="px-6 py-4 font-bold <?php echo ($t['type'] == 'deposit' || $t['type'] == 'sale') ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?php echo ($t['type'] == 'deposit' || $t['type'] == 'sale') ? '+' : '-'; ?><?php echo number_format($t['amount']); ?>₮
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-500"><?php echo date('Y-m-d H:i', strtotime($t['transaction_date'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">Гүйлгээ олдсонгүй.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- EDIT STATUS MODAL -->
    <div id="editModal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        
        <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg z-50 overflow-y-auto">
            
            <div class="modal-close absolute top-0 right-0 cursor-pointer flex flex-col items-center mt-4 mr-4 text-white text-sm z-50">
                <svg class="fill-current text-white" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                    <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                </svg>
            </div>

            <!-- Modal Content -->
            <form method="POST" action="finance.php">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" id="modal_request_id" name="request_id" value="">
                <input type="hidden" name="update_status" value="1"> 
                
                <div class="modal-content py-4 text-left px-6">
                    <div class="flex justify-between items-center pb-3 border-b">
                        <p class="text-xl font-bold text-slate-800">Төлөв өөрчлөх</p>
                        <div class="modal-close cursor-pointer z-50 text-slate-500 hover:text-slate-800">
                            <i class="fas fa-times"></i>
                        </div>
                    </div>

                    <div class="my-5">
                        <p class="mb-4 text-sm text-slate-600">
                            Гүйлгээний дугаар: <span id="modal_request_num" class="font-mono font-bold text-indigo-600 bg-indigo-50 px-1 rounded">#</span><br>
                            Хэрэглэгч: <span id="modal_username" class="font-bold text-slate-700"></span><br>
                            Дүн: <span id="modal_request_amount" class="font-bold"></span>₮
                        </p>
                        
                        <label for="status" class="block text-sm font-medium text-slate-700 mb-2">Төлөв сонгох</label>
                        <select id="modal_status" name="status" class="w-full border border-slate-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="pending">Хүлээгдэж буй (Pending)</option>
                            <option value="completed">Шилжүүлсэн (Completed)</option>
                            <option value="rejected">Татгалзсан (Rejected)</option>
                        </select>
                        
                        <div class="mt-4 p-3 bg-blue-50 text-blue-700 text-xs rounded border border-blue-200">
                            <p class="font-bold mb-1"><i class="fas fa-info-circle"></i> Санамж:</p>
                            <ul class="list-disc list-inside">
                                <li><strong>Татгалзсан</strong> гэж сонговол хэрэглэгчид мөнгө буцаж орно (REFUND).</li>
                                <li><strong>Pending/Completed</strong> гэж сонговол данснаас мөнгө хасагдсан хэвээр үлдэнэ.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2 border-t mt-4 gap-2">
                        <button type="button" class="modal-close px-4 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition text-sm font-medium">Хаах</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium shadow-md">Хадгалах</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function switchTab(tabId) {
            document.getElementById('content-withdrawals').classList.add('hidden');
            document.getElementById('content-transactions').classList.add('hidden');
            document.getElementById('content-' + tabId).classList.remove('hidden');
            
            // Reset styles
            document.getElementById('tab-withdrawals').className = 'tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-slate-500 border-transparent hover:text-slate-700 hover:border-slate-300 transition-colors';
            document.getElementById('tab-transactions').className = 'tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-slate-500 border-transparent hover:text-slate-700 hover:border-slate-300 transition-colors';
            
            // Set active
            document.getElementById('tab-' + tabId).className = 'tab-btn active whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-indigo-600 border-indigo-500 transition-colors';
        }

        // Modal Logic
        const modal = document.getElementById('editModal');
        const overlay = document.querySelector('.modal-overlay');
        const closeBtns = document.querySelectorAll('.modal-close');

        function openEditModal(id, currentStatus, amount, username, txNum) {
            document.getElementById('modal_request_id').value = id;
            document.getElementById('modal_request_num').innerText = txNum;
            document.getElementById('modal_username').innerText = username;
            document.getElementById('modal_request_amount').innerText = new Intl.NumberFormat().format(amount);
            document.getElementById('modal_status').value = currentStatus;
            
            toggleModal();
        }

        function toggleModal() {
            modal.classList.toggle('opacity-0');
            modal.classList.toggle('pointer-events-none');
            document.body.classList.toggle('modal-active');
        }

        overlay.addEventListener('click', toggleModal);
        closeBtns.forEach(btn => btn.addEventListener('click', toggleModal));

        document.onkeydown = function(evt) {
            evt = evt || window.event;
            var isEscape = false;
            if ("key" in evt) {
                isEscape = (evt.key === "Escape" || evt.key === "Esc");
            } else {
                isEscape = (evt.keyCode === 27);
            }
            if (isEscape && document.body.classList.contains('modal-active')) {
                toggleModal();
            }
        };
    </script>
</body>
</html>