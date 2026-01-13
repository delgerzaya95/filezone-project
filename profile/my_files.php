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

// Avatar Logic (Same as my_services.php)
$db_avatar = $user_data['avatar_url'] ?? '';
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

// --- DATA FETCHING FOR FILES ---

// 1. Uploaded by me (All files)
// Join with categories to get category name if needed, here just basic info
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
    
    <!-- Sidebar -->
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
                                $preview_src = $has_preview ? '../' . htmlspecialchars($file['preview_image']) : '';
                                
                                // Modal Data JSON
                                $fileDetails = htmlspecialchars(json_encode([
                                    'title' => $file['title'],
                                    'price' => $file['price'],
                                    'status' => $file['status'],
                                    'reject_reason' => $file['reject_reason'],
                                    'preview_image' => $preview_src,
                                    'file_type' => $file['file_type'],
                                    'file_size' => $file['file_size'],
                                    'views' => $file['view_count'],
                                    'downloads' => $file['download_count'],
                                    'upload_date' => $file['upload_date']
                                ]), ENT_QUOTES, 'UTF-8');

                                // Link Logic: If pending/rejected -> OPEN MODAL. If approved -> GO TO DETAILS
                                $rowAction = ($file['status'] == 'approved') ? "window.location.href='../file-details.php?id={$file['id']}'" : "openFileModal($fileDetails, 'desc-{$file['id']}')";
                                $cursorClass = 'cursor-pointer';
                            ?>
                            <tr class="bg-white hover:bg-gray-50 transition group">
                                <td class="px-6 py-4 text-gray-400 text-xs"><?php echo $cnt++; ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3 <?php echo $cursorClass; ?>" onclick="<?php echo $rowAction; ?>">
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0 border border-gray-200 hover:opacity-80 transition">
                                            <?php if($has_preview): ?>
                                                <img src="<?php echo $preview_src; ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <i class="<?php echo getFileIcon($file['file_type']); ?> text-lg"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-gray-900 truncate max-w-xs block hover:text-blue-600 transition">
                                                <?php echo htmlspecialchars($file['title']); ?>
                                            </div>
                                            <p class="text-xs text-gray-500 uppercase"><?php echo htmlspecialchars($file['file_type']); ?> • <?php echo number_format($file['file_size'] / 1024, 0); ?> KB</p>
                                        </div>
                                    </div>
                                    <!-- Hidden Description -->
                                    <div id="desc-<?php echo $file['id']; ?>" class="hidden">
                                        <?php echo $file['description']; ?>
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
                                        <!-- View Button: Always opens modal for quick preview -->
                                        <button onclick="openFileModal(<?php echo $fileDetails; ?>, 'desc-<?php echo $file['id']; ?>')" class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition" title="Урьдчилан харах">
                                            <i class="far fa-eye"></i>
                                        </button>
                                        
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
                        $p_file_url = '../file-details.php?id=' . $p_file['id'];
                    ?>
                    <div class="p-4 hover:bg-gray-50 transition flex flex-row gap-4 items-start">
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

<!-- FILE PREVIEW MODAL -->
<div id="filePreviewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 backdrop-blur-sm p-4 transition-all duration-300">
    <div class="bg-white w-full max-w-2xl mx-auto rounded-2xl shadow-2xl z-50 overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col max-h-[90vh]" id="filePreviewContent">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <i class="far fa-file-alt text-blue-600"></i> Файлыг урьдчилан харах
            </h3>
            <button onclick="closeFileModal()" class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Preview Image/Icon -->
                <div class="w-full md:w-1/3 flex-shrink-0">
                    <div class="rounded-xl overflow-hidden bg-gray-100 border border-gray-200 aspect-[3/4] flex items-center justify-center relative group">
                        <img id="modalFileImage" src="" class="w-full h-full object-cover hidden">
                        <div id="modalFileIcon" class="text-6xl text-gray-300 hidden">
                            <i class="fas fa-file"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <div class="text-2xl font-bold text-green-600" id="modalFilePrice"></div>
                        <div id="modalFileStatus" class="mt-2"></div>
                    </div>
                </div>

                <!-- Details -->
                <div class="w-full md:w-2/3 space-y-4">
                    <div>
                        <h2 id="modalFileTitle" class="text-xl font-bold text-gray-900 leading-tight"></h2>
                        <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                            <span class="bg-gray-100 px-2 py-1 rounded text-gray-700 font-bold uppercase" id="modalFileType"></span>
                            <span id="modalFileSize"></span>
                            <span>•</span>
                            <span id="modalFileDate"></span>
                        </div>
                    </div>

                    <div class="prose prose-sm max-w-none text-gray-600 bg-gray-50 p-4 rounded-lg border border-gray-100" id="modalFileDesc">
                        <!-- Description content -->
                    </div>

                    <div class="flex gap-4 text-sm text-gray-500 border-t border-gray-100 pt-4">
                        <div class="flex items-center gap-1"><i class="far fa-eye"></i> <span id="modalFileViews"></span> үзсэн</div>
                        <div class="flex items-center gap-1"><i class="fas fa-download"></i> <span id="modalFileDownloads"></span> татсан</div>
                    </div>

                    <div id="modalFileRejection" class="hidden bg-red-50 border border-red-100 p-3 rounded-lg text-xs text-red-600">
                        <strong class="block mb-1">Татгалзсан шалтгаан:</strong>
                        <span id="modalFileRejectText"></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end">
            <button onclick="closeFileModal()" class="bg-gray-900 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-800 transition">Хаах</button>
        </div>
    </div>
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

