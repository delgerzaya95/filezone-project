<?php 
// Хуудасны гарчиг тохируулах (header.php дотор ашиглагдана)
$page_title = "Файл дэлгэрэнгүй - Filezone.mn";

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
                <a href="browse-files.php" class="flex items-center gap-3 px-3 py-2 text-brand-600 bg-brand-50 rounded-lg font-medium">
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
                <a href="services.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium group">
                    <div class="w-5 h-5 rounded bg-purple-100 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition">
                        <i class="fas fa-pen-nib text-xs"></i>
                    </div>
                    Дизайн & График
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
            
            <!-- Breadcrumb -->
            <nav class="flex mb-6 text-xs text-gray-500">
                <ol class="flex items-center space-x-2">
                    <li><a href="index.php" class="hover:text-brand-600">Нүүр</a></li>
                    <li><span class="text-gray-300">/</span></li>
                    <li><a href="browse-files.php" class="hover:text-brand-600">Файлууд</a></li>
                    <li><span class="text-gray-300">/</span></li>
                    <li><a href="#" class="hover:text-brand-600">Санхүү & Excel</a></li>
                    <li><span class="text-gray-300">/</span></li>
                    <li class="font-medium text-gray-900 line-clamp-1 max-w-[150px]">Санхүүгийн тайлангийн иж бүрэн загвар</li>
                </ol>
            </nav>

            <div class="flex flex-col lg:flex-row gap-8">
                
                <!-- Left Column: Preview & Description -->
                <div class="lg:w-2/3">
                    
                    <!-- File Header (Mobile Only) -->
                    <div class="lg:hidden mb-6">
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">Санхүүгийн тайлангийн иж бүрэн загвар 2025 (Excel)</h1>
                        <div class="flex items-center gap-4 text-xs text-gray-500">
                            <span class="flex items-center gap-1"><i class="fas fa-calendar"></i> 2025-02-20</span>
                            <span class="flex items-center gap-1"><i class="fas fa-eye"></i> 1,205 үзсэн</span>
                        </div>
                    </div>

                    <!-- Preview Image -->
                    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm mb-8">
                        <div class="aspect-video bg-gray-100 relative group cursor-pointer flex items-center justify-center">
                            <!-- Placeholder for actual image -->
                            <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80" alt="File Preview" class="w-full h-full object-cover">
                            
                            <!-- Hover Overlay -->
                            <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                <span class="bg-white/90 text-gray-800 px-4 py-2 rounded-full text-sm font-bold shadow-lg transform translate-y-2 group-hover:translate-y-0 transition">
                                    <i class="fas fa-search-plus mr-1"></i> Томоор харах
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="flex gap-6">
                            <button class="border-b-2 border-brand-600 py-3 text-sm font-bold text-brand-600">Тайлбар</button>
                            <button class="border-b-2 border-transparent py-3 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">Сэтгэгдэл (12)</button>
                        </nav>
                    </div>

                    <!-- Description Content -->
                    <div class="prose prose-sm max-w-none text-gray-600 mb-10">
                        <p>Энэхүү файл нь жижиг дунд бизнес эрхлэгчид болон нягтлан бодогчдод зориулсан <strong>Санхүүгийн тайлангийн иж бүрэн автоматжуулсан Excel загвар</strong> юм.</p>
                        <p>Файлын онцлог давуу талууд:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Бүх тооцоолол томьёогоор автоматжсан.</li>
                            <li>Баланс, Орлого үр дүн, Мөнгөн гүйлгээний тайлангууд хоорондоо холбогдсон.</li>
                            <li>Татварын тайлан гаргахад бэлэн загвартай.</li>
                            <li>Хэрэглэхэд хялбар, ойлгомжтой заавартай.</li>
                        </ul>
                        <p>Татаж аваад шууд ашиглахад бэлэн. Асуух зүйл байвал сэтгэгдэл хэсэгт үлдээнэ үү.</p>
                    </div>

                    <!-- Reviews Section -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-6">
                        <h3 class="font-bold text-gray-900 mb-4">Хэрэглэгчийн сэтгэгдэл</h3>
                        
                        <!-- Single Review -->
                        <div class="flex gap-4 border-b border-gray-100 pb-4 mb-4 last:border-0 last:pb-0 last:mb-0">
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex-shrink-0 flex items-center justify-center text-gray-500">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-bold text-sm text-gray-900">Boldoo</span>
                                    <span class="text-xs text-gray-400">2 хоногийн өмнө</span>
                                </div>
                                <div class="flex text-yellow-400 text-xs mb-1">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </div>
                                <p class="text-sm text-gray-600">Маш хэрэгтэй файл байна. Баярлалаа. Ажил хөнгөвчиллөө.</p>
                            </div>
                        </div>

                        <!-- Single Review -->
                        <div class="flex gap-4 border-b border-gray-100 pb-4 mb-4 last:border-0 last:pb-0 last:mb-0">
                            <div class="w-10 h-10 rounded-full bg-pink-100 flex-shrink-0 flex items-center justify-center text-pink-500 font-bold text-xs">
                                S
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-bold text-sm text-gray-900">Sarnai</span>
                                    <span class="text-xs text-gray-400">5 хоногийн өмнө</span>
                                </div>
                                <div class="flex text-yellow-400 text-xs mb-1">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                                </div>
                                <p class="text-sm text-gray-600">Дажгүй юм байна. Гэхдээ зарим томьёог нь өөрөө засах хэрэгтэй байлаа.</p>
                            </div>
                        </div>

                        <a href="#" class="block text-center text-sm font-medium text-brand-600 hover:text-brand-700 mt-4">Бүх сэтгэгдлийг харах</a>
                    </div>

                </div>

                <!-- Right Column: Info & Purchase (Sticky) -->
                <div class="lg:w-1/3">
                    <div class="sticky top-24 space-y-6">
                        
                        <!-- Purchase Card -->
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-lg p-6">
                            <h1 class="hidden lg:block text-xl font-bold text-gray-900 mb-4 leading-snug">Санхүүгийн тайлангийн иж бүрэн загвар 2025 (Excel)</h1>
                            
                            <div class="flex items-end gap-2 mb-6">
                                <span class="text-3xl font-bold text-green-600">15,000₮</span>
                                <span class="text-sm text-gray-400 line-through mb-1">30,000₮</span>
                                <span class="bg-red-100 text-red-600 text-[10px] font-bold px-2 py-0.5 rounded mb-1.5 ml-auto">-50%</span>
                            </div>

                            <button class="w-full bg-brand-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-brand-500/30 hover:bg-brand-700 transition btn-shine flex items-center justify-center gap-2 mb-3">
                                <i class="fas fa-download"></i> Худалдаж авах
                            </button>
                            
                            <p class="text-xs text-center text-gray-500 mb-6">
                                <i class="fas fa-lock text-gray-400 mr-1"></i> Төлбөр төлсний дараа шууд татагдана.
                            </p>

                            <!-- File Stats Grid -->
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 text-center">
                                    <div class="text-gray-400 text-xs mb-1">Файлын төрөл</div>
                                    <div class="font-bold text-gray-800 flex items-center justify-center gap-1">
                                        <i class="fas fa-file-excel text-green-600"></i> XLSX
                                    </div>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 text-center">
                                    <div class="text-gray-400 text-xs mb-1">Хэмжээ</div>
                                    <div class="font-bold text-gray-800">2.5 MB</div>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 text-center">
                                    <div class="text-gray-400 text-xs mb-1">Хуудас</div>
                                    <div class="font-bold text-gray-800">5 Sheet</div>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 text-center">
                                    <div class="text-gray-400 text-xs mb-1">Таталт</div>
                                    <div class="font-bold text-gray-800">1,250</div>
                                </div>
                            </div>
                        </div>

                        <!-- Seller Profile Card -->
                        <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
                            <div class="relative">
                                <img src="assets/avatars/default.png" class="w-12 h-12 rounded-full object-cover bg-gray-100">
                                <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 text-sm">Bat-Erdene</h4>
                                <p class="text-xs text-gray-500 mb-1">Нягтлан бодогч</p>
                                <div class="flex items-center gap-1 text-xs text-yellow-500">
                                    <i class="fas fa-star"></i> 4.9 <span class="text-gray-400">(45)</span>
                                </div>
                            </div>
                            <button class="text-brand-600 hover:bg-brand-50 p-2 rounded-lg transition">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>

                        <!-- Safety Notice -->
                        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex gap-3">
                            <i class="fas fa-shield-alt text-blue-500 mt-0.5"></i>
                            <div class="text-xs text-blue-800">
                                <p class="font-bold mb-1">Найдвартай файл</p>
                                <p class="opacity-80">Энэ файлыг администратор шалгаж баталгаажуулсан болно.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Related Files -->
            <div class="mt-16 mb-10">
                <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <span class="w-1 h-6 bg-brand-600 rounded-full"></span>
                    Төстэй файлууд
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- File Card 1 -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-lg bg-green-50 text-green-600 flex items-center justify-center text-xl">
                                <i class="fas fa-file-excel"></i>
                            </div>
                            <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded">XLSX</span>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                            Татварын тайлангийн загвар
                        </h3>
                        <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                            <span><i class="fas fa-download mr-1"></i> 340</span>
                            <span>•</span>
                            <span>Bat-Erdene</span>
                        </div>
                        <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                            <span class="font-bold text-gray-900">5,000₮</span>
                            <button class="text-gray-400 hover:text-brand-600 transition-colors">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- File Card 2 -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                                <i class="fas fa-file-word"></i>
                            </div>
                            <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded">DOCX</span>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                            Санхүүгийн журам
                        </h3>
                        <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                            <span><i class="fas fa-download mr-1"></i> 120</span>
                            <span>•</span>
                            <span>Admin</span>
                        </div>
                        <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                            <span class="font-bold text-gray-900">2,000₮</span>
                            <button class="text-gray-400 hover:text-brand-600 transition-colors">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

<?php include 'includes/footer.php'; ?>