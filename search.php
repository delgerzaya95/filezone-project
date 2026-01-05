<?php
session_start();
require_once 'includes/db.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$files = [];
$services = [];

if ($query) {
    // 1. Файл хайх
    $stmt_files = $pdo->prepare("
        SELECT f.*, u.username, u.avatar_url, 
        (SELECT AVG(rating) FROM ratings WHERE file_id = f.id) as avg_rating,
        (SELECT COUNT(*) FROM ratings WHERE file_id = f.id) as review_count
        FROM files f 
        JOIN users u ON f.user_id = u.id 
        WHERE f.status = 'approved' AND (f.title LIKE ? OR f.description LIKE ?) 
        ORDER BY f.upload_date DESC LIMIT 10
    ");
    $stmt_files->execute(["%$query%", "%$query%"]);
    $files = $stmt_files->fetchAll(PDO::FETCH_ASSOC);

    // 2. Үйлчилгээ хайх
    $stmt_services = $pdo->prepare("
        SELECT s.*, u.username, u.avatar_url,
        (SELECT COUNT(*) FROM service_reviews WHERE service_id = s.id) as review_count
        FROM services s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.status = 'active' AND (s.title LIKE ? OR s.description LIKE ?) 
        ORDER BY s.created_at DESC LIMIT 10
    ");
    $stmt_services->execute(["%$query%", "%$query%"]);
    $services = $stmt_services->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = "Хайлтын үр дүн: " . htmlspecialchars($query);
include 'includes/header.php';
?>

<div class="flex flex-1 max-w-7xl mx-auto w-full py-8 px-4">
    <div class="w-full">
        
        <h1 class="text-2xl font-bold text-gray-900 mb-6">
            "<?php echo htmlspecialchars($query); ?>" хайлтын үр дүн
        </h1>

        <?php if (empty($files) && empty($services)): ?>
            <div class="text-center py-16 bg-white rounded-2xl border border-dashed border-gray-300">
                <i class="fas fa-search text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">Илэрц олдсонгүй.</p>
                <a href="index.php" class="text-brand-600 hover:underline text-sm mt-2 inline-block">Нүүр хуудас руу буцах</a>
            </div>
        <?php else: ?>

            <!-- FILES RESULTS -->
            <?php if (!empty($files)): ?>
                <div class="mb-10">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-file-alt text-blue-500"></i> Файлууд
                        </h2>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <?php foreach ($files as $file): ?>
                            <!-- File Card (Simplified) -->
                            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-all">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                                        <i class="fas fa-file-<?php echo $file['file_type'] == 'pdf' ? 'pdf' : 'alt'; ?>"></i>
                                    </div>
                                    <span class="bg-gray-100 text-gray-600 text-xs font-bold px-2 py-1 rounded uppercase"><?php echo htmlspecialchars($file['file_type']); ?></span>
                                </div>
                                <h3 class="font-bold text-gray-900 mb-2 line-clamp-2 text-sm">
                                    <a href="file-details.php?id=<?php echo $file['id']; ?>" class="hover:text-brand-600">
                                        <?php echo htmlspecialchars($file['title']); ?>
                                    </a>
                                </h3>
                                <div class="flex items-center justify-between pt-3 border-t border-gray-100 mt-2">
                                    <span class="font-bold <?php echo $file['price'] == 0 ? 'text-green-600' : 'text-gray-900'; ?>">
                                        <?php echo $file['price'] == 0 ? 'Үнэгүй' : number_format($file['price']) . '₮'; ?>
                                    </span>
                                    <a href="file-details.php?id=<?php echo $file['id']; ?>" class="text-gray-400 hover:text-brand-600"><i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SERVICES RESULTS -->
            <?php if (!empty($services)): ?>
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-briefcase text-purple-500"></i> Үйлчилгээнүүд
                        </h2>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <?php foreach ($services as $srv): ?>
                            <!-- Service Card (Simplified) -->
                            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition-all">
                                <div class="h-32 bg-gray-200 relative">
                                    <img src="<?php echo !empty($srv['cover_image']) ? htmlspecialchars($srv['cover_image']) : 'assets/images/service-placeholder.jpg'; ?>" class="w-full h-full object-cover">
                                </div>
                                <div class="p-4">
                                    <h3 class="font-bold text-gray-900 mb-2 line-clamp-2 text-sm">
                                        <a href="service-details.php?id=<?php echo $srv['id']; ?>" class="hover:text-brand-600">
                                            <?php echo htmlspecialchars($srv['title']); ?>
                                        </a>
                                    </h3>
                                    <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                                        <div class="flex text-yellow-400 text-xs gap-1">
                                            <i class="fas fa-star"></i> <?php echo number_format($srv['rating_avg'], 1); ?>
                                        </div>
                                        <span class="font-bold text-gray-900 text-sm"><?php echo number_format($srv['price_min']); ?>₮</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>