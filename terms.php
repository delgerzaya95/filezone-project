<?php 
// Хуудасны гарчиг тохируулах
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
            <a href="services.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-briefcase w-5 text-center"></i> Үйлчилгээ
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
            <p class="text-gray-500 text-sm">Сүүлд шинэчлэгдсэн: <?php echo date('Y оны m-р сарын d'); ?></p>
        </div>

        <!-- Terms Content -->
        <div class="prose prose-slate max-w-4xl prose-headings:font-bold prose-headings:text-gray-900 prose-p:text-gray-600 prose-li:text-gray-600">
            
            <div class="bg-white border border-gray-200 rounded-2xl p-8 mb-8 shadow-sm">
                <h3>1. Нийтлэг үндэслэл</h3>
                <p>1.1. Filezone.mn (цаашид "Сайт" гэх) нь хэрэглэгчдэд дижитал файл худалдах, худалдан авах, болон мэргэжлийн үйлчилгээ үзүүлэх, авах боломжийг олгох дундын зуучлалын платформ юм.</p>
                <p>1.2. Энэхүү үйлчилгээний нөхцөл нь хэрэглэгч сайтад бүртгүүлэх, файл оруулах, үйлчилгээ захиалах, төлбөр төлөхтэй холбоотой харилцааг зохицуулна.</p>
                <p>1.3. Хэрэглэгч сайтад бүртгүүлснээр энэхүү нөхцөлийг бүрэн хүлээн зөвшөөрсөнд тооцно.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-8 mb-8 shadow-sm">
                <h3>2. Үйлчилгээний захиалга ба Гүйцэтгэл</h3>
                <p><strong>2.1. Захиалга үүсэх:</strong> Захиалагч төлбөрөө 100% урьдчилж төлснөөр захиалга "Хүлээгдэж буй" (Pending) төлөвт шилжинэ. Төлбөр нь Filezone системийн хүлээлгийн дансанд (Escrow) найдвартай хадгалагдана.</p>
                <p><strong>2.2. Ажил эхлүүлэх (24 цагийн дүрэм):</strong> Гүйцэтгэгч нь захиалга ирснээс хойш <strong>24 цагийн дотор</strong> "Ажиллаж эхлэх" товчийг дарах үүрэгтэй. Хэрэв 24 цагийн дотор эхлүүлэхгүй бол захиалга автоматаар цуцлагдаж, төлбөр захиалагчид 100% буцаан олгогдоно.</p>
                <p><strong>2.3. Ажил хүлээлгэн өгөх:</strong> Гүйцэтгэгч ажлаа дуусгаад "Хүлээлгэн өгөх" (Deliver) товчийг дарж, ажлын үр дүнг системд оруулна.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-8 mb-8 shadow-sm">
                <h3>3. Баталгаажуулалт ба Автомат хаалт</h3>
                <p><strong>3.1. Захиалагчийн эрх:</strong> Ажил хүлээлгэн өгснөөс хойш захиалагч ажлыг шалгаж "Хүлээн авах" эсвэл "Засвар хүсэх" эрхтэй.</p>
                <p><strong>3.2. Автомат баталгаажуулалт (72 цагийн дүрэм):</strong> Хэрэв захиалагч ажил хүлээлгэн өгснөөс хойш <strong>72 цагийн дотор</strong> ямар нэгэн үйлдэл хийхгүй (хариу өгөхгүй) бол систем ажлыг автоматаар "Дууссан" гэж тооцож, төлбөрийг гүйцэтгэгчид шилжүүлнэ.</p>
                <p><strong>3.3. Засвар хүсэх:</strong> Захиалагч ажлын чанарт сэтгэл ханамжгүй бол үндэслэл бүхий шалтгаанаар засвар (Revision) хийлгэх хүсэлт гаргаж болно. Энэ үед захиалгын хугацаа сунгагдана.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-8 mb-8 shadow-sm">
                <h3>4. Төлбөр тооцоо & Шимтгэл</h3>
                <p>4.1. Сайтад байршуулсан файл болон үйлчилгээний үнийг нийтлэгч өөрөө тогтооно.</p>
                <p>4.2. Худалдан авалт хийгдэх бүрд нийт дүнгийн <strong>10%-ийн шимтгэл</strong> суутгагдаж, үлдэгдэл дүн нийтлэгчийн хэтэвчинд орно.</p>
                <p>4.3. Хэрэглэгч хэтэвчин дэх мөнгөн дүнгээ 5,000₮-с дээш болсон тохиолдолд өөрийн банкны данс руу татах эрхтэй. Гүйлгээ ажлын 1-3 хоногт хийгдэнэ.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-8 mb-8 shadow-sm">
                <h3>5. Маргаан шийдвэрлэх</h3>
                <p>5.1. Хэрэглэгч хоорондын үл ойлголцол, чанарын асуудал гарвал "Маргаан үүсгэх" (Dispute) сонголтыг ашиглан Админд хандах боломжтой.</p>
                <p>5.2. Админ хоёр талын баримт, чат, ажлын үр дүнг шалгаад эцсийн шийдвэрийг гаргана (Төлбөрийг буцаах эсвэл гүйцэтгэгчид олгох).</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-8 shadow-sm">
                <h3>6. Хориглох зүйлс & Хариуцлага</h3>
                <ul>
                    <li>Монгол Улсын хууль тогтоомжийг зөрчсөн агуулгатай файл/үйлчилгээ оруулах.</li>
                    <li>Вирус, хортой код агуулсан файл оруулах.</li>
                    <li>Насанд хүрэгчдэд зориулсан (+18) контент оруулах.</li>
                    <li>Системийн гадуур (шууд дансаар) гүйлгээ хийхийг санал болгох.</li>
                </ul>
                <p class="mt-4">6.1. Сайт нь хэрэглэгчийн оруулсан файлын агуулга, чанар, үнэн зөв байдалд шууд хариуцлага хүлээхгүй.</p>
                <p>6.2. Техникийн саатал, сүлжээний доголдлоос үүдэн гарах хохирлыг сайт хариуцахгүй боловч хэвийн ажиллагааг хангахын төлөө бүх боломжоороо ажиллана.</p>
            </div>

        </div>

        <!-- Footer -->
        <div class="mt-12 border-t border-gray-200 py-8 text-center bg-white rounded-t-2xl">
            <p class="text-sm text-gray-500 mb-2">&copy; <?php echo date('Y'); ?> Filezone.mn. Бүх эрх хуулиар хамгаалагдсан.</p>
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

        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="profile/dashboard.php" class="block w-full text-center py-2.5 border border-gray-200 rounded-xl font-semibold text-gray-600 hover:bg-gray-50">Миний булан</a>
            <a href="logout.php" class="block w-full text-center py-2.5 bg-slate-700 text-white rounded-xl font-bold hover:bg-slate-800 shadow-sm">Гарах</a>
        <?php else: ?>
            <div class="grid grid-cols-2 gap-3">
                <a href="login.php" class="block w-full text-center py-2.5 border border-gray-200 rounded-xl font-semibold text-gray-600 hover:bg-gray-50">Нэвтрэх</a>
                <a href="register.php" class="block w-full text-center py-2.5 bg-slate-700 text-white rounded-xl font-bold hover:bg-slate-800 shadow-sm">Бүртгүүлэх</a>
            </div>
        <?php endif; ?>
        
        <hr class="border-gray-100">

        <a href="index.php" class="flex items-center gap-3 text-gray-600 font-medium">
            <i class="fas fa-home w-5 text-center text-gray-400"></i> Нүүр хуудас
        </a>
        <a href="services.php" class="flex items-center gap-3 text-gray-600 font-medium">
            <i class="fas fa-briefcase w-5 text-center text-gray-400"></i> Үйлчилгээ
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