<?php
// user_profile.php
session_start();
require_once 'includes/db.php';

// Check DB Connection
if (!isset($conn)) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
}

// Get User ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$profile_id = intval($_GET['id']);
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$is_own_profile = ($profile_id === $current_user_id);

// --- 1. FETCH USER DETAILS & STATS (UPDATED LOGIC) ---
// Үнэлгээг зөвхөн ирсэн сэтгэгдлүүдээс тооцох (service_reviews table join)
// Шинэ үйлчилгээ үнэлгээг 0 болгож унагахгүй байх зорилготой.
$sql_user = "
    SELECT u.*, 
    (SELECT COUNT(*) FROM service_orders WHERE seller_id = u.id AND status = 'completed') as completed_orders,
    (SELECT AVG(r.rating) FROM service_reviews r JOIN services s ON r.service_id = s.id WHERE s.user_id = u.id) as avg_rating,
    (SELECT COUNT(r.id) FROM service_reviews r JOIN services s ON r.service_id = s.id WHERE s.user_id = u.id) as total_reviews_count,
    (SELECT COUNT(*) FROM services WHERE user_id = u.id AND status = 'active') as total_services,
    (SELECT COUNT(*) FROM files WHERE user_id = u.id AND status = 'approved') as total_files
    FROM users u 
    WHERE u.id = ? AND u.status = 'active'";

$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("Хэрэглэгч олдсонгүй.");
}

// --- AVATAR & COLOR LOGIC ---
$username = $user['username'];
$full_name = $user['full_name'] ? $user['full_name'] : $user['username'];
$user_bio = $user['bio'] ?? '';

// Generate deterministic color
$hash = md5($username);
$color_hex = substr($hash, 0, 6);
$r = hexdec(substr($color_hex,0,2));
$g = hexdec(substr($color_hex,2,2));
$b = hexdec(substr($color_hex,4,2));
$gradient_start = "rgba($r, $g, $b, 0.9)";
$gradient_end = "rgba(".max(0,$r-50).", ".max(0,$g-50).", ".max(0,$b-50).", 1)";

$avatar = "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=" . $color_hex . "&color=fff&size=256";
if (!empty($user['avatar_url'])) {
    if (strpos($user['avatar_url'], 'http') === 0) {
        $avatar = $user['avatar_url'];
    } elseif (file_exists($user['avatar_url'])) {
        $avatar = $user['avatar_url'];
    }
}

// --- 2. FETCH SKILLS ---
$skills_list = [];
$check_table = $conn->query("SHOW TABLES LIKE 'user_skills'");
if ($check_table && $check_table->num_rows > 0) {
    $sql_skills = "SELECT skill_name as name, skill_level as level FROM user_skills WHERE user_id = ?";
    $stmt_skills = $conn->prepare($sql_skills);
    $stmt_skills->bind_param("i", $profile_id);
    $stmt_skills->execute();
    $res_skills = $stmt_skills->get_result();
    while ($row = $res_skills->fetch_assoc()) {
        $skills_list[] = $row;
    }
} else {
    // Fallback logic
    if (!empty($user['skills'])) {
        $legacy_skills = explode(',', $user['skills']);
        foreach($legacy_skills as $ls) {
            $trimmed = trim($ls);
            if($trimmed) {
                $skills_list[] = ['name' => $trimmed, 'level' => 'Intermediate'];
            }
        }
    }
}

// --- 3. HELPERS ---
function getLevelBadge($level) {
    switch($level) {
        case 'pro_seller': return '<span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-xs font-bold border border-purple-200"><i class="fas fa-crown mr-1"></i> Pro Seller</span>';
        case 'level_2': return '<span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold border border-blue-200"><i class="fas fa-medal mr-1"></i> Түвшин 2</span>';
        case 'level_1': return '<span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200"><i class="fas fa-star mr-1"></i> Түвшин 1</span>';
        default: return '<span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold border border-gray-200">Шинэ</span>';
    }
}

function getSkillClass($level) {
    switch(strtolower($level)) {
        case 'expert': return 'bg-purple-50 text-purple-700 border-purple-100';
        case 'beginner': return 'bg-green-50 text-green-700 border-green-100';
        default: return 'bg-blue-50 text-blue-700 border-blue-100';
    }
}

