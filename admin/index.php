<?php
session_start();
require_once '../includes/db.php';

// Админ эрх шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --------------------------------------------------------------------------
// 1. STATS COUNTERS (Тоон үзүүлэлтүүд)
// --------------------------------------------------------------------------

// Хүлээгдэж буй файлууд
$stmt = $pdo->query("SELECT COUNT(*) FROM files WHERE status = 'pending'");
$pending_files_count = $stmt->fetchColumn();

// Идэвхтэй захиалгууд (Service Orders)
$stmt = $pdo->query("SELECT COUNT(*) FROM service_orders WHERE status IN ('pending', 'in_progress')");
$active_orders_count = $stmt->fetchColumn();

// Мөнгө татах хүсэлтүүд
$stmt = $pdo->query("SELECT COUNT(*) FROM withdrawal_requests WHERE status = 'pending'");
$pending_withdrawals_count = $stmt->fetchColumn();

// Нийт орлого (Platform Revenue - approx calculation based on transactions)
// Энд нийт гүйлгээний дүнг авч байна. Хэрэв зөвхөн шимтгэлээ тооцох бол logic-оо өөрчилнө.
$stmt = $pdo->query("SELECT SUM(amount) FROM transactions WHERE status = 'success'");
$total_revenue = $stmt->fetchColumn() ?: 0;

// --------------------------------------------------------------------------
// 2. URGENT DATA FETCHING (Яаралтай мэдээллүүд)
// --------------------------------------------------------------------------

// A. Баталгаажуулалт хүлээж буй файлууд (Хамгийн сүүлийн 5)
$sql_files = "SELECT f.id, f.title, f.file_type, f.upload_date, u.username 
              FROM files f 
              JOIN users u ON f.user_id = u.id 
              WHERE f.status = 'pending' 
              ORDER BY f.upload_date DESC LIMIT 5";
$pending_files = $pdo->query($sql_files)->fetchAll(PDO::FETCH_ASSOC);

// B. Сүүлийн үеийн захиалгууд (Services)
// АЛДАА ЗАСАВ: created_at -> ordered_at
$sql_orders = "SELECT so.id, s.title as service_title, u.username as buyer_name, so.status, so.ordered_at as created_at, so.price
               FROM service_orders so
               JOIN services s ON so.service_id = s.id
               JOIN users u ON so.buyer_id = u.id
               WHERE so.status IN ('pending', 'in_progress')
               ORDER BY so.ordered_at DESC LIMIT 5";
$active_orders = $pdo->query($sql_orders)->fetchAll(PDO::FETCH_ASSOC);

// C. Мөнгө татах хүсэлтүүд
// АЛДАА ЗАСАВ: bank_name, details (account info) багануудыг withdrawal_requests хүснэгтээс авах
// users хүснэгтэд эдгээр багана байхгүй
$sql_withdrawals = "SELECT w.id, w.amount, w.request_date, u.username, w.bank_name, w.details as bank_account
                    FROM withdrawal_requests w
                    JOIN users u ON w.user_id = u.id
                    WHERE w.status = 'pending'
                    ORDER BY w.request_date ASC LIMIT 5"; // ASC: First come first serve
$pending_withdrawals = $pdo->query($sql_withdrawals)->fetchAll(PDO::FETCH_ASSOC);

