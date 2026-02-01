<?php 
session_start();
require_once 'includes/db.php';
require_once 'api/qpay_config.php';
require_once 'api/qpay_handler.php';
require_once 'includes/notifications.php'; 

if (!isset($_SESSION['user_id'])) {
    $return_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: login.php?redirect=$return_url");
    exit;
}

$user_id = $_SESSION['user_id'];
$COMMISSION_RATE = 0.10; // 10% Шимтгэл

// ===================================================================
//  AJAX HANDLERS (File Purchase Only)
// ===================================================================
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    // --- ACTION 1: QPay Invoice Үүсгэх ---
    if ($_GET['action'] == 'create_invoice' && isset($_POST['file_id'])) {
        $file_id_ajax = intval($_POST['file_id']);
        
        // Файлын мэдээлэл
        $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ?");
        $stmt->execute([$file_id_ajax]);
        $file_data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Хэрэглэгчийн мэдээлэл
        $stmt_u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt_u->execute([$user_id]);
        $user_data = $stmt_u->fetch(PDO::FETCH_ASSOC);

        if ($file_data && $user_data) {
            $invoice_response = create_qpay_invoice($file_data, $user_data);

            if (isset($invoice_response['invoice_id'])) {
                $_SESSION['pending_file_purchase'] = [
                    'qpay_invoice_id' => $invoice_response['invoice_id'],
                    'file_id'         => $file_id_ajax,
                    'user_id'         => $user_id,
                    'price'           => $file_data['price'],
                    'owner_id'        => $file_data['user_id']
                ];
                echo json_encode(['success' => true, 'invoice_data' => $invoice_response]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Нэхэмжлэх үүсгэж чадсангүй: ' . ($invoice_response['error'] ?? 'Unknown error')]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Файл эсвэл хэрэглэгч олдсонгүй.']);
        }
        exit;
    }

    // --- ACTION 2: QPay Төлбөр шалгах ---
    if ($_GET['action'] == 'check_payment') {
        if (!isset($_SESSION['pending_file_purchase'])) {
            echo json_encode(['success' => false, 'message' => 'Төлбөрийн мэдээлэл олдсонгүй.']);
            exit;
        }

        $pending = $_SESSION['pending_file_purchase'];
        $payment_check = check_qpay_payment_status($pending['qpay_invoice_id']);

        if ($payment_check['status'] == 'PAID') {
            try {
                $pdo->beginTransaction();

                // Давхар гүйлгээ шалгах (Transaction аль хэдийн бүртгэгдсэн эсэх)
                $stmt_check = $pdo->prepare("SELECT id FROM transactions WHERE user_id = ? AND file_id = ? AND type = 'file_purchase' AND status = 'success'");
                $stmt_check->execute([$pending['user_id'], $pending['file_id']]);
                
                if ($stmt_check->rowCount() > 0) {
                    $pdo->commit();
                    processFilePurchase($pdo, $pending['file_id'], $pending['user_id']);
                    unset($_SESSION['pending_file_purchase']);
                    echo json_encode(['success' => true, 'message' => 'Already processed.']);
                    exit;
                }

                // 1. Transaction (System Log) бүртгэх
                // Энэ бол системийн ерөнхий журнал
                $txn_num = 'QPAY-' . date('Ymd') . '-' . rand(1000, 9999);
                $stmt_trx = $pdo->prepare("INSERT INTO transactions (transaction_number, user_id, file_id, amount, payment_method, status, type, transaction_date) VALUES (?, ?, ?, ?, 'qpay', 'success', 'file_purchase', NOW())");
                $stmt_trx->execute([$txn_num, $pending['user_id'], $pending['file_id'], $pending['price']]);

                // 2. Орлого хуваарилалт (Commission Calculation)
                $price = $pending['price'];
                $net_income = $price * (1 - $COMMISSION_RATE); // 90%
                
                // Seller Balance Update (users.balance руу ШУУД орно - Direct Deposit)
                // QPay-ээр төлсөн тул Худалдан авагчийн балансаас хасахгүй, зөвхөн Худалдагчид нэмнэ.
                $stmt_add = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt_add->execute([$net_income, $pending['owner_id']]);

                // 3. Таталтын тоог нэмэх
                $stmt_dl = $pdo->prepare("UPDATE files SET download_count = download_count + 1 WHERE id = ?");
                $stmt_dl->execute([$pending['file_id']]);

                // 4. USER TRANSACTIONS (Давхар бичилт - Double Entry)
                
                // Файлын нэрийг авах
                $stmt_fname = $pdo->prepare("SELECT title FROM files WHERE id = ?");
                $stmt_fname->execute([$pending['file_id']]);
                $file_title = $stmt_fname->fetchColumn();

                // A. Buyer Record (Худалдан авалтын түүх)
                // QPay-ээр төлсөн ч гэсэн хэрэглэгчийн түүхэнд "Худалдан авалт" гэж харагдах ёстой.
                $ut_txn_buy = 'PUR-' . date('Ymd') . '-' . rand(1000, 9999);
                $desc_buy = "Файл худалдан авалт (QPay): " . $file_title;
                $stmt_ut_buy = $pdo->prepare("INSERT INTO user_transactions (transaction_number, user_id, type, amount, description, transaction_date, file_id) VALUES (?, ?, 'purchase', ?, ?, NOW(), ?)");
                $stmt_ut_buy->execute([$ut_txn_buy, $pending['user_id'], $price, $desc_buy, $pending['file_id']]);

                // B. Seller Record (Борлуулалтын түүх)
                // Худалдагчийн түүхэнд "Орлого" гэж харагдана (шимтгэл хассан дүнгээр).
                $ut_txn_sell = 'SALE-' . date('Ymd') . '-' . rand(1000, 9999);
                $desc_sell = "Файл борлуулалт: " . $file_title;
                $stmt_ut_sell = $pdo->prepare("INSERT INTO user_transactions (transaction_number, user_id, type, amount, description, transaction_date, file_id) VALUES (?, ?, 'sale', ?, ?, NOW(), ?)");
                $stmt_ut_sell->execute([$ut_txn_sell, $pending['owner_id'], $net_income, $desc_sell, $pending['file_id']]);

                $pdo->commit();
                
                // Мэдэгдэл илгээх
                processFilePurchase($pdo, $pending['file_id'], $pending['user_id']);
                // Худалдагчид мэдэгдэх
                if ($pending['owner_id'] != $pending['user_id']) {
                    sendNotification($pdo, $pending['owner_id'], 'sale', "Таны \"{$file_title}\" файлыг хэрэглэгч худалдаж авлаа. +".number_format($net_income)."₮", "profile/my_files.php");
                }
                
                unset($_SESSION['pending_file_purchase']);
                echo json_encode(['success' => true, 'message' => 'Payment successful.']);

            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        } elseif ($payment_check['status'] == 'ERROR') {
             echo json_encode(['success' => false, 'message' => 'Payment check error.']);
        } else {
             echo json_encode(['success' => false, 'message' => 'Төлөгдөөгүй байна.']);
        }
        exit;
    }

    // --- ACTION 3: Balance Payment (Хэтэвчээр төлөх) ---
    if ($_GET['action'] == 'pay_balance' && isset($_POST['file_id'])) {
        $file_id_ajax = intval($_POST['file_id']);
        
        // User Balance Check
        $stmt_bal = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt_bal->execute([$user_id]);
        $current_balance = $stmt_bal->fetchColumn();

        // File Info
        $stmt_file = $pdo->prepare("SELECT * FROM files WHERE id = ?");
        $stmt_file->execute([$file_id_ajax]);
        $file_data = $stmt_file->fetch(PDO::FETCH_ASSOC);

        if (!$file_data) {
            echo json_encode(['success' => false, 'message' => 'Файл олдсонгүй.']);
            exit;
        }

        $amount = $file_data['price'];
        $owner_id = $file_data['user_id'];

        if ($current_balance >= $amount) {
            try {
                $pdo->beginTransaction();

                // 1. Check duplicate
                $stmt_check = $pdo->prepare("SELECT id FROM transactions WHERE user_id = ? AND file_id = ? AND status = 'success'");
                $stmt_check->execute([$user_id, $file_id_ajax]);
                if ($stmt_check->rowCount() > 0) {
                    $pdo->commit();
                    processFilePurchase($pdo, $file_id_ajax, $user_id);
                    echo json_encode(['success' => true, 'message' => 'Already purchased.']);
                    exit;
                }

                // 2. Commission Calculation
                $net_income = $amount * (1 - $COMMISSION_RATE);

                // 3. Money Transfer (Balance -> Balance directly)
                // A. Buyer: Reduce Balance (Зарлага)
                $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")->execute([$amount, $user_id]);
                // B. Seller: Increase Balance (Орлого - Direct Deposit)
                $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$net_income, $owner_id]);

                // 4. Transaction (System Log)
                $txn_num = 'BAL-' . date('Ymd') . '-' . rand(1000, 9999);
                $stmt_trx = $pdo->prepare("INSERT INTO transactions (transaction_number, user_id, file_id, amount, payment_method, status, type, transaction_date) VALUES (?, ?, ?, ?, 'balance', 'success', 'file_purchase', NOW())");
                $stmt_trx->execute([$txn_num, $user_id, $file_id_ajax, $amount]);
                
                // 5. Update Download Count
                $pdo->prepare("UPDATE files SET download_count = download_count + 1 WHERE id = ?")->execute([$file_id_ajax]);
                
                // 6. USER TRANSACTIONS (Давхар бичилт - Double Entry)
                
                // A. Buyer Record (Зарлага)
                $ut_txn_buy = 'PUR-' . date('Ymd') . '-' . rand(1000, 9999);
                $desc_buy = "Файл худалдан авалт: " . $file_data['title'];
                $stmt_ut_buy = $pdo->prepare("INSERT INTO user_transactions (transaction_number, user_id, type, amount, description, transaction_date, file_id) VALUES (?, ?, 'purchase', ?, ?, NOW(), ?)");
                $stmt_ut_buy->execute([$ut_txn_buy, $user_id, $amount, $desc_buy, $file_id_ajax]);

                // B. Seller Record (Орлого)
                $ut_txn_sell = 'SALE-' . date('Ymd') . '-' . rand(1000, 9999);
                $desc_sell = "Файл борлуулалт: " . $file_data['title'];
                $stmt_ut_sell = $pdo->prepare("INSERT INTO user_transactions (transaction_number, user_id, type, amount, description, transaction_date, file_id) VALUES (?, ?, 'sale', ?, ?, NOW(), ?)");
                $stmt_ut_sell->execute([$ut_txn_sell, $owner_id, $net_income, $desc_sell, $file_id_ajax]);

                $pdo->commit();
                
                // Мэдэгдэл
                processFilePurchase($pdo, $file_id_ajax, $user_id);
                if ($owner_id != $user_id) {
                    sendNotification($pdo, $owner_id, 'sale', "Таны \"{$file_data['title']}\" файлыг хэрэглэгч худалдаж авлаа. +".number_format($net_income)."₮", "profile/my_files.php");
                }
                
                echo json_encode(['success' => true]);

            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Гүйлгээ хийхэд алдаа гарлаа: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Таны хэтэвчин дэх үлдэгдэл хүрэлцэхгүй байна.']);
        }
        exit;
    }
}

