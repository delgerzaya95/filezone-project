<?php
// --------------------------------------------------------------------------
// MOCK DATA (Санхүүгийн мэдээлэл)
// --------------------------------------------------------------------------

// Мөнгө татах хүсэлтүүд
$withdrawals = [
    [
        'id' => 501,
        'user' => 'Bat-Erdene',
        'amount' => '50,000₮',
        'method' => 'Khan Bank',
        'account' => '5000123456',
        'status' => 'pending',
        'date' => '2025-10-24 10:30',
        'avatar' => 'https://ui-avatars.com/api/?name=Bat&background=random'
    ],
    [
        'id' => 502,
        'user' => 'Sarnai',
        'amount' => '200,000₮',
        'method' => 'Golomt Bank',
        'account' => '1105123456',
        'status' => 'approved',
        'date' => '2025-10-23 15:45',
        'avatar' => 'https://ui-avatars.com/api/?name=Sarnai&background=random'
    ],
    [
        'id' => 503,
        'user' => 'DesignPro',
        'amount' => '15,000₮',
        'method' => 'State Bank',
        'account' => '3400123456',
        'status' => 'rejected',
        'date' => '2025-10-22 09:10',
        'avatar' => 'https://ui-avatars.com/api/?name=Design&background=random'
    ],
    [
        'id' => 504,
        'user' => 'Tuya',
        'amount' => '80,000₮',
        'method' => 'Khan Bank',
        'account' => '5034567890',
        'status' => 'pending',
        'date' => '2025-10-24 12:00',
        'avatar' => 'https://ui-avatars.com/api/?name=Tuya&background=random'
    ]
];

// Гүйлгээний түүх
$transactions = [
    ['id' => 'TRX-1001', 'user' => 'Boldoo', 'type' => 'income', 'desc' => 'Үйлчилгээний төлбөр (VIP)', 'amount' => '+15,000₮', 'date' => '2025-10-24 14:20', 'status' => 'completed'],
    ['id' => 'TRX-1002', 'user' => 'Sarnai', 'type' => 'expense', 'desc' => 'Мөнгө таталт (#502)', 'amount' => '-200,000₮', 'date' => '2025-10-23 16:00', 'status' => 'completed'],
    ['id' => 'TRX-1003', 'user' => 'NewUser123', 'type' => 'income', 'desc' => 'Данс цэнэглэлт', 'amount' => '+5,000₮', 'date' => '2025-10-23 11:30', 'status' => 'completed'],
    ['id' => 'TRX-1004', 'user' => 'Gantulga', 'type' => 'income', 'desc' => 'Файл худалдан авалт', 'amount' => '+2,500₮', 'date' => '2025-10-22 18:45', 'status' => 'completed'],
    ['id' => 'TRX-1005', 'user' => 'System', 'type' => 'expense', 'desc' => 'Сервер төлбөр', 'amount' => '-150,000₮', 'date' => '2025-10-20 09:00', 'status' => 'completed'],
];

