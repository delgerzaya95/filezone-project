<?php
session_start();
require_once '../includes/db.php';

// Админ эрх шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --------------------------------------------------------------------------
// AJAX HANDLERS (Файл засах, устгах үйлдлүүд)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // JSON хариу бэлдэх функц
    function jsonResponse($success, $message) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }

    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_file') {
            $id = intval($_POST['file_id']);
            $title = trim($_POST['title']);
            $status = $_POST['status'];
            $reason = isset($_POST['reject_reason']) ? trim($_POST['reject_reason']) : null;

            if (empty($title)) {
                jsonResponse(false, 'Файлын нэр хоосон байж болохгүй.');
            }

            try {
                $stmt = $pdo->prepare("UPDATE files SET title = ?, status = ?, reject_reason = ? WHERE id = ?");
                $stmt->execute([$title, $status, $reason, $id]);
                jsonResponse(true, 'Файлын мэдээлэл шинэчлэгдлээ.');
            } catch (PDOException $e) {
                jsonResponse(false, 'Database error: ' . $e->getMessage());
            }
        }

        if ($_POST['action'] === 'delete_file') {
            $id = intval($_POST['file_id']);
            try {
                // Файлыг устгахын өмнө физик файлыг устгах логик энд байж болно
                // Одоогоор зөвхөн DB-ээс устгая
                $stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
                $stmt->execute([$id]);
                jsonResponse(true, 'Файл амжилттай устгагдлаа.');
            } catch (PDOException $e) {
                jsonResponse(false, 'Database error: ' . $e->getMessage());
            }
        }
    }
}

// --------------------------------------------------------------------------
// DATA FETCHING & FILTERING
// --------------------------------------------------------------------------

// Pagination Setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';

// Query Builder
$where_clauses = ["1=1"];
$params = [];

if ($search) {
    $where_clauses[] = "(f.title LIKE ? OR u.username LIKE ? OR f.id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = $search;
}

if ($status_filter) {
    $where_clauses[] = "f.status = ?";
    $params[] = $status_filter;
}

if ($type_filter) {
    $where_clauses[] = "f.file_type = ?";
    $params[] = $type_filter;
}

$where_sql = implode(" AND ", $where_clauses);

// Count Total Records
$count_sql = "SELECT COUNT(*) 
              FROM files f 
              LEFT JOIN users u ON f.user_id = u.id 
              WHERE $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch Records
$sql = "SELECT f.*, u.username, u.avatar_url 
        FROM files f 
        LEFT JOIN users u ON f.user_id = u.id 
        WHERE $where_sql 
        ORDER BY f.upload_date DESC 
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper: Format Size
function formatSize($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    return $bytes;
}

