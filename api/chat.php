<?php
// api/chat.php
session_start();

// DB холболт
require_once '../includes/db.php';
// Notification функцийг оруулж ирэх
if (file_exists('../includes/notifications.php')) {
    require_once '../includes/notifications.php';
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Нэвтрэх шаардлагатай']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// --------------------------------------------------------------------------
// 1. ЗУРВАС ИЛГЭЭХ (SEND MESSAGE)
// --------------------------------------------------------------------------
if ($action === 'send_message') {
    $order_id = intval($_POST['order_id']);
    $message = trim($_POST['message'] ?? ''); // Message can be empty if file is sent

    // Файл байгаа эсэхийг шалгах
    $has_file = isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK;

    if (empty($message) && !$has_file) {
        echo json_encode(['success' => false, 'error' => 'Зурвас эсвэл файл оруулна уу']);
        exit;
    }

    try {
        // Хэрэглэгч болон эрх шалгах + Нөгөө хэрэглэгчийг олох
        $check = $pdo->prepare("SELECT id, buyer_id, seller_id FROM service_orders WHERE id = ?");
        $check->execute([$order_id]);
        $order = $check->fetch(PDO::FETCH_ASSOC);
        
        if (!$order || ($order['buyer_id'] != $user_id && $order['seller_id'] != $user_id)) {
            echo json_encode(['success' => false, 'error' => 'Танд энэ захиалга дээр бичих эрх байхгүй.']);
            exit;
        }

        // Хүлээн авагчийг тодорхойлох
        $receiver_id = ($order['buyer_id'] == $user_id) ? $order['seller_id'] : $order['buyer_id'];

        // Файл хадгалах логик
        $file_url = null;
        if ($has_file) {
            $file = $_FILES['attachment'];
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'zip', 'rar', 'txt', 'xlsx'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed) && $file['size'] <= 10 * 1024 * 1024) { // 10MB Limit
                // Хавтас үүсгэх
                $upload_dir = '../uploads/chat/' . $order_id . '/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
                $destination = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $file_url = 'uploads/chat/' . $order_id . '/' . $filename;
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Файлын төрөл буруу эсвэл хэмжээ хэтэрсэн байна.']);
                exit;
            }
        }

        // Бааз руу хадгалах
        $stmt = $pdo->prepare("INSERT INTO order_messages (order_id, sender_id, message, file_url) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $user_id, $message, $file_url]);

        // МЭДЭГДЭЛ ИЛГЭЭХ (Notification)
        if (function_exists('sendNotification')) {
            $notif_msg = $has_file ? "Танд шинэ файл ирлээ." : "Танд шинэ зурвас ирлээ.";
            // Сүүлийн 5 минутад мэдэгдэл очсон эсэхийг шалгах (Spam-аас сэргийлэх) - Сонголтоор
            // Энд шууд илгээнэ
            sendNotification($pdo, $receiver_id, 'message', $notif_msg, "profile/order_details.php?id=$order_id");
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}

// --------------------------------------------------------------------------
// 2. ЗУРВАС ТАТАХ (GET MESSAGES)
// --------------------------------------------------------------------------
if ($action === 'get_messages') {
    $order_id = intval($_POST['order_id']);
    
    // Хэрэглэгч шалгах
    $check = $pdo->prepare("SELECT id, buyer_id, seller_id FROM service_orders WHERE id = ?");
    $check->execute([$order_id]);
    $order = $check->fetch(PDO::FETCH_ASSOC);
    
    if (!$order || ($order['buyer_id'] != $user_id && $order['seller_id'] != $user_id)) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    // Зурвасуудыг авах
    $stmt = $pdo->prepare("
        SELECT m.*, u.username, u.avatar_url, u.full_name
        FROM order_messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.order_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$order_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Уншсан болгох
    $pdo->prepare("UPDATE order_messages SET is_read = 1 WHERE order_id = ? AND sender_id != ?")->execute([$order_id, $user_id]);

    // HTML бэлдэх
    $html = '';
    if (count($messages) > 0) {
        $last_date = '';

        foreach ($messages as $msg) {
            $is_me = ($msg['sender_id'] == $user_id);
            
            // Огноо
            $msg_date = date('Y-m-d', strtotime($msg['created_at']));
            if ($msg_date != $last_date) {
                $display_date = ($msg_date == date('Y-m-d')) ? 'Өнөөдөр' : $msg_date;
                $html .= '<div class="flex justify-center mb-4"><span class="text-[10px] bg-gray-200 text-gray-500 px-2 py-0.5 rounded-full">'.$display_date.'</span></div>';
                $last_date = $msg_date;
            }

            $time = date('H:i', strtotime($msg['created_at']));
            
            // Avatar
            $avatar = $msg['avatar_url'];
            if(empty($avatar)) {
                $name_for_avatar = !empty($msg['full_name']) ? $msg['full_name'] : $msg['username'];
                $avatar = "https://ui-avatars.com/api/?name=".urlencode($name_for_avatar)."&background=random&color=fff";
            } elseif (strpos($avatar, 'http') !== 0) {
                $avatar = '../' . $avatar;
            }

            // Styles
            $align = $is_me ? 'justify-end' : 'justify-start';
            $bubble_color = $is_me ? 'bg-blue-600 text-white rounded-br-none' : 'bg-white text-gray-800 border border-gray-200 rounded-bl-none';
            $meta_align = $is_me ? 'text-right' : 'text-left';

            // Файл харуулах хэсэг
            $file_html = '';
            if (!empty($msg['file_url'])) {
                $file_name = basename($msg['file_url']);
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                $file_path = '../' . $msg['file_url'];

                if ($is_image) {
                    $file_html = '<div class="mb-2"><a href="'.$file_path.'" target="_blank"><img src="'.$file_path.'" class="max-w-[200px] rounded-lg border border-gray-200 hover:opacity-90 transition"></a></div>';
                } else {
                    $icon = 'fa-file';
                    if ($ext == 'pdf') $icon = 'fa-file-pdf';
                    if (in_array($ext, ['zip', 'rar'])) $icon = 'fa-file-archive';
                    if (in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word';
                    
                    $file_bg = $is_me ? 'bg-blue-500 border-blue-400' : 'bg-gray-50 border-gray-200';
                    $file_text = $is_me ? 'text-white' : 'text-gray-700';
                    
                    $file_html = '
                    <a href="'.$file_path.'" target="_blank" class="flex items-center gap-3 p-3 mb-2 rounded-lg border '.$file_bg.' hover:opacity-90 transition group">
                        <div class="w-8 h-8 flex items-center justify-center bg-white/20 rounded-lg">
                            <i class="fas '.$icon.' '.$file_text.'"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold truncate '.$file_text.'">'.$file_name.'</p>
                            <p class="text-[10px] opacity-70 '.$file_text.'">Татах</p>
                        </div>
                    </a>';
                }
            }

            $html .= '
            <div class="flex w-full '.$align.' mb-4 group animate-fade-in">
                '.(!$is_me ? '<img src="'.$avatar.'" class="w-8 h-8 rounded-full mr-2 self-end mb-1 object-cover border border-gray-100">' : '').'
                <div class="max-w-[75%]">
                    '. (!empty($msg['message']) || !empty($file_html) ? 
                        '<div class="'.$bubble_color.' px-4 py-2.5 rounded-2xl text-sm shadow-sm break-words leading-relaxed relative">
                            '.$file_html.'
                            '.nl2br(htmlspecialchars($msg['message'])).'
                        </div>' : '') .'
                    <div class="text-[10px] text-gray-400 mt-1 '.$meta_align.' flex items-center gap-1 '.($is_me ? 'justify-end' : '').'">
                        '.$time.'
                        '.($is_me ? ($msg['is_read'] ? '<i class="fas fa-check-double text-blue-500"></i>' : '<i class="fas fa-check"></i>') : '').'
                    </div>
                </div>
            </div>';
        }
    } else {
        $html = '<div class="flex flex-col items-center justify-center h-full text-gray-400">
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-2">
                <i class="far fa-comments text-xl"></i>
            </div>
            <p class="text-sm">Харилцан яриа эхлээгүй байна.</p>
        </div>';
    }
    
    echo json_encode(['success' => true, 'html' => $html]);
    exit;
}
?>