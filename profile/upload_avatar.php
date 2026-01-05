<?php
// profile/upload_avatar.php

session_start();
// JSON гаралт буцаах тул буферлэж эхэлнэ
ob_start();
header('Content-Type: application/json');

// 1. Тохиргооны файлуудыг дуудах (Хавтас дотор байгаа тул ../ ашиглана)
require_once '../includes/db.php'; 

// 2. Баазтай холбогдох (Хэрэв db.php дотор холболт үүсээгүй бол)
if (!isset($conn)) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
        exit;
    }
}

// 3. Нэвтэрсэн эсэхийг шалгах
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Login required.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// 4. POST хүсэлт болон Файл ирсэн эсэхийг шалгах
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    
    // Output buffer цэвэрлэх
    ob_clean(); 

    try {
        $file = $_FILES['avatar'];
        
        // Алдаа шалгах
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error code: ' . $file['error']);
        }

        // Төрөл шалгах
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new Exception('Файл зураг биш байна.');
        }

        // MIME type шалгах
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($imageInfo['mime'], $allowed_mimes)) {
            throw new Exception('Зөвхөн JPG, PNG, GIF, WEBP зураг оруулна уу.');
        }

        // Хэмжээ шалгах (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('Зургийн хэмжээ 5MB-аас хэтрэхгүй байх ёстой.');
        }

        // Хадгалах хавтас (Root руу буцаж байж uploads руу орно)
        // Анхаар: Бодит хадгалах зам (Server Path)
        $base_upload_dir = '../uploads/avatars/' . $user_id . '/';
        
        if (!file_exists($base_upload_dir)) {
            if (!mkdir($base_upload_dir, 0755, true)) {
                throw new Exception('Хавтас үүсгэж чадсангүй (Permission error).');
            }
        }

        // Хуучин зургийг авах (Устгахын тулд)
        $old_sql = "SELECT avatar_url FROM users WHERE id = ?";
        $old_stmt = $conn->prepare($old_sql);
        $old_stmt->bind_param("i", $user_id);
        $old_stmt->execute();
        $old_res = $old_stmt->get_result();
        $old_row = $old_res->fetch_assoc();
        // Баазад хадгалагдсан зам (жишээ нь: uploads/avatars/1/img.jpg)
        $old_avatar_db_path = $old_row['avatar_url'] ?? '';

        // Шинэ файлын нэр
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if(empty($ext)) {
            $mime_map = [
                'image/jpeg' => 'jpg', 'image/png' => 'png',
                'image/gif' => 'gif', 'image/webp' => 'webp'
            ];
            $ext = $mime_map[$imageInfo['mime']] ?? 'jpg';
        }
        
        $new_filename = 'avatar_' . time() . '_' . uniqid() . '.' . $ext;
        
        // Сервер дээр хадгалах зам
        $target_path_server = $base_upload_dir . $new_filename;
        
        // Баазад хадгалах зам (Вэбээс дуудахад хэрэглэх зам - Root-ээс эхлэлтэй)
        // "../uploads/..." гэж биш "uploads/..." гэж хадгалвал зүгээр байдаг.
        // Гэхдээ таны систем яаж ажилладгаас хамаарна. Одоогоор стандартаар хадгалая.
        $target_path_db = 'uploads/avatars/' . $user_id . '/' . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $target_path_server)) {
            // Баазад шинэчлэх
            $update_sql = "UPDATE users SET avatar_url = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $target_path_db, $user_id);
            
            if ($update_stmt->execute()) {
                // Хуучин зургийг устгах
                // Хуучин зам нь root-ээс эхтэй тул устгахдаа ../ залгах хэрэгтэй болж магадгүй
                $old_file_to_delete = '../' . $old_avatar_db_path;
                
                if (!empty($old_avatar_db_path) && file_exists($old_file_to_delete) && strpos($old_avatar_db_path, 'default') === false) {
                    // Шинэ файлтай давхцахгүй бол устга
                    if(realpath($old_file_to_delete) != realpath($target_path_server)){
                         unlink($old_file_to_delete);
                    }
                }
                
                echo json_encode([
                    'success' => true, 
                    // Frontend дээр харуулахдаа ../uploads/... гэж харуулах хэрэгтэй
                    'new_avatar_url' => '../' . $target_path_db,
                    'message' => 'Зураг амжилттай солигдлоо.'
                ]);
            } else {
                unlink($target_path_server);
                throw new Exception('Database update failed.');
            }
        } else {
            throw new Exception('Файлыг зөөж чадсангүй.');
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
exit();