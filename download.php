<?php 
// Хуудасны гарчиг тохируулах (header.php дотор ашиглагдана)
$page_title = "Файл татах - Filezone.mn";

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
                <a href="browse-files.php" class="flex items-center gap-3 px-3 py-2 text-brand-600 bg-brand-50 rounded-lg font-medium">
                    <i class="fas fa-folder-open w-5 text-center"></i> Файлууд
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                    <i class="fas fa-fire w-5 text-center text-orange-500"></i> Эрэлттэй
                </a>
            </div>

             <!-- Services Menu -->
             <div class="space-y-1 mb-8">
                <div class="flex items-center justify-between px-3 mb-2">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Freelance / Үйлчилгээ</h3>
                </div>
                <a href="services.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium group">
                    <div class="w-5 h-5 rounded bg-purple-100 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition">
                        <i class="fas fa-pen-nib text-xs"></i>
                    </div>
                    Дизайн & График
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 py-10 px-4 lg:px-0 min-w-0 flex items-center justify-center">
            
            <div class="max-w-2xl w-full">
                <!-- Success Card -->
                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 md:p-12 text-center relative overflow-hidden">
                    
                    <!-- Decorative Background -->
                    <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-green-400 to-brand-500"></div>
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-green-50 rounded-full blur-3xl opacity-50"></div>
                    <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-brand-50 rounded-full blur-3xl opacity-50"></div>

                    <!-- Success Icon -->
                    <div class="relative z-10 w-24 h-24 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 text-5xl shadow-sm animate-bounce-slow">
                        <i class="fas fa-check"></i>
                    </div>
                    
                    <h1 class="relative z-10 text-3xl font-bold text-gray-900 mb-2">Төлбөр амжилттай төлөгдлөө!</h1>
                    <p class="relative z-10 text-gray-500 mb-8">Худалдан авалт хийсэнд баярлалаа. Таны файл татахад бэлэн боллоо.</p>

                    <!-- File Info Box -->
                    <div class="relative z-10 bg-gray-50 border border-gray-200 rounded-2xl p-5 mb-8 flex items-center gap-5 text-left hover:border-brand-300 transition-colors">
                        <div class="w-14 h-14 bg-white border border-gray-200 rounded-xl flex items-center justify-center text-3xl text-green-600 shadow-sm flex-shrink-0">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <div class="flex-1 overflow-hidden">
                            <h3 class="font-bold text-gray-900 text-base mb-1 truncate">Санхүүгийн тайлангийн иж бүрэн загвар 2025.xlsx</h3>
                            <div class="flex items-center gap-3 text-xs text-gray-500 uppercase font-medium">
                                <span class="bg-white px-2 py-0.5 rounded border border-gray-200">2.5 MB</span>
                                <span class="bg-white px-2 py-0.5 rounded border border-gray-200">XLSX</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="relative z-10 space-y-4 max-w-sm mx-auto">
                        <button onclick="window.print()" class="w-full bg-brand-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-brand-500/30 hover:bg-brand-700 hover:-translate-y-1 transition-all btn-shine flex items-center justify-center gap-3 text-lg">
                            <i class="fas fa-download animate-bounce"></i> Файл татах
                        </button>
                        
                        <div class="bg-yellow-50 text-yellow-800 text-xs px-4 py-3 rounded-lg border border-yellow-100 flex items-start gap-2 text-left">
                            <i class="fas fa-exclamation-circle mt-0.5 text-yellow-600"></i>
                            <div>
                                <span class="font-bold">Анхаар:</span> Татах холбоос 24 цагийн дараа идэвхгүй болно. Та файлаа компьютертээ хадгалж аваарай.
                            </div>
                        </div>
                    </div>
                    
                    <div class="relative z-10 mt-10 pt-6 border-t border-gray-100 flex justify-center gap-6 text-sm">
                        <a href="index.php" class="text-gray-500 hover:text-brand-600 font-medium transition-colors">
                            &larr; Нүүр хуудас
                        </a>
                        <span class="text-gray-300">|</span>
                        <a href="browse-files.php" class="text-gray-500 hover:text-brand-600 font-medium transition-colors">
                            Өөр файл хайх
                        </a>
                    </div>
                </div>
                
                <!-- Receipt / Transaction ID (Optional) -->
                <div class="text-center mt-6 text-xs text-gray-400">
                    Гүйлгээний дугаар: <span class="font-mono">TRX-987654321</span>
                </div>
            </div>

        </main>
    </div>

<?php include 'includes/footer.php'; ?>