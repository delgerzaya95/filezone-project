<?php
// qpay_config.php файлыг заавал дуудна (API түлхүүрүүд болон DB тохиргоо энд байх ёстой)
require_once 'qpay_config.php';

// 'functions.php'-ийг дуудахгүйгээр дотооддоо функцүүдийг шийдэх хэсэг

/**
 * 1. Мэдээллийн сантай холбогдох функц
 * Хэрэв өөр газар зарлагдаагүй бол энд шинээр үүсгэнэ.
 */
if (!function_exists('db_connect')) {
    function db_connect() {
        // Эдгээр тогтмолууд qpay_config.php дотор эсвэл энд тодорхойлогдсон байх ёстой
        if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
        if (!defined('DB_USER')) define('DB_USER', 'filezone_mn'); // Эсвэл өөрийн DB хэрэглэгч
        if (!defined('DB_PASS')) define('DB_PASS', '099da7e85a2688');     // Эсвэл өөрийн DB нууц үг
        if (!defined('DB_NAME')) define('DB_NAME', 'filezone_mn'); // Өөрийн DB нэр

        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (!$conn) {
            error_log("Database connection failed: " . mysqli_connect_error());
            return false;
        }
        
        mysqli_set_charset($conn, "utf8mb4");
        return $conn;
    }
}

/**
 * 2. Орлого тооцоолох функц (Файл эзэмшигчид өгөх дүн)
 * Хэрэв өөр газар зарлагдаагүй бол энд шинээр үүсгэнэ.
 */
if (!function_exists('calculate_earnings')) {
    function calculate_earnings($amount) {
        // Жишээ: Сайт 10% шимтгэл авдаг гэж үзвэл (0.1)
        // Хэрэв таны шимтгэл өөр бол энэ тоог өөрчлөөрэй.
        $site_fee_percent = 10; 
        
        $fee = ($amount * $site_fee_percent) / 100;
        return $amount - $fee;
    }
}

// ==========================================================
// QPAY ҮНДСЭН ФУНКЦҮҮД (Өөрчлөлт ороогүй, хэвийн үргэлжилнэ)
// ==========================================================

// QPay-с Authentication token авах функц
function get_qpay_token() {
    $url = QPAY_API_URL . 'auth/token';
    $credentials = base64_encode(QPAY_CLIENT_ID . ':' . QPAY_CLIENT_SECRET);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $credentials
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('QPay token error: ' . curl_error($ch));
        return null;
    }
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

// Файл худалдан авах нэхэмжлэх үүсгэх
function create_qpay_invoice($file, $user) {
    $token = get_qpay_token();
    if (!$token) {
        return ['error' => 'Token авч чадсангүй.'];
    }

    $url = QPAY_API_URL . 'invoice';

    $sender_invoice_no = 'filezone-' . $user['id'] . '-' . $file['id'] . '-' . time();
    $amount = $file['price'];
    
    $description = $file['title'] . ' (ID: ' . $sender_invoice_no . ')';

    $post_data = [
        'invoice_code' => QPAY_INVOICE_CODE,
        'sender_invoice_no' => $sender_invoice_no,
        'invoice_receiver_code' => (string)$user['id'], 
        'invoice_description' => $description,
        'amount' => $amount,
        'callback_url' => QPAY_CALLBACK_URL . '?invoice_id=' . $sender_invoice_no . '&user_id=' . $user['id'] . '&file_id=' . $file['id'],
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('QPay invoice error: ' . curl_error($ch));
        return ['error' => 'Нэхэмжлэх үүсгэхэд алдаа гарлаа.'];
    }
    curl_close($ch);

    $result = json_decode($response, true);
    
    if (is_array($result) && !isset($result['error'])) {
        $result['sender_invoice_no'] = $sender_invoice_no;
    }
    
    return $result;
}

