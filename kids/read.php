<?php
// --------------------------------------------------------------------------
// MOCK DATA & LOGIC (Өгөгдлийн баазтай холбох хэсэг)
// --------------------------------------------------------------------------

// URL-аас номын ID болон хуудасны дугаарыг авах
$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 1;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Жишээ өгөгдөл (DB-ээс иймэрхүү бүтэцтэй ирнэ гэж тооцов)
$book_data = [
    1 => [
        'title' => 'Мэлхий гүнж', 
        'author' => 'Ардын үлгэр', 
        'total_pages' => 5, 
        'category' => 'fairytale',
        'content' => [
            1 => "Эрт урьд цагт нэгэн хаан амьдран суудаг байжээ. Хаан гурван хүүтэй юм гэнэ. Хөвгүүдээ эрийн цээнд хүрэхэд хаан тэднийг дуудаад: <br><br>— Хүүхдүүд минь, та нар одоо өөрсдөдөө эхнэр сонгох цаг боллоо. Тиймээс сум тавь. Хэний сум хаана тусна, тэндээс сүйт бүсгүйгээ олж ав гэж зарлиг буулгажээ.",
            2 => "Ингээд ах дүү гурав эцгийнхээ өгсөн нум сумыг аваад, өргөн тал руу гарч гэнэ. <br><br>Ууган хүүгийнх нь сум язгууртны хашаанд тусч, дунд хүүгийн сум худалдаачны хашаанд тусчээ. Харин отгон хүү Иван Царевичийн сум алс хол намагт унасан байв.",
            3 => "Иван Царевич сумаа хайсаар намагт иртэл нэгэн ногоон мэлхий сумыг нь амандаа зуучихсан сууж байхыг олж харав. Иван Царевич гайхан: <br><br>— Мэлхий минь, сумыг минь өгөөч гэхэд мэлхий хүний хэлээр: <br>— Намайг эхнэрээ болгож авбал өгье гэжээ.",
            4 => "Иван Царевич ихэд гунигласан ч эцгийн зарлиг тул мэлхийг авч харьжээ. Хаан ах дүү гурвын сүйт бүсгүйчүүдийг хараад инээсэнгүй, харин бэрүүдийнхээ уран үйлийг шалгахаар шийджээ.",
            5 => "Маргааш өглөө нь хаан бэрүүддээ цамц оёж өгөхийг тушаав. Иван Царевич гэртээ ирээд гунигтайгаар толгойгоо гудайлган суухад мэлхий асуув: <br><br>— Иван Царевич, юунд ингэтлээ гуниглана вэ? Хаан аав чинь хэцүү даалгавар өгөө юу?"
        ]
    ],
    2 => [
        'title' => 'Нарны аймаг', 
        'author' => 'Шинжлэх ухаан', 
        'total_pages' => 3, 
        'category' => 'science',
        'content' => [
            1 => "Нарны аймаг нь Нар, түүнийг тойрон эргэдэг найман гараг, тэдгээрийн дагуулууд, мөн одой гарагууд, сүүлт одод, солир зэрэг олон жижиг биетүүдээс бүрдэнэ. <br><br>Нар бол манай аймгийн төвд орших шар одой од бөгөөд бүх массын 99.86%-ийг эзэлдэг.",
            2 => "Буд гараг бол Наранд хамгийн ойр оршдог, хамгийн жижиг гараг юм. Түүний гадаргуу нь тогоонуудаар дүүрэн бөгөөд сартай их төстэй харагддаг. <br><br>Харин Сугар гараг нь дэлхийтэй хэмжээгээрээ ойролцоо боловч маш зузаан агаар мандалтай тул гадаргуу дээрх температур нь маш өндөр байдаг.",
            3 => "Дэлхий бол амьдрал оршин буй цорын ганц мэдэгдэж буй гараг юм. Гадаргуугийнх нь 71%-ийг ус эзэлдэг учраас сансраас хөх өнгөтэй харагддаг."
        ]
    ],
    'default' => [
        'title' => 'Номын нэр', 
        'author' => 'Зохиолч', 
        'total_pages' => 1, 
        'category' => 'general',
        'content' => [1 => "Энэ номын агуулга одоогоор ороогүй байна."]
    ]
];

