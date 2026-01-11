<?php
// Filezone Kids - Payment Page
// Path: filezone.mn/kids/payment.php

session_start();

// 1. DB Connections & Configs
require_once __DIR__ . '/../includes/db.php'; // Main DB (Auth & Transactions)
require_once __DIR__ . '/db_kids.php';        // Kids DB (Subscription)
require_once __DIR__ . '/../api/qpay_config.php';
require_once __DIR__ . '/../api/qpay_handler.php';

// Хэрэглэгч нэвтрээгүй бол
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?redirect=kids/payment.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$price = 19900; // Насан туршийн эрхийн үнэ
$product_name = "Kids Premium (Насан туршийн эрх)";

// 2. Check if user is ALREADY Premium
try {
    $stmt_check = $pdo_kids->prepare("
        SELECT id FROM kids_subscriptions 
        WHERE user_id = ? 
        AND status = 'active' 
        AND (end_date IS NULL OR end_date > NOW())
        LIMIT 1
    ");
    $stmt_check->execute([$user_id]);
    if ($stmt_check->fetch()) {
        header("Location: profile.php");
        exit();
    }
} catch (PDOException $e) { /* Ignore */ }

// ===================================================================
//  AJAX HANDLERS (Processing Payment)
// ===================================================================
if (isset($_GET['action'])) {
    // JSON хариу өгөхөөс өмнө ямар нэгэн HTML гарахаас сэргийлэх
    ob_clean();
    header('Content-Type: application/json');

    // --- ACTION 1: QPay Invoice Үүсгэх ---
    if ($_GET['action'] == 'create_invoice') {
        
        $stmt_u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt_u->execute([$user_id]);
        $user_data = $stmt_u->fetch(PDO::FETCH_ASSOC);

        if ($user_data) {
            $item_data = [
                'id' => 'kids_lifetime_' . $user_id,
                'title' => $product_name,
                'price' => $price,
                'code' => 'KIDS_PREMIUM' 
            ];

            $invoice_response = create_qpay_invoice($item_data, $user_data);

            if (isset($invoice_response['invoice_id'])) {
                $_SESSION['pending_kids_purchase'] = [
                    'qpay_invoice_id' => $invoice_response['invoice_id'],
                    'user_id' => $user_id,
                    'price' => $price,
                    'type' => 'lifetime'
                ];
                echo json_encode(['success' => true, 'invoice_data' => $invoice_response]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Нэхэмжлэх үүсгэж чадсангүй. API хариуг шалгана уу.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Хэрэглэгч олдсонгүй.']);
        }
        exit;
    }

    // --- ACTION 2: QPay Төлбөр шалгах ---
    if ($_GET['action'] == 'check_payment') {
        if (!isset($_SESSION['pending_kids_purchase'])) {
            echo json_encode(['success' => false, 'message' => 'Төлбөрийн мэдээлэл олдсонгүй.']);
            exit;
        }

        $pending = $_SESSION['pending_kids_purchase'];
        
        // QPay API-аас шалгах
        $payment_check = check_qpay_payment_status($pending['qpay_invoice_id']);
        
        // Төлбөр төлөгдсөн эсэхийг олон хувилбараар шалгах
        $is_paid = false;

        // Хувилбар 1: Шууд status талбар
        if (isset($payment_check['status']) && strtoupper($payment_check['status']) == 'PAID') {
            $is_paid = true;
        }
        // Хувилбар 2: payment_status талбар
        elseif (isset($payment_check['payment_status']) && strtoupper($payment_check['payment_status']) == 'PAID') {
            $is_paid = true;
        }
        // Хувилбар 3: rows доторх мэдээлэл
        elseif (isset($payment_check['rows']) && is_array($payment_check['rows']) && count($payment_check['rows']) > 0) {
            foreach ($payment_check['rows'] as $row) {
                if (isset($row['payment_status']) && strtoupper($row['payment_status']) == 'PAID') {
                    $is_paid = true;
                    break;
                }
            }
        }

        if ($is_paid) {
            try {
                // Transaction эхлүүлэх
                $pdo->beginTransaction();

                // 1. Гүйлгээ бүртгэх (Үндсэн бааз руу)
                $txn_num = 'KIDS-QPAY-' . date('Ymd') . '-' . rand(1000, 9999);
                
                $stmt_check_dup = $pdo->prepare("SELECT id FROM transactions WHERE transaction_number = ?");
                $stmt_check_dup->execute([$txn_num]);
                
                if ($stmt_check_dup->rowCount() == 0) {
                    // ЗАСВАР: description баганыг хасч, зөвхөн байгаа багануудыг ашиглав
                    $stmt_trx = $pdo->prepare("INSERT INTO transactions (transaction_number, user_id, amount, payment_method, status, type, transaction_date) VALUES (?, ?, ?, 'qpay', 'success', 'service_payment', NOW())");
                    $stmt_trx->execute([$txn_num, $user_id, $price]);
                }

                $pdo->commit();

                // 2. Kids Subscription идэвхжүүлэх
                $stmt_sub = $pdo_kids->prepare("
                    INSERT INTO kids_subscriptions (user_id, plan_type, start_date, end_date, status, price_paid) 
                    VALUES (?, 'lifetime', NOW(), NULL, 'active', ?)
                ");
                $stmt_sub->execute([$user_id, $price]);
                
                unset($_SESSION['pending_kids_purchase']);
                echo json_encode(['success' => true, 'message' => 'Payment successful.']);

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                // Алдааг JS руу буцаах
                echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
             echo json_encode(['success' => false, 'status' => 'pending', 'message' => 'Төлөгдөөгүй байна.']);
        }
        exit;
    }
}

// ===================================================================
//  VIEW LOGIC
// ===================================================================

include 'header.php';
?>

<style>
    .loader {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #33cbcc;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        animation: spin 1s linear infinite;
        display: inline-block;
    }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .fade-in { animation: fadeIn 0.5s ease-in; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    /* Modal styles */
    .modal {
        display: none; 
        position: fixed; 
        z-index: 50; 
        left: 0;
        top: 0;
        width: 100%; 
        height: 100%; 
        overflow: auto; 
        background-color: rgba(0,0,0,0.5); 
        align-items: center;
        justify-content: center;
    }
    .modal-content {
        background-color: #fff;
        margin: auto;
        padding: 20px;
        border: 1px solid #888;
        width: 90%;
        max-width: 500px;
        border-radius: 1rem;
        position: relative;
        animation: slideDown 0.3s ease-out;
    }
    @keyframes slideDown {
        from { transform: translateY(-50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
</style>

<div class="font-kids bg-gray-50 min-h-screen pb-20">
    
    <!-- Header -->
    <div class="bg-[#e0f7fa] py-8 mb-8 border-b border-[#33cbcc]/20">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-3xl font-extrabold text-gray-800 font-title">Төлбөр төлөх</h1>
            <p class="text-gray-600">Kids Premium эрх идэвхжүүлэх</p>
        </div>
    </div>

    <div class="container mx-auto px-4 max-w-4xl">
        
        <!-- Error Message Container -->
        <div id="main-error-container" class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2 hidden">
            <i class="fas fa-exclamation-circle"></i> <span id="main-error-text"></span>
        </div>

        <div class="bg-white rounded-3xl shadow-lg overflow-hidden border border-gray-100 flex flex-col md:flex-row">
            
            <!-- Left: Order Summary -->
            <div class="md:w-1/2 p-8 bg-gray-50 border-b md:border-b-0 md:border-r border-gray-100">
                <h3 class="text-xl font-bold text-gray-800 mb-6 font-title">Таны захиалга</h3>
                
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 bg-yellow-100 rounded-2xl flex items-center justify-center text-3xl shadow-sm text-yellow-500">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 text-lg"><?php echo $product_name; ?></h4>
                        <p class="text-sm text-gray-500">Бүх материалд хязгааргүй хандах эрх</p>
                    </div>
                </div>

                <div class="space-y-3 mb-8 border-t border-b border-gray-200 py-4">
                    <div class="flex justify-between text-gray-600">
                        <span>Үнэ:</span>
                        <span class="font-bold"><?php echo number_format($price); ?>₮</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Хугацаа:</span>
                        <span class="text-[#33cbcc] font-bold">Насан турш</span>
                    </div>
                </div>

                <div class="flex justify-between items-center text-xl font-extrabold text-gray-800">
                    <span>Нийт төлөх:</span>
                    <span class="text-[#33cbcc]"><?php echo number_format($price); ?>₮</span>
                </div>
            </div>

            <!-- Right: Payment Methods -->
            <div class="md:w-1/2 p-8 relative">
                <h3 class="text-xl font-bold text-gray-800 mb-6 font-title">Төлбөрийн хэрэгсэл</h3>

                <!-- QPay Button -->
                <button id="btn-qpay" onclick="initQPay()" class="w-full bg-[#1b3e6e] hover:bg-[#153259] text-white py-4 rounded-xl font-bold shadow-md transition transform hover:-translate-y-0.5 flex items-center justify-center gap-3 mb-4 group">
                    <img src="../assets/images/qpay-logo.png" class="h-6 bg-white rounded px-1" alt="QPay">
                    <span>QPay-ээр төлөх</span>
                </button>

                <!-- Bank Transfer Button (Restored) -->
                <button onclick="openBankModal()" class="w-full bg-white border-2 border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50 py-4 rounded-xl font-bold transition flex items-center justify-center gap-3">
                    <i class="fas fa-university text-gray-400"></i>
                    <span>Дансаар шилжүүлэх</span>
                </button>

                <!-- QPay QR Area -->
                <div id="qpay-area" class="hidden mt-4 p-6 bg-gray-50 rounded-xl border border-gray-200 text-center fade-in relative">
                    <button onclick="cancelQPay()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                    
                    <h4 class="font-bold text-gray-800 mb-2">QPay QR код</h4>
                    
                    <div id="qpay-loader" class="py-8">
                        <div class="loader"></div>
                        <p class="text-xs text-gray-500 mt-2">Нэхэмжлэх үүсгэж байна...</p>
                    </div>

                    <div id="qpay-content" class="hidden">
                        <div id="qr-wrapper" class="bg-white p-2 rounded-lg inline-block shadow-sm mb-4">
                            <img id="qr-image" src="" class="w-48 h-48 object-contain" alt="QR">
                        </div>
                        
                        <!-- Bank Links Grid -->
                        <div id="bank-links" class="grid grid-cols-4 gap-2 mb-4"></div>

                        <div class="text-sm text-blue-600 bg-blue-50 py-2 rounded-lg animate-pulse">
                            <i class="fas fa-sync fa-spin mr-1"></i> Төлбөр хүлээж байна...
                        </div>
                    </div>
                </div>

            </div>
        </div>
        
        <div class="mt-8 text-center text-gray-400 text-xs max-w-lg mx-auto">
            <i class="fas fa-lock mr-1"></i> Таны гүйлгээ нууцлалтай, аюулгүй хийгдэнэ. Төлбөр төлөгдсөний дараа эрх шууд идэвхжинэ.
        </div>
    </div>

    <!-- Bank Transfer Modal (Restored) -->
    <div id="bankModal" class="modal flex">
        <div class="modal-content relative">
            <button onclick="closeBankModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
            
            <div class="text-center mb-6">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3 text-blue-600">
                    <i class="fas fa-university text-xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 font-title">Дансаар шилжүүлэх</h3>
                <p class="text-sm text-gray-500">Төлбөрийг доорх данс руу шилжүүлнэ үү</p>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 mb-4 border border-gray-200">
                <div class="flex justify-between items-center mb-3 pb-3 border-b border-gray-200">
                    <span class="text-gray-500 text-sm">Банк:</span>
                    <span class="font-bold text-gray-800">ХААН Банк</span>
                </div>
                <div class="flex justify-between items-center mb-3 pb-3 border-b border-gray-200">
                    <span class="text-gray-500 text-sm">Дансны дугаар:</span>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-gray-800 text-lg">5076767189</span>
                        <button onclick="copyToClipboard('5076767189')" class="text-[#33cbcc] hover:text-teal-600" title="Хуулах">
                            <i class="far fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="flex justify-between items-center mb-3 pb-3 border-b border-gray-200">
                    <span class="text-gray-500 text-sm">Хүлээн авагч:</span>
                    <span class="font-bold text-gray-800">Дэлгэрзаяа</span>
                </div>
                 <div class="flex justify-between items-center mb-3 pb-3 border-b border-gray-200">
                    <span class="text-gray-500 text-sm">IBAN:</span>
                    <span class="font-bold text-gray-800">70000500</span>
                </div>
                <div class="flex justify-between items-start">
                    <span class="text-gray-500 text-sm mt-1">Гүйлгээний утга:</span>
                    <div class="text-right">
                        <div class="font-bold text-red-500 bg-red-50 px-2 py-1 rounded border border-red-100 text-sm">
                            <?php 
                            // Хэрэглэгчийн утасны дугаар байвал санал болгох
                            $user_phone = '';
                            if (isset($pdo)) {
                                $stmt_u = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
                                $stmt_u->execute([$user_id]);
                                $u_data = $stmt_u->fetch();
                                if ($u_data && !empty($u_data['phone'])) $user_phone = $u_data['phone'];
                            }
                            echo $user_phone ? $user_phone : 'Таны утас'; 
                            ?>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">(Баталгаажуулахад хэрэгтэй)</p>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <p class="text-sm text-gray-600 mb-4">
                    Шилжүүлэг хийсний дараа <a href="https://m.me/filezone.mn" target="_blank" class="text-[#33cbcc] font-bold hover:underline">Chatbot</a>-оор эсвэл админтай холбогдож баталгаажуулна уу.
                </p>
                <button onclick="closeBankModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded-lg transition w-full">
                    Хаах
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-2xl p-8 max-w-sm w-full text-center relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-green-400 to-[#33cbcc]"></div>
        <div class="w-20 h-20 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-5 text-4xl shadow-inner"><i class="fas fa-check-circle"></i></div>
        <h3 class="text-2xl font-bold text-gray-900 mb-2">Амжилттай!</h3>
        <p class="text-gray-500 mb-6">Premium эрх идэвхжлээ.</p>
        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden"><div class="bg-green-500 h-2 rounded-full animate-[loading_2s_ease-in-out_forwards]" style="width: 0%"></div></div>
        <style>@keyframes loading { 0% { width: 0%; } 100% { width: 100%; } }</style>
    </div>
</div>

<script>
    let checkInterval;

    function showError(msg) {
        const container = document.getElementById('main-error-container');
        document.getElementById('main-error-text').innerText = msg;
        container.classList.remove('hidden');
    }

    function showSuccess() {
        document.getElementById('success-modal').classList.remove('hidden');
        setTimeout(() => {
            window.location.href = 'profile.php?success=1';
        }, 2000);
    }

    // --- QPAY LOGIC ---
    function initQPay() {
        // UI Updates
        document.getElementById('qpay-area').classList.remove('hidden');
        document.getElementById('btn-qpay').classList.add('opacity-50', 'cursor-not-allowed');
        document.getElementById('btn-qpay').disabled = true;
        
        // Fetch Invoice
        fetch('?action=create_invoice')
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('qpay-loader').classList.add('hidden');
                    document.getElementById('qpay-content').classList.remove('hidden');
                    
                    // Render QR
                    const qrBase64 = data.invoice_data.qr_image || data.invoice_data.qPay_QRimage;
                    document.getElementById('qr-image').src = "data:image/png;base64," + qrBase64;

                    // Render Bank Apps (Optional but good UX)
                    const urls = data.invoice_data.urls || [];
                    const bankContainer = document.getElementById('bank-links');
                    bankContainer.innerHTML = ''; // Clear previous
                    
                    // Limit to first 4-8 banks to save space
                    urls.slice(0, 8).forEach(url => {
                        const link = document.createElement('a');
                        link.href = url.link;
                        link.target = "_blank";
                        link.className = "block bg-white border border-gray-200 rounded p-1 hover:border-[#33cbcc] transition text-center";
                        link.innerHTML = `<img src="${url.logo_url || url.logo}" class="w-8 h-8 mx-auto object-contain rounded">`;
                        bankContainer.appendChild(link);
                    });

                    startChecking(data.invoice_data.invoice_id);
                } else {
                    alert(data.message || "Error creating invoice");
                    cancelQPay();
                }
            })
            .catch(err => {
                console.error(err);
                alert("Network error");
                cancelQPay();
            });
    }

    function startChecking(invoiceId) {
        if(checkInterval) clearInterval(checkInterval);
        checkInterval = setInterval(() => {
            fetch('?action=check_payment')
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        clearInterval(checkInterval);
                        showSuccess();
                    } else if (data.status === 'error') {
                        // Баазын алдаа гарвал зогсоож мэдэгдэнэ
                        clearInterval(checkInterval);
                        showError(data.message);
                        cancelQPay();
                    }
                    // status === 'pending' үед үргэлжлүүлэн шалгана
                })
                .catch(err => {
                    // JSON parsing error or network error
                    console.log("Check error (polling continues):", err);
                });
        }, 3000); // Check every 3 seconds
    }

    function cancelQPay() {
        if(checkInterval) clearInterval(checkInterval);
        document.getElementById('qpay-area').classList.add('hidden');
        document.getElementById('btn-qpay').classList.remove('opacity-50', 'cursor-not-allowed');
        document.getElementById('btn-qpay').disabled = false;
        document.getElementById('qpay-loader').classList.remove('hidden');
        document.getElementById('qpay-content').classList.add('hidden');
    }

    // --- BANK MODAL LOGIC (Restored) ---
    function openBankModal() {
        document.getElementById('bankModal').style.display = 'flex';
    }

    function closeBankModal() {
        document.getElementById('bankModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('bankModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Дансны дугаар хуулагдлаа!');
            }, (err) => {
                console.error('Async: Could not copy text: ', err);
            });
        } else {
            let textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                alert('Дансны дугаар хуулагдлаа!');
            } catch (err) {
                console.error('Fallback: Oops, unable to copy', err);
            }
            document.body.removeChild(textArea);
        }
    }
</script>

<?php include 'footer.php'; ?>