// ===================================================================
//  VIEW LOGIC (GET Request)
// ===================================================================

$id = isset($_GET['id']) ? intval($_GET['id']) : 0; // Only expect ID

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

// Зөвхөн FILES хүснэгтээс хайна
$stmt = $pdo->prepare("SELECT title, price, user_id FROM files WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Файл олдсонгүй.");
}

// Own Item Check
if ($item['user_id'] == $user_id) {
    header("Location: file-details.php?id=$id");
    exit;
}

// User Balance
$stmt_bal = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
$stmt_bal->execute([$user_id]);
$user_balance = $stmt_bal->fetchColumn();

$page_title = "Төлбөр төлөх - Filezone.mn";
include 'includes/header.php';
?>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <!-- Sidebar -->
    <aside class="hidden lg:block w-64 flex-shrink-0 py-6 pr-6 h-[calc(100vh-64px)] sticky top-16 overflow-y-auto no-scrollbar">
        <div class="space-y-1 mb-6">
            <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Үндсэн</h3>
            <a href="index.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-home w-5 text-center"></i> Нүүр хуудас
            </a>
            <a href="browse-files.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-folder-open w-5 text-center"></i> Файлууд
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0 relative">
        <div class="max-w-5xl mx-auto"> 
            
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Төлбөр төлөх</h1>
                <p class="text-sm text-gray-500">Та доорх аргуудаас сонгон төлбөрөө хийнэ үү.</p>
            </div>

            <div id="main-error-container" class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2 hidden">
                <i class="fas fa-exclamation-circle"></i> <span id="main-error-text"></span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Payment Methods -->
                <div class="lg:col-span-8 order-2 lg:order-1">
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                        <h3 class="font-bold text-gray-900 mb-6 border-b pb-4">Төлбөрийн хэрэгсэл сонгох</h3>

                        <form id="balanceForm">
                            <input type="hidden" name="file_id" value="<?php echo $id; ?>">
                            
                            <!-- QPay -->
                            <div class="payment-option border rounded-xl p-4 mb-4 cursor-pointer hover:border-brand-500 hover:bg-brand-50 transition relative group" onclick="selectPayment('qpay', this)">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center p-1 overflow-hidden relative">
                                        <img src="assets/images/qpay-logo.png" alt="QPay" class="w-full h-full object-contain">
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-gray-900 text-sm">QPay (Банкны апп)</h4>
                                        <p class="text-xs text-gray-500">Хаан, Төрийн банк, Голомт, МБанк...</p>
                                    </div>
                                    <div class="radio-indicator w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center bg-white">
                                        <div class="w-2.5 h-2.5 rounded-full bg-brand-600 opacity-0 transform scale-0 transition"></div>
                                    </div>
                                </div>
                                <div id="qpay-container" class="hidden mt-4 pt-4 border-t border-gray-200 text-center bg-gray-50 rounded-b-lg -mx-4 -mb-4 px-4 pb-4">
                                    <div id="qpay-loading" class="py-4 hidden"><i class="fas fa-spinner fa-spin text-brand-600 text-3xl"></i><p class="text-xs text-gray-500 mt-2 font-medium">Нэхэмжлэх үүсгэж байна...</p></div>
                                    <div id="qpay-qr-display" class="hidden">
                                        <p class="text-sm font-bold text-gray-700 mb-2">QR кодыг уншуулах эсвэл доорх апп-уудаас сонгоно уу</p>
                                        <div id="qr-image-wrapper" class="mx-auto bg-white p-2 rounded shadow-sm inline-block mb-3 border border-gray-200"></div>
                                        <div id="bank-links" class="grid grid-cols-4 sm:grid-cols-5 gap-2 mb-4 max-w-md mx-auto"></div>
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="flex items-center gap-2 text-xs text-blue-600 bg-blue-50 px-3 py-1.5 rounded-full animate-pulse"><i class="fas fa-sync fa-spin"></i> Төлбөр хүлээгдэж байна...</div>
                                            <button type="button" id="checkPaymentBtn" class="bg-brand-600 text-white px-6 py-2 rounded-lg text-sm font-bold hover:bg-brand-700 transition shadow-sm" onclick="manualCheckPayment()">Төлбөр шалгах</button>
                                        </div>
                                    </div>
                                    <div id="qpay-error" class="hidden text-red-500 text-sm py-2"></div>
                                </div>
                            </div>

                            <!-- Balance -->
                            <div class="payment-option border rounded-xl p-4 mb-6 cursor-pointer hover:border-brand-500 hover:bg-brand-50 transition relative group" onclick="selectPayment('balance', this)">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl border border-indigo-100">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-gray-900 text-sm">Хэтэвч (Balance)</h4>
                                        <p class="text-xs text-gray-500">Үлдэгдэл: <span class="font-bold text-green-600"><?php echo number_format($user_balance); ?>₮</span></p>
                                    </div>
                                    <div class="radio-indicator w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center bg-white">
                                        <div class="w-2.5 h-2.5 rounded-full bg-brand-600 opacity-0 transform scale-0 transition"></div>
                                    </div>
                                </div>
                                <?php if($user_balance < $item['price']): ?>
                                    <div class="mt-2 pl-16 text-xs flex items-center gap-2">
                                        <span class="text-red-500"><i class="fas fa-exclamation-circle"></i> Үлдэгдэл хүрэлцэхгүй.</span>
                                        <a href="wallet.php" class="text-brand-600 font-bold hover:underline">Цэнэглэх &rarr;</a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <button type="submit" id="payBtn" class="w-full bg-gray-100 text-gray-400 font-bold py-3.5 rounded-xl transition cursor-not-allowed border border-gray-200 flex items-center justify-center gap-2" disabled>
                                <span>Төлбөр төлөх</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-4 order-1 lg:order-2">
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm sticky top-24">
                        <h3 class="font-bold text-gray-900 mb-4 border-b pb-4">Захиалгын мэдээлэл</h3>
                        <div class="flex gap-3 mb-4">
                            <div class="w-16 h-16 bg-gray-100 rounded-lg flex-shrink-0 flex items-center justify-center text-2xl text-gray-400">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-400 uppercase tracking-wide font-bold">Файл</span>
                                <p class="text-sm font-medium text-gray-800 line-clamp-2 leading-snug"><?php echo htmlspecialchars($item['title']); ?></p>
                            </div>
                        </div>
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500">Үнэ</span>
                                <span class="font-medium text-gray-900"><?php echo number_format($item['price']); ?>₮</span>
                            </div>
                            <div class="flex justify-between items-center pt-3 border-t border-dashed border-gray-300">
                                <span class="font-bold text-gray-900">Нийт төлөх</span>
                                <span class="text-xl font-bold text-brand-600"><?php echo number_format($item['price']); ?>₮</span>
                            </div>
                        </div>
                        <div class="bg-blue-50 p-3 rounded-lg text-xs text-blue-700 flex gap-2 items-start">
                            <i class="fas fa-shield-alt mt-0.5"></i> 
                            <span>Төлбөр амжилттай төлөгдсөний дараа татах холбоос идэвхэжнэ.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Success Modal -->