$book = isset($book_data[$book_id]) ? $book_data[$book_id] : $book_data['default'];

// Хуудасны хязгаарлалт
if ($current_page < 1) $current_page = 1;
if ($current_page > $book['total_pages']) $current_page = $book['total_pages'];

// Navigation URLs
$prev_page = $current_page - 1;
$next_page = $current_page + 1;

// Зургийн зам
$image_src = "https://placehold.co/800x600/e2e8f0/475569?text=Page+{$current_page}"; 
// Текст агуулга
$page_text = isset($book['content'][$current_page]) ? $book['content'][$current_page] : "Текст олдсонгүй.";

?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $book['title']; ?> - Filezone Kids</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        story: ['Nunito', 'sans-serif'], // Font for story text
                    },
                    colors: {
                        primary: '#4F46E5', 
                        secondary: '#0EA5E9', 
                        dark: '#1F2937',
                        light: '#F3F4F6',
                        paper: '#FFFBF0', // Warm paper color for text background
                    },
                    typography: {
                        DEFAULT: {
                            css: {
                                color: '#333',
                                maxWidth: 'none',
                            },
                        },
                    },
                }
            }
        }
    </script>
    <style>
        body { background-color: #F8FAFC; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        
        .sidebar-link:hover, .sidebar-link.active {
            background-color: #F0F9FF;
            color: #0284C7;
            border-right: 3px solid #0284C7;
        }
        
        /* Reader Layout Styles */
        .reader-container {
            height: calc(100vh - 4rem); 
            overflow: hidden;
        }
        
        .split-view {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        @media (min-width: 768px) {
            .split-view {
                flex-direction: row;
            }
        }

        .image-pane {
            background-color: #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .text-pane {
            background-color: #FFFBF0; /* Warm reading color */
            overflow-y: auto;
            position: relative;
        }

        .book-page-image {
            max-height: 90%;
            max-width: 90%;
            object-fit: contain;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
        }

        /* Fullscreen override */
        body.fullscreen-mode .sidebar,
        body.fullscreen-mode .main-header {
            display: none !important;
        }
        body.fullscreen-mode .reader-container {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 50;
        }
    </style>
</head>
<body class="font-sans text-gray-800 antialiased overflow-hidden">

    <div class="flex h-screen bg-gray-50">
        
        <!-- SIDEBAR -->
        <aside class="sidebar w-64 bg-white shadow-xl hidden md:flex flex-col z-20">
            <div class="h-16 flex items-center px-6 border-b border-gray-100">
                <a href="index.php" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center text-white">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-800 tracking-tight">Filezone <span class="text-sky-500">Kids</span></span>
                </a>
            </div>
            <div class="flex-1 overflow-y-auto py-4">
                <nav class="space-y-1">
                    <a href="filezone-kids.html" class="sidebar-link flex items-center px-6 py-3 text-gray-600 hover:text-sky-600 group">
                        <i class="fas fa-arrow-left w-5 h-5 mr-3 text-gray-400 group-hover:text-sky-500"></i>
                        <span class="font-medium">Буцах</span>
                    </a>
                    
                    <div class="px-6 py-4 mt-2">
                        <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                            <h4 class="font-bold text-blue-800 mb-1 text-sm"><?php echo $book['title']; ?></h4>
                            <p class="text-xs text-blue-600 mb-3"><?php echo $book['author']; ?></p>
                            
                            <div class="flex justify-between items-center text-xs text-blue-500 mb-1">
                                <span>Явц:</span>
                                <span><?php echo floor(($current_page / $book['total_pages']) * 100); ?>%</span>
                            </div>
                            <div class="w-full bg-blue-200 rounded-full h-1.5 mb-3">
                                <div class="bg-blue-500 h-1.5 rounded-full transition-all duration-500" style="width: <?php echo ($current_page / $book['total_pages']) * 100; ?>%"></div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 mt-4">
                                <button onclick="changeFontSize('small')" class="p-2 bg-white rounded border border-blue-200 hover:bg-blue-100 text-xs">A-</button>
                                <button onclick="changeFontSize('large')" class="p-2 bg-white rounded border border-blue-200 hover:bg-blue-100 text-sm font-bold">A+</button>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
        </aside>

        <!-- MAIN CONTENT WRAPPER -->
        <div class="flex-1 flex flex-col overflow-hidden relative">
            
            <!-- HEADER -->
            <header class="main-header h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 shadow-sm z-10">
                <div class="flex items-center">
                    <button class="md:hidden text-gray-500 hover:text-gray-700 mr-4">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-lg font-bold text-gray-800 truncate max-w-xs md:max-w-md">
                        <?php echo $book['title']; ?> <span class="text-gray-400 font-normal text-sm ml-2">| Хуудас <?php echo $current_page; ?></span>
                    </h1>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="toggleFullscreen()" class="p-2 text-gray-500 hover:text-sky-600 rounded-lg hover:bg-gray-100 transition" title="Бүтэн дэлгэц">
                        <i class="fas fa-expand"></i>
                    </button>
                    <a href="filezone-kids.html" class="p-2 text-gray-500 hover:text-red-500 rounded-lg hover:bg-gray-100 transition" title="Хаах">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </header>

            <!-- READER AREA (Split View) -->
            <main class="reader-container">
                <div class="split-view">
                    
                    <!-- LEFT: Image Pane (50% on desktop, Top on mobile) -->
                    <div class="image-pane w-full md:w-1/2 h-1/2 md:h-full relative group">
                        <!-- Loading State -->
                        <div id="loader" class="absolute inset-0 flex items-center justify-center bg-gray-200 z-10 hidden">
                            <i class="fas fa-spinner fa-spin text-4xl text-sky-500"></i>
                        </div>

                        <img src="<?php echo $image_src; ?>" alt="Page <?php echo $current_page; ?>" class="book-page-image shadow-2xl" id="pageImage" onload="hideLoader()">
                        
                        <!-- Image Overlay Nav (Desktop Only mostly) -->
                        <?php if ($prev_page >= 1): ?>
                        <a href="?book_id=<?php echo $book_id; ?>&page=<?php echo $prev_page; ?>" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white/50 hover:bg-white text-gray-700 w-10 h-10 rounded-full flex items-center justify-center shadow-md transition md:flex hidden">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($next_page <= $book['total_pages']): ?>
                        <a href="?book_id=<?php echo $book_id; ?>&page=<?php echo $next_page; ?>" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white/50 hover:bg-white text-gray-700 w-10 h-10 rounded-full flex items-center justify-center shadow-md transition md:flex hidden">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>

                    <!-- RIGHT: Text Pane (50% on desktop, Bottom on mobile) -->
                    <div class="text-pane w-full md:w-1/2 h-1/2 md:h-full p-6 md:p-10 lg:p-12 overflow-y-auto">
                        <div class="max-w-prose mx-auto">
                            <!-- Page number indicator inside text -->
                            <span class="inline-block px-3 py-1 bg-sky-100 text-sky-700 rounded-full text-xs font-bold mb-6">
                                Хуудас <?php echo $current_page; ?>
                            </span>

                            <!-- The Story Text -->
                            <div id="story-content" class="font-story text-gray-800 leading-loose text-lg md:text-xl lg:text-2xl transition-all">
                                <?php echo $page_text; ?>
                            </div>

                            <!-- Mobile Navigation (Visible only on small screens inside text area) -->
                            <div class="flex justify-between mt-8 md:hidden border-t border-gray-200 pt-6">
                                <?php if ($prev_page >= 1): ?>
                                    <a href="?book_id=<?php echo $book_id; ?>&page=<?php echo $prev_page; ?>" class="text-sky-600 font-bold flex items-center gap-2">
                                        <i class="fas fa-arrow-left"></i> Өмнөх
                                    </a>
                                <?php else: ?>
                                    <span></span>
                                <?php endif; ?>

                                <?php if ($next_page <= $book['total_pages']): ?>
                                    <a href="?book_id=<?php echo $book_id; ?>&page=<?php echo $next_page; ?>" class="text-sky-600 font-bold flex items-center gap-2">
                                        Дараах <i class="fas fa-arrow-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </main>

            <!-- FOOTER TOOLBAR (Optional, can be removed if split view nav is enough) -->
            <div class="h-16 bg-white border-t border-gray-200 flex items-center justify-between px-6 shadow-lg z-20 md:flex hidden">
                 <!-- Previous Button -->
                 <div class="w-32">
                    <?php if ($prev_page >= 1): ?>
                        <a href="?book_id=<?php echo $book_id; ?>&page=<?php echo $prev_page; ?>" class="flex items-center gap-2 text-gray-600 hover:text-sky-600 font-medium px-3 py-2 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-arrow-left"></i> Өмнөх
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Slider -->
                <div class="flex-1 max-w-md mx-4">
                     <input type="range" min="1" max="<?php echo $book['total_pages']; ?>" value="<?php echo $current_page; ?>" 
                        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-sky-500"
                        onchange="window.location.href='?book_id=<?php echo $book_id; ?>&page='+this.value">
                </div>

                <!-- Next Button -->
                <div class="w-32 flex justify-end">
                    <?php if ($next_page <= $book['total_pages']): ?>
                        <a href="?book_id=<?php echo $book_id; ?>&page=<?php echo $next_page; ?>" class="flex items-center gap-2 text-white bg-sky-500 hover:bg-sky-600 font-medium px-4 py-2 rounded-lg shadow-sm transition transform hover:-translate-y-0.5">
                            Дараах <i class="fas fa-arrow-right"></i>
                        </a>
                    <?php else: ?>
                            <a href="filezone-kids.html" class="flex items-center gap-2 text-white bg-green-500 hover:bg-green-600 font-medium px-4 py-2 rounded-lg shadow-sm transition">
                            Дуусгах <i class="fas fa-check"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script>
        function toggleFullscreen() {
            document.body.classList.toggle('fullscreen-mode');
            const icon = document.querySelector('.fa-expand');
            if (document.body.classList.contains('fullscreen-mode')) {
                icon.classList.remove('fa-expand');
                icon.classList.add('fa-compress');
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                }
            } else {
                icon.classList.remove('fa-compress');
                icon.classList.add('fa-expand');
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }

        function hideLoader() {
            document.getElementById('loader').classList.add('hidden');
        }
        
        function changeFontSize(size) {
            const content = document.getElementById('story-content');
            if (size === 'large') {
                content.classList.remove('text-lg', 'md:text-xl');
                content.classList.add('text-2xl', 'md:text-3xl');
            } else {
                content.classList.remove('text-2xl', 'md:text-3xl');
                content.classList.add('text-lg', 'md:text-xl');
            }
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                <?php if ($prev_page >= 1): ?>
                window.location.href = "?book_id=<?php echo $book_id; ?>&page=<?php echo $prev_page; ?>";
                <?php endif; ?>
            } else if (e.key === 'ArrowRight') {
                <?php if ($next_page <= $book['total_pages']): ?>
                window.location.href = "?book_id=<?php echo $book_id; ?>&page=<?php echo $next_page; ?>";
                <?php endif; ?>
            }
        });
    </script>
</body>
</html>