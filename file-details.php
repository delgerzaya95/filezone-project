<?php 
// 1. АЛДАА ИЛРҮҮЛЭХ КОД (Development mode)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'includes/db.php';

// --------------------------------------------------------------------------
// 2. MAIN PAGE LOGIC
// --------------------------------------------------------------------------

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: browse-files.php");
    exit;
}

$file_id = intval($_GET['id']);
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// 1. Fetch File Details with User & Category info
$stmt = $pdo->prepare("
    SELECT f.*, u.username, u.avatar_url, u.level, 
           c.name as category_name, c.slug as category_slug,
           s.name as subcategory_name
    FROM files f
    LEFT JOIN users u ON f.user_id = u.id
    LEFT JOIN categories c ON f.category_id = c.id
    LEFT JOIN subcategories s ON f.subcategory_id = s.id
    WHERE f.id = ? AND f.status = 'approved'
");
$stmt->execute([$file_id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    echo "Файл олдсонгүй эсвэл зөвшөөрөгдөөгүй байна.";
    exit;
}

// 2. Fetch File Previews
$stmt_prev = $pdo->prepare("SELECT preview_url FROM file_previews WHERE file_id = ? ORDER BY order_index ASC");
$stmt_prev->execute([$file_id]);
$previews = $stmt_prev->fetchAll(PDO::FETCH_COLUMN);

// If no previews, use a default placeholder
if (empty($previews)) {
    $previews[] = 'assets/images/file-placeholder.jpg'; 
}

// 3. Increment View Count
$stmt_view = $pdo->prepare("UPDATE files SET view_count = view_count + 1 WHERE id = ?");
$stmt_view->execute([$file_id]);

// 4. Check Purchase Status & Saved Status
$has_purchased = false;
$is_saved = false;

if ($current_user_id) {
    // Check Purchased
    if ($file['price'] <= 0 || $current_user_id == $file['user_id']) {
        $has_purchased = true;
    } else {
        $stmt_check = $pdo->prepare("SELECT id FROM transactions WHERE user_id = ? AND file_id = ? AND status = 'success'");
        $stmt_check->execute([$current_user_id, $file_id]);
        if ($stmt_check->rowCount() > 0) {
            $has_purchased = true;
        }
    }

    // Check Saved
    try {
        $stmt_save = $pdo->prepare("SELECT id FROM saved_files WHERE user_id = ? AND file_id = ?");
        $stmt_save->execute([$current_user_id, $file_id]);
        if ($stmt_save->rowCount() > 0) {
            $is_saved = true;
        }
    } catch (PDOException $e) {
        // Table might not exist yet
    }
} elseif ($file['price'] <= 0) {
    $has_purchased = true; // Free file for guests
}

// 5. Fetch Comments
$stmt_comm = $pdo->prepare("
    SELECT c.*, u.username, u.avatar_url 
    FROM comments c 
    LEFT JOIN users u ON c.user_id = u.id 
    WHERE c.file_id = ? AND c.status = 'approved' 
    ORDER BY c.comment_date DESC
");
$stmt_comm->execute([$file_id]);
$comments = $stmt_comm->fetchAll(PDO::FETCH_ASSOC);

// 6. Fetch Related Files (Same Category)
$stmt_rel = $pdo->prepare("
    SELECT f.*, u.username 
    FROM files f 
    LEFT JOIN users u ON f.user_id = u.id 
    WHERE f.category_id = ? AND f.id != ? AND f.status = 'approved' 
    ORDER BY f.download_count DESC 
    LIMIT 4
");
$stmt_rel->execute([$file['category_id'], $file_id]);
$related_files = $stmt_rel->fetchAll(PDO::FETCH_ASSOC);

// Helper function for icons
function getFileIconClass($type) {
    switch ($type) {
        case 'pdf': return 'fa-file-pdf text-red-500';
        case 'doc': case 'docx': return 'fa-file-word text-blue-500';
        case 'xls': case 'xlsx': return 'fa-file-excel text-green-500';
        case 'ppt': case 'pptx': return 'fa-file-powerpoint text-orange-500';
        case 'zip': case 'rar': return 'fa-file-archive text-yellow-500';
        default: return 'fa-file text-gray-400';
    }
}

// Page Title
$page_title = $file['title'] . " - Filezone.mn";
include 'includes/header.php';
?>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <!-- Sidebar Navigation -->
    <aside class="hidden lg:block w-64 flex-shrink-0 py-6 pr-6 h-[calc(100vh-64px)] sticky top-16 overflow-y-auto no-scrollbar">
        <!-- Mini Upload CTA -->
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 text-white shadow-xl">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                    <i class="fas fa-wallet text-yellow-400"></i>
                </div>
                <span class="font-bold text-sm">Мөнгө олох уу?</span>
            </div>
            <p class="text-xs text-gray-300 mb-3 leading-relaxed">Хэрэггүй файлаа устгах биш, бусдад зарж орлого ол!</p>
            <a href="upload.php" class="block w-full text-center bg-white text-gray-900 py-1.5 rounded-lg text-xs font-bold hover:bg-gray-100 transition">
                Эхлэх &rarr;
            </a>
        </div>

        <!-- Menu Links -->
        <div class="space-y-1 mb-6">
            <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Үндсэн</h3>
            <a href="index.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors">
                <i class="fas fa-home w-5 text-center"></i> Нүүр хуудас
            </a>
            <a href="browse-files.php" class="flex items-center gap-3 px-3 py-2 text-brand-600 bg-brand-50 rounded-lg font-medium">
                <i class="fas fa-folder-open w-5 text-center"></i> Файлууд
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
        
        <!-- Breadcrumb -->
        <nav class="flex mb-6 text-xs text-gray-500">
            <ol class="flex items-center space-x-2 flex-wrap">
                <li><a href="index.php" class="hover:text-brand-600">Нүүр</a></li>
                <li><span class="text-gray-300">/</span></li>
                <li><a href="browse-files.php" class="hover:text-brand-600">Файлууд</a></li>
                <li><span class="text-gray-300">/</span></li>
                <li>
                    <a href="browse-files.php?cat=<?php echo htmlspecialchars($file['category_slug']); ?>" class="hover:text-brand-600">
                        <?php echo htmlspecialchars($file['category_name']); ?>
                    </a>
                </li>
                <?php if($file['subcategory_name']): ?>
                <li><span class="text-gray-300">/</span></li>
                <li><span class="text-gray-500"><?php echo htmlspecialchars($file['subcategory_name']); ?></span></li>
                <?php endif; ?>
                <li><span class="text-gray-300">/</span></li>
                <li class="font-medium text-gray-900 line-clamp-1 max-w-[150px]" title="<?php echo htmlspecialchars($file['title']); ?>">
                    <?php echo htmlspecialchars($file['title']); ?>
                </li>
            </ol>
        </nav>

        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Left Column: Preview & Description -->
            <div class="lg:w-2/3">
                
                <!-- File Header (Mobile Only) -->
                <div class="lg:hidden mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($file['title']); ?></h1>
                    <div class="flex items-center gap-4 text-xs text-gray-500">
                        <span class="flex items-center gap-1"><i class="fas fa-calendar"></i> <?php echo date('Y-m-d', strtotime($file['upload_date'])); ?></span>
                        <span class="flex items-center gap-1"><i class="fas fa-eye"></i> <?php echo number_format($file['view_count']); ?> үзсэн</span>
                    </div>
                </div>

                <!-- Preview Image Slider/Display -->
                <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm mb-8">
                    <div class="aspect-video bg-gray-100 relative group flex items-center justify-center overflow-hidden">
                        
                        <?php if (count($previews) > 1): ?>
                            <!-- Slider Controls -->
                            <button onclick="prevSlide()" class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 text-white w-8 h-8 rounded-full flex items-center justify-center hover:bg-black/70 z-10 opacity-0 group-hover:opacity-100 transition"><i class="fas fa-chevron-left"></i></button>
                            <button onclick="nextSlide()" class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 text-white w-8 h-8 rounded-full flex items-center justify-center hover:bg-black/70 z-10 opacity-0 group-hover:opacity-100 transition"><i class="fas fa-chevron-right"></i></button>
                        <?php endif; ?>

                        <div id="slider-container" class="w-full h-full relative">
                            <?php foreach($previews as $idx => $url): ?>
                                <img src="<?php echo htmlspecialchars($url); ?>" 
                                     class="w-full h-full object-contain absolute inset-0 transition-opacity duration-300 <?php echo $idx === 0 ? 'opacity-100 relative' : 'opacity-0'; ?>" 
                                     data-index="<?php echo $idx; ?>" 
                                     alt="Preview <?php echo $idx+1; ?>">
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Thumbnails (if multiple) -->
                    <?php if (count($previews) > 1): ?>
                    <div class="flex gap-2 p-2 overflow-x-auto bg-gray-50 border-t border-gray-200">
                        <?php foreach($previews as $idx => $url): ?>
                            <div onclick="goToSlide(<?php echo $idx; ?>)" class="w-16 h-12 rounded border border-gray-300 cursor-pointer overflow-hidden opacity-70 hover:opacity-100 transition <?php echo $idx === 0 ? 'ring-2 ring-brand-500 opacity-100' : ''; ?>" id="thumb-<?php echo $idx; ?>">
                                <img src="<?php echo htmlspecialchars($url); ?>" class="w-full h-full object-cover">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="flex gap-6" id="detailTabs">
                        <button onclick="switchTab('desc')" id="tab-desc" class="border-b-2 border-brand-600 py-3 text-sm font-bold text-brand-600 transition-colors">Тайлбар</button>
                        <button onclick="switchTab('comments')" id="tab-comments" class="border-b-2 border-transparent py-3 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors">Сэтгэгдэл (<span id="comment-count"><?php echo count($comments); ?></span>)</button>
                    </nav>
                </div>

                <!-- Content Area -->
                <div id="content-desc">
                    <!-- Description Content -->
                    <div class="prose prose-sm max-w-none text-gray-600 mb-10">
                        <?php echo $file['description']; // TinyMCE HTML content ?>
                    </div>
                </div>

                <div id="content-comments" class="hidden">
                    <!-- Comment Form -->
                    <?php if($current_user_id): ?>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 mb-6">
                        <form id="commentForm" onsubmit="submitComment(event)">
                            <input type="hidden" name="file_id" value="<?php echo $file_id; ?>">
                            <div class="flex gap-3">
                                <img src="<?php echo $_SESSION['avatar'] ?? 'assets/avatars/default.png'; ?>" class="w-8 h-8 rounded-full">
                                <div class="flex-1">
                                    <textarea name="comment" id="commentText" rows="2" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none" placeholder="Сэтгэгдэл бичих..." required></textarea>
                                    <div class="flex justify-end mt-2">
                                        <button type="submit" id="submitBtn" class="bg-brand-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-brand-700 disabled:opacity-50">Илгээх</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4 bg-gray-50 rounded-xl border border-gray-100 mb-6">
                        <p class="text-sm text-gray-500">Сэтгэгдэл бичихийн тулд <a href="login.php?redirect=file-details.php?id=<?php echo $file_id; ?>" class="text-brand-600 font-bold">нэвтэрнэ үү</a>.</p>
                    </div>
                    <?php endif; ?>

                    <!-- Comments List -->
                    <div class="space-y-6" id="commentsList">
                        <?php if(empty($comments)): ?>
                            <p class="text-gray-500 text-sm text-center" id="noCommentsMsg">Одоогоор сэтгэгдэл байхгүй байна.</p>
                        <?php else: ?>
                            <?php foreach($comments as $comment): ?>
                            <div class="flex gap-4 border-b border-gray-100 pb-4 last:border-0 comment-item">
                                <img src="<?php echo !empty($comment['avatar_url']) ? $comment['avatar_url'] : 'assets/avatars/default.png'; ?>" class="w-10 h-10 rounded-full bg-gray-200 flex-shrink-0 object-cover">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-bold text-sm text-gray-900"><?php echo htmlspecialchars($comment['username']); ?></span>
                                        <span class="text-xs text-gray-400"><?php echo date('Y-m-d H:i', strtotime($comment['comment_date'])); ?></span>
                                    </div>
                                    <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Right Column: Info & Purchase (Sticky) -->
            <div class="lg:w-1/3">
                <div class="sticky top-24 space-y-6">
                    
                    <!-- Purchase Card -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-lg p-6">
                        <h1 class="hidden lg:block text-xl font-bold text-gray-900 mb-4 leading-snug"><?php echo htmlspecialchars($file['title']); ?></h1>
                        
                        <?php if($file['price'] > 0): ?>
                            <div class="flex items-end gap-2 mb-6">
                                <span class="text-3xl font-bold text-green-600"><?php echo number_format($file['price']); ?>₮</span>
                            </div>
                        <?php else: ?>
                            <div class="flex items-end gap-2 mb-6">
                                <span class="text-3xl font-bold text-green-600">Үнэгүй</span>
                            </div>
                        <?php endif; ?>

                        <?php if($has_purchased): ?>
                            <a href="download.php?file_id=<?php echo $file_id; ?>" class="w-full bg-green-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-green-500/30 hover:bg-green-700 transition flex items-center justify-center gap-2 mb-3">
                                <i class="fas fa-download"></i> Шууд татах
                            </a>
                            <p class="text-xs text-center text-green-600 mb-3">
                                <i class="fas fa-check-circle mr-1"></i> Та энэ файлыг эзэмшиж байна.
                            </p>
                        <?php else: ?>
                            <?php if($current_user_id): ?>
                                <a href="payment.php?type=file&id=<?php echo $file_id; ?>" class="w-full bg-brand-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-brand-500/30 hover:bg-brand-700 transition btn-shine flex items-center justify-center gap-2 mb-3">
                                    <i class="fas fa-shopping-cart"></i> Худалдаж авах
                                </a>
                            <?php else: ?>
                                <a href="login.php?redirect=file-details.php?id=<?php echo $file_id; ?>" class="w-full bg-brand-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-brand-500/30 hover:bg-brand-700 transition flex items-center justify-center gap-2 mb-3">
                                    <i class="fas fa-sign-in-alt"></i> Нэвтэрч авах
                                </a>
                            <?php endif; ?>
                            
                            <p class="text-xs text-center text-gray-500 mb-3">
                                <i class="fas fa-lock text-gray-400 mr-1"></i> Төлбөр төлсний дараа шууд татагдана.
                            </p>
                        <?php endif; ?>

                        <!-- SAVE BUTTON (UPDATED) -->
                        <button id="saveBtn" onclick="toggleSaveFile(<?php echo $file_id; ?>)" 
                                class="w-full py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-colors flex items-center justify-center gap-2 mb-6">
                            <i class="<?php echo $is_saved ? 'fas' : 'far'; ?> fa-heart <?php echo $is_saved ? 'text-red-500' : ''; ?>"></i> 
                            <span id="saveText"><?php echo $is_saved ? 'Хадгалсан' : 'Хадгалах'; ?></span>
                        </button>

                        <!-- File Stats Grid -->
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 text-center">
                                <div class="text-gray-400 text-xs mb-1">Файлын төрөл</div>
                                <div class="font-bold text-gray-800 flex items-center justify-center gap-1 uppercase">
                                    <i class="fas <?php echo getFileIconClass($file['file_type']); ?>"></i> <?php echo htmlspecialchars($file['file_type']); ?>
                                </div>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 text-center">
                                <div class="text-gray-400 text-xs mb-1">Хэмжээ</div>
                                <div class="font-bold text-gray-800">
                                    <?php 
                                        $size = $file['file_size'];
                                        if($size < 1024) echo $size . " B";
                                        elseif($size < 1048576) echo round($size/1024, 1) . " KB";
                                        else echo round($size/1048576, 1) . " MB";
                                    ?>
                                </div>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 text-center">
                                <div class="text-gray-400 text-xs mb-1">Шинэчлэгдсэн</div>
                                <div class="font-bold text-gray-800 text-xs"><?php echo date('Y/m/d', strtotime($file['last_updated'])); ?></div>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 text-center">
                                <div class="text-gray-400 text-xs mb-1">Таталт</div>
                                <div class="font-bold text-gray-800"><?php echo number_format($file['download_count']); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Seller Profile Card -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
                        <div class="relative">
                            <img src="<?php echo !empty($file['avatar_url']) ? $file['avatar_url'] : 'assets/avatars/default.png'; ?>" class="w-12 h-12 rounded-full object-cover bg-gray-100">
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 text-sm"><?php echo htmlspecialchars($file['username']); ?></h4>
                            <p class="text-xs text-gray-500 mb-1 capitalize"><?php echo htmlspecialchars($file['level']); ?> Seller</p>
                        </div>
                        <a href="user-profile.php?id=<?php echo $file['user_id']; ?>" class="text-brand-600 hover:bg-brand-50 p-2 rounded-lg transition">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>

                    <!-- Safety Notice -->
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex gap-3">
                        <i class="fas fa-shield-alt text-blue-500 mt-0.5"></i>
                        <div class="text-xs text-blue-800">
                            <p class="font-bold mb-1">Найдвартай файл</p>
                            <p class="opacity-80">Энэ файлыг администратор шалгаж баталгаажуулсан болно.</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Related Files -->
        <?php if(!empty($related_files)): ?>
        <div class="mt-16 mb-10">
            <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                <span class="w-1 h-6 bg-brand-600 rounded-full"></span>
                Төстэй файлууд
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach($related_files as $rel): ?>
                <a href="file-details.php?id=<?php echo $rel['id']; ?>" class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow group relative block">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center text-xl border border-gray-100">
                            <i class="fas <?php echo getFileIconClass($rel['file_type']); ?>"></i>
                        </div>
                        <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded uppercase"><?php echo htmlspecialchars($rel['file_type']); ?></span>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2 group-hover:text-brand-600 transition-colors text-sm" title="<?php echo htmlspecialchars($rel['title']); ?>">
                        <?php echo htmlspecialchars($rel['title']); ?>
                    </h3>
                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-download mr-1"></i> <?php echo number_format($rel['download_count']); ?></span>
                        <span>•</span>
                        <span><?php echo htmlspecialchars($rel['username']); ?></span>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                        <?php if($rel['price'] > 0): ?>
                            <span class="font-bold text-gray-900"><?php echo number_format($rel['price']); ?>₮</span>
                        <?php else: ?>
                            <span class="font-bold text-green-600">Үнэгүй</span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

<script>
    // --- Slider Logic ---
    let currentSlide = 0;
    const slides = document.querySelectorAll('#slider-container img');
    const thumbs = document.querySelectorAll('[id^="thumb-"]');

    function showSlide(n) {
        if(slides.length === 0) return;
        
        slides[currentSlide].classList.remove('opacity-100', 'relative');
        slides[currentSlide].classList.add('opacity-0', 'absolute');
        
        // Remove active class from previous thumb
        if(thumbs.length > 0) {
            thumbs[currentSlide].classList.remove('ring-2', 'ring-brand-500', 'opacity-100');
            thumbs[currentSlide].classList.add('opacity-70');
        }

        currentSlide = (n + slides.length) % slides.length;

        slides[currentSlide].classList.remove('opacity-0', 'absolute');
        slides[currentSlide].classList.add('opacity-100', 'relative');

        // Add active class to new thumb
        if(thumbs.length > 0) {
            thumbs[currentSlide].classList.remove('opacity-70');
            thumbs[currentSlide].classList.add('ring-2', 'ring-brand-500', 'opacity-100');
        }
    }

    function nextSlide() { showSlide(currentSlide + 1); }
    function prevSlide() { showSlide(currentSlide - 1); }
    function goToSlide(n) { showSlide(n); }

    // --- Tab Logic ---
    function switchTab(tab) {
        const btnDesc = document.getElementById('tab-desc');
        const btnComments = document.getElementById('tab-comments');
        const contentDesc = document.getElementById('content-desc');
        const contentComments = document.getElementById('content-comments');

        if (tab === 'desc') {
            btnDesc.className = 'border-b-2 border-brand-600 py-3 text-sm font-bold text-brand-600 transition-colors';
            btnComments.className = 'border-b-2 border-transparent py-3 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors';
            contentDesc.classList.remove('hidden');
            contentComments.classList.add('hidden');
        } else {
            btnComments.className = 'border-b-2 border-brand-600 py-3 text-sm font-bold text-brand-600 transition-colors';
            btnDesc.className = 'border-b-2 border-transparent py-3 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors';
            contentComments.classList.remove('hidden');
            contentDesc.classList.add('hidden');
        }
    }

    // --- AJAX Comment Logic ---
    function submitComment(e) {
        e.preventDefault();
        const form = document.getElementById('commentForm');
        const submitBtn = document.getElementById('submitBtn');
        const commentText = document.getElementById('commentText').value;
        const fileId = form.file_id.value;
        
        if (!commentText.trim()) return;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '...';

        const formData = new FormData();
        formData.append('file_id', fileId);
        formData.append('comment', commentText);

        fetch('api/post_comment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Илгээх';

            if (data.success) {
                document.getElementById('commentText').value = '';
                const noCommentsMsg = document.getElementById('noCommentsMsg');
                if (noCommentsMsg) noCommentsMsg.remove();

                const commentsList = document.getElementById('commentsList');
                const newCommentHtml = `
                    <div class="flex gap-4 border-b border-gray-100 pb-4 last:border-0 comment-item animate-fade-in">
                        <img src="${data.user.avatar}" class="w-10 h-10 rounded-full bg-gray-200 flex-shrink-0 object-cover">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-bold text-sm text-gray-900">${data.user.username}</span>
                                <span class="text-xs text-gray-400">Дөнгөж сая</span>
                            </div>
                            <p class="text-sm text-gray-600">${data.comment}</p>
                        </div>
                    </div>
                `;
                commentsList.insertAdjacentHTML('afterbegin', newCommentHtml);
                const countSpan = document.getElementById('comment-count');
                countSpan.innerText = parseInt(countSpan.innerText) + 1;
            } else {
                alert(data.message || 'Алдаа гарлаа. Дахин оролдоно уу.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Илгээх';
            alert('Сүлжээний алдаа гарлаа.');
        });
    }

    // --- Save File Logic (API) ---
    async function toggleSaveFile(fileId) {
        const btn = document.getElementById('saveBtn');
        const icon = btn.querySelector('i');
        const text = document.getElementById('saveText');
        const originalText = text.innerText;

        text.innerText = 'Уншиж байна...';
        btn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('action', 'toggle_save_file');
            formData.append('id', fileId);

            const response = await fetch('api/toggle_save.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                if (result.status === 'saved') {
                    icon.classList.remove('far');
                    icon.classList.add('fas', 'text-red-500');
                    text.innerText = 'Хадгалсан';
                } else {
                    icon.classList.remove('fas', 'text-red-500');
                    icon.classList.add('far');
                    text.innerText = 'Хадгалах';
                }
            } else {
                if(result.message === 'Login required') {
                    // Optional: Redirect to login
                    alert('Та нэвтэрсний дараа хадгалах боломжтой.');
                } else {
                    alert(result.message);
                    text.innerText = originalText;
                }
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Алдаа гарлаа.');
            text.innerText = originalText;
        } finally {
            btn.disabled = false;
        }
    }
</script>

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        animation: fadeIn 0.5s ease-out forwards;
    }
</style>

<?php include 'includes/footer.php'; ?>