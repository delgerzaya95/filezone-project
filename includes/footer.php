<!-- Footer -->
            <div class="mt-12 border-t border-gray-200 py-8 text-center bg-white rounded-t-2xl">
                <p class="text-sm text-gray-500 mb-2">&copy; 2025 Filezone.mn. Бүх эрх хуулиар хамгаалагдсан.</p>
                <div class="flex justify-center gap-4 text-xs text-gray-400">
                    <a href="terms.php" class="hover:text-gray-600">Үйлчилгээний нөхцөл</a>
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
            <a href="browse-files.php" class="flex items-center gap-3 text-gray-600 font-medium">
                <i class="fas fa-folder-open w-5 text-center text-gray-400"></i> Файлууд
            </a>
            <a href="categories.php" class="flex items-center gap-3 text-brand-600 font-medium">
                <i class="fas fa-th-large w-5 text-center"></i> Ангилал
            </a>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    
</body>
</html>