<?php
session_start();

// Database холболт
require_once '../includes/db.php';

// Админ эрх шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// --------------------------------------------------------------------------
// HELPER FUNCTIONS (Color Generator)
// --------------------------------------------------------------------------

function get_category_color($id) {
    $colors = [
        'bg-red-100 text-red-600',
        'bg-orange-100 text-orange-600',
        'bg-amber-100 text-amber-600',
        'bg-yellow-100 text-yellow-600',
        'bg-lime-100 text-lime-600',
        'bg-green-100 text-green-600',
        'bg-emerald-100 text-emerald-600',
        'bg-teal-100 text-teal-600',
        'bg-cyan-100 text-cyan-600',
        'bg-sky-100 text-sky-600',
        'bg-blue-100 text-blue-600',
        'bg-indigo-100 text-indigo-600',
        'bg-violet-100 text-violet-600',
        'bg-purple-100 text-purple-600',
        'bg-fuchsia-100 text-fuchsia-600',
        'bg-pink-100 text-pink-600',
        'bg-rose-100 text-rose-600'
    ];
    
    // ID-аар нь индекс гаргаж авах (Modulo operator)
    $index = $id % count($colors);
    return $colors[$index];
}

// --------------------------------------------------------------------------
// ACTION HANDLERS (Add, Edit, Delete)
// --------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. ADD / UPDATE CATEGORY
    if (isset($_POST['category_action'])) {
        $name = $_POST['name'];
        $slug = $_POST['slug'];
        $icon_class = $_POST['icon_class'];
        
        if ($_POST['category_action'] == 'add') {
            // Check slug
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->rowCount() > 0) {
                $error = "Энэ slug бүртгэлтэй байна. Өөр slug сонгоно уу.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon_class) VALUES (?, ?, ?)");
                if ($stmt->execute([$name, $slug, $icon_class])) {
                    $message = "Ангилал амжилттай нэмэгдлээ.";
                } else {
                    $error = "Ангилал нэмэхэд алдаа гарлаа.";
                }
            }
        } elseif ($_POST['category_action'] == 'update') {
            $id = intval($_POST['id']);
            // Check slug excluding current
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $id]);
            if ($stmt->rowCount() > 0) {
                $error = "Энэ slug бүртгэлтэй байна.";
            } else {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, icon_class = ? WHERE id = ?");
                if ($stmt->execute([$name, $slug, $icon_class, $id])) {
                    $message = "Ангилал амжилттай шинэчлэгдлээ.";
                } else {
                    $error = "Ангилал засахад алдаа гарлаа.";
                }
            }
        }
    }

    // 2. ADD / UPDATE SUBCATEGORY
    if (isset($_POST['subcategory_action'])) {
        $category_id = intval($_POST['category_id']);
        $name = $_POST['name'];
        
        if ($_POST['subcategory_action'] == 'add') {
            $stmt = $pdo->prepare("INSERT INTO subcategories (category_id, name) VALUES (?, ?)");
            if ($stmt->execute([$category_id, $name])) {
                $message = "Дэд ангилал нэмэгдлээ.";
            } else {
                $error = "Дэд ангилал нэмэхэд алдаа гарлаа.";
            }
        } elseif ($_POST['subcategory_action'] == 'update') {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("UPDATE subcategories SET category_id = ?, name = ? WHERE id = ?");
            if ($stmt->execute([$category_id, $name, $id])) {
                $message = "Дэд ангилал шинэчлэгдлээ.";
            } else {
                $error = "Дэд ангилал засахад алдаа гарлаа.";
            }
        }
    }

    // 3. ADD / UPDATE CHILD CATEGORY
    if (isset($_POST['child_category_action'])) {
        $subcategory_id = intval($_POST['subcategory_id']);
        $name = $_POST['name'];
        
        if ($_POST['child_category_action'] == 'add') {
            $stmt = $pdo->prepare("INSERT INTO child_category (subcategory_id, name) VALUES (?, ?)");
            if ($stmt->execute([$subcategory_id, $name])) {
                $message = "Жижиг ангилал нэмэгдлээ.";
            } else {
                $error = "Жижиг ангилал нэмэхэд алдаа гарлаа.";
            }
        } elseif ($_POST['child_category_action'] == 'update') {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("UPDATE child_category SET subcategory_id = ?, name = ? WHERE id = ?");
            if ($stmt->execute([$subcategory_id, $name, $id])) {
                $message = "Жижиг ангилал шинэчлэгдлээ.";
            } else {
                $error = "Жижиг ангилал засахад алдаа гарлаа.";
            }
        }
    }

    // 4. DELETE ACTIONS
    if (isset($_POST['delete_category'])) {
        $id = intval($_POST['id']);
        // Check dependencies
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM subcategories WHERE category_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Энэ ангилалд дэд ангилал байна. Эхлээд тэдгээрийг устгана уу.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Ангилал устгагдлаа.";
        }
    }

    if (isset($_POST['delete_subcategory'])) {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM child_category WHERE subcategory_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Энэ дэд ангилалд жижиг ангилал байна.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM subcategories WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Дэд ангилал устгагдлаа.";
        }
    }

    if (isset($_POST['delete_child_category'])) {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM child_category WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Жижиг ангилал устгагдлаа.";
    }
}

