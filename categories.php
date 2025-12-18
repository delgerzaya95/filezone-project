<?php
// Сонгогдсон ангиллыг авах (Default нь 'contracts')
$current_cat = isset($_GET['cat']) ? $_GET['cat'] : 'contracts';

// Ангилалуудын өгөгдөл (Mock Data)
$categories = [
    'contracts' => [
        'id' => 'contracts',
        'name' => 'Гэрээ & Загвар',
        'icon' => 'fas fa-file-contract',
        'color' => 'blue',
        'subs' => ['Хөдөлмөрийн гэрээ', 'Түрээсийн гэрээ', 'Зээлийн гэрээ', 'Ажил гүйцэтгэх гэрээ', 'Бэлэглэлийн гэрээ', 'Хамтран ажиллах', 'Нууцлалын гэрээ', 'Бусад албан бичиг']
    ],
    'education' => [
        'id' => 'education',
        'name' => 'Диплом & Курс',
        'icon' => 'fas fa-graduation-cap',
        'color' => 'green',
        'subs' => ['Бакалаврын диплом', 'Магистрын ажил', 'Курсын ажил', 'Бие даалт', 'Лабораторын ажил', 'Эссэ', 'Илтгэл']
    ],
    'books' => [
        'id' => 'books',
        'name' => 'Ном & Товхимол',
        'icon' => 'fas fa-book',
        'color' => 'purple',
        'subs' => ['Уран зохиол', 'Бизнес хөгжил', 'Түүх', 'Шинжлэх ухаан', 'Гадаад хэл', 'Хүүхдийн ном']
    ],
    'finance' => [
        'id' => 'finance',
        'name' => 'Санхүү & Excel',
        'icon' => 'fas fa-calculator',
        'color' => 'red',
        'subs' => ['Санхүүгийн тайлан', 'Татварын тайлан', 'Цалингийн хүснэгт', 'Төсвийн тооцоолол', 'Excel загварууд', 'Бизнес төлөвлөгөө']
    ],
    'design' => [
        'id' => 'design',
        'name' => 'Дизайн & График',
        'icon' => 'fas fa-palette',
        'color' => 'orange',
        'subs' => ['Лого', 'Нэрийн хуудас', 'Сошиал постер', 'Web UI', 'Brochure', 'Banner', 'Vector дүрс']
    ],
    'marketing' => [
        'id' => 'marketing',
        'name' => 'Маркетинг',
        'icon' => 'fas fa-bullhorn',
        'color' => 'yellow',
        'subs' => ['Маркетинг төлөвлөгөө', 'Сошиал контент төлөвлөгөө', 'Судалгаа', 'Стратеги']
    ]
];

