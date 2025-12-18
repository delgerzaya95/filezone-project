<?php
// --------------------------------------------------------------------------
// MOCK DATA (Сэтгэгдлүүд)
// --------------------------------------------------------------------------

$comments = [
    [
        'id' => 1,
        'user' => 'Bat-Erdene',
        'avatar' => 'https://ui-avatars.com/api/?name=Bat-Erdene&background=random',
        'content' => 'Энэ файлыг татахад асуудал гараад байна. Туслаарай.',
        'item' => 'English_Grammar.pdf',
        'type' => 'file', // file or service
        'date' => '2025-10-24 14:30',
        'status' => 'pending'
    ],
    [
        'id' => 2,
        'user' => 'Sarnai',
        'avatar' => 'https://ui-avatars.com/api/?name=Sarnai&background=random',
        'content' => 'Маш хэрэгтэй програм байна. Баярлалаа!',
        'item' => 'Adobe Photoshop 2025',
        'type' => 'file',
        'date' => '2025-10-24 12:15',
        'status' => 'approved'
    ],
    [
        'id' => 3,
        'user' => 'Spammer101',
        'avatar' => 'https://ui-avatars.com/api/?name=Spam&background=ef4444&color=fff',
        'content' => 'Check out this amazing site: www.scam-site.com !!! Free money!!!',
        'item' => 'Business Plan Template',
        'type' => 'file',
        'date' => '2025-10-23 23:00',
        'status' => 'spam'
    ],
    [
        'id' => 4,
        'user' => 'Boldoo',
        'avatar' => 'https://ui-avatars.com/api/?name=Boldoo&background=random',
        'content' => 'Таны үйлчилгээний үнэ жоохон үнэтэй юм биш үү? Хөнгөлэлт байгаа юу?',
        'item' => 'Лого хийж өгнө',
        'type' => 'service',
        'date' => '2025-10-23 10:45',
        'status' => 'approved'
    ],
    [
        'id' => 5,
        'user' => 'Tuya',
        'avatar' => 'https://ui-avatars.com/api/?name=Tuya&background=random',
        'content' => 'Файлын нууц үг нь юу вэ?',
        'item' => 'Game_Setup.rar',
        'type' => 'file',
        'date' => '2025-10-22 16:20',
        'status' => 'pending'
    ]
];

?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments Management - Filezone Admin</title>
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
                    <h1 class="text-xl font-bold text-slate-800">Сэтгэгдлийн удирдлага</h1>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Statistics -->
                    <span class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">Шинэ: 2</span>
                    <span class="text-xs font-medium text-red-500 bg-red-50 px-3 py-1 rounded-full">Спам: 1</span>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6">
                
                <!-- Filters Bar -->
                <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex flex-col md:flex-row gap-4 flex-1">
                        <!-- Search -->
                        <div class="relative flex-1 max-w-md">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="text" placeholder="Сэтгэгдлийн агуулга эсвэл хэрэглэгчээр хайх..." class="pl-10 pr-4 py-2 w-full border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <!-- Status Filter -->
                        <select class="border border-slate-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-slate-600">
                            <option value="">Бүх төлөв</option>
                            <option value="pending">Хүлээгдэж буй</option>
                            <option value="approved">Зөвшөөрсөн</option>
                            <option value="spam">Спам</option>
                        </select>
                    </div>

                    <!-- Bulk Actions -->
                    <div class="flex items-center gap-2">
                        <button class="p-2 text-slate-500 hover:text-green-600 border border-slate-300 rounded-lg bg-white" title="Бүгдийг зөвшөөрөх">
                            <i class="fas fa-check-double"></i>
                        </button>
                    </div>
                </div>

                <!-- Comments List -->
                <div class="space-y-4">
                    <?php foreach ($comments as $comment): ?>
                    <div id="comment-<?php echo $comment['id']; ?>" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 transition-all hover:shadow-md <?php echo $comment['status'] === 'pending' ? 'border-l-4 border-l-yellow-400' : ($comment['status'] === 'spam' ? 'border-l-4 border-l-red-500 opacity-75' : ''); ?>">
                        <div class="flex items-start gap-4">
                            <!-- Avatar -->
                            <img src="<?php echo $comment['avatar']; ?>" alt="" class="w-10 h-10 rounded-full border border-slate-200 flex-shrink-0">
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-2">
                                    <h4 class="text-sm font-bold text-slate-800">
                                        <?php echo $comment['user']; ?>
                                        <span class="text-slate-400 font-normal ml-1">бичсэн нь:</span>
                                        <a href="#" class="text-indigo-600 hover:text-indigo-800 ml-1"><?php echo $comment['item']; ?></a>
                                    </h4>
                                    <span class="text-xs text-slate-400 mt-1 sm:mt-0"><i class="far fa-clock mr-1"></i> <?php echo $comment['date']; ?></span>
                                </div>
                                
                                <p class="text-slate-600 text-sm leading-relaxed mb-4"><?php echo $comment['content']; ?></p>
                                
                                <!-- Status Badge (Visible if not approved) -->
                                <?php if($comment['status'] === 'pending'): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 mb-3">
                                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Хүлээгдэж буй
                                    </span>
                                <?php elseif($comment['status'] === 'spam'): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 mb-3">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Спам
                                    </span>
                                <?php endif; ?>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-3 border-t border-slate-100 pt-3">
                                    <?php if($comment['status'] !== 'approved'): ?>
                                        <button onclick="updateStatus(<?php echo $comment['id']; ?>, 'approved')" class="text-xs font-medium text-green-600 hover:text-green-800 flex items-center gap-1 px-2 py-1 rounded hover:bg-green-50 transition">
                                            <i class="fas fa-check"></i> Зөвшөөрөх
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button onclick="replyComment(<?php echo $comment['id']; ?>)" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 flex items-center gap-1 px-2 py-1 rounded hover:bg-indigo-50 transition">
                                        <i class="fas fa-reply"></i> Хариулах
                                    </button>

                                    <?php if($comment['status'] !== 'spam'): ?>
                                        <button onclick="updateStatus(<?php echo $comment['id']; ?>, 'spam')" class="text-xs font-medium text-orange-500 hover:text-orange-700 flex items-center gap-1 px-2 py-1 rounded hover:bg-orange-50 transition">
                                            <i class="fas fa-exclamation-circle"></i> Спам
                                        </button>
                                    <?php endif; ?>

                                    <button onclick="deleteComment(<?php echo $comment['id']; ?>)" class="text-xs font-medium text-red-500 hover:text-red-700 flex items-center gap-1 px-2 py-1 rounded hover:bg-red-50 transition ml-auto">
                                        <i class="fas fa-trash"></i> Устгах
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex justify-center">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-slate-300 bg-white text-sm font-medium text-slate-500 hover:bg-slate-50">
                            <span class="sr-only">Previous</span>
                            <i class="fas fa-chevron-left h-5 w-5"></i>
                        </a>
                        <a href="#" class="relative inline-flex items-center px-4 py-2 border border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">1</a>
                        <a href="#" class="relative inline-flex items-center px-4 py-2 border border-slate-300 bg-indigo-50 text-sm font-medium text-indigo-600 hover:bg-indigo-100">2</a>
                        <a href="#" class="relative inline-flex items-center px-4 py-2 border border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">3</a>
                        <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-slate-300 bg-white text-sm font-medium text-slate-500 hover:bg-slate-50">
                            <span class="sr-only">Next</span>
                            <i class="fas fa-chevron-right h-5 w-5"></i>
                        </a>
                    </nav>
                </div>

            </main>
        </div>
    </div>

    <<script src="js/script.js"></script>
</body>
</html>