<?php
// --------------------------------------------------------------------------
// MOCK DATA (Файлуудын жагсаалт)
// --------------------------------------------------------------------------

$files = [
    ['id' => 101, 'name' => 'English_Grammar_Full_Course.pdf', 'type' => 'pdf', 'user' => 'Bat-Erdene', 'size' => '2.5 MB', 'date' => '2025-10-24 10:30', 'downloads' => 120, 'status' => 'active', 'category' => 'Боловсрол'],
    ['id' => 102, 'name' => 'Marketing_Plan_2025.docx', 'type' => 'word', 'user' => 'Sarnai', 'size' => '1.2 MB', 'date' => '2025-10-24 09:15', 'downloads' => 45, 'status' => 'active', 'category' => 'Бизнес'],
    ['id' => 103, 'name' => 'Summer_Vacation_Photos.zip', 'type' => 'zip', 'user' => 'Boldoo', 'size' => '150 MB', 'date' => '2025-10-23 16:45', 'downloads' => 12, 'status' => 'deleted', 'category' => 'Хувийн'],
    ['id' => 104, 'name' => 'Software_Setup_v2.exe', 'type' => 'exe', 'user' => 'Admin', 'size' => '45 MB', 'date' => '2025-10-23 14:20', 'downloads' => 890, 'status' => 'active', 'category' => 'Програм'],
    ['id' => 105, 'name' => 'Financial_Report_Q3.xlsx', 'type' => 'excel', 'user' => 'Tuya', 'size' => '800 KB', 'date' => '2025-10-23 11:00', 'downloads' => 5, 'status' => 'pending', 'category' => 'Санхүү'],
    ['id' => 106, 'name' => 'Logo_Design_Pack.ai', 'type' => 'image', 'user' => 'DesignPro', 'size' => '15 MB', 'date' => '2025-10-22 18:30', 'downloads' => 34, 'status' => 'active', 'category' => 'Дизайн'],
    ['id' => 107, 'name' => 'Lecture_Notes_History.pdf', 'type' => 'pdf', 'user' => 'Student01', 'size' => '5 MB', 'date' => '2025-10-22 09:00', 'downloads' => 200, 'status' => 'active', 'category' => 'Боловсрол'],
    ['id' => 108, 'name' => 'Music_Album_Mix.mp3', 'type' => 'audio', 'user' => 'DJ_Alex', 'size' => '89 MB', 'date' => '2025-10-21 22:15', 'downloads' => 1500, 'status' => 'active', 'category' => 'Хөгжим'],
];