// Helper: Get Icon
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
                    <a href="../upload.php" target="_blank" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                        <i class="fas fa-cloud-upload-alt"></i> <span class="hidden sm:inline">Файл нэмэх</span>
                    </a>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                
                <!-- Filters Bar -->
                <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
                    <form method="GET" action="" class="flex flex-col md:flex-row gap-4 items-center justify-between">
                        <div class="flex flex-col md:flex-row gap-4 flex-1 w-full">
                            <!-- Search -->
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Файлын нэр, ID эсвэл хэрэглэгчээр хайх..." class="pl-10 pr-4 py-2 w-full border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            
                            <!-- Status Filter -->
                            <select name="status" onchange="this.form.submit()" class="border border-slate-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-slate-600">
                                <option value="">Бүх төлөв</option>
                                <option value="approved" <?php if($status_filter == 'approved') echo 'selected'; ?>>Зөвшөөрсөн</option>
                                <option value="pending" <?php if($status_filter == 'pending') echo 'selected'; ?>>Хүлээгдэж буй</option>
                                <option value="rejected" <?php if($status_filter == 'rejected') echo 'selected'; ?>>Татгалзсан</option>
                            </select>

                             <!-- Type Filter -->
                             <select name="type" onchange="this.form.submit()" class="border border-slate-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-slate-600">
                                <option value="">Бүх төрөл</option>
                                <option value="pdf" <?php if($type_filter == 'pdf') echo 'selected'; ?>>PDF</option>
                                <option value="docx" <?php if($type_filter == 'docx') echo 'selected'; ?>>Word</option>
                                <option value="zip" <?php if($type_filter == 'zip') echo 'selected'; ?>>ZIP/Archive</option>
                                <option value="exe" <?php if($type_filter == 'exe') echo 'selected'; ?>>Software (EXE)</option>
                                <option value="mp3" <?php if($type_filter == 'mp3') echo 'selected'; ?>>Audio (MP3)</option>
                            </select>
                        </div>

                        <!-- Reset Button -->
                        <div class="flex items-center gap-2">
                             <a href="files.php" class="p-2 text-slate-500 hover:text-slate-700 border border-slate-300 rounded-lg bg-white" title="Шинэчлэх">
                                <i class="fas fa-sync-alt"></i>
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
                                                    <p class="text-sm font-semibold text-slate-800 hover:text-indigo-600 cursor-pointer truncate max-w-xs" title="<?php echo htmlspecialchars($file['title']); ?>">
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
                                                    $avatar = !empty($file['avatar_url']) ? '../'.$file['avatar_url'] : 'https://ui-avatars.com/api/?name='.urlencode($file['username']).'&background=random&color=fff';
                                                ?>
                                                <img src="<?php echo $avatar; ?>" class="w-6 h-6 rounded-full bg-slate-200">
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
                                                <button onclick='openEditModal(<?php echo json_encode($file); ?>)' class="p-1.5 text-slate-400 hover:text-indigo-600 rounded hover:bg-indigo-50 transition" title="Засах">
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
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>" class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 text-slate-600">Өмнөх</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="px-3 py-1 text-sm border border-indigo-500 bg-indigo-50 text-indigo-600 rounded font-medium"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>" class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 text-slate-600"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>" class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 text-slate-600">Дараах</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        
        <div class="modal-container bg-white w-11/12 md:max-w-lg mx-auto rounded shadow-lg z-50 overflow-y-auto">
            <!-- Header -->
            <div class="flex justify-between items-center px-6 py-4 border-b">
                <p class="text-lg font-bold text-slate-800">Файл засах</p>
                <div class="modal-close cursor-pointer z-50 text-slate-500 hover:text-slate-800" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </div>
            </div>

            <!-- Body -->
            <div class="px-6 py-4">
                <form id="editFileForm">
                    <input type="hidden" id="editFileId" name="file_id">
                    <input type="hidden" name="action" value="update_file">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Файлын нэр</label>
                            <input type="text" id="editFileName" name="title" class="w-full border border-slate-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Төлөв</label>
                            <select id="editFileStatus" name="status" class="w-full border border-slate-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" onchange="toggleRejectReason(this.value)">
                                <option value="approved">Зөвшөөрөх (Approved)</option>
                                <option value="pending">Хүлээгдэж буй (Pending)</option>
                                <option value="rejected">Татгалзах (Rejected)</option>
                            </select>
                        </div>

                        <div id="rejectReasonGroup" class="hidden">
                            <label class="block text-sm font-medium text-red-700 mb-1">Татгалзсан шалтгаан</label>
                            <textarea id="editRejectReason" name="reject_reason" rows="3" class="w-full border border-red-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 text-sm" placeholder="Яагаад татгалзсан бэ?"></textarea>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-2 px-6 py-4 border-t bg-slate-50">
                <button onclick="closeEditModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition text-sm font-medium">Болих</button>
                <button onclick="saveFileChanges()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium">Хадгалах</button>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('editModal');
        const overlay = document.querySelector('.modal-overlay');

        function openEditModal(file) {
            document.getElementById('editFileId').value = file.id;
            document.getElementById('editFileName').value = file.title;
            document.getElementById('editFileStatus').value = file.status;
            document.getElementById('editRejectReason').value = file.reject_reason || '';
            
            toggleRejectReason(file.status);

            modal.classList.remove('opacity-0', 'pointer-events-none');
            document.body.classList.add('modal-active');
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
            if(confirm('Та энэ файлыг устгахдаа итгэлтэй байна уу?')) {
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