// Тухайн сонгогдсон ангилалд харгалзах жишээ файлууд (Mock Data with Images for Card Style)
function getMockFiles($catId) {
    $files = [];
    
    // Загварын зурагнууд (Unsplash)
    $images = [
        'contracts' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
        'education' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
        'books' => 'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
        'finance' => 'https://images.unsplash.com/photo-1554224155-984063584d45?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
        'design' => 'https://images.unsplash.com/photo-1626785774573-4b7993125651?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
        'marketing' => 'https://images.unsplash.com/photo-1533750516457-a7f992034fec?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
    ];

    $titles = [
        'contracts' => ['Орон сууц түрээсийн гэрээ 2025', 'Ажилтантай байгуулах хөдөлмөрийн гэрээ', 'Зээлийн гэрээний загвар', 'Нууцлалын гэрээ (NDA)', 'Бараа нийлүүлэх гэрээ', 'Хамтран ажиллах гэрээ'],
        'education' => ['Маркетингийн менежмент дипломын ажил', 'Санхүүгийн шинжилгээ курсын ажил', 'Макро эдийн засаг бие даалт', 'Түүхийн эссэ: Чингис хаан', 'Англи хэлний дүрэм (Presentation)', 'Сэтгэл судлалын судалгаа'],
        'books' => ['Бяцхан хунтайж (Монгол)', 'Санхүүгийн эрх чөлөөнд хүрэх нь', 'Англи хэлний 3000 үг', 'Python програмчлалын үндэс', 'Маркетингийн 22 хууль', 'Икигай: Японы нууц'],
        'finance' => ['Цалингийн хүснэгт (Автомат бодолттой)', 'Өрхийн төсвийн загвар', 'Татварын тайлангийн маягт', 'Бизнес төлөвлөгөөний Excel загвар', 'Cash Flow тооцоолол', 'Баланс тайлан'],
        'design' => ['Instagram Post Templates (PSD)', 'Компанийн танилцуулга загвар', 'Лого багц (Vector)', 'Нэрийн хуудасны загвар', 'Web UI Kit (Figma)', 'PowerPoint Presentation'],
        'marketing' => ['Сошиал медиа контент төлөвлөгөө', 'Маркетингийн жилийн төлөвлөгөө', 'SWOT шинжилгээний загвар', 'Хэрэглэгчийн судалгааны асуулга', 'Brand Guidelines Template', 'Facebook Ads Strategy']
    ];

    $types = ['docx' => 'blue', 'xlsx' => 'green', 'pdf' => 'red', 'pptx' => 'orange', 'zip' => 'gray'];
    $authors = ['Батаа', 'Цэцгээ', 'Болд', 'Хулан', 'Тэмүүлэн', 'Сараа'];
    $avatars = ['Felix', 'Aneka', 'John', 'Sarnai', 'Bat', 'Tuya']; // Dicebear seeds

    $catTitles = $titles[$catId] ?? $titles['contracts'];
    $catImage = $images[$catId] ?? $images['contracts'];

    foreach ($catTitles as $index => $title) {
        // Random file type
        $type = 'docx';
        if ($catId == 'finance') $type = 'xlsx';
        if ($catId == 'design') $type = 'zip';
        if ($catId == 'books') $type = 'pdf';
        
        $files[] = [
            'id' => $index + 1,
            'title' => $title,
            'type' => $type,
            'image' => $catImage,
            'author' => $authors[$index % count($authors)],
            'avatar_seed' => $avatars[$index % count($avatars)],
            'price' => ($index == 0) ? '15,000₮' : (($index == 1) ? 'Үнэгүй' : rand(5, 50) . ',000₮'),
            'rating' => rand(40, 50) / 10,
            'review_count' => rand(5, 100)
        ];
    }
    return $files;
}