// Helper: Format Time Ago
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'жил',
        'm' => 'сар',
        'w' => 'долоо хоног',
        'd' => 'өдөр',
        'h' => 'цаг',
        'i' => 'минут',
        's' => 'секунд',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v;
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . '-ын өмнө' : 'дөнгөж сая';
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ Хяналтын Самбар - FileZone</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/tailwind-config.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- HEADER -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shadow-sm z-10">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden text-slate-500"><i class="fas fa-bars text-xl"></i></button>
                    <h1 class="text-xl font-bold text-slate-800">Хяналтын самбар</h1>
                </div>
                
                <div class="flex items-center gap-4">
                    <!-- Notifications -->
                    <div class="relative">
                        <button class="text-slate-500 hover:text-indigo-600 transition relative">
                            <i class="far fa-bell text-xl"></i>
                            <?php if($pending_files_count + $pending_withdrawals_count > 0): ?>
                                <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white"></span>
                            <?php endif; ?>
                        </button>
                    </div>
                    
                    <!-- Admin Profile -->
                    <div class="flex items-center gap-2">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-bold text-slate-700">Админ</p>
                            <p class="text-xs text-slate-500">Super Admin</p>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold border border-indigo-200">
                            A
                        </div>
                    </div>
                </div>
            </header>

            <!-- DASHBOARD CONTENT -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                
                <!-- 1. KPI CARDS (Important Stats) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    
                    <!-- Pending Files -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group">
                        <div class="flex justify-between items-start z-10 relative">
                            <div>
                                <p class="text-sm font-medium text-slate-500 mb-1">Шалгах файлууд</p>
                                <h3 class="text-3xl font-bold text-slate-800"><?php echo $pending_files_count; ?></h3>
                            </div>
                            <div class="w-10 h-10 rounded-lg bg-orange-50 text-orange-500 flex items-center justify-center text-xl">
                                <i class="fas fa-file-contract"></i>
                            </div>
                        </div>
                        <?php if($pending_files_count > 0): ?>
                            <div class="mt-4">
                                <a href="files.php?status=pending" class="text-xs font-bold text-orange-600 hover:text-orange-700 flex items-center gap-1">
                                    Шалгах <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="mt-4 text-xs text-green-500 font-medium"><i class="fas fa-check"></i> Бүгд шалгагдсан</div>
                        <?php endif; ?>
                        <div class="absolute bottom-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition transform scale-150">
                            <i class="fas fa-file-contract text-6xl text-orange-500"></i>
                        </div>
                    </div>

                    <!-- Active Orders -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group">
                        <div class="flex justify-between items-start z-10 relative">
                            <div>
                                <p class="text-sm font-medium text-slate-500 mb-1">Идэвхтэй захиалга</p>
                                <h3 class="text-3xl font-bold text-slate-800"><?php echo $active_orders_count; ?></h3>
                            </div>
                            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center text-xl">
                                <i class="fas fa-briefcase"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="service_orders.php" class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                                Дэлгэрэнгүй <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Pending Withdrawals -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group">
                        <div class="flex justify-between items-start z-10 relative">
                            <div>
                                <p class="text-sm font-medium text-slate-500 mb-1">Мөнгө татах хүсэлт</p>
                                <h3 class="text-3xl font-bold text-slate-800"><?php echo $pending_withdrawals_count; ?></h3>
                            </div>
                            <div class="w-10 h-10 rounded-lg bg-red-50 text-red-500 flex items-center justify-center text-xl">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                        </div>
                        <?php if($pending_withdrawals_count > 0): ?>
                            <div class="mt-4">
                                <a href="finance.php" class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1">
                                    Шийдвэрлэх <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="mt-4 text-xs text-green-500 font-medium"><i class="fas fa-check"></i> Хүсэлт алга</div>
                        <?php endif; ?>
                    </div>

                    <!-- Total Revenue -->
                    <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl p-6 shadow-lg text-white relative overflow-hidden">
                        <div class="relative z-10">
                            <p class="text-sm font-medium text-indigo-100 mb-1">Нийт орлого</p>
                            <h3 class="text-3xl font-bold"><?php echo number_format($total_revenue); ?>₮</h3>
                            <p class="text-xs text-indigo-200 mt-4">Нийт гүйлгээний дүн</p>
                        </div>
                        <div class="absolute -bottom-4 -right-4 opacity-20 text-8xl">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>

                <!-- 2. URGENT ACTIONS GRID -->
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                    
                    <!-- A. Pending Files List -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <i class="fas fa-file-circle-check text-orange-500"></i> Баталгаажуулах файлууд
                            </h3>
                            <?php if($pending_files_count > 5): ?>
                                <a href="files.php?status=pending" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Бүгдийг харах</a>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 overflow-auto">
                            <?php if(count($pending_files) > 0): ?>
                                <table class="w-full text-sm text-left">
                                    <tbody class="divide-y divide-slate-100">
                                        <?php foreach($pending_files as $file): ?>
                                            <tr class="hover:bg-slate-50 transition">
                                                <td class="px-6 py-3">
                                                    <div class="font-medium text-slate-800 truncate max-w-[200px]" title="<?php echo htmlspecialchars($file['title']); ?>">
                                                        <?php echo htmlspecialchars($file['title']); ?>
                                                    </div>
                                                    <div class="text-xs text-slate-500">
                                                        <?php echo $file['username']; ?> • <?php echo strtoupper($file['file_type']); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-3 text-right whitespace-nowrap text-xs text-slate-400">
                                                    <?php echo time_elapsed_string($file['upload_date']); ?>
                                                </td>
                                                <td class="px-6 py-3 text-right">
                                                    <a href="edit_file.php?id=<?php echo $file['id']; ?>" class="text-indigo-600 hover:text-indigo-800 text-xs font-bold bg-indigo-50 px-2 py-1 rounded">Шалгах</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="p-8 text-center text-slate-400">
                                    <i class="fas fa-check-circle text-4xl mb-2 text-green-100"></i>
                                    <p class="text-sm">Шалгах файл байхгүй байна.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- B. Pending Withdrawals List -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <i class="fas fa-hand-holding-usd text-red-500"></i> Мөнгө татах хүсэлтүүд
                            </h3>
                            <a href="finance.php" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Удирдах</a>
                        </div>
                        <div class="flex-1 overflow-auto">
                            <?php if(count($pending_withdrawals) > 0): ?>
                                <div class="divide-y divide-slate-100">
                                    <?php foreach($pending_withdrawals as $req): ?>
                                        <div class="px-6 py-3 hover:bg-slate-50 transition flex justify-between items-center">
                                            <div>
                                                <div class="font-bold text-slate-800 text-sm">
                                                    <?php echo number_format($req['amount']); ?>₮
                                                </div>
                                                <div class="text-xs text-slate-500">
                                                    <?php echo $req['username']; ?> • <?php echo $req['bank_name']; ?>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-xs text-slate-400 mb-1"><?php echo date('m-d H:i', strtotime($req['request_date'])); ?></div>
                                                <a href="finance.php" class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded font-bold hover:bg-red-200">ШИЙДВЭРЛЭХ</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="p-8 text-center text-slate-400">
                                    <i class="fas fa-piggy-bank text-4xl mb-2 text-slate-100"></i>
                                    <p class="text-sm">Хүлээгдэж буй хүсэлт алга.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- C. Recent Service Orders -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <i class="fas fa-bolt text-yellow-500"></i> Шинэ захиалгууд
                            </h3>
                            <a href="service_orders.php" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Бүгд</a>
                        </div>
                        <div class="flex-1 overflow-auto">
                            <?php if(count($active_orders) > 0): ?>
                                <div class="divide-y divide-slate-100">
                                    <?php foreach($active_orders as $order): ?>
                                        <div class="px-6 py-3 hover:bg-slate-50 transition">
                                            <div class="flex justify-between items-start mb-1">
                                                <span class="text-xs font-bold px-2 py-0.5 rounded <?php echo $order['status']=='pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700'; ?>">
                                                    <?php echo strtoupper($order['status']); ?>
                                                </span>
                                                <span class="text-xs text-slate-400"><?php echo time_elapsed_string($order['created_at']); ?></span>
                                            </div>
                                            <h4 class="text-sm font-medium text-slate-800 truncate" title="<?php echo htmlspecialchars($order['service_title']); ?>">
                                                <?php echo htmlspecialchars($order['service_title']); ?>
                                            </h4>
                                            <div class="flex justify-between items-center mt-1">
                                                <span class="text-xs text-slate-500">Захиалагч: <?php echo $order['buyer_name']; ?></span>
                                                <span class="text-xs font-bold text-slate-700"><?php echo number_format($order['price']); ?>₮</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="p-8 text-center text-slate-400">
                                    <i class="fas fa-box-open text-4xl mb-2 text-slate-100"></i>
                                    <p class="text-sm">Идэвхтэй захиалга алга.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- 3. CHART SECTION (Simple Traffic/Revenue Placeholder) -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-slate-800">Системийн тойм (Сүүлийн 7 хоног)</h3>
                        <select class="text-sm border border-slate-200 rounded-lg px-2 py-1 bg-slate-50 outline-none">
                            <option>Орлого</option>
                            <option>Хандалт</option>
                        </select>
                    </div>
                    <div class="h-64 w-full bg-slate-50 rounded-lg flex items-center justify-center border border-dashed border-slate-300 relative">
                        <canvas id="mainChart"></canvas>
                        <!-- Fallback text if JS fails -->
                        <p class="text-slate-400 text-sm absolute">График ачаалж байна...</p>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Chart Script (Mock Data) -->
    <script>
        const ctx = document.getElementById('mainChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Дав', 'Мяг', 'Лха', 'Пүр', 'Баа', 'Бям', 'Ням'],
                datasets: [{
                    label: 'Орлого (₮)',
                    data: [15000, 25000, 10000, 35000, 20000, 45000, 30000],
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>