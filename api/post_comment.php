<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$file_id = isset($_POST['file_id']) ? intval($_POST['file_id']) : 0;
$comment_text = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($file_id <= 0 || empty($comment_text)) {
    echo json_encode(['success' => false, 'message' => 'Мэдээлэл дутуу байна']);
    exit;
}

try {
    // Insert Comment
    $stmt = $pdo->prepare("INSERT INTO comments (user_id, file_id, comment, comment_date, status) VALUES (?, ?, ?, NOW(), 'approved')");
    $stmt->execute([$user_id, $file_id, $comment_text]);
    
    // Fetch User Info for Response
    $stmt_user = $pdo->prepare("SELECT username, avatar_url FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    $avatar = !empty($user['avatar_url']) ? $user['avatar_url'] : 'assets/avatars/default.png';

    echo json_encode([
        'success' => true,
        'message' => 'Сэтгэгдэл амжилттай нэмэгдлээ',
        'comment' => nl2br(htmlspecialchars($comment_text)),
        'user' => [
            'username' => htmlspecialchars($user['username']),
            'avatar' => htmlspecialchars($avatar)
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>