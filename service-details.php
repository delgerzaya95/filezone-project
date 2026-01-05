<?php
// 1. АЛДАА ИЛРҮҮЛЭХ КОД (Development mode)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'includes/db.php';

// Brevo API файлыг дуудах
require_once 'api/brevo_email.php';

// Кирилл үсэгтэй ажиллахад тохиргоо
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding("UTF-8");
}

// --------------------------------------------------------------------------
// 2. AJAX HANDLERS (REPORT ONLY - SAVE MOVED TO API)
// --------------------------------------------------------------------------

// --- A. REPORT SERVICE (Зөрчил мэдээлэх) ---
if (isset($_POST['action']) && $_POST['action'] == 'report_service') {
    ob_clean(); // Buffer цэвэрлэх
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Та зөрчил мэдээлэхийн тулд эхлээд нэвтэрнэ үү.']);
        exit;
    }

    $svc_id_report = intval($_POST['service_id']);
    $reason = trim($_POST['reason']);
    $description = trim($_POST['description']);
    $reporter_id = $_SESSION['user_id'];

    if (empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Зөрчлийн төрлийг сонгоно уу.']);
        exit;
    }

    try {
        // Давхар мэдээлсэн эсэхийг шалгах
        $check = $pdo->prepare("SELECT id FROM service_reports WHERE service_id = ? AND reporter_id = ? AND status = 'pending'");
        $check->execute([$svc_id_report, $reporter_id]);
        
        if ($check->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Та энэ үйлчилгээг аль хэдийн мэдээлсэн байна.']);
            exit;
        }

        // Хадгалах
        $stmt_report = $pdo->prepare("INSERT INTO service_reports (service_id, reporter_id, reason, description) VALUES (?, ?, ?, ?)");
        $stmt_report->execute([$svc_id_report, $reporter_id, $reason, $description]);

        // Mэйл илгээх (Optional)
        if ($stmt_report->rowCount() > 0) {
            $stmt_info = $pdo->prepare("SELECT s.title, u.username FROM services s JOIN users u ON u.id = ? WHERE s.id = ?");
            $stmt_info->execute([$reporter_id, $svc_id_report]);
            $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

            if ($info && function_exists('sendBrevoEmail')) {
                $adminEmail = "info@filezone.mn"; 
                sendBrevoEmail(
                    $adminEmail,
                    $info['title'],
                    $info['username'],
                    $reason,
                    $description,
                    $svc_id_report
                );
            }
        }

        echo json_encode(['success' => true, 'message' => 'Таны хүсэлтийг хүлээн авлаа. Бид шалгаад арга хэмжээ авах болно.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Системийн алдаа: ' . $e->getMessage()]);
    }
    exit;
}

// --------------------------------------------------------------------------
// 3. ҮНДСЭН ХУУДАСНЫ ЛОГИК
// --------------------------------------------------------------------------

// ID шалгах
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: services.php");
    exit;
}

$service_id = intval($_GET['id']);

// ТУСЛАХ ФУНКЦУУД
function deliveryUnitToMn($unit, $time) {
    switch ($unit) {
        case 'hour': return $time . ' цаг';
        case 'day': return $time . ' хоног';
        case 'week': return $time . ' долоо хоног';
        case 'month': return $time . ' сар';
        default: return $time . ' ' . $unit;
    }
}

