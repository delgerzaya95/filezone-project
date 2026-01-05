<?php
session_start();
include 'includes/db.php';

// Баазтай холбогдох
if (!isset($conn)) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Нэвтрээгүй бол login руу
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$file_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($file_id > 0) {
    // 1. Файлын үндсэн мэдээллийг авах (file_url баганаас)
    // Зөвхөн өөрийн оруулсан файлыг устгах эрхтэй байна
    $sql = "SELECT id, file_url FROM files WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $file = $result->fetch_assoc();
        
        // ---------------------------------------------------------
        // АЛХАМ 1: Үндсэн файлыг серверээс устгах
        // ---------------------------------------------------------
        if (!empty($file['file_url']) && file_exists($file['file_url'])) {
            unlink($file['file_url']);
        }
        
        // ---------------------------------------------------------
        // АЛХАМ 2: Preview зургуудыг серверээс устгах
        // (file_previews хүснэгтээс замнуудыг авч устгана)
        // ---------------------------------------------------------
        $preview_sql = "SELECT preview_url FROM file_previews WHERE file_id = ?";
        $p_stmt = $conn->prepare($preview_sql);
        $p_stmt->bind_param("i", $file_id);
        $p_stmt->execute();
        $p_result = $p_stmt->get_result();

        while ($row = $p_result->fetch_assoc()) {
            if (!empty($row['preview_url']) && file_exists($row['preview_url'])) {
                // Default зураг биш бол устгана (хэрэв та default зураг ашигладаг бол)
                if (strpos($row['preview_url'], 'default') === false) {
                    unlink($row['preview_url']);
                }
            }
        }

        // ---------------------------------------------------------
        // АЛХАМ 3: Баазаас устгах
        // files хүснэгтээс устгахад 'ON DELETE CASCADE' тохиргоотой тул 
        // file_previews, comments, ratings зэрэг хүснэгтээс
        // холбоотой мэдээллүүд автоматаар устана.
        // ---------------------------------------------------------
        $delete_sql = "DELETE FROM files WHERE id = ?";
        $del_stmt = $conn->prepare($delete_sql);
        $del_stmt->bind_param("i", $file_id);
        
        if ($del_stmt->execute()) {
            // Амжилттай устгагдлаа
             header("Location: profile.php?msg=deleted");
        } else {
            // Баазын алдаа
            header("Location: profile.php?err=db_error");
        }
    } else {
        // Файл олдсонгүй эсвэл уг хэрэглэгчийнх биш
        header("Location: profile.php?err=not_found");
    }
} else {
    header("Location: profile.php");
}
?>