$active_category = $categories[$current_cat] ?? $categories['contracts'];
$active_files = getMockFiles($current_cat);
?>
<?php 
// Хуудасны гарчиг тохируулах
$page_title = "Ангилал - Filezone.mn";
include 'includes/header.php'; 
?>

    <div class="w-full max-w-[1400px] mx-auto flex items-start">
        
        <?php include 'includes/sidebar.php' ?>

        <!-- Main Content -->
        <main class="flex-1 py-6 px-4 lg:px-8 min-w-0">
            
            <!-- Breadcrumb -->
            <nav class="flex mb-4 text-xs text-gray-500">
                <ol class="flex items-center space-x-2">
                    <li><a href="index.php" class="hover:text-brand-600">Нүүр</a></li>
                    <li><span class="text-gray-300">/</span></li>
                    <li class="font-medium text-gray-900">Бүх ангилал</li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                        Файлын ангилал
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Та өөрт хэрэгтэй файлын төрлийг сонгоод, дэлгэрэнгүй хайлт хийгээрэй.</p>
                </div>
                
                <!-- Sort Dropdown (Added from Services) -->
                <div class="flex items-center gap-2">
                    <label for="sort" class="text-sm text-gray-500 hidden sm:block">Эрэмбэлэх:</label>
                    <select id="sort" class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-brand-500 focus:border-brand-500 block p-2.5 outline-none cursor-pointer hover:border-gray-300 transition">
                        <option>Санал болгох</option>
                        <option>Шинэ нь эхэндээ</option>
                        <option>Үнэлгээ өндөр</option>
                        <option>Үнэ: Багаас их рүү</option>
                    </select>
                </div>
            </div>

            <!-- MAIN CATEGORIES GRID (Original Category Selection) -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                <?php foreach($categories as $key => $cat): ?>
                    <?php 
                        $isActive = ($current_cat == $key); 
                        $bgClass = $isActive ? "border-{$cat['color']}-500 ring-2 ring-{$cat['color']}-500 ring-offset-2 bg-{$cat['color']}-50" : "bg-white border-gray-200 hover:border-{$cat['color']}-300 hover:shadow-md";
                        $iconBg = $isActive ? "bg-{$cat['color']}-500 text-white" : "bg-{$cat['color']}-50 text-{$cat['color']}-600";
                    ?>
                    <a href="categories.php?cat=<?php echo $key; ?>" class="<?php echo $bgClass; ?> p-4 rounded-2xl border transition-all duration-300 text-center group h-full flex flex-col items-center justify-center">
                        <div class="w-12 h-12 rounded-full <?php echo $iconBg; ?> flex items-center justify-center mb-3 text-xl group-hover:scale-110 transition-transform duration-300 shadow-sm">
                            <i class="<?php echo $cat['icon']; ?>"></i>
                        </div>
                        <span class="text-sm font-bold text-gray-800 group-hover:text-<?php echo $cat['color']; ?>-600 transition-colors"><?php echo $cat['name']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- SUBCATEGORIES PILLS (Styled somewhat like services tabs) -->
            <div class="flex flex-wrap gap-2 mb-8 overflow-x-auto no-scrollbar pb-2">
                <button class="px-4 py-2 text-sm font-bold text-white bg-gray-800 rounded-full shadow-md transition-transform hover:scale-105">
                    Бүгд
                </button>
                <?php foreach($active_category['subs'] as $sub): ?>
                    <a href="browse-files.php?cat=<?php echo $active_category['id']; ?>&sub=<?php echo urlencode($sub); ?>" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-full hover:bg-gray-50 hover:border-gray-300 transition-colors whitespace-nowrap">
                        <?php echo $sub; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- FILE GRID (Services Style) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                
                <?php foreach($active_files as $file): ?>
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group service-card">
                        <!-- Image & Badge -->
                        <div class="h-40 bg-gray-200 relative overflow-hidden">
                            <img src="<?php echo $file['image']; ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <!-- File Type Badge -->
                            <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded text-xs font-bold text-gray-700 shadow-sm uppercase">
                                <i class="fas fa-file-<?php echo ($file['type'] == 'zip' ? 'archive' : $file['type']); ?> text-brand-500 text-[10px] mr-1"></i> <?php echo $file['type']; ?>
                            </div>
                        </div>
                        
                        <!-- Content -->
                        <div class="p-4 pt-2 relative">
                            <!-- Avatar & Price -->
                            <div class="flex justify-between items-start">
                                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $file['avatar_seed']; ?>" class="w-10 h-10 rounded-full border-2 border-white bg-gray-100 -mt-7 object-cover shadow-sm">
                                <div class="text-right">
                                    <span class="block <?php echo ($file['price'] == 'Үнэгүй' ? 'text-green-600' : 'text-brand-600'); ?> font-bold text-lg">
                                        <?php echo $file['price']; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Title -->
                            <h3 class="font-bold text-gray-800 mt-2 line-clamp-2 text-sm h-10 leading-relaxed group-hover:text-brand-600 transition-colors">
                                <?php echo $file['title']; ?>
                            </h3>

                            <!-- Rating -->
                            <div class="flex items-center gap-2 mt-2 text-xs text-gray-500">
                                <i class="fas fa-star text-yellow-400"></i> <?php echo $file['rating']; ?> (<?php echo $file['review_count']; ?>)
                            </div>

                            <!-- Hover Action Button -->
                            <div class="service-action absolute inset-x-0 bottom-0 p-4 bg-white/95 backdrop-blur opacity-0 translate-y-2 transition-all duration-300 flex items-center justify-center">
                                <a href="file-details.php?id=<?php echo $file['id']; ?>" class="w-full bg-brand-600 text-white text-center py-2 rounded-lg font-medium text-sm hover:bg-brand-700 shadow-lg shadow-brand-500/20">
                                    Дэлгэрэнгүй
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

            <!-- Pagination -->
            <div class="flex justify-center mt-10">
                <nav class="flex items-center gap-2">
                    <a href="#" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </a>
                    <a href="#" class="w-8 h-8 flex items-center justify-center rounded-lg bg-brand-600 text-white font-medium text-sm shadow-md shadow-brand-500/30">1</a>
                    <a href="#" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium text-sm transition">2</a>
                    <span class="px-2 text-gray-400">...</span>
                    <a href="#" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </a>
                </nav>
            </div>

            <?php include 'includes/footer.php'; ?>