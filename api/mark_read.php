<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_SESSION['user_id'])) {
    $notif_id = intval($_POST['id']);
    $user_id = $_SESSION['user_id'];

    // Зөвхөн өөрийн мэдэгдлийг уншсан болгох
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notif_id, $user_id]);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>