// deposit.php-д зориулсан QPay invoice үүсгэх функц
function create_qpay_deposit_invoice($deposit_data, $user) {
    $token = get_qpay_token();
    if (!$token) {
        return ['error' => 'Token авч чадсангүй.'];
    }

    $url = QPAY_API_URL . 'invoice';

    $sender_invoice_no = 'FZ' . date('Ymd') . $user['id'] . time();
    $amount = $deposit_data['price'];
    
    $description = 'Данс цэнэглэлт. User: ' . $user['username'] . '. Invoice: ' . $sender_invoice_no;
    
    $note = implode(' | ', [
        'Хэрэглэгч: ' . $user['username'] . ' (ID: ' . $user['id'] . ')',
        'Цэнэглэх дүн: ' . number_format($amount) . ' MNT',
        'Огноо: ' . date('Y-m-d H:i:s'),
        'Үйлчилгээ: FileZone.mn',
        'Тайлбар: Веб сайтын баланс цэнэглэлт',
        'InvoiceID: ' . $sender_invoice_no
    ]);

    $post_data = [
        'invoice_code' => QPAY_INVOICE_CODE,
        'sender_invoice_no' => $sender_invoice_no,
        'invoice_receiver_code' => 'FZ_CUST_' . $user['id'],
        'invoice_description' => $description,
        'amount' => $amount,
        'callback_url' => QPAY_CALLBACK_URL . '?type=deposit&invoice_id=' . $sender_invoice_no . '&user_id=' . $user['id'] . '&amount=' . $amount,
        'note' => $note,
        'sender_branch_code' => 'FILEZONE_WEB',
        'sender_register_no' => 'FZ_USER_' . $user['id']
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        error_log('cURL Error: ' . curl_error($ch));
        return ['error' => 'Холболтын алдаа: ' . curl_error($ch)];
    }
    
    curl_close($ch);
    $result = json_decode($response, true);
    
    if (isset($result['error_code'])) {
        return ['error' => 'QPay алдаа (' . $result['error_code'] . '): ' . ($result['error_message'] ?? 'Тодорхойгүй алдаа')];
    }
    
    if (!isset($result['invoice_id'])) {
        return ['error' => 'Нэхэмжлэх ID авч чадсангүй.'];
    }

    $result['sender_invoice_no'] = $sender_invoice_no;
    
    return $result;
}

// QPay-н нэхэмжлэхийг ШУУД ID-Г НЬ АШИГЛАН шалгах
function check_qpay_payment_status($qpay_invoice_id) {
    $token = get_qpay_token();
    if (!$token) {
        return ['status' => 'ERROR', 'message' => 'Token авч чадсангүй.'];
    }

    $url = QPAY_API_URL . 'invoice/' . $qpay_invoice_id;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPGET, 1); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        curl_close($ch);
        return ['status' => 'ERROR', 'message' => 'cURL Error'];
    }
    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['payments']) && is_array($data['payments'])) {
        foreach ($data['payments'] as $payment) {
            if (isset($payment['payment_status']) && strtoupper($payment['payment_status']) == 'PAID') {
                return ['status' => 'PAID', 'message' => 'Төлөгдсөн.'];
            }
        }
    }

    if (isset($data['invoice_status']) && strtoupper($data['invoice_status']) == 'PAID') {
        return ['status' => 'PAID', 'message' => 'Төлөгдсөн.'];
    }
    
    $current_status = $data['invoice_status'] ?? 'UNKNOWN';
    return ['status' => 'PENDING', 'message' => 'Хүлээгдэж байна: ' . $current_status];
}

// QPay-н нэхэмжлэхийг SENDER_INVOICE_NO-г ашиглан шалгах
function check_qpay_payment_by_sender_no($sender_invoice_no) {
    $token = get_qpay_token();
    if (!$token) {
        return false;
    }

    $url = QPAY_API_URL . 'payment/check';
    
    $post_data = [
        'object_type' => 'INVOICE',
        'object_id' => $sender_invoice_no,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        curl_close($ch);
        return false;
    }
    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['rows']) && is_array($data['rows'])) {
        foreach ($data['rows'] as $payment) {
            if (isset($payment['payment_status']) && strtoupper($payment['payment_status']) == 'PAID') {
                return true;
            }
        }
    }
    
    return false;
}

// Амжилттай цэнэглэлтийг боловсруулах
function process_successful_deposit($user_id, $amount, $invoice_id) {
    $conn = db_connect(); // Шинэ local db_connect функцийг ашиглана
    if (!$conn) {
        error_log("process_successful_deposit: Database connection failed.");
        return false;
    }

    $user_id = (int)$user_id;
    $amount = (float)$amount;
    $invoice_id_safe = mysqli_real_escape_string($conn, $invoice_id);

    if ($amount <= 0) return false;

    $check_sql = "SELECT id FROM user_transactions WHERE description LIKE '%" . $invoice_id_safe . "%'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        return true; 
    }

    mysqli_begin_transaction($conn);
    try {
        $update_sql = "UPDATE users SET balance = balance + ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt_update, "di", $amount, $user_id);
        mysqli_stmt_execute($stmt_update);

        $description = "Данс цэнэглэлт (Qpay). Invoice: " . $invoice_id_safe;
        $insert_sql = "INSERT INTO user_transactions (user_id, type, amount, description) VALUES (?, 'deposit', ?, ?)";
        $stmt_insert = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt_insert, "ids", $user_id, $amount, $description);
        mysqli_stmt_execute($stmt_insert);

        mysqli_commit($conn);
        return true; 

    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("QPay Deposit DB Error: " . $e->getMessage());
        return false; 
    }
}

