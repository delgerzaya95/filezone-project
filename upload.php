<?php 
// Хуудасны гарчиг тохируулах (header.php дотор ашиглагдана)
$page_title = "Файл оруулах - Filezone.mn";

// Header оруулах
include 'includes/header.php'; 
?>


    <div class="flex flex-1 max-w-7xl mx-auto w-full">
        
        <!-- Sidebar Navigation -->
        <aside class="hidden lg:block w-64 flex-shrink-0 py-6 pr-6 h-[calc(100vh-64px)] sticky top-16 overflow-y-auto no-scrollbar">
            
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

            <!-- Profile Menu -->
            <div class="space-y-1 mb-8">
                <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Миний цэс</h3>
                
                <a href="profile.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-chart-pie w-5 text-center"></i> Хяналтын самбар
                </a>
                <a href="profile.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-folder w-5 text-center"></i> Миний файлууд
                </a>
                <a href="profile.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-briefcase w-5 text-center"></i> Миний үйлчилгээ
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
            
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Шинэ файл оруулах</h1>
                <p class="text-sm text-gray-500">Та өөрийн бүтээл, бие даалт, дипломын ажлаа зарж орлого олоорой.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Column: Upload Form -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- 1. Drag & Drop Zone -->
                    <div class="bg-white border-2 border-dashed border-brand-300 rounded-2xl p-8 text-center hover:bg-brand-50/50 transition cursor-pointer group relative" id="drop-zone">
                        <input type="file" id="file-upload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="handleFileSelect(this)">
                        
                        <div class="w-16 h-16 bg-brand-100 text-brand-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition duration-300">
                            <i class="fas fa-cloud-upload-alt text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Файлаа энд чирж оруулах</h3>
                        <p class="text-sm text-gray-500 mb-4">Эсвэл дарж файлаа сонгоно уу</p>
                        <p class="text-xs text-gray-400">Зөвшөөрөгдөх: PDF, DOCX, XLSX, PPTX, ZIP (Max 50MB)</p>
                    </div>

                    <!-- Progress Bar (Hidden by default) -->
                    <div id="upload-progress" class="hidden bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700" id="file-name-display">my_file.pdf</span>
                            <span class="text-sm font-bold text-brand-600">45%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-brand-600 h-2 rounded-full transition-all duration-500" style="width: 45%"></div>
                        </div>
                    </div>

                    <!-- 2. File Details Form -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <h3 class="font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Файлын мэдээлэл</h3>
                        
                        <form class="space-y-6">
                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Файлын гарчиг <span class="text-red-500">*</span></label>
                                <input type="text" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition" placeholder="Жишээ: Санхүүгийн тайлангийн загвар 2025">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Category -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Ангилал <span class="text-red-500">*</span></label>
                                    <select class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none bg-white">
                                        <option value="">Сонгоно уу...</option>
                                        <option value="contracts">Гэрээ & Загвар</option>
                                        <option value="education">Диплом & Курс</option>
                                        <option value="finance">Санхүү & Excel</option>
                                        <option value="books">Ном & Товхимол</option>
                                        <option value="other">Бусад</option>
                                    </select>
                                </div>
                                <!-- Price -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Үнэ (MNT) <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="number" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition" placeholder="5000">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-xs">₮</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">0 гэж бичвэл "Үнэгүй" болно.</p>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Дэлгэрэнгүй тайлбар</label>
                                <textarea rows="5" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition" placeholder="Файлын агуулга, хуудасны тоо, онцлог талуудыг бичнэ үү..."></textarea>
                            </div>

                            <!-- Cover Image Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Нүүр зураг (Заавал биш)</label>
                                <div class="flex items-center gap-4">
                                    <div class="w-20 h-20 bg-gray-100 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400 overflow-hidden" id="preview-container">
                                        <i class="fas fa-image text-2xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <input type="file" id="cover-upload" class="hidden" onchange="handleCoverSelect(this)">
                                        <label for="cover-upload" class="inline-block bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-xs font-bold hover:bg-gray-50 cursor-pointer mb-1">
                                            Зураг сонгох
                                        </label>
                                        <p class="text-xs text-gray-500">Борлуулалтад нүүр зураг чухал нөлөөтэй. (JPG, PNG)</p>
                                    </div>
                                </div>
                            </div>

                            <hr class="border-gray-100">

                            <!-- Terms -->
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="terms" type="checkbox" class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="terms" class="font-medium text-gray-700">Би зөвшөөрч байна</label>
                                    <p class="text-gray-500 text-xs">Энэ файл нь миний оюуны өмч мөн бөгөөд бусдын зохиогчийн эрхийг зөрчөөгүй болно.</p>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="flex gap-4 pt-2">
                                <button type="submit" class="flex-1 bg-brand-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-brand-500/30 hover:bg-brand-700 hover:-translate-y-0.5 transition-all">
                                    Нийтлэх
                                </button>
                                <button type="button" onclick="history.back()" class="px-6 py-3 border border-gray-300 text-gray-600 font-bold rounded-xl hover:bg-gray-50 transition">
                                    Болих
                                </button>
                            </div>
                        </form>
                    </div>

                </div>

                <!-- Right Column: Tips -->
                <div class="lg:col-span-1">
                    <div class="bg-yellow-50 border border-yellow-100 rounded-2xl p-6 sticky top-24">
                        <h3 class="font-bold text-yellow-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-lightbulb"></i> Зөвлөмж
                        </h3>
                        <ul class="space-y-3 text-sm text-yellow-800">
                            <li class="flex gap-2">
                                <i class="fas fa-check-circle mt-0.5 text-yellow-600"></i>
                                <span>Гарчиг ойлгомжтой, тодорхой байх тусам хайлтад сайн илэрнэ.</span>
                            </li>
                            <li class="flex gap-2">
                                <i class="fas fa-check-circle mt-0.5 text-yellow-600"></i>
                                <span>Тайлбар хэсэгт файлын давуу талыг сайн бичээрэй.</span>
                            </li>
                            <li class="flex gap-2">
                                <i class="fas fa-check-circle mt-0.5 text-yellow-600"></i>
                                <span>Үнээ бодитой тогтоох нь борлуулалтыг нэмэгдүүлнэ.</span>
                            </li>
                            <li class="flex gap-2">
                                <i class="fas fa-check-circle mt-0.5 text-yellow-600"></i>
                                <span>Зохиогчийн эрхийн зөрчилтэй файл устгагдахыг анхаарна уу.</span>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>

            <?php include 'includes/footer.php' ?>