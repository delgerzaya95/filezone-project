<?php 
// Хуудасны гарчиг тохируулах (header.php дотор ашиглагдана)
$page_title = "Үйлчилгээний нөхцөл - Filezone.mn";

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

             <!-- Help Menu -->
             <div class="space-y-1 mb-8">
                <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Тусламж</h3>
                <a href="guides.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-question-circle w-5 text-center"></i> Түгээмэл асуулт
                </a>
                <a href="contact.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-envelope w-5 text-center"></i> Холбоо барих
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
            
            <!-- Page Header -->
            <div class="mb-8 border-b border-gray-200 pb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Үйлчилгээний нөхцөл</h1>
                <p class="text-gray-500 text-sm">Сүүлд шинэчлэгдсэн: 2025 оны 10-р сарын 08</p>
            </div>

            <!-- Terms Content -->
            <div class="prose prose-slate max-w-4xl prose-headings:font-bold prose-headings:text-gray-900 prose-p:text-gray-600 prose-li:text-gray-600">
                
                <div class="bg-white border border-gray-200 rounded-2xl p-8 mb-8 shadow-sm">
                    <h3>1. Нийтлэг үндэслэл</h3>
                    <p>1.1. Filezone.mn (цаашид "Сайт" гэх) нь хэрэглэгчдэд дижитал файл худалдах, худалдан авах, солилцох боломжийг олгох дундын зуучлалын платформ юм.</p>
                    <p>1.2. Энэхүү үйлчилгээний нөхцөл нь хэрэглэгч сайтад бүртгүүлэх, файл оруулах, татах, төлбөр төлөхтэй холбоотой харилцааг зохицуулна.</p>
                    <p>1.3. Хэрэглэгч сайтад бүртгүүлснээр энэхүү нөхцөлийг бүрэн хүлээн зөвшөөрсөнд тооцно.</p>
                </div>

                <div class="bg-white border border-gray-200 rounded-2xl p-8 mb-8 shadow-sm">
                    <h3>2. Хэрэглэгчийн эрх, үүрэг</h3>
                    <p>2.1. Хэрэглэгч нь өөрийн оруулсан файлын зохиогчийн эрхийг бүрэн хариуцна. Бусдын бүтээлийг зөвшөөрөлгүй хуулбарлах, худалдахыг хориглоно.</p>
                    <p>2.2. Хэрэглэгч өөрийн дансны аюулгүй байдлыг хангах, нууц үгээ бусдад дамжуулахгүй байх үүрэгтэй.</p>
                    <p>2.3. Хэрэглэгч худалдан авсан файлаа зөвхөн хувийн хэрэгцээнд ашиглах эрхтэй бөгөөд гуравдагч этгээдэд дамжуулах, олон нийтэд тараахыг хориглоно.</p>
                </div>

                <div class="bg-white border border-gray-200 rounded-2xl p-8 mb-8 shadow-sm">
                    <h3>3. Төлбөр тооцоо & Шимтгэл</h3>
                    <p>3.1. Сайтад байршуулсан файлын үнийг нийтлэгч өөрөө тогтооно.</p>
                    <p>3.2. Худалдан авалт хийгдэх бүрд нийт дүнгийн <strong>10%-ийн шимтгэл</strong> суутгагдаж, үлдэгдэл дүн нийтлэгчийн хэтэвчинд орно.</p>
                    <p>3.3. Хэрэглэгч хэтэвчин дэх мөнгөн дүнгээ 5,000₮-с дээш болсон тохиолдолд өөрийн банкны данс руу татах эрхтэй.</p>
                </div>

                <div class="bg-white border border-gray-200 rounded-2xl p-8 mb-8 shadow-sm">
                    <h3>4. Хориглох зүйлс</h3>
                    <ul>
                        <li>Монгол Улсын хууль тогтоомжийг зөрчсөн агуулгатай файл оруулах.</li>
                        <li>Вирус, хортой код агуулсан файл оруулах.</li>
                        <li>Насанд хүрэгчдэд зориулсан (+18) контент оруулах.</li>
                        <li>Бусдын нэр төр, алдар хүндэд халдсан мэдээлэл оруулах.</li>
                    </ul>
                </div>

                <div class="bg-white border border-gray-200 rounded-2xl p-8 shadow-sm">
                    <h3>5. Хариуцлага</h3>
                    <p>5.1. Сайт нь хэрэглэгчийн оруулсан файлын агуулга, чанар, үнэн зөв байдалд шууд хариуцлага хүлээхгүй.</p>
                    <p>5.2. Техникийн саатал, сүлжээний доголдлоос үүдэн гарах хохирлыг сайт хариуцахгүй боловч хэвийн ажиллагааг хангахын төлөө бүх боломжоороо ажиллана.</p>
                </div>

            </div>

            <!-- Footer -->
            <div class="mt-12 border-t border-gray-200 py-8 text-center bg-white rounded-t-2xl">
                <p class="text-sm text-gray-500 mb-2">&copy; 2025 Filezone.mn. Бүх эрх хуулиар хамгаалагдсан.</p>
                <div class="flex justify-center gap-4 text-xs text-gray-400">
                    <a href="terms.php" class="text-brand-600 font-medium">Үйлчилгээний нөхцөл</a>
                    <span>•</span>
                    <a href="privacy.php" class="hover:text-gray-600">Нууцлалын бодлого</a>
                </div>
            </div>

        </main>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu-overlay" class="fixed inset-0 bg-gray-900/50 z-40 hidden" onclick="toggleMenu()"></div>
    
    <!-- Mobile Menu Sidebar -->
    <div id="mobile-menu" class="fixed inset-y-0 left-0 w-64 bg-white z-50 transform -translate-x-full transition-transform duration-300 ease-in-out border-r border-gray-200">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <span class="font-bold text-lg text-gray-800">Цэс</span>
            <button onclick="toggleMenu()" class="text-gray-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 space-y-4">
            <a href="upload.php" class="block w-full text-center py-2.5 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-lg font-bold shadow-lg">
                <i class="fas fa-cloud-upload-alt mr-2"></i> Файл оруулах
            </a>

            <div class="grid grid-cols-2 gap-3">
                <a href="login.php" class="block w-full text-center py-2.5 border border-gray-200 rounded-xl font-semibold text-gray-600 hover:bg-gray-50">Нэвтрэх</a>
                <a href="register.php" class="block w-full text-center py-2.5 bg-slate-700 text-white rounded-xl font-bold hover:bg-slate-800 shadow-sm">Бүртгүүлэх</a>
            </div>
            
            <hr class="border-gray-100">

            <a href="index.php" class="flex items-center gap-3 text-gray-600 font-medium">
                <i class="fas fa-home w-5 text-center text-gray-400"></i> Нүүр хуудас
            </a>
            <a href="guides.php" class="flex items-center gap-3 text-gray-600 font-medium">
                <i class="fas fa-question-circle w-5 text-center text-gray-400"></i> Тусламж
            </a>
            <a href="contact.php" class="flex items-center gap-3 text-gray-600 font-medium">
                <i class="fas fa-envelope w-5 text-center text-gray-400"></i> Холбоо барих
            </a>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('mobile-menu');
            const overlay = document.getElementById('mobile-menu-overlay');
            
            if (menu.classList.contains('-translate-x-full')) {
                menu.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                menu.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
    </script>
</body>
</html>