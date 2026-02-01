<?php
session_start();
require_once '../includes/db.php';

// Brevo API холболт (Зам зөв эсэхийг шалгах)
if (file_exists('api/brevo_admin.php')) {
    require_once 'api/brevo_admin.php';
} else {
    // Хэрэв өөр хавтсанд байвал шалгах
    if (file_exists('../admin/api/brevo_admin.php')) {
         require_once '../admin/api/brevo_admin.php';
    } else {
         // Файл олдохгүй бол алдаа заана, гэхдээ сайт гацахгүй байх үүднээс die() хийхгүй байж болно
         $brevo_error = "Brevo API файл олдсонгүй.";
    }
}

// Админ эрх шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$msg = '';
$msg_type = '';

// URL-аас мэдээлэл авах (Жишээ нь хэрэглэгчийн жагсаалтаас 'Мэйл бичих' дарахад автоматаар бөглөгдөнө)
$pre_email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
$pre_name = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : '';
$pre_subject = isset($_GET['subject']) ? htmlspecialchars($_GET['subject']) : '';

// Мэйл илгээх үйлдэл
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to_email = trim($_POST['to_email']);
    $to_name = trim($_POST['to_name']);
    $subject = trim($_POST['subject']);
    
    // Загварын хэсгүүд
    $main_title = trim($_POST['main_title']);
    $body_content = trim($_POST['body_content']);
    
    // Info Box (Сонголттой)
    $info_title = trim($_POST['info_title']);
    $info_text = trim($_POST['info_text']);
    $infoBox = null;
    if (!empty($info_title) && !empty($info_text)) {
        $infoBox = ['title' => $info_title, 'text' => $info_text];
    }

    // Button (Сонголттой)
    $btn_text = trim($_POST['btn_text']);
    $btn_link = trim($_POST['btn_link']);
    $button = null;
    if (!empty($btn_text) && !empty($btn_link)) {
        $button = ['text' => $btn_text, 'link' => $btn_link];
    }

    if (empty($to_email) || empty($subject) || empty($body_content)) {
        $msg = "Имэйл, Гарчиг болон Агуулга заавал байх ёстой.";
        $msg_type = "error";
    } else {
        if (!function_exists('getPremiumEmailTemplate')) {
             $msg = "Мэйл илгээх функц олдсонгүй (api/brevo_admin.php файл дуудагдсангүй).";
             $msg_type = "error";
        } else {
            // Line break-ийг HTML <br> болгох (Энгийн текст бичихэд мөр шилжилт хадгалагдана)
            $formatted_body = nl2br($body_content);

            // Загвар үүсгэх
            $html_content = getPremiumEmailTemplate($to_name, $main_title, $formatted_body, $infoBox, $button);

            // Илгээх
            $result = _sendBrevoRequest($to_email, $to_name, $subject, $html_content);
            $res = json_decode($result, true);

            if (isset($res['messageId'])) {
                $msg = "Мэйл амжилттай илгээгдлээ! (Message ID: " . $res['messageId'] . ")";
                $msg_type = "success";
                
                // Амжилттай болсны дараа талбаруудыг цэвэрлэх эсэх (Одоогоор цэвэрлэхгүй үлдээе, дахин илгээхэд амар)
                // $to_email = ''; $to_name = ''; ...
            } else {
                $msg = "Алдаа гарлаа: " . ($res['message'] ?? 'Unknown error');
                $msg_type = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мэйл илгээх - FileZone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
</head>
<body class="font-sans text-slate-800 antialiased bg-slate-50">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- HEADER -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shadow-sm z-10">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden text-slate-500"><i class="fas fa-bars text-xl"></i></button>
                    <h1 class="text-xl font-bold text-slate-800">Гараар мэйл илгээх</h1>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                
                <div class="max-w-4xl mx-auto">
                    
                    <?php if (isset($brevo_error)): ?>
                         <div class="p-4 rounded-lg mb-6 bg-red-100 text-red-700 border border-red-200">
                            <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $brevo_error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($msg): ?>
                        <div class="p-4 rounded-lg mb-6 <?php echo $msg_type == 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
                            <i class="<?php echo $msg_type == 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'; ?> mr-2"></i>
                            <?php echo $msg; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-6 border-b border-slate-100 bg-slate-50">
                            <h2 class="font-bold text-slate-700">Хүлээн авагчийн мэдээлэл</h2>
                        </div>
                        
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Хүлээн авагчийн мэйл <span class="text-red-500">*</span></label>
                                <input type="email" name="to_email" value="<?php echo isset($to_email) ? htmlspecialchars($to_email) : $pre_email; ?>" required class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="user@example.com">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Хүлээн авагчийн нэр</label>
                                <input type="text" name="to_name" value="<?php echo isset($to_name) ? htmlspecialchars($to_name) : $pre_name; ?>" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Хэрэглэгчийн нэр">
                            </div>
                        </div>

                        <div class="p-6 border-t border-slate-100 bg-slate-50">
                            <h2 class="font-bold text-slate-700">Мэйлийн агуулга</h2>
                        </div>

                        <div class="p-6 space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Мэйлийн гарчиг (Subject) <span class="text-red-500">*</span></label>
                                <input type="text" name="subject" value="<?php echo isset($subject) ? htmlspecialchars($subject) : $pre_subject; ?>" required class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Жнь: Re: Таны асуултын хариу">
                                <p class="text-xs text-slate-500 mt-1">Хэрэглэгчийн Inbox-д харагдах гарчиг.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Том гарчиг (Main Title)</label>
                                <input type="text" name="main_title" value="<?php echo isset($main_title) ? htmlspecialchars($main_title) : 'Сайн байна уу?'; ?>" class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Жнь: Сайн байна уу?">
                                <p class="text-xs text-slate-500 mt-1">Мэйлийг нээхэд дотор нь хамгийн дээр том үсгээр харагдах гарчиг.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Үндсэн текст (Body) <span class="text-red-500">*</span></label>
                                <textarea name="body_content" rows="8" required class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Энд хариу захиагаа бичнэ үү..."><?php echo isset($body_content) ? htmlspecialchars($body_content) : ''; ?></textarea>
                                <p class="text-xs text-slate-500 mt-1">Enter дарж шинэ мөрөнд шилжихэд автоматаар мөр шилжинэ.</p>
                            </div>
                        </div>

                        <!-- Optional Components -->
                        <div class="p-6 border-t border-slate-100 bg-slate-50 flex items-center justify-between cursor-pointer hover:bg-slate-100 transition" onclick="document.getElementById('extraOptions').classList.toggle('hidden');">
                            <h2 class="font-bold text-slate-700 flex items-center gap-2"><i class="fas fa-plus-circle text-indigo-500"></i> Нэмэлт (Товчлуур & Мэдээллийн хайрцаг)</h2>
                            <i class="fas fa-chevron-down text-slate-400"></i>
                        </div>

                        <div id="extraOptions" class="hidden p-6 space-y-6 bg-slate-50/50 border-t border-slate-100">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Info Box -->
                                <div class="bg-white p-4 rounded border border-slate-200">
                                    <h3 class="text-sm font-bold text-slate-700 mb-3 border-b pb-2">Мэдээллийн хайрцаг (Info Box)</h3>
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 mb-1">Гарчиг</label>
                                            <input type="text" name="info_title" value="<?php echo isset($info_title) ? htmlspecialchars($info_title) : ''; ?>" class="w-full border border-slate-300 rounded px-3 py-1.5 text-sm" placeholder="Жнь: ТАНЫ ЗАХИАЛГА">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 mb-1">Текст</label>
                                            <input type="text" name="info_text" value="<?php echo isset($info_text) ? htmlspecialchars($info_text) : ''; ?>" class="w-full border border-slate-300 rounded px-3 py-1.5 text-sm" placeholder="Жнь: Захиалга #123 амжилттай...">
                                        </div>
                                    </div>
                                </div>

                                <!-- Button -->
                                <div class="bg-white p-4 rounded border border-slate-200">
                                    <h3 class="text-sm font-bold text-slate-700 mb-3 border-b pb-2">Товчлуур (Button)</h3>
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 mb-1">Товчлуурын текст</label>
                                            <input type="text" name="btn_text" value="<?php echo isset($btn_text) ? htmlspecialchars($btn_text) : ''; ?>" class="w-full border border-slate-300 rounded px-3 py-1.5 text-sm" placeholder="Жнь: Энд дарж үзнэ үү">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 mb-1">Холбоос (Link)</label>
                                            <input type="url" name="btn_link" value="<?php echo isset($btn_link) ? htmlspecialchars($btn_link) : ''; ?>" class="w-full border border-slate-300 rounded px-3 py-1.5 text-sm" placeholder="https://filezone.mn/...">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 border-t border-slate-200 flex justify-end bg-gray-50">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg shadow-lg shadow-indigo-500/30 transition flex items-center gap-2">
                                <i class="fas fa-paper-plane"></i> Илгээх
                            </button>
                        </div>

                    </form>

                    <!-- Preview Hint -->
                    <div class="mt-6 text-center text-sm text-slate-400">
                        <p><i class="fas fa-info-circle mr-1"></i> Энэ форм нь <strong>brevo_admin.php</strong> доторх <code>getPremiumEmailTemplate</code> загварыг ашиглан илгээнэ.</p>
                    </div>

                </div>

            </main>
        </div>
    </div>
</body>
</html>