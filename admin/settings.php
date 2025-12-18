<?php
// --------------------------------------------------------------------------
// MOCK DATA (Тохиргооны утгууд)
// --------------------------------------------------------------------------

$settings = [
    'site_name' => 'Filezone',
    'site_description' => 'Монголын хамгийн том файл хуваалцах платформ',
    'contact_email' => 'support@filezone.mn',
    'phone' => '+976 88112233',
    'facebook_url' => 'https://facebook.com/filezone',
    'instagram_url' => 'https://instagram.com/filezone',
    
    'qpay_merchant_id' => 'FILEZONE_MN',
    'qpay_env' => 'sandbox', // sandbox or production
    'commission_rate' => 10, // 10%
    'min_withdrawal' => 5000,
    
    'max_upload_size' => 100, // MB
    'allowed_extensions' => 'jpg,png,pdf,docx,zip,rar',
    'maintenance_mode' => false
];

?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Filezone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
</head>
<body class="font-sans text-slate-800 antialiased">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- HEADER -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shadow-sm z-10">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden text-slate-500"><i class="fas fa-bars text-xl"></i></button>
                    <h1 class="text-xl font-bold text-slate-800">Сайтын тохиргоо</h1>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="saveSettings()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                        <i class="fas fa-save"></i> <span class="hidden sm:inline">Хадгалах</span>
                    </button>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6">
                
                <div class="max-w-6xl mx-auto flex flex-col md:flex-row gap-6">
                    
                    <!-- Settings Navigation -->
                    <div class="w-full md:w-64 bg-white rounded-xl shadow-sm border border-slate-200 h-fit overflow-hidden flex-shrink-0">
                        <nav class="flex flex-col">
                            <button onclick="switchSettingTab('general')" id="st-general" class="settings-tab-btn active text-left px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50 transition border-l-3 border-transparent flex items-center gap-3">
                                <i class="fas fa-globe text-slate-400"></i> Ерөнхий тохиргоо
                            </button>
                            <button onclick="switchSettingTab('payment')" id="st-payment" class="settings-tab-btn text-left px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50 transition border-l-3 border-transparent flex items-center gap-3">
                                <i class="fas fa-credit-card text-slate-400"></i> Төлбөр & QPay
                            </button>
                            <button onclick="switchSettingTab('system')" id="st-system" class="settings-tab-btn text-left px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50 transition border-l-3 border-transparent flex items-center gap-3">
                                <i class="fas fa-server text-slate-400"></i> Систем & Файл
                            </button>
                            <button onclick="switchSettingTab('security')" id="st-security" class="settings-tab-btn text-left px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50 transition border-l-3 border-transparent flex items-center gap-3">
                                <i class="fas fa-shield-alt text-slate-400"></i> Аюулгүй байдал
                            </button>
                        </nav>
                    </div>

                    <!-- Settings Content Form -->
                    <div class="flex-1 bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        
                        <!-- 1. General Settings -->
                        <div id="content-general" class="settings-content space-y-6">
                            <h2 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4">Ерөнхий мэдээлэл</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Сайтын нэр</label>
                                    <input type="text" value="<?php echo $settings['site_name']; ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Холбоо барих утас</label>
                                    <input type="text" value="<?php echo $settings['phone']; ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Сайтын тайлбар (Meta Description)</label>
                                    <textarea rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"><?php echo $settings['site_description']; ?></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Имэйл хаяг</label>
                                    <input type="email" value="<?php echo $settings['contact_email']; ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>

                            <h3 class="text-md font-bold text-slate-800 mt-6 mb-3">Сошиал холбоос</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"><i class="fab fa-facebook"></i></span>
                                    <input type="text" value="<?php echo $settings['facebook_url']; ?>" class="pl-10 w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"><i class="fab fa-instagram"></i></span>
                                    <input type="text" value="<?php echo $settings['instagram_url']; ?>" class="pl-10 w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>

                        <!-- 2. Payment Settings -->
                        <div id="content-payment" class="settings-content space-y-6 hidden">
                            <h2 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4">Төлбөрийн тохиргоо (QPay)</h2>
                            
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4 flex items-start gap-3">
                                <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5"></i>
                                <div class="text-sm text-yellow-800">
                                    <span class="font-bold">Анхаар:</span> Эдгээр тохиргоог өөрчлөх нь гүйлгээнд шууд нөлөөлнө. QPay Merchant мэдээллээ зөв оруулна уу.
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">QPay Merchant ID</label>
                                    <input type="text" value="<?php echo $settings['qpay_merchant_id']; ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono bg-slate-50">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Орчин (Environment)</label>
                                    <select class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="sandbox" <?php echo $settings['qpay_env'] == 'sandbox' ? 'selected' : ''; ?>>Sandbox (Туршилт)</option>
                                        <option value="production" <?php echo $settings['qpay_env'] == 'production' ? 'selected' : ''; ?>>Production (Бодит)</option>
                                    </select>
                                </div>
                            </div>

                            <h3 class="text-md font-bold text-slate-800 mt-6 mb-3">Шимтгэл & Хязгаар</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Сайтын шимтгэл (%)</label>
                                    <div class="relative">
                                        <input type="number" value="<?php echo $settings['commission_rate']; ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 pr-8">
                                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-500">%</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">Хэрэглэгч үйлчилгээ үзүүлэхэд авах шимтгэл.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Хамгийн бага татах дүн (₮)</label>
                                    <input type="number" value="<?php echo $settings['min_withdrawal']; ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>

                        <!-- 3. System Settings -->
                        <div id="content-system" class="settings-content space-y-6 hidden">
                            <h2 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4">Системийн тохиргоо</h2>
                            
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Файл хуулах дээд хэмжээ (Max Upload Size)</label>
                                    <div class="relative max-w-xs">
                                        <input type="number" value="<?php echo $settings['max_upload_size']; ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 pr-10">
                                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-500 font-bold">MB</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Зөвшөөрөгдсөн өргөтгөлүүд</label>
                                    <input type="text" value="<?php echo $settings['allowed_extensions']; ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <p class="text-xs text-slate-500 mt-1">Таслалаар тусгаарлаж бичнэ үү (Ex: jpg,png,pdf)</p>
                                </div>
                                
                                <div class="flex items-center gap-3 mt-2 p-4 border border-slate-200 rounded-lg bg-slate-50">
                                    <input type="checkbox" id="maintenance" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                    <div>
                                        <label for="maintenance" class="block text-sm font-medium text-slate-800">Засварын горим (Maintenance Mode)</label>
                                        <p class="text-xs text-slate-500">Идэвхжүүлсэн үед админаас бусад хэрэглэгчид сайт руу хандах боломжгүй болно.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Security Settings -->
                        <div id="content-security" class="settings-content space-y-6 hidden">
                            <h2 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4">Аюулгүй байдал</h2>
                            
                            <form onsubmit="event.preventDefault(); alert('Нууц үг солигдлоо! (Demo)');">
                                <h3 class="text-md font-bold text-slate-800 mb-3">Админ нууц үг солих</h3>
                                <div class="space-y-4 max-w-md">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Хуучин нууц үг</label>
                                        <input type="password" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Шинэ нууц үг</label>
                                        <input type="password" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Шинэ нууц үг давтах</label>
                                        <input type="password" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <button type="submit" class="bg-indigo-50 text-indigo-700 px-4 py-2 rounded-lg text-sm font-medium border border-indigo-200 hover:bg-indigo-100 transition">
                                        Нууц үг солих
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>