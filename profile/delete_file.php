<?php
session_start();

// 1. DB холболт (profile фолдер дотор байгаа тул ../ ашиглана)
require_once '../includes/db.php';

// Баазтай холбогдох (db.php-ээс $pdo ирэх ёстой, гэхдээ mysqli ашиглаж байгаа бол доорх)
if (!isset($conn) && !isset($pdo)) {
    // Fallback if db.php doesn't provide connection immediately
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// PDO ашиглахыг зөвлөж байна (өмнөх файлууд дээр PDO ашигласан тул)
// Хэрэв таны db.php $conn (mysqli) буцаадаг бол түүнийг ашиглана.
// Энд нийцтэй байдлын үүднээс PDO ашиглая (My_files.php дээр $conn байсан ч edit_file дээр $pdo байсан).
// Таны db.php файлыг харахад $conn эсвэл $pdo алийг нь ч үүсгэж магадгүй тул шалгая.
if (isset($pdo)) {
    $db = $pdo;
    $is_pdo = true;
} else {
    $db = $conn;
    $is_pdo = false;
}

// Нэвтрээгүй бол login руу
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$file_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($file_id > 0) {
    // 1. Файлын үндсэн мэдээллийг авах
    $sql = "SELECT id, file_url FROM files WHERE id = ? AND user_id = ?";
    
    if ($is_pdo) {
        $stmt = $db->prepare($sql);
        $stmt->execute([$file_id, $user_id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $file_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $file = $result->fetch_assoc();
    }

    if ($file) {
        
        // ---------------------------------------------------------
        // АЛХАМ 1: Үндсэн файлыг серверээс устгах
        // ---------------------------------------------------------
        // DB дээрх зам нь 'uploads/...' гэж байгаа тул '../uploads/...' болгоно
        $file_path = '../' . $file['file_url'];
        
        if (!empty($file['file_url']) && file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Хавтас цэвэрлэх бэлтгэл (file_url нь uploads/files/USER/FILE_ID/filename.ext гэж байгаа)
        $file_dir = dirname($file_path); 

        // ---------------------------------------------------------
        // АЛХАМ 2: Preview зургуудыг серверээс устгах
        // ---------------------------------------------------------
        $preview_sql = "SELECT preview_url FROM file_previews WHERE file_id = ?";
        
        if ($is_pdo) {
            $p_stmt = $db->prepare($preview_sql);
            $p_stmt->execute([$file_id]);
            $previews = $p_stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $p_stmt = $db->prepare($preview_sql);
            $p_stmt->bind_param("i", $file_id);
            $p_stmt->execute();
            $p_result = $p_stmt->get_result();
            $previews = [];
            while ($row = $p_result->fetch_assoc()) {
                $previews[] = $row;
            }
        }

        foreach ($previews as $row) {
            $img_path = '../' . $row['preview_url'];
            if (!empty($row['preview_url']) && file_exists($img_path)) {
                // Default зураг биш бол устгана
                if (strpos($row['preview_url'], 'default') === false) {
                    unlink($img_path);
                }
            }
        }

        // ---------------------------------------------------------
        // АЛХАМ 3: Хоосон хавтаснуудыг устгах (Цэвэрлэгээ)
        // ---------------------------------------------------------
        // 1. previews хавтас
        $previews_dir = $file_dir . '/previews';
        if (is_dir($previews_dir)) {
            // Хавтас хоосон эсэхийг шалгахгүйгээр rmdir хийж үзнэ (хоосон бол устана)
            @rmdir($previews_dir); 
        }
        // 2. file_id хавтас
        if (is_dir($file_dir)) {
            @rmdir($file_dir);
        }

        // ---------------------------------------------------------
        // АЛХАМ 4: Баазаас устгах
        // ---------------------------------------------------------
        $delete_sql = "DELETE FROM files WHERE id = ?";
        
        if ($is_pdo) {
            $del_stmt = $db->prepare($delete_sql);
            $deleted = $del_stmt->execute([$file_id]);
        } else {
            $del_stmt = $db->prepare($delete_sql);
            $del_stmt->bind_param("i", $file_id);
            $deleted = $del_stmt->execute();
        }
        
        if ($deleted) {
            // Амжилттай устгагдлаа -> my_files.php руу буцах
             header("Location: my_files.php?msg=deleted");
        } else {
            // Баазын алдаа
            header("Location: my_files.php?err=db_error");
        }
    } else {
        // Файл олдсонгүй эсвэл уг хэрэглэгчийнх биш
        header("Location: my_files.php?err=not_found");
    }
} else {
    header("Location: my_files.php");
}
exit;
?>