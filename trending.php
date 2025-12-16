<?php include 'includes/header.php'; ?>

<!-- Main Wrapper -->
<div class="w-full max-w-[1400px] mx-auto flex items-start">
    
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="flex-1 w-full min-w-0 p-4 lg:p-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <span class="p-2 bg-orange-100 text-orange-500 rounded-lg">
                    <i class="fas fa-fire"></i>
                </span>
                Эрэлттэй файлууд
            </h1>
            <p class="text-gray-500 mt-2 ml-12 text-sm">Хамгийн олон удаа татагдсан, өндөр үнэлгээтэй файлууд.</p>
        </div>

        <!-- Time Filter Tabs -->
        <div class="flex gap-2 mb-8 overflow-x-auto pb-2 no-scrollbar">
            <button class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-full whitespace-nowrap shadow-md transform scale-105">
                Өнөөдөр
            </button>
            <button class="px-4 py-2 bg-white border border-gray-200 text-gray-600 text-sm font-medium rounded-full hover:bg-gray-50 hover:border-gray-300 transition whitespace-nowrap">
                Энэ 7 хоног
            </button>
            <button class="px-4 py-2 bg-white border border-gray-200 text-gray-600 text-sm font-medium rounded-full hover:bg-gray-50 hover:border-gray-300 transition whitespace-nowrap">
                Энэ сар
            </button>
        </div>

        <!-- Trending Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Card 1 (Rank #1) -->
            <div class="group relative bg-white rounded-2xl border border-gray-200 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <!-- Rank Badge -->
                <div class="absolute -top-3 -left-3 w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 text-white font-bold text-lg rounded-xl flex items-center justify-center shadow-lg z-10 border-2 border-white">
                    1
                </div>
                
                <div class="p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 text-xl font-bold">
                                <i class="fas fa-file-word"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 line-clamp-1 group-hover:text-brand-600 transition">Бизнес төлөвлөгөө 2025</h3>
                                <p class="text-xs text-gray-500">Бизнес & Санхүү</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 mb-4 text-xs text-gray-500 bg-gray-50 p-2 rounded-lg">
                        <span class="flex items-center gap-1 text-orange-500 font-semibold">
                            <i class="fas fa-star"></i> 4.9
                        </span>
                        <span class="w-px h-3 bg-gray-300"></span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-download"></i> 1.2k татсан
                        </span>
                    </div>

                    <div class="flex items-center justify-between mt-auto">
                        <div class="flex items-center gap-2">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Bataa" alt="User" class="w-6 h-6 rounded-full border border-gray-200">
                            <span class="text-xs font-medium text-gray-600">Батаа Б.</span>
                        </div>
                        <span class="text-brand-600 font-bold">15,000₮</span>
                    </div>
                </div>
            </div>

            <!-- Card 2 (Rank #2) -->
            <div class="group relative bg-white rounded-2xl border border-gray-200 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <div class="absolute -top-3 -left-3 w-10 h-10 bg-gray-200 text-gray-600 font-bold text-lg rounded-xl flex items-center justify-center shadow-md z-10 border-2 border-white">
                    2
                </div>
                
                <div class="p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center text-green-600 text-xl font-bold">
                                <i class="fas fa-file-excel"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 line-clamp-1 group-hover:text-brand-600 transition">Цалингийн хүснэгт (Автомат)</h3>
                                <p class="text-xs text-gray-500">Нягтлан бодох</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 mb-4 text-xs text-gray-500 bg-gray-50 p-2 rounded-lg">
                        <span class="flex items-center gap-1 text-orange-500 font-semibold">
                            <i class="fas fa-star"></i> 4.8
                        </span>
                        <span class="w-px h-3 bg-gray-300"></span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-download"></i> 856 татсан
                        </span>
                    </div>

                    <div class="flex items-center justify-between mt-auto">
                        <div class="flex items-center gap-2">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Saraa" alt="User" class="w-6 h-6 rounded-full border border-gray-200">
                            <span class="text-xs font-medium text-gray-600">Сараа М.</span>
                        </div>
                        <span class="text-brand-600 font-bold">5,000₮</span>
                    </div>
                </div>
            </div>

            <!-- Card 3 (Rank #3) -->
            <div class="group relative bg-white rounded-2xl border border-gray-200 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <div class="absolute -top-3 -left-3 w-10 h-10 bg-orange-100 text-orange-700 font-bold text-lg rounded-xl flex items-center justify-center shadow-md z-10 border-2 border-white">
                    3
                </div>
                
                <div class="p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600 text-xl font-bold">
                                <i class="fas fa-file-powerpoint"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 line-clamp-1 group-hover:text-brand-600 transition">Маркетинг танилцуулга</h3>
                                <p class="text-xs text-gray-500">Маркетинг</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 mb-4 text-xs text-gray-500 bg-gray-50 p-2 rounded-lg">
                        <span class="flex items-center gap-1 text-orange-500 font-semibold">
                            <i class="fas fa-star"></i> 4.7
                        </span>
                        <span class="w-px h-3 bg-gray-300"></span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-download"></i> 643 татсан
                        </span>
                    </div>

                    <div class="flex items-center justify-between mt-auto">
                        <div class="flex items-center gap-2">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Naraa" alt="User" class="w-6 h-6 rounded-full border border-gray-200">
                            <span class="text-xs font-medium text-gray-600">Наран</span>
                        </div>
                        <span class="text-brand-600 font-bold">10,000₮</span>
                    </div>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="group relative bg-white rounded-2xl border border-gray-200 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <div class="p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center text-red-600 text-xl font-bold">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 line-clamp-1 group-hover:text-brand-600 transition">Бие даалтын загвар</h3>
                                <p class="text-xs text-gray-500">Оюутан</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 mb-4 text-xs text-gray-500 bg-gray-50 p-2 rounded-lg">
                        <span class="flex items-center gap-1 text-orange-500 font-semibold">
                            <i class="fas fa-star"></i> 4.5
                        </span>
                        <span class="w-px h-3 bg-gray-300"></span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-download"></i> 420 татсан
                        </span>
                    </div>

                    <div class="flex items-center justify-between mt-auto">
                        <div class="flex items-center gap-2">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Bold" alt="User" class="w-6 h-6 rounded-full border border-gray-200">
                            <span class="text-xs font-medium text-gray-600">Болд</span>
                        </div>
                        <span class="text-green-600 font-bold text-sm">Үнэгүй</span>
                    </div>
                </div>
            </div>

            <!-- Card 5 -->
            <div class="group relative bg-white rounded-2xl border border-gray-200 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <div class="p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 text-xl font-bold">
                                <i class="fas fa-file-contract"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 line-clamp-1 group-hover:text-brand-600 transition"> түрээсийн гэрээ</h3>
                                <p class="text-xs text-gray-500">Хууль, Эрх зүй</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 mb-4 text-xs text-gray-500 bg-gray-50 p-2 rounded-lg">
                        <span class="flex items-center gap-1 text-orange-500 font-semibold">
                            <i class="fas fa-star"></i> 4.9
                        </span>
                        <span class="w-px h-3 bg-gray-300"></span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-download"></i> 380 татсан
                        </span>
                    </div>

                    <div class="flex items-center justify-between mt-auto">
                        <div class="flex items-center gap-2">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Tuya" alt="User" class="w-6 h-6 rounded-full border border-gray-200">
                            <span class="text-xs font-medium text-gray-600">Туяа</span>
                        </div>
                        <span class="text-brand-600 font-bold">2,000₮</span>
                    </div>
                </div>
            </div>

            <!-- Card 6 -->
            <div class="group relative bg-white rounded-2xl border border-gray-200 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <div class="p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-pink-50 flex items-center justify-center text-pink-600 text-xl font-bold">
                                <i class="fas fa-book"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 line-clamp-1 group-hover:text-brand-600 transition">Англи хэлний 3000 үг</h3>
                                <p class="text-xs text-gray-500">Гадаад хэл</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 mb-4 text-xs text-gray-500 bg-gray-50 p-2 rounded-lg">
                        <span class="flex items-center gap-1 text-orange-500 font-semibold">
                            <i class="fas fa-star"></i> 4.6
                        </span>
                        <span class="w-px h-3 bg-gray-300"></span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-download"></i> 310 татсан
                        </span>
                    </div>

                    <div class="flex items-center justify-between mt-auto">
                        <div class="flex items-center gap-2">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Ganaa" alt="User" class="w-6 h-6 rounded-full border border-gray-200">
                            <span class="text-xs font-medium text-gray-600">Ганбаа</span>
                        </div>
                        <span class="text-brand-600 font-bold">5,000₮</span>
                    </div>
                </div>
            </div>

        </div>

    <?php include 'includes/footer.php'; ?>