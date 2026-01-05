<?php
// profile/saved.php
session_start();
require_once '../includes/db.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --------------------------------------------------------------------------
// USER INFO & AVATAR LOGIC (Added for consistency)
// --------------------------------------------------------------------------
$stmt_u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_u->execute([$user_id]);
$user_data = $stmt_u->fetch(PDO::FETCH_ASSOC);
$username = $user_data['username'] ?? 'User';
$email = $user_data['email'] ?? '';

// Avatar Logic
$db_avatar = $user_data['avatar_url'];
$avatar = "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=random&color=fff";

if (!empty($db_avatar)) {
    if (strpos($db_avatar, 'http') === 0) {
        $avatar = $db_avatar;
    } else {
        if (file_exists('../' . $db_avatar)) {
            $avatar = '../' . $db_avatar;
        }
    }
}

// --------------------------------------------------------------------------
// AJAX HANDLERS (Remove Items)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    try {
        if ($_POST['action'] == 'remove_service') {
            $svc_id = intval($_POST['id']);
            $stmt = $pdo->prepare("DELETE FROM saved_services WHERE user_id = ? AND service_id = ?");
            $stmt->execute([$user_id, $svc_id]);
            $response['success'] = true;
        } 
        elseif ($_POST['action'] == 'remove_file') {
            $file_id = intval($_POST['id']);
            $stmt = $pdo->prepare("DELETE FROM saved_files WHERE user_id = ? AND file_id = ?");
            $stmt->execute([$user_id, $file_id]);
            $response['success'] = true;
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error';
    }

    echo json_encode($response);
    exit;
}

// --------------------------------------------------------------------------
// FETCH DATA
// --------------------------------------------------------------------------

// 1. Saved Services
$sql_services = "
    SELECT s.*, u.username, u.full_name, u.avatar_url, ss.created_at as saved_at
    FROM saved_services ss
    JOIN services s ON ss.service_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE ss.user_id = ? AND s.status = 'active'
    ORDER BY ss.created_at DESC
";
$stmt_svc = $pdo->prepare($sql_services);
$stmt_svc->execute([$user_id]);
$saved_services = $stmt_svc->fetchAll(PDO::FETCH_ASSOC);

// 2. Saved Files
$sql_files = "
    SELECT f.*, u.username, u.full_name, sf.created_at as saved_at
    FROM saved_files sf
    JOIN files f ON sf.file_id = f.id
    JOIN users u ON f.user_id = u.id
    WHERE sf.user_id = ? AND f.status = 'approved'
    ORDER BY sf.created_at DESC
";
$stmt_file = $pdo->prepare($sql_files);
$stmt_file->execute([$user_id]);
$saved_files = $stmt_file->fetchAll(PDO::FETCH_ASSOC);

// Helpers
function formatPrice($price) {
    return number_format($price) . '₮';
}

$pageTitle = "Хадгалсан зүйлс";
$current_page = 'saved.php';
include 'header.php';
?>

<!-- Styles -->
<style>
    .nav-tab.active {
        border-bottom: 2px solid #6366f1;
        color: #4f46e5;
        font-weight: 600;
    }
    .fade-out {
        animation: fadeOut 0.3s forwards;
    }
    @keyframes fadeOut {
        to { opacity: 0; transform: scale(0.95); height: 0; margin: 0; padding: 0; }
    }
