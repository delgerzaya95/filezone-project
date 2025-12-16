<?php 
// Хуудасны гарчиг тохируулах (header.php дотор ашиглагдана)
$page_title = "Хайлтын үр дүн - Filezone.mn";

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
                <a href="browse-files.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-folder-open w-5 text-center"></i> Файлууд
                </a>
            </div>

            <!-- Categories -->
            <div class="space-y-1 mb-8">
                <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Файлын ангилал</h3>
                <a href="browse-files.php?cat=contracts" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-file-contract w-5 text-center"></i> Гэрээ & Загвар
                </a>
                <a href="browse-files.php?cat=finance" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-calculator w-5 text-center"></i> Санхүү & Excel
                </a>
            </div>

             <!-- Services Menu -->
             <div class="space-y-1 mb-8">
                <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Үйлчилгээ</h3>
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
            
            <!-- Search Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Хайлтын үр дүн: <span class="text-brand-600">"Санхүү"</span></h1>
                <p class="text-sm text-gray-500">Нийт 8 илэрц олдлоо</p>
            </div>

            <!-- Filters & Tabs -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6 border-b border-gray-200 pb-4">
                <!-- Type Tabs -->
                <div class="flex gap-2 p-1 bg-gray-100 rounded-lg">
                    <button class="px-4 py-1.5 text-sm font-medium text-gray-800 bg-white rounded shadow-sm">Бүгд (8)</button>
                    <button class="px-4 py-1.5 text-sm font-medium text-gray-500 hover:text-gray-700">Файл (6)</button>
                    <button class="px-4 py-1.5 text-sm font-medium text-gray-500 hover:text-gray-700">Үйлчилгээ (2)</button>
                </div>

                <!-- Sort & Filter Dropdowns -->
                <div class="flex gap-2">
                    <select class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-brand-500 focus:border-brand-500 p-2">
                        <option>Бүх үнэ</option>
                        <option>Үнэгүй</option>
                        <option>Төлбөртэй</option>
                    </select>
                    <select class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-brand-500 focus:border-brand-500 p-2">
                        <option>Шинэ нь эхэндээ</option>
                        <option>Их татагдсан</option>
                    </select>
                </div>
            </div>

            <!-- Results Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                
                <!-- Result 1: File -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                    <div class="absolute top-3 right-3 bg-blue-50 text-blue-600 text-[10px] font-bold px-2 py-0.5 rounded uppercase">Файл</div>
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-lg bg-green-50 text-green-600 flex items-center justify-center text-xl">
                            <i class="fas fa-file-excel"></i>
                        </div>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                        <span class="bg-yellow-100 text-yellow-800 px-1 rounded">Санхүү</span>гийн тайлангийн иж бүрэн загвар 2025
                    </h3>
                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-download mr-1"></i> 1,205</span>
                        <span>•</span>
                        <span>Bat-Erdene</span>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                        <span class="font-bold text-gray-900">15,000₮</span>
                        <a href="file-details.php" class="text-gray-400 hover:text-brand-600 transition-colors">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Result 2: File -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                    <div class="absolute top-3 right-3 bg-blue-50 text-blue-600 text-[10px] font-bold px-2 py-0.5 rounded uppercase">Файл</div>
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                            <i class="fas fa-file-word"></i>
                        </div>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                        Байгууллагын <span class="bg-yellow-100 text-yellow-800 px-1 rounded">санхүү</span>гийн дотоод журам
                    </h3>
                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-download mr-1"></i> 85</span>
                        <span>•</span>
                        <span>Admin</span>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                        <span class="font-bold text-gray-900">5,000₮</span>
                        <a href="file-details.php" class="text-gray-400 hover:text-brand-600 transition-colors">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Result 3: Service (Different Style) -->
                <div class="bg-white rounded-xl border border-purple-200 p-0 overflow-hidden hover:shadow-md transition group">
                    <div class="bg-purple-50 p-3 flex justify-between items-center border-b border-purple-100">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-purple-200 text-purple-600 flex items-center justify-center text-xs"><i class="fas fa-briefcase"></i></div>
                            <span class="text-xs font-bold text-purple-700">Үйлчилгээ</span>
                        </div>
                        <span class="text-[10px] text-gray-500">2 өдөрт</span>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <img src="assets/avatars/default.png" class="w-6 h-6 rounded-full border border-gray-200">
                            <span class="text-xs text-gray-500">Sarnai</span>
                        </div>
                        <h3 class="font-bold text-gray-900 text-sm mb-2 line-clamp-2 group-hover:text-purple-600 transition-colors">
                            <span class="bg-yellow-100 text-yellow-800 px-1 rounded">Санхүү</span>гийн зөвлөгөө өгч, тайлан гаргана
                        </h3>
                        <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-50">
                            <div class="flex text-yellow-400 text-xs">
                                <i class="fas fa-star"></i> 5.0
                            </div>
                            <span class="font-bold text-gray-900">50,000₮</span>
                        </div>
                        <a href="service-details.php" class="block w-full text-center mt-3 bg-purple-600 text-white py-1.5 rounded-lg text-xs font-bold hover:bg-purple-700">Захиалах</a>
                    </div>
                </div>

                <!-- Result 4: File -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                    <div class="absolute top-3 right-3 bg-blue-50 text-blue-600 text-[10px] font-bold px-2 py-0.5 rounded uppercase">Файл</div>
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-lg bg-green-50 text-green-600 flex items-center justify-center text-xl">
                            <i class="fas fa-file-excel"></i>
                        </div>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                        Татварын тайлангийн загвар (НӨАТ, ХХОАТ)
                    </h3>
                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-download mr-1"></i> 340</span>
                        <span>•</span>
                        <span>User99</span>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                        <span class="text-green-600 font-bold text-sm bg-green-50 px-2 py-0.5 rounded">Үнэгүй</span>
                        <a href="file-details.php" class="text-gray-400 hover:text-brand-600 transition-colors">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Result 5: Service -->
                <div class="bg-white rounded-xl border border-purple-200 p-0 overflow-hidden hover:shadow-md transition group">
                    <div class="bg-purple-50 p-3 flex justify-between items-center border-b border-purple-100">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-purple-200 text-purple-600 flex items-center justify-center text-xs"><i class="fas fa-briefcase"></i></div>
                            <span class="text-xs font-bold text-purple-700">Үйлчилгээ</span>
                        </div>
                        <span class="text-[10px] text-gray-500">1 өдөрт</span>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <img src="assets/avatars/default.png" class="w-6 h-6 rounded-full border border-gray-200">
                            <span class="text-xs text-gray-500">Boldoo</span>
                        </div>
                        <h3 class="font-bold text-gray-900 text-sm mb-2 line-clamp-2 group-hover:text-purple-600 transition-colors">
                            Excel дээр <span class="bg-yellow-100 text-yellow-800 px-1 rounded">санхүү</span>гийн тооцоолол хийж өгнө
                        </h3>
                        <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-50">
                            <div class="flex text-yellow-400 text-xs">
                                <i class="fas fa-star"></i> 4.8
                            </div>
                            <span class="font-bold text-gray-900">20,000₮</span>
                        </div>
                        <a href="service-details.php" class="block w-full text-center mt-3 bg-purple-600 text-white py-1.5 rounded-lg text-xs font-bold hover:bg-purple-700">Захиалах</a>
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
                    <a href="#" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </a>
                </nav>
            </div>

            <?php include 'includes/footer.php' ?>