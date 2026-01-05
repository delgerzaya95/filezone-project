<?php
// includes/notifications.php

/**
 * 1. Үндсэн мэдэгдэл илгээх функц
 */
function sendNotification($pdo, $user_id, $type, $message, $link = '#') {
    if (!$user_id || empty($message)) return false;

    try {
        $sql = "INSERT INTO notifications (user_id, type, message, link, is_read, created_at) 
                VALUES (:user_id, :type, :message, :link, 0, NOW())";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $user_id,
            ':type'    => $type,
            ':message' => $message,
            ':link'    => $link
        ]);
    } catch (PDOException $e) {
        error_log("Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * 2. Файл худалдан авалт (File Purchase)
 */
function processFilePurchase($pdo, $file_id, $buyer_id) {
    // ... (Өмнөх код хэвээрээ) ...
    $stmt = $pdo->prepare("SELECT title, user_id, price FROM files WHERE id = ?");
    $stmt->execute([$file_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) return null;

    $seller_id = $file['user_id'];
    $file_title = $file['title'];
    $price = $file['price'];

    if (session_status() === PHP_SESSION_NONE) session_start();
    $session_key = 'notified_file_' . $file_id . '_' . $buyer_id;

    if (!isset($_SESSION[$session_key])) {
        // Buyer notification
        sendNotification($pdo, $buyer_id, 'success', "Та \"{$file_title}\" файлыг амжилттай худалдаж авлаа.", "profile/my_files.php");
        
        // Seller notification
        if ($seller_id != $buyer_id) { 
            sendNotification($pdo, $seller_id, 'order', "Таны \"{$file_title}\" файл зарагдлаа! Орлого: " . number_format($price) . "₮", "profile/dashboard.php");
        }
        $_SESSION[$session_key] = true;
    }
    return $file;
}

/**
 * 3. Үйлчилгээний захиалга (Service Order) - ШИНЭ
 */
function processServicePurchase($pdo, $service_id, $buyer_id) {
    // Үйлчилгээний мэдээлэл авах
    $stmt = $pdo->prepare("SELECT title, user_id, price_min FROM services WHERE id = ?");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$service) return null;

    $seller_id = $service['user_id'];
    $title = $service['title'];
    $price = $service['price_min']; // Эхлэх үнээр тооцож байна (Бодит системд сонгосон багцын үнэ байх ёстой)

    // Session шалгах
    if (session_status() === PHP_SESSION_NONE) session_start();
    $session_key = 'notified_service_' . $service_id . '_' . $buyer_id;

    if (!isset($_SESSION[$session_key])) {
        
        // A. service_orders хүснэгтэд захиалга үүсгэх
        // Эхлээд ийм захиалга байгаа эсэхийг шалгана (Pending төлөвтэй байж магадгүй)
        // Гэхдээ төлбөр төлөгдсөн тул шинээр үүсгэх эсвэл Pending-ийг Active болгох хэрэгтэй.
        // Энд шууд шинээр Active төлөвтэй үүсгэе.
        
        $stmt_order = $pdo->prepare("INSERT INTO service_orders (service_id, buyer_id, seller_id, price, status, ordered_at) VALUES (?, ?, ?, ?, 'active', NOW())");
        $stmt_order->execute([$service_id, $buyer_id, $seller_id, $price]);
        $order_id = $pdo->lastInsertId();

        // B. Мэдэгдэл: Худалдан авагч (Buyer)
        sendNotification(
            $pdo,
            $buyer_id,           
            'success',           
            "Та \"{$title}\" үйлчилгээг амжилттай захиаллаа. Захиалгын ID: #{$order_id}", 
            "profile/my_orders.php?id={$order_id}"
        );

        // C. Мэдэгдэл: Гүйцэтгэгч (Seller)
        if ($seller_id != $buyer_id) { 
            sendNotification(
                $pdo,
                $seller_id,          
                'order',             
                "Шинэ захиалга! \"{$title}\" үйлчилгээнд захиалга ирлээ. Үнэ: " . number_format($price) . "₮", 
                "profile/service_orders.php?id={$order_id}" // Гүйцэтгэгчийн захиалга удирдах хэсэг
            );
        }

        $_SESSION[$session_key] = true;
    }

    return $service;
}
?>