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
// ACTION HANDLERS (Add, Edit, Delete)
// --------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. ADD / UPDATE SERVICE CATEGORY
    if (isset($_POST['action'])) {
        $name = $_POST['name'];
        $slug = $_POST['slug'];
        $icon_class = $_POST['icon_class'];
        
        if ($_POST['action'] == 'add') {
            // Slug давхцаж байгаа эсэхийг шалгах
            $stmt = $pdo->prepare("SELECT id FROM service_categories WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->rowCount() > 0) {
                $error = "Энэ slug бүртгэлтэй байна. Өөр slug сонгоно уу.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO service_categories (name, slug, icon_class) VALUES (?, ?, ?)");
                if ($stmt->execute([$name, $slug, $icon_class])) {
                    $message = "Үйлчилгээний ангилал амжилттай нэмэгдлээ.";
                } else {
                    $error = "Ангилал нэмэхэд алдаа гарлаа.";
                }
            }
        } elseif ($_POST['action'] == 'update') {
            $id = intval($_POST['id']);
            // Slug давхцаж байгаа эсэхийг шалгах (өөрийгөө алгасах)
            $stmt = $pdo->prepare("SELECT id FROM service_categories WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $id]);
            if ($stmt->rowCount() > 0) {
                $error = "Энэ slug бүртгэлтэй байна.";
            } else {
                $stmt = $pdo->prepare("UPDATE service_categories SET name = ?, slug = ?, icon_class = ? WHERE id = ?");
                if ($stmt->execute([$name, $slug, $icon_class, $id])) {
                    $message = "Ангилал амжилттай шинэчлэгдлээ.";
                } else {
                    $error = "Ангилал засахад алдаа гарлаа.";
                }
            }
        }
    }

    // 2. DELETE SERVICE CATEGORY
    if (isset($_POST['delete_category'])) {
        $id = intval($_POST['id']);
        
        // Энэ ангилалд үйлчилгээ байгаа эсэхийг шалгах
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE category_id = ?"); // Эсвэл category_slug ашиглаж байгаа бол тохируулах
        $stmt->execute([$id]);
        
        // Хэрэв та category_slug ашиглаж байгаа бол доорх логикийг ашиглана уу:
        // $stmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE category_slug = (SELECT slug FROM service_categories WHERE id = ?)");
        
        if ($stmt->fetchColumn() > 0) {
            $error = "Энэ ангилалд үйлчилгээ бүртгэлтэй байна. Эхлээд үйлчилгээнүүдийг шилжүүлэх эсвэл устгана уу.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM service_categories WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Ангилал устгагдлаа.";
        }
    }
}

// --------------------------------------------------------------------------
// HELPER FUNCTION (Color Generator)
// --------------------------------------------------------------------------
function get_category_color($id) {
    $colors = [
        'bg-red-100 text-red-600', 'bg-orange-100 text-orange-600', 'bg-amber-100 text-amber-600',
        'bg-yellow-100 text-yellow-600', 'bg-lime-100 text-lime-600', 'bg-green-100 text-green-600',
        'bg-emerald-100 text-emerald-600', 'bg-teal-100 text-teal-600', 'bg-cyan-100 text-cyan-600',
        'bg-sky-100 text-sky-600', 'bg-blue-100 text-blue-600', 'bg-indigo-100 text-indigo-600',
        'bg-violet-100 text-violet-600', 'bg-purple-100 text-purple-600', 'bg-fuchsia-100 text-fuchsia-600',
        'bg-pink-100 text-pink-600', 'bg-rose-100 text-rose-600'
    ];
    return $colors[$id % count($colors)];
}

// --------------------------------------------------------------------------
// DATA FETCHING
// --------------------------------------------------------------------------

$search = $_GET['search'] ?? '';
$where_sql = "1=1";
$params = [];