function getInitials($name) {
    $name = (string)$name;
    if (function_exists('mb_substr')) {
        return mb_strtoupper(mb_substr($name, 0, 2));
    }
    return strtoupper(substr($name, 0, 2));
}

function getRandomColorClass($str) {
    $colors = [
        ['bg' => 'bg-red-100', 'text' => 'text-red-600'],
        ['bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
        ['bg' => 'bg-green-100', 'text' => 'text-green-600'],
        ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600'],
        ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600'],
        ['bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
    ];
    $index = strlen($str) % count($colors);
    return $colors[$index];
}

// --- 4. FETCH SERVICES & FILES ---
$sql_services = "SELECT * FROM services WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 6";
$stmt_svc = $conn->prepare($sql_services);
$stmt_svc->bind_param("i", $profile_id);
$stmt_svc->execute();
$services = $stmt_svc->get_result();

$sql_files = "SELECT * FROM files WHERE user_id = ? AND status = 'approved' ORDER BY upload_date DESC LIMIT 6";
$stmt_files = $conn->prepare($sql_files);
$stmt_files->bind_param("i", $profile_id);
$stmt_files->execute();
$files = $stmt_files->get_result();

// --- 5. FETCH REVIEWS (NEW) ---
$sql_reviews = "
    SELECT r.*, s.title as service_title, 
           reviewer.username as reviewer_username, reviewer.full_name as reviewer_full_name, reviewer.avatar_url as reviewer_avatar
    FROM service_reviews r 
    JOIN services s ON r.service_id = s.id 
    JOIN users reviewer ON r.user_id = reviewer.id
    WHERE s.user_id = ?
    ORDER BY r.created_at DESC
";
$stmt_rev = $conn->prepare($sql_reviews);
$stmt_rev->bind_param("i", $profile_id);
$stmt_rev->execute();
$reviews_result = $stmt_rev->get_result();

$pageTitle = $full_name;
include 'includes/header.php'; 
?>

<!-- Custom Styles -->
<style>
    .profile-cover {
        background: linear-gradient(135deg, <?php echo $gradient_start; ?> 0%, <?php echo $gradient_end; ?> 100%);
        height: 200px;
    }
    .profile-avatar { margin-top: -75px; }
    .nav-tab.active { border-bottom: 2px solid #6366f1; color: #4f46e5; font-weight: 600; }
    .skill-pill { transition: all 0.2s; }
    .skill-pill:hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    
    /* Advanced Skills Styles */
    .skill-level-badge {
        font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; margin-left: 6px; font-weight: 600; text-transform: uppercase;
    }
    .level-beginner { background-color: #d1fae5; color: #065f46; }
    .level-intermediate { background-color: #dbeafe; color: #1e40af; }
    .level-expert { background-color: #fae8ff; color: #86198f; }
</style>

<div class="bg-gray-50 min-h-screen pb-12">
    
    <!-- Hero / Cover Section -->
    <div class="profile-cover w-full relative">
        <?php if($is_own_profile): ?>
            <!-- Self-View Notification Banner -->
            <div class="absolute top-4 left-0 right-0 flex justify-center px-4">
                <div class="bg-black/50 backdrop-blur-md text-white text-sm px-6 py-2 rounded-full shadow-lg flex items-center gap-2 border border-white/20 animate-fade-in">
                    <i class="fas fa-eye"></i>
                    <span>Таны профайл бусад хэрэглэгчдэд яг ингэж харагдана.</span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- LEFT COLUMN: User Info Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 -mt-16 relative z-10 mb-6">
                    <div class="flex flex-col items-center text-center">
                        <!-- Avatar -->
                        <div class="profile-avatar mb-4 relative group">
                            <img src="<?php echo htmlspecialchars($avatar); ?>" 
                                 class="w-32 h-32 rounded-full border-4 border-white shadow-md object-cover bg-white" 
                                 alt="<?php echo htmlspecialchars($username); ?>">
                            
                            <?php if($is_own_profile): ?>
                                <a href="profile/settings.php" class="absolute bottom-0 right-0 w-8 h-8 bg-white border border-gray-200 rounded-full flex items-center justify-center text-gray-500 hover:text-blue-600 shadow-sm transition" title="Зураг солих">
                                    <i class="fas fa-camera text-xs"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Name & Badge -->
                        <h1 class="text-2xl font-bold text-gray-900 mb-1">
                            <?php echo htmlspecialchars($full_name); ?>
                        </h1>
                        <p class="text-gray-500 text-sm mb-3">@<?php echo htmlspecialchars($user['username']); ?></p>
                        
                        <div class="mb-4">
                            <?php echo getLevelBadge($user['level']); ?>
                        </div>

                        <!-- Action Buttons -->
                        <div class="w-full flex gap-3 mb-6">
                            <?php if($is_own_profile): ?>
                                <button onclick="openSkillsModal()" class="flex-1 bg-white border border-gray-300 text-gray-700 py-2 rounded-lg font-medium hover:bg-gray-50 transition shadow-sm">
                                    <i class="fas fa-pen mr-2 text-xs"></i> Засварлах
                                </button>
                                <a href="profile/settings.php" class="flex-none px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition" title="Тохиргоо">
                                    <i class="fas fa-cog"></i>
                                </a>
                            <?php else: ?>
                                <button onclick="openContactModal()" class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-medium hover:bg-blue-700 transition shadow-lg shadow-blue-500/30">
                                    <i class="fas fa-envelope mr-2"></i> Холбогдох
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="w-full border-t border-gray-100 pt-6 space-y-4">
                            <!-- Stats Row -->
                            <div class="grid grid-cols-2 gap-4 text-center">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase tracking-wide">Үнэлгээ</p>
                                    <div class="flex items-center justify-center gap-1 text-yellow-500 font-bold text-lg">
                                        <?php if($user['total_reviews_count'] > 0): ?>
                                            <i class="fas fa-star"></i>
                                            <span><?php echo number_format((float)$user['avg_rating'], 1); ?></span>
                                            <span class="text-xs text-gray-400 font-normal">(<?php echo $user['total_reviews_count']; ?>)</span>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-sm font-normal">Шинэ</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase tracking-wide">Гүйцэтгэсэн</p>
                                    <p class="font-bold text-gray-900 text-lg"><?php echo $user['completed_orders']; ?></p>
                                </div>
                            </div>

                            <hr class="border-dashed border-gray-200">

                            <!-- Details -->
                            <div class="text-left space-y-3 text-sm text-gray-600">
                                <div class="flex justify-between">
                                    <span><i class="fas fa-map-marker-alt w-5 text-gray-400"></i> Байршил</span>
                                    <span class="font-medium text-gray-900"><?php echo !empty($user['location']) ? htmlspecialchars($user['location']) : 'Тодорхойгүй'; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span><i class="fas fa-user w-5 text-gray-400"></i> Нэгдсэн</span>
                                    <span class="font-medium text-gray-900"><?php echo date('Y сарын d', strtotime($user['join_date'])); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span><i class="fas fa-clock w-5 text-gray-400"></i> Сүүлд</span>
                                    <span class="font-medium text-green-600">Онлайн</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skills & Bio Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6 group relative">
                    <?php if($is_own_profile): ?>
                        <button onclick="openSkillsModal()" class="absolute top-4 right-4 text-gray-400 hover:text-blue-600 p-2 rounded-full hover:bg-blue-50 transition opacity-0 group-hover:opacity-100">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    <?php endif; ?>

                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-tools text-gray-400 text-sm"></i> Ур чадвар
                    </h3>
                    
                    <?php if(!empty($skills_list)): ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach($skills_list as $skill): 
                                $lvl_class = 'level-' . strtolower($skill['level'] ?? 'intermediate');
                            ?>
                                <div class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-200 bg-white shadow-sm text-xs font-semibold text-gray-700">
                                    <span><?php echo htmlspecialchars($skill['name']); ?></span>
                                    <span class="skill-level-badge <?php echo $lvl_class; ?>"><?php echo htmlspecialchars($skill['level'] ?? 'Intermediate'); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-400 text-sm italic text-center py-2 bg-gray-50 rounded-lg">
                            <?php echo $is_own_profile ? 'Та ур чадвараа нэмж оруулаарай.' : 'Ур чадвар оруулаагүй байна.'; ?>
                        </p>
                    <?php endif; ?>

                    <hr class="my-6 border-gray-100">

                    <h3 class="font-bold text-gray-900 mb-3 flex items-center gap-2">
                        <i class="fas fa-align-left text-gray-400 text-sm"></i> Танилцуулга
                    </h3>
                    <div class="text-sm text-gray-600 leading-relaxed text-justify">
                        <?php echo !empty($user_bio) ? nl2br(htmlspecialchars($user_bio)) : '<p class="text-gray-400 italic text-center py-2">Танилцуулга оруулаагүй байна.</p>'; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Content -->
            <div class="lg:col-span-2 pt-6">
                
                <!-- Tabs -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 px-2">
                    <div class="flex space-x-6 overflow-x-auto no-scrollbar">
                        <button onclick="switchProfileTab('services')" id="tab-btn-services" class="nav-tab active px-4 py-4 text-sm font-medium whitespace-nowrap transition-colors">
                            Үйлчилгээ <span class="ml-1 bg-blue-100 text-blue-700 py-0.5 px-2 rounded-full text-xs"><?php echo $user['total_services']; ?></span>
                        </button>
                        <button onclick="switchProfileTab('files')" id="tab-btn-files" class="nav-tab px-4 py-4 text-sm font-medium whitespace-nowrap text-gray-500 hover:text-gray-700 transition-colors">
                            Файл <span class="ml-1 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs"><?php echo $user['total_files']; ?></span>
                        </button>
                        <button onclick="switchProfileTab('reviews')" id="tab-btn-reviews" class="nav-tab px-4 py-4 text-sm font-medium whitespace-nowrap text-gray-500 hover:text-gray-700 transition-colors">
                            Сэтгэгдэл <span class="ml-1 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs"><?php echo $user['total_reviews_count']; ?></span>
                        </button>
                    </div>
                </div>

                <!-- 1. SERVICES TAB -->
                <div id="content-services" class="tab-content block">
                    <?php if ($services->num_rows > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php while($svc = $services->fetch_assoc()): 
                                $cover = !empty($svc['cover_image']) ? $svc['cover_image'] : 'https://placehold.co/600x400?text=No+Image';
                            ?>
                            <a href="service-details.php?id=<?php echo $svc['id']; ?>" class="group bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300">
                                <div class="relative aspect-video overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($cover); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                    <div class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm px-2 py-1 rounded text-xs font-bold text-gray-800 shadow-sm">
                                        <?php echo number_format($svc['price_min']); ?>₮
                                    </div>
                                </div>
                                <div class="p-4">
                                    <h3 class="font-bold text-gray-900 text-sm line-clamp-2 mb-2 group-hover:text-blue-600 transition"><?php echo htmlspecialchars($svc['title']); ?></h3>
                                    <div class="flex items-center justify-between text-xs text-gray-500">
                                        <div class="flex items-center text-yellow-500">
                                            <i class="fas fa-star mr-1"></i>
                                            <span class="font-bold text-gray-700"><?php echo number_format($svc['rating_avg'], 1); ?></span>
                                        </div>
                                        <span><i class="far fa-clock mr-1"></i> <?php echo $svc['delivery_time']; ?> өдөр</span>
                                    </div>
                                </div>
                            </a>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                            <i class="fas fa-briefcase text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Идэвхтэй үйлчилгээ байхгүй байна.</p>
                            <?php if($is_own_profile): ?>
                                <a href="add_service.php" class="mt-4 inline-block text-blue-600 hover:underline text-sm">Үйлчилгээ нэмэх</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 2. FILES TAB -->
                <div id="content-files" class="tab-content hidden">
                    <?php if ($files->num_rows > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php while($file = $files->fetch_assoc()): ?>
                            <div class="bg-white p-4 rounded-xl border border-gray-200 flex gap-4 hover:shadow-md transition">
                                <div class="w-12 h-12 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0 text-2xl">
                                    <?php 
                                        $ext = $file['file_type'];
                                        $icon = 'fa-file text-gray-400';
                                        if(in_array($ext, ['pdf'])) $icon = 'fa-file-pdf text-red-500';
                                        if(in_array($ext, ['doc','docx'])) $icon = 'fa-file-word text-blue-500';
                                        if(in_array($ext, ['zip','rar'])) $icon = 'fa-file-archive text-yellow-500';
                                    ?>
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-gray-900 text-sm truncate mb-1">
                                        <a href="file-details.php?id=<?php echo $file['id']; ?>" class="hover:text-blue-600 transition">
                                            <?php echo htmlspecialchars($file['title']); ?>
                                        </a>
                                    </h4>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500"><?php echo number_format($file['file_size'] / 1024); ?> KB</span>
                                        <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                                            <?php echo ($file['price'] > 0) ? number_format($file['price']) . '₮' : 'Үнэгүй'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                            <i class="fas fa-folder-open text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Оруулсан файл байхгүй байна.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 3. REVIEWS TAB -->
                <div id="content-reviews" class="tab-content hidden">
                    <?php if ($reviews_result->num_rows > 0): ?>
                        <div class="space-y-4">
                            <?php while($review = $reviews_result->fetch_assoc()): 
                                $r_name = !empty($review['reviewer_full_name']) ? $review['reviewer_full_name'] : $review['reviewer_username'];
                                $r_initials = getInitials($r_name);
                                $r_color = getRandomColorClass($r_name);
                                $r_avatar = !empty($review['reviewer_avatar']) ? $review['reviewer_avatar'] : null;
                            ?>
                            <div class="bg-white p-5 rounded-xl border border-gray-200">
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 rounded-full flex-shrink-0 overflow-hidden flex items-center justify-center <?php echo $r_avatar ? 'bg-gray-100' : ($r_color['bg'] . ' ' . $r_color['text']); ?>">
                                        <?php if($r_avatar): ?>
                                            <img src="<?php echo htmlspecialchars($r_avatar); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <span class="font-bold text-xs"><?php echo $r_initials; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start mb-1">
                                            <div>
                                                <h4 class="font-bold text-gray-900 text-sm"><?php echo htmlspecialchars($r_name); ?></h4>
                                                <p class="text-xs text-gray-500">
                                                    Үйлчилгээ: <a href="service-details.php?id=<?php echo $review['service_id']; ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($review['service_title']); ?></a>
                                                </p>
                                            </div>
                                            <span class="text-xs text-gray-400"><?php echo date('Y-m-d', strtotime($review['created_at'])); ?></span>
                                        </div>
                                        
                                        <div class="flex text-yellow-400 text-xs mb-2">
                                            <?php for($i=1; $i<=5; $i++): ?>
                                                <i class="fas fa-star<?php echo ($i <= $review['rating']) ? '' : '-o text-gray-300'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        
                                        <p class="text-gray-600 text-sm leading-relaxed">
                                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-xl border border-dashed border-gray-300 p-12 text-center">
                            <i class="far fa-comment-dots text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Сэтгэгдэл хараахан байхгүй байна.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- CONTACT MODAL -->
<div id="contactModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="contactModalContent">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">Холбоо барих</h3>
                <button onclick="closeContactModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="flex items-center gap-4 mb-6 p-4 bg-gray-50 rounded-xl">
                <img src="<?php echo htmlspecialchars($avatar); ?>" class="w-12 h-12 rounded-full object-cover border border-gray-200">
                <div>
                    <p class="font-bold text-gray-900"><?php echo htmlspecialchars($full_name); ?></p>
                    <p class="text-xs text-gray-500">@<?php echo htmlspecialchars($user['username']); ?></p>
                </div>
            </div>

            <div class="space-y-4">
                <?php if(!empty($user['email'])): ?>
                <div class="flex items-center gap-3 text-sm">
                    <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <span class="text-gray-700"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($user['phone'])): ?>
                <div class="flex items-center gap-3 text-sm">
                    <div class="w-8 h-8 rounded-full bg-green-50 flex items-center justify-center text-green-600">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <span class="text-gray-700"><?php echo htmlspecialchars($user['phone']); ?></span>
                </div>
                <?php endif; ?>

                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-500 mb-2">Шууд зурвас үлдээх:</p>
                    <form onsubmit="alert('Зурвас илгээгдлээ (Демо)'); closeContactModal(); return false;">
                        <textarea class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none mb-3" rows="3" placeholder="Сайн байна уу, тантай хамтарч ажиллах саналтай байна..." required></textarea>
                        <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-bold hover:bg-blue-700 transition">
                            Илгээх
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SKILLS MODAL (Only included if own profile) -->
<?php if($is_own_profile): ?>
<div id="skillsModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm transition-all duration-300">
    <div class="bg-white w-11/12 md:max-w-2xl mx-auto rounded-2xl shadow-2xl z-50 overflow-hidden transform scale-95 transition-transform duration-300" id="skillsModalContent">
        
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <div>
                <h3 class="text-xl font-bold text-gray-900">Профайл засварлах</h3>
                <p class="text-xs text-gray-500">Ур чадвар болон танилцуулгаа шинэчлэх</p>
            </div>
            <button onclick="closeSkillsModal()" class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="p-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
            <form id="skillsForm" class="space-y-6">
                <input type="hidden" name="action" value="update_skills">
                
                <!-- 1. Skills Section -->
                <div>
                    <label class="block text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                        <i class="fas fa-award text-purple-500"></i> Ур чадвар (Skills)
                    </label>
                    
                    <div class="flex gap-2 mb-3">
                        <input type="text" id="skillInputName" placeholder="Жнь: Photoshop, Translation..." class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none">
                        <select id="skillInputLevel" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-purple-500 outline-none">
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate" selected>Intermediate</option>
                            <option value="Expert">Expert</option>
                        </select>
                        <button type="button" onclick="addSkill()" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 transition">Нэмэх</button>
                    </div>

                    <div id="skillsList" class="space-y-2 mb-2"></div>
                    <input type="hidden" name="skills_json" id="skillsHiddenJSON">
                </div>

                <hr class="border-gray-100">

                <!-- 2. Bio Section -->
                <div>
                    <label class="block text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                        <i class="fas fa-align-left text-blue-500"></i> Товч танилцуулга
                    </label>
                    <div class="relative">
                        <textarea name="bio" rows="6" class="w-full border border-gray-300 rounded-lg p-4 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none leading-relaxed" placeholder="Өөрийн туршлага, ажлын түүх, давуу талуудын талаар дэлгэрэнгүй бичнэ үү..."><?php echo htmlspecialchars($user_bio); ?></textarea>
                        <div class="absolute bottom-2 right-2 text-xs text-gray-400 bg-white px-1">Макс 1000 тэмдэгт</div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Footer Actions -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
            <button type="button" onclick="closeSkillsModal()" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition shadow-sm">Болих</button>
            <button type="button" onclick="submitSkills()" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition shadow-lg shadow-blue-500/30 flex items-center gap-2">
                <span>Хадгалах</span>
                <i class="fas fa-check"></i>
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// --- Tab Logic ---
function switchProfileTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.add('hidden');
        el.classList.remove('block');
    });
    
    document.querySelectorAll('.nav-tab').forEach(el => {
        el.classList.remove('active', 'border-b-2', 'border-blue-600', 'text-blue-600');
        el.classList.add('text-gray-500', 'hover:text-gray-700');
    });

    document.getElementById('content-' + tab).classList.remove('hidden');
    document.getElementById('content-' + tab).classList.add('block');

    const btn = document.getElementById('tab-btn-' + tab);
    btn.classList.add('active', 'border-b-2', 'border-blue-600', 'text-blue-600');
    btn.classList.remove('text-gray-500', 'hover:text-gray-700');
}

// --- Contact Modal ---
const contactModal = document.getElementById('contactModal');
const contactModalContent = document.getElementById('contactModalContent');

function openContactModal() {
    if(!contactModal) return;
    contactModal.classList.remove('hidden');
    setTimeout(() => {
        contactModalContent.classList.remove('scale-95', 'opacity-0');
        contactModalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeContactModal() {
    if(!contactModal) return;
    contactModalContent.classList.remove('scale-100', 'opacity-100');
    contactModalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        contactModal.classList.add('hidden');
    }, 300);
}

if(contactModal) {
    contactModal.addEventListener('click', function(e) {
        if (e.target === contactModal) closeContactModal();
    });
}

// --- Skills Logic (Only if own profile) ---
<?php if($is_own_profile): ?>
let skillsData = <?php echo json_encode($skills_list); ?>;
const skillsListEl = document.getElementById('skillsList');
const skillInputName = document.getElementById('skillInputName');
const skillInputLevel = document.getElementById('skillInputLevel');
const skillsHiddenJSON = document.getElementById('skillsHiddenJSON');

function renderSkills() {
    skillsListEl.innerHTML = '';
    if (skillsData.length === 0) {
        skillsListEl.innerHTML = '<div class="text-center py-4 text-gray-400 text-sm italic border-2 border-dashed border-gray-200 rounded-lg">Одоогоор ур чадвар нэмээгүй байна.</div>';
    } else {
        skillsData.forEach((skill, index) => {
            const levelClass = {
                'Beginner': 'bg-green-100 text-green-700 border-green-200',
                'Intermediate': 'bg-blue-100 text-blue-700 border-blue-200',
                'Expert': 'bg-purple-100 text-purple-700 border-purple-200'
            }[skill.level] || 'bg-gray-100 text-gray-700';

            const div = document.createElement('div');
            div.className = 'flex justify-between items-center p-3 bg-white border border-gray-200 rounded-lg shadow-sm group hover:border-blue-300 transition';
            div.innerHTML = `
                <div class="flex items-center gap-3">
                    <span class="font-medium text-gray-800 text-sm">${skill.name}</span>
                    <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded border ${levelClass}">${skill.level}</span>
                </div>
                <button type="button" onclick="removeSkill(${index})" class="text-gray-400 hover:text-red-500 p-1 rounded-full hover:bg-red-50 transition opacity-0 group-hover:opacity-100 focus:opacity-100">
                    <i class="fas fa-trash-alt"></i>
                </button>
            `;
            skillsListEl.appendChild(div);
        });
    }
    skillsHiddenJSON.value = JSON.stringify(skillsData);
}

function addSkill() {
    const name = skillInputName.value.trim();
    const level = skillInputLevel.value;
    if (name) {
        if (skillsData.some(s => s.name.toLowerCase() === name.toLowerCase())) {
            alert('Энэ ур чадвар бүртгэгдсэн байна.');
            return;
        }
        skillsData.push({ name: name, level: level });
        renderSkills();
        skillInputName.value = '';
        skillInputName.focus();
    }
}

window.removeSkill = function(index) {
    skillsData.splice(index, 1);
    renderSkills();
}

function openSkillsModal() {
    document.getElementById('skillsModal').classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('skillsModalContent').classList.remove('scale-95');
        document.getElementById('skillsModalContent').classList.add('scale-100');
    }, 10);
    renderSkills();
}

function closeSkillsModal() {
    document.getElementById('skillsModalContent').classList.remove('scale-100');
    document.getElementById('skillsModalContent').classList.add('scale-95');
    setTimeout(() => {
        document.getElementById('skillsModal').classList.add('hidden');
    }, 200);
}

function submitSkills() {
    const form = document.getElementById('skillsForm');
    const formData = new FormData(form);
    
    // API зам (profile folder-оос гадна байвал ../ ашиглахгүй)
    // user_profile.php нь root-д байгаа тул profile/update_skills.php эсвэл шууд update_skills.php байж болно.
    // Бид profile/update_skills.php гэж таамаглая. Хэрэв root дээр байгаа бол засаарай.
    const apiUrl = 'profile/update_skills.php'; 

    const btn = document.querySelector('button[onclick="submitSkills()"]');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Хадгалж байна...';

    fetch(apiUrl, { method: 'POST', body: formData })
    .then(response => response.text().then(text => {
        try { return JSON.parse(text); } 
        catch (e) { throw new Error("Серверээс буруу хариу ирлээ."); }
    }))
    .then(data => {
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> Амжилттай!';
            btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            btn.classList.add('bg-green-600', 'hover:bg-green-700');
            setTimeout(() => { location.reload(); }, 800);
        } else {
            alert('Алдаа: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Сүлжээний алдаа гарлаа.');
        btn.disabled = false;
        btn.innerHTML = originalContent;
    });
}

// Init
renderSkills();
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>