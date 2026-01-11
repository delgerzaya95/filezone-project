<?php
// Filezone Kids - Premium Subscription Page
// Path: filezone.mn/kids/premium.php

require_once __DIR__ . '/../includes/db.php'; 
$page_title = "Premium авах - Filezone Kids";

// Kids header-ийг дуудна
include 'header.php';
?>

<div class="font-kids bg-gray-50 min-h-screen">
    
    <!-- Hero Section -->
    <div class="bg-[#33cbcc] pt-12 pb-24 relative overflow-hidden">
        <!-- Background shapes -->
        <div class="absolute top-10 left-10 text-white opacity-20 animate-spin-slow"><i class="fas fa-star fa-3x"></i></div>
        <div class="absolute bottom-10 right-10 text-white opacity-20"><i class="fas fa-crown fa-4x"></i></div>
        
        <div class="container mx-auto px-4 text-center relative z-10">
            <h1 class="text-4xl md:text-5xl font-extrabold text-white font-title mb-4">
                Filezone Kids <span class="text-yellow-300">Premium</span>
            </h1>
            <p class="text-lg md:text-xl text-teal-50 max-w-2xl mx-auto font-medium">
                Хүүхдийнхээ боловсролд хөрөнгө оруулалт хийж, хязгааргүй суралцах боломжийг нээгээрэй.
            </p>
        </div>
    </div>

    <!-- Pricing Cards Section -->
    <div class="container mx-auto px-4 -mt-16 pb-20 relative z-20">
        <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <!-- Free Plan -->
            <div class="bg-white rounded-3xl shadow-lg p-8 border-2 border-gray-100 flex flex-col relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-gray-50 rounded-bl-full -mr-10 -mt-10 z-0"></div>
                <h3 class="text-2xl font-bold text-gray-700 mb-2 relative z-10 font-title">Энгийн эрх</h3>
                <div class="text-4xl font-extrabold text-gray-800 mb-6">0₮ <span class="text-base font-normal text-gray-500">/ сард</span></div>
                
                <ul class="space-y-4 mb-8 flex-grow">
                    <li class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-500 mt-1"></i>
                        <span class="text-gray-600">Өдөрт 3 хүртэлх файл татах</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-500 mt-1"></i>
                        <span class="text-gray-600">Зөвхөн "Үнэгүй" тэмдэглэгээтэй материалууд</span>
                    </li>
                    <li class="flex items-start gap-3 opacity-50">
                        <i class="fas fa-times-circle text-gray-300 mt-1"></i>
                        <span class="text-gray-400">Шинэ материалуудад хандах эрхгүй</span>
                    </li>
                    <li class="flex items-start gap-3 opacity-50">
                        <i class="fas fa-times-circle text-gray-300 mt-1"></i>
                        <span class="text-gray-400">Зар сурталчилгаатай</span>
                    </li>
                </ul>
                
                <a href="browse.php" class="block w-full py-3 rounded-xl border-2 border-gray-200 text-gray-600 font-bold text-center hover:bg-gray-50 transition">
                    Үргэлжлүүлэх
                </a>
            </div>

            <!-- Premium Plan -->
            <div class="bg-white rounded-3xl shadow-xl p-8 border-4 border-yellow-400 flex flex-col relative transform md:-translate-y-4">
                <div class="absolute top-0 right-0 bg-yellow-400 text-white text-xs font-bold px-3 py-1 rounded-bl-xl uppercase tracking-wide">Санал болгож байна</div>
                <h3 class="text-2xl font-bold text-[#33cbcc] mb-2 font-title flex items-center gap-2">
                    <i class="fas fa-crown text-yellow-400"></i> Premium эрх
                </h3>
                <div class="text-4xl font-extrabold text-gray-800 mb-1">19,900₮ <span class="text-base font-normal text-gray-500">/ сард</span></div>
                <p class="text-xs text-gray-400 mb-6 font-bold uppercase tracking-wide">3 сараар авбал хямдралтай</p>
                
                <ul class="space-y-4 mb-8 flex-grow">
                    <li class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center text-green-500 flex-shrink-0"><i class="fas fa-check"></i></div>
                        <span class="text-gray-700 font-bold">Хязгааргүй файл татах эрх</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center text-green-500 flex-shrink-0"><i class="fas fa-check"></i></div>
                        <span class="text-gray-700 font-bold">Бүх "Premium" материалууд нээгдэнэ</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center text-green-500 flex-shrink-0"><i class="fas fa-check"></i></div>
                        <span class="text-gray-700">Шинэ материалуудыг түрүүлж үзэх</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center text-green-500 flex-shrink-0"><i class="fas fa-check"></i></div>
                        <span class="text-gray-700">Ямар ч зар сурталчилгаагүй</span>
                    </li>
                </ul>
                
                <a href="payment.php" class="block w-full py-4 rounded-xl bg-gradient-to-r from-yellow-400 to-orange-400 hover:from-yellow-500 hover:to-orange-500 text-white font-bold text-center shadow-lg transform transition hover:-translate-y-1">
                    Premium авах 🚀
                </a>
            </div>

        </div>
    </div>

    <!-- FAQ Section -->
    <div class="bg-white py-16">
        <div class="container mx-auto px-4 max-w-3xl">
            <h2 class="text-2xl md:text-3xl font-extrabold text-gray-800 text-center mb-10 font-title">Түгээмэл асуултууд</h2>
            
            <div class="space-y-4">
                <div class="border border-gray-200 rounded-xl p-6 hover:shadow-md transition">
                    <h3 class="font-bold text-lg text-gray-800 mb-2">Premium эрхээ яаж идэвхжүүлэх вэ?</h3>
                    <p class="text-gray-600">Төлбөр төлсний дараа таны эрх автоматаар идэвхжинэ. Хэрэв идэвхжихгүй бол бидэнтэй холбоо барина уу.</p>
                </div>
                <div class="border border-gray-200 rounded-xl p-6 hover:shadow-md transition">
                    <h3 class="font-bold text-lg text-gray-800 mb-2">Төлбөрийг ямар хэлбэрээр төлөх боломжтой вэ?</h3>
                    <p class="text-gray-600">Бид QPay болон бүх төрлийн банкны картаар төлбөр хүлээн авдаг.</p>
                </div>
                <div class="border border-gray-200 rounded-xl p-6 hover:shadow-md transition">
                    <h3 class="font-bold text-lg text-gray-800 mb-2">Мөнгө буцаан олгох боломжтой юу?</h3>
                    <p class="text-gray-600">Дижитал бүтээгдэхүүн тул мөнгө буцаан олгох боломжгүй. Та эхлээд үнэгүй материалуудыг татаж үзээрэй.</p>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>