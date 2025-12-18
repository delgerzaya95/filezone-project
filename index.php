<?php 
// Хуудасны гарчиг тохируулах (header.php дотор ашиглагдана)
$page_title = "Нүүр хуудас - Filezone.mn";

// Header оруулах
include 'includes/header.php'; 
?>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <?php include 'includes/sidebar.php' ?>

    <!-- Main Content -->
    <main class="flex-1 py-6 min-w-0">
        
        <!-- NEW: Single Motion Graphic Hero Section (Compact & Animated) -->
        <div class="relative w-full mb-8 overflow-hidden rounded-2xl bg-indigo-900 text-white shadow-xl mx-4 lg:mx-0">
            <!-- Animated Background Layers -->
            <div class="absolute inset-0 w-full h-full bg-gradient-to-br from-indigo-900 via-purple-900 to-blue-900"></div>
            
            <!-- Floating Blobs (Motion Graphics) -->
            <div class="absolute top-0 -left-4 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
            <div class="absolute top-0 -right-4 w-72 h-72 bg-yellow-500 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000" style="animation-delay: 2s;"></div>
            <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000" style="animation-delay: 4s;"></div>
            
            <!-- Decorative Grid/Noise -->
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px;"></div>

            <!-- Content -->
            <div class="relative z-10 px-6 py-10 md:py-12 text-center">
                <span class="inline-block py-1 px-3 rounded-full bg-white/10 text-xs font-semibold mb-3 border border-white/20 backdrop-blur-sm shadow-sm">
                    🚀 Filezone 2.0
                </span>
                
                <h1 class="text-3xl md:text-4xl font-bold mb-3 leading-tight drop-shadow-md">
                    Бүгдийг нэг дороос.
                </h1>
                
                <p class="text-indigo-100 mb-6 max-w-xl mx-auto text-sm md:text-base opacity-90">
                    12,000+ бэлэн файл татах, эсвэл мэргэжлийн хүмүүсээр хүссэн ажлаа хийлгэх боломжтой Монголын хамгийн том дижитал платформ.
                </p>
                
                <div class="flex flex-wrap justify-center gap-4">
                    <button class="bg-white text-indigo-900 px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-indigo-50 transition shadow-lg flex items-center transform hover:-translate-y-0.5">
                        <i class="fas fa-search mr-2"></i> Хайх
                    </button>
                    <a href="upload.php" class="bg-gradient-to-r from-orange-500 to-red-500 text-white border border-transparent px-6 py-2.5 rounded-xl font-bold text-sm transition shadow-lg shadow-orange-500/30 flex items-center transform hover:-translate-y-0.5 btn-shine">
                        <i class="fas fa-plus mr-2"></i> Файл зарах
                    </a>
                </div>
            </div>
        </div>

        <!-- NEW: Visual Categories (Browse by Type) -->
        <div class="mx-4 lg:mx-0 mb-10">
            <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Төрлөөр хайх</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <!-- Category Item -->
                <a href="browse-files.php?cat=contracts" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:border-brand-200 transition text-center group">
                    <div class="w-10 h-10 mx-auto bg-blue-50 rounded-full flex items-center justify-center text-blue-600 mb-2 group-hover:scale-110 transition">
                        <i class="fas fa-file-contract text-lg"></i>
                    </div>
                    <span class="text-xs font-bold text-gray-700">Гэрээ & Загвар</span>
                </a>
                <!-- Category Item -->
                <a href="browse-files.php?cat=education" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:border-brand-200 transition text-center group">
                    <div class="w-10 h-10 mx-auto bg-green-50 rounded-full flex items-center justify-center text-green-600 mb-2 group-hover:scale-110 transition">
                        <i class="fas fa-graduation-cap text-lg"></i>
                    </div>
                    <span class="text-xs font-bold text-gray-700">Диплом & Курс</span>
                </a>
                <!-- Category Item -->
                <a href="browse-files.php?cat=books" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:border-brand-200 transition text-center group">
                    <div class="w-10 h-10 mx-auto bg-purple-50 rounded-full flex items-center justify-center text-purple-600 mb-2 group-hover:scale-110 transition">
                        <i class="fas fa-book text-lg"></i>
                    </div>
                    <span class="text-xs font-bold text-gray-700">Ном & Товхимол</span>
                </a>
                <!-- Category Item -->
                <a href="browse-files.php?cat=design" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:border-brand-200 transition text-center group">
                    <div class="w-10 h-10 mx-auto bg-orange-50 rounded-full flex items-center justify-center text-orange-600 mb-2 group-hover:scale-110 transition">
                        <i class="fas fa-palette text-lg"></i>
                    </div>
                    <span class="text-xs font-bold text-gray-700">Дизайн & Лого</span>
                </a>
                <!-- Category Item -->
                <a href="browse-files.php?cat=finance" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:border-brand-200 transition text-center group">
                    <div class="w-10 h-10 mx-auto bg-red-50 rounded-full flex items-center justify-center text-red-600 mb-2 group-hover:scale-110 transition">
                        <i class="fas fa-calculator text-lg"></i>
                    </div>
                    <span class="text-xs font-bold text-gray-700">Санхүү & Excel</span>
                </a>
                <!-- Category Item -->
                <a href="categories.php" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:border-brand-200 transition text-center group">
                    <div class="w-10 h-10 mx-auto bg-gray-50 rounded-full flex items-center justify-center text-gray-600 mb-2 group-hover:scale-110 transition">
                        <i class="fas fa-th-large text-lg"></i>
                    </div>
                    <span class="text-xs font-bold text-gray-700">Бүгдийг харах</span>
                </a>
            </div>
        </div>

        <!-- SECTION: SERVICES -->
        <div class="mb-10 mx-4 lg:mx-0">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span class="w-1.5 h-6 bg-purple-600 rounded-full"></span>
                    Санал болгож буй үйлчилгээ
                </h2>
                <a href="services.php" class="text-sm font-medium text-brand-600 hover:text-brand-700">Бүгд &rarr;</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Service Card 1 -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group service-card">
                    <div class="h-36 bg-gray-200 relative overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1558655146-d09347e92766?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Service" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded text-xs font-bold text-gray-700 shadow-sm">
                            <i class="fas fa-clock text-gray-400 text-[10px] mr-1"></i> 2 хоног
                        </div>
                    </div>
                    <div class="p-4 pt-2 relative">
                        <div class="flex justify-between items-start">
                            <img src="assets/avatars/default.png" class="w-10 h-10 rounded-full border-2 border-white bg-gray-100 -mt-7 object-cover shadow-sm">
                            <div class="text-right">
                                <span class="block text-green-600 font-bold text-lg">30,000₮</span>
                            </div>
                        </div>
                        <h3 class="font-bold text-gray-800 mt-2 line-clamp-2 text-sm h-10 leading-relaxed group-hover:text-brand-600 transition-colors">
                            Мэргэжлийн түвшинд CV болон Resume янзалж өгнө
                        </h3>
                        <div class="service-action absolute inset-x-0 bottom-0 p-4 bg-white/95 backdrop-blur opacity-0 translate-y-2 transition-all duration-300 flex items-center justify-center">
                            <a href="service-details.php" class="w-full bg-brand-600 text-white text-center py-2 rounded-lg font-medium text-sm hover:bg-brand-700 shadow-lg shadow-brand-500/20">
                                Захиалах
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Service Card 2 -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group service-card">
                    <div class="h-36 bg-gray-200 relative overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1542744173-8e7e53415bb0?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Service" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded text-xs font-bold text-gray-700 shadow-sm">
                            <i class="fas fa-clock text-gray-400 text-[10px] mr-1"></i> 1 хоног
                        </div>
                    </div>
                    <div class="p-4 pt-2 relative">
                        <div class="flex justify-between items-start">
                            <img src="assets/avatars/default.png" class="w-10 h-10 rounded-full border-2 border-white bg-gray-100 -mt-7 object-cover shadow-sm">
                            <div class="text-right">
                                <span class="block text-green-600 font-bold text-lg">15,000₮</span>
                            </div>
                        </div>
                        <h3 class="font-bold text-gray-800 mt-2 line-clamp-2 text-sm h-10 leading-relaxed group-hover:text-brand-600 transition-colors">
                            Англи хэлнээс Монгол хэл рүү текст орчуулна
                        </h3>
                        <div class="service-action absolute inset-x-0 bottom-0 p-4 bg-white/95 backdrop-blur opacity-0 translate-y-2 transition-all duration-300 flex items-center justify-center">
                            <a href="service-details.php" class="w-full bg-brand-600 text-white text-center py-2 rounded-lg font-medium text-sm hover:bg-brand-700 shadow-lg shadow-brand-500/20">
                                Захиалах
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Service Card 3 (Logo) -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group service-card">
                    <div class="h-36 bg-gray-200 relative overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1626785774573-4b7993125651?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Service" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded text-xs font-bold text-gray-700 shadow-sm">
                            <i class="fas fa-clock text-gray-400 text-[10px] mr-1"></i> 3 хоног
                        </div>
                    </div>
                    <div class="p-4 pt-2 relative">
                        <div class="flex justify-between items-start">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" class="w-10 h-10 rounded-full border-2 border-white bg-gray-100 -mt-7 object-cover shadow-sm">
                            <div class="text-right">
                                <span class="block text-green-600 font-bold text-lg">50,000₮</span>
                            </div>
                        </div>
                        <h3 class="font-bold text-gray-800 mt-2 line-clamp-2 text-sm h-10 leading-relaxed group-hover:text-brand-600 transition-colors">
                            Компанийн лого болон брэндбүүк хийнэ
                        </h3>
                        <div class="service-action absolute inset-x-0 bottom-0 p-4 bg-white/95 backdrop-blur opacity-0 translate-y-2 transition-all duration-300 flex items-center justify-center">
                            <a href="service-details.php" class="w-full bg-brand-600 text-white text-center py-2 rounded-lg font-medium text-sm hover:bg-brand-700 shadow-lg shadow-brand-500/20">
                                Захиалах
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Service Card 4 (Web) -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group service-card">
                    <div class="h-36 bg-gray-200 relative overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1498050108023-c5249f4df085?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Service" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded text-xs font-bold text-gray-700 shadow-sm">
                            <i class="fas fa-clock text-gray-400 text-[10px] mr-1"></i> 5 хоног
                        </div>
                    </div>
                    <div class="p-4 pt-2 relative">
                        <div class="flex justify-between items-start">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Aneka" class="w-10 h-10 rounded-full border-2 border-white bg-gray-100 -mt-7 object-cover shadow-sm">
                            <div class="text-right">
                                <span class="block text-green-600 font-bold text-lg">250,000₮</span>
                            </div>
                        </div>
                        <h3 class="font-bold text-gray-800 mt-2 line-clamp-2 text-sm h-10 leading-relaxed group-hover:text-brand-600 transition-colors">
                            Танилцуулга вебсайт хурдан хийж өгнө
                        </h3>
                        <div class="service-action absolute inset-x-0 bottom-0 p-4 bg-white/95 backdrop-blur opacity-0 translate-y-2 transition-all duration-300 flex items-center justify-center">
                            <a href="service-details.php" class="w-full bg-brand-600 text-white text-center py-2 rounded-lg font-medium text-sm hover:bg-brand-700 shadow-lg shadow-brand-500/20">
                                Захиалах
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Service Card 5 (Social) -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group service-card">
                    <div class="h-36 bg-gray-200 relative overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1611162617474-5b21e879e113?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Service" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded text-xs font-bold text-gray-700 shadow-sm">
                            <i class="fas fa-clock text-gray-400 text-[10px] mr-1"></i> 1 хоног
                        </div>
                    </div>
                    <div class="p-4 pt-2 relative">
                        <div class="flex justify-between items-start">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=John" class="w-10 h-10 rounded-full border-2 border-white bg-gray-100 -mt-7 object-cover shadow-sm">
                            <div class="text-right">
                                <span class="block text-green-600 font-bold text-lg">15,000₮</span>
                            </div>
                        </div>
                        <h3 class="font-bold text-gray-800 mt-2 line-clamp-2 text-sm h-10 leading-relaxed group-hover:text-brand-600 transition-colors">
                            Сошиал медиа постер дизайн
                        </h3>
                        <div class="service-action absolute inset-x-0 bottom-0 p-4 bg-white/95 backdrop-blur opacity-0 translate-y-2 transition-all duration-300 flex items-center justify-center">
                            <a href="service-details.php" class="w-full bg-brand-600 text-white text-center py-2 rounded-lg font-medium text-sm hover:bg-brand-700 shadow-lg shadow-brand-500/20">
                                Захиалах
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Add Service Promo (Existing but updated visually) -->
                <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center text-center p-6 hover:border-brand-500 hover:bg-brand-50 transition cursor-pointer group h-full min-h-[250px]">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mb-4 shadow-sm group-hover:scale-110 transition duration-300">
                        <i class="fas fa-plus text-2xl text-brand-600"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-2 text-lg">Үйлчилгээ нэмэх</h3>
                    <p class="text-sm text-gray-500 mb-6 px-4">Та өөрийн чадвараа ашиглан орлого олоорой. Бүртгүүлэхэд үнэгүй.</p>
                    <a href="add_service.php" class="px-6 py-2 bg-brand-600 text-white text-sm font-bold rounded-lg hover:bg-brand-700 transition shadow-lg shadow-brand-500/20">
                        Эхлэх
                    </a>
                </div>
            </div>
        </div>

        <!-- NEW: TRENDING / POPULAR FILES -->
        <!-- Онцлох болон Хамгийн их татагдсан хэсэг -->
        <div class="mb-10 mx-4 lg:mx-0">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-fire text-orange-500"></i>
                    Эрэлттэй файлууд (Trending)
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Trending Item 1 -->
                <div class="bg-white p-4 rounded-xl border border-orange-100 shadow-sm relative overflow-hidden hover:shadow-md transition">
                    <div class="absolute top-0 right-0 bg-orange-500 text-white text-[10px] font-bold px-2 py-1 rounded-bl-lg">TOP 1</div>
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-red-50 text-red-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-file-pdf text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm line-clamp-1 hover:text-orange-600 cursor-pointer">Байгууллагын хөдөлмөрийн гэрээ</h3>
                            <p class="text-xs text-gray-500 mb-2">Lawyer_mn • 2.5k татсан</p>
                            <span class="text-brand-600 font-bold text-sm">5,000₮</span>
                        </div>
                    </div>
                </div>
                
                <!-- Trending Item 2 -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden hover:shadow-md transition">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-file-word text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm line-clamp-1 hover:text-orange-600 cursor-pointer">Тайлангийн загвар (Excel)</h3>
                            <p class="text-xs text-gray-500 mb-2">Accountant • 1.2k татсан</p>
                            <span class="text-brand-600 font-bold text-sm">15,000₮</span>
                        </div>
                    </div>
                </div>

                <!-- Trending Item 3 -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden hover:shadow-md transition">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-yellow-50 text-yellow-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-file-powerpoint text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm line-clamp-1 hover:text-orange-600 cursor-pointer">Бизнес төлөвлөгөө PPT</h3>
                            <p class="text-xs text-gray-500 mb-2">Startup MN • 980 татсан</p>
                            <span class="text-brand-600 font-bold text-sm">8,000₮</span>
                        </div>
                    </div>
                </div>

                <!-- Trending Item 4 (NEW) -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden hover:shadow-md transition">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-file-contract text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm line-clamp-1 hover:text-orange-600 cursor-pointer">Уул уурхайн гэрээний загвар</h3>
                            <p class="text-xs text-gray-500 mb-2">MiningExpert • 850 татсан</p>
                            <span class="text-brand-600 font-bold text-sm">20,000₮</span>
                        </div>
                    </div>
                </div>

                <!-- Trending Item 5 (NEW) -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden hover:shadow-md transition">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-green-50 text-green-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-book text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm line-clamp-1 hover:text-orange-600 cursor-pointer">IELTS бэлтгэлийн ном (PDF)</h3>
                            <p class="text-xs text-gray-500 mb-2">EnglishCenter • 1.5k татсан</p>
                            <span class="text-brand-600 font-bold text-sm">10,000₮</span>
                        </div>
                    </div>
                </div>

                <!-- Trending Item 6 (NEW) -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden hover:shadow-md transition">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-calculator text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm line-clamp-1 hover:text-orange-600 cursor-pointer">Барилгын төсөв тооцох файл</h3>
                            <p class="text-xs text-gray-500 mb-2">BarilgaProject • 700 татсан</p>
                            <span class="text-brand-600 font-bold text-sm">25,000₮</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- NEW FILES -->
        <div class="mx-4 lg:mx-0 mb-10">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span class="w-1.5 h-6 bg-brand-600 rounded-full"></span>
                    Шинэ файлууд
                </h2>
                <a href="browse-files.php" class="text-sm font-medium text-brand-600 hover:text-brand-700">Бүгд &rarr;</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- File Card 1 -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                            <i class="fas fa-file-word"></i>
                        </div>
                        <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded">DOCX</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                        Дадлагын тайлангийн жишээ загвар
                    </h3>
                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-download mr-1"></i> 20</span>
                        <span>•</span>
                        <span>Student123</span>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                        <span class="font-bold text-gray-900">3,000₮</span>
                        <button class="text-gray-400 hover:text-brand-600 transition-colors">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>

                <!-- File Card 2 -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-lg bg-green-50 text-green-600 flex items-center justify-center text-xl">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded">XLSX</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                        Сошиал контент төлөвлөгөө (Calendar)
                    </h3>
                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-download mr-1"></i> 45</span>
                        <span>•</span>
                        <span>Marketer_B</span>
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
                        <div class="w-10 h-10 rounded-lg bg-yellow-50 text-yellow-600 flex items-center justify-center text-xl">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded">DOCX</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                        Үл хөдлөх хөрөнгө худалдах гэрээ
                    </h3>
                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-download mr-1"></i> 12</span>
                        <span>•</span>
                        <span>Agent007</span>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                        <span class="font-bold text-gray-900">8,000₮</span>
                        <button class="text-gray-400 hover:text-brand-600 transition-colors">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>

                <!-- File Card 4 -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                            <i class="fas fa-file-code"></i>
                        </div>
                        <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded">PDF</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                        Python хэлний анхан шатны гарын авлага
                    </h3>
                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-download mr-1"></i> 34</span>
                        <span>•</span>
                        <span>CoderBag</span>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                        <span class="font-bold text-gray-900">5,000₮</span>
                        <button class="text-gray-400 hover:text-brand-600 transition-colors">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>

                <!-- File Card 5 -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center text-xl">
                            <i class="fas fa-file-powerpoint"></i>
                        </div>
                        <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded">PPTX</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                        Байгууллагын танилцуулга PPT
                    </h3>
                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-download mr-1"></i> 56</span>
                        <span>•</span>
                        <span>BizGuru</span>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                        <span class="font-bold text-gray-900">15,000₮</span>
                        <button class="text-gray-400 hover:text-brand-600 transition-colors">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>

                <!-- File Card 6 -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-lg bg-green-50 text-green-600 flex items-center justify-center text-xl">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded">XLSX</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                        Төслийн менежментийн загвар
                    </h3>
                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-download mr-1"></i> 18</span>
                        <span>•</span>
                        <span>PM_Expert</span>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                        <span class="font-bold text-gray-900">20,000₮</span>
                        <button class="text-gray-400 hover:text-brand-600 transition-colors">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>

                <!-- File Card 7 -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-xl">
                            <i class="fas fa-book"></i>
                        </div>
                        <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded">PDF</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                        Англи хэлний дүрэм (Intermediate)
                    </h3>
                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-download mr-1"></i> 90</span>
                        <span>•</span>
                        <span>Teacher_A</span>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                        <span class="font-bold text-gray-900">5,000₮</span>
                        <button class="text-gray-400 hover:text-brand-600 transition-colors">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>

                <!-- File Card 8 -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-lg bg-pink-50 text-pink-600 flex items-center justify-center text-xl">
                            <i class="fas fa-file-archive"></i>
                        </div>
                        <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded">ZIP</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm">
                        Вэб сайт дизайн UI Kit
                    </h3>
                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-download mr-1"></i> 65</span>
                        <span>•</span>
                        <span>Designer_X</span>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                        <span class="font-bold text-gray-900">40,000₮</span>
                        <button class="text-gray-400 hover:text-brand-600 transition-colors">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>

                <!-- CTA Card in Grid (Mix content) - Moved to end of row if needed, or removed as we have 8 items now. Keeping 8 items is cleaner. -->
            </div>
        </div>

        <!-- MOST DOWNLOADED FILES (New Section) -->
        <div class="mx-4 lg:mx-0 mb-16">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span class="w-1.5 h-6 bg-green-500 rounded-full"></span>
                    Хамгийн их татагдсан
                </h2>
                <a href="browse-files.php?sort=popular" class="text-sm font-medium text-brand-600 hover:text-brand-700">Бүгд &rarr;</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Most Downloaded 1 -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden hover:shadow-md transition">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-file-word text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm line-clamp-1 hover:text-blue-600 cursor-pointer">Ажилд орох өргөдөл (Маягт)</h3>
                            <p class="text-xs text-gray-500 mb-2">HR_Mongolia • 8.5k татсан</p>
                            <span class="text-green-600 font-bold text-sm bg-green-50 px-2 py-0.5 rounded">Үнэгүй</span>
                        </div>
                    </div>
                </div>

                <!-- Most Downloaded 2 -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden hover:shadow-md transition">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-file-contract text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm line-clamp-1 hover:text-indigo-600 cursor-pointer">Түрээсийн гэрээ (Орон сууц)</h3>
                            <p class="text-xs text-gray-500 mb-2">RealEstate • 6.2k татсан</p>
                            <span class="text-brand-600 font-bold text-sm">3,000₮</span>
                        </div>
                    </div>
                </div>

                <!-- Most Downloaded 3 -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden hover:shadow-md transition">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-green-50 text-green-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-file-invoice text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm line-clamp-1 hover:text-green-600 cursor-pointer">Нэхэмжлэх загвар (Excel)</h3>
                            <p class="text-xs text-gray-500 mb-2">Accounting • 5.1k татсан</p>
                            <span class="text-green-600 font-bold text-sm bg-green-50 px-2 py-0.5 rounded">Үнэгүй</span>
                        </div>
                    </div>
                </div>

                <!-- Most Downloaded 4 -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden hover:shadow-md transition">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-red-50 text-red-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-file-pdf text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm line-clamp-1 hover:text-red-600 cursor-pointer">CV Загвар (Modern)</h3>
                            <p class="text-xs text-gray-500 mb-2">DesignPro • 4.8k татсан</p>
                            <span class="text-brand-600 font-bold text-sm">5,000₮</span>
                        </div>
                    </div>
                </div>

                <!-- Most Downloaded 5 -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden hover:shadow-md transition">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-yellow-50 text-yellow-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-file-alt text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm line-clamp-1 hover:text-yellow-600 cursor-pointer">Чөлөөний хуудас</h3>
                            <p class="text-xs text-gray-500 mb-2">OfficeAdmin • 3.5k татсан</p>
                            <span class="text-green-600 font-bold text-sm bg-green-50 px-2 py-0.5 rounded">Үнэгүй</span>
                        </div>
                    </div>
                </div>

                <!-- Most Downloaded 6 -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden hover:shadow-md transition">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-money-bill-wave text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm line-clamp-1 hover:text-purple-600 cursor-pointer">Бэлэн мөнгөний орлого зарлага</h3>
                            <p class="text-xs text-gray-500 mb-2">FinBook • 3.1k татсан</p>
                            <span class="text-brand-600 font-bold text-sm">2,000₮</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Featured Collections (Added Here) -->
        <div class="mx-4 lg:mx-0 mb-16">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 text-white shadow-2xl p-8 md:p-12">
                <!-- Decorative shapes -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-brand-500 rounded-full blur-[100px] opacity-20"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-purple-500 rounded-full blur-[100px] opacity-20"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
                    <div class="md:w-1/2 space-y-6">
                        <h2 class="text-2xl md:text-3xl font-bold leading-tight">
                            Танд зарах боломжтой файл байна уу?
                        </h2>
                        <p class="text-slate-300 text-sm md:text-base leading-relaxed">
                            Компьютерт тань хэрэггүй хэвтэж байгаа файлуудаа (бие даалт, диплом, тайлан, загвар) бусдад хэрэгтэй байдлаар зарж, нэмэлт орлого олох боломжтой.
                        </p>
                        <ul class="space-y-3 text-sm text-slate-300">
                            <li class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-full bg-green-500/20 text-green-400 flex items-center justify-center text-xs">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span>70-80% -ийн өндөр шимтгэл</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-full bg-green-500/20 text-green-400 flex items-center justify-center text-xs">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span>Хүссэн үедээ мөнгөө татах</span>
                            </li>
                        </ul>
                        <div class="pt-2">
                            <a href="upload.php" class="inline-flex items-center gap-2 bg-white text-slate-900 px-6 py-3 rounded-xl font-bold text-sm hover:bg-gray-100 transition shadow-lg btn-shine">
                                <i class="fas fa-cloud-upload-alt"></i> Файл оруулж эхлэх
                            </a>
                        </div>
                    </div>
                    
                    <div class="md:w-1/2 flex justify-center relative">
                        <!-- Floating Cards Visual -->
                        <div class="relative w-64 h-64 animate-[float_6s_ease-in-out_infinite]">
                            <!-- Card 1 -->
                            <div class="absolute top-0 right-4 w-48 bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-xl transform rotate-6 shadow-xl">
                                <div class="h-2 w-16 bg-white/30 rounded mb-2"></div>
                                <div class="h-16 w-full bg-white/10 rounded mb-3"></div>
                                <div class="flex justify-between items-center">
                                    <div class="h-2 w-8 bg-white/30 rounded"></div>
                                    <div class="h-4 w-12 bg-green-400/80 rounded"></div>
                                </div>
                            </div>
                            <!-- Card 2 -->
                            <div class="absolute top-8 left-0 w-48 bg-white p-4 rounded-xl shadow-2xl transform -rotate-3 z-10">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-500 flex items-center justify-center">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </div>
                                    <div>
                                        <div class="h-2 w-20 bg-gray-200 rounded mb-1"></div>
                                        <div class="h-2 w-12 bg-gray-100 rounded"></div>
                                    </div>
                                </div>
                                <div class="flex justify-between items-end border-t border-gray-100 pt-3">
                                    <span class="text-xs text-gray-400">Орлого</span>
                                    <span class="text-lg font-bold text-gray-800">+150,000₮</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php' ?>