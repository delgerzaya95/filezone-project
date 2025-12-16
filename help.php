<?php 
// Хуудасны гарчиг тохируулах (header.php дотор ашиглагдана)
$page_title = "Тусламж - Filezone.mn";

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

             <!-- Help Menu Active -->
             <div class="space-y-1 mb-8">
                <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Тусламж</h3>
                <a href="guides.php" class="flex items-center gap-3 px-3 py-2 text-brand-600 bg-brand-50 rounded-lg font-medium">
                    <i class="fas fa-question-circle w-5 text-center"></i> Түгээмэл асуулт
                </a>
                <a href="contact.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium group">
                    <i class="fas fa-envelope w-5 text-center"></i> Холбоо барих
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
            
            <!-- NEW: Professional & Friendly Hero Section -->
            <div class="bg-gradient-to-br from-brand-700 via-brand-600 to-indigo-700 rounded-3xl p-8 md:p-14 text-white mb-16 relative overflow-hidden shadow-2xl">
                <!-- Decorative Background Shapes -->
                <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 bg-white opacity-10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 bg-purple-500 opacity-20 rounded-full blur-3xl"></div>
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-5"></div>

                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-10">
                    
                    <!-- Left: Text Content -->
                    <div class="text-center md:text-left md:w-1/2 space-y-6">
                        <!-- Badge -->
                        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-1.5 rounded-full text-brand-50 text-xs font-semibold border border-white/20 mb-2 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                            <span>Итгэлтэй, Найдвартай, Хурдан</span>
                        </div>
                        
                        <h1 class="text-3xl md:text-5xl font-bold leading-tight tracking-tight">
                            Мэдлэгээ хуваалц, <br/>
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-orange-300">Орлогоо нэмэгдүүл</span>
                        </h1>
                        
                        <p class="text-brand-100 text-base md:text-lg leading-relaxed opacity-90 max-w-lg mx-auto md:mx-0">
                            Filezone бол таны дижитал хөрөнгийг үнэ цэнэд хүргэх гүүр юм. 
                            Бие даалт, дипломоос эхлээд мэргэжлийн үйлчилгээ хүртэл бүгдийг нэг дороос.
                        </p>

                        <!-- Social Proof -->
                        <div class="flex flex-wrap justify-center md:justify-start gap-6 pt-2 items-center">
                            <div class="flex -space-x-3">
                                <img class="w-10 h-10 rounded-full border-2 border-brand-600 bg-white" src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" alt="User">
                                <img class="w-10 h-10 rounded-full border-2 border-brand-600 bg-white" src="https://api.dicebear.com/7.x/avataaars/svg?seed=Aneka" alt="User">
                                <img class="w-10 h-10 rounded-full border-2 border-brand-600 bg-white" src="https://api.dicebear.com/7.x/avataaars/svg?seed=John" alt="User">
                                <div class="w-10 h-10 rounded-full border-2 border-brand-600 bg-white text-brand-600 flex items-center justify-center text-xs font-bold">+2k</div>
                            </div>
                            <div class="flex flex-col text-left">
                                <div class="flex text-yellow-400 text-sm">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </div>
                                <span class="text-xs font-medium text-brand-200">5,000+ хэрэглэгчдийн сонголт</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Visual/Image Content (Glassmorphism) -->
                    <div class="md:w-1/2 relative flex justify-center md:justify-end">
                        <div class="relative z-10 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 shadow-2xl transform rotate-2 hover:rotate-0 transition duration-500 w-full max-w-sm animate-[float_6s_ease-in-out_infinite]">
                            <!-- Mockup Card Header -->
                            <div class="flex items-center justify-between mb-6 border-b border-white/10 pb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center text-xl shadow-lg text-white">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-lg text-white">Миний Орлого</h3>
                                        <p class="text-xs text-brand-200">2025 оны 10 сар</p>
                                    </div>
                                </div>
                                <span class="text-green-300 bg-green-500/20 px-2 py-1 rounded text-xs font-bold border border-green-500/30">+12% Өсөлт</span>
                            </div>
                            
                            <!-- Mockup Body -->
                            <div class="space-y-4">
                                <div class="flex justify-between items-center bg-black/20 p-3 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded bg-blue-500/20 text-blue-300 flex items-center justify-center"><i class="fas fa-file-word"></i></div>
                                        <div>
                                            <div class="h-2 w-24 bg-white/80 rounded mb-1"></div>
                                            <div class="h-1.5 w-16 bg-white/40 rounded"></div>
                                        </div>
                                    </div>
                                    <span class="text-white font-bold text-sm">+5,000₮</span>
                                </div>
                                <div class="flex justify-between items-center bg-black/20 p-3 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded bg-purple-500/20 text-purple-300 flex items-center justify-center"><i class="fas fa-pen-nib"></i></div>
                                        <div>
                                            <div class="h-2 w-20 bg-white/80 rounded mb-1"></div>
                                            <div class="h-1.5 w-12 bg-white/40 rounded"></div>
                                        </div>
                                    </div>
                                    <span class="text-white font-bold text-sm">+30,000₮</span>
                                </div>
                            </div>

                            <!-- Mockup Footer -->
                            <div class="mt-6 pt-4 border-t border-white/10 flex justify-between items-center">
                                <span class="text-sm text-brand-200">Нийт үлдэгдэл:</span>
                                <span class="text-2xl font-bold text-white tracking-wide">150,000₮</span>
                            </div>
                        </div>
                        
                        <!-- Floating Elements behind -->
                        <div class="absolute top-0 right-10 w-24 h-24 bg-blue-500 rounded-full opacity-30 blur-xl"></div>
                        <div class="absolute bottom-10 left-10 w-20 h-20 bg-pink-500 rounded-full opacity-30 blur-xl"></div>
                    </div>
                </div>
            </div>

            <!-- 3 MAIN PILLARS (3 Үндсэн чиглэл) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
                <!-- 1. Sell Files -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 hover:border-brand-400 hover:shadow-lg transition group relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition">
                        <i class="fas fa-file-invoice-dollar text-8xl text-brand-600"></i>
                    </div>
                    <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-6 text-2xl group-hover:scale-110 transition duration-300">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Файл зарж мөнгө олох</h3>
                    <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                        Өөрт байгаа бие даалт, диплом, тайлан зэрэг файлаа оруулж, татан авалт бүрээс орлого олох боломж.
                    </p>
                    <a href="#earn-files" class="text-brand-600 font-bold text-sm hover:underline flex items-center gap-2">
                        Дэлгэрэнгүй үзэх <i class="fas fa-arrow-down"></i>
                    </a>
                </div>

                <!-- 2. Offer Services -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 hover:border-purple-400 hover:shadow-lg transition group relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition">
                        <i class="fas fa-briefcase text-8xl text-purple-600"></i>
                    </div>
                    <div class="w-14 h-14 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center mb-6 text-2xl group-hover:scale-110 transition duration-300">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Үйлчилгээ үзүүлэх</h3>
                    <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                        График дизайн, орчуулга, шивэх зэрэг өөрийн чадвараа ашиглан бусдад үйлчилгээ үзүүлж мөнгө олох.
                    </p>
                    <a href="#earn-services" class="text-purple-600 font-bold text-sm hover:underline flex items-center gap-2">
                        Дэлгэрэнгүй үзэх <i class="fas fa-arrow-down"></i>
                    </a>
                </div>

                <!-- 3. Get Services -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 hover:border-green-400 hover:shadow-lg transition group relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition">
                        <i class="fas fa-search-dollar text-8xl text-green-600"></i>
                    </div>
                    <div class="w-14 h-14 bg-green-50 text-green-600 rounded-xl flex items-center justify-center mb-6 text-2xl group-hover:scale-110 transition duration-300">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Үйлчилгээ авах</h3>
                    <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                        Мэргэжлийн хүмүүсээр лого хийлгэх, диплом бичүүлэх, орчуулга хийлгэх зэрэг ажлаа гүйцэтгүүлэх.
                    </p>
                    <a href="#buy-services" class="text-green-600 font-bold text-sm hover:underline flex items-center gap-2">
                        Дэлгэрэнгүй үзэх <i class="fas fa-arrow-down"></i>
                    </a>
                </div>
            </div>

            <!-- DETAILED GUIDE 1: EARN WITH FILES -->
            <div id="earn-files" class="mb-16 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="h-10 w-1 bg-brand-600 rounded-full"></div>
                    <h2 class="text-xl font-bold text-gray-900">Хэрхэн файл зарж мөнгө олох вэ?</h2>
                </div>
                
                <div class="bg-white rounded-2xl border border-gray-200 p-6 md:p-8 relative overflow-hidden">
                    <!-- Background Decoration -->
                    <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-brand-50 rounded-full opacity-50 pointer-events-none"></div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-8 relative">
                        <div class="hidden md:block absolute top-8 left-0 w-full h-0.5 bg-gray-100 -z-0"></div>

                        <!-- Step 1 -->
                        <div class="relative z-10 text-center">
                            <div class="w-16 h-16 mx-auto bg-white border-2 border-brand-100 text-brand-600 rounded-full flex items-center justify-center text-xl font-bold mb-4 shadow-sm">1</div>
                            <h4 class="font-bold text-gray-800 mb-2">Бүртгүүлэх</h4>
                            <p class="text-xs text-gray-500">Сайтад үнэгүй бүртгүүлж, нэвтэрч орно.</p>
                        </div>
                        <!-- Step 2 -->
                        <div class="relative z-10 text-center">
                            <div class="w-16 h-16 mx-auto bg-white border-2 border-brand-100 text-brand-600 rounded-full flex items-center justify-center text-xl font-bold mb-4 shadow-sm">2</div>
                            <h4 class="font-bold text-gray-800 mb-2">Файл оруулах</h4>
                            <p class="text-xs text-gray-500">"Файл оруулах" товч дээр дарж файлаа хуулна.</p>
                        </div>
                        <!-- Step 3 -->
                        <div class="relative z-10 text-center">
                            <div class="w-16 h-16 mx-auto bg-white border-2 border-brand-100 text-brand-600 rounded-full flex items-center justify-center text-xl font-bold mb-4 shadow-sm">3</div>
                            <h4 class="font-bold text-gray-800 mb-2">Үнэ тогтоох</h4>
                            <p class="text-xs text-gray-500">Та өөрийн файлын үнийг өөрөө шийднэ.</p>
                        </div>
                        <!-- Step 4 -->
                        <div class="relative z-10 text-center">
                            <div class="w-16 h-16 mx-auto bg-green-500 text-white rounded-full flex items-center justify-center text-xl font-bold mb-4 shadow-lg shadow-green-500/30">
                                <i class="fas fa-check"></i>
                            </div>
                            <h4 class="font-bold text-gray-800 mb-2">Орлого олох</h4>
                            <p class="text-xs text-gray-500">Худалдан авалт бүрээс таны дансанд мөнгө орно.</p>
                        </div>
                    </div>
                    <div class="mt-8 text-center">
                        <a href="upload.php" class="inline-block bg-brand-600 text-white px-6 py-2.5 rounded-lg font-bold text-sm hover:bg-brand-700 transition">Файл оруулж эхлэх</a>
                    </div>
                </div>
            </div>

            <!-- DETAILED GUIDE 2: EARN WITH SERVICES (NEW) -->
            <div id="earn-services" class="mb-16 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="h-10 w-1 bg-purple-600 rounded-full"></div>
                    <h2 class="text-xl font-bold text-gray-900">Хэрхэн үйлчилгээ үзүүлж мөнгө олох вэ?</h2>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 p-6 md:p-8 relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-purple-50 rounded-full opacity-50 pointer-events-none"></div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Step Card 1 -->
                        <div class="bg-gray-50 p-5 rounded-xl border border-gray-100 text-center hover:bg-white hover:shadow-md transition">
                            <div class="w-10 h-10 mx-auto bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center mb-3 font-bold">1</div>
                            <h4 class="font-bold text-gray-800 mb-1">Зар оруулах</h4>
                            <p class="text-xs text-gray-500">"Лого хийнэ", "Орчуулга хийнэ" гэх мэт зарыг үнэ болон хугацаатай нь оруулна.</p>
                        </div>
                        <!-- Step Card 2 -->
                        <div class="bg-gray-50 p-5 rounded-xl border border-gray-100 text-center hover:bg-white hover:shadow-md transition">
                            <div class="w-10 h-10 mx-auto bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center mb-3 font-bold">2</div>
                            <h4 class="font-bold text-gray-800 mb-1">Захиалга авах</h4>
                            <p class="text-xs text-gray-500">Хэрэглэгч таны үйлчилгээг сонирхож, төлбөрөө байршуулах үед танд мэдэгдэл ирнэ.</p>
                        </div>
                        <!-- Step Card 3 -->
                        <div class="bg-gray-50 p-5 rounded-xl border border-gray-100 text-center hover:bg-white hover:shadow-md transition">
                            <div class="w-10 h-10 mx-auto bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center mb-3 font-bold">3</div>
                            <h4 class="font-bold text-gray-800 mb-1">Ажил гүйцэтгэх</h4>
                            <p class="text-xs text-gray-500">Захиалагчтай холбогдож, ажлыг чанартай гүйцэтгээд хүлээлгэж өгнө.</p>
                        </div>
                        <!-- Step Card 4 -->
                        <div class="bg-gray-50 p-5 rounded-xl border border-gray-100 text-center hover:bg-white hover:shadow-md transition">
                            <div class="w-10 h-10 mx-auto bg-green-100 text-green-600 rounded-lg flex items-center justify-center mb-3 font-bold"><i class="fas fa-check"></i></div>
                            <h4 class="font-bold text-gray-800 mb-1">Мөнгөө авах</h4>
                            <p class="text-xs text-gray-500">Захиалагч ажлыг баталгаажуулсны дараа мөнгө таны дансанд орно.</p>
                        </div>
                    </div>
                    <div class="mt-8 text-center">
                        <a href="add_service.php" class="inline-block bg-purple-600 text-white px-6 py-2.5 rounded-lg font-bold text-sm hover:bg-purple-700 transition">Үйлчилгээ нэмэх</a>
                    </div>
                </div>
            </div>

            <!-- DETAILED GUIDE 3: BUYING SERVICES -->
            <div id="buy-services" class="mb-16 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="h-10 w-1 bg-green-600 rounded-full"></div>
                    <h2 class="text-xl font-bold text-gray-900">Хэрхэн үйлчилгээ авах вэ?</h2>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 p-6 md:p-8">
                    <ul class="space-y-4">
                        <li class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center font-bold text-sm">1</div>
                            <div>
                                <h4 class="font-bold text-gray-800 text-sm">Хайлт хийх</h4>
                                <p class="text-xs text-gray-500 mt-1">Танд хэрэгтэй үйлчилгээг (Жишээ нь: "CV янзлах") хайж олно.</p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center font-bold text-sm">2</div>
                            <div>
                                <h4 class="font-bold text-gray-800 text-sm">Захиалга өгөх</h4>
                                <p class="text-xs text-gray-500 mt-1">Гүйцэтгэгчийн үнэлгээг харж байгаад сонгож, төлбөрөө байршуулна (Таны мөнгө ажил дуустал сайтад найдвартай хадгалагдана).</p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center font-bold text-sm">3</div>
                            <div>
                                <h4 class="font-bold text-gray-800 text-sm">Хүлээж авах</h4>
                                <p class="text-xs text-gray-500 mt-1">Гүйцэтгэгч ажлаа хүлээлгэн өгсний дараа та шалгаад "Баталгаажуулах" товч дарна.</p>
                            </div>
                        </li>
                    </ul>
                    <div class="mt-6">
                        <a href="services.php" class="text-green-600 font-bold text-sm hover:underline">Үйлчилгээнүүдийг үзэх &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- FAQ Accordion (Updated) -->
            <div class="max-w-3xl mx-auto">
                <h2 class="text-xl font-bold text-gray-900 mb-6 text-center">Түгээмэл асуултууд</h2>
                
                <div class="space-y-4">
                    <!-- FAQ 1 -->
                    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                        <button class="w-full flex items-center justify-between p-4 text-left font-medium text-gray-800 hover:bg-gray-50 transition" onclick="toggleFaq('faq1')">
                            <span>Би үйлчилгээний мөнгөө яаж авах вэ?</span>
                            <i id="icon-faq1" class="fas fa-chevron-down text-gray-400 text-sm transition-transform"></i>
                        </button>
                        <div id="faq1" class="hidden p-4 pt-0 text-sm text-gray-600 bg-gray-50/50 border-t border-gray-100">
                            Таны гүйцэтгэсэн ажлыг захиалагч баталгаажуулсны дараа мөнгө таны "Хэтэвч" рүү шууд орно. Тэндээсээ та хүссэн банкны данс руугаа татаж авах боломжтой.
                        </div>
                    </div>
                    <!-- FAQ 2 -->
                    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                        <button class="w-full flex items-center justify-between p-4 text-left font-medium text-gray-800 hover:bg-gray-50 transition" onclick="toggleFaq('faq2')">
                            <span>Файлын үнийг хэдээр тавих ёстой вэ?</span>
                            <i id="icon-faq2" class="fas fa-chevron-down text-gray-400 text-sm transition-transform"></i>
                        </button>
                        <div id="faq2" class="hidden p-4 pt-0 text-sm text-gray-600 bg-gray-50/50 border-t border-gray-100">
                            Үнийг та өөрөө тогтооно. Гэхдээ бид оюутны бие даалтыг 1,000₮-5,000₮, Дипломын ажлыг 20,000₮-с дээш үнэлэхийг зөвлөдөг.
                        </div>
                    </div>
                </div>
            </div>

<?php include 'includes/footer.php'; ?>