<!-- Kids Footer -->
<footer class="bg-white border-t border-gray-200 pt-12 pb-8 relative overflow-hidden">
    <!-- Decorative bottom wave -->
    <div class="absolute bottom-0 left-0 right-0 h-2 bg-gradient-to-r from-yellow-400 via-pink-400 to-[#33cbcc]"></div>

    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <!-- Brand -->
            <div class="col-span-1 md:col-span-1 text-center md:text-left">
                <a href="index.php" class="inline-block text-2xl font-extrabold font-title text-gray-700 mb-4">
                    Filezone <span class="text-[#33cbcc]">Kids</span>
                </a>
                <p class="text-sm text-gray-500 mb-4">
                    Хүүхдийн ирээдүйг гэрэлтүүлж, сурч боловсрох үйлсийг дэмжинэ.
                </p>
                <div class="flex justify-center md:justify-start gap-4">
                    <a href="#" class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 hover:bg-blue-200 transition"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="w-8 h-8 rounded-full bg-pink-100 flex items-center justify-center text-pink-600 hover:bg-pink-200 transition"><i class="fab fa-instagram"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-span-1 text-center md:text-left">
                <h4 class="font-bold text-gray-800 mb-4">Хэсгүүд</h4>
                <ul class="space-y-2 text-sm text-gray-500">
                    <li><a href="index.php" class="hover:text-[#33cbcc]">Нүүр хуудас</a></li>
                    <li><a href="browse.php?cat=math" class="hover:text-[#33cbcc]">Математик</a></li>
                    <li><a href="browse.php?cat=write" class="hover:text-[#33cbcc]">Бичиг үсэг</a></li>
                    <li><a href="browse.php?cat=logic" class="hover:text-[#33cbcc]">Логик сэтгэлгээ</a></li>
                </ul>
            </div>

            <!-- Parents Info -->
            <div class="col-span-1 text-center md:text-left">
                <h4 class="font-bold text-gray-800 mb-4">Эцэг эхчүүдэд</h4>
                <ul class="space-y-2 text-sm text-gray-500">
                    <li><a href="../contact.php" class="hover:text-[#33cbcc]">Холбоо барих</a></li>
                    <li><a href="../help.php" class="hover:text-[#33cbcc]">Тусламж</a></li>
                    <!-- Premium Terms Link -->
                    <li>
                        <a href="../terms.php#premium-kids" class="flex items-center justify-center md:justify-start gap-2 text-yellow-600 font-bold hover:text-yellow-700">
                            <i class="fas fa-crown text-xs"></i> Premium эрхийн нөхцөл
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Newsletter/Promo -->
            <div class="col-span-1 bg-[#e0f7fa] rounded-xl p-4 text-center">
                <h4 class="font-bold text-[#00838f] mb-2">Шинэ дасгалууд</h4>
                <p class="text-xs text-[#006064] mb-3">Долоо хоног бүр шинэ материал нэмэгддэг!</p>
                <a href="browse.php?sort=newest" class="inline-block w-full bg-[#33cbcc] hover:bg-[#2bb5b6] text-white text-sm font-bold py-2 rounded-lg shadow transition">
                    Шинийг үзэх
                </a>
            </div>
        </div>

        <div class="border-t border-gray-100 pt-6 text-center">
            <p class="text-xs text-gray-400">
                &copy; <?php echo date('Y'); ?> Filezone.mn. Бүх эрх хуулиар хамгаалагдсан. 
                <a href="../index.php" class="text-gray-400 hover:text-gray-600 underline ml-2">Үндсэн сайт</a>
            </p>
        </div>
    </div>
</footer>
</body>
</html>