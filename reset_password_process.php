<?php
// Database connection
require_once 'includes/db.php';
require_once 'api/brevo_email.php'; // Brevo email function дуудах

// Session эхлүүлэх
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Зөвхөн POST хүсэлт хүлээн авна
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reset-password.php');
    exit;
}

$action = $_POST['action'] ?? 'request_reset';

if ($action === 'request_reset') {
    // ---------------------------------------------------------
    // 1. НУУЦ ҮГ СЭРГЭЭХ ХҮСЭЛТ
    // ---------------------------------------------------------
    
    $email = trim($_POST['email']);

    if (empty($email)) {
        $_SESSION['flash_error'] = "Та имэйл хаягаа оруулна уу.";
        header('Location: reset-password.php');
        exit;
    }

    try {
        // Хэрэглэгч байгаа эсэхийг шалгах
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            // Token expire time: Одоогоос 1 цагийн дараа
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // ШИНЭЧЛЭЛТ: `password_resets` хүснэгт ашиглах
            
            // 1. Хуучин token байвал устгах (давхардахаас сэргийлж)
            $delStmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $delStmt->execute([$email]);

            // 2. Шинэ token хадгалах
            $insStmt = $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
            $insStmt->execute([$email, $token]);

            // Сэргээх холбоос
            $resetLink = "https://filezone.mn/reset-password.php?token=" . $token . "&email=" . urlencode($email);

            // Brevo API ашиглан Имэйл илгээх
            // sendPasswordResetEmail функц api/brevo_email.php дотор байгаа
            // Хариуг JSON decode хийж шалгана
            $emailResponseJson = sendPasswordResetEmail($email, $user['username'], $resetLink);
            $emailResponse = json_decode($emailResponseJson, true);

            // Brevo амжилттай бол 'messageId' буцаадаг. Алдаа бол 'code' эсвэл 'message' буцаана.
            if (isset($emailResponse['messageId'])) {
                $_SESSION['flash_success'] = "Нууц үг сэргээх холбоосыг таны имэйл рүү илгээлээ. Имэйлээ шалгана уу (Spam хавтсаа мөн шалгаарай).";
            } else {
                // Алдаа гарсан тохиолдолд
                $errorMsg = isset($emailResponse['message']) ? $emailResponse['message'] : 'Тодорхойгүй алдаа';
                // Туршилтын үед алдааг дэлгэрэнгүй харуулах нь зүйтэй
                $_SESSION['flash_error'] = "Имэйл илгээхэд алдаа гарлаа: " . $errorMsg;
                
                // Хэрэв имэйл явахгүй бол туршилтын журмаар холбоосыг энд харуулж болно (Production дээр хасна)
                $_SESSION['flash_success'] = "Имэйл систем алдаатай байна. Туршилтын холбоос: <a href='$resetLink' class='underline font-bold text-blue-600'>Энд дарж сэргээх</a>";
            }

        } else {
             // Хэрэглэгчийн нууцлалыг хамгаалах үүднээс ижил мессеж өгнө
             $_SESSION['flash_success'] = "Хэрэв таны имэйл бүртгэлтэй бол бид сэргээх холбоосыг илгээлээ.";
        }

    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Алдаа гарлаа: " . $e->getMessage();
    }

    header('Location: reset-password.php');
    exit;

} elseif ($action === 'reset_password') {
    // ---------------------------------------------------------
    // 2. ШИНЭ НУУЦ ҮГ ХАДГАЛАХ
    // ---------------------------------------------------------
    
    $token = $_POST['token'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($token) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['flash_error'] = "Мэдээлэл дутуу байна.";
        header("Location: reset-password.php?token=$token&email=$email");
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['flash_error'] = "Нууц үг хоорондоо таарахгүй байна.";
        header("Location: reset-password.php?token=$token&email=$email");
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['flash_error'] = "Нууц үг дор хаяж 6 тэмдэгттэй байх ёстой.";
        header("Location: reset-password.php?token=$token&email=$email");
        exit;
    }

    try {
        // ШИНЭЧЛЭЛТ: `password_resets` хүснэгтээс token шалгах
        // created_at нь 1 цагийн дотор байх ёстой гэж шалгана
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$email, $token]);
        $resetRecord = $stmt->fetch();

        if ($resetRecord) {
            // Нууц үг шинэчлэх (users table дээр)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $updateStmt->execute([$hashed_password, $email]);

            // Ашигласан token-г устгах
            $delStmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $delStmt->execute([$email]);

            // АМЖИЛТТАЙ СОЛИГДЛОО МЕССЕЖ
            $_SESSION['flash_success'] = "Таны нууц үг амжилттай солигдлоо. Та одоо шинэ нууц үгээрээ нэвтэрнэ үү.";
            
            // Login хуудас руу шилжүүлэх
            header('Location: login.php');
            exit;
        } else {
            $_SESSION['flash_error'] = "Холбоос хүчингүй болсон эсвэл хугацаа нь дууссан байна.";
            header('Location: reset-password.php');
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Алдаа гарлаа: " . $e->getMessage();
        header("Location: reset-password.php?token=$token&email=$email");
        exit;
    }
}
?>