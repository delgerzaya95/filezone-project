<?php
// Filezone Kids - Toggle Save AJAX
// Path: kids/ajax_save.php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/db_kids.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$material_id = isset($_POST['material_id']) ? (int)$_POST['material_id'] : 0;

if ($material_id > 0) {
    try {
        // Check if already saved
        $stmt_check = $pdo_kids->prepare("SELECT id FROM kids_saved_materials WHERE user_id = ? AND material_id = ?");
        $stmt_check->execute([$user_id, $material_id]);
        
        if ($stmt_check->fetch()) {
            // Remove
            $stmt_del = $pdo_kids->prepare("DELETE FROM kids_saved_materials WHERE user_id = ? AND material_id = ?");
            $stmt_del->execute([$user_id, $material_id]);
            echo json_encode(['status' => 'removed']);
        } else {
            // Add
            $stmt_add = $pdo_kids->prepare("INSERT INTO kids_saved_materials (user_id, material_id) VALUES (?, ?)");
            $stmt_add->execute([$user_id, $material_id]);
            echo json_encode(['status' => 'saved']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
}
?>