// --------------------------------------------------------------------------
// DATA FETCHING
// --------------------------------------------------------------------------

// 1. Categories
$categories = $pdo->query("
    SELECT c.*, 
    (SELECT COUNT(*) FROM subcategories WHERE category_id = c.id) as subcategory_count
    FROM categories c ORDER BY c.id ASC
")->fetchAll();

// 2. Subcategories
$subcategories = $pdo->query("
    SELECT sc.*, c.name as category_name, c.icon_class as category_icon
    FROM subcategories sc
    JOIN categories c ON sc.category_id = c.id
    ORDER BY c.name, sc.name
")->fetchAll();

// 3. Child Categories
$child_categories = $pdo->query("
    SELECT cc.*, sc.name as subcategory_name, sc.category_id, c.name as category_name
    FROM child_category cc
    JOIN subcategories sc ON cc.subcategory_id = sc.id
    JOIN categories c ON sc.category_id = c.id
    ORDER BY c.name, sc.name, cc.name
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ангилал - FileZone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
</head>
<body class="font-sans text-slate-800 antialiased bg-slate-50">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- HEADER -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shadow-sm z-10">
                <div class="flex items-center gap-4">
                    <button id="mobileMenuBtn" class="md:hidden text-slate-500"><i class="fas fa-bars text-xl"></i></button>
                    <h1 class="text-xl font-bold text-slate-800">Ангилалын удирдлага</h1>
                </div>
                <!-- Profile -->
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-slate-700">Админ</span>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                
                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $message; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <!-- 1. CATEGORIES SECTION -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-8 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-gray-50">
                        <h2 class="text-lg font-bold text-slate-800">Үндсэн Ангилал</h2>
                        <button onclick="openCategoryModal('add')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded text-sm font-medium flex items-center gap-2 transition">
                            <i class="fas fa-plus"></i> Шинэ ангилал
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                                    <th class="px-6 py-3 font-semibold">Icon</th>
                                    <th class="px-6 py-3 font-semibold">Нэр</th>
                                    <th class="px-6 py-3 font-semibold">Slug (Англи)</th>
                                    <th class="px-6 py-3 font-semibold text-center">Дэд ангилал</th>
                                    <th class="px-6 py-3 font-semibold text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($categories as $cat): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 text-center w-16">
                                        <!-- Dynamic Color based on ID -->
                                        <div class="w-8 h-8 rounded <?php echo get_category_color($cat['id']); ?> flex items-center justify-center">
                                            <i class="<?php echo htmlspecialchars($cat['icon_class']); ?>"></i>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-slate-700"><?php echo htmlspecialchars($cat['name']); ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-500"><?php echo htmlspecialchars($cat['slug']); ?></td>
                                    <td class="px-6 py-4 text-center text-sm">
                                        <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded-full text-xs font-bold"><?php echo $cat['subcategory_count']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button onclick="openCategoryModal('edit', <?php echo htmlspecialchars(json_encode($cat)); ?>)" class="text-indigo-600 hover:text-indigo-900 mx-1"><i class="fas fa-edit"></i></button>
                                        <form method="POST" class="inline-block" onsubmit="return confirm('Та энэ ангиллыг устгахдаа итгэлтэй байна уу?');">
                                            <input type="hidden" name="delete_category" value="1">
                                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900 mx-1"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. SUBCATEGORIES SECTION -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-8 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-gray-50">
                        <h2 class="text-lg font-bold text-slate-800">Дэд Ангилал</h2>
                        <button onclick="openSubcategoryModal('add')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm font-medium flex items-center gap-2 transition">
                            <i class="fas fa-plus"></i> Шинэ дэд ангилал
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                                    <th class="px-6 py-3 font-semibold">Үндсэн Ангилал</th>
                                    <th class="px-6 py-3 font-semibold">Дэд Ангилал</th>
                                    <th class="px-6 py-3 font-semibold text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($subcategories as $sub): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        <span class="flex items-center gap-2">
                                            <!-- Dynamic Color here as well -->
                                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs <?php echo get_category_color($sub['category_id']); ?>">
                                                <i class="<?php echo $sub['category_icon']; ?>"></i>
                                            </div>
                                            <?php echo htmlspecialchars($sub['category_name']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-slate-700"><?php echo htmlspecialchars($sub['name']); ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <button onclick="openSubcategoryModal('edit', <?php echo htmlspecialchars(json_encode($sub)); ?>)" class="text-blue-600 hover:text-blue-900 mx-1"><i class="fas fa-edit"></i></button>
                                        <form method="POST" class="inline-block" onsubmit="return confirm('Устгах уу?');">
                                            <input type="hidden" name="delete_subcategory" value="1">
                                            <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900 mx-1"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3. CHILD CATEGORIES SECTION -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-8 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-gray-50">
                        <h2 class="text-lg font-bold text-slate-800">Жижиг Ангилал (Child)</h2>
                        <button onclick="openChildModal('add')" class="bg-teal-600 hover:bg-teal-700 text-white px-3 py-1.5 rounded text-sm font-medium flex items-center gap-2 transition">
                            <i class="fas fa-plus"></i> Шинэ жижиг ангилал
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                                    <th class="px-6 py-3 font-semibold">Үндсэн > Дэд</th>
                                    <th class="px-6 py-3 font-semibold">Жижиг Ангилал</th>
                                    <th class="px-6 py-3 font-semibold text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($child_categories as $child): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 text-sm text-slate-500">
                                        <?php echo htmlspecialchars($child['category_name']); ?> <i class="fas fa-angle-right text-xs mx-1"></i> <?php echo htmlspecialchars($child['subcategory_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-slate-700"><?php echo htmlspecialchars($child['name']); ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <button onclick="openChildModal('edit', <?php echo htmlspecialchars(json_encode($child)); ?>)" class="text-teal-600 hover:text-teal-900 mx-1"><i class="fas fa-edit"></i></button>
                                        <form method="POST" class="inline-block" onsubmit="return confirm('Устгах уу?');">
                                            <input type="hidden" name="delete_child_category" value="1">
                                            <input type="hidden" name="id" value="<?php echo $child['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900 mx-1"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- 1. CATEGORY MODAL -->
    <div id="categoryModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('categoryModal')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form method="POST" action="">
                    <input type="hidden" name="category_action" id="cat_action" value="add">
                    <input type="hidden" name="id" id="cat_id">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4" id="cat_modal_title">Шинэ ангилал</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Нэр</label>
                                <input type="text" name="name" id="cat_name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Slug (Англи)</label>
                                <input type="text" name="slug" id="cat_slug" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Icon Class (FontAwesome)</label>
                                <input type="text" name="icon_class" id="cat_icon" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="fas fa-folder">
                                <p class="text-xs text-gray-500 mt-1">Жишээ: fas fa-book, fas fa-video</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Хадгалах</button>
                        <button type="button" onclick="closeModal('categoryModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Болих</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. SUBCATEGORY MODAL -->
    <div id="subcategoryModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('subcategoryModal')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form method="POST" action="">
                    <input type="hidden" name="subcategory_action" id="sub_action" value="add">
                    <input type="hidden" name="id" id="sub_id">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4" id="sub_modal_title">Шинэ дэд ангилал</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Үндсэн ангилал</label>
                                <select name="category_id" id="sub_cat_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Дэд ангиллын нэр</label>
                                <input type="text" name="name" id="sub_name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Хадгалах</button>
                        <button type="button" onclick="closeModal('subcategoryModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Болих</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 3. CHILD CATEGORY MODAL -->
    <div id="childModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('childModal')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form method="POST" action="">
                    <input type="hidden" name="child_category_action" id="child_action" value="add">
                    <input type="hidden" name="id" id="child_id">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4" id="child_modal_title">Шинэ жижиг ангилал</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Дэд ангилал</label>
                                <select name="subcategory_id" id="child_sub_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-teal-500 focus:border-teal-500 sm:text-sm" required>
                                    <?php foreach ($subcategories as $sub): ?>
                                        <option value="<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['category_name'] . ' > ' . $sub['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Жижиг ангиллын нэр</label>
                                <input type="text" name="name" id="child_name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-teal-500 focus:border-teal-500 sm:text-sm" required>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Хадгалах</button>
                        <button type="button" onclick="closeModal('childModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Болих</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
    // General Modal Functions
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    // --- Category Modal ---
    function openCategoryModal(action, data = null) {
        const modal = document.getElementById('categoryModal');
        const title = document.getElementById('cat_modal_title');
        const actionInput = document.getElementById('cat_action');
        
        if (action === 'edit' && data) {
            title.textContent = 'Ангилал засах';
            actionInput.value = 'update';
            document.getElementById('cat_id').value = data.id;
            document.getElementById('cat_name').value = data.name;
            document.getElementById('cat_slug').value = data.slug;
            document.getElementById('cat_icon').value = data.icon_class;
        } else {
            title.textContent = 'Шинэ ангилал';
            actionInput.value = 'add';
            document.getElementById('cat_id').value = '';
            document.getElementById('cat_name').value = '';
            document.getElementById('cat_slug').value = '';
            document.getElementById('cat_icon').value = 'fas fa-folder';
        }
        modal.classList.remove('hidden');
    }

    // --- Subcategory Modal ---
    function openSubcategoryModal(action, data = null) {
        const modal = document.getElementById('subcategoryModal');
        const title = document.getElementById('sub_modal_title');
        const actionInput = document.getElementById('sub_action');
        
        if (action === 'edit' && data) {
            title.textContent = 'Дэд ангилал засах';
            actionInput.value = 'update';
            document.getElementById('sub_id').value = data.id;
            document.getElementById('sub_cat_id').value = data.category_id;
            document.getElementById('sub_name').value = data.name;
        } else {
            title.textContent = 'Шинэ дэд ангилал';
            actionInput.value = 'add';
            document.getElementById('sub_id').value = '';
            document.getElementById('sub_cat_id').selectedIndex = 0;
            document.getElementById('sub_name').value = '';
        }
        modal.classList.remove('hidden');
    }

    // --- Child Category Modal ---
    function openChildModal(action, data = null) {
        const modal = document.getElementById('childModal');
        const title = document.getElementById('child_modal_title');
        const actionInput = document.getElementById('child_action');
        
        if (action === 'edit' && data) {
            title.textContent = 'Жижиг ангилал засах';
            actionInput.value = 'update';
            document.getElementById('child_id').value = data.id;
            document.getElementById('child_sub_id').value = data.subcategory_id;
            document.getElementById('child_name').value = data.name;
        } else {
            title.textContent = 'Шинэ жижиг ангилал';
            actionInput.value = 'add';
            document.getElementById('child_id').value = '';
            document.getElementById('child_sub_id').selectedIndex = 0;
            document.getElementById('child_name').value = '';
        }
        modal.classList.remove('hidden');
    }
    </script>
</body>
</html>