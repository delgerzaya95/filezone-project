<?php
session_start();

// 1. DATABASE & LIB
require_once 'includes/db.php';

// Composer autoload (Google Client Library)
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
} else {
    die('Google Client Library not found. Run "composer require google/apiclient"');
}

// 2. GOOGLE ТОХИРГОО
define('GOOGLE_CLIENT_ID', '373366261943-i0kps82i4tf6g2g0hp0jbvccu5aigcrf.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', 'https://www.filezone.mn/google-login.php');

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");

// 3. БУЦАЖ ИРЭХ ХЭСЭГ (Callback Handler)
if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (!isset($token['error'])) {
            $client->setAccessToken($token['access_token']);
            
            $google_service = new Google_Service_Oauth2($client);
            $data = $google_service->userinfo->get();

            $google_id = $data->id;
            $name = $data->name;
            $email = $data->email;
            $picture = $data->picture;

            // --- ХЭРЭГЛЭГЧИЙГ ШАЛГАХ (PDO) ---
            $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
            $stmt->execute([$google_id, $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // А. Хэрэглэгч бүртгэлтэй бол нэвтрүүлэх
                
                // Google ID эсвэл Avatar шинэчлэгдсэн бол update хийх
                if (empty($user['google_id']) || empty($user['avatar_url'])) {
                    $update_sql = "UPDATE users SET google_id = ?, avatar_url = ? WHERE id = ?";
                    $update_stmt = $pdo->prepare($update_sql);
                    $update_stmt->execute([$google_id, $picture, $user['id']]);
                    
                    // Шинэчлэгдсэн мэдээллийг session-д авахын тулд avatar-г update хийнэ
                    $user['avatar_url'] = $picture;
                }

                // Session үүсгэх
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['avatar'] = $user['avatar_url'];

                header("Location: index.php");
                exit();

            } else {
                // Б. Шинэ хэрэглэгч бүртгэх
                
                // Username давхардахаас сэргийлэх
                $base_username = strtolower(str_replace(' ', '', $name));
                $username = $base_username;
                $counter = 1;

                while (true) {
                    $chk_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $chk_stmt->execute([$username]);
                    if ($chk_stmt->rowCount() == 0) break;
                    $username = $base_username . $counter++;
                }

                // Санамсаргүй нууц үг
                $random_pass = bin2hex(random_bytes(10));
                $hashed_pass = password_hash($random_pass, PASSWORD_DEFAULT);

                // Баазад оруулах
                $sql = "INSERT INTO users (username, email, google_id, password, role, is_verified, avatar_url, join_date, last_active) 
                        VALUES (?, ?, ?, ?, 'user', 1, ?, NOW(), NOW())";
                $insert_stmt = $pdo->prepare($sql);
                $insert_stmt->execute([$username, $email, $google_id, $hashed_pass, $picture]);

                // Шинэ хэрэглэгчийн ID-г авах
                $newUserId = $pdo->lastInsertId();

                // Session үүсгэх
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'user';
                $_SESSION['avatar'] = $picture;

                header("Location: index.php");
                exit();
            }

        } else {
            // Token error
            header("Location: login.php?error=google_token");
            exit();
        }
    } catch (Exception $e) {
        // Exception handling
        error_log("Google Login Error: " . $e->getMessage());
        header("Location: login.php?error=google_exception");
        exit();
    }
}

// 4. КОД БАЙХГҮЙ БОЛ GOOGLE РҮҮ ҮСЭРГЭХ
// Хэрэв хэрэглэгч шууд энэ файл руу хандвал Google login page рүү явна
$authUrl = $client->createAuthUrl();
header("Location: " . $authUrl);
exit();
?>