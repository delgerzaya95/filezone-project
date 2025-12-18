<?php
// --------------------------------------------------------------------------
// MOCK DATA (Өгөгдлийн баазтай холбох үед энд SQL query бичигдэнэ)
// --------------------------------------------------------------------------

// Статистик тоон мэдээлэл
$stats = [
    'total_users' => 1250,
    'total_files' => 8430,
    'total_downloads' => 45200,
    'total_revenue' => '2,450,000₮',
    'pending_withdrawals' => 3
];

// Сүүлд нэмэгдсэн файлууд (Жишээ)
$recent_files = [
    ['id' => 101, 'name' => 'English_Grammar.pdf', 'user' => 'Bat-Erdene', 'size' => '2.5 MB', 'date' => '2025-10-24 10:30', 'status' => 'active'],
    ['id' => 102, 'name' => 'Project_Plan.docx', 'user' => 'Sarnai', 'size' => '1.2 MB', 'date' => '2025-10-24 09:15', 'status' => 'active'],
    ['id' => 103, 'name' => 'Holiday_Photos.zip', 'user' => 'Boldoo', 'size' => '150 MB', 'date' => '2025-10-23 16:45', 'status' => 'deleted'],
    ['id' => 104, 'name' => 'Setup_v2.exe', 'user' => 'Admin', 'size' => '45 MB', 'date' => '2025-10-23 14:20', 'status' => 'active'],
    ['id' => 105, 'name' => 'Report_2025.pdf', 'user' => 'Tuya', 'size' => '800 KB', 'date' => '2025-10-23 11:00', 'status' => 'pending'],
];

