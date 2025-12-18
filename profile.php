<?php 
// Хуудасны гарчиг тохируулах (header.php дотор ашиглагдана)
$page_title = "Хувийн хуудас - Filezone.mn";

// Header оруулах
include 'includes/header.php'; 
?>


    <div class="flex flex-1 max-w-7xl mx-auto w-full">
        
        <!-- Sidebar Navigation -->
        <aside class="hidden lg:block w-64 flex-shrink-0 py-6 pr-6 h-[calc(100vh-64px)] sticky top-16 overflow-y-auto no-scrollbar">
            
            <!-- User Info Card -->
            <div class="bg-white border border-gray-200 rounded-xl p-4 mb-6 shadow-sm text-center">
                <div class="relative w-20 h-20 mx-auto mb-3">
                    <img src="assets/avatars/default.png" class="w-full h-full rounded-full object-cover border-2 border-brand-100">
                    <button class="absolute bottom-0 right-0 w-6 h-6 bg-white border border-gray-200 rounded-full flex items-center justify-center text-gray-500 hover:text-brand-600 shadow-sm text-xs">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                <h3 class="font-bold text-gray-900">Bat-Erdene</h3>
                <p class="text-xs text-gray-500 mb-3">bat@example.com</p>
                <div class="flex justify-center gap-2">
                    <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded font-medium">Verified</span>
                    <span class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded font-medium">Seller</span>
                </div>
            </div>

            <!-- Profile Menu -->
            <div class="space-y-1 mb-8">
                <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Миний цэс</h3>
                
                <button onclick="openTab(event, 'section-dashboard')" class="tab-btn sidebar-link w-full flex items-center gap-3 px-3 py-2 text-brand-600 bg-brand-50 rounded-lg font-medium transition-colors">
                    <i class="fas fa-chart-pie w-5 text-center"></i> Хяналтын самбар
                </button>
                <button onclick="openTab(event, 'section-files')" class="tab-btn sidebar-link w-full flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-folder w-5 text-center"></i> Миний файлууд
                </button>
                <button onclick="openTab(event, 'section-services')" class="tab-btn sidebar-link w-full flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-briefcase w-5 text-center"></i> Үйлчилгээ
                </button>
                <button onclick="openTab(event, 'section-wallet')" class="tab-btn sidebar-link w-full flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-wallet w-5 text-center"></i> Хэтэвч & Татах
                </button>
                <button onclick="openTab(event, 'section-settings')" class="tab-btn sidebar-link w-full flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-cog w-5 text-center"></i> Тохиргоо
                </button>
                <a href="logout.php" class="w-full flex items-center gap-3 px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg font-medium transition-colors mt-4">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i> Гарах
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
            
            <!-- SECTION 1: DASHBOARD (Default) -->
            <div id="section-dashboard" class="tab-content profile-section fade-in">
                <!-- Page Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Хяналтын самбар</h1>
                        <p class="text-sm text-gray-500">Тавтай морил, өнөөдөр танд юу шинэ байна?</p>
                    </div>
                    <div class="flex gap-3">
                        <a href="upload.php" class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                            <i class="fas fa-plus mr-1"></i> Файл нэмэх
                        </a>
                        <a href="add_service.php" class="bg-brand-600 text-white hover:bg-brand-700 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm shadow-brand-500/30">
                            <i class="fas fa-briefcase mr-1"></i> Үйлчилгээ нэмэх
                        </a>
                    </div>
                </div>

                <!-- Stats Grid (First version style) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-gray-500 text-xs uppercase font-medium">Нийт орлого</p>
                                <h3 class="text-2xl font-bold text-gray-900 mt-1">150,000₮</h3>
                            </div>
                            <div class="w-10 h-10 bg-green-100 text-green-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-wallet"></i>
                            </div>
                        </div>
                        <div class="text-xs text-green-600 font-medium flex items-center gap-1">
                            <i class="fas fa-arrow-up"></i> 12% өмнөх сараас
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-gray-500 text-xs uppercase font-medium">Идэвхтэй файл</p>
                                <h3 class="text-2xl font-bold text-gray-900 mt-1">24</h3>
                            </div>
                            <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-file-alt"></i>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">
                            Нийт таталт: <span class="font-bold text-gray-800">1,205</span>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-gray-500 text-xs uppercase font-medium">Идэвхтэй захиалга</p>
                                <h3 class="text-2xl font-bold text-gray-900 mt-1">3</h3>
                            </div>
                            <div class="w-10 h-10 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-tasks"></i>
                            </div>
                        </div>
                        <div class="text-xs text-orange-500 font-medium flex items-center gap-1">
                            <i class="fas fa-clock"></i> 2 хугацаа дөхсөн
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 p-5 rounded-xl text-white shadow-lg relative overflow-hidden">
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div>
                                <p class="text-gray-400 text-xs uppercase font-medium">Үлдэгдэл</p>
                                <h3 class="text-2xl font-bold text-white mt-1">45,000₮</h3>
                            </div>
                            <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center">
                                <i class="fas fa-coins text-yellow-400"></i>
                            </div>
                        </div>
                        <button onclick="switchTab('wallet', document.querySelectorAll('.sidebar-link')[3])" class="w-full bg-white/10 hover:bg-white/20 text-white text-xs font-bold py-2 rounded-lg transition border border-white/10">
                            Мөнгө татах
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Recent Sales / Activities -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Sales Section -->
                        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                                <h3 class="font-bold text-gray-900 text-sm">Сүүлийн борлуулалт</h3>
                                <a href="#" class="text-xs font-medium text-brand-600 hover:text-brand-700">Бүгдийг үзэх</a>
                            </div>
                            <div class="divide-y divide-gray-100">
                                <!-- Item 1 -->
                                <div class="p-4 flex items-center gap-4 hover:bg-gray-50 transition">
                                    <div class="w-10 h-10 bg-green-50 text-green-600 rounded-lg flex items-center justify-center flex-shrink-0 font-bold text-xs">
                                        XLSX
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 truncate">Санхүүгийн тайлан 2025</h4>
                                        <p class="text-xs text-gray-500">2 минутын өмнө • User123</p>
                                    </div>
                                    <span class="font-bold text-green-600 text-sm">+5,000₮</span>
                                </div>
                                <!-- Item 2 -->
                                <div class="p-4 flex items-center gap-4 hover:bg-gray-50 transition">
                                    <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center flex-shrink-0 font-bold text-xs">
                                        DOCX
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 truncate">Байгууллагын журам</h4>
                                        <p class="text-xs text-gray-500">1 цагийн өмнө • Boldoo</p>
                                    </div>
                                    <span class="font-bold text-green-600 text-sm">+3,000₮</span>
                                </div>
                                <!-- Item 3 -->
                                <div class="p-4 flex items-center gap-4 hover:bg-gray-50 transition">
                                    <div class="w-10 h-10 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center flex-shrink-0 font-bold text-xs">
                                        SVC
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 truncate">CV Засах үйлчилгээ</h4>
                                        <p class="text-xs text-gray-500">5 цагийн өмнө • Sarnai</p>
                                    </div>
                                    <span class="font-bold text-green-600 text-sm">+10,000₮</span>
                                </div>
                            </div>
                        </div>

                        <!-- My Files (Table) - RESTORED -->
                        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                                <h3 class="font-bold text-gray-900 text-sm">Миний файлууд</h3>
                                <a href="upload.php" class="text-xs font-medium text-brand-600 hover:text-brand-700">+ Шинэ</a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">Файлын нэр</th>
                                            <th scope="col" class="px-6 py-3">Үнэ</th>
                                            <th scope="col" class="px-6 py-3">Таталт</th>
                                            <th scope="col" class="px-6 py-3 text-right">Үйлдэл</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="bg-white border-b hover:bg-gray-50">
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                                Санхүүгийн тайлан.xlsx
                                            </td>
                                            <td class="px-6 py-4">5,000₮</td>
                                            <td class="px-6 py-4">120</td>
                                            <td class="px-6 py-4 text-right">
                                                <a href="#" class="font-medium text-brand-600 hover:underline mr-3">Засах</a>
                                                <a href="#" class="font-medium text-red-600 hover:underline">Устгах</a>
                                            </td>
                                        </tr>
                                        <tr class="bg-white border-b hover:bg-gray-50">
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                                Хөдөлмөрийн гэрээ.docx
                                            </td>
                                            <td class="px-6 py-4 text-green-600">Үнэгүй</td>
                                            <td class="px-6 py-4">345</td>
                                            <td class="px-6 py-4 text-right">
                                                <a href="#" class="font-medium text-brand-600 hover:underline mr-3">Засах</a>
                                                <a href="#" class="font-medium text-red-600 hover:underline">Устгах</a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Account & Notifications -->
                    <div class="lg:col-span-1 space-y-6">
                        
                        <!-- Account Status -->
                        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                            <h3 class="font-bold text-gray-900 text-sm mb-4">Дансны төлөв</h3>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-gray-500">Түвшин</span>
                                <span class="text-sm font-bold text-brand-600">Pro Seller</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 mb-4">
                                <div class="bg-brand-600 h-1.5 rounded-full" style="width: 70%"></div>
                            </div>
                            <p class="text-xs text-gray-400 mb-6">Дараагийн түвшинд хүрэхэд 30,000₮ дутуу байна.</p>
                            
                            <button class="w-full border border-gray-200 text-gray-600 text-xs font-bold py-2 rounded-lg hover:bg-gray-50 transition">
                                Дэлгэрэнгүй үзэх
                            </button>
                        </div>

                        <!-- Notifications -->
                        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                            <h3 class="font-bold text-gray-900 text-sm mb-4">Мэдэгдэл</h3>
                            <div class="space-y-4">
                                <div class="flex gap-3">
                                    <div class="w-2 h-2 mt-1.5 bg-blue-500 rounded-full flex-shrink-0"></div>
                                    <div>
                                        <p class="text-xs text-gray-800">Шинэ захиалга ирлээ: <strong>"Орчуулга"</strong></p>
                                        <span class="text-[10px] text-gray-400">10 минутын өмнө</span>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="w-2 h-2 mt-1.5 bg-green-500 rounded-full flex-shrink-0"></div>
                                    <div>
                                        <p class="text-xs text-gray-800">Таны файлын борлуулалт амжилттай.</p>
                                        <span class="text-[10px] text-gray-400">2 цагийн өмнө</span>
                                    </div>
                                </div>
                                <div class="flex gap-3 opacity-50">
                                    <div class="w-2 h-2 mt-1.5 bg-gray-300 rounded-full flex-shrink-0"></div>
                                    <div>
                                        <p class="text-xs text-gray-800">Системийн шинэчлэл хийгдлээ.</p>
                                        <span class="text-[10px] text-gray-400">Өчигдөр</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- SECTION 2: MY FILES -->
            <div id="section-files" class="tab-content profile-section fade-in hidden">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Миний файлууд</h1>
                    <a href="upload.php" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-brand-700 transition"><i class="fas fa-plus mr-2"></i> Шинэ файл</a>
                </div>
                
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="px-6 py-4">Файлын нэр</th>
                                    <th scope="col" class="px-6 py-4">Огноо</th>
                                    <th scope="col" class="px-6 py-4">Үнэ</th>
                                    <th scope="col" class="px-6 py-4">Таталт</th>
                                    <th scope="col" class="px-6 py-4">Орлого</th>
                                    <th scope="col" class="px-6 py-4 text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr class="bg-white hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900 flex items-center gap-3">
                                        <div class="w-8 h-8 rounded bg-green-100 text-green-600 flex items-center justify-center"><i class="fas fa-file-excel"></i></div>
                                        Санхүүгийн тайлан.xlsx
                                    </td>
                                    <td class="px-6 py-4">2025-02-15</td>
                                    <td class="px-6 py-4 font-bold text-gray-700">5,000₮</td>
                                    <td class="px-6 py-4">120</td>
                                    <td class="px-6 py-4 text-green-600 font-bold">600,000₮</td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-blue-600 hover:text-blue-800 mx-2"><i class="fas fa-edit"></i></button>
                                        <button class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <tr class="bg-white hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900 flex items-center gap-3">
                                        <div class="w-8 h-8 rounded bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fas fa-file-word"></i></div>
                                        Хөдөлмөрийн гэрээ.docx
                                    </td>
                                    <td class="px-6 py-4">2025-01-20</td>
                                    <td class="px-6 py-4 text-green-600">Үнэгүй</td>
                                    <td class="px-6 py-4">345</td>
                                    <td class="px-6 py-4">-</td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-blue-600 hover:text-blue-800 mx-2"><i class="fas fa-edit"></i></button>
                                        <button class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: SERVICES -->
            <div id="section-services" class="tab-content profile-section fade-in hidden">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Миний үйлчилгээ</h1>
                    <a href="add_service.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-purple-700 transition"><i class="fas fa-plus mr-2"></i> Үйлчилгээ нэмэх</a>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="flex gap-6">
                        <button class="border-b-2 border-purple-600 py-3 text-sm font-bold text-purple-600">Миний зарууд</button>
                        <button class="border-b-2 border-transparent py-3 text-sm font-medium text-gray-500 hover:text-gray-700">Ирсэн захиалга (3)</button>
                    </nav>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Service Card -->
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition group">
                        <div class="h-32 bg-gray-200 relative">
                            <img src="https://images.unsplash.com/photo-1542744173-8e7e53415bb0?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Service" class="w-full h-full object-cover">
                            <span class="absolute top-2 right-2 bg-green-500 text-white text-[10px] font-bold px-2 py-0.5 rounded">Идэвхтэй</span>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-gray-900 text-sm mb-2 line-clamp-2">Англи хэлнээс Монгол хэл рүү мэргэжлийн орчуулга хийнэ</h3>
                            <div class="flex justify-between items-center text-xs text-gray-500 mt-3">
                                <span><i class="fas fa-eye mr-1"></i> 450 үзсэн</span>
                                <span class="font-bold text-gray-900">15,000₮</span>
                            </div>
                            <div class="mt-4 flex gap-2">
                                <button class="flex-1 bg-gray-100 text-gray-600 py-1.5 rounded text-xs font-bold hover:bg-gray-200">Засах</button>
                                <button class="flex-1 bg-gray-100 text-gray-600 py-1.5 rounded text-xs font-bold hover:bg-gray-200">Түр зогсоох</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: WALLET -->
            <div id="section-wallet" class="tab-content profile-section fade-in hidden">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Хэтэвч & Татан авалт</h1>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Balance Card -->
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 p-8 rounded-2xl text-white shadow-xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-10 -mt-10"></div>
                        <p class="text-gray-400 text-sm uppercase font-medium mb-1">Боломжит үлдэгдэл</p>
                        <h2 class="text-4xl font-bold mb-6">45,000₮</h2>
                        <div class="flex justify-between items-end">
                            <div>
                                <p class="text-xs text-gray-400">Хүлээгдэж буй</p>
                                <p class="font-bold text-yellow-400">15,000₮</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-400">Нийт татсан</p>
                                <p class="font-bold">120,000₮</p>
                            </div>
                        </div>
                    </div>

                    <!-- Withdrawal Form -->
                    <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <h3 class="font-bold text-gray-900 mb-4">Мөнгө татах хүсэлт</h3>
                        <form class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Банк сонгох</label>
                                    <select class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-sm">
                                        <option>Хаан банк</option>
                                        <option>Голомт банк</option>
                                        <option>Төрийн банк</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Дансны дугаар</label>
                                    <input type="number" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-sm" placeholder="5000000000">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Данс эзэмшигчийн нэр</label>
                                <input type="text" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-sm" placeholder="Батын Болд">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Татах дүн (Мин: 5,000₮)</label>
                                <input type="number" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-sm" placeholder="10000">
                            </div>
                            <button type="button" class="w-full bg-brand-600 text-white font-bold py-3 rounded-lg hover:bg-brand-700 transition shadow-lg shadow-brand-500/20">
                                Хүсэлт илгээх
                            </button>
                        </form>
                    </div>
                </div>

                <h3 class="font-bold text-gray-900 mt-8 mb-4">Гүйлгээний түүх</h3>
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Огноо</th>
                                <th class="px-6 py-3">Тайлбар</th>
                                <th class="px-6 py-3">Төлөв</th>
                                <th class="px-6 py-3 text-right">Дүн</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-white border-b">
                                <td class="px-6 py-4">2025-02-18</td>
                                <td class="px-6 py-4">Зарлага (Хаан банк - 5059...)</td>
                                <td class="px-6 py-4"><span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs font-bold">Амжилттай</span></td>
                                <td class="px-6 py-4 text-right text-red-600 font-bold">-20,000₮</td>
                            </tr>
                            <tr class="bg-white border-b">
                                <td class="px-6 py-4">2025-02-15</td>
                                <td class="px-6 py-4">Орлого (Санхүүгийн тайлан)</td>
                                <td class="px-6 py-4"><span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs font-bold">Амжилттай</span></td>
                                <td class="px-6 py-4 text-right text-green-600 font-bold">+5,000₮</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECTION 5: SETTINGS -->
            <div id="section-settings" class="tab-content profile-section fade-in hidden">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Хувийн тохиргоо</h1>
                
                <div class="max-w-2xl bg-white border border-gray-200 rounded-2xl p-8 shadow-sm">
                    <div class="flex items-center gap-6 mb-8">
                        <img src="assets/avatars/default.png" class="w-20 h-20 rounded-full object-cover border-2 border-gray-200">
                        <div>
                            <button class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50">Зураг солих</button>
                            <p class="text-xs text-gray-500 mt-2">JPG, PNG өргөтгөлтэй, дээд тал нь 2MB</p>
                        </div>
                    </div>

                    <form class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Хэрэглэгчийн нэр</label>
                                <input type="text" value="Bat-Erdene" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-brand-500 focus:border-brand-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Утасны дугаар</label>
                                <input type="text" value="88112233" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-brand-500 focus:border-brand-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Имэйл хаяг</label>
                            <input type="email" value="bat@example.com" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-brand-500 focus:border-brand-500" disabled>
                            <p class="text-xs text-gray-500 mt-1">Имэйл хаягийг солих боломжгүй.</p>
                        </div>
                        
                        <hr class="border-gray-200">
                        
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 mb-4">Нууц үг солих</h3>
                            <div class="space-y-4">
                                <input type="password" placeholder="Хуучин нууц үг" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                                <input type="password" placeholder="Шинэ нууц үг" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                                <input type="password" placeholder="Шинэ нууц үг давтах" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                            </div>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Цуцлах</button>
                            <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-brand-600 rounded-lg hover:bg-brand-700 shadow-sm">Хадгалах</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php include 'includes/footer.php' ?>