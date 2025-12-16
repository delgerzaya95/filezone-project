<?php
// Энэ хэсэгт PHP логик бичигдэнэ (Database connection, fetching data etc.)
// Жишээ өгөгдлүүд (Mock Data):
$service = [
    'title' => 'Байгууллагын санхүүгийн цогц тайлан гаргаж, зөвлөгөө өгнө',
    'category' => 'Санхүү & Нягтлан',
    'price' => '150,000₮',
    'rating' => 4.8,
    'review_count' => 124,
    'orders_queue' => 3,
    'delivery_days' => 2,
    'description' => 'Мэргэжлийн нягтлан бодогч танай байгууллагын санхүүгийн тайланг ОУ-ын стандартын дагуу чанартай, хурдан шуурхай гаргаж өгнө. <br><br> 
    <strong>Үйлчилгээнд багтах зүйлс:</strong>
    <ul class="list-disc pl-5 mt-2">
        <li>Баланс тайлан</li>
        <li>Орлого үр дүнгийн тайлан</li>
        <li>Мөнгөн гүйлгээний тайлан</li>
        <li>Өмчийн өөрчлөлтийн тайлан</li>
    </ul>',
    'author' => 'B.Delgerzaya',
    'author_level' => 'Мэргэшсэн',
    'author_avatar' => 'assets/images/default-avatar.png', // Таны файлд байгаа default avatar
    'views' => 1502,
    'created_at' => '2025-10-01'
];
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $service['title']; ?> - Filezone.mn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/tailwind-config.js"></script>
</head>
<body class="text-gray-700 antialiased font-sans flex flex-col min-h-screen">

    <!-- 1. Top Bar -->
    <div class="bg-slate-800 text-gray-400 text-[10px] sm:text-xs py-1.5 border-b border-slate-700">
        <div class="w-full max-w-7xl mx-auto px-4 flex justify-between items-center">
            <span>Монголын дижитал файлын сан</span>
            <div class="flex gap-3">
                <a href="guides.php" class="hover:text-white transition-colors">Тусламж</a>
                <span class="hidden sm:inline">|</span>
                <a href="contact.php" class="hover:text-white transition-colors">Холбоо барих</a>
            </div>
        </div>
    </div>
    
    <!-- 2. Navbar -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo & Search -->
                <div class="flex items-center flex-1 gap-4 lg:gap-8">
                    <a href="index.php" class="flex-shrink-0 flex items-center gap-2">
                        <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-brand-500/30">F</div>
                        <span class="font-bold text-xl tracking-tight text-gray-900 hidden sm:block">Filezone</span>
                    </a>

                    <!-- Desktop Search -->
                    <div class="hidden md:block w-full max-w-md relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" placeholder="Хайх: Диплом, Гэрээ, Орчуулга..." 
                            class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all shadow-sm">
                    </div>
                </div>

                <!-- Right Side Actions -->
                <div class="flex items-center gap-4">
                    <a href="upload.php" class="hidden md:flex items-center gap-2 bg-gradient-to-r from-orange-500 to-red-500 text-white px-5 py-2 rounded-full font-bold text-sm shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50 transform hover:-translate-y-0.5 transition-all btn-shine">
                        <i class="fas fa-cloud-upload-alt"></i>
                        Файл оруулах
                    </a>

                    <div class="h-8 w-px bg-gray-200 hidden md:block mx-1"></div>

                    <button class="md:hidden p-2 text-gray-500 hover:bg-gray-100 rounded-lg" onclick="toggleMenu()">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    
                    <div class="hidden md:flex items-center gap-3">
                        <a href="login.php" class="text-gray-500 hover:text-brand-600 font-semibold text-sm px-3 py-2 transition-colors">Нэвтрэх</a>
                        <a href="register.php" class="bg-slate-700 hover:bg-slate-800 text-white text-sm font-bold px-5 py-2.5 rounded-lg transition-all shadow-sm border border-slate-700">
                            Бүртгүүлэх
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex flex-1 max-w-7xl mx-auto w-full">
        
        <!-- Sidebar Navigation -->
        <aside class="hidden lg:block w-64 flex-shrink-0 py-6 pr-6 h-[calc(100vh-64px)] sticky top-16 overflow-y-auto no-scrollbar">
            <!-- Mini Upload CTA -->
            <div class="mb-6 p-4 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 text-white shadow-xl">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                        <i class="fas fa-wallet text-yellow-400"></i>
                    </div>
                    <span class="font-bold text-sm">Мөнгө олох уу?</span>
                </div>
                <p class="text-xs text-gray-300 mb-3 leading-relaxed">Хэрэггүй файлаа устгах биш, бусдад зарж орлого ол!</p>
                <a href="upload.php" class="block w-full text-center bg-white text-gray-900 py-1.5 rounded-lg text-xs font-bold hover:bg-gray-100 transition">
                    Эхлэх &rarr;
                </a>
            </div>

            <!-- Menu Links -->
            <div class="space-y-1 mb-6">
                <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Үндсэн</h3>
                <a href="index.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-home w-5 text-center"></i> Нүүр хуудас
                </a>
                <a href="browse-files.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-folder-open w-5 text-center"></i> Файлууд
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-fire w-5 text-center text-orange-500"></i> Эрэлттэй
                </a>
            </div>

             <!-- Services Menu -->
             <div class="space-y-1 mb-8">
                <div class="flex items-center justify-between px-3 mb-2">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Freelance / Үйлчилгээ</h3>
                </div>
                <a href="services.php" class="flex items-center gap-3 px-3 py-2 text-brand-600 bg-brand-50 rounded-lg font-medium">
                    <div class="w-5 h-5 rounded bg-brand-100 text-brand-600 flex items-center justify-center transition">
                        <i class="fas fa-briefcase text-xs"></i>
                    </div>
                    Үйлчилгээнүүд
                </a>
                <a href="services.php?cat=design" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium group">
                    <div class="w-5 h-5 rounded bg-purple-100 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition">
                        <i class="fas fa-pen-nib text-xs"></i>
                    </div>
                    Дизайн & График
                </a>
                <a href="services.php?cat=web" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium group">
                    <div class="w-5 h-5 rounded bg-blue-100 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition">
                        <i class="fas fa-code text-xs"></i>
                    </div>
                    Вэб хөгжүүлэлт
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
            
            <!-- Breadcrumb -->
            <nav class="flex mb-6 text-xs text-gray-500 overflow-x-auto no-scrollbar whitespace-nowrap">
                <a href="index.php" class="hover:text-brand-600 transition-colors">Нүүр</a>
                <span class="mx-2 text-gray-300">/</span>
                <a href="services.php" class="hover:text-brand-600 transition-colors">Үйлчилгээ</a>
                <span class="mx-2 text-gray-300">/</span>
                <span class="font-medium text-gray-900"><?php echo $service['category']; ?></span>
            </nav>

            <!-- Title & Meta (Mobile only header) -->
            <div class="lg:hidden mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-3 leading-snug"><?php echo $service['title']; ?></h1>
                <div class="flex items-center gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <img src="<?php echo $service['author_avatar']; ?>" class="w-6 h-6 rounded-full object-cover">
                        <span class="font-medium text-gray-900"><?php echo $service['author']; ?></span>
                    </div>
                    <div class="flex items-center text-yellow-500">
                        <i class="fas fa-star mr-1"></i>
                        <span class="font-bold text-gray-900"><?php echo $service['rating']; ?></span>
                        <span class="text-gray-400 ml-1">(<?php echo $service['review_count']; ?>)</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Column: Media & Details (66% width) -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- Image Gallery -->
                    <div class="space-y-4">
                        <div class="aspect-video w-full bg-gray-100 rounded-2xl overflow-hidden border border-gray-200 group relative">
                            <!-- Main Image Placeholder -->
                            <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80" alt="Service Main Image" class="w-full h-full object-cover">
                            
                            <!-- Gallery Navigation Arrows (Optional) -->
                            <button class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/90 p-2 rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-opacity hover:bg-white text-gray-700">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/90 p-2 rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-opacity hover:bg-white text-gray-700">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        
                        <!-- Thumbnails -->
                        <div class="flex gap-4 overflow-x-auto pb-2 no-scrollbar">
                            <button class="w-20 h-20 flex-shrink-0 rounded-xl border-2 border-brand-500 overflow-hidden p-0.5">
                                <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?ixlib=rb-1.2.1&auto=format&fit=crop&w=150&q=80" class="w-full h-full object-cover rounded-lg">
                            </button>
                            <button class="w-20 h-20 flex-shrink-0 rounded-xl border border-gray-200 overflow-hidden hover:border-gray-300">
                                <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?ixlib=rb-1.2.1&auto=format&fit=crop&w=150&q=80" class="w-full h-full object-cover">
                            </button>
                            <button class="w-20 h-20 flex-shrink-0 rounded-xl border border-gray-200 overflow-hidden hover:border-gray-300">
                                <img src="https://images.unsplash.com/photo-1554224154-260327c00c19?ixlib=rb-1.2.1&auto=format&fit=crop&w=150&q=80" class="w-full h-full object-cover">
                            </button>
                        </div>
                    </div>

                    <!-- Description Section -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 md:p-8 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Үйлчилгээний тухай</h2>
                        <div class="prose max-w-none text-gray-600 text-sm">
                            <?php echo $service['description']; ?>
                        </div>
                        
                        <!-- Service Attributes -->
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-6 mt-8 pt-8 border-t border-gray-100">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Файлын төрөл</p>
                                <p class="font-medium text-gray-900 text-sm">PDF, Excel, Word</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Хэл</p>
                                <p class="font-medium text-gray-900 text-sm">Монгол, Англи</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Засвар хийх</p>
                                <p class="font-medium text-gray-900 text-sm">2 удаа үнэгүй</p>
                            </div>
                        </div>
                    </div>

                    <!-- Author Profile Card -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 md:p-8 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Гүйцэтгэгч</h2>
                        <div class="flex flex-col sm:flex-row gap-6 items-start">
                            <div class="w-20 h-20 rounded-full bg-gray-100 flex-shrink-0 overflow-hidden border border-gray-200">
                                <img src="<?php echo $service['author_avatar']; ?>" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-lg font-bold text-gray-900 hover:text-brand-600 cursor-pointer"><?php echo $service['author']; ?></h3>
                                    <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2.5 py-1 rounded-full border border-green-200">
                                        Online
                                    </span>
                                </div>
                                <p class="text-gray-600 text-sm mb-4 line-clamp-2">Санхүүгийн мэргэжлээр 10 жил ажилласан туршлагатай. Бүх төрлийн тайлан тооцоог чанарын өндөр түвшинд хийж гүйцэтгэнэ.</p>
                                <div class="flex gap-3">
                                    <a href="profile.php" class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">Профайл үзэх</a>
                                    <button class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">Зурвас бичих</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Author Stats -->
                        <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-100">
                            <div class="text-center">
                                <p class="text-lg font-bold text-gray-900">4.9</p>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Үнэлгээ</p>
                            </div>
                            <div class="text-center border-l border-gray-200">
                                <p class="text-lg font-bold text-gray-900">142</p>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Захиалга</p>
                            </div>
                            <div class="text-center border-l border-gray-200">
                                <p class="text-lg font-bold text-gray-900">100%</p>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Хариулах</p>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews Section -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 md:p-8 shadow-sm" id="reviews">
                        <div class="flex items-center justify-between mb-8">
                            <h2 class="text-xl font-bold text-gray-900">Сэтгэгдэл (<?php echo $service['review_count']; ?>)</h2>
                            <div class="flex items-center gap-2">
                                <div class="flex text-yellow-400 text-sm">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                                <span class="font-bold text-gray-900"><?php echo $service['rating']; ?></span>
                            </div>
                        </div>

                        <!-- Single Review -->
                        <div class="space-y-6">
                            <div class="flex gap-4 items-start">
                                <div class="w-10 h-10 rounded-full bg-gray-200 flex-shrink-0 flex items-center justify-center text-gray-500 font-bold text-xs">
                                    B
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <h4 class="font-bold text-gray-900 text-sm">Boldoo User</h4>
                                        <span class="text-xs text-gray-400">2 өдрийн өмнө</span>
                                    </div>
                                    <div class="flex text-yellow-400 text-xs mb-2">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                    </div>
                                    <p class="text-gray-600 text-xs leading-relaxed">Маш хурдан, чанартай хийж өгсөн. Баярлалаа!</p>
                                </div>
                            </div>
                            <hr class="border-gray-100">
                             <div class="flex gap-4 items-start">
                                <div class="w-10 h-10 rounded-full bg-gray-200 flex-shrink-0 flex items-center justify-center text-gray-500 font-bold text-xs">
                                    S
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <h4 class="font-bold text-gray-900 text-sm">Saraa</h4>
                                        <span class="text-xs text-gray-400">1 долоо хоногийн өмнө</span>
                                    </div>
                                    <div class="flex text-yellow-400 text-xs mb-2">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                                    </div>
                                    <p class="text-gray-600 text-xs leading-relaxed">Дажгүй шүү. Гэхдээ бага зэрэг удаж байж хариу өгсөн.</p>
                                </div>
                            </div>
                        </div>
                        
                        <button class="w-full mt-6 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
                            Бүх сэтгэгдлийг харах
                        </button>
                    </div>

                    <!-- FAQ -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 md:p-8 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Түгээмэл асуулт</h2>
                        <div class="space-y-4">
                            <details class="group [&_summary::-webkit-details-marker]:hidden">
                                <summary class="flex items-center justify-between cursor-pointer rounded-lg bg-gray-50 p-4 text-gray-900 font-medium hover:bg-gray-100 transition-colors text-sm">
                                    Тайлан гарахад хэр хугацаа шаардах вэ?
                                    <span class="shrink-0 ml-1.5 p-1.5 text-gray-900 bg-white rounded-full group-open:-rotate-180 transition-transform">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </span>
                                </summary>
                                <div class="mt-4 px-4 leading-relaxed text-gray-600 text-sm">
                                    Бичиг баримт бүрэн бол ажлын 2-3 хоногт багтаан гаргаж өгнө. Яаралтай горимоор 24 цагийн дотор гаргах боломжтой (нэмэлт төлбөртэй).
                                </div>
                            </details>
                            <details class="group [&_summary::-webkit-details-marker]:hidden">
                                <summary class="flex items-center justify-between cursor-pointer rounded-lg bg-gray-50 p-4 text-gray-900 font-medium hover:bg-gray-100 transition-colors text-sm">
                                    НӨАТ-ын баримт өгөх үү?
                                    <span class="shrink-0 ml-1.5 p-1.5 text-gray-900 bg-white rounded-full group-open:-rotate-180 transition-transform">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </span>
                                </summary>
                                <div class="mt-4 px-4 leading-relaxed text-gray-600 text-sm">
                                    Тиймээ, байгууллагын нэр дээр и-баримт шивж өгөх боломжтой.
                                </div>
                            </details>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Sidebar (33% width) - Sticky on Desktop -->
                <div class="lg:col-span-1">
                    <div class="sticky top-24 space-y-6">
                        
                        <!-- Desktop Title (Hidden on mobile) -->
                        <div class="hidden lg:block">
                            <h1 class="text-xl font-bold text-gray-900 leading-snug mb-2"><?php echo $service['title']; ?></h1>
                            <div class="flex items-center gap-2 text-sm mb-4">
                                <div class="flex text-yellow-500">
                                    <i class="fas fa-star"></i>
                                    <span class="ml-1 font-bold text-gray-900"><?php echo $service['rating']; ?></span>
                                </div>
                                <span class="text-gray-300">|</span>
                                <span class="text-gray-500"><?php echo $service['views']; ?> үзсэн</span>
                            </div>
                        </div>

                        <!-- Price & Action Card -->
                        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-lg shadow-gray-100/50">
                            <div class="flex items-end gap-2 mb-6">
                                <span class="text-3xl font-extrabold text-gray-900"><?php echo $service['price']; ?></span>
                                <span class="text-gray-500 mb-1 font-medium text-xs">/ эхлэх үнэ</span>
                            </div>

                            <div class="space-y-3 mb-6">
                                <div class="flex items-center justify-between text-sm text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <i class="far fa-clock text-brand-600 w-4"></i> Хугацаа
                                    </div>
                                    <span class="font-semibold text-gray-900"><?php echo $service['delivery_days']; ?> хоног</span>
                                </div>
                                <div class="flex items-center justify-between text-sm text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-sync-alt text-brand-600 w-4"></i> Засвар
                                    </div>
                                    <span class="font-semibold text-gray-900">2 удаа</span>
                                </div>
                                <div class="flex items-center justify-between text-sm text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-tasks text-brand-600 w-4"></i> Хүлээгдэж буй
                                    </div>
                                    <span class="font-semibold text-gray-900"><?php echo $service['orders_queue']; ?> захиалга</span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <button class="w-full py-3.5 bg-brand-600 text-white rounded-xl font-bold hover:bg-brand-700 transition-all shadow-lg shadow-brand-500/30 flex items-center justify-center gap-2 transform active:scale-[0.98] btn-shine">
                                    Захиалах <i class="fas fa-arrow-right text-xs"></i>
                                </button>
                                <button class="w-full py-3.5 bg-white border border-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                                    <i class="far fa-heart"></i> Хадгалах
                                </button>
                            </div>
                            
                            <div class="mt-4 text-center">
                                <p class="text-xs text-gray-400">Төлбөрийг Filezone батлан даана. Ажил дууссаны дараа мөнгийг шилжүүлнэ.</p>
                            </div>
                        </div>

                        <!-- Safety Card -->
                        <div class="bg-slate-50 rounded-2xl border border-gray-200 p-5">
                            <h3 class="font-bold text-gray-900 mb-3 text-sm">Баталгаат үйлчилгээ</h3>
                            <ul class="space-y-3 text-sm text-gray-600">
                                <li class="flex items-start gap-2.5">
                                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                                    <span>Баталгаажсан хэрэглэгч</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <i class="fas fa-shield-alt text-brand-500 mt-0.5"></i>
                                    <span>Төлбөрийн аюулгүй байдал</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <i class="fas fa-headset text-purple-500 mt-0.5"></i>
                                    <span>24/7 тусламж үйлчилгээ</span>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- Report Button -->
                        <button class="w-full flex items-center justify-center gap-2 text-sm text-gray-400 hover:text-red-500 transition-colors py-2">
                            <i class="fas fa-flag"></i> Зөрчил мэдээлэх
                        </button>

                    </div>
                </div>

            </div>

            <!-- Related Services (Bottom Section) -->
            <div class="mt-16 pt-10 border-t border-gray-200 mb-10">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-xl font-bold text-gray-900">Төстэй үйлчилгээнүүд</h2>
                    <a href="#" class="text-brand-600 font-medium hover:text-brand-700 flex items-center gap-1 text-sm">
                        Бүгдийг үзэх <i class="fas fa-chevron-right text-xs"></i>
                    </a>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Related Card 1 -->
                    <div class="group bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <div class="relative aspect-[4/3] overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1542744173-8e7e53415bb0?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </div>
                        <div class="p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <img src="assets/images/default-avatar.png" class="w-5 h-5 rounded-full bg-gray-200">
                                <span class="text-xs font-medium text-gray-500">Bat-Erdene</span>
                            </div>
                            <h3 class="font-bold text-gray-900 line-clamp-2 mb-3 group-hover:text-brand-600 transition-colors text-sm">Бизнес төлөвлөгөө боловсруулна</h3>
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                <div class="flex items-center gap-1 text-yellow-500 text-xs font-bold">
                                    <i class="fas fa-star"></i> 5.0
                                </div>
                                <span class="font-bold text-gray-900 text-sm">200,000₮</span>
                            </div>
                        </div>
                    </div>
                     <!-- Related Card 2 -->
                     <div class="group bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <div class="relative aspect-[4/3] overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </div>
                        <div class="p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <img src="assets/images/default-avatar.png" class="w-5 h-5 rounded-full bg-gray-200">
                                <span class="text-xs font-medium text-gray-500">Sarnai</span>
                            </div>
                            <h3 class="font-bold text-gray-900 line-clamp-2 mb-3 group-hover:text-brand-600 transition-colors text-sm">Excel програм дээр автоматжуулалт хийнэ</h3>
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                <div class="flex items-center gap-1 text-yellow-500 text-xs font-bold">
                                    <i class="fas fa-star"></i> 4.5
                                </div>
                                <span class="font-bold text-gray-900 text-sm">50,000₮</span>
                            </div>
                        </div>
                    </div>
                     <!-- Related Card 3 -->
                     <div class="group bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <div class="relative aspect-[4/3] overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </div>
                        <div class="p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <img src="assets/images/default-avatar.png" class="w-5 h-5 rounded-full bg-gray-200">
                                <span class="text-xs font-medium text-gray-500">Tuya</span>
                            </div>
                            <h3 class="font-bold text-gray-900 line-clamp-2 mb-3 group-hover:text-brand-600 transition-colors text-sm">НДШ болон ХХОАТ-ын тайлан гаргана</h3>
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                <div class="flex items-center gap-1 text-yellow-500 text-xs font-bold">
                                    <i class="fas fa-star"></i> 5.0
                                </div>
                                <span class="font-bold text-gray-900 text-sm">80,000₮</span>
                            </div>
                        </div>
                    </div>
                     <!-- Related Card 4 -->
                     <div class="group bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <div class="relative aspect-[4/3] overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1557804506-669a67965ba0?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </div>
                        <div class="p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <img src="assets/images/default-avatar.png" class="w-5 h-5 rounded-full bg-gray-200">
                                <span class="text-xs font-medium text-gray-500">User123</span>
                            </div>
                            <h3 class="font-bold text-gray-900 line-clamp-2 mb-3 group-hover:text-brand-600 transition-colors text-sm">PowerPoint танилцуулга бэлтгэнэ</h3>
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                <div class="flex items-center gap-1 text-yellow-500 text-xs font-bold">
                                    <i class="fas fa-star"></i> 4.0
                                </div>
                                <span class="font-bold text-gray-900 text-sm">40,000₮</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'includes/footer.php' ?>