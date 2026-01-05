<?php
session_start();

// Database холболт (Замаа шалгаарай)
require_once '../includes/db.php';

// Админ эрх шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Хэрэв админ биш бол login руу буцаана
    header("Location: ../login.php"); 
    exit;
}

$message = '';
$error = '';

// --------------------------------------------------------------------------
// ACTION HANDLERS
// --------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. ТӨЛӨВ ӨӨРЧЛӨХ & ТАЙЛБАР БИЧИХ (Process Report)
    if (isset($_POST['update_report'])) {
        $report_id = intval($_POST['report_id']);
        $new_status = $_POST['status'];
        $admin_note = trim($_POST['admin_note']); // Админы хийсэн үйлдэл/тайлбар

        try {
            $sql = "UPDATE service_reports SET status = ?, admin_note = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$new_status, $admin_note, $report_id]);
            $message = "Зөрчлийн мэдээлэл шинэчлэгдлээ.";
        } catch (PDOException $e) {
            $error = "Алдаа гарлаа: " . $e->getMessage();
        }
    }

    // 2. УСТГАХ
    if (isset($_POST['delete_report'])) {
        $id = intval($_POST['id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM service_reports WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Зөрчлийн мэдээлэл устгагдлаа.";
        } catch (PDOException $e) {
            $error = "Устгахад алдаа гарлаа: " . $e->getMessage();
        }
    }
}

// --------------------------------------------------------------------------
// DATA FETCHING
// --------------------------------------------------------------------------

$status_filter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clauses = ["1=1"];
$params = [];

// Status Filter
if (!empty($status_filter)) {
    $where_clauses[] = "r.status = ?";
    $params[] = $status_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// Count Total
$count_sql = "SELECT COUNT(*) FROM service_reports r WHERE $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Fetch Reports
// Service table-ээс title, Users table-ээс username авна
$sql = "SELECT r.*, 
               s.title as service_title, s.id as service_id,
               u.username as reporter_name, u.email as reporter_email
        FROM service_reports r
        LEFT JOIN services s ON r.service_id = s.id
        LEFT JOIN users u ON r.reporter_id = u.id
        WHERE $where_sql 
        ORDER BY r.created_at DESC 
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// Statistics for Cards
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM service_reports")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM service_reports WHERE status = 'pending'")->fetchColumn(),
    'reviewed' => $pdo->query("SELECT COUNT(*) FROM service_reports WHERE status = 'reviewed'")->fetchColumn(),
    'resolved' => $pdo->query("SELECT COUNT(*) FROM service_reports WHERE status = 'resolved'")->fetchColumn(),
];

