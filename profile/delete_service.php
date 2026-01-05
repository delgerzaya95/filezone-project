<?php
// profile/delete_service.php
session_start();
include '../includes/db.php';

if (!isset($conn)) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
}

// Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check ID
if (!isset($_GET['id'])) {
    header("Location: my_services.php");
    exit();
}

$service_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Check Ownership
$check_sql = "SELECT id FROM services WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $service_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Soft Delete: Status-ийг 'deleted' болгох
    $delete_sql = "UPDATE services SET status = 'deleted' WHERE id = ?";
    $del_stmt = $conn->prepare($delete_sql);
    $del_stmt->bind_param("i", $service_id);
    
    if ($del_stmt->execute()) {
        // Success
        header("Location: my_services.php?msg=deleted");
    } else {
        // DB Error
        header("Location: my_services.php?error=db_error");
    }
} else {
    // Not owner or not found
    header("Location: my_services.php?error=not_found");
}
exit();
?>