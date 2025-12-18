<?php
// --------------------------------------------------------------------------
// MOCK DATA (Хэрэглэгчдийн жагсаалт)
// --------------------------------------------------------------------------

$users = [
    ['id' => 1, 'name' => 'Bat-Erdene', 'email' => 'bat@example.com', 'role' => 'user', 'joined' => '2025-01-15', 'status' => 'active', 'avatar' => 'https://ui-avatars.com/api/?name=Bat-Erdene&background=random'],
    ['id' => 2, 'name' => 'Sarnai Tsetseg', 'email' => 'sarnai@example.com', 'role' => 'editor', 'joined' => '2025-02-20', 'status' => 'active', 'avatar' => 'https://ui-avatars.com/api/?name=Sarnai&background=random'],
    ['id' => 3, 'name' => 'Boldoo Admin', 'email' => 'admin@filezone.mn', 'role' => 'admin', 'joined' => '2024-12-01', 'status' => 'active', 'avatar' => 'https://ui-avatars.com/api/?name=Boldoo&background=6366f1&color=fff'],
    ['id' => 4, 'name' => 'Spammer Guy', 'email' => 'spam@fake.com', 'role' => 'user', 'joined' => '2025-10-23', 'status' => 'banned', 'avatar' => 'https://ui-avatars.com/api/?name=Spammer&background=ef4444&color=fff'],
    ['id' => 5, 'name' => 'Tuya N', 'email' => 'tuya@example.com', 'role' => 'user', 'joined' => '2025-05-10', 'status' => 'pending', 'avatar' => 'https://ui-avatars.com/api/?name=Tuya&background=random'],
    ['id' => 6, 'name' => 'Gantulga', 'email' => 'gana@example.com', 'role' => 'user', 'joined' => '2025-08-14', 'status' => 'active', 'avatar' => 'https://ui-avatars.com/api/?name=Gantulga&background=random'],
];

?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Filezone Admin</title>
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
                    <h1 class="text-xl font-bold text-slate-800">Хэрэглэгчийн удирдлага</h1>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="alert('Шинэ хэрэглэгч нэмэх цонх (Demo)')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                        <i class="fas fa-user-plus"></i> <span class="hidden sm:inline">Хэрэглэгч нэмэх</span>
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
                            <input type="text" placeholder="Нэр, Имэйл эсвэл ID-аар хайх..." class="pl-10 pr-4 py-2 w-full border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        
                        <!-- Role Filter -->
                        <select class="border border-slate-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-slate-600">
                            <option value="">Бүх үүрэг</option>
                            <option value="admin">Администратор</option>
                            <option value="editor">Редактор</option>
                            <option value="user">Хэрэглэгч</option>
                        </select>

                        <!-- Status Filter -->
                        <select class="border border-slate-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-slate-600">
                            <option value="">Бүх төлөв</option>
                            <option value="active">Идэвхтэй</option>
                            <option value="pending">Хүлээгдэж буй</option>
                            <option value="banned">Бандуулсан</option>
                        </select>
                    </div>

                    <!-- Export/Bulk Actions -->
                    <div class="flex items-center gap-2">
                        <button class="p-2 text-slate-500 hover:text-slate-700 border border-slate-300 rounded-lg bg-white" title="Шинэчлэх">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button class="p-2 text-slate-500 hover:text-slate-700 border border-slate-300 rounded-lg bg-white" title="Excel татах">
                            <i class="fas fa-file-excel"></i>
                        </button>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                    <th class="px-6 py-4 w-10">
                                        <input type="checkbox" class="form-checkbox text-indigo-600 rounded">
                                    </th>
                                    <th class="px-6 py-4 font-semibold">Хэрэглэгч</th>
                                    <th class="px-6 py-4 font-semibold">Үүрэг</th>
                                    <th class="px-6 py-4 font-semibold">Бүртгүүлсэн</th>
                                    <th class="px-6 py-4 font-semibold">Төлөв</th>
                                    <th class="px-6 py-4 font-semibold text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($users as $user): ?>
                                <tr id="user-row-<?php echo $user['id']; ?>" class="hover:bg-slate-50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" class="form-checkbox text-indigo-600 rounded">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <img class="h-10 w-10 rounded-full border border-slate-200" src="<?php echo $user['avatar']; ?>" alt="">
                                            <div>
                                                <div class="text-sm font-semibold text-slate-900 user-name"><?php echo $user['name']; ?></div>
                                                <div class="text-xs text-slate-500 user-email"><?php echo $user['email']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="user-role inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-800'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-slate-600"><?php echo $user['joined']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 user-status-cell">
                                        <?php if($user['status'] == 'active'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Идэвхтэй
                                            </span>
                                        <?php elseif($user['status'] == 'pending'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Хүлээгдэж буй
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Бандуулсан
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button onclick="openEditUserModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?>', '<?php echo $user['email']; ?>', '<?php echo $user['role']; ?>', '<?php echo $user['status']; ?>')" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded hover:bg-indigo-50 transition" title="Засах">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            
                                            <?php if($user['status'] !== 'banned'): ?>
                                            <button onclick="banUser(<?php echo $user['id']; ?>)" class="p-1.5 text-slate-400 hover:text-orange-600 rounded hover:bg-orange-50 transition" title="Бандах">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                            <?php else: ?>
                                            <button onclick="unbanUser(<?php echo $user['id']; ?>)" class="p-1.5 text-slate-400 hover:text-green-600 rounded hover:bg-green-50 transition" title="Бан цуцлах">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                            <?php endif; ?>

                                            <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50 transition" title="Устгах">
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
                        <span class="text-sm text-slate-500">Нийт 1250 хэрэглэгчээс 1-6 харагдаж байна</span>
                        <div class="flex items-center gap-1">
                            <button class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 disabled:opacity-50 text-slate-600" disabled>Өмнөх</button>
                            <button class="px-3 py-1 text-sm border border-indigo-500 bg-indigo-50 text-indigo-600 rounded font-medium">1</button>
                            <button class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 text-slate-600">2</button>
                            <span class="text-slate-400 px-1">...</span>
                            <button class="px-3 py-1 text-sm border border-slate-300 rounded hover:bg-slate-50 text-slate-600">Дараах</button>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeEditUserModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Хэрэглэгчийн мэдээлэл засах</h3>
                    <div class="mt-4 space-y-4">
                        <input type="hidden" id="editUserId">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Нэр</label>
                            <input type="text" id="editUserName" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Имэйл</label>
                            <input type="email" id="editUserEmail" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Үүрэг</label>
                                <select id="editUserRole" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="user">Хэрэглэгч</option>
                                    <option value="editor">Редактор</option>
                                    <option value="admin">Администратор</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Төлөв</label>
                                <select id="editUserStatus" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="active">Идэвхтэй</option>
                                    <option value="pending">Хүлээгдэж буй</option>
                                    <option value="banned">Бандуулсан</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="saveUserChanges()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Хадгалах</button>
                    <button type="button" onclick="closeEditUserModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Болих</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>