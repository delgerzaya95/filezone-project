<?php 
// Хуудасны гарчиг тохируулах (header.php дотор ашиглагдана)
$page_title = "Үйлчилгээ - Filezone.mn";

// Header оруулах
include 'includes/header.php'; 
?>


    <div class="flex flex-1 max-w-7xl mx-auto w-full">
        
        <?php include 'includes/sidebar.php' ?>

        <!-- Main Content -->
        <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
            
            <!-- Breadcrumb -->
            <nav class="flex mb-4 text-xs text-gray-500">
                <ol class="flex items-center space-x-2">
                    <li><a href="index.php" class="hover:text-brand-600">Нүүр</a></li>
                    <li><span class="text-gray-300">/</span></li>
                    <li class="font-medium text-gray-900">Бүх үйлчилгээ</li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                        <span class="bg-gradient-to-r from-purple-600 to-brand-600 text-transparent bg-clip-text">Бүх үйлчилгээ</span>
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Мэргэжлийн хүмүүсээр хүссэн ажлаа хийлгээрэй.</p>
                </div>
                
                <!-- Sort Dropdown -->
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

            <!-- Filters (Tabs) -->
            <div class="flex flex-wrap gap-2 mb-8 overflow-x-auto no-scrollbar pb-2">
                <button class="px-4 py-2 text-sm font-bold text-white bg-brand-600 rounded-full shadow-md shadow-brand-500/20 transition-transform hover:scale-105">
                    Бүгд
                </button>
                <button class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-full hover:bg-gray-50 hover:border-gray-300 transition-colors whitespace-nowrap">
                    Дизайн & График
                </button>
                <button class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-full hover:bg-gray-50 hover:border-gray-300 transition-colors whitespace-nowrap">
                    Вэб хөгжүүлэлт
                </button>
                <button class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-full hover:bg-gray-50 hover:border-gray-300 transition-colors whitespace-nowrap">
                    Орчуулга & Бичих
                </button>
                <button class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-full hover:bg-gray-50 hover:border-gray-300 transition-colors whitespace-nowrap">
                    Видео & Анимэйшн
                </button>
                <button class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-full hover:bg-gray-50 hover:border-gray-300 transition-colors whitespace-nowrap">
                    Бизнес
                </button>
            </div>

            <!-- Services Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                
                <!-- Service 1 -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group service-card">
                    <div class="h-40 bg-gray-200 relative overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1558655146-d09347e92766?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
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
                        <div class="flex items-center gap-2 mt-2 text-xs text-gray-500">
                            <i class="fas fa-star text-yellow-400"></i> 5.0 (24)
                        </div>
                        <div class="service-action absolute inset-x-0 bottom-0 p-4 bg-white/95 backdrop-blur opacity-0 translate-y-2 transition-all duration-300 flex items-center justify-center">
                            <a href="service-details.php" class="w-full bg-brand-600 text-white text-center py-2 rounded-lg font-medium text-sm hover:bg-brand-700 shadow-lg shadow-brand-500/20">
                                Дэлгэрэнгүй
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Service 2 -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group service-card">
                    <div class="h-40 bg-gray-200 relative overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1542744173-8e7e53415bb0?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded text-xs font-bold text-gray-700 shadow-sm">
                            <i class="fas fa-clock text-gray-400 text-[10px] mr-1"></i> 1 хоног
                        </div>
                    </div>
                    <div class="p-4 pt-2 relative">
                        <div class="flex justify-between items-start">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" class="w-10 h-10 rounded-full border-2 border-white bg-gray-100 -mt-7 object-cover shadow-sm">
                            <div class="text-right">
                                <span class="block text-green-600 font-bold text-lg">15,000₮</span>
                            </div>
                        </div>
                        <h3 class="font-bold text-gray-800 mt-2 line-clamp-2 text-sm h-10 leading-relaxed group-hover:text-brand-600 transition-colors">
                            Англи хэлнээс Монгол хэл рүү текст орчуулна
                        </h3>
                        <div class="flex items-center gap-2 mt-2 text-xs text-gray-500">
                            <i class="fas fa-star text-yellow-400"></i> 4.8 (12)
                        </div>
                        <div class="service-action absolute inset-x-0 bottom-0 p-4 bg-white/95 backdrop-blur opacity-0 translate-y-2 transition-all duration-300 flex items-center justify-center">
                            <a href="service-details.php" class="w-full bg-brand-600 text-white text-center py-2 rounded-lg font-medium text-sm hover:bg-brand-700 shadow-lg shadow-brand-500/20">
                                Дэлгэрэнгүй
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Service 3 -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group service-card">
                    <div class="h-40 bg-gray-200 relative overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1626785774573-4b7993125651?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded text-xs font-bold text-gray-700 shadow-sm">
                            <i class="fas fa-clock text-gray-400 text-[10px] mr-1"></i> 3 хоног
                        </div>
                    </div>
                    <div class="p-4 pt-2 relative">
                        <div class="flex justify-between items-start">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Aneka" class="w-10 h-10 rounded-full border-2 border-white bg-gray-100 -mt-7 object-cover shadow-sm">
                            <div class="text-right">
                                <span class="block text-green-600 font-bold text-lg">50,000₮</span>
                            </div>
                        </div>
                        <h3 class="font-bold text-gray-800 mt-2 line-clamp-2 text-sm h-10 leading-relaxed group-hover:text-brand-600 transition-colors">
                            Компанийн лого болон брэндбүүк хийнэ
                        </h3>
                        <div class="flex items-center gap-2 mt-2 text-xs text-gray-500">
                            <i class="fas fa-star text-yellow-400"></i> 5.0 (5)
                        </div>
                        <div class="service-action absolute inset-x-0 bottom-0 p-4 bg-white/95 backdrop-blur opacity-0 translate-y-2 transition-all duration-300 flex items-center justify-center">
                            <a href="service-details.php" class="w-full bg-brand-600 text-white text-center py-2 rounded-lg font-medium text-sm hover:bg-brand-700 shadow-lg shadow-brand-500/20">
                                Дэлгэрэнгүй
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Service 4 -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group service-card">
                    <div class="h-40 bg-gray-200 relative overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1498050108023-c5249f4df085?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded text-xs font-bold text-gray-700 shadow-sm">
                            <i class="fas fa-clock text-gray-400 text-[10px] mr-1"></i> 5 хоног
                        </div>
                    </div>
                    <div class="p-4 pt-2 relative">
                        <div class="flex justify-between items-start">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=John" class="w-10 h-10 rounded-full border-2 border-white bg-gray-100 -mt-7 object-cover shadow-sm">
                            <div class="text-right">
                                <span class="block text-green-600 font-bold text-lg">250,000₮</span>
                            </div>
                        </div>
                        <h3 class="font-bold text-gray-800 mt-2 line-clamp-2 text-sm h-10 leading-relaxed group-hover:text-brand-600 transition-colors">
                            Танилцуулга вебсайт хурдан хийж өгнө
                        </h3>
                        <div class="flex items-center gap-2 mt-2 text-xs text-gray-500">
                            <i class="fas fa-star text-yellow-400"></i> 4.9 (42)
                        </div>
                        <div class="service-action absolute inset-x-0 bottom-0 p-4 bg-white/95 backdrop-blur opacity-0 translate-y-2 transition-all duration-300 flex items-center justify-center">
                            <a href="service-details.php" class="w-full bg-brand-600 text-white text-center py-2 rounded-lg font-medium text-sm hover:bg-brand-700 shadow-lg shadow-brand-500/20">
                                Дэлгэрэнгүй
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Service 5 -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group service-card">
                    <div class="h-40 bg-gray-200 relative overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1611162617474-5b21e879e113?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded text-xs font-bold text-gray-700 shadow-sm">
                            <i class="fas fa-clock text-gray-400 text-[10px] mr-1"></i> 1 хоног
                        </div>
                    </div>
                    <div class="p-4 pt-2 relative">
                        <div class="flex justify-between items-start">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Sarnai" class="w-10 h-10 rounded-full border-2 border-white bg-gray-100 -mt-7 object-cover shadow-sm">
                            <div class="text-right">
                                <span class="block text-green-600 font-bold text-lg">15,000₮</span>
                            </div>
                        </div>
                        <h3 class="font-bold text-gray-800 mt-2 line-clamp-2 text-sm h-10 leading-relaxed group-hover:text-brand-600 transition-colors">
                            Сошиал медиа постер дизайн
                        </h3>
                        <div class="flex items-center gap-2 mt-2 text-xs text-gray-500">
                            <i class="fas fa-star text-yellow-400"></i> 4.5 (8)
                        </div>
                        <div class="service-action absolute inset-x-0 bottom-0 p-4 bg-white/95 backdrop-blur opacity-0 translate-y-2 transition-all duration-300 flex items-center justify-center">
                            <a href="service-details.php" class="w-full bg-brand-600 text-white text-center py-2 rounded-lg font-medium text-sm hover:bg-brand-700 shadow-lg shadow-brand-500/20">
                                Дэлгэрэнгүй
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Service 6 -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group service-card">
                    <div class="h-40 bg-gray-200 relative overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1555421689-3f034debb7a6?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded text-xs font-bold text-gray-700 shadow-sm">
                            <i class="fas fa-clock text-gray-400 text-[10px] mr-1"></i> 2 хоног
                        </div>
                    </div>
                    <div class="p-4 pt-2 relative">
                        <div class="flex justify-between items-start">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Tuya" class="w-10 h-10 rounded-full border-2 border-white bg-gray-100 -mt-7 object-cover shadow-sm">
                            <div class="text-right">
                                <span class="block text-green-600 font-bold text-lg">20,000₮</span>
                            </div>
                        </div>
                        <h3 class="font-bold text-gray-800 mt-2 line-clamp-2 text-sm h-10 leading-relaxed group-hover:text-brand-600 transition-colors">
                            Видео монтаж болон эффект
                        </h3>
                        <div class="flex items-center gap-2 mt-2 text-xs text-gray-500">
                            <i class="fas fa-star text-yellow-400"></i> 5.0 (2)
                        </div>
                        <div class="service-action absolute inset-x-0 bottom-0 p-4 bg-white/95 backdrop-blur opacity-0 translate-y-2 transition-all duration-300 flex items-center justify-center">
                            <a href="service-details.php" class="w-full bg-brand-600 text-white text-center py-2 rounded-lg font-medium text-sm hover:bg-brand-700 shadow-lg shadow-brand-500/20">
                                Дэлгэрэнгүй
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Service 7 -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group service-card">
                    <div class="h-40 bg-gray-200 relative overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1586281380349-632531db7ed4?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded text-xs font-bold text-gray-700 shadow-sm">
                            <i class="fas fa-clock text-gray-400 text-[10px] mr-1"></i> 3 хоног
                        </div>
                    </div>
                    <div class="p-4 pt-2 relative">
                        <div class="flex justify-between items-start">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Bat" class="w-10 h-10 rounded-full border-2 border-white bg-gray-100 -mt-7 object-cover shadow-sm">
                            <div class="text-right">
                                <span class="block text-green-600 font-bold text-lg">100,000₮</span>
                            </div>
                        </div>
                        <h3 class="font-bold text-gray-800 mt-2 line-clamp-2 text-sm h-10 leading-relaxed group-hover:text-brand-600 transition-colors">
                            Бизнес зөвлөгөө ба төлөвлөгөө
                        </h3>
                        <div class="flex items-center gap-2 mt-2 text-xs text-gray-500">
                            <i class="fas fa-star text-yellow-400"></i> 4.7 (15)
                        </div>
                        <div class="service-action absolute inset-x-0 bottom-0 p-4 bg-white/95 backdrop-blur opacity-0 translate-y-2 transition-all duration-300 flex items-center justify-center">
                            <a href="service-details.php" class="w-full bg-brand-600 text-white text-center py-2 rounded-lg font-medium text-sm hover:bg-brand-700 shadow-lg shadow-brand-500/20">
                                Дэлгэрэнгүй
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Service 8 -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group service-card">
                    <div class="h-40 bg-gray-200 relative overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded text-xs font-bold text-gray-700 shadow-sm">
                            <i class="fas fa-clock text-gray-400 text-[10px] mr-1"></i> 1 хоног
                        </div>
                    </div>
                    <div class="p-4 pt-2 relative">
                        <div class="flex justify-between items-start">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Boldoo" class="w-10 h-10 rounded-full border-2 border-white bg-gray-100 -mt-7 object-cover shadow-sm">
                            <div class="text-right">
                                <span class="block text-green-600 font-bold text-lg">5,000₮</span>
                            </div>
                        </div>
                        <h3 class="font-bold text-gray-800 mt-2 line-clamp-2 text-sm h-10 leading-relaxed group-hover:text-brand-600 transition-colors">
                            Текст шивэх болон хөрвүүлэх
                        </h3>
                        <div class="flex items-center gap-2 mt-2 text-xs text-gray-500">
                            <i class="fas fa-star text-yellow-400"></i> 4.0 (5)
                        </div>
                        <div class="service-action absolute inset-x-0 bottom-0 p-4 bg-white/95 backdrop-blur opacity-0 translate-y-2 transition-all duration-300 flex items-center justify-center">
                            <a href="service-details.php" class="w-full bg-brand-600 text-white text-center py-2 rounded-lg font-medium text-sm hover:bg-brand-700 shadow-lg shadow-brand-500/20">
                                Дэлгэрэнгүй
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Pagination -->
            <div class="flex justify-center mt-10">
                <nav class="flex items-center gap-2">
                    <a href="#" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </a>
                    <a href="#" class="w-8 h-8 flex items-center justify-center rounded-lg bg-brand-600 text-white font-medium text-sm shadow-md shadow-brand-500/30">1</a>
                    <a href="#" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium text-sm transition">2</a>
                    <a href="#" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium text-sm transition">3</a>
                    <span class="px-2 text-gray-400">...</span>
                    <a href="#" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </a>
                </nav>
            </div>

            <?php include 'includes/footer.php' ?>