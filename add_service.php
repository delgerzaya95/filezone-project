<?php 
// Хуудасны гарчиг тохируулах (header.php дотор ашиглагдана)
$page_title = "Үйлчилгээ нэмэх - Filezone.mn";

// Header оруулах
include 'includes/header.php'; 
?>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <!-- Sidebar Navigation -->
    <?php include 'includes/sidebar.php' ?>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Үйлчилгээ нэмэх</h1>
            <p class="text-sm text-gray-500">Та өөрийн чадвараа ашиглан бусдад үйлчилгээ үзүүлж мөнгө олоорой.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column: Service Form -->
            <div class="lg:col-span-2 space-y-6">
                
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                    <h3 class="font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Үйлчилгээний мэдээлэл</h3>
                    
                    <form class="space-y-6">
                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Үйлчилгээний гарчиг <span class="text-red-500">*</span></label>
                            <input type="text" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition" placeholder="Жишээ: Би танд мэргэжлийн түвшинд лого бүтээж өгнө">
                            <p class="text-xs text-gray-400 mt-1">Товч бөгөөд тодорхой байх хэрэгтэй. "Би ... хийж өгнө" гэсэн хэлбэрээр бичвэл зүгээр.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Category -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Ангилал <span class="text-red-500">*</span></label>
                                <select class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none bg-white">
                                    <option value="">Сонгоно уу...</option>
                                    <option value="design">Дизайн & График</option>
                                    <option value="web">Вэб хөгжүүлэлт</option>
                                    <option value="writing">Орчуулга & Бичих</option>
                                    <option value="video">Видео & Анимэйшн</option>
                                    <option value="business">Бизнес зөвлөгөө</option>
                                    <option value="other">Бусад</option>
                                </select>
                            </div>
                            <!-- Delivery Time -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Гүйцэтгэх хугацаа <span class="text-red-500">*</span></label>
                                <select class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none bg-white">
                                    <option value="1">1 өдөр</option>
                                    <option value="2">2 өдөр</option>
                                    <option value="3">3 өдөр</option>
                                    <option value="5">5 өдөр</option>
                                    <option value="7">7 өдөр</option>
                                    <option value="14">14 өдөр</option>
                                </select>
                            </div>
                        </div>

                        <!-- Price -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Үнэ (MNT) <span class="text-red-500">*</span></label>
                            <div class="relative max-w-sm">
                                <input type="number" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition" placeholder="30000">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 text-xs">₮</span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Хамгийн доод үнэ: 5,000₮</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Дэлгэрэнгүй тайлбар <span class="text-red-500">*</span></label>
                            <textarea rows="6" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition" placeholder="Та юу хийж чадах, ямар туршлагатай, захиалагч яагаад таныг сонгох ёстой талаар дэлгэрэнгүй бичнэ үү..."></textarea>
                        </div>

                        <!-- Requirements -->
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                            <label class="block text-sm font-bold text-blue-800 mb-1.5">Захиалагчаас шаардагдах зүйлс</label>
                            <p class="text-xs text-blue-600 mb-2">Ажлыг эхлүүлэхийн тулд танд захиалагчаас юу хэрэгтэй вэ? (Жишээ нь: Компанийн нэр, өнгөний сонголт, текст г.м)</p>
                            <textarea rows="3" class="w-full border border-blue-200 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Надад дараах мэдээллүүдийг илгээнэ үү..."></textarea>
                        </div>

                        <!-- Cover Image Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Нүүр зураг (Thumbnail) <span class="text-red-500">*</span></label>
                            <div class="flex flex-col items-start gap-4">
                                <div class="w-full h-48 bg-gray-100 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center text-gray-400 overflow-hidden relative cursor-pointer hover:bg-gray-50 transition" onclick="document.getElementById('cover-upload').click()">
                                    <div class="text-center" id="preview-placeholder">
                                        <i class="fas fa-image text-3xl mb-2"></i>
                                        <p class="text-xs">Зураг оруулах (1280x720)</p>
                                    </div>
                                    <img id="preview-img" class="absolute inset-0 w-full h-full object-cover hidden">
                                </div>
                                <input type="file" id="cover-upload" class="hidden" onchange="handleCoverSelect(this)">
                            </div>
                        </div>

                        <hr class="border-gray-100">

                        <!-- Terms -->
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="terms" type="checkbox" class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="terms" class="font-medium text-gray-700">Би зөвшөөрч байна</label>
                                <p class="text-gray-500 text-xs">Би энэ үйлчилгээг чанартай, цаг хугацаанд нь гүйцэтгэхээ баталж байна.</p>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-4 pt-2">
                            <button type="submit" class="flex-1 bg-purple-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-purple-500/30 hover:bg-purple-700 hover:-translate-y-0.5 transition-all">
                                Үйлчилгээ нийтлэх
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
                <div class="bg-purple-50 border border-purple-100 rounded-2xl p-6 sticky top-24">
                    <h3 class="font-bold text-purple-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-star"></i> Амжилттай зарах зөвлөмж
                    </h3>
                    <ul class="space-y-4 text-sm text-purple-800">
                        <li class="flex gap-3">
                            <span class="w-6 h-6 bg-purple-200 text-purple-700 rounded-full flex items-center justify-center flex-shrink-0 font-bold text-xs">1</span>
                            <span><strong>Гарчиг:</strong> Товч бөгөөд тодорхой байх. Үйлчлүүлэгч юу авах вэ гэдгээ шууд ойлгох ёстой.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 bg-purple-200 text-purple-700 rounded-full flex items-center justify-center flex-shrink-0 font-bold text-xs">2</span>
                            <span><strong>Зураг:</strong> Чанартай, анхаарал татахуйц зураг ашиглах нь борлуулалтыг 30% нэмэгдүүлдэг.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 bg-purple-200 text-purple-700 rounded-full flex items-center justify-center flex-shrink-0 font-bold text-xs">3</span>
                            <span><strong>Үнэ:</strong> Зах зээлийн ханшийг судалж, өрсөлдөхүйц үнэ тавих.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 bg-purple-200 text-purple-700 rounded-full flex items-center justify-center flex-shrink-0 font-bold text-xs">4</span>
                            <span><strong>Хариуцлага:</strong> Та захиалга авсан бол заавал дуусгах үүрэгтэйг санаарай.</span>
                        </li>
                    </ul>
                </div>
            </div>

        </div>

<?php include 'includes/footer.php'; ?>