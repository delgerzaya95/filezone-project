<?php
// api/toggle_save.php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

// Нэвтрээгүй бол алдаа буцаана
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    // ----------------------------------------------------------------------
    // 1. ҮЙЛЧИЛГЭЭ ХАДГАЛАХ (TOGGLE SERVICE)
    // ----------------------------------------------------------------------
    if ($action === 'toggle_save_service') {
        $service_id = intval($_POST['id']); // Generic 'id' parameter

        // Шалгах
        $check = $pdo->prepare("SELECT id FROM saved_services WHERE user_id = ? AND service_id = ?");
        $check->execute([$user_id, $service_id]);

        if ($check->rowCount() > 0) {
            // Хадгалсан байна -> Устгах (Unsave)
            $del = $pdo->prepare("DELETE FROM saved_services WHERE user_id = ? AND service_id = ?");
            $del->execute([$user_id, $service_id]);
            echo json_encode(['success' => true, 'status' => 'unsaved', 'message' => 'Хадгалахаа болилоо.']);
        } else {
            // Хадгалаагүй байна -> Хадгалах (Save)
            $ins = $pdo->prepare("INSERT INTO saved_services (user_id, service_id) VALUES (?, ?)");
            $ins->execute([$user_id, $service_id]);
            echo json_encode(['success' => true, 'status' => 'saved', 'message' => 'Амжилттай хадгаллаа.']);
        }
        exit;
    }

    // ----------------------------------------------------------------------
    // 2. ФАЙЛ ХАДГАЛАХ (TOGGLE FILE)
    // ----------------------------------------------------------------------
    if ($action === 'toggle_save_file') {
        $file_id = intval($_POST['id']); // Generic 'id' parameter

        // Шалгах
        $check = $pdo->prepare("SELECT id FROM saved_files WHERE user_id = ? AND file_id = ?");
        $check->execute([$user_id, $file_id]);

        if ($check->rowCount() > 0) {
            // Хадгалсан байна -> Устгах (Unsave)
            $del = $pdo->prepare("DELETE FROM saved_files WHERE user_id = ? AND file_id = ?");
            $del->execute([$user_id, $file_id]);
            echo json_encode(['success' => true, 'status' => 'unsaved', 'message' => 'Хадгалахаа болилоо.']);
        } else {
            // Хадгалаагүй байна -> Хадгалах (Save)
            $ins = $pdo->prepare("INSERT INTO saved_files (user_id, file_id) VALUES (?, ?)");
            $ins->execute([$user_id, $file_id]);
            echo json_encode(['success' => true, 'status' => 'saved', 'message' => 'Амжилттай хадгаллаа.']);
        }
        exit;
    }

    // Буруу action ирвэл
    echo json_encode(['success' => false, 'message' => 'Invalid action']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>