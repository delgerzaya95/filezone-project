<?php
// profile/my_files.php
session_start();

// 1. Include paths
include '../includes/db.php';

if (!isset($conn)) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- USER INFO FETCHING ---
$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

$username = $user_data['username'] ?? 'User';
$email = $user_data['email'] ?? '';
$active_files_count = 0; // Sidebar-д хэрэгтэй бол тоолж болно

// Avatar Logic
$db_avatar = $user_data['avatar_url'];
$avatar = "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=random&color=fff";
if (!empty($db_avatar)) {
    if (strpos($db_avatar, 'http') === 0) {
        $avatar = $db_avatar;
    } else {
        // DB path is usually 'uploads/...', so we need '../uploads/...'
        if (file_exists('../' . $db_avatar)) {
            $avatar = '../' . $db_avatar;
        }
    }
}

// --- DATA FETCHING FOR FILES ---

// 1. Uploaded by me (All files)
$sql_all_my_files = "SELECT f.*, 
    (SELECT preview_url FROM file_previews fp WHERE fp.file_id = f.id ORDER BY order_index ASC LIMIT 1) as preview_image 
    FROM files f WHERE f.user_id = ? ORDER BY f.upload_date DESC";
$stmt = $conn->prepare($sql_all_my_files);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$all_my_files_result = $stmt->get_result();

// 2. Purchased by me
$purchased_files_result = false;
$check_trans_table = $conn->query("SHOW TABLES LIKE 'transactions'");
if($check_trans_table && $check_trans_table->num_rows > 0) {
    $sql_purchased = "SELECT f.*, t.transaction_date as purchase_date, u.username as author_name,
        (SELECT preview_url FROM file_previews fp WHERE fp.file_id = f.id ORDER BY order_index ASC LIMIT 1) as preview_image 
        FROM transactions t 
        JOIN files f ON t.file_id = f.id 
        JOIN users u ON f.user_id = u.id
        WHERE t.user_id = ? AND t.status = 'success' 
        ORDER BY t.transaction_date DESC";
    $stmt = $conn->prepare($sql_purchased);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $purchased_files_result = $stmt->get_result();
}

// Helpers
function getFileIcon($type) {
    $icons = [
        'pdf' => 'fa-file-pdf text-red-500',
        'doc' => 'fa-file-word text-blue-500', 'docx' => 'fa-file-word text-blue-500',
        'xls' => 'fa-file-excel text-green-500', 'xlsx' => 'fa-file-excel text-green-500',
        'ppt' => 'fa-file-powerpoint text-orange-500', 'pptx' => 'fa-file-powerpoint text-orange-500',
        'zip' => 'fa-file-archive text-yellow-600', 'rar' => 'fa-file-archive text-yellow-600',
        'jpg' => 'fa-file-image text-purple-500', 'png' => 'fa-file-image text-purple-500',
        'mp4' => 'fa-file-video text-pink-500',
        'other' => 'fa-file text-gray-400'
    ];
    return $icons[strtolower($type)] ?? 'fa-file text-gray-400';
}

function getStatusBadge($status) {
    switch($status) {
        case 'approved': return '<span class="px-2 py-1 text-[10px] font-bold uppercase bg-green-100 text-green-700 rounded-full">Зөвшөөрсөн</span>';
        case 'rejected': return '<span class="px-2 py-1 text-[10px] font-bold uppercase bg-red-100 text-red-700 rounded-full">Татгалзсан</span>';
        default: return '<span class="px-2 py-1 text-[10px] font-bold uppercase bg-yellow-100 text-yellow-700 rounded-full">Хүлээгдэж буй</span>';
    }
}

$pageTitle = "Миний файлууд";
include 'header.php'; 
?>

