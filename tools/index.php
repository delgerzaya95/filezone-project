<?php
// MOCK DATA: Хэрэгслүүдийн жагсаалт (Өгөгдлийн баазтай холбох үед эндээс татна)
$tools = [
    [
        'id' => 1,
        'title' => 'JPG-ээс PNG руу',
        'desc' => 'Зургийн форматыг чанарыг алдагдуулахгүйгээр хөрвүүлэх.',
        'category' => 'image',
        'icon' => 'fa-image',
        'color' => 'bg-blue-500',
        'usage' => '15.2k'
    ],
    [
        'id' => 2,
        'title' => 'PDF нэгтгэх',
        'desc' => 'Олон PDF файлыг нэг файл болгон нэгтгэх хэрэгсэл.',
        'category' => 'pdf',
        'icon' => 'fa-file-pdf',
        'color' => 'bg-red-500',
        'usage' => '42k'
    ],
    [
        'id' => 3,
        'title' => 'Үг тоологч',
        'desc' => 'Текст дэх үг, үсэг, өгүүлбэрийн тоог гаргах.',
        'category' => 'text',
        'icon' => 'fa-align-left',
        'color' => 'bg-slate-500',
        'usage' => '8.5k'
    ],
    [
        'id' => 4,
        'title' => 'QR Код үүсгэгч',
        'desc' => 'Вэбсайт, текст, wifi холболтын QR код үүсгэх.',
        'category' => 'util',
        'icon' => 'fa-qrcode',
        'color' => 'bg-gray-800',
        'usage' => '21k'
    ],
    [
        'id' => 5,
        'title' => 'Нууц үг зохиох',
        'desc' => 'Хүчирхэг, таахад бэрх нууц үг санамсаргүйгээр үүсгэх.',
        'category' => 'security',
        'icon' => 'fa-key',
        'color' => 'bg-green-500',
        'usage' => '12k'
    ],
    [
        'id' => 6,
        'title' => 'PDF-ээс Word руу',
        'desc' => 'PDF файлыг засах боломжтой Word файл болгох.',
        'category' => 'pdf',
        'icon' => 'fa-file-word',
        'color' => 'bg-blue-700',
        'usage' => '35k'
    ],
    [
        'id' => 7,
        'title' => 'Интернет хурд',
        'desc' => 'Таны интернетийн хурдыг шалгах хэрэгсэл.',
        'category' => 'util',
        'icon' => 'fa-tachometer-alt',
        'color' => 'bg-violet-500',
        'usage' => '9k'
    ],
    [
        'id' => 8,
        'title' => 'Youtube Thumbnail',
        'desc' => 'Youtube бичлэгийн нүүр зургийг татах.',
        'category' => 'image',
        'icon' => 'fa-play',
        'color' => 'bg-red-600',
        'usage' => '5k'
    ]
];
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filezone Tools - Онлайн хэрэгслүүд</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#4F46E5', // Brand Blue
                        secondary: '#14B8A6', // Teal - Tools theme color
                        dark: '#1F2937',
                        light: '#F3F4F6'
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #F8FAFC; }
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Sidebar styling matches Kids page exactly */
        .sidebar-link {
            transition: all 0.2s;
        }
        .sidebar-link:hover, .sidebar-link.active {
            background-color: #F0FDFA; /* teal-50 */
            color: #0D9488; /* teal-600 */
            border-right: 3px solid #0D9488;
        }
        .tool-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="font-sans text-gray-800 antialiased overflow-hidden">

    <div class="flex h-screen bg-gray-50">
        
        <!-- SIDEBAR (Matches Kids page structure) -->
        <aside class="w-64 bg-white shadow-xl hidden md:flex flex-col z-20">
            <!-- Logo Area -->
            <div class="h-16 flex items-center px-6 border-b border-gray-100">
                <a href="index.php" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-teal-500 rounded-lg flex items-center justify-center text-white">
                        <i class="fas fa-toolbox"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-800 tracking-tight">Filezone <span class="text-teal-500">Tools</span></span>
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 overflow-y-auto py-4">
                <nav class="space-y-1">
                    <a href="#" onclick="filterTools('all')" class="sidebar-link active flex items-center px-6 py-3 text-gray-600 hover:text-teal-600 group">
                        <i class="fas fa-th-large w-5 h-5 mr-3 text-gray-400 group-hover:text-teal-500"></i>
                        <span class="font-medium">Бүх хэрэгсэл</span>
                    </a>
                    
                    <div class="px-6 pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Ангилал
                    </div>

                    <a href="#" onclick="filterTools('pdf')" class="sidebar-link flex items-center px-6 py-3 text-gray-600 group">
                        <i class="fas fa-file-pdf w-5 h-5 mr-3 text-gray-400 group-hover:text-teal-500"></i>
                        <span class="font-medium">PDF Хэрэгсэл</span>
                    </a>
                    <a href="#" onclick="filterTools('image')" class="sidebar-link flex items-center px-6 py-3 text-gray-600 group">
                        <i class="fas fa-image w-5 h-5 mr-3 text-gray-400 group-hover:text-teal-500"></i>
                        <span class="font-medium">Зураг янзлах</span>
                    </a>
                    <a href="#" onclick="filterTools('text')" class="sidebar-link flex items-center px-6 py-3 text-gray-600 group">
                        <i class="fas fa-font w-5 h-5 mr-3 text-gray-400 group-hover:text-teal-500"></i>
                        <span class="font-medium">Текст хэрэгсэл</span>
                    </a>
                    <a href="#" onclick="filterTools('security')" class="sidebar-link flex items-center px-6 py-3 text-gray-600 group">
                        <i class="fas fa-shield-alt w-5 h-5 mr-3 text-gray-400 group-hover:text-teal-500"></i>
                        <span class="font-medium">Нууцлал</span>
                    </a>
                    <a href="#" onclick="filterTools('util')" class="sidebar-link flex items-center px-6 py-3 text-gray-600 group">
                        <i class="fas fa-cogs w-5 h-5 mr-3 text-gray-400 group-hover:text-teal-500"></i>
                        <span class="font-medium">Бусад</span>
                    </a>
                </nav>
            </div>

            <!-- Sidebar Footer -->
            <div class="p-4 border-t border-gray-100 bg-gray-50">
                <a href="index.php" class="flex items-center gap-3 px-2 group">
                    <div class="w-8 h-8 rounded-full bg-gray-200 group-hover:bg-gray-300 flex items-center justify-center text-gray-500 transition">
                        <i class="fas fa-arrow-left"></i>
                    </div>
                    <div class="text-sm font-medium text-gray-600 group-hover:text-gray-800 transition">Үндсэн сайт руу буцах</div>
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT WRAPPER -->
        <div class="flex-1 flex flex-col overflow-hidden relative">
            
            <!-- HEADER (Top Bar - No Auth, Matches Kids Design) -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 shadow-sm z-10">
                <!-- Mobile Menu Button -->
                <button class="md:hidden text-gray-500 hover:text-gray-700 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>

                <!-- Search Bar -->
                <div class="flex-1 max-w-3xl ml-4 md:ml-0">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </span>
                        <input type="text" id="toolSearch" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 sm:text-sm transition duration-150 ease-in-out" placeholder="Хэрэгсэл хайх (Жишээ нь: PDF, QR)...">
                    </div>
                </div>

                <!-- Right Side Info -->
                <div class="flex items-center gap-3 ml-6">
                    <span class="hidden md:block text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                        <i class="fas fa-check-circle text-green-500 mr-1"></i> Үнэгүй
                    </span>
                    <span class="hidden md:block text-sm text-gray-400 italic">Бүртгэл шаардлагагүй</span>
                </div>
            </header>

            <!-- MAIN SCROLLABLE AREA -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6 md:p-8">
                
                <!-- Breadcrumb & Welcome -->
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Онлайн хэрэгслүүд</h1>
                        <p class="text-sm text-gray-500 mt-1">Танд хэрэгтэй бүх дижитал багаж нэг дор.</p>
                    </div>
                </div>

                <!-- Hero Banner (Similar to Kids but Teal Theme) -->
                <div class="rounded-2xl bg-gradient-to-r from-teal-500 to-emerald-500 p-8 text-white shadow-lg mb-10 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-4 translate-y-4">
                        <i class="fas fa-magic text-9xl"></i>
                    </div>
                    <div class="relative z-10 max-w-xl">
                        <span class="inline-block px-3 py-1 bg-white/20 rounded-full text-xs font-semibold mb-4 backdrop-blur-sm border border-white/10">Эрэлттэй</span>
                        <h2 class="text-3xl font-bold mb-4">PDF Файлыг WORD болгох</h2>
                        <p class="text-teal-50 mb-6 text-sm md:text-base leading-relaxed">
                            Бичиг баримтаа хялбархан хөрвүүлж, засварлах боломжтой болгоорой. 100% үнэгүй.
                        </p>
                        <button class="bg-white text-teal-700 hover:bg-gray-100 font-bold py-2.5 px-6 rounded-lg shadow-md transition transform hover:-translate-y-0.5 flex items-center gap-2">
                            <span>Ашиглах</span> <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Filters Toolbar (Matches Kids Design) -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0 hide-scrollbar">
                        <button onclick="filterTools('all')" class="filter-btn active px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-800 hover:bg-gray-200 transition whitespace-nowrap">Бүгд</button>
                        <button onclick="filterTools('pdf')" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition whitespace-nowrap">PDF</button>
                        <button onclick="filterTools('image')" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition whitespace-nowrap">Зураг</button>
                        <button onclick="filterTools('util')" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition whitespace-nowrap">Хэрэгсэл</button>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500">Эрэмбэлэх:</span>
                        <select class="border-gray-300 border text-sm rounded-lg focus:ring-teal-500 focus:border-teal-500 block p-2 bg-gray-50 text-gray-700">
                            <option>Шинэ нь эхэндээ</option>
                            <option>Их ашигласан</option>
                            <option>А-Я үсгээр</option>
                        </select>
                    </div>
                </div>

                <!-- Tools Grid -->
                <!-- Layout matches Kids grid logic -->
                <div id="toolsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <!-- Javascript will populate this based on PHP data logic simulation -->
                    <?php foreach ($tools as $tool): ?>
                    <div class="tool-card bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 group flex flex-col h-full" data-category="<?php echo $tool['category']; ?>" data-title="<?php echo strtolower($tool['title']); ?>">
                        <!-- Icon Header Area (Replaces Book Cover) -->
                        <div class="h-32 <?php echo $tool['color']; ?>/10 flex items-center justify-center relative group-hover:bg-opacity-20 transition-colors">
                            <div class="w-16 h-16 rounded-2xl <?php echo $tool['color']; ?> flex items-center justify-center text-white text-3xl shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                                <i class="fas <?php echo $tool['icon']; ?>"></i>
                            </div>
                            <!-- Usage Badge -->
                            <div class="absolute top-3 right-3 bg-white/80 backdrop-blur-sm px-2 py-1 rounded text-xs font-semibold text-gray-600">
                                <i class="fas fa-bolt text-yellow-500 mr-1"></i> <?php echo $tool['usage']; ?>
                            </div>
                        </div>
                        
                        <div class="p-5 flex-1 flex flex-col">
                            <h3 class="font-bold text-gray-800 text-lg mb-2 group-hover:text-teal-600 transition-colors">
                                <?php echo $tool['title']; ?>
                            </h3>
                            <p class="text-sm text-gray-500 mb-4 line-clamp-2">
                                <?php echo $tool['desc']; ?>
                            </p>
                            
                            <div class="mt-auto pt-4 border-t border-gray-50">
                                <button class="w-full bg-gray-50 hover:bg-teal-50 text-gray-700 hover:text-teal-700 font-semibold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 group-hover:bg-teal-500 group-hover:text-white">
                                    Нээх <i class="fas fa-external-link-alt text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            </main>
        </div>
    </div>

    <!-- JavaScript Logic -->
    <script>
        // Search Function
        document.getElementById('toolSearch').addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.tool-card');
            
            cards.forEach(card => {
                const title = card.getAttribute('data-title');
                if (title.includes(term)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Filter Function
        function filterTools(category) {
            // Update buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active', 'bg-gray-100', 'text-gray-800');
                btn.classList.add('text-gray-600');
            });
            event.target.classList.add('active', 'bg-gray-100', 'text-gray-800');
            event.target.classList.remove('text-gray-600');

            const cards = document.querySelectorAll('.tool-card');
            
            cards.forEach(card => {
                const cardCat = card.getAttribute('data-category');
                if (category === 'all' || cardCat === category) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>