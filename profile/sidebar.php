<?php
// profile/sidebar.php

// Одоогийн хуудасны нэрийг тодорхойлох
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}

// User data logic
$sb_username = $username ?? ($_SESSION['username'] ?? 'User');
$sb_email = $email ?? ($_SESSION['email'] ?? '');
$sb_avatar = $avatar ?? "https://ui-avatars.com/api/?name=" . urlencode($sb_username) . "&background=random&color=fff";
$sb_verified = isset($user_data['is_verified']) ? $user_data['is_verified'] : 0;
// Check if user is a seller
$sb_is_seller = (isset($active_files) && $active_files > 0) || (isset($user_data['level']) && $user_data['level'] != 'new');

// User ID for profile link
$sb_user_id = $_SESSION['user_id'] ?? 0;

// Function to determine active class
function getActiveClass($pageName, $currentPage) {
    if ($pageName === $currentPage) {
        return 'bg-blue-50 text-blue-600';
    }
    return 'text-gray-600 hover:bg-gray-100';
}
?>

<aside class="hidden lg:block w-64 flex-shrink-0 py-6 pr-6 h-[calc(100vh-64px)] sticky top-16 overflow-y-auto no-scrollbar">
    <!-- User Info Box -->
    <div class="bg-white border border-gray-200 rounded-xl p-4 mb-6 shadow-sm text-center">
        <div class="relative w-20 h-20 mx-auto mb-3 avatar-group cursor-pointer group">
            <img id="sidebar-avatar" src="<?php echo htmlspecialchars($sb_avatar); ?>" class="w-full h-full rounded-full object-cover border-2 border-brand-100">
            
            <!-- Upload Overlay (Only visible if on dashboard or settings) -->
            <?php if ($current_page == 'dashboard.php' || $current_page == 'settings.php'): ?>
            <label for="avatar-input" class="absolute inset-0 rounded-full flex items-center justify-center bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 cursor-pointer text-white transition-opacity duration-200">
                <i class="fas fa-camera text-xl"></i>
            </label>
            <form id="avatar-form-sidebar" style="display:none;">
                <input type="file" id="avatar-input" name="avatar" accept="image/jpeg, image/png, image/webp" onchange="uploadAvatar(this)">
            </form>
            <?php else: ?>
            <a href="settings.php" class="absolute inset-0 rounded-full flex items-center justify-center bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 cursor-pointer text-white transition-opacity duration-200">
                <i class="fas fa-cog text-xl"></i>
            </a>
            <?php endif; ?>

            <div class="absolute bottom-0 right-0 w-6 h-6 bg-white border border-gray-200 rounded-full flex items-center justify-center text-gray-500 shadow-sm text-xs pointer-events-none">
                <i class="fas fa-camera"></i>
            </div>
        </div>
        
        <h3 class="font-bold text-gray-900 truncate"><?php echo htmlspecialchars($sb_username); ?></h3>
        <p class="text-xs text-gray-500 mb-3 truncate"><?php echo htmlspecialchars($sb_email); ?></p>
        
        <div class="flex justify-center gap-2">
            <?php if($sb_verified): ?>
                <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded font-medium">Verified</span>
            <?php endif; ?>
            <?php if($sb_is_seller): ?>
                <span class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded font-medium">Seller</span>
            <?php endif; ?>
        </div>
        
        <!-- View Profile Button (ЭНД НЭМСЭН) -->
        <a href="../user_profile.php?id=<?php echo $sb_user_id; ?>" class="mt-3 block w-full py-1.5 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition text-center">
            Профайл үзэх
        </a>
    </div>

    <!-- Menu Items -->
    <div class="space-y-1 mb-8">
        <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Миний цэс</h3>
        
        <a href="dashboard.php" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?php echo getActiveClass('dashboard.php', $current_page); ?>">
            <i class="fas fa-chart-pie w-5 text-center"></i> Хяналтын самбар
        </a>
        
        <a href="my_files.php" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?php echo getActiveClass('my_files.php', $current_page); ?>">
            <i class="fas fa-folder w-5 text-center"></i> Миний файлууд
        </a>
        
        <a href="my_services.php" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?php echo getActiveClass('my_services.php', $current_page); ?>">
            <i class="fas fa-briefcase w-5 text-center"></i> Үйлчилгээ
        </a>
        
        <!-- Updated Icon for Orders -->
        <a href="my_orders.php" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?php echo getActiveClass('my_orders.php', $current_page); ?>">
            <i class="fas fa-shopping-bag w-5 text-center"></i> Захиалгууд
        </a>

        <!-- New: Saved Items -->
        <a href="saved.php" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?php echo getActiveClass('saved.php', $current_page); ?>">
            <i class="far fa-heart w-5 text-center"></i> Хадгалсан
        </a>

        <a href="wallet.php" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?php echo getActiveClass('wallet.php', $current_page); ?>">
            <i class="fas fa-wallet w-5 text-center"></i> Хэтэвч & Татах
        </a>
        
        <a href="settings.php" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?php echo getActiveClass('settings.php', $current_page); ?>">
            <i class="fas fa-cog w-5 text-center"></i> Тохиргоо
        </a>
        
        <a href="../logout.php" class="w-full flex items-center gap-3 px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg font-medium transition-colors mt-4">
            <i class="fas fa-sign-out-alt w-5 text-center"></i> Гарах
        </a>
    </div>
</aside>