// Modal Logic
function openFileModal(data, descId) {
    const descHtml = document.getElementById(descId).innerHTML;
    
    document.getElementById('modalFileTitle').textContent = data.title;
    document.getElementById('modalFileDesc').innerHTML = descHtml;
    
    // Price
    document.getElementById('modalFilePrice').textContent = (data.price > 0) ? new Intl.NumberFormat().format(data.price) + '₮' : 'Үнэгүй';
    
    // Meta
    document.getElementById('modalFileType').textContent = data.file_type;
    document.getElementById('modalFileSize').textContent = (data.file_size / 1024).toFixed(0) + ' KB';
    document.getElementById('modalFileDate').textContent = data.upload_date.split(' ')[0];
    document.getElementById('modalFileViews').textContent = data.views;
    document.getElementById('modalFileDownloads').textContent = data.downloads;

    // Image/Icon
    const imgEl = document.getElementById('modalFileImage');
    const iconEl = document.getElementById('modalFileIcon');
    
    if (data.preview_image) {
        imgEl.src = data.preview_image;
        imgEl.classList.remove('hidden');
        iconEl.classList.add('hidden');
    } else {
        imgEl.classList.add('hidden');
        iconEl.classList.remove('hidden');
        // Simple icon mapping based on type
        let iconClass = 'fa-file text-gray-400';
        if(['jpg','png','jpeg'].includes(data.file_type)) iconClass = 'fa-file-image text-purple-500';
        else if(['pdf'].includes(data.file_type)) iconClass = 'fa-file-pdf text-red-500';
        else if(['doc','docx'].includes(data.file_type)) iconClass = 'fa-file-word text-blue-500';
        
        iconEl.innerHTML = `<i class="fas ${iconClass}"></i>`;
    }

    // Status Badge
    const statusEl = document.getElementById('modalFileStatus');
    let statusHtml = '';
    if (data.status === 'approved') statusHtml = '<span class="px-2 py-1 text-xs font-bold uppercase bg-green-100 text-green-700 rounded-full">Зөвшөөрсөн</span>';
    else if (data.status === 'rejected') statusHtml = '<span class="px-2 py-1 text-xs font-bold uppercase bg-red-100 text-red-700 rounded-full">Татгалзсан</span>';
    else statusHtml = '<span class="px-2 py-1 text-xs font-bold uppercase bg-yellow-100 text-yellow-700 rounded-full">Хүлээгдэж буй</span>';
    statusEl.innerHTML = statusHtml;

    // Rejection Reason
    const rejectBox = document.getElementById('modalFileRejection');
    if (data.status === 'rejected' && data.reject_reason) {
        rejectBox.classList.remove('hidden');
        document.getElementById('modalFileRejectText').textContent = data.reject_reason;
    } else {
        rejectBox.classList.add('hidden');
    }

    // Show Modal
    const modal = document.getElementById('filePreviewModal');
    const content = document.getElementById('filePreviewContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95');
        content.classList.add('scale-100');
    }, 10);
}

function closeFileModal() {
    const modal = document.getElementById('filePreviewModal');
    const content = document.getElementById('filePreviewContent');
    content.classList.remove('scale-100');
    content.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}
</script>
<?php include '../includes/footer.php'; ?>