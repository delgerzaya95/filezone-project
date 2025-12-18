<?php
// Database тохиргоо
$db_host = '127.0.0.1';      // Хост (ихэнх тохиолдолд localhost байна)
$db_name = 'filezone_mn';    // Таны database-ийн нэр (SQL файл дээрх нэрээр авлаа)
$db_user = 'filezone_mn';           // Database хэрэглэгчийн нэр (Localhost дээр ихэвчлэн 'root' байдаг)
$db_pass = '099da7e85a2688';               // Database нууц үг (Localhost дээр ихэвчлэн хоосон байдаг)

// Алдааг харуулах эсэх (Production буюу сайт асах үед false болгох хэрэгтэй)
$debug_mode = true;

try {
    // PDO холболт үүсгэх
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    
    // Алдааны горимыг тохируулах (Exception шидэх)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Өгөгдлийг татахдаа дандаа Associative Array хэлбэрээр татах
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // SQL Injection-оос хамгаалахын тулд Emulated Prepared Statements-ийг унтраах
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    // Хэрэв холболт амжилтгүй болвол алдаа заана
    if ($debug_mode) {
        die("Database холболтын алдаа: " . $e->getMessage());
    } else {
        die("Уучлаарай, системд алдаа гарлаа. Та түр хүлээгээд дахин оролдоно уу.");
    }
}

// Session эхлүүлэх (Хэрэв эхлээгүй бол)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Туслах функц: Хэрэглэгч нэвтэрсэн эсэхийг шалгах
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Туслах функц: Админ мөн эсэхийг шалгах
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Туслах функц: Текст цэвэрлэх (XSS хамгаалалт)
 */
function clean($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>