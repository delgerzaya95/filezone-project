<?php
session_start();
require_once '../includes/db.php';

// Админ эрх шалгах
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --- CONFIGURATION ---
$my_ip = '2405:5700:301:8a67:b4be:c7b0:854:b519'; // Таны IP хаяг (Шүүлтүүрээс хасах)
$hide_bots = isset($_GET['hide_bots']) ? (int)$_GET['hide_bots'] : 1; // 1: Bot нуух, 0: Харуулах

// --- СЕРВЕРИЙН МЭДЭЭЛЭЛ АВАХ ---
function getServerLoad() {
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        return $load[0];
    }
    return 0;
}

function getDiskUsage() {
    $path = "."; 
    $total = disk_total_space($path);
    $free = disk_free_space($path);
    $used = $total - $free;
    
    return [
        'total' => round($total / 1024 / 1024 / 1024, 2) . ' GB',
        'free' => round($free / 1024 / 1024 / 1024, 2) . ' GB',
        'used_percent' => ($total > 0) ? round(($used / $total) * 100, 1) : 0
    ];
}

// Browser & OS parser helper
function parseUserAgent($userAgent) {
    $browser = "Unknown";
    $os = "Unknown";

    if (strpos($userAgent, 'Windows') !== false) $os = 'Windows';
    elseif (strpos($userAgent, 'Mac') !== false) $os = 'MacOS';
    elseif (strpos($userAgent, 'Linux') !== false) $os = 'Linux';
    elseif (strpos($userAgent, 'Android') !== false) $os = 'Android';
    elseif (strpos($userAgent, 'iPhone') !== false) $os = 'iOS';

    if (strpos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
    elseif (strpos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
    elseif (strpos($userAgent, 'Safari') !== false) $browser = 'Safari';
    elseif (strpos($userAgent, 'Edge') !== false) $browser = 'Edge';

    return ['os' => $os, 'browser' => $browser];
}

$server_load = getServerLoad();
$disk_info = getDiskUsage();

// --- SQL FILTER BUILDER ---
// Bot шүүлтүүрийн нөхцөлүүд
$bot_sql_v = "";   // Alias-тай (v.user_agent)
$bot_sql_raw = ""; // Alias-гүй (user_agent)

if ($hide_bots) {
    $bot_keywords = ['bot', 'crawl', 'spider', 'slurp', 'cypex', 'python', 'curl', 'wget', 'zgrab', 'scan', 'checker'];
    foreach ($bot_keywords as $kw) {
        $bot_sql_v .= " AND v.user_agent NOT LIKE '%$kw%' ";
        $bot_sql_raw .= " AND user_agent NOT LIKE '%$kw%' ";
    }
}

// --- ХАНДАЛТЫН СТАТИСТИК ---

try {
    // 1. Өнөөдрийн нийт хандалт
    $sql_hits = "SELECT COUNT(*) FROM visitor_logs WHERE DATE(visited_at) = CURDATE() AND ip_address != ? $bot_sql_raw";
    $stmt = $pdo->prepare($sql_hits);
    $stmt->execute([$my_ip]);
    $today_hits = $stmt->fetchColumn();

    // 2. Өнөөдөр орсон өвөрмөц (Unique) хүмүүс
    $sql_visitors = "SELECT COUNT(DISTINCT ip_address) FROM visitor_logs WHERE DATE(visited_at) = CURDATE() AND ip_address != ? $bot_sql_raw";
    $stmt = $pdo->prepare($sql_visitors);
    $stmt->execute([$my_ip]);
    $today_visitors = $stmt->fetchColumn();

    // 3. Сүүлийн хандалтууд (Last 50)
    $logs_sql = "
        SELECT v.*, u.username, u.avatar_url 
        FROM visitor_logs v 
        LEFT JOIN users u ON v.user_id = u.id 
        WHERE v.ip_address != ? $bot_sql_v
        ORDER BY v.visited_at DESC 
        LIMIT 50
    ";
    $stmt = $pdo->prepare($logs_sql);
    $stmt->execute([$my_ip]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. IP-аар нь бүлэглэж, хамгийн их орсон хүмүүсийг харах (Top 10)
    $top_ips_sql = "
        SELECT ip_address, COUNT(*) as hits, MAX(visited_at) as last_seen, 
               MAX(user_agent) as agent, MAX(user_id) as last_user_id
        FROM visitor_logs 
        WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND ip_address != ? $bot_sql_raw
        GROUP BY ip_address 
        ORDER BY hits DESC 
        LIMIT 10
    ";
    $stmt = $pdo->prepare($top_ips_sql);
    $stmt->execute([$my_ip]);
    $top_ips = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $today_hits = 0; $today_visitors = 0; $logs = []; $top_ips = [];
    $db_error = $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Monitor - FileZone Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="../assets/js/tailwind-config.js"></script>
    <style>
        .animate-pulse-slow { animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Header -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center px-6 shadow-sm justify-between">
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-satellite-dish text-indigo-600"></i> Шууд хяналт
                    </h1>
                    
                    <!-- Bot Filter Toggle -->
                    <a href="monitor.php?hide_bots=<?php echo $hide_bots ? '0' : '1'; ?>" 
                       class="flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold transition
                       <?php echo $hide_bots ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                        <?php if($hide_bots): ?>
                            <i class="fas fa-robot"></i> Bot: Нуусан
                        <?php else: ?>
                            <i class="fas fa-eye"></i> Bot: Харагдаж байна
                        <?php endif; ?>
                    </a>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full" title="My IP: <?php echo $my_ip; ?>">
                        <i class="fas fa-user-shield mr-1"></i> Таны IP шүүгдсэн
                    </div>
                    <a href="monitor.php?hide_bots=<?php echo $hide_bots; ?>" class="text-indigo-600 hover:text-indigo-800 transition" title="Шинэчлэх">
                        <i class="fas fa-sync-alt"></i>
                    </a>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                
                <?php if(isset($db_error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                        <p class="text-red-700 text-sm"><?php echo $db_error; ?></p>
                    </div>
                <?php endif; ?>

                <!-- SERVER HEALTH CARDS -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- CPU Load -->
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">CPU</h3>
                            <i class="fas fa-microchip text-blue-500 text-lg opacity-80"></i>
                        </div>
                        <div class="text-2xl font-bold text-gray-800"><?php echo $server_load; ?>%</div>
                        <div class="w-full bg-gray-100 rounded-full h-1 mt-3 overflow-hidden">
                            <div class="bg-blue-500 h-1 rounded-full" style="width: <?php echo min($server_load * 10, 100); ?>%"></div>
                        </div>
                    </div>

                    <!-- Disk Usage -->
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Disk</h3>
                            <i class="fas fa-hdd text-purple-500 text-lg opacity-80"></i>
                        </div>
                        <div class="text-2xl font-bold text-gray-800"><?php echo $disk_info['used_percent']; ?>%</div>
                        <div class="w-full bg-gray-100 rounded-full h-1 mt-3 overflow-hidden">
                            <div class="bg-purple-500 h-1 rounded-full" style="width: <?php echo $disk_info['used_percent']; ?>%"></div>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1 text-right">Free: <?php echo $disk_info['free']; ?></p>
                    </div>

                    <!-- Today Visitors -->
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Зочид</h3>
                            <i class="fas fa-users text-green-500 text-lg opacity-80"></i>
                        </div>
                        <div class="text-2xl font-bold text-green-600"><?php echo number_format($today_visitors); ?></div>
                        <p class="text-[10px] text-gray-400 mt-1">Давхардаагүй IP</p>
                    </div>

                    <!-- Today Hits -->
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Хандалт</h3>
                            <i class="fas fa-mouse-pointer text-orange-500 text-lg opacity-80"></i>
                        </div>
                        <div class="text-2xl font-bold text-orange-600"><?php echo number_format($today_hits); ?></div>
                        <p class="text-[10px] text-gray-400 mt-1">Нийт үзэлт</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- REAL-TIME LOGS (Detailed) -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-[600px]">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                            <h3 class="font-bold text-gray-800 text-sm">Сүүлийн хандалтууд (Live)</h3>
                            <div class="flex items-center gap-2">
                                <span class="flex h-2 w-2 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                </span>
                                <span class="text-xs text-gray-500">Real-time</span>
                            </div>
                        </div>
                        <div class="overflow-auto custom-scroll flex-1">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50/50 sticky top-0 z-10 backdrop-blur-sm">
                                    <tr>
                                        <th class="px-6 py-3">Хэрэглэгч / IP</th>
                                        <th class="px-6 py-3">Төхөөрөмж</th>
                                        <th class="px-6 py-3">Хуудас</th>
                                        <th class="px-6 py-3 text-right">Цаг</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if(!empty($logs)): ?>
                                        <?php foreach($logs as $log): 
                                            $device = parseUserAgent($log['user_agent']);
                                            $is_bot = (strpos(strtolower($log['user_agent']), 'bot') !== false || strpos(strtolower($log['user_agent']), 'crawl') !== false);
                                        ?>
                                        <tr class="bg-white hover:bg-gray-50 transition group">
                                            <td class="px-6 py-3 whitespace-nowrap">
                                                <div class="flex items-center gap-3">
                                                    <?php if($log['username']): ?>
                                                        <div class="relative">
                                                            <img src="<?php echo !empty($log['avatar_url']) ? '../' . $log['avatar_url'] : 'https://ui-avatars.com/api/?name='.urlencode($log['username']).'&background=random'; ?>" class="w-8 h-8 rounded-full object-cover border border-gray-200">
                                                            <div class="absolute -bottom-1 -right-1 bg-green-500 w-2.5 h-2.5 rounded-full border-2 border-white"></div>
                                                        </div>
                                                        <div>
                                                            <div class="font-bold text-gray-900 text-xs"><?php echo htmlspecialchars($log['username']); ?></div>
                                                            <div class="text-[10px] text-gray-400 font-mono ip-address" data-ip="<?php echo $log['ip_address']; ?>"><?php echo $log['ip_address']; ?></div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                                                            <?php if($is_bot): ?>
                                                                <i class="fas fa-robot text-yellow-600"></i>
                                                            <?php else: ?>
                                                                <i class="fas fa-user-secret"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <div class="font-medium text-gray-600 text-xs"><?php echo $is_bot ? 'Crawler/Bot' : 'Guest'; ?></div>
                                                            <div class="text-[10px] text-gray-400 font-mono ip-address" data-ip="<?php echo $log['ip_address']; ?>"><?php echo $log['ip_address']; ?></div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-[10px] text-blue-500 mt-1 location-info hidden pl-11">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </div>
                                            </td>
                                            <td class="px-6 py-3">
                                                <div class="flex flex-col">
                                                    <span class="text-xs text-gray-700">
                                                        <?php 
                                                            if($is_bot) echo '<span class="bg-yellow-100 text-yellow-800 px-1 rounded text-[10px] mr-1">BOT</span>';
                                                            echo $device['os'] . ' • ' . $device['browser']; 
                                                        ?>
                                                    </span>
                                                    <span class="text-[10px] text-gray-400 truncate max-w-[150px]" title="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                                        <?php echo htmlspecialchars($log['user_agent']); ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-3 truncate max-w-[200px]" title="<?php echo htmlspecialchars($log['request_url']); ?>">
                                                <span class="bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded text-[10px] font-mono mr-1"><?php echo $log['method']; ?></span>
                                                <span class="text-xs text-gray-600"><?php echo htmlspecialchars(basename($log['request_url'])); ?></span>
                                            </td>
                                            <td class="px-6 py-3 text-right text-xs whitespace-nowrap text-gray-400">
                                                <?php echo date('H:i:s', strtotime($log['visited_at'])); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                                <?php echo $hide_bots ? 'Bot нуугдсан байна. Бодит хандалт алга.' : 'Одоогоор хандалт алга.'; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TOP IPs & LOCATION -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Top Active IPs -->
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                                <h3 class="font-bold text-gray-800 text-sm">Идэвхтэй зочид (Top 10)</h3>
                            </div>
                            <div class="p-0 overflow-y-auto max-h-[400px] custom-scroll">
                                <?php if(!empty($top_ips)): ?>
                                    <?php foreach($top_ips as $ip): 
                                        $percent = ($today_hits > 0) ? round(($ip['hits'] / $today_hits) * 100) : 0;
                                        
                                        // Get user info if available for this IP
                                        $user_display = "Guest";
                                        if ($ip['last_user_id']) {
                                            $u_stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                                            $u_stmt->execute([$ip['last_user_id']]);
                                            $u_name = $u_stmt->fetchColumn();
                                            if ($u_name) $user_display = $u_name;
                                        }
                                        
                                        // Simple bot detection for display
                                        $is_top_bot = (strpos(strtolower($ip['agent']), 'bot') !== false || strpos(strtolower($ip['agent']), 'crawl') !== false);
                                    ?>
                                    <div class="border-b border-gray-50 last:border-0 p-4 hover:bg-gray-50 transition">
                                        <div class="flex justify-between items-start mb-1">
                                            <div>
                                                <div class="font-bold text-xs text-gray-700 flex items-center gap-1">
                                                    <?php echo $ip['ip_address']; ?>
                                                    <?php if($user_display !== 'Guest'): ?>
                                                        <span class="bg-indigo-100 text-indigo-700 text-[9px] px-1 rounded"><?php echo $user_display; ?></span>
                                                    <?php elseif($is_top_bot): ?>
                                                        <span class="bg-yellow-100 text-yellow-800 text-[9px] px-1 rounded">BOT</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-[10px] text-gray-400 ip-geo-target" data-ip="<?php echo $ip['ip_address']; ?>">Checking...</div>
                                            </div>
                                            <span class="bg-blue-50 text-blue-600 text-xs font-bold px-2 py-0.5 rounded-full">
                                                <?php echo $ip['hits']; ?>
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-1 mb-1">
                                            <div class="bg-blue-500 h-1 rounded-full" style="width: <?php echo min($percent, 100); ?>%"></div>
                                        </div>
                                        <div class="text-[10px] text-gray-400 flex justify-between">
                                            <span><?php echo date('H:i', strtotime($ip['last_seen'])); ?></span>
                                            <span><?php echo $percent; ?>%</span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-sm text-gray-400 text-center py-4">Мэдээлэл алга.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-xs text-indigo-800">
                            <i class="fas fa-info-circle mr-1"></i>
                            <strong>Tip:</strong> "Bot: Нуусан" горимд байхад зөвхөн бодит хэрэглэгчид (эсвэл шинэ Bot) харагдана.
                        </div>
                    </div>

                </div>

            </main>
        </div>
    </div>

    <!-- Client-side GeoIP Lookup Script (Optimized) -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ipsToLookup = new Set();
            const CACHE_KEY = 'geoip_cache';
            
            // Get Cache
            let geoCache = {};
            try { geoCache = JSON.parse(localStorage.getItem(CACHE_KEY)) || {}; } catch(e) {}

            // Helper to update UI
            const updateUI = (ip, locationText) => {
                const icon = locationText.includes('Private') ? 'fa-network-wired' : 'fa-map-marker-alt';
                
                document.querySelectorAll(`.ip-geo-target[data-ip="${ip}"]`).forEach(el => {
                    el.innerHTML = `<i class="fas ${icon} mr-1"></i> ${locationText}`;
                });

                document.querySelectorAll(`.ip-address[data-ip="${ip}"]`).forEach(el => {
                    const container = el.closest('td').querySelector('.location-info');
                    if(container) {
                        container.innerHTML = `<i class="fas ${icon} mr-1"></i> ${locationText}`;
                        container.classList.remove('hidden');
                    }
                });
            };

            // Collect IPs
            document.querySelectorAll('.ip-geo-target, .ip-address').forEach(el => {
                const ip = el.getAttribute('data-ip');
                if (geoCache[ip]) {
                    updateUI(ip, geoCache[ip]);
                } else {
                    ipsToLookup.add(ip);
                }
            });

            // Process IPs with Queue
            const ipArray = Array.from(ipsToLookup);
            let index = 0;

            function lookupNextIp() {
                if (index >= ipArray.length) return;
                
                const ip = ipArray[index];
                
                // Fetch with timeout logic
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 3000); // 3 sec timeout

                fetch(`http://ip-api.com/json/${ip}?fields=status,country,city`, { signal: controller.signal })
                    .then(response => response.json())
                    .then(data => {
                        clearTimeout(timeoutId);
                        let loc = 'Unknown';
                        if (data.status === 'success') {
                            loc = `${data.city}, ${data.country}`;
                        } else {
                            loc = 'Private/Local';
                        }
                        
                        // Cache & Update
                        geoCache[ip] = loc;
                        localStorage.setItem(CACHE_KEY, JSON.stringify(geoCache));
                        updateUI(ip, loc);
                    })
                    .catch(err => {
                        console.log('GeoIP Skip:', ip);
                        // Don't cache errors effectively, just skip for UI
                    })
                    .finally(() => {
                        index++;
                        setTimeout(lookupNextIp, 1500); // Delay to avoid API block
                    });
            }

            if(ipArray.length > 0) lookupNextIp();
        });
    </script>
</body>
</html>