?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Зөрчил мэдээлэл - FileZone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
</head>
<body class="font-sans text-slate-800 antialiased bg-slate-50">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR (Include your sidebar here) -->
        <?php include 'sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- HEADER -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shadow-sm z-10">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden text-slate-500"><i class="fas fa-bars text-xl"></i></button>
                    <h1 class="text-xl font-bold text-slate-800">Зөрчил мэдээлэл (Reports)</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-slate-700">Админ</span>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                
                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $message; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Нийт зөрчил</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['total']); ?></p>
                        </div>
                        <div class="bg-red-50 text-red-600 p-3 rounded-full"><i class="fas fa-flag text-xl"></i></div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Хүлээгдэж буй</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['pending']); ?></p>
                        </div>
                        <div class="bg-yellow-50 text-yellow-600 p-3 rounded-full"><i class="fas fa-clock text-xl"></i></div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Шалгаж буй</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['reviewed']); ?></p>
                        </div>
                        <div class="bg-blue-50 text-blue-600 p-3 rounded-full"><i class="fas fa-eye text-xl"></i></div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Шийдвэрлэсэн</p>
                            <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['resolved']); ?></p>
                        </div>
                        <div class="bg-green-50 text-green-600 p-3 rounded-full"><i class="fas fa-check-circle text-xl"></i></div>
                    </div>
                </div>

                <!-- Filters & Table -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
                    
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <div class="flex flex-col md:flex-row gap-4 w-full md:w-auto">
                            <form method="GET" class="flex gap-2 w-full">
                                <select name="status" class="border border-slate-300 rounded-lg text-sm px-3 py-2 bg-white text-slate-700 focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Бүх төлөв</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="reviewed" <?php echo $status_filter == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                    <option value="resolved" <?php echo $status_filter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="dismissed" <?php echo $status_filter == 'dismissed' ? 'selected' : ''; ?>>Dismissed</option>
                                </select>
                                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">Шүүх</button>
                            </form>
                        </div>
                    </div>

                    <!-- Reports Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                    <th class="px-6 py-4 font-semibold">ID</th>
                                    <th class="px-6 py-4 font-semibold">Мэдээлэгч</th>
                                    <th class="px-6 py-4 font-semibold">Холбоотой үйлчилгээ</th>
                                    <th class="px-6 py-4 font-semibold">Шалтгаан</th>
                                    <th class="px-6 py-4 font-semibold">Админы шийдвэр</th> <!-- Шинэ багана -->
                                    <th class="px-6 py-4 font-semibold">Төлөв</th>
                                    <th class="px-6 py-4 font-semibold text-right">Огноо</th>
                                    <th class="px-6 py-4 font-semibold text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (count($reports) > 0): ?>
                                    <?php foreach ($reports as $r): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 text-sm font-mono text-slate-500">#<?php echo $r['id']; ?></td>
                                        <td class="px-6 py-4 text-sm font-medium text-slate-700">
                                            <?php echo htmlspecialchars($r['reporter_name']); ?>
                                            <div class="text-xs text-slate-400 font-normal"><?php echo htmlspecialchars($r['reporter_email']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-indigo-600 hover:underline">
                                            <a href="../service-details.php?id=<?php echo $r['service_id']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($r['service_title']); ?> <i class="fas fa-external-link-alt text-xs"></i>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            <span class="font-bold text-red-500 block"><?php echo htmlspecialchars(ucfirst($r['reason'])); ?></span>
                                            <span class="text-xs text-gray-500 line-clamp-1" title="<?php echo htmlspecialchars($r['description']); ?>">
                                                <?php echo htmlspecialchars($r['description']); ?>
                                            </span>
                                        </td>
                                        <!-- Admin Note Display -->
                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            <?php if (!empty($r['admin_note'])): ?>
                                                <span class="text-xs text-gray-700 font-medium line-clamp-2" title="<?php echo htmlspecialchars($r['admin_note']); ?>">
                                                    <?php echo htmlspecialchars($r['admin_note']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-xs text-gray-400 italic">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php 
                                                $status_classes = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'reviewed' => 'bg-blue-100 text-blue-800',
                                                    'resolved' => 'bg-green-100 text-green-800',
                                                    'dismissed' => 'bg-gray-100 text-gray-800'
                                                ];
                                                $s_class = $status_classes[$r['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $s_class; ?>">
                                                <?php echo ucfirst($r['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm text-slate-500">
                                            <?php echo date('Y-m-d H:i', strtotime($r['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button onclick='openProcessModal(<?php echo json_encode($r); ?>)' class="bg-indigo-50 text-indigo-600 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-indigo-100 transition">
                                                    Шийдвэрлэх
                                                </button>
                                                <form method="POST" class="inline" onsubmit="return confirm('Энэ мэдээллийг устгах уу?');">
                                                    <input type="hidden" name="delete_report" value="1">
                                                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50 transition" title="Устгах">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center text-slate-500">Зөрчлийн мэдээлэл олдсонгүй.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <div class="mt-4 flex justify-center gap-2">
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="px-3 py-1 border <?php echo $i == $page ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-300 text-slate-600 hover:bg-slate-50'; ?> rounded text-sm">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <!-- Process Report Modal -->
    <div id="processModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('processModal')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form method="POST" action="">
                    <input type="hidden" name="update_report" value="1">
                    <input type="hidden" name="report_id" id="modal_report_id">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-gavel text-indigo-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Зөрчил шийдвэрлэх</h3>
                                <div class="mt-2">
                                    <div class="bg-gray-50 p-3 rounded text-sm text-gray-600 mb-4">
                                        <p><strong>Шалтгаан:</strong> <span id="modal_reason"></span></p>
                                        <p class="mt-1"><strong>Тайлбар:</strong> <span id="modal_desc"></span></p>
                                    </div>

                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Төлөв өөрчлөх</label>
                                            <select name="status" id="modal_status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                                <option value="pending">Pending (Хүлээгдэж буй)</option>
                                                <option value="reviewed">Reviewed (Шалгаж байна)</option>
                                                <option value="resolved">Resolved (Шийдвэрлэсэн)</option>
                                                <option value="dismissed">Dismissed (Татгалзсан)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Админы тэмдэглэл / Авсан арга хэмжээ</label>
                                            <textarea name="admin_note" id="modal_admin_note" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Жишээ: Үйлчилгээг идэвхгүй болгов, эсвэл хэрэглэгчид анхааруулга өгөв..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Хадгалах</button>
                        <button type="button" onclick="closeModal('processModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Болих</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function openProcessModal(report) {
            document.getElementById('modal_report_id').value = report.id;
            document.getElementById('modal_reason').textContent = report.reason;
            document.getElementById('modal_desc').textContent = report.description || 'Тайлбар байхгүй';
            document.getElementById('modal_status').value = report.status;
            document.getElementById('modal_admin_note').value = report.admin_note || '';
            
            document.getElementById('processModal').classList.remove('hidden');
        }
    </script>
</body>
</html>