<div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-2xl p-8 max-w-sm w-full text-center relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-green-400 to-brand-500"></div>
        <div class="w-20 h-20 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-5 text-4xl shadow-inner"><i class="fas fa-check-circle"></i></div>
        <h3 class="text-2xl font-bold text-gray-900 mb-2">Амжилттай!</h3>
        <p class="text-gray-500 mb-6">Төлбөр амжилттай төлөгдлөө. Таныг шилжүүлж байна...</p>
        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden"><div class="bg-green-500 h-2 rounded-full animate-[loading_2s_ease-in-out_forwards]" style="width: 0%"></div></div>
        <style>@keyframes loading { 0% { width: 0%; } 100% { width: 100%; } }</style>
    </div>
</div>

<script>
    let qpayPollInterval = null;
    const fileId = <?php echo $id; ?>;
    const price = <?php echo $item['price']; ?>;
    const balance = <?php echo $user_balance; ?>;

    function showError(msg) {
        const container = document.getElementById('main-error-container');
        document.getElementById('main-error-text').innerText = msg;
        container.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function showSuccessModal() {
        document.getElementById('success-modal').classList.remove('hidden');
        setTimeout(() => {
            window.location.href = "file-details.php?id=" + fileId + "&purchase=success";
        }, 2000);
    }

    document.getElementById('balanceForm').addEventListener('submit', async function(e) {
        e.preventDefault(); 
        const btn = document.getElementById('payBtn');
        if (btn.disabled) return;
        document.getElementById('main-error-container').classList.add('hidden');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Уншиж байна...';

        try {
            const formData = new FormData(this);
            const response = await fetch('?action=pay_balance', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) showSuccessModal();
            else { showError(data.message || 'Алдаа гарлаа.'); btn.disabled = false; btn.innerHTML = '<span>Төлбөр төлөх</span>'; }
        } catch (err) {
            console.error(err);
            showError('Сервертэй холбогдоход алдаа гарлаа.');
            btn.disabled = false;
            btn.innerHTML = '<span>Төлбөр төлөх</span>';
        }
    });

    function selectPayment(method, element) {
        if(qpayPollInterval) clearInterval(qpayPollInterval);
        document.querySelectorAll('.payment-option').forEach(el => {
            el.classList.remove('border-brand-500', 'bg-brand-50', 'ring-1', 'ring-brand-500');
            el.querySelector('.radio-indicator').classList.remove('border-brand-600');
            el.querySelector('.radio-indicator div').classList.add('opacity-0', 'scale-0');
        });
        document.getElementById('qpay-container').classList.add('hidden');
        element.classList.add('border-brand-500', 'bg-brand-50', 'ring-1', 'ring-brand-500');
        element.querySelector('.radio-indicator').classList.add('border-brand-600');
        element.querySelector('.radio-indicator div').classList.remove('opacity-0', 'scale-0');

        const payBtn = document.getElementById('payBtn');
        if (method === 'qpay') {
            payBtn.style.display = 'none'; 
            document.getElementById('qpay-container').classList.remove('hidden');
            createInvoice(); 
        } else if (method === 'balance') {
             payBtn.style.display = 'flex'; 
            if (balance >= price) {
                payBtn.disabled = false;
                payBtn.classList.add('bg-brand-600', 'text-white', 'hover:bg-brand-700', 'btn-shine');
                payBtn.classList.remove('bg-gray-100', 'text-gray-400', 'cursor-not-allowed', 'border', 'border-gray-200');
                payBtn.innerHTML = '<span>Төлбөр төлөх</span>';
            } else {
                payBtn.disabled = true;
                payBtn.classList.add('bg-gray-100', 'text-gray-400', 'cursor-not-allowed', 'border', 'border-gray-200');
                payBtn.classList.remove('bg-brand-600', 'text-white', 'hover:bg-brand-700', 'btn-shine');
                payBtn.innerHTML = '<span>Үлдэгдэл хүрэлцэхгүй</span>';
            }
        }
    }

    async function createInvoice() {
        const loading = document.getElementById('qpay-loading');
        const display = document.getElementById('qpay-qr-display');
        const errorDiv = document.getElementById('qpay-error');
        loading.classList.remove('hidden'); display.classList.add('hidden'); errorDiv.classList.add('hidden');

        try {
            const formData = new FormData();
            formData.append('file_id', fileId);
            const response = await fetch('?action=create_invoice', { method: 'POST', body: formData });
            const data = await response.json();
            loading.classList.add('hidden');

            if (data.success) {
                const qrBase64 = data.invoice_data.qr_image || data.invoice_data.qPay_QRimage;
                if(qrBase64) document.getElementById('qr-image-wrapper').innerHTML = `<img src="data:image/png;base64,${qrBase64}" alt="QPay QR" class="w-40 h-40">`;
                const urls = data.invoice_data.urls || [];
                if(urls.length > 0) {
                    document.getElementById('bank-links').innerHTML = urls.map(url => `
                        <a href="${url.link}" target="_blank" class="block bg-white border border-gray-200 rounded-lg p-1 hover:border-brand-500 hover:shadow-md transition flex flex-col items-center justify-center gap-1 h-14" title="${url.name}">
                            <img src="${url.logo_url || url.logo}" alt="${url.name}" class="w-8 h-8 object-contain rounded">
                            <span class="text-[9px] text-gray-500 truncate w-full text-center">${url.name}</span>
                        </a>`).join('');
                }
                display.classList.remove('hidden');
                startPolling();
            } else {
                errorDiv.textContent = data.message || "Error"; errorDiv.classList.remove('hidden');
            }
        } catch (err) {
            loading.classList.add('hidden'); errorDiv.textContent = "Error"; errorDiv.classList.remove('hidden'); console.error(err);
        }
    }

    async function checkPayment() {
        try {
            const response = await fetch('?action=check_payment');
            const data = await response.json();
            if (data.success) { clearInterval(qpayPollInterval); showSuccessModal(); }
        } catch (err) { console.error("Check payment error", err); }
    }

    function startPolling() {
        if(qpayPollInterval) clearInterval(qpayPollInterval);
        qpayPollInterval = setInterval(checkPayment, 5000);
    }

    function manualCheckPayment() {
        const btn = document.getElementById('checkPaymentBtn');
        const originalText = btn.innerText;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Шалгаж байна...';
        btn.disabled = true;
        checkPayment().then(() => { setTimeout(() => { btn.innerText = originalText; btn.disabled = false; }, 1000); });
    }
</script>

<?php include 'includes/footer.php'; ?>