function getRandomColorClass($str) {
    $colors = [
        ['bg' => 'bg-red-100', 'text' => 'text-red-600'],
        ['bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
        ['bg' => 'bg-green-100', 'text' => 'text-green-600'],
        ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600'],
        ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600'],
        ['bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
        ['bg' => 'bg-pink-100', 'text' => 'text-pink-600'],
    ];
    $str = (string)$str;
    $index = strlen($str) % count($colors);
    return $colors[$index];
}

function getInitials($name) {
    $name = (string)$name;
    if (function_exists('mb_substr')) {
        return mb_strtoupper(mb_substr($name, 0, 2));
    }
    preg_match_all('/./u', $name, $matches);
    return strtoupper(implode('', array_slice($matches[0] ?? [], 0, 2)));
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Дөнгөж сая';
    if ($diff < 3600) return floor($diff / 60) . ' минутын өмнө';
    if ($diff < 86400) return floor($diff / 3600) . ' цагийн өмнө';
    if ($diff < 604800) return floor($diff / 86400) . ' өдрийн өмнө';
    return date('Y-m-d', $time);
}

// VIEW COUNT НЭМЭХ
if (!isset($_SESSION['viewed_service_' . $service_id])) {
    $pdo->prepare("UPDATE services SET view_count = view_count + 1 WHERE id = ?")->execute([$service_id]);
    $_SESSION['viewed_service_' . $service_id] = true;
}

// ӨГӨГДӨЛ ТАТАХ
try {
    // A. Үндсэн мэдээлэл
    $stmt = $pdo->prepare("
        SELECT s.*, u.username, u.full_name, u.avatar_url, u.created_at as user_joined, sc.name as category_name,
        (SELECT COUNT(*) FROM service_reviews WHERE service_id = s.id) as review_count,
        (SELECT COUNT(*) FROM service_orders WHERE service_id = s.id AND status IN ('pending', 'active')) as queue_count,
        (SELECT COUNT(*) FROM service_orders WHERE seller_id = u.id AND status = 'completed') as total_completed_orders
        FROM services s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN service_categories sc ON s.category_id = sc.id
        WHERE s.id = ? AND s.status = 'active'
    ");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$service) {
        die("<div style='text-align:center; padding:50px;'><h1>Уучлаарай, энэ үйлчилгээ олдсонгүй.</h1><a href='services.php'>Буцах</a></div>");
    }

    // B. Gallery
    $stmt_img = $pdo->prepare("SELECT preview_url FROM service_previews WHERE service_id = ? ORDER BY order_index ASC");
    $stmt_img->execute([$service_id]);
    $gallery = $stmt_img->fetchAll(PDO::FETCH_COLUMN);

    if (empty($gallery) && !empty($service['cover_image'])) {
        $gallery[] = $service['cover_image'];
    } elseif (empty($gallery)) {
        $gallery[] = 'assets/images/service-placeholder.jpg';
    }

    // C. Reviews
    $stmt_rev = $pdo->prepare("
        SELECT r.*, u.username, u.full_name, u.avatar_url 
        FROM service_reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.service_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt_rev->execute([$service_id]);
    $reviews = $stmt_rev->fetchAll(PDO::FETCH_ASSOC);

    // D. FAQ
    $stmt_faq = $pdo->prepare("SELECT * FROM service_faqs WHERE service_id = ?");
    $stmt_faq->execute([$service_id]);
    $faqs = $stmt_faq->fetchAll(PDO::FETCH_ASSOC);

    // E. Related
    $stmt_rel = $pdo->prepare("
        SELECT s.*, u.username, u.full_name, u.avatar_url 
        FROM services s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.category_id = ? AND s.id != ? AND s.status = 'active' 
        LIMIT 4
    ");
    $stmt_rel->execute([$service['category_id'], $service_id]);
    $related_services = $stmt_rel->fetchAll(PDO::FETCH_ASSOC);

    // F. Saved Check
    $is_saved = false;
    if (isset($_SESSION['user_id'])) {
        $check_save = $pdo->prepare("SELECT id FROM saved_services WHERE user_id = ? AND service_id = ?");
        $check_save->execute([$_SESSION['user_id'], $service_id]);
        if ($check_save->rowCount() > 0) {
            $is_saved = true;
        }
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$author_name = !empty($service['full_name']) ? $service['full_name'] : $service['username'];
$rating = number_format($service['rating_avg'], 1);
$delivery_time = deliveryUnitToMn($service['delivery_unit'], $service['delivery_time']);
$price = number_format($service['price_min']) . '₮';

$hasAuthorAvatar = !empty($service['avatar_url']);
$authorInitials = getInitials($author_name);
$authorColor = getRandomColorClass($author_name);

$page_title = htmlspecialchars($service['title']) . " - Filezone.mn";

// Header
include 'includes/header.php';
?>

<div class="flex flex-1 max-w-7xl mx-auto w-full pt-6">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 px-4 lg:px-8 min-w-0 pb-12">
        <!-- Breadcrumb -->
        <nav class="flex mb-6 text-xs text-gray-500 overflow-x-auto no-scrollbar whitespace-nowrap">
            <a href="index.php" class="hover:text-brand-600 transition-colors">Нүүр</a>
            <span class="mx-2 text-gray-300">/</span>
            <a href="services.php" class="hover:text-brand-600 transition-colors">Үйлчилгээ</a>
            <span class="mx-2 text-gray-300">/</span>
            <span class="font-medium text-gray-900"><?php echo htmlspecialchars($service['category_name']); ?></span>
        </nav>

        <!-- Mobile Title -->
        <div class="lg:hidden mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-3 leading-snug"><?php echo htmlspecialchars($service['title']); ?></h1>
            <div class="flex items-center gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <?php if($hasAuthorAvatar): ?>
                        <img src="<?php echo htmlspecialchars($service['avatar_url']); ?>" class="w-6 h-6 rounded-full object-cover">
                    <?php else: ?>
                        <div class="w-6 h-6 rounded-full <?php echo $authorColor['bg'] . ' ' . $authorColor['text']; ?> flex items-center justify-center text-[10px] font-bold">
                            <?php echo $authorInitials; ?>
                        </div>
                    <?php endif; ?>
                    <span class="font-medium text-gray-900"><?php echo htmlspecialchars($author_name); ?></span>
                </div>
                <div class="flex items-center text-yellow-500">
                    <i class="fas fa-star mr-1"></i>
                    <span class="font-bold text-gray-900"><?php echo $rating; ?></span>
                    <span class="text-gray-400 ml-1">(<?php echo $service['review_count']; ?>)</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- LEFT COLUMN -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Gallery -->
                <div class="space-y-4">
                    <div class="aspect-video w-full bg-gray-100 rounded-2xl overflow-hidden border border-gray-200 group relative">
                        <img id="mainImage" src="<?php echo htmlspecialchars($gallery[0]); ?>" alt="Service Main Image" class="w-full h-full object-cover transition-opacity duration-300">
                    </div>
                    <?php if(count($gallery) > 1): ?>
                    <div class="flex gap-4 overflow-x-auto pb-2 no-scrollbar">
                        <?php foreach($gallery as $index => $img): ?>
                        <button onclick="changeImage('<?php echo htmlspecialchars($img); ?>', this)" 
                                class="w-20 h-20 flex-shrink-0 rounded-xl border-2 <?php echo $index === 0 ? 'border-brand-500' : 'border-gray-200 hover:border-gray-300'; ?> overflow-hidden transition-all thumbnail-btn">
                            <img src="<?php echo htmlspecialchars($img); ?>" class="w-full h-full object-cover">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <div class="bg-white rounded-2xl border border-gray-200 p-6 md:p-8 shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Үйлчилгээний тухай</h2>
                    <div class="prose max-w-none text-gray-600 text-sm">
                        <?php echo $service['description']; ?>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-6 mt-8 pt-8 border-t border-gray-100">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Ангилал</p>
                            <p class="font-medium text-gray-900 text-sm"><?php echo htmlspecialchars($service['category_name']); ?></p>
                        </div>
                        <?php if($service['requirements']): ?>
                        <div class="col-span-2">
                            <p class="text-xs text-gray-500 mb-1">Шаардлагатай мэдээлэл</p>
                            <p class="font-medium text-gray-900 text-sm truncate"><?php echo htmlspecialchars($service['requirements']); ?></p>
                        </div>
                        <?php endif; ?>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Засвар хийх</p>
                            <p class="font-medium text-gray-900 text-sm"><?php echo $service['revision_count']; ?> удаа үнэгүй</p>
                        </div>
                    </div>
                </div>

                <!-- Author Profile -->
                <div class="bg-white rounded-2xl border border-gray-200 p-6 md:p-8 shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Гүйцэтгэгч</h2>
                    <div class="flex flex-col sm:flex-row gap-6 items-start">
                        <div class="w-20 h-20 rounded-full flex-shrink-0 overflow-hidden border border-gray-200 flex items-center justify-center <?php echo $hasAuthorAvatar ? 'bg-gray-100' : ($authorColor['bg'] . ' ' . $authorColor['text']); ?>">
                            <?php if($hasAuthorAvatar): ?>
                                <img src="<?php echo htmlspecialchars($service['avatar_url']); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="text-2xl font-bold"><?php echo $authorInitials; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 w-full">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-lg font-bold text-gray-900 hover:text-brand-600 cursor-pointer"><?php echo htmlspecialchars($author_name); ?></h3>
                                <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2.5 py-1 rounded-full border border-green-200">Active</span>
                            </div>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?php echo !empty($service['bio']) ? htmlspecialchars($service['bio']) : 'Тайлбар оруулаагүй байна.'; ?></p>
                            <div class="flex gap-3">
                                <a href="user_profile.php?id=<?php echo $service['user_id']; ?>" class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">Профайл үзэх</a>
                                <button class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">Зурвас бичих</button>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-100">
                        <div class="text-center">
                            <p class="text-lg font-bold text-gray-900"><?php echo $rating; ?></p>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Үнэлгээ</p>
                        </div>
                        <div class="text-center border-l border-gray-200">
                            <p class="text-lg font-bold text-gray-900"><?php echo $service['total_completed_orders']; ?></p>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Захиалга</p>
                        </div>
                        <div class="text-center border-l border-gray-200">
                            <p class="text-lg font-bold text-gray-900"><?php echo date('Y', strtotime($service['user_joined'])); ?></p>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Он оноос</p>
                        </div>
                    </div>
                </div>

                <!-- Reviews -->
                <?php if(!empty($reviews)): ?>
                <div class="bg-white rounded-2xl border border-gray-200 p-6 md:p-8 shadow-sm">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-xl font-bold text-gray-900">Сэтгэгдэл (<?php echo $service['review_count']; ?>)</h2>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-star text-yellow-400"></i>
                            <span class="font-bold text-gray-900"><?php echo $rating; ?></span>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <?php foreach($reviews as $review): ?>
                            <?php 
                                $r_name = !empty($review['full_name']) ? $review['full_name'] : $review['username'];
                                $r_avatar = $review['avatar_url'];
                                $r_initials = getInitials($r_name);
                                $r_color = getRandomColorClass($r_name);
                            ?>
                            <div class="flex gap-4 items-start">
                                <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center overflow-hidden <?php echo $r_avatar ? 'bg-gray-200' : ($r_color['bg'] . ' ' . $r_color['text']); ?>">
                                    <?php if($r_avatar): ?>
                                        <img src="<?php echo htmlspecialchars($r_avatar); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="font-bold text-xs"><?php echo $r_initials; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <h4 class="font-bold text-gray-900 text-sm"><?php echo htmlspecialchars($r_name); ?></h4>
                                        <span class="text-xs text-gray-400"><?php echo timeAgo($review['created_at']); ?></span>
                                    </div>
                                    <div class="flex text-yellow-400 text-xs mb-2">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="fas fa-star<?php echo ($i <= $review['rating']) ? '' : '-o text-gray-300'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="text-gray-600 text-xs leading-relaxed"><?php echo htmlspecialchars($review['comment']); ?></p>
                                </div>
                            </div>
                            <hr class="border-gray-100 last:hidden">
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- FAQ -->
                <?php if(!empty($faqs)): ?>
                <div class="bg-white rounded-2xl border border-gray-200 p-6 md:p-8 shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Түгээмэл асуулт</h2>
                    <div class="space-y-4">
                        <?php foreach($faqs as $faq): ?>
                        <details class="group [&_summary::-webkit-details-marker]:hidden">
                            <summary class="flex items-center justify-between cursor-pointer rounded-lg bg-gray-50 p-4 text-gray-900 font-medium hover:bg-gray-100 transition-colors text-sm">
                                <?php echo htmlspecialchars($faq['question']); ?>
                                <span class="shrink-0 ml-1.5 p-1.5 text-gray-900 bg-white rounded-full group-open:-rotate-180 transition-transform"><i class="fas fa-chevron-down"></i></span>
                            </summary>
                            <div class="mt-4 px-4 leading-relaxed text-gray-600 text-sm">
                                <?php echo htmlspecialchars($faq['answer']); ?>
                            </div>
                        </details>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT COLUMN (Sticky) -->
            <div class="lg:col-span-1">
                <div class="sticky top-24 space-y-6">
                    <div class="hidden lg:block">
                        <h1 class="text-xl font-bold text-gray-900 leading-snug mb-2"><?php echo htmlspecialchars($service['title']); ?></h1>
                        <div class="flex items-center gap-2 text-sm mb-4">
                            <div class="flex text-yellow-500">
                                <i class="fas fa-star"></i>
                                <span class="ml-1 font-bold text-gray-900"><?php echo $rating; ?></span>
                            </div>
                            <span class="text-gray-300">|</span>
                            <span class="text-gray-500"><?php echo $service['view_count']; ?> үзсэн</span>
                        </div>
                    </div>

                    <!-- Price Card -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-lg shadow-gray-100/50">
                        <div class="flex items-end gap-2 mb-6">
                            <span class="text-3xl font-extrabold text-gray-900"><?php echo $price; ?></span>
                            <span class="text-gray-500 mb-1 font-medium text-xs">/ эхлэх үнэ</span>
                        </div>
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center justify-between text-sm text-gray-600">
                                <div class="flex items-center gap-2"><i class="far fa-clock text-brand-600 w-4"></i> Хугацаа</div>
                                <span class="font-semibold text-gray-900"><?php echo $delivery_time; ?></span>
                            </div>
                            <div class="flex items-center justify-between text-sm text-gray-600">
                                <div class="flex items-center gap-2"><i class="fas fa-sync-alt text-brand-600 w-4"></i> Засвар</div>
                                <span class="font-semibold text-gray-900"><?php echo $service['revision_count']; ?> удаа</span>
                            </div>
                            <div class="flex items-center justify-between text-sm text-gray-600">
                                <div class="flex items-center gap-2"><i class="fas fa-tasks text-brand-600 w-4"></i> Хүлээгдэж буй</div>
                                <span class="font-semibold text-gray-900"><?php echo $service['queue_count']; ?> захиалга</span>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <a href="service_payment.php?type=service&id=<?php echo $service['id']; ?>" class="w-full py-3.5 bg-brand-600 text-white rounded-xl font-bold hover:bg-brand-700 transition-all shadow-lg shadow-brand-500/30 flex items-center justify-center gap-2 transform active:scale-[0.98] btn-shine">
                                Захиалах <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                            <button id="saveBtn" onclick="toggleSave(<?php echo $service_id; ?>)" 
                                    class="w-full py-3.5 bg-white border border-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                                <i class="<?php echo $is_saved ? 'fas' : 'far'; ?> fa-heart <?php echo $is_saved ? 'text-red-500' : ''; ?>"></i> 
                                <span id="saveText"><?php echo $is_saved ? 'Хадгалсан' : 'Хадгалах'; ?></span>
                            </button>
                        </div>
                        <div class="mt-4 text-center">
                            <p class="text-xs text-gray-400">Төлбөрийг Filezone батлан даана.</p>
                        </div>
                    </div>

                    <!-- Safety & Report -->
                    <div class="bg-slate-50 rounded-2xl border border-gray-200 p-5">
                        <h3 class="font-bold text-gray-900 mb-3 text-sm">Баталгаат үйлчилгээ</h3>
                        <ul class="space-y-3 text-sm text-gray-600">
                            <li class="flex items-start gap-2.5"><i class="fas fa-check-circle text-green-500 mt-0.5"></i><span>Баталгаажсан хэрэглэгч</span></li>
                            <li class="flex items-start gap-2.5"><i class="fas fa-shield-alt text-brand-500 mt-0.5"></i><span>Төлбөрийн аюулгүй байдал</span></li>
                        </ul>
                    </div>
                    <button onclick="openReportModal()" class="w-full flex items-center justify-center gap-2 text-sm text-gray-400 hover:text-red-500 transition-colors py-2">
                        <i class="fas fa-flag"></i> Зөрчил мэдээлэх
                    </button>
                </div>
            </div>
        </div>

        <!-- RELATED SERVICES -->
        <?php if(!empty($related_services)): ?>
        <div class="mt-16 pt-10 border-t border-gray-200 mb-10">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-xl font-bold text-gray-900">Төстэй үйлчилгээнүүд</h2>
                <a href="services.php?cat=<?php echo $service['category_id']; ?>" class="text-brand-600 font-medium hover:text-brand-700 flex items-center gap-1 text-sm">
                    Бүгдийг үзэх <i class="fas fa-chevron-right text-xs"></i>
                </a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach($related_services as $rel): ?>
                    <?php 
                        $rel_img = !empty($rel['cover_image']) ? $rel['cover_image'] : 'assets/images/service-placeholder.jpg';
                        $rel_user = !empty($rel['full_name']) ? $rel['full_name'] : $rel['username'];
                        $rel_price = number_format($rel['price_min']) . '₮';
                        $rel_rating = number_format($rel['rating_avg'], 1);
                        
                        $hasRelAvatar = !empty($rel['avatar_url']);
                        $relInitials = getInitials($rel_user);
                        $relColor = getRandomColorClass($rel_user);
                    ?>
                    <div class="group bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col h-full">
                        <div class="relative aspect-[4/3] overflow-hidden flex-shrink-0">
                            <img src="<?php echo htmlspecialchars($rel_img); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </div>
                        <div class="p-4 flex-grow flex flex-col">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-5 h-5 rounded-full overflow-hidden flex-shrink-0 flex items-center justify-center <?php echo $hasRelAvatar ? 'bg-gray-100' : ($relColor['bg'] . ' ' . $relColor['text']); ?>">
                                    <?php if($hasRelAvatar): ?>
                                        <img src="<?php echo htmlspecialchars($rel['avatar_url']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="font-bold text-[8px]"><?php echo $relInitials; ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs font-medium text-gray-500 truncate"><?php echo htmlspecialchars($rel_user); ?></span>
                            </div>
                            <h3 class="font-bold text-gray-900 line-clamp-2 mb-3 group-hover:text-brand-600 transition-colors text-sm flex-grow">
                                <a href="service-details.php?id=<?php echo $rel['id']; ?>">
                                    <?php echo htmlspecialchars($rel['title']); ?>
                                </a>
                            </h3>
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100 mt-auto">
                                <div class="flex items-center gap-1 text-yellow-500 text-xs font-bold">
                                    <i class="fas fa-star"></i> <?php echo $rel_rating; ?>
                                </div>
                                <span class="font-bold text-gray-900 text-sm"><?php echo $rel_price; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

<!-- REPORT MODAL -->
<div id="reportModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeReportModal()"></div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                            <h3 class="text-base font-semibold leading-6 text-gray-900">Зөрчил мэдээлэх</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 mb-4">Та яагаад энэ үйлчилгээг мэдээлэх гэж байна вэ?</p>
                                <form id="reportForm">
                                    <input type="hidden" name="action" value="report_service">
                                    <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
                                    <div class="space-y-3 mb-4">
                                        <div class="flex items-center"><input id="r1" name="reason" type="radio" value="scam" class="h-4 w-4 text-red-600"><label for="r1" class="ml-3 block text-sm text-gray-900">Залилан / Хуурамч</label></div>
                                        <div class="flex items-center"><input id="r2" name="reason" type="radio" value="inappropriate" class="h-4 w-4 text-red-600"><label for="r2" class="ml-3 block text-sm text-gray-900">Зохимжгүй агуулга</label></div>
                                        <div class="flex items-center"><input id="r3" name="reason" type="radio" value="spam" class="h-4 w-4 text-red-600"><label for="r3" class="ml-3 block text-sm text-gray-900">Спам / Зар сурталчилгаа</label></div>
                                        <div class="flex items-center"><input id="r4" name="reason" type="radio" value="other" class="h-4 w-4 text-red-600"><label for="r4" class="ml-3 block text-sm text-gray-900">Бусад</label></div>
                                    </div>
                                    <div>
                                        <label for="desc" class="block text-sm font-medium text-gray-900 mb-1">Дэлгэрэнгүй тайлбар</label>
                                        <textarea id="desc" name="description" rows="3" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-red-600 sm:text-sm sm:leading-6"></textarea>
                                    </div>
                                </form>
                                <div id="reportMessage" class="mt-2 text-sm text-center hidden font-medium"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="button" onclick="submitReport()" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Илгээх</button>
                    <button type="button" onclick="closeReportModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Болих</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php' ?>

<script>
    // Gallery Image Switcher
    function changeImage(src, btn) {
        const mainImage = document.getElementById('mainImage');
        mainImage.style.opacity = '0.5';
        setTimeout(() => {
            mainImage.src = src;
            mainImage.style.opacity = '1';
        }, 150);
        document.querySelectorAll('.thumbnail-btn').forEach(el => {
            el.classList.remove('border-brand-500');
            el.classList.add('border-gray-200');
        });
        btn.classList.remove('border-gray-200');
        btn.classList.add('border-brand-500');
    }

    // Toggle Save Function (API)
    async function toggleSave(serviceId) {
        const btn = document.getElementById('saveBtn');
        const icon = btn.querySelector('i');
        const text = document.getElementById('saveText');
        const originalText = text.innerText;

        text.innerText = 'Уншиж байна...';
        btn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('action', 'toggle_save_service');
            formData.append('id', serviceId);

            const response = await fetch('api/toggle_save.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                if (result.status === 'saved') {
                    icon.classList.remove('far');
                    icon.classList.add('fas', 'text-red-500');
                    text.innerText = 'Хадгалсан';
                } else {
                    icon.classList.remove('fas', 'text-red-500');
                    icon.classList.add('far');
                    text.innerText = 'Хадгалах';
                }
            } else {
                if (result.message === 'Login required') {
                    // Optional: Redirect to login or show login modal
                    alert('Та нэвтэрсний дараа хадгалах боломжтой.');
                } else {
                    alert(result.message);
                    text.innerText = originalText;
                }
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Алдаа гарлаа.');
            text.innerText = originalText;
        } finally {
            btn.disabled = false;
        }
    }

    // Report Modal Logic
    function openReportModal() { document.getElementById('reportModal').classList.remove('hidden'); }
    function closeReportModal() { 
        document.getElementById('reportModal').classList.add('hidden'); 
        document.getElementById('reportMessage').classList.add('hidden');
        document.getElementById('reportForm').reset();
    }

    async function submitReport() {
        const form = document.getElementById('reportForm');
        const formData = new FormData(form);
        const msgBox = document.getElementById('reportMessage');
        
        try {
            const response = await fetch(window.location.href, { method: 'POST', body: formData });
            const result = await response.json();
            
            msgBox.textContent = result.message;
            msgBox.classList.remove('hidden', 'text-green-600', 'text-red-600');
            msgBox.classList.add(result.success ? 'text-green-600' : 'text-red-600');

            if (result.success) { setTimeout(closeReportModal, 2000); }
        } catch (error) {
            console.error('Error:', error);
            msgBox.textContent = 'Алдаа гарлаа. Та дахин оролдоно уу.';
            msgBox.classList.remove('hidden');
            msgBox.classList.add('text-red-600');
        }
    }
</script>