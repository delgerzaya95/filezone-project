<?php
// --------------------------------------------------------------------------
// MOCK DATA (Ангилалын бүтэц)
// --------------------------------------------------------------------------

// Үндсэн ангилалууд (Parent Categories)
// Дэд ангилалууд (Sub Categories) - parent_id нь үндсэн ангилалын ID байна.
$categories = [
    [
        'id' => 1, 
        'name' => 'Боловсрол', 
        'slug' => 'education',
        'icon' => 'fa-graduation-cap',
        'file_count' => 1250, 
        'status' => 'active', 
        'parent_id' => null, // Main Category
        'subcategories' => [
            ['id' => 11, 'name' => 'ЕБС-ийн сурах бичиг', 'slug' => 'ebs-books', 'file_count' => 450, 'status' => 'active', 'parent_id' => 1],
            ['id' => 12, 'name' => 'Оюутны лекц', 'slug' => 'university-lectures', 'file_count' => 800, 'status' => 'active', 'parent_id' => 1]
        ]
    ],
    [
        'id' => 2, 
        'name' => 'Програм хангамж', 
        'slug' => 'software',
        'icon' => 'fa-laptop-code',
        'file_count' => 850, 
        'status' => 'active', 
        'parent_id' => null,
        'subcategories' => [
            ['id' => 21, 'name' => 'Үйлдлийн систем', 'slug' => 'os', 'file_count' => 120, 'status' => 'active', 'parent_id' => 2],
            ['id' => 22, 'name' => 'Хэрэглээний програм', 'slug' => 'apps', 'file_count' => 730, 'status' => 'active', 'parent_id' => 2]
        ]
    ],
    [
        'id' => 3, 
        'name' => 'Мультимедиа', 
        'slug' => 'multimedia',
        'icon' => 'fa-photo-video',
        'file_count' => 3400, 
        'status' => 'active', 
        'parent_id' => null,
        'subcategories' => [
            ['id' => 31, 'name' => 'Зураг & Дизайн', 'slug' => 'graphics', 'file_count' => 2100, 'status' => 'active', 'parent_id' => 3],
            ['id' => 32, 'name' => 'Видео & Аудио', 'slug' => 'video-audio', 'file_count' => 1300, 'status' => 'active', 'parent_id' => 3]
        ]
    ],
    [
        'id' => 4, 
        'name' => 'Архив', 
        'slug' => 'archive',
        'icon' => 'fa-archive',
        'file_count' => 50, 
        'status' => 'inactive', 
        'parent_id' => null,
        'subcategories' => [] // No subcategories
    ]
];

?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - Filezone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
</head>
<body class="font-sans text-slate-800 antialiased">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR (Same as previous files) -->
        <?php include 'sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- HEADER -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shadow-sm z-10">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden text-slate-500"><i class="fas fa-bars text-xl"></i></button>
                    <h1 class="text-xl font-bold text-slate-800">Ангилалын удирдлага</h1>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="openModal('add')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                        <i class="fas fa-plus"></i> <span class="hidden sm:inline">Ангилал нэмэх</span>
                    </button>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6">
                
                <!-- Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Нийт ангилал</p>
                            <h3 class="text-2xl font-bold text-slate-800">10</h3>
                        </div>
                        <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-layer-group"></i>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Идэвхтэй</p>
                            <h3 class="text-2xl font-bold text-slate-800">9</h3>
                        </div>
                        <div class="w-10 h-10 bg-green-50 text-green-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Хоосон</p>
                            <h3 class="text-2xl font-bold text-slate-800">1</h3>
                        </div>
                        <div class="w-10 h-10 bg-slate-100 text-slate-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-inbox"></i>
                        </div>
                    </div>
                </div>

                <!-- Categories Table -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                    <th class="px-6 py-4 font-semibold w-1/3">Ангилалын нэр</th>
                                    <th class="px-6 py-4 font-semibold">Slug (URL)</th>
                                    <th class="px-6 py-4 font-semibold">Файл тоо</th>
                                    <th class="px-6 py-4 font-semibold">Төлөв</th>
                                    <th class="px-6 py-4 font-semibold text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($categories as $cat): ?>
                                    <!-- Parent Category Row -->
                                    <tr id="cat-<?php echo $cat['id']; ?>" class="hover:bg-slate-50 transition-colors group bg-white">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                                                    <i class="fas <?php echo $cat['icon']; ?>"></i>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-slate-800"><?php echo $cat['name']; ?></p>
                                                    <p class="text-xs text-slate-400">Үндсэн ангилал</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-mono text-slate-500">/<?php echo $cat['slug']; ?></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                                <?php echo number_format($cat['file_count']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if($cat['status'] == 'active'): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-500">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($cat)); ?>)" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded hover:bg-indigo-50 transition" title="Засах">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50 transition" title="Устгах">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Subcategories Loop -->
                                    <?php foreach ($cat['subcategories'] as $sub): ?>
                                    <tr id="cat-<?php echo $sub['id']; ?>" class="subcategory-row transition-colors group border-l-4 border-l-indigo-500/0 hover:border-l-indigo-500">
                                        <td class="px-6 py-3 relative">
                                            <!-- Visual Tree structure -->
                                            <div class="flex items-center gap-3 pl-8">
                                                <div class="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-[1px] bg-slate-300"></div>
                                                <div class="absolute left-6 top-0 h-1/2 w-[1px] bg-slate-300"></div>
                                                
                                                <div class="w-2 h-2 rounded-full bg-slate-300"></div>
                                                <p class="text-sm font-medium text-slate-700"><?php echo $sub['name']; ?></p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-3 text-sm font-mono text-slate-400">/<?php echo $cat['slug']; ?>/<?php echo $sub['slug']; ?></td>
                                        <td class="px-6 py-3">
                                            <span class="text-xs text-slate-500"><?php echo number_format($sub['file_count']); ?> файл</span>
                                        </td>
                                        <td class="px-6 py-3">
                                            <?php if($sub['status'] == 'active'): ?>
                                                <span class="text-xs text-green-600 font-medium">Active</span>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400 font-medium">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($sub)); ?>)" class="p-1 text-slate-400 hover:text-indigo-600 text-xs">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button class="p-1 text-slate-400 hover:text-red-600 text-xs">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>
    
    <!-- Add/Edit Category Modal -->
    <div id="categoryModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Ангилал нэмэх</h3>
                    <div class="mt-4 space-y-4">
                        <input type="hidden" id="catId">
                        
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ангилалын нэр</label>
                            <input type="text" id="catName" placeholder="Жишээ: Програм хангамж" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <!-- Slug -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Slug (URL)</label>
                            <input type="text" id="catSlug" placeholder="Жишээ: software" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-gray-50">
                        </div>

                        <!-- Parent Category Select -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Үндсэн ангилал (Хамаарах)</label>
                            <select id="catParent" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">-- Үндсэн ангилал (Main) --</option>
                                <?php foreach ($categories as $pCat): ?>
                                    <option value="<?php echo $pCat['id']; ?>"><?php echo $pCat['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Хэрэв дэд ангилал бол эндээс эцэг ангилалыг нь сонгоно уу.</p>
                        </div>

                        <!-- Icon (Only for Main categories really, but useful generally) -->
                        <div id="iconGroup">
                            <label class="block text-sm font-medium text-gray-700">Icon Class (FontAwesome)</label>
                            <input type="text" id="catIcon" placeholder="fa-folder" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Төлөв</label>
                            <select id="catStatus" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="active">Идэвхтэй</option>
                                <option value="inactive">Идэвхгүй</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="saveCategory()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Хадгалах</button>
                    <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Болих</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>