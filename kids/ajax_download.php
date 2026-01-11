<?php
// Filezone Kids - Track Downloads AJAX
// Path: kids/ajax_download.php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/db_kids.php';

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Invalid Request');
}

$material_id = isset($_POST['material_id']) ? (int)$_POST['material_id'] : 0;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

if ($material_id > 0) {
    try {
        // 1. Increment total download count
        $stmt = $pdo_kids->prepare("UPDATE kids_materials SET download_count = download_count + 1 WHERE id = ?");
        $stmt->execute([$material_id]);

        // 2. Log user download history (if logged in)
        if ($user_id > 0) {
            $stmt_log = $pdo_kids->prepare("INSERT INTO kids_downloads (user_id, material_id) VALUES (?, ?)");
            $stmt_log->execute([$user_id, $material_id]);
        }
        
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        // Error handling (Silent)
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>