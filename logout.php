<?php
// Session эхлүүлэх (хэрэв эхлээгүй бол)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Google Token Revoke (Хэрэв Google-ээр нэвтэрсэн бол)
// Серверийн тохиргооноос хамаарч google_config.php-г дуудахад алдаа гарч болзошгүй тул try-catch ашиглана
if (isset($_SESSION['access_token']) && file_exists('includes/google_config.php') && file_exists('vendor/autoload.php')) {
    try {
        require_once 'includes/google_config.php';
        if (isset($google_client)) {
            // Google session-ийг Server талаас цуцлах
            $google_client->revokeToken($_SESSION['access_token']);
        }
    } catch (Throwable $e) {
        // Алдаа гарсан ч logout үйлдэл зогсох ёсгүй тул зөвхөн алдааг log руу бичнэ
        error_log("Google Token Revoke Error: " . $e->getMessage());
    }
}

// 1. Session хувьсагчдыг цэвэрлэх (Array-г хоослох)
$_SESSION = array();

// 2. Session cookie-г устгах
// Энэ нь хэрэглэгчийн browser дээрх PHPSESSID-г устгана
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Session-ийг сервер дээр бүр мөсөн устгах
session_destroy();

// 4. Нэвтрэх хуудас руу шилжүүлэх
header("Location: login.php");
exit;
?>