?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filezone Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Chart.js for graphs -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
</head>
<body class="font-sans text-slate-800 antialiased">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- TOP HEADER -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-10 shadow-sm">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden text-slate-500 hover:text-slate-700 focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl font-bold text-slate-800 hidden sm:block">Хяналтын самбар</h1>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Search -->
                    <div class="relative hidden md:block">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text" placeholder="Хайлт хийх..." class="pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent w-64 transition-all">
                    </div>

                    <!-- Notifications -->
                    <button class="relative p-2 text-slate-500 hover:text-indigo-600 transition-colors">
                        <i class="far fa-bell text-lg"></i>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                    </button>

                    <!-- Profile Dropdown (Simple) -->
                    <div class="relative ml-2">
                        <button class="flex items-center gap-2 focus:outline-none">
                            <span class="text-sm font-medium text-slate-700 hidden md:block">Админ</span>
                            <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- MAIN SCROLLABLE CONTENT -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6">
                
                <!-- 1. Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Users Card -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-100 flex items-center justify-between hover:shadow-md transition">
                        <div>
                            <p class="text-sm font-medium text-slate-500 mb-1">Нийт хэрэглэгч</p>
                            <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['total_users']); ?></h3>
                            <p class="text-xs text-green-500 font-medium mt-1"><i class="fas fa-arrow-up"></i> 12% өссөн</p>
                        </div>
                        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center text-xl">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>

                    <!-- Files Card -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-100 flex items-center justify-between hover:shadow-md transition">
                        <div>
                            <p class="text-sm font-medium text-slate-500 mb-1">Нийт файл</p>
                            <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['total_files']); ?></h3>
                            <p class="text-xs text-green-500 font-medium mt-1"><i class="fas fa-arrow-up"></i> 5% өссөн</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center text-xl">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>

                    <!-- Downloads Card -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-100 flex items-center justify-between hover:shadow-md transition">
                        <div>
                            <p class="text-sm font-medium text-slate-500 mb-1">Татаж авалт</p>
                            <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['total_downloads']); ?></h3>
                            <p class="text-xs text-slate-400 font-medium mt-1">Нийт хугацаанд</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-full flex items-center justify-center text-xl">
                            <i class="fas fa-cloud-download-alt"></i>
                        </div>
                    </div>

                    <!-- Revenue Card -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-100 flex items-center justify-between hover:shadow-md transition">
                        <div>
                            <p class="text-sm font-medium text-slate-500 mb-1">Нийт орлого</p>
                            <h3 class="text-2xl font-bold text-slate-800"><?php echo $stats['total_revenue']; ?></h3>
                            <p class="text-xs text-green-500 font-medium mt-1"><i class="fas fa-arrow-up"></i> 8.2% өссөн</p>
                        </div>
                        <div class="w-12 h-12 bg-green-50 text-green-600 rounded-full flex items-center justify-center text-xl">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>

                <!-- 2. Charts & Tables Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    
                    <!-- Chart Area -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-slate-800">Хандалтын статистик</h3>
                            <select class="text-sm border-slate-300 rounded-md text-slate-500 bg-slate-50 p-1 border">
                                <option>Сүүлийн 7 хоног</option>
                                <option>Энэ сар</option>
                            </select>
                        </div>
                        <div class="relative h-72 w-full">
                            <canvas id="trafficChart"></canvas>
                        </div>
                    </div>

                    <!-- Recent Activity / Quick Actions -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                        <h3 class="text-lg font-bold text-slate-800 mb-4">Шуурхай үйлдэл</h3>
                        <div class="space-y-3">
                            <button class="w-full flex items-center justify-between p-3 bg-indigo-50 rounded-lg text-indigo-700 hover:bg-indigo-100 transition">
                                <span class="font-medium flex items-center gap-2"><i class="fas fa-file-upload"></i> Шинэ файл нэмэх</span>
                                <i class="fas fa-chevron-right text-sm"></i>
                            </button>
                            <button class="w-full flex items-center justify-between p-3 bg-slate-50 rounded-lg text-slate-700 hover:bg-slate-100 transition">
                                <span class="font-medium flex items-center gap-2"><i class="fas fa-user-plus"></i> Хэрэглэгч үүсгэх</span>
                                <i class="fas fa-chevron-right text-sm"></i>
                            </button>
                            <button class="w-full flex items-center justify-between p-3 bg-slate-50 rounded-lg text-slate-700 hover:bg-slate-100 transition">
                                <span class="font-medium flex items-center gap-2"><i class="fas fa-bullhorn"></i> Зарлал оруулах</span>
                                <i class="fas fa-chevron-right text-sm"></i>
                            </button>
                        </div>

                        <h3 class="text-lg font-bold text-slate-800 mt-8 mb-4">Системийн төлөв</h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-slate-500">Сервер ачаалал</span>
                                    <span class="text-slate-700 font-bold">24%</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: 24%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-slate-500">Хадгалах зай</span>
                                    <span class="text-slate-700 font-bold">1.2TB / 5TB</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2">
                                    <div class="bg-indigo-500 h-2 rounded-full" style="width: 35%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Recent Files Table -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800">Сүүлд нэмэгдсэн файлууд</h3>
                        <a href="admin/files.php" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Бүгдийг харах &rarr;</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                                    <th class="px-6 py-3 font-semibold">Файлын нэр</th>
                                    <th class="px-6 py-3 font-semibold">Хэрэглэгч</th>
                                    <th class="px-6 py-3 font-semibold">Хэмжээ</th>
                                    <th class="px-6 py-3 font-semibold">Огноо</th>
                                    <th class="px-6 py-3 font-semibold">Төлөв</th>
                                    <th class="px-6 py-3 font-semibold text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($recent_files as $file): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                                <i class="fas <?php echo strpos($file['name'], 'pdf') ? 'fa-file-pdf' : (strpos($file['name'], 'zip') ? 'fa-file-archive' : 'fa-file-alt'); ?>"></i>
                                            </div>
                                            <span class="text-sm font-medium text-slate-700"><?php echo $file['name']; ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600"><?php echo $file['user']; ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-500"><?php echo $file['size']; ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-500"><?php echo $file['date']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if($file['status'] == 'active'): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Идэвхтэй</span>
                                        <?php elseif($file['status'] == 'pending'): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Хүлээгдэж буй</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Устгагдсан</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-slate-400 hover:text-indigo-600 mx-1"><i class="fas fa-edit"></i></button>
                                        <button class="text-slate-400 hover:text-red-600 mx-1"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/script.js"></script>
</body>
</html>