</style>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
        
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Хадгалсан зүйлс</h1>
            <p class="text-sm text-gray-500 mt-1">Таны сонирхож хадгалсан үйлчилгээ болон файлууд</p>
        </div>

        <!-- Tabs -->
        <div class="bg-white border border-gray-200 rounded-xl mb-6 px-2 shadow-sm">
            <div class="flex space-x-6">
                <button onclick="switchTab('services')" id="tab-btn-services" class="nav-tab active px-4 py-4 text-sm font-medium transition-colors flex items-center gap-2">
                    <i class="fas fa-briefcase"></i> Үйлчилгээ 
                    <span class="bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs" id="count-services"><?php echo count($saved_services); ?></span>
                </button>
                <button onclick="switchTab('files')" id="tab-btn-files" class="nav-tab px-4 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors flex items-center gap-2">
                    <i class="fas fa-folder"></i> Файл
                    <span class="bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs" id="count-files"><?php echo count($saved_files); ?></span>
                </button>
            </div>
        </div>

        <!-- 1. SERVICES CONTENT -->
        <div id="content-services" class="tab-content block space-y-4">
            <?php if (count($saved_services) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach($saved_services as $svc): 
                        $cover = !empty($svc['cover_image']) ? (strpos($svc['cover_image'], 'http') === 0 ? $svc['cover_image'] : '../' . $svc['cover_image']) : 'https://placehold.co/400x300?text=No+Image';
                        $user_name = !empty($svc['full_name']) ? $svc['full_name'] : $svc['username'];
                    ?>
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group relative" id="service-card-<?php echo $svc['id']; ?>">
                        <!-- Remove Button -->
                        <button onclick="removeService(<?php echo $svc['id']; ?>)" class="absolute top-2 right-2 z-10 w-8 h-8 bg-white/90 backdrop-blur rounded-full flex items-center justify-center text-red-500 hover:bg-red-50 transition shadow-sm" title="Хадгалахыг болих">
                            <i class="fas fa-heart"></i>
                        </button>

                        <a href="../service-details.php?id=<?php echo $svc['id']; ?>" class="block">
                            <div class="relative aspect-video overflow-hidden">
                                <img src="<?php echo htmlspecialchars($cover); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <div class="absolute bottom-2 right-2 bg-black/70 backdrop-blur-sm px-2 py-1 rounded text-xs font-bold text-white">
                                    <?php echo formatPrice($svc['price_min']); ?>
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs text-gray-500"><i class="far fa-user"></i> <?php echo htmlspecialchars($user_name); ?></span>
                                    <span class="text-xs text-yellow-500 font-bold"><i class="fas fa-star"></i> <?php echo $svc['rating_avg']; ?></span>
                                </div>
                                <h3 class="font-bold text-gray-900 text-sm line-clamp-2 mb-3 group-hover:text-blue-600 transition"><?php echo htmlspecialchars($svc['title']); ?></h3>
                                <div class="text-xs text-gray-400 flex justify-between items-center border-t border-gray-100 pt-2">
                                    <span><i class="far fa-clock"></i> <?php echo $svc['delivery_time'] . ' ' . $svc['delivery_unit']; ?></span>
                                    <span>Хадгалсан: <?php echo date('m/d', strtotime($svc['saved_at'])); ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-16 bg-white rounded-xl border border-dashed border-gray-300">
                    <div class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="far fa-heart"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Хадгалсан үйлчилгээ алга</h3>
                    <p class="text-gray-500 text-sm mt-1 mb-6">Та сонирхсон үйлчилгээгээ зүрхэн товч дарж энд хадгалах боломжтой.</p>
                    <a href="../services.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-search mr-2"></i> Үйлчилгээ хайх
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- 2. FILES CONTENT -->
        <div id="content-files" class="tab-content hidden space-y-4">
            <?php if (count($saved_files) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach($saved_files as $file): 
                        $user_name = !empty($file['full_name']) ? $file['full_name'] : $file['username'];
                        
                        // File Icon Logic
                        $icon = 'fa-file text-gray-400';
                        $ext = $file['file_type'];
                        if(in_array($ext, ['pdf'])) $icon = 'fa-file-pdf text-red-500';
                        if(in_array($ext, ['doc','docx'])) $icon = 'fa-file-word text-blue-500';
                        if(in_array($ext, ['xls','xlsx'])) $icon = 'fa-file-excel text-green-500';
                        if(in_array($ext, ['zip','rar'])) $icon = 'fa-file-archive text-yellow-500';
                    ?>
                    <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition group relative" id="file-card-<?php echo $file['id']; ?>">
                        <!-- Remove Button -->
                        <button onclick="removeFile(<?php echo $file['id']; ?>)" class="absolute top-2 right-2 z-10 w-7 h-7 bg-white rounded-full flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 transition border border-gray-100" title="Устгах">
                            <i class="fas fa-times text-xs"></i>
                        </button>

                        <a href="../file-details.php?id=<?php echo $file['id']; ?>" class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0 text-2xl border border-gray-100">
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            <div class="flex-1 min-w-0 pt-1">
                                <h4 class="font-bold text-gray-900 text-sm truncate mb-1 group-hover:text-blue-600 transition">
                                    <?php echo htmlspecialchars($file['title']); ?>
                                </h4>
                                <p class="text-xs text-gray-500 mb-2 truncate">By <?php echo htmlspecialchars($user_name); ?></p>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                                        <?php echo ($file['price'] > 0) ? formatPrice($file['price']) : 'Үнэгүй'; ?>
                                    </span>
                                    <span class="text-[10px] text-gray-400"><?php echo number_format($file['file_size'] / 1024); ?> KB</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-16 bg-white rounded-xl border border-dashed border-gray-300">
                    <div class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Хадгалсан файл алга</h3>
                    <p class="text-gray-500 text-sm mt-1 mb-6">Танд хэрэгтэй файлуудаа энд хадгалаад дараа нь ашиглаарай.</p>
                    <a href="../browse-files.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-search mr-2"></i> Файл хайх
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
// Tab Switching
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('block'));
    
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

// Remove Service
async function removeService(id) {
    if(!confirm('Та энэ үйлчилгээг хадгалсан жагсаалтаас хасахдаа итгэлтэй байна уу?')) return;

    const formData = new FormData();
    formData.append('action', 'remove_service');
    formData.append('id', id);

    try {
        const response = await fetch('saved.php', { method: 'POST', body: formData });
        const result = await response.json();
        
        if (result.success) {
            const card = document.getElementById('service-card-' + id);
            card.classList.add('fade-out');
            setTimeout(() => card.remove(), 300);
            
            // Update Count
            const countEl = document.getElementById('count-services');
            countEl.innerText = Math.max(0, parseInt(countEl.innerText) - 1);
        }
    } catch (e) {
        console.error(e);
        alert('Алдаа гарлаа.');
    }
}

// Remove File
async function removeFile(id) {
    if(!confirm('Та энэ файлыг хадгалсан жагсаалтаас хасахдаа итгэлтэй байна уу?')) return;

    const formData = new FormData();
    formData.append('action', 'remove_file');
    formData.append('id', id);

    try {
        const response = await fetch('saved.php', { method: 'POST', body: formData });
        const result = await response.json();
        
        if (result.success) {
            const card = document.getElementById('file-card-' + id);
            card.classList.add('fade-out');
            setTimeout(() => card.remove(), 300);

            // Update Count
            const countEl = document.getElementById('count-files');
            countEl.innerText = Math.max(0, parseInt(countEl.innerText) - 1);
        }
    } catch (e) {
        console.error(e);
        alert('Алдаа гарлаа.');
    }
}
</script>

<?php include '../includes/footer.php'; ?>