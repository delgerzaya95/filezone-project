<?php
session_start();
require_once '../includes/db.php';
// МЭЙЛ API ДУУДАХ
require_once 'api/brevo_admin.php'; 

// Админ эрх шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --------------------------------------------------------------------------
// AJAX HANDLERS
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    function jsonResponse($success, $message) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }

    if (isset($_POST['action'])) {
        // UPDATE FILE
        if ($_POST['action'] === 'update_file') {
            $id = intval($_POST['file_id']);
            $title = trim($_POST['title']);
            $status = $_POST['status'];
            $reason = isset($_POST['reject_reason']) ? trim($_POST['reject_reason']) : null;

            if (empty($title)) {
                jsonResponse(false, 'Файлын нэр хоосон байж болохгүй.');
            }

            try {
                // 1. ФАЙЛЫН ЭЗЭН БОЛОН ХУУЧИН ТӨЛӨВИЙГ АВАХ
                $stmt_owner = $pdo->prepare("
                    SELECT f.user_id, f.status as old_status, u.email, u.username 
                    FROM files f 
                    JOIN users u ON f.user_id = u.id 
                    WHERE f.id = ?
                ");
                $stmt_owner->execute([$id]);
                $file_info = $stmt_owner->fetch(PDO::FETCH_ASSOC);

                // 2. UPDATE QUERY
                $stmt = $pdo->prepare("UPDATE files SET title = ?, status = ?, reject_reason = ? WHERE id = ?");
                $stmt->execute([$title, $status, $reason, $id]);

                // 3. МЭЙЛ ИЛГЭЭХ ЛОГИК
                if ($file_info && $status !== $file_info['old_status']) {
                    if ($status === 'approved') {
                        notifyFileApproved($file_info['email'], $file_info['username'], $title, $id);
                    } elseif ($status === 'rejected') {
                        notifyFileRejected($file_info['email'], $file_info['username'], $title, $reason);
                    }
                }

                jsonResponse(true, 'Файлын мэдээлэл шинэчлэгдлээ. (Мэйл илгээгдсэн)');
            } catch (PDOException $e) {
                jsonResponse(false, 'Database error: ' . $e->getMessage());
            }
        }

        // DELETE FILE
        if ($_POST['action'] === 'delete_file') {
            $id = intval($_POST['file_id']);
            try {
                $stmt = $pdo->prepare("SELECT file_url FROM files WHERE id = ?");
                $stmt->execute([$id]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($file) {
                    $file_path = '../' . $file['file_url']; 
                    if (!empty($file['file_url']) && file_exists($file_path)) {
                        unlink($file_path);
                    }
                    
                    $file_dir = dirname($file_path);
                    $p_stmt = $pdo->prepare("SELECT preview_url FROM file_previews WHERE file_id = ?");
                    $p_stmt->execute([$id]);
                    $previews = $p_stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($previews as $row) {
                        $img_path = '../' . $row['preview_url'];
                        if (!empty($row['preview_url']) && file_exists($img_path)) {
                            unlink($img_path);
                        }
                    }

                    $previews_dir = $file_dir . '/previews';
                    if (is_dir($previews_dir)) @rmdir($previews_dir);
                    if (is_dir($file_dir)) @rmdir($file_dir);

                    $del_stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
                    $del_stmt->execute([$id]);
                    
                    jsonResponse(true, 'Файл бүрэн устгагдлаа.');
                } else {
                    jsonResponse(false, 'Файл олдсонгүй.');
                }
            } catch (PDOException $e) {
                jsonResponse(false, 'Database error: ' . $e->getMessage());
            }
        }
        
        // GET PREVIEWS
        if ($_POST['action'] === 'get_previews') {
            $id = intval($_POST['file_id']);
            try {
                $stmt = $pdo->prepare("SELECT preview_url FROM file_previews WHERE file_id = ? ORDER BY order_index ASC");
                $stmt->execute([$id]);
                $previews = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $formatted_previews = array_map(function($url) {
                    return '../' . $url;
                }, $previews);
                
                echo json_encode(['success' => true, 'previews' => $formatted_previews]);
                exit;
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
        }
    }
}

// --------------------------------------------------------------------------
// DATA FETCHING & FILTERING
// --------------------------------------------------------------------------
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Query Builder
$where_clauses = ["1=1"];
$params = [];

// 1. Search
if ($search) {
    $where_clauses[] = "(f.title LIKE ? OR u.username LIKE ? OR f.id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = $search;
}

// 2. Status
if ($status_filter) {
    $where_clauses[] = "f.status = ?";
    $params[] = $status_filter;
}

// 3. Type
if ($type_filter) {
    $where_clauses[] = "f.file_type = ?";
    $params[] = $type_filter;
}

// 4. Date Range
if ($date_from) {
    $where_clauses[] = "DATE(f.upload_date) >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $where_clauses[] = "DATE(f.upload_date) <= ?";
    $params[] = $date_to;
}

$where_sql = implode(" AND ", $where_clauses);

// Count Total Records
$count_sql = "SELECT COUNT(*) FROM files f LEFT JOIN users u ON f.user_id = u.id WHERE $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch Records
$sql = "SELECT f.*, u.username, u.avatar_url 
        FROM files f 
        LEFT JOIN users u ON f.user_id = u.id 
        WHERE $where_sql 
        ORDER BY FIELD(f.status, 'pending', 'approved', 'rejected'), f.upload_date DESC 
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helpers
function formatSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' bytes';
}

function getFileIcon($type) {
    switch ($type) {
        case 'pdf': return 'fa-file-pdf text-red-500';
        case 'doc': case 'docx': return 'fa-file-word text-blue-500';
        case 'xls': case 'xlsx': return 'fa-file-excel text-green-500';
        case 'ppt': case 'pptx': return 'fa-file-powerpoint text-orange-500';
        case 'zip': case 'rar': return 'fa-file-archive text-yellow-500';
        case 'jpg': case 'jpeg': case 'png': case 'gif': return 'fa-file-image text-purple-500';
        case 'mp3': case 'wav': return 'fa-file-audio text-pink-500';
        case 'mp4': case 'mov': case 'avi': return 'fa-file-video text-red-600';
        case 'exe': return 'fa-cogs text-gray-500';
        case 'txt': return 'fa-file-alt text-slate-400';
        default: return 'fa-file text-gray-400';
    }
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Файлын удирдлага - Filezone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
    <style>
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow-x: hidden; overflow-y: visible !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
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
                    <h1 class="text-xl font-bold text-slate-800">Файлын удирдлага</h1>
                </div>
                <div class="flex items-center gap-3">
                    <a href="file_upload.php" target="_blank" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                        <i class="fas fa-cloud-upload-alt"></i> <span class="hidden sm:inline">Файл нэмэх</span>
                    </a>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                
                <!-- Filters Bar (Expanded) -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-6">
                    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        
                        <!-- Search -->
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase">Хайлт</label>
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Файлын нэр, ID, хэрэглэгч..." class="pl-10 pr-4 py-2 w-full border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase">Төлөв</label>
                            <select name="status" class="w-full border border-slate-300 rounded-lg text-sm px-3 py-2 bg-white text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Бүх төлөв</option>
                                <option value="pending" <?php if($status_filter == 'pending') echo 'selected'; ?>>⏳ Хүлээгдэж буй</option>
                                <option value="approved" <?php if($status_filter == 'approved') echo 'selected'; ?>>✅ Зөвшөөрсөн</option>
                                <option value="rejected" <?php if($status_filter == 'rejected') echo 'selected'; ?>>❌ Татгалзсан</option>
                            </select>
                        </div>

                         <!-- Type Filter -->
                         <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase">Файлын төрөл</label>
                            <select name="type" class="w-full border border-slate-300 rounded-lg text-sm px-3 py-2 bg-white text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Бүх төрөл</option>
                                <option value="pdf" <?php if($type_filter == 'pdf') echo 'selected'; ?>>PDF</option>
                                <option value="docx" <?php if($type_filter == 'docx') echo 'selected'; ?>>Word</option>
                                <option value="xlsx" <?php if($type_filter == 'xlsx') echo 'selected'; ?>>Excel</option>
                                <option value="pptx" <?php if($type_filter == 'pptx') echo 'selected'; ?>>PowerPoint</option>
                                <option value="zip" <?php if($type_filter == 'zip') echo 'selected'; ?>>Archive (ZIP/RAR)</option>
                                <option value="exe" <?php if($type_filter == 'exe') echo 'selected'; ?>>Software (EXE)</option>
                                <option value="mp3" <?php if($type_filter == 'mp3') echo 'selected'; ?>>Audio (MP3)</option>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase">Эхлэх огноо</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-600">
                        </div>

                        <!-- Date To -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1 uppercase">Дуусах огноо</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-600">
                        </div>

                        <!-- Actions -->
                        <div class="col-span-1 md:col-span-2 flex items-end gap-2">
                             <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition shadow-sm font-medium">
                                <i class="fas fa-filter mr-1"></i> Шүүх
                            </button>
                             <a href="files.php" class="px-4 py-2 text-slate-600 bg-white border border-slate-300 rounded-lg text-sm hover:bg-slate-50 transition font-medium">
                                <i class="fas fa-undo mr-1"></i> Цэвэрлэх
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Files Table -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                    <th class="px-6 py-4 font-semibold">Файлын мэдээлэл</th>
                                    <th class="px-6 py-4 font-semibold">Үнэ</th>
                                    <th class="px-6 py-4 font-semibold">Эзэмшигч</th>
                                    <th class="px-6 py-4 font-semibold">Статистик</th>
                                    <th class="px-6 py-4 font-semibold">Төлөв</th>
                                    <th class="px-6 py-4 font-semibold text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (count($files) > 0): ?>
                                    <?php foreach ($files as $file): ?>
                                    <tr id="row-<?php echo $file['id']; ?>" class="hover:bg-slate-50 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-4">
                                                <div class="w-10 h-10 rounded-lg bg-slate-50 flex items-center justify-center text-lg shadow-sm border border-slate-100 shrink-0">
                                                    <i class="fas <?php echo getFileIcon($file['file_type']); ?>"></i>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-slate-800 hover:text-indigo-600 cursor-pointer truncate max-w-xs" onclick='openEditModal(<?php echo json_encode($file); ?>)' title="<?php echo htmlspecialchars($file['title']); ?>">
                                                        <?php echo htmlspecialchars($file['title']); ?>
                                                    </p>
                                                    <p class="text-xs text-slate-500 mt-0.5">
                                                        <span class="font-medium bg-slate-100 px-1 rounded"><?php echo strtoupper($file['file_type']); ?></span> • <?php echo formatSize($file['file_size']); ?> • <?php echo date('Y-m-d', strtotime($file['upload_date'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if($file['price'] > 0): ?>
                                                <span class="text-sm font-bold text-green-600"><?php echo number_format($file['price']); ?>₮</span>
                                            <?php else: ?>
                                                <span class="text-sm font-bold text-slate-500">Үнэгүй</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <?php 
                                                    $avatar_url = $file['avatar_url'];
                                                    $avatar_src = '';
                                                    if (!empty($avatar_url)) {
                                                        if (strpos($avatar_url, 'http') === 0) {
                                                            $avatar_src = $avatar_url;
                                                        } else {
                                                            $avatar_src = '../' . $avatar_url;
                                                        }
                                                    } else {
                                                        $avatar_src = 'https://ui-avatars.com/api/?name='.urlencode($file['username']).'&background=4F46E5&color=fff&bold=true';
                                                    }
                                                ?>
                                                <img src="<?php echo $avatar_src; ?>" class="w-6 h-6 rounded-full bg-slate-200 object-cover">
                                                <span class="text-sm text-slate-600 truncate max-w-[100px]" title="<?php echo htmlspecialchars($file['username']); ?>"><?php echo htmlspecialchars($file['username']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-xs text-slate-500 space-y-1">
                                                <div title="Таталт"><i class="fas fa-download text-slate-400 mr-1 w-4"></i> <?php echo number_format($file['download_count']); ?></div>
                                                <div title="Үзэлт"><i class="fas fa-eye text-slate-400 mr-1 w-4"></i> <?php echo number_format($file['view_count']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 file-status">
                                            <?php if($file['status'] == 'approved'): ?>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Зөвшөөрсөн
                                                </span>
                                            <?php elseif($file['status'] == 'pending'): ?>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-200 animate-pulse">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Хүлээгдэж буй
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200" title="<?php echo htmlspecialchars($file['reject_reason']); ?>">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Татгалзсан
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button onclick='openEditModal(<?php echo json_encode($file); ?>)' class="p-1.5 text-slate-400 hover:text-indigo-600 rounded hover:bg-indigo-50 transition" title="Засах / Дэлгэрэнгүй">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <?php if(!empty($file['file_url'])): ?>
                                                <a href="../<?php echo $file['file_url']; ?>" target="_blank" class="p-1.5 text-slate-400 hover:text-green-600 rounded hover:bg-green-50 transition" title="Татах">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <?php endif; ?>
                                                <button onclick="deleteFile(<?php echo $file['id']; ?>)" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50 transition" title="Устгах">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">Файл олдсонгүй.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="bg-white px-6 py-4 border-t border-slate-200 flex items-center justify-between">
                        <span class="text-sm text-slate-500">Нийт <?php echo $total_records; ?> файлаас <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total_records); ?> харагдаж байна</span>
                        <div class="flex items-center gap-1">
                            <?php 
                                // Build pagination links preserving all filters
                                $query_params = $_GET;
                                function buildPageLink($page, $params) {
                                    $params['page'] = $page;
                                    return '?' . http_build_query($params);
                                }
                            ?>

                            <?php if ($page > 1): ?>
                                <a href="<?php echo buildPageLink($page - 1, $query_params); ?>" class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 text-slate-600">Өмнөх</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="<?php echo buildPageLink($i, $query_params); ?>" class="px-3 py-1 text-sm border rounded <?php echo $i == $page ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="<?php echo buildPageLink($page + 1, $query_params); ?>" class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 text-slate-600">Дараах</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>
    
    <!-- Edit/View Modal -->
    <div id="editModal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        
        <div class="modal-container bg-white w-11/12 md:max-w-4xl mx-auto rounded-xl shadow-xl z-50 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="flex justify-between items-center px-6 py-4 border-b bg-gray-50">
                <p class="text-lg font-bold text-slate-800">Файлын дэлгэрэнгүй & Засах</p>
                <div class="modal-close cursor-pointer text-slate-500 hover:text-slate-800" onclick="closeEditModal()">
                    <i class="fas fa-times text-xl"></i>
                </div>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-6 bg-white">
                <div class="flex flex-col lg:flex-row gap-8">
                    <!-- Left Column: Details -->
                    <div class="flex-1 space-y-6">
                        <form id="editFileForm">
                            <input type="hidden" id="editFileId" name="file_id">
                            <input type="hidden" name="action" value="update_file">
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Файлын нэр</label>
                                    <input type="text" id="editFileName" name="title" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 text-sm bg-gray-50">
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Үнэ</label>
                                        <input type="text" id="editFilePrice" readonly class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Төрөл</label>
                                        <input type="text" id="editFileType" readonly class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed uppercase">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Тайлбар</label>
                                    <div id="editFileDesc" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-gray-50 h-32 overflow-y-auto"></div>
                                </div>
                                
                                <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-100">
                                    <h3 class="text-sm font-bold text-indigo-900 mb-3">Статус өөрчлөх</h3>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Төлөв</label>
                                        <select id="editFileStatus" name="status" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 text-sm bg-white" onchange="toggleRejectReason(this.value)">
                                            <option value="approved">✅ Зөвшөөрөх (Approved)</option>
                                            <option value="pending">⏳ Хүлээгдэж буй (Pending)</option>
                                            <option value="rejected">❌ Татгалзах (Rejected)</option>
                                        </select>
                                    </div>

                                    <div id="rejectReasonGroup" class="hidden mt-3">
                                        <label class="block text-sm font-medium text-red-700 mb-1">Татгалзсан шалтгаан <span class="text-red-500">*</span></label>
                                        <textarea id="editRejectReason" name="reject_reason" rows="3" class="w-full border border-red-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 text-sm bg-white" placeholder="Хэрэглэгчид харагдах шалтгааныг бичнэ үү..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Right Column: Previews -->
                    <div class="w-full lg:w-1/3">
                        <h3 class="text-sm font-bold text-slate-700 mb-3">Зургууд (Previews)</h3>
                        <div id="previewContainer" class="grid grid-cols-2 gap-2 max-h-[400px] overflow-y-auto pr-1">
                            <!-- JS will populate -->
                            <div class="col-span-2 text-center py-8 text-gray-400 bg-gray-50 rounded-lg border border-dashed border-gray-200 text-sm">
                                Уншиж байна...
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-slate-200">
                            <h3 class="text-sm font-bold text-slate-700 mb-2">Үндсэн файл</h3>
                            <a id="downloadLink" href="#" target="_blank" class="flex items-center justify-center w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition text-sm font-medium border border-slate-300">
                                <i class="fas fa-download mr-2"></i> Файлыг татах / Харах
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-between items-center px-6 py-4 border-t bg-gray-50">
                <button type="button" onclick="deleteFile(document.getElementById('editFileId').value)" class="text-red-600 hover:text-red-700 text-sm font-medium">
                    <i class="fas fa-trash-alt mr-1"></i> Энэ файлыг устгах
                </button>
                <div class="flex gap-2">
                    <button onclick="closeEditModal()" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm font-medium">Болих</button>
                    <button onclick="saveFileChanges()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium shadow-sm">Өөрчлөлтийг хадгалах</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('editModal');
        const overlay = document.querySelector('.modal-overlay');

        function openEditModal(file) {
            // Fill basic fields
            document.getElementById('editFileId').value = file.id;
            document.getElementById('editFileName').value = file.title;
            document.getElementById('editFilePrice').value = file.price > 0 ? file.price + '₮' : 'Үнэгүй';
            document.getElementById('editFileType').value = file.file_type;
            document.getElementById('editFileDesc').innerHTML = file.description; // HTML content support
            document.getElementById('editFileStatus').value = file.status;
            document.getElementById('editRejectReason').value = file.reject_reason || '';
            
            // File Download Link
            const dlLink = document.getElementById('downloadLink');
            if(file.file_url) {
                dlLink.href = '../' + file.file_url;
                dlLink.classList.remove('hidden');
            } else {
                dlLink.classList.add('hidden');
            }

            toggleRejectReason(file.status);
            
            // Load Previews via AJAX
            loadPreviews(file.id);

            modal.classList.remove('opacity-0', 'pointer-events-none');
            document.body.classList.add('modal-active');
        }

        function loadPreviews(fileId) {
            const container = document.getElementById('previewContainer');
            container.innerHTML = '<div class="col-span-2 text-center py-4"><i class="fas fa-spinner fa-spin text-indigo-500"></i></div>';
            
            const formData = new FormData();
            formData.append('action', 'get_previews');
            formData.append('file_id', fileId);

            fetch('files.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                container.innerHTML = '';
                if(data.success && data.previews.length > 0) {
                    data.previews.forEach(url => {
                        const div = document.createElement('div');
                        div.className = 'aspect-square rounded-lg overflow-hidden border border-slate-200 bg-gray-100 relative group cursor-pointer';
                        div.innerHTML = `<img src="${url}" class="w-full h-full object-cover" onclick="window.open('${url}', '_blank')">`;
                        container.appendChild(div);
                    });
                } else {
                    container.innerHTML = '<div class="col-span-2 text-center py-8 text-gray-400 bg-gray-50 rounded-lg border border-dashed border-gray-200 text-xs">Зураггүй</div>';
                }
            })
            .catch(() => {
                container.innerHTML = '<div class="col-span-2 text-center text-red-500 text-xs">Алдаа гарлаа</div>';
            });
        }

        function closeEditModal() {
            modal.classList.add('opacity-0', 'pointer-events-none');
            document.body.classList.remove('modal-active');
        }

        function toggleRejectReason(status) {
            const group = document.getElementById('rejectReasonGroup');
            if (status === 'rejected') {
                group.classList.remove('hidden');
            } else {
                group.classList.add('hidden');
            }
        }

        function saveFileChanges() {
            const formData = new FormData(document.getElementById('editFileForm'));

            fetch('files.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Амжилттай хадгалагдлаа');
                    location.reload();
                } else {
                    alert('Алдаа: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Сүлжээний алдаа гарлаа.');
            });
        }

        function deleteFile(id) {
            if(confirm('АНХААР: Энэ файлыг сервер болон мэдээллийн сангаас БҮР МӨСӨН устгах гэж байна! Итгэлтэй байна уу?')) {
                const formData = new FormData();
                formData.append('action', 'delete_file');
                formData.append('file_id', id);

                fetch('files.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message); // Амжилттай устгагдлаа
                        location.reload();
                    } else {
                        alert('Алдаа: ' + data.message);
                    }
                })
                .catch(error => alert('Сүлжээний алдаа гарлаа.'));
            }
        }

        // Close modal on overlay click
        overlay.addEventListener('click', closeEditModal);
    </script>
</body>
</html>