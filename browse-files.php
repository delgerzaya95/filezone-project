<?php 
// Хуудасны гарчиг тохируулах (header.php дотор ашиглагдана)
$page_title = "Файл хайх - Filezone.mn";

// Header оруулах
include 'includes/header.php'; 
?>

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
            <a href="#" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-fire w-5 text-center text-orange-500"></i> Эрэлттэй
            </a>
        </div>

        <!-- NEW: File Categories -->
        <div class="space-y-1 mb-8">
            <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Файлын ангилал</h3>
            
            <a href="browse-files.php?cat=contracts" class="flex items-center gap-3 px-3 py-2 text-brand-600 bg-brand-50 rounded-lg font-medium">
                <i class="fas fa-file-contract w-5 text-center"></i> Гэрээ & Загвар
            </a>
            <a href="browse-files.php?cat=education" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-graduation-cap w-5 text-center"></i> Диплом & Курс
            </a>
            <a href="browse-files.php?cat=books" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-book w-5 text-center"></i> Ном & Товхимол
            </a>
            <a href="browse-files.php?cat=finance" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-calculator w-5 text-center"></i> Санхүү & Excel
            </a>
            <a href="browse-files.php?cat=marketing" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-bullhorn w-5 text-center"></i> Маркетинг
            </a>
            <a href="browse-files.php?cat=other" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-archive w-5 text-center"></i> Бусад
            </a>
        </div>

        <!-- Services Menu -->
        <div class="space-y-1 mb-8">
            <div class="flex items-center justify-between px-3 mb-2">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Freelance / Үйлчилгээ</h3>
            </div>
            <a href="services.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium group">
                <div class="w-5 h-5 rounded bg-purple-100 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition">
                    <i class="fas fa-pen-nib text-xs"></i>
                </div>
                Дизайн & График
            </a>
            <a href="services.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium group">
                <div class="w-5 h-5 rounded bg-blue-100 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition">
                    <i class="fas fa-code text-xs"></i>
                </div>
                Вэб хөгжүүлэлт
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
        
        <!-- Breadcrumb -->
        <nav class="flex mb-4 text-xs text-gray-500">
            <ol class="flex items-center space-x-2">
                <li><a href="index.php" class="hover:text-brand-600">Нүүр</a></li>
                <li><span class="text-gray-300">/</span></li>
                <li><a href="browse-files.php" class="hover:text-brand-600">Файлууд</a></li>
                <li><span class="text-gray-300">/</span></li>
                <li class="font-medium text-gray-900">Гэрээ & Загвар</li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-contract text-lg"></i>
                    </div>
                    Гэрээ & Загвар
                </h1>
                <p class="text-sm text-gray-500 mt-1 ml-14">Бүх төрлийн албан бичиг, гэрээний бэлэн загварууд.</p>
            </div>
            
            <!-- Sort Dropdown -->
            <div class="flex items-center gap-2">
                <label for="sort" class="text-sm text-gray-500 hidden sm:block">Эрэмбэлэх:</label>
                <select id="sort" class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-brand-500 focus:border-brand-500 block p-2.5">
                    <option>Шинэ нь эхэндээ</option>
                    <option>Хамгийн их татагдсан</option>
                    <option>Үнэ: Багаас их рүү</option>
                    <option>Үнэ: Ихээс бага рүү</option>
                </select>
            </div>
        </div>

        <!-- Filters (Tabs) -->
        <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-1">
            <button class="px-4 py-2 text-sm font-medium text-brand-600 border-b-2 border-brand-600 -mb-1.5 bg-brand-50/50 rounded-t-lg">
                Бүгд
            </button>
            <button class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300 transition-colors">
                Үнэгүй
            </button>
            <button class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300 transition-colors">
                Төлбөртэй
            </button>
        </div>

        <!-- File Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-8">
            
            <!-- File Card 1 -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                        <i class="fas fa-file-word"></i>
                    </div>
                    <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded">DOCX</span>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                    Байгууллагын дотоод журмын загвар 2025
                </h3>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                    <span><i class="fas fa-download mr-1"></i> 120</span>
                    <span>•</span>
                    <span>Admin</span>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                    <span class="font-bold text-gray-900">5,000₮</span>
                    <button class="text-gray-400 hover:text-brand-600 transition-colors">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                </div>
            </div>

            <!-- File Card 2 -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-xl">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded">PDF</span>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                    Хөдөлмөрийн гэрээний стандарт загвар
                </h3>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                    <span><i class="fas fa-download mr-1"></i> 2,340</span>
                    <span>•</span>
                    <span>Lawyer_mn</span>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                    <span class="text-green-600 font-bold text-sm bg-green-50 px-2 py-0.5 rounded">Үнэгүй</span>
                    <button class="text-gray-400 hover:text-brand-600 transition-colors">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>

            <!-- File Card 3 -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                        <i class="fas fa-file-word"></i>
                    </div>
                    <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded">DOCX</span>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                    Түрээсийн гэрээ (Орон сууц болон агуулах)
                </h3>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                    <span><i class="fas fa-download mr-1"></i> 560</span>
                    <span>•</span>
                    <span>User99</span>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                    <span class="font-bold text-gray-900">3,000₮</span>
                    <button class="text-gray-400 hover:text-brand-600 transition-colors">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                </div>
            </div>

            <!-- File Card 4 -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                        <i class="fas fa-file-word"></i>
                    </div>
                    <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded">DOCX</span>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                    Ажил хүлээлцэх акт
                </h3>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                    <span><i class="fas fa-download mr-1"></i> 85</span>
                    <span>•</span>
                    <span>HR_Manager</span>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                    <span class="text-green-600 font-bold text-sm bg-green-50 px-2 py-0.5 rounded">Үнэгүй</span>
                    <button class="text-gray-400 hover:text-brand-600 transition-colors">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>

            <!-- File Card 5 -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                        <i class="fas fa-file-word"></i>
                    </div>
                    <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded">DOCX</span>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                    Бэлэглэлийн гэрээ
                </h3>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                    <span><i class="fas fa-download mr-1"></i> 42</span>
                    <span>•</span>
                    <span>Lawyer_mn</span>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                    <span class="font-bold text-gray-900">2,000₮</span>
                    <button class="text-gray-400 hover:text-brand-600 transition-colors">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                </div>
            </div>

            <!-- File Card 6 -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-yellow-50 text-yellow-600 flex items-center justify-center text-xl">
                        <i class="fas fa-file-powerpoint"></i>
                    </div>
                    <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded">PPTX</span>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                    Гэрээний танилцуулга слайд
                </h3>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                    <span><i class="fas fa-download mr-1"></i> 156</span>
                    <span>•</span>
                    <span>Designer</span>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                    <span class="font-bold text-gray-900">10,000₮</span>
                    <button class="text-gray-400 hover:text-brand-600 transition-colors">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center mt-10">
            <nav class="flex items-center gap-2">
                <a href="#" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700">
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>
                <a href="#" class="w-8 h-8 flex items-center justify-center rounded-lg bg-brand-600 text-white font-medium text-sm">1</a>
                <a href="#" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium text-sm">2</a>
                <a href="#" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium text-sm">3</a>
                <span class="px-2 text-gray-400">...</span>
                <a href="#" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium text-sm">12</a>
                <a href="#" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700">
                    <i class="fas fa-chevron-right text-xs"></i>
                </a>
            </nav>
        </div>

<?php include 'includes/footer.php'; ?>