?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Management - Filezone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
</head>
<body class="font-sans text-slate-800 antialiased">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- HEADER -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shadow-sm z-10">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden text-slate-500"><i class="fas fa-bars text-xl"></i></button>
                    <h1 class="text-xl font-bold text-slate-800">Санхүүгийн удирдлага</h1>
                </div>
                <div class="flex items-center gap-3">
                    <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                        <i class="fas fa-file-export"></i> <span class="hidden sm:inline">Тайлан татах</span>
                    </button>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6">
                
                <!-- Financial Overview Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Нийт орлого (Энэ сар)</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-1">2,450,000₮</h3>
                            <p class="text-xs text-green-600 mt-1 flex items-center"><i class="fas fa-arrow-up mr-1"></i> 15% өссөн</p>
                        </div>
                        <div class="w-12 h-12 bg-green-50 text-green-600 rounded-full flex items-center justify-center text-xl">
                            <i class="fas fa-coins"></i>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Хүлээгдэж буй зарлага</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-1">130,000₮</h3>
                            <p class="text-xs text-slate-400 mt-1">2 хүсэлт</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-50 text-yellow-600 rounded-full flex items-center justify-center text-xl">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Нийт төлсөн</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-1">12,500,000₮</h3>
                            <p class="text-xs text-slate-400 mt-1">2025 он гарсаар</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center text-xl">
                            <i class="fas fa-check-double"></i>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-slate-200 mb-6">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button onclick="switchTab('withdrawals')" id="tab-withdrawals" class="tab-btn active whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-indigo-600 border-indigo-500">
                            Татсан мөнгө (Withdrawals)
                        </button>
                        <button onclick="switchTab('transactions')" id="tab-transactions" class="tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-slate-500 border-transparent hover:text-slate-700 hover:border-slate-300">
                            Гүйлгээний түүх (Transactions)
                        </button>
                    </nav>
                </div>

                <!-- Tab Content: Withdrawals -->
                <div id="content-withdrawals" class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Мөнгө татах хүсэлтүүд</h3>
                            <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded">2 Шинэ</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                        <th class="px-6 py-4 font-semibold">Хэрэглэгч</th>
                                        <th class="px-6 py-4 font-semibold">Дүн</th>
                                        <th class="px-6 py-4 font-semibold">Дансны мэдээлэл</th>
                                        <th class="px-6 py-4 font-semibold">Огноо</th>
                                        <th class="px-6 py-4 font-semibold">Төлөв</th>
                                        <th class="px-6 py-4 font-semibold text-right">Үйлдэл</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($withdrawals as $w): ?>
                                    <tr id="withdrawal-<?php echo $w['id']; ?>" class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <img src="<?php echo $w['avatar']; ?>" class="w-8 h-8 rounded-full border border-slate-200">
                                                <span class="text-sm font-medium text-slate-700"><?php echo $w['user']; ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 font-bold text-slate-800"><?php echo $w['amount']; ?></td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-slate-700 font-medium"><?php echo $w['method']; ?></div>
                                            <div class="text-xs text-slate-500"><?php echo $w['account']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-500"><?php echo $w['date']; ?></td>
                                        <td class="px-6 py-4 status-cell">
                                            <?php if($w['status'] == 'approved'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Шилжүүлсэн
                                                </span>
                                            <?php elseif($w['status'] == 'pending'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 animate-pulse">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Хүлээгдэж буй
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Татгалзсан
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <?php if($w['status'] == 'pending'): ?>
                                                <button onclick="approveWithdrawal(<?php echo $w['id']; ?>)" class="bg-green-50 text-green-600 hover:bg-green-100 px-3 py-1 rounded text-xs font-bold mr-2 border border-green-200 transition">Зөвшөөрөх</button>
                                                <button onclick="rejectWithdrawal(<?php echo $w['id']; ?>)" class="bg-red-50 text-red-600 hover:bg-red-100 px-3 py-1 rounded text-xs font-bold border border-red-200 transition">Татгалзах</button>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400 italic">Үйлдэл хийгдсэн</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Transactions -->
                <div id="content-transactions" class="hidden space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Бүх гүйлгээнүүд</h3>
                            <input type="text" placeholder="Гүйлгээ хайх..." class="text-xs border border-slate-300 rounded px-2 py-1 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                        <th class="px-6 py-4 font-semibold">Гүйлгээний ID</th>
                                        <th class="px-6 py-4 font-semibold">Хэрэглэгч</th>
                                        <th class="px-6 py-4 font-semibold">Утга</th>
                                        <th class="px-6 py-4 font-semibold">Дүн</th>
                                        <th class="px-6 py-4 font-semibold">Огноо</th>
                                        <th class="px-6 py-4 font-semibold">Төлөв</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($transactions as $t): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 text-sm font-mono text-slate-600"><?php echo $t['id']; ?></td>
                                        <td class="px-6 py-4 text-sm text-slate-700 font-medium"><?php echo $t['user']; ?></td>
                                        <td class="px-6 py-4 text-sm text-slate-600"><?php echo $t['desc']; ?></td>
                                        <td class="px-6 py-4 font-bold <?php echo $t['type'] === 'income' ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo $t['amount']; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-500"><?php echo $t['date']; ?></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                                Амжилттай
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>