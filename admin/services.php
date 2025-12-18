<?php
// --------------------------------------------------------------------------
// MOCK DATA (Хэрэглэгчдийн санал болгож буй үйлчилгээнүүд)
// --------------------------------------------------------------------------

$services = [
    [
        'id' => 101,
        'title' => 'Мэргэжлийн түвшинд лого, брэндбүүк бүтээнэ',
        'provider' => 'DesignMaster',
        'provider_avatar' => 'https://ui-avatars.com/api/?name=Design&background=random',
        'category' => 'График Дизайн',
        'price' => '50,000₮',
        'status' => 'pending', // Хүлээгдэж буй (Шинэ)
        'created_at' => '2025-10-24 10:30',
        'image' => 'https://images.unsplash.com/photo-1626785774573-4b799314346d?auto=format&fit=crop&w=100&q=80'
    ],
    [
        'id' => 102,
        'title' => 'Англи хэлнээс Монгол руу орчуулга хийнэ',
        'provider' => 'Sarnai_Trans',
        'provider_avatar' => 'https://ui-avatars.com/api/?name=Sarnai&background=random',
        'category' => 'Орчуулга & Бичвэр',
        'price' => '15,000₮',
        'status' => 'active', // Зөвшөөрөгдсөн (Идэвхтэй)
        'created_at' => '2025-10-24 09:15',
        'image' => 'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=100&q=80'
    ],
    [
        'id' => 103,
        'title' => 'Бакалавр, Магистрын дипломны судалгаа хийнэ',
        'provider' => 'Researcher99',
        'provider_avatar' => 'https://ui-avatars.com/api/?name=Res&background=random',
        'category' => 'Судалгаа & Зөвлөгөө',
        'price' => '150,000₮',
        'status' => 'active',
        'created_at' => '2025-10-23 16:45',
        'image' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?auto=format&fit=crop&w=100&q=80'
    ],
    [
        'id' => 104,
        'title' => '1 цагийн дотор CV болон Resume бэлтгэж өгнө',
        'provider' => 'JobHunter',
        'provider_avatar' => 'https://ui-avatars.com/api/?name=Job&background=random',
        'category' => 'Хүний нөөц',
        'price' => '20,000₮',
        'status' => 'rejected', // Татгалзсан
        'created_at' => '2025-10-23 14:20',
        'image' => 'https://images.unsplash.com/photo-1586281380349-632531db7ed4?auto=format&fit=crop&w=100&q=80'
    ],
    [
        'id' => 105,
        'title' => 'WordPress вэбсайт хийж өгнө',
        'provider' => 'WebDev_Mongolia',
        'provider_avatar' => 'https://ui-avatars.com/api/?name=Web&background=random',
        'category' => 'Програмчлал',
        'price' => '300,000₮',
        'status' => 'pending',
        'created_at' => '2025-10-23 11:00',
        'image' => 'https://images.unsplash.com/photo-1547658719-da2b51169166?auto=format&fit=crop&w=100&q=80'
    ]
];

?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Moderation - Filezone Admin</title>
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
                    <h1 class="text-xl font-bold text-slate-800">Үйлчилгээний модератор</h1>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Statistics or Quick info -->
                    <span class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">Хүлээгдэж буй: 2</span>
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
                            <input type="text" placeholder="Үйлчилгээний нэр эсвэл хэрэглэгчээр хайх..." class="pl-10 pr-4 py-2 w-full border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <!-- Status Filter -->
                        <select class="border border-slate-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-slate-600">
                            <option value="">Бүх төлөв</option>
                            <option value="pending">Хүлээгдэж буй (Шинэ)</option>
                            <option value="active">Зөвшөөрсөн</option>
                            <option value="rejected">Татгалзсан</option>
                        </select>

                         <!-- Category Filter -->
                         <select class="border border-slate-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-slate-600">
                            <option value="">Бүх ангилал</option>
                            <option value="design">График Дизайн</option>
                            <option value="translation">Орчуулга</option>
                            <option value="research">Судалгаа</option>
                            <option value="coding">Програмчлал</option>
                        </select>
                    </div>
                </div>

                <!-- Services Table -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                    <th class="px-6 py-4 font-semibold">Үйлчилгээ</th>
                                    <th class="px-6 py-4 font-semibold">Үзүүлэгч</th>
                                    <th class="px-6 py-4 font-semibold">Ангилал</th>
                                    <th class="px-6 py-4 font-semibold">Үнэ (Эхлэх)</th>
                                    <th class="px-6 py-4 font-semibold">Төлөв</th>
                                    <th class="px-6 py-4 font-semibold text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($services as $service): ?>
                                <tr id="service-row-<?php echo $service['id']; ?>" class="hover:bg-slate-50 transition-colors group">
                                    <td class="px-6 py-4 w-1/3">
                                        <div class="flex items-start gap-3">
                                            <img src="<?php echo $service['image']; ?>" alt="" class="w-12 h-12 rounded-lg object-cover border border-slate-200 flex-shrink-0">
                                            <div>
                                                <p class="text-sm font-bold text-slate-800 line-clamp-2 hover:text-indigo-600 cursor-pointer" title="<?php echo $service['title']; ?>"><?php echo $service['title']; ?></p>
                                                <p class="text-xs text-slate-500 mt-1"><?php echo $service['created_at']; ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <img src="<?php echo $service['provider_avatar']; ?>" alt="" class="w-6 h-6 rounded-full border border-slate-200">
                                            <span class="text-sm text-slate-700 font-medium"><?php echo $service['provider']; ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                            <?php echo $service['category']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-bold text-slate-700"><?php echo $service['price']; ?></td>
                                    <td class="px-6 py-4 service-status-cell">
                                        <?php if($service['status'] == 'active'): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Зөвшөөрсөн
                                            </span>
                                        <?php elseif($service['status'] == 'pending'): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200 animate-pulse">
                                                <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Хүлээгдэж буй
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Татгалзсан
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- View Button (Always visible) -->
                                            <button class="p-1.5 text-slate-400 hover:text-indigo-600 rounded hover:bg-indigo-50 transition" title="Дэлгэрэнгүй үзэх">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            <?php if($service['status'] == 'pending'): ?>
                                                <!-- Action Buttons for Pending items -->
                                                <button onclick="approveService(<?php echo $service['id']; ?>)" class="p-1.5 text-green-500 hover:text-green-700 bg-green-50 hover:bg-green-100 rounded transition border border-green-200" title="Зөвшөөрөх">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button onclick="rejectService(<?php echo $service['id']; ?>)" class="p-1.5 text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 rounded transition border border-red-200" title="Татгалзах">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php elseif($service['status'] == 'active'): ?>
                                                <!-- Already Active -->
                                                <button onclick="rejectService(<?php echo $service['id']; ?>)" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50 transition" title="Идэвхгүй болгох">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button onclick="deleteService(<?php echo $service['id']; ?>)" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50 transition" title="Устгах">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
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

    <!-- Review Modal (Optional - Could be used to see full details before approving) -->
    <!-- For simplicity, using JS confirm for now -->

    <script src="js/script.js"></script>
</body>
</html>