<!-- Local Styles -->
<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .file-tab-btn.active { border-bottom: 2px solid #2563eb; color: #2563eb; font-weight: 600; }
    .file-tab-btn { color: #6b7280; font-weight: 500; transition: all 0.2s; }
    .file-tab-btn:hover { color: #1f2937; }
</style>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <!-- Sidebar (Replicated for consistency) -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Миний файлууд</h1>
            <a href="../upload.php" class="bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm"><i class="fas fa-cloud-upload-alt mr-1"></i> Файл оруулах</a>
        </div>

        <!-- Sub Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button onclick="switchFileTab('uploaded')" id="tab-btn-uploaded" class="file-tab-btn active whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Миний оруулсан <span class="ml-1 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs"><?php echo $all_my_files_result->num_rows; ?></span>
                </button>
                <button onclick="switchFileTab('purchased')" id="tab-btn-purchased" class="file-tab-btn whitespace-nowrap py-4 px-1 border-b-2 border-transparent font-medium text-sm">
                    Худалдаж авсан <span class="ml-1 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs"><?php echo $purchased_files_result ? $purchased_files_result->num_rows : 0; ?></span>
                </button>
            </nav>
        </div>

        <!-- 1. Uploaded Files List -->
        <div id="file-content-uploaded" class="file-tab-content block">
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <?php if ($all_my_files_result && $all_my_files_result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 w-12">#</th>
                                <th class="px-6 py-3">Файлын нэр</th>
                                <th class="px-6 py-3">Үнэ</th>
                                <th class="px-6 py-3">Статус</th>
                                <th class="px-6 py-3">Үзсэн / Татсан</th>
                                <th class="px-6 py-3">Огноо</th>
                                <th class="px-6 py-3 text-right">Үйлдэл</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php 
                            $cnt = 1;
                            while($file = $all_my_files_result->fetch_assoc()): 
                                $has_preview = !empty($file['preview_image']);
                                // Preview Image Logic
                                $preview_src = $has_preview ? '../' . htmlspecialchars($file['preview_image']) : '';
                                
                                // File Detail URL -> file-details.php (Updated)
                                $file_detail_url = '../file-details.php?id=' . $file['id'];
                            ?>
                            <tr class="bg-white hover:bg-gray-50 transition group">
                                <td class="px-6 py-4 text-gray-400 text-xs"><?php echo $cnt++; ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <!-- Image/Icon wrapped in Link -->
                                        <a href="<?php echo $file_detail_url; ?>" class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0 border border-gray-200 hover:opacity-80 transition">
                                            <?php if($has_preview): ?>
                                                <img src="<?php echo $preview_src; ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <i class="<?php echo getFileIcon($file['file_type']); ?> text-lg"></i>
                                            <?php endif; ?>
                                        </a>
                                        <div class="min-w-0">
                                            <!-- Title wrapped in Link -->
                                            <a href="<?php echo $file_detail_url; ?>" class="text-sm font-medium text-gray-900 truncate max-w-xs block hover:text-blue-600 transition">
                                                <?php echo htmlspecialchars($file['title']); ?>
                                            </a>
                                            <p class="text-xs text-gray-500 uppercase"><?php echo htmlspecialchars($file['file_type']); ?> • <?php echo number_format($file['file_size'] / 1024, 0); ?> KB</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    <?php echo ($file['price'] > 0) ? number_format($file['price']) . '₮' : '<span class="text-green-600">Үнэгүй</span>'; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo getStatusBadge($file['status']); ?>
                                    <?php if($file['status'] == 'rejected' && !empty($file['reject_reason'])): ?>
                                        <div class="text-[10px] text-red-500 mt-1 max-w-[150px] truncate" title="<?php echo htmlspecialchars($file['reject_reason']); ?>"><?php echo htmlspecialchars($file['reject_reason']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    <div class="flex items-center gap-3">
                                        <span title="Үзсэн"><i class="far fa-eye mr-1"></i> <?php echo $file['view_count']; ?></span>
                                        <span title="Татсан"><i class="fas fa-download mr-1"></i> <?php echo $file['download_count']; ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    <?php echo date('Y-m-d', strtotime($file['upload_date'])); ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Actions (in profile/ folder) -->
                                        <a href="<?php echo $file_detail_url; ?>" class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition" title="Харах"><i class="fas fa-external-link-alt"></i></a>
                                        <a href="edit_file.php?id=<?php echo $file['id']; ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Засах"><i class="fas fa-edit"></i></a>
                                        <a href="delete_file.php?id=<?php echo $file['id']; ?>" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Устгах" onclick="return confirm('Та энэ файлыг устгахдаа итгэлтэй байна уу?');"><i class="fas fa-trash-alt"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl"><i class="fas fa-folder-open"></i></div>
                        <h3 class="text-lg font-medium text-gray-900">Файл оруулаагүй байна</h3>
                        <a href="../upload.php" class="inline-flex items-center px-4 py-2 mt-4 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i> Анхны файлаа оруулах
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Purchased Files List -->
        <div id="file-content-purchased" class="file-tab-content hidden">
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <?php if ($purchased_files_result && $purchased_files_result->num_rows > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-0 divide-y md:divide-y-0 md:divide-x md:divide-gray-100">
                    <?php while($p_file = $purchased_files_result->fetch_assoc()): 
                        $has_preview = !empty($p_file['preview_image']);
                        $preview_src = $has_preview ? '../' . htmlspecialchars($p_file['preview_image']) : '';
                        
                        // File Detail URL -> file-details.php (Updated)
                        $p_file_url = '../file-details.php?id=' . $p_file['id'];
                    ?>
                    <div class="p-4 hover:bg-gray-50 transition flex flex-row gap-4 items-start">
                        <!-- Clickable Image -->
                        <a href="<?php echo $p_file_url; ?>" class="w-20 h-24 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden border border-gray-200 relative group block">
                            <?php if($has_preview): ?>
                                <img src="<?php echo $preview_src; ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-3xl text-gray-300">
                                    <i class="<?php echo getFileIcon($p_file['file_type']); ?>"></i>
                                </div>
                            <?php endif; ?>
                        </a>
                        <div class="flex-1 min-w-0 flex flex-col justify-between h-24">
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 line-clamp-2 leading-tight mb-1">
                                    <a href="<?php echo $p_file_url; ?>" class="hover:text-blue-600 transition"><?php echo htmlspecialchars($p_file['title']); ?></a>
                                </h4>
                                <p class="text-xs text-gray-500 mb-1">Зохиогч: <?php echo htmlspecialchars($p_file['author_name']); ?></p>
                                <p class="text-xs text-gray-400"><?php echo date('Y-m-d', strtotime($p_file['purchase_date'])); ?>-нд авсан</p>
                            </div>
                            <div class="flex items-center justify-between mt-auto">
                                <span class="text-xs font-bold text-gray-700 bg-gray-100 px-2 py-0.5 rounded"><?php echo strtoupper($p_file['file_type']); ?></span>
                                <a href="../download.php?id=<?php echo $p_file['id']; ?>" class="text-xs font-bold text-white bg-green-600 hover:bg-green-700 px-3 py-1.5 rounded-lg transition shadow-sm flex items-center gap-1">
                                    <i class="fas fa-download"></i> Татах
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl"><i class="fas fa-shopping-bag"></i></div>
                        <h3 class="text-lg font-medium text-gray-900">Худалдан авалт хийгээгүй байна</h3>
                        <a href="../index.php" class="inline-flex items-center px-4 py-2 mt-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-search mr-2"></i> Файл хайх
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
function switchFileTab(type) {
    document.querySelectorAll('.file-tab-content').forEach(el => {
        el.classList.add('hidden');
        el.classList.remove('block');
    });
    
    document.querySelectorAll('.file-tab-btn').forEach(el => {
        el.classList.remove('active', 'border-b-2', 'border-blue-600', 'text-blue-600');
        el.classList.add('border-transparent', 'text-gray-500');
    });

    document.getElementById('file-content-' + type).classList.remove('hidden');
    document.getElementById('file-content-' + type).classList.add('block');
    
    const activeBtn = document.getElementById('tab-btn-' + type);
    activeBtn.classList.add('active', 'border-b-2', 'border-blue-600', 'text-blue-600');
    activeBtn.classList.remove('border-transparent', 'text-gray-500');
}
</script>
<?php include '../includes/footer.php'; ?>