<?php
session_start();
include 'includes/db.php';

// Хэрэв db.php дотор $conn үүсээгүй бол (зөвхөн хувьсагчид байвал) холболт үүсгэх
if (!isset($conn)) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Нэвтрээгүй бол нэвтрэх хуудас руу
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$file_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

// 1. Файлын мэдээллийг татах (Зөвхөн өөрийн файлыг засах)
$sql = "SELECT * FROM files WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $file_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Файл олдсонгүй эсвэл танд засах эрх байхгүй.");
}

$file = $result->fetch_assoc();

// 2. Ангиллуудыг татах (Select box-д зориулж)
$categories = [];
$cat_sql = "SELECT * FROM categories ORDER BY name ASC";
$cat_result = $conn->query($cat_sql);
while($row = $cat_result->fetch_assoc()) {
    $categories[] = $row;
}

// 3. Form Submit хийх үед
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']);
    // Subcategory логик байвал энд нэмнэ

    if (empty($title)) {
        $error = "Файлын гарчиг хоосон байна.";
    } else {
        $update_sql = "UPDATE files SET title = ?, price = ?, description = ?, category_id = ? WHERE id = ? AND user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sdsiii", $title, $price, $description, $category_id, $file_id, $user_id);
        
        if ($update_stmt->execute()) {
            $message = "Файлын мэдээлэл амжилттай шинэчлэгдлээ.";
            // Шинэчлэгдсэн мэдээллийг дахин татах
            $stmt->execute();
            $file = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "Алдаа гарлаа: " . $conn->error;
        }
    }
}

// Header
$page_title = "Файл засах - " . htmlspecialchars($file['title']);
include 'includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 py-10">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h1 class="text-lg font-bold text-gray-800">Файл засах</h1>
            <a href="dashboard.php" class="text-sm text-gray-500 hover:text-gray-700">Буцах</a>
        </div>
        
        <div class="p-6">
            <?php if($message): ?>
                <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="space-y-4">
                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Файлын гарчиг</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($file['title']); ?>" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ангилал</label>
                        <select name="category_id" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $file['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Price -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Үнэ (₮)</label>
                        <input type="number" name="price" value="<?php echo htmlspecialchars($file['price']); ?>" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500" min="0" step="100">
                        <p class="text-xs text-gray-500 mt-1">0 гэж оруулбал үнэгүй татагдана.</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Тайлбар</label>
                        <textarea name="description" rows="5" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($file['description']); ?></textarea>
                    </div>
                    
                    <div class="pt-4 flex items-center justify-end gap-3">
                        <a href="dashboard.php" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Цуцлах</a>
                        <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm">Хадгалах</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>