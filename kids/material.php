<?php
// Filezone Kids - Single Material View
// Path: filezone.mn/kids/material.php

// 1. DB Connections
require_once __DIR__ . '/../includes/db.php'; // Main DB (Auth)
require_once __DIR__ . '/db_kids.php';        // Kids DB (Materials)

// 2. Get Material ID securely
$material_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($material_id === 0) {
    header("Location: browse.php");
    exit();
}

// 3. Fetch Material Details
try {
    $stmt = $pdo_kids->prepare("
        SELECT m.*, 
               c.name as cat_name, 
               c.slug as cat_slug, 
               c.icon_class, 
               c.color_theme 
        FROM kids_materials m
        LEFT JOIN kids_categories c ON m.category_id = c.id
        WHERE m.id = :id AND m.status = 'active'
    ");
    $stmt->execute([':id' => $material_id]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);

    // If not found
    if (!$material) {
        header("Location: browse.php?error=notfound");
        exit();
    }

    // 4. Fetch Preview Images & Organize Slides
    // Зөвхөн нэмэлт зургуудыг баазаас татна
    $stmt_prev = $pdo_kids->prepare("
        SELECT image_path FROM kids_material_previews 
        WHERE material_id = :id 
        ORDER BY sort_order ASC
    ");
    $stmt_prev->execute([':id' => $material_id]);
    $db_previews = $stmt_prev->fetchAll(PDO::FETCH_COLUMN);

    // Слайдны массив бэлтгэх
    $previews = [];

    // А. Ковер зургийг ХАМГИЙН ЭХЭНД нэмнэ (хэрэв байгаа бол)
    if (!empty($material['cover_image'])) {
        $previews[] = $material['cover_image'];
    }

    // Б. Нэмэлт зургуудыг араас нь залгана
    if (!empty($db_previews)) {
        foreach($db_previews as $prev) {
            $previews[] = $prev;
        }
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$page_title = $material['title'] . " - Filezone Kids";

// Use KIDS header
include 'header.php';

// --- CHECK PERMISSIONS & STATUS (LIFETIME UPDATE) ---
$user_is_premium = false;
$user_logged_in = isset($_SESSION['user_id']);
$is_saved = false;

if ($user_logged_in) {
    // 1. Check Real Subscription Status (Lifetime logic)
    try {
        $stmt_sub = $pdo_kids->prepare("
            SELECT id FROM kids_subscriptions 
            WHERE user_id = ? 
            AND status = 'active' 
            AND (end_date IS NULL OR end_date > NOW()) 
            LIMIT 1
        ");
        $stmt_sub->execute([$_SESSION['user_id']]);
        if ($stmt_sub->fetch()) {
            $user_is_premium = true;
        }
    } catch (Exception $e) { /* Silent fail */ }

    // 2. Check if Saved
    try {
        $stmt_save = $pdo_kids->prepare("SELECT id FROM kids_saved_materials WHERE user_id = ? AND material_id = ?");
        $stmt_save->execute([$_SESSION['user_id'], $material_id]);
        if ($stmt_save->fetch()) {
            $is_saved = true;
        }
    } catch (Exception $e) { /* Silent fail */ }
}

// Can user download?
$can_download = false;
if ($user_logged_in) {
    if (!$material['is_premium']) {
        $can_download = true; // Free item
    } elseif ($user_is_premium) {
        $can_download = true; // Premium user
    }
}

// Helper for Age Label
function getAgeLabelMaterial($age) {
    $labels = [
        '2-3' => '2-3 Нас',
        '3-4' => '3-4 Нас',
        '4-5' => '4-5 Нас',
        'school_prep' => 'Сургуульд бэлтгэх',
        'preschool' => 'Сургуульд бэлтгэх',
        'grade-1' => '1-р анги',
        'all' => 'Бүх нас'
    ];
    return $labels[$age] ?? $age;
}

$preview_pages_count = $material['page_count']; 
?>

<style>
    /* Blurred preview for premium content if not unlocked */
    .premium-blur {
        filter: blur(5px);
        user-select: none;
        pointer-events: none;
    }
    .lock-overlay {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(3px);
    }
    
    /* Paper styling */
    .paper-sheet {
        background: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border: 1px solid #e5e7eb;
        transition: opacity 0.3s ease-in-out;
    }
    
    /* Navigation buttons */
    .slider-btn {
        background-color: rgba(255, 255, 255, 0.9);
        color: #4b5563;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transition: all 0.2s;
    }
    .slider-btn:hover {
        background-color: #33cbcc;
        color: white;
        border-color: #33cbcc;
        transform: scale(1.1);
    }
</style>

<div class="font-kids bg-gray-50 min-h-screen pb-20">

    <!-- Breadcrumb -->
    <div class="bg-white border-b border-gray-200 sticky top-[80px] z-30">
        <div class="container mx-auto px-4 py-3">
            <div class="flex items-center gap-2 text-sm text-gray-500 font-bold">
                <a href="index.php" class="hover:text-[#33cbcc]">Нүүр</a>
                <i class="fas fa-chevron-right text-xs text-gray-300"></i>
                <a href="browse.php?cat=<?php echo htmlspecialchars($material['cat_slug']); ?>" class="hover:text-[#33cbcc] capitalize">
                    <?php echo htmlspecialchars($material['cat_name']); ?>
                </a>
                <i class="fas fa-chevron-right text-xs text-gray-300"></i>
                <span class="text-gray-800 truncate max-w-[200px]"><?php echo htmlspecialchars($material['title']); ?></span>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- LEFT: PREVIEW IMAGE CAROUSEL -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-3xl shadow-lg border border-gray-100 p-4 md:p-8 relative overflow-hidden group">
                    
                    <!-- Carousel Container -->
                    <div class="relative w-full max-w-lg mx-auto aspect-[210/297]">
                        
                        <!-- Slides Wrapper -->
                        <div id="preview-slides" class="w-full h-full relative <?php echo ($material['is_premium'] && !$can_download) ? 'premium-blur' : ''; ?>">
                            
                            <?php 
                            if (!empty($previews)): 
                                foreach ($previews as $index => $img_path):
                                    // Зам засах: kids/ хавтсанд байгаа тул ../ нэмнэ
                                    $img_url = (strpos($img_path, 'uploads/') === 0) ? '../' . $img_path : $img_path;
                                    $z_index = ($index === 0) ? 'z-10' : 'z-0 opacity-0';
                            ?>
                                <div class="preview-slide paper-sheet w-full h-full absolute inset-0 <?php echo $z_index; ?> flex items-center justify-center bg-gray-50 overflow-hidden" data-index="<?php echo $index; ?>">
                                    <?php if(file_exists(__DIR__ . '/../' . $img_path)): ?>
                                        <img src="<?php echo htmlspecialchars($img_url); ?>" alt="Preview <?php echo $index + 1; ?>" class="w-full h-full object-contain">
                                    <?php else: ?>
                                        <div class="text-center p-8">
                                            <i class="<?php echo $material['icon_class']; ?> text-6xl text-gray-300 mb-4"></i>
                                            <p class="text-gray-400 font-bold">Зураг олдсонгүй</p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <span class="absolute top-2 right-4 text-xs font-bold text-gray-400 bg-white/80 px-2 py-1 rounded">
                                        <?php echo ($index === 0) ? 'Ковер' : 'Хуудас ' . ($index); ?>
                                    </span>
                                </div>
                            <?php 
                                endforeach;
                            else: 
                            ?>
                                <!-- Fallback if absolutely no images -->
                                <div class="preview-slide paper-sheet w-full h-full absolute inset-0 z-10 flex flex-col items-center justify-center p-8 bg-[linear-gradient(to_bottom,transparent_19px,#f8f8f8_20px)] bg-[length:100%_20px]" data-index="0">
                                    <i class="<?php echo $material['icon_class']; ?> text-9xl text-gray-200 mb-8 transform scale-110"></i>
                                    <div class="text-gray-400 font-bold">Урьдчилан харах зураг байхгүй</div>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- Controls (Only show if multiple slides) -->
                        <?php if (count($previews) > 1): ?>
                            <button onclick="changeSlide(-1)" class="slider-btn absolute left-2 top-1/2 transform -translate-y-1/2 p-3 rounded-full z-20 focus:outline-none">
                                <i class="fas fa-chevron-left text-lg"></i>
                            </button>
                            <button onclick="changeSlide(1)" class="slider-btn absolute right-2 top-1/2 transform -translate-y-1/2 p-3 rounded-full z-20 focus:outline-none">
                                <i class="fas fa-chevron-right text-lg"></i>
                            </button>
                        <?php endif; ?>

                        <!-- Lock Overlay for Premium -->
                        <?php if ($material['is_premium'] && !$can_download): ?>
                            <div class="absolute inset-0 lock-overlay flex flex-col items-center justify-center text-center p-6 z-30 rounded-lg">
                                <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-500 mb-4 shadow-sm animate-bounce">
                                    <i class="fas fa-lock text-3xl"></i>
                                </div>
                                <h3 class="text-2xl font-extrabold text-gray-800 font-title mb-2">Энэ бол Premium материал</h3>
                                <p class="text-gray-600 mb-6 max-w-md font-medium">Та Premium эрх авснаар энэ болон бусад 500+ материалыг хязгааргүй татах боломжтой.</p>
                                <a href="premium.php" class="bg-gradient-to-r from-yellow-400 to-orange-400 hover:from-yellow-500 hover:to-orange-500 text-white font-bold py-3 px-8 rounded-full shadow-lg transform transition hover:-translate-y-1">
                                    Premium эрх авах
                                </a>
                            </div>
                        <?php endif; ?>

                    </div>
                    
                    <!-- Thumbnails / Dots -->
                    <?php if (count($previews) > 1): ?>
                    <div class="flex justify-center gap-2 mt-6">
                        <?php foreach($previews as $i => $prev): ?>
                            <button onclick="showSlide(<?php echo $i; ?>)" class="nav-dot w-3 h-3 rounded-full <?php echo $i === 0 ? 'bg-[#33cbcc]' : 'bg-gray-300'; ?> hover:bg-[#33cbcc]/50 transition-all"></button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- RIGHT: DETAILS & ACTIONS -->
            <div class="lg:col-span-5">
                <div class="sticky top-32">
                    
                    <!-- Badges -->
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span class="px-3 py-1 bg-<?php echo $material['color_theme']; ?>-100 text-<?php echo $material['color_theme']; ?>-600 rounded-lg text-xs font-bold uppercase tracking-wider shadow-sm">
                            <?php echo htmlspecialchars($material['cat_name']); ?>
                        </span>
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold uppercase tracking-wider shadow-sm">
                            <?php echo getAgeLabelMaterial($material['target_age']); ?>
                        </span>
                        <?php if($material['is_premium']): ?>
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-600 rounded-lg text-xs font-bold uppercase tracking-wider flex items-center gap-1 shadow-sm">
                                <i class="fas fa-crown"></i> Premium
                            </span>
                        <?php else: ?>
                            <span class="px-3 py-1 bg-green-100 text-green-600 rounded-lg text-xs font-bold uppercase tracking-wider shadow-sm">
                                Үнэгүй
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Title -->
                    <h1 class="text-3xl md:text-4xl font-extrabold text-gray-800 font-title mb-4 leading-tight">
                        <?php echo htmlspecialchars($material['title']); ?>
                    </h1>

                    <!-- Stats -->
                    <div class="flex items-center gap-6 text-gray-500 text-sm font-bold mb-6 border-b border-gray-100 pb-6">
                        <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-100">
                            <i class="fas fa-download text-purple-400"></i> <span id="download-count"><?php echo number_format($material['download_count']); ?></span>
                        </div>
                        <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-100">
                            <i class="fas fa-file-alt text-blue-400"></i> <?php echo $material['page_count']; ?> хуудас
                        </div>
                        <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-100">
                            <i class="fas fa-hdd text-gray-400"></i> <?php echo $material['file_size_text']; ?>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 mb-8 shadow-sm">
                        <h3 class="text-gray-800 font-bold mb-2 flex items-center gap-2">
                            <i class="fas fa-info-circle text-[#33cbcc]"></i> Тайлбар
                        </h3>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($material['description'])); ?>
                        </p>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="space-y-3">
                        <?php if ($can_download): ?>
                            <!-- DIRECT Download Button with AJAX Tracking -->
                            <a href="<?php echo htmlspecialchars('../' . $material['file_path']); ?>" 
                               download 
                               onclick="recordDownload(<?php echo $material['id']; ?>)"
                               class="block w-full bg-[#33cbcc] hover:bg-[#2bb5b6] text-white font-bold py-4 rounded-xl shadow-lg shadow-cyan-200 text-center transition transform hover:-translate-y-1 flex items-center justify-center gap-3">
                                <i class="fas fa-cloud-download-alt text-xl"></i>
                                <span>ТАТАЖ АВАХ</span>
                            </a>
                            
                            <!-- Save Button -->
                            <button onclick="toggleSave(<?php echo $material['id']; ?>)" 
                                    id="save-btn" 
                                    class="block w-full bg-white border-2 border-gray-200 text-gray-600 hover:border-pink-200 hover:text-pink-500 font-bold py-3 rounded-xl transition text-center flex items-center justify-center gap-2 group shadow-sm <?php echo $is_saved ? 'text-pink-500 border-pink-200' : ''; ?>">
                                
                                <i class="<?php echo $is_saved ? 'fas' : 'far'; ?> fa-heart group-hover:fas transition-transform group-hover:scale-110 text-xl" id="save-icon"></i>
                                <span id="save-text"><?php echo $is_saved ? 'Хадгалагдсан' : 'Хадгалах'; ?></span>
                            </button>

                        <?php elseif (!$user_logged_in): ?>
                             <!-- Login Required -->
                            <a href="../login.php?redirect=kids/material.php?id=<?php echo $material_id; ?>" class="block w-full bg-gray-800 hover:bg-gray-700 text-white font-bold py-4 rounded-xl shadow-md text-center transition flex items-center justify-center gap-2">
                                <i class="fas fa-sign-in-alt"></i> Нэвтэрч татах
                            </a>
                        <?php else: ?>
                            <!-- Premium Required -->
                            <a href="premium.php" class="block w-full bg-gradient-to-r from-yellow-400 to-orange-400 hover:from-yellow-500 hover:to-orange-500 text-white font-bold py-4 rounded-xl shadow-lg shadow-orange-200 text-center transition transform hover:-translate-y-1 flex items-center justify-center gap-2">
                                <i class="fas fa-crown"></i>
                                <span>Premium эрх авах</span>
                            </a>
                            <div class="bg-yellow-50 text-yellow-700 text-xs p-3 rounded-lg text-center border border-yellow-100 flex items-center justify-center gap-2">
                                <i class="fas fa-lock"></i> Энэ файлыг татахын тулд Premium эрх шаардлагатай.
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>

        <!-- RELATED MATERIALS -->
        <div class="mt-20">
            <h2 class="text-2xl font-extrabold text-gray-800 font-title mb-6 flex items-center gap-2">
                <span class="text-pink-400">★</span> Танд таалагдаж магадгүй
            </h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                <?php 
                // Fetch Related Materials (Same Category, Random 4)
                $stmt_related = $pdo_kids->prepare("
                    SELECT m.*, c.name as cat_name, c.slug as cat_slug, c.color_theme, c.icon_class
                    FROM kids_materials m
                    LEFT JOIN kids_categories c ON m.category_id = c.id
                    WHERE m.status = 'active' 
                    AND m.category_id = :cat_id 
                    AND m.id != :current_id
                    ORDER BY RAND() 
                    LIMIT 4
                ");
                $stmt_related->execute([
                    ':cat_id' => $material['category_id'],
                    ':current_id' => $material_id
                ]);
                $related_items = $stmt_related->fetchAll();

                foreach($related_items as $item): 
                    // Prepare related item vars
                    $r_is_premium = $item['is_premium'];
                    $r_imgSrc = !empty($item['cover_image']) ? '../' . $item['cover_image'] : null;
                    $r_bgClass = 'bg-' . $item['color_theme'] . '-50';
                ?>
                    <a href="material.php?id=<?php echo $item['id']; ?>" class="paper-shadow bg-white rounded-xl overflow-hidden border border-gray-200 group block relative hover:border-<?php echo $item['color_theme']; ?>-300 transition">
                        <?php if($r_is_premium): ?>
                            <div class="absolute top-2 right-2 bg-yellow-400 text-white text-[9px] font-bold px-2 py-0.5 rounded shadow-sm z-10">PREMIUM</div>
                        <?php endif; ?>
                        
                        <div class="aspect-[210/297] bg-gray-50 relative p-4 flex flex-col items-center justify-center border-b border-gray-100">
                             <?php if($r_imgSrc && file_exists(__DIR__ . '/../' . $item['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($r_imgSrc); ?>" class="w-full h-full object-cover shadow-sm border border-gray-100" alt="<?php echo htmlspecialchars($item['title']); ?>">
                             <?php else: ?>
                                <div class="w-full h-full bg-white border border-gray-200 p-2 flex flex-col items-center justify-center <?php echo $r_bgClass; ?>">
                                    <i class="<?php echo $item['icon_class']; ?> text-5xl text-gray-300 opacity-50 group-hover:scale-110 transition duration-300"></i>
                                    <span class="text-[10px] text-gray-400 font-bold mt-2 uppercase"><?php echo htmlspecialchars($item['cat_name']); ?></span>
                                </div>
                             <?php endif; ?>
                        </div>
                        
                        <div class="p-3">
                            <h3 class="font-bold text-gray-800 text-sm mb-1 line-clamp-1 group-hover:text-[#33cbcc] transition"><?php echo htmlspecialchars($item['title']); ?></h3>
                            <div class="flex justify-between items-center mt-2">
                                <span class="text-xs font-bold text-gray-400 uppercase"><?php echo htmlspecialchars($item['cat_name']); ?></span>
                                <?php if(!$r_is_premium): ?>
                                    <span class="text-green-500 font-bold text-xs">Үнэгүй</span>
                                <?php else: ?>
                                    <span class="text-yellow-500 font-bold text-xs"><i class="fas fa-crown"></i></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<script>
    // --- SLIDER LOGIC ---
    let currentSlide = 0;
    const slides = document.querySelectorAll('.preview-slide');
    const dots = document.querySelectorAll('.nav-dot');
    const totalSlides = slides.length;

    function showSlide(index) {
        if (totalSlides === 0) return;
        if (index >= totalSlides) index = 0;
        if (index < 0) index = totalSlides - 1;
        
        currentSlide = index;

        // Hide all slides
        slides.forEach(slide => {
            slide.classList.remove('z-10', 'opacity-100');
            slide.classList.add('z-0', 'opacity-0');
        });

        // Show active slide
        const activeSlide = document.querySelector(`.preview-slide[data-index="${currentSlide}"]`);
        if(activeSlide) {
            activeSlide.classList.remove('z-0', 'opacity-0');
            activeSlide.classList.add('z-10', 'opacity-100');
        }

        // Update dots
        dots.forEach(dot => {
            dot.classList.remove('bg-[#33cbcc]');
            dot.classList.add('bg-gray-300');
        });
        if(dots[currentSlide]) {
            dots[currentSlide].classList.remove('bg-gray-300');
            dots[currentSlide].classList.add('bg-[#33cbcc]');
        }
    }

    function changeSlide(direction) {
        showSlide(currentSlide + direction);
    }

    // --- SAVE LOGIC ---
    function toggleSave(id) {
        // Simple auth check via PHP rendered var
        <?php if(!$user_logged_in): ?>
            window.location.href = '../login.php?redirect=kids/material.php?id=' + id;
            return;
        <?php endif; ?>

        const btn = document.getElementById('save-btn');
        const icon = document.getElementById('save-icon');
        const text = document.getElementById('save-text');

        fetch('ajax_save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'material_id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'saved') {
                icon.classList.remove('far');
                icon.classList.add('fas');
                btn.classList.add('text-pink-500', 'border-pink-200');
                text.textContent = 'Хадгалагдсан';
            } else if (data.status === 'removed') {
                icon.classList.remove('fas');
                icon.classList.add('far');
                btn.classList.remove('text-pink-500', 'border-pink-200');
                text.textContent = 'Хадгалах';
            }
        })
        .catch(err => console.error(err));
    }

    // --- DOWNLOAD TRACKING LOGIC ---
    function recordDownload(id) {
        // Fire and forget request to increment count
        fetch('ajax_download.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'material_id=' + id
        }).then(() => {
            // Optional: Increment counter on UI visually
            const countEl = document.getElementById('download-count');
            if(countEl) {
                let current = parseInt(countEl.innerText.replace(/,/g, ''));
                if(!isNaN(current)) countEl.innerText = (current + 1).toLocaleString();
            }
        });
    }
</script>

<?php include 'footer.php'; ?>