// Icon Helper Function
function getFileIcon($type) {
    switch ($type) {
        case 'pdf': return 'fa-file-pdf text-red-500';
        case 'word': return 'fa-file-word text-blue-500';
        case 'excel': return 'fa-file-excel text-green-500';
        case 'zip': return 'fa-file-archive text-yellow-500';
        case 'image': return 'fa-file-image text-purple-500';
        case 'audio': return 'fa-file-audio text-pink-500';
        case 'video': return 'fa-file-video text-red-600';
        case 'exe': return 'fa-cogs text-gray-500';
        default: return 'fa-file text-gray-400';
    }
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Files Management - Filezone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
</head>
<body class="font-sans text-slate-800 antialiased">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR (Same as Dashboard) -->
        <?php include 'sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- HEADER -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shadow-sm z-10">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden text-slate-500"><i class="fas fa-bars text-xl"></i></button>
                    <h1 class="text-xl font-bold text-slate-800">Файлын удирдлага</h1>
                </div>
                <div class="flex items-center gap-3">
                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                        <i class="fas fa-cloud-upload-alt"></i> <span class="hidden sm:inline">Файл нэмэх</span>
                    </button>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6">
                
                <!-- Filters Bar -->
                <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex flex-col md:flex-row gap-4 flex-1">
                        <!-- Search -->
                        <div class="relative flex-1 max-w-md">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="text" placeholder="Файлын нэр, ID эсвэл хэрэглэгчээр хайх..." class="pl-10 pr-4 py-2 w-full border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        
                        <!-- Status Filter -->
                        <select class="border border-slate-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-slate-600">
                            <option value="">Бүх төлөв</option>
                            <option value="active">Идэвхтэй</option>
                            <option value="pending">Хүлээгдэж буй</option>
                            <option value="deleted">Устгагдсан</option>
                        </select>

                         <!-- Category Filter -->
                         <select class="border border-slate-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-slate-600">
                            <option value="">Бүх ангилал</option>
                            <option value="edu">Боловсрол</option>
                            <option value="soft">Програм</option>
                            <option value="ent">Энтертайнмент</option>
                        </select>
                    </div>

                    <!-- Export/Bulk Actions -->
                    <div class="flex items-center gap-2">
                         <button class="p-2 text-slate-500 hover:text-slate-700 border border-slate-300 rounded-lg bg-white" title="Шинэчлэх">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button class="p-2 text-slate-500 hover:text-slate-700 border border-slate-300 rounded-lg bg-white" title="Татаж авах">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </div>

                <!-- Files Table -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                    <th class="px-6 py-4 w-10">
                                        <input type="checkbox" class="form-checkbox text-indigo-600 rounded">
                                    </th>
                                    <th class="px-6 py-4 font-semibold">Файлын мэдээлэл</th>
                                    <th class="px-6 py-4 font-semibold">Ангилал</th>
                                    <th class="px-6 py-4 font-semibold">Эзэмшигч</th>
                                    <th class="px-6 py-4 font-semibold">Статистик</th>
                                    <th class="px-6 py-4 font-semibold">Төлөв</th>
                                    <th class="px-6 py-4 font-semibold text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($files as $file): ?>
                                <tr id="row-<?php echo $file['id']; ?>" class="hover:bg-slate-50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" class="form-checkbox text-indigo-600 rounded">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-lg bg-slate-50 flex items-center justify-center text-lg shadow-sm border border-slate-100">
                                                <i class="fas <?php echo getFileIcon($file['type']); ?>"></i>
                                            </div>
                                            <div>
                                                <p class="file-name text-sm font-semibold text-slate-800 hover:text-indigo-600 cursor-pointer truncate max-w-xs" title="<?php echo $file['name']; ?>">
                                                    <?php echo $file['name']; ?>
                                                </p>
                                                <p class="text-xs text-slate-500 mt-0.5">
                                                    <span class="font-medium"><?php echo $file['size']; ?></span> • <?php echo $file['date']; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="file-category inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                            <?php echo $file['category']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">
                                                <?php echo substr($file['user'], 0, 1); ?>
                                            </div>
                                            <span class="text-sm text-slate-600"><?php echo $file['user']; ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-600">
                                            <i class="fas fa-download text-slate-400 mr-1 text-xs"></i> <?php echo number_format($file['downloads']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 file-status">
                                        <?php if($file['status'] == 'active'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Идэвхтэй
                                            </span>
                                        <?php elseif($file['status'] == 'pending'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Хүлээгдэж буй
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Устгагдсан
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button onclick="openEditModal(<?php echo $file['id']; ?>, '<?php echo htmlspecialchars($file['name'], ENT_QUOTES); ?>', '<?php echo $file['category']; ?>', '<?php echo $file['status']; ?>')" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded hover:bg-indigo-50 transition" title="Засах">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button class="p-1.5 text-slate-400 hover:text-green-600 rounded hover:bg-green-50 transition" title="Татах">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50 transition" title="Устгах">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-white px-6 py-4 border-t border-slate-200 flex items-center justify-between">
                        <span class="text-sm text-slate-500">Нийт 8430 файлаас 1-8 харагдаж байна</span>
                        <div class="flex items-center gap-1">
                            <button class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 disabled:opacity-50 text-slate-600" disabled>Өмнөх</button>
                            <button class="px-3 py-1 text-sm border border-indigo-500 bg-indigo-50 text-indigo-600 rounded font-medium">1</button>
                            <button class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 text-slate-600">2</button>
                            <button class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 text-slate-600">3</button>
                            <span class="text-slate-400 px-1">...</span>
                            <button class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 text-slate-600">120</button>
                            <button class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 text-slate-600">Дараах</button>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeEditModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Файл засах</h3>
                    <div class="mt-4 space-y-4">
                        <input type="hidden" id="editFileId">
                        <div>
                            <label for="editFileName" class="block text-sm font-medium text-gray-700">Файлын нэр</label>
                            <input type="text" id="editFileName" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="editFileCategory" class="block text-sm font-medium text-gray-700">Ангилал</label>
                            <select id="editFileCategory" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="Боловсрол">Боловсрол</option>
                                <option value="Бизнес">Бизнес</option>
                                <option value="Хувийн">Хувийн</option>
                                <option value="Програм">Програм</option>
                                <option value="Санхүү">Санхүү</option>
                                <option value="Дизайн">Дизайн</option>
                                <option value="Хөгжим">Хөгжим</option>
                                <option value="Энтертайнмент">Энтертайнмент</option>
                            </select>
                        </div>
                        <div>
                            <label for="editFileStatus" class="block text-sm font-medium text-gray-700">Төлөв</label>
                            <select id="editFileStatus" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="active">Идэвхтэй</option>
                                <option value="pending">Хүлээгдэж буй</option>
                                <option value="deleted">Устгагдсан</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="saveFileChanges()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">Хадгалах</button>
                    <button type="button" onclick="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Болих</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>