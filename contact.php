<?php
// contact.php - Updated to handle form submission internally
session_start();
require_once 'includes/db.php';
require_once 'api/brevo_email.php'; // Мэйл илгээх функцийг оруулж ирэх

// Админ мэйл (хүлээн авах)
$adminEmail = 'delgerzaya95@gmail.com'; 

// Форм илгээх логик
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Өгөгдөл авах & цэвэрлэх
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // 2. Баталгаажуулалт
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        header("Location: contact.php?status=error&msg=missing_fields");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: contact.php?status=error&msg=invalid_email");
        exit;
    }

    // 3. Мэйл илгээх (Brevo API)
    try {
        if (function_exists('sendContactFormEmail')) {
            $response = sendContactFormEmail($adminEmail, $name, $email, $subject, $message);
            
            // Brevo API хариуг шалгах (JSON decode хийж id ирсэн эсэхийг үзэж болно)
            $resData = json_decode($response, true);
            
            if (isset($resData['messageId']) || isset($resData['id'])) {
                header("Location: contact.php?status=success");
            } else {
                // API-аас алдаа ирвэл log руу бичээд хэрэглэгчид амжилттай гэж харуулах (Spam-аас сэргийлэх)
                error_log("Brevo Error: " . $response);
                header("Location: contact.php?status=success"); 
            }
        } else {
            error_log("Error: sendContactFormEmail function not found.");
            header("Location: contact.php?status=error&msg=system_error");
        }
    } catch (Exception $e) {
        error_log("Contact Form Exception: " . $e->getMessage());
        header("Location: contact.php?status=error&msg=system_error");
    }
    exit;
}

$pageTitle = "Холбоо барих - Filezone.mn";
include 'includes/header.php';

// Check for status messages
$status = $_GET['status'] ?? '';
$msg_type = '';
$msg_text = '';