if (!empty($search)) {
    $where_sql .= " AND (name LIKE ? OR slug LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Ангиллуудыг татах (Үйлчилгээний тоотой нь хамт)
// Тайлбар: Хэрэв services table дээр category_id байгаа бол доорх query зөв.
// Хэрэв category_slug байгаа бол JOIN хэсгийг тохируулах хэрэгтэй.
$sql = "SELECT sc.*, 
        (SELECT COUNT(*) FROM services WHERE category_id = sc.id) as service_count 
        FROM service_categories sc 
        WHERE $where_sql 
        ORDER BY sc.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Үйлчилгээний Ангилал - FileZone Admin</title>
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
                    <h1 class="text-xl font-bold text-slate-800">Үйлчилгээний ангилал</h1>
                </div>
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

                <!-- Content -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-8 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex flex-col md:flex-row justify-between items-center bg-gray-50 gap-4">
                        <div class="flex items-center gap-4 w-full md:w-auto">
                            <h2 class="text-lg font-bold text-slate-800 whitespace-nowrap">Ангилалууд</h2>
                            <form method="GET" class="relative w-full md:w-64">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Хайх..." class="pl-10 pr-4 py-1.5 w-full border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </form>
                        </div>
                        <button onclick="openModal('add')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm font-medium flex items-center gap-2 transition w-full md:w-auto justify-center">
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
                                    <th class="px-6 py-3 font-semibold text-center">Үйлчилгээ</th>
                                    <th class="px-6 py-3 font-semibold text-right">Үйлдэл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (count($categories) > 0): ?>
                                    <?php foreach ($categories as $cat): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 text-center w-16">
                                            <div class="w-8 h-8 rounded <?php echo get_category_color($cat['id']); ?> flex items-center justify-center">
                                                <i class="<?php echo htmlspecialchars($cat['icon_class']); ?>"></i>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 font-medium text-slate-700"><?php echo htmlspecialchars($cat['name']); ?></td>
                                        <td class="px-6 py-4 text-sm text-slate-500"><?php echo htmlspecialchars($cat['slug']); ?></td>
                                        <td class="px-6 py-4 text-center text-sm">
                                            <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded-full text-xs font-bold"><?php echo $cat['service_count']; ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($cat)); ?>)" class="text-indigo-600 hover:text-indigo-900 mx-1 p-1 bg-indigo-50 rounded hover:bg-indigo-100 transition"><i class="fas fa-edit"></i></button>
                                            <form method="POST" class="inline-block" onsubmit="return confirm('Та энэ ангиллыг устгахдаа итгэлтэй байна уу?');">
                                                <input type="hidden" name="delete_category" value="1">
                                                <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900 mx-1 p-1 bg-red-50 rounded hover:bg-red-100 transition"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">Ангилал олдсонгүй.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- MODAL -->
    <div id="categoryModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form method="POST" action="">
                    <input type="hidden" name="action" id="modal_action" value="add">
                    <input type="hidden" name="id" id="modal_id">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900" id="modal_title">Шинэ ангилал</h3>
                            <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Нэр</label>
                                <input type="text" name="name" id="modal_name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Slug (Англи)</label>
                                <input type="text" name="slug" id="modal_slug" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required placeholder="example-slug">
                                <p class="text-xs text-gray-500 mt-1">URL дээр харагдах нэр (зай авахгүй, жижиг үсгээр)</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Icon Class (FontAwesome)</label>
                                <div class="flex gap-2 mt-1">
                                    <div class="relative flex-grow">
                                        <input type="text" name="icon_class" id="modal_icon" class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 pl-10 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="fas fa-briefcase">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i id="icon_preview" class="fas fa-briefcase text-gray-400"></i>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Жишээ: fas fa-code, fas fa-paint-brush</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Хадгалах</button>
                        <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Болих</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
    function closeModal() {
        document.getElementById('categoryModal').classList.add('hidden');
    }

    function openModal(action, data = null) {
        const modal = document.getElementById('categoryModal');
        const title = document.getElementById('modal_title');
        const actionInput = document.getElementById('modal_action');
        
        // Reset icon preview listener
        const iconInput = document.getElementById('modal_icon');
        const iconPreview = document.getElementById('icon_preview');
        
        iconInput.oninput = function() {
            iconPreview.className = this.value + " text-gray-400";
        };

        if (action === 'edit' && data) {
            title.textContent = 'Ангилал засах';
            actionInput.value = 'update';
            document.getElementById('modal_id').value = data.id;
            document.getElementById('modal_name').value = data.name;
            document.getElementById('modal_slug').value = data.slug;
            document.getElementById('modal_icon').value = data.icon_class;
            iconPreview.className = data.icon_class + " text-gray-400";
        } else {
            title.textContent = 'Шинэ ангилал';
            actionInput.value = 'add';
            document.getElementById('modal_id').value = '';
            document.getElementById('modal_name').value = '';
            document.getElementById('modal_slug').value = '';
            document.getElementById('modal_icon').value = 'fas fa-briefcase';
            iconPreview.className = "fas fa-briefcase text-gray-400";
        }
        modal.classList.remove('hidden');
    }
    </script>
</body>
</html>