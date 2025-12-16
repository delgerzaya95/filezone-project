<?php 
// Хуудасны гарчиг тохируулах (header.php дотор ашиглагдана)
$page_title = "Нууцлалын бодлого - Filezone.mn";

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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Нууцлалын бодлого</h1>
            <p class="text-gray-500 text-sm">Сүүлд шинэчлэгдсэн: 2025 оны 10-р сарын 08</p>
        </div>

        <!-- Privacy Content -->
        <div class="prose prose-slate max-w-4xl prose-headings:font-bold prose-headings:text-gray-900 prose-p:text-gray-600 prose-li:text-gray-600">

            <div class="bg-white border border-gray-200 rounded-2xl p-8 mb-8 shadow-sm">
                <h3>1. Мэдээлэл цуглуулах</h3>
                <p>Бид таныг сайтад бүртгүүлэх, файл оруулах, худалдан авалт хийх үед дараах мэдээллийг цуглуулна:</p>
                <ul>
                    <li>Нэвтрэх нэр, цахим шуудангийн хаяг (Email), утасны дугаар.</li>
                    <li>Төлбөр тооцоотой холбоотой гүйлгээний түүх.</li>
                    <li>Сайтад хандалт хийсэн IP хаяг, төхөөрөмжийн мэдээлэл.</li>
                </ul>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-8 mb-8 shadow-sm">
                <h3>2. Мэдээллийн ашиглалт</h3>
                <p>Бид таны хувийн мэдээллийг дараах зорилгоор ашиглана:</p>
                <ul>
                    <li>Үйлчилгээ үзүүлэх, төлбөр тооцоог баталгаажуулах.</li>
                    <li>Шинэ үйлчилгээ, урамшуулал, өөрчлөлтийн талаар мэдээлэх.</li>
                    <li>Сайтын аюулгүй байдлыг хангах, залилангаас урьдчилан сэргийлэх.</li>
                    <li>Хэрэглэгчийн туршлагыг сайжруулах, статистик дүн шинжилгээ хийх.</li>
                </ul>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-8 mb-8 shadow-sm">
                <h3>3. Мэдээллийн хамгаалалт</h3>
                <p>3.1. Бид таны хувийн мэдээллийг гуравдагч этгээдэд зарахгүй, түрээслүүлэхгүй.</p>
                <p>3.2. Хууль хяналтын байгууллагын албан ёсны шаардлагаар мэдээллийг гаргаж өгөхөөс бусад тохиолдолд нууцлалыг чанд хадгална.</p>
                <p>3.3. Таны нууц үг шифрлэгдсэн хэлбэрээр хадгалагдах бөгөөд админ харах боломжгүй.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-8 shadow-sm">
                <h3>4. Cookie ашиглалт</h3>
                <p>Бид сайтын үйл ажиллагааг сайжруулах, хэрэглэгчийн тохиргоог хадгалах зорилгоор "Cookie" ашигладаг. Та хөтчийн тохиргооноос Cookie-г идэвхгүй болгох боломжтой боловч энэ нь сайтын зарим функц ажиллахгүй байхад хүргэж болзошгүй.</p>
            </div>

        </div>

        <?php include 'includes/footer.php' ?>