if ($status == 'success') {
    $msg_type = 'success';
    $msg_text = 'Таны зурвас амжилттай илгээгдлээ. Бид удахгүй хариу өгөх болно.';
} elseif ($status == 'error') {
    $msg_type = 'error';
    $err_code = $_GET['msg'] ?? '';
    if ($err_code == 'missing_fields') $msg_text = 'Бүх талбарыг бөглөнө үү.';
    elseif ($err_code == 'invalid_email') $msg_text = 'Имэйл хаяг буруу байна.';
    else $msg_text = 'Системийн алдаа гарлаа. Дараа дахин оролдоно уу.';
}
?>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <!-- Sidebar Navigation -->
    <aside class="hidden lg:block w-64 flex-shrink-0 py-6 pr-6 h-[calc(100vh-64px)] sticky top-16 overflow-y-auto no-scrollbar">
        <!-- Mini Upload CTA -->
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 text-white shadow-xl">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                    <i class="fas fa-wallet text-yellow-400"></i>
                </div>
                <span class="font-bold text-sm">Мөнгө олох уу?</span>
            </div>
            <p class="text-xs text-gray-300 mb-3 leading-relaxed">Хэрэггүй файлаа устгах биш, бусдад зарж орлого ол!</p>
            <a href="upload.php" class="block w-full text-center bg-white text-gray-900 py-1.5 rounded-lg text-xs font-bold hover:bg-gray-100 transition">
                Эхлэх &rarr;
            </a>
        </div>

        <!-- Menu Links -->
        <div class="space-y-1 mb-6">
            <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Үндсэн</h3>
            <a href="index.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-home w-5 text-center"></i> Нүүр хуудас
            </a>
            <a href="browse-files.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-folder-open w-5 text-center"></i> Файлууд
            </a>
        </div>

         <!-- Help Menu Active -->
         <div class="space-y-1 mb-8">
            <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Тусламж</h3>
            <a href="help.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-question-circle w-5 text-center"></i> Түгээмэл асуулт
            </a>
            <a href="contact.php" class="flex items-center gap-3 px-3 py-2 text-brand-600 bg-brand-50 rounded-lg font-medium group">
                <i class="fas fa-envelope w-5 text-center"></i> Холбоо барих
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
        
        <!-- Page Header -->
        <div class="mb-10 text-center lg:text-left">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Бидэнтэй холбогдох</h1>
            <p class="text-gray-500">Танд асуух зүйл байна уу? Бид танд туслахад үргэлж бэлэн.</p>
        </div>

        <!-- Alert Message -->
        <?php if ($msg_type): ?>
            <div class="mb-8 p-4 rounded-xl <?php echo $msg_type == 'success' ? 'bg-green-50 border border-green-100 text-green-800' : 'bg-red-50 border border-red-100 text-red-800'; ?> flex items-start gap-3">
                <i class="fas <?php echo $msg_type == 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> mt-0.5"></i>
                <div>
                    <h4 class="font-bold text-sm"><?php echo $msg_type == 'success' ? 'Амжилттай!' : 'Алдаа!'; ?></h4>
                    <p class="text-sm opacity-90"><?php echo htmlspecialchars($msg_text); ?></p>
                </div>
                <button onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
            
            <!-- Contact Info Column -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Info Card 1 -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-start gap-4">
                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-1">Имэйл хаяг</h3>
                        <p class="text-sm text-gray-500 mb-2">Ажлын өдрүүдэд 24 цагийн дотор хариу өгнө.</p>
                        <a href="mailto:info@filezone.mn" class="text-brand-600 font-medium hover:underline">info@filezone.mn</a>
                    </div>
                </div>

                <!-- Info Card 2 -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-start gap-4">
                    <div class="w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-1">Утас</h3>
                        <p class="text-sm text-gray-500 mb-2">Дав-Баасан, 09:00 - 18:00</p>
                        <a href="tel:+97699815313" class="text-brand-600 font-medium hover:underline">+976 9981-5313</a>
                    </div>
                </div>

                <!-- Social Media Card -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 p-6 rounded-2xl text-white shadow-lg">
                    <h3 class="font-bold mb-4">Биднийг дагаарай</h3>
                    <div class="flex gap-4">
                        <a href="https://www.facebook.com/filezone.mn" target="_blank" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-brand-600 transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-pink-600 transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-blue-400 transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Help -->
                <div class="bg-orange-50 border border-orange-100 p-6 rounded-2xl">
                    <h3 class="font-bold text-orange-800 mb-2 flex items-center gap-2">
                        <i class="fas fa-lightbulb"></i> Зөвлөмж
                    </h3>
                    <p class="text-sm text-orange-700 mb-4">Та асуулт асуухаасаа өмнө манай "Түгээмэл асуулт" хэсгийг шалгаж үзээрэй.</p>
                    <a href="help.php" class="text-sm font-bold text-orange-600 hover:text-orange-800 underline">Тусламж цэс рүү очих &rarr;</a>
                </div>

            </div>

            <!-- Contact Form Column -->
            <div class="lg:col-span-2">
                <div class="bg-white p-6 md:p-8 rounded-2xl border border-gray-200 shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Зурвас илгээх</h2>
                    
                    <form action="" method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Таны нэр</label>
                                <input type="text" id="name" name="name" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all" placeholder="Нэрээ оруулна уу">
                            </div>
                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Имэйл хаяг</label>
                                <input type="email" id="email" name="email" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all" placeholder="name@example.com">
                            </div>
                        </div>

                        <!-- Subject -->
                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-1.5">Сэдэв</label>
                            <select id="subject" name="subject" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all text-gray-600">
                                <option value="">Сэдвээ сонгоно уу...</option>
                                <option value="payment">Төлбөр тооцоо</option>
                                <option value="account">Бүртгэл & Нэвтрэх</option>
                                <option value="file">Файл оруулах/Татах</option>
                                <option value="service">Үйлчилгээтэй холбоотой</option>
                                <option value="other">Бусад</option>
                            </select>
                        </div>

                        <!-- Message -->
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-1.5">Зурвас</label>
                            <textarea id="message" name="message" rows="6" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all" placeholder="Асуух зүйлээ дэлгэрэнгүй бичнэ үү..."></textarea>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="w-full sm:w-auto bg-brand-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-brand-500/30 hover:bg-brand-700 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-paper-plane"></i> Илгээх
                        </button>
                    </form>
                </div>
            </div>

        </div>

    </main>
</div>

<?php include 'includes/footer.php'; ?>