// ==========================================================
// CALLBACK HANDLER LOGIC
// ==========================================================

if (isset($_GET['invoice_id']) && isset($_GET['user_id'])) {
    
    $invoice_id_from_qpay = $_GET['invoice_id'];
    
    // Гүйлгээг шалгах
    $is_paid = check_qpay_payment_by_sender_no($invoice_id_from_qpay);
    
    if ($is_paid) {
        $conn = db_connect(); // Local db_connect
        if (!$conn) {
            error_log('Callback Error: Database connection failed.');
            http_response_code(500);
            exit;
        }

        $user_id = (int)$_GET['user_id'];
        $invoice_id_safe = mysqli_real_escape_string($conn, $invoice_id_from_qpay);

        if (isset($_GET['type']) && $_GET['type'] == 'deposit' && isset($_GET['amount'])) {
            // ===== ДАНС ЦЭНЭГЛЭЛТ =====
            $amount = (float)$_GET['amount'];
            process_successful_deposit($user_id, $amount, $invoice_id_from_qpay);

        } elseif (isset($_GET['file_id'])) {
            // ===== ФАЙЛ ХУДАЛДАН АВАЛТ =====
            $file_id = (int)$_GET['file_id'];
            
            $file_sql = "SELECT price, user_id FROM files WHERE id = ?";
            $stmt = mysqli_prepare($conn, $file_sql);
            mysqli_stmt_bind_param($stmt, "i", $file_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $file = mysqli_fetch_assoc($result);
            
            $amount = $file ? (float)$file['price'] : 0;
            $owner_id = $file ? (int)$file['user_id'] : 0;

            $check_sql = "SELECT id FROM transactions WHERE user_id = ? AND file_id = ? AND status = 'success' AND payment_method = 'qpay'";
            $stmt_check = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($stmt_check, "ii", $user_id, $file_id);
            mysqli_stmt_execute($stmt_check);
            $check_result = mysqli_stmt_get_result($stmt_check);

            if (mysqli_num_rows($check_result) == 0 && $amount > 0) {
                mysqli_begin_transaction($conn);
                try {
                    // Түүх бичих
                    $insert_sql = "INSERT INTO transactions (user_id, file_id, amount, payment_method, status) VALUES (?, ?, ?, 'qpay', 'success')";
                    $stmt_insert = mysqli_prepare($conn, $insert_sql);
                    mysqli_stmt_bind_param($stmt_insert, "iid", $user_id, $file_id, $amount);
                    mysqli_stmt_execute($stmt_insert);
                    
                    // Таталт нэмэх
                    $update_sql = "UPDATE files SET download_count = download_count + 1 WHERE id = ?";
                    $stmt_update = mysqli_prepare($conn, $update_sql);
                    mysqli_stmt_bind_param($stmt_update, "i", $file_id);
                    mysqli_stmt_execute($stmt_update);

                    // Файл эзэмшигчид мөнгө олгох (Calculate Earnings функцийг энд ашиглана)
                    if ($owner_id > 0) {
                        // functions.php байхгүй тул дээр тодорхойлсон local функцийг ашиглана
                        $earning = calculate_earnings($amount); 
                        
                        $owner_sql = "UPDATE users SET balance = balance + ? WHERE id = ?";
                        $stmt_owner = mysqli_prepare($conn, $owner_sql);
                        mysqli_stmt_bind_param($stmt_owner, "di", $earning, $owner_id);
                        mysqli_stmt_execute($stmt_owner);
                    }
                    
                    mysqli_commit($conn);
                    error_log("QPay Purchase Success: User $user_id, File $file_id, Invoice $invoice_id_safe");
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    error_log("QPay Purchase DB Error: " . $e->getMessage());
                }
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Callback processed.']);
        exit;
        
    } else {
        error_log("QPay Callback Error: Payment not verified for invoice $invoice_id_from_qpay");
        http_response_code(400); 
        echo json_encode(['success' => false, 'message' => 'Payment not verified.']);
        exit;
    }
}
?>