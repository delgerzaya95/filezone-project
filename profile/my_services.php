<?php
// profile/my_services.php
session_start();

// 1. Include paths (Correct relative path)
require_once '../includes/db.php';

// Check DB Connection
if (!isset($conn)) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- User Info ---
$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$username = $user_data['username'] ?? 'User';
$email = $user_data['email'] ?? '';
$user_bio = $user_data['bio'] ?? '';

// --- Fetch Skills from user_skills table ---
$skills_array = [];
// Хэрэв user_skills хүснэгт үүсээгүй бол алдаа өгөхөөс сэргийлж шалгах (Optional but safer)
$check_skills_table = $conn->query("SHOW TABLES LIKE 'user_skills'");
if ($check_skills_table && $check_skills_table->num_rows > 0) {
    $sql_skills = "SELECT skill_name as name, skill_level as level FROM user_skills WHERE user_id = ?";
    $stmt_skills = $conn->prepare($sql_skills);
    $stmt_skills->bind_param("i", $user_id);
    $stmt_skills->execute();
    $res_skills = $stmt_skills->get_result();

    while ($row = $res_skills->fetch_assoc()) {
        $skills_array[] = $row;
    }
} else {
    // Хэрэв user_skills хүснэгт байхгүй бол хуучин json баганаас авах (Backup logic)
    $legacy_skills = $user_data['skills'] ?? '';
    $decoded = json_decode($legacy_skills, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $skills_array = $decoded;
    }
}

// Avatar Logic (Fixing paths)
$db_avatar = $user_data['avatar_url'];
// Default avatar
$avatar = "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=random&color=fff";

if (!empty($db_avatar)) {
    if (strpos($db_avatar, 'http') === 0) {
        $avatar = $db_avatar;
    } else {
        if (file_exists('../' . $db_avatar)) {
            $avatar = '../' . $db_avatar;
        }
    }
}

// --- Fetch Services ---
$sql_services = "SELECT * FROM services WHERE user_id = ? AND status != 'deleted' ORDER BY created_at DESC";
$stmt = $conn->prepare($sql_services);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$services_result = $stmt->get_result();

// --- Helpers ---
function getServiceStatusBadge($status) {
    switch($status) {
        case 'active': return '<span class="px-2 py-1 text-[10px] font-bold uppercase bg-green-100 text-green-700 rounded-full">Идэвхтэй</span>';
        case 'paused': return '<span class="px-2 py-1 text-[10px] font-bold uppercase bg-gray-100 text-gray-700 rounded-full">Зогсоосон</span>';
        case 'pending': return '<span class="px-2 py-1 text-[10px] font-bold uppercase bg-yellow-100 text-yellow-700 rounded-full">Хүлээгдэж буй</span>';
        case 'rejected': return '<span class="px-2 py-1 text-[10px] font-bold uppercase bg-red-100 text-red-700 rounded-full">Татгалзсан</span>';
        default: return '<span class="px-2 py-1 text-[10px] font-bold uppercase bg-gray-100 text-gray-500 rounded-full">' . htmlspecialchars($status) . '</span>';
    }
}

function formatDeliveryTime($time, $unit) {
    $unit_map = ['hour' => 'цаг', 'day' => 'өдөр', 'week' => 'долоо хоног', 'month' => 'сар'];
    $u = $unit_map[$unit] ?? $unit;
    return $time . ' ' . $u;
}

$pageTitle = "Миний үйлчилгээнүүд";
$current_page = 'my_services.php';

include 'header.php';
?>

<!-- Styles -->
<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .fade-in { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    /* Advanced Skills Styles */
    .skill-level-badge {
        font-size: 0.65rem;
        padding: 2px 6px;
        border-radius: 4px;
        margin-left: 6px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .level-beginner { background-color: #d1fae5; color: #065f46; }
    .level-intermediate { background-color: #dbeafe; color: #1e40af; }
    .level-expert { background-color: #fae8ff; color: #86198f; }
</style>

<div class="flex flex-1 max-w-7xl mx-auto w-full">
    
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 lg:px-0 min-w-0">
        
        <!-- Header Actions -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Миний үйлчилгээнүүд</h1>
                <p class="text-sm text-gray-500 mt-1">Таны нийтэлсэн болон идэвхтэй үйлчилгээнүүд</p>
            </div>
            <div class="flex gap-3 w-full sm:w-auto">
                <button onclick="openSkillsModal()" class="flex-1 sm:flex-none bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center justify-center gap-2 group">
                    <div class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center group-hover:bg-purple-100 transition">
                        <i class="fas fa-magic text-purple-500"></i>
                    </div>
                    <span>Ур чадвар & Танилцуулга</span>
                </button>
                <a href="../add_service.php" class="flex-1 sm:flex-none bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center justify-center gap-2">
                    <i class="fas fa-plus"></i> Үйлчилгээ нэмэх
                </a>
            </div>
        </div>

        <!-- Skills Preview Section -->
        <?php if(!empty($skills_array) || !empty($user_bio)): ?>
        <div class="bg-white border border-gray-200 rounded-xl p-6 mb-8 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Bio -->
                <div class="md:col-span-2">
                    <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                        <i class="far fa-user text-gray-400"></i> Товч танилцуулга
                    </h4>
                    <div class="text-sm text-gray-600 leading-relaxed bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <?php echo !empty($user_bio) ? nl2br(htmlspecialchars($user_bio)) : '<span class="text-gray-400 italic">Танилцуулга хоосон байна.</span>'; ?>
                    </div>
                </div>
                
                <!-- Skills -->
                <div>
                    <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                        <i class="fas fa-tools text-gray-400"></i> Ур чадвар
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach($skills_array as $skill): 
                            $level_class = 'level-' . strtolower($skill['level']);
                        ?>
                            <div class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-200 bg-white shadow-sm text-sm text-gray-700">
                                <span><?php echo htmlspecialchars($skill['name']); ?></span>
                                <span class="skill-level-badge <?php echo $level_class; ?>"><?php echo htmlspecialchars($skill['level']); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($skills_array)): ?>
                            <span class="text-gray-400 text-sm italic">Ур чадвар оруулаагүй байна.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Services List -->
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <?php if ($services_result && $services_result->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3">Үйлчилгээ</th>
                            <th class="px-6 py-3">Үнэ</th>
                            <th class="px-6 py-3">Хугацаа</th>
                            <th class="px-6 py-3">Статус</th>
                            <th class="px-6 py-3">Үнэлгээ</th>
                            <th class="px-6 py-3 text-right">Үйлдэл</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php while($service = $services_result->fetch_assoc()): 
                            $cover_img = 'https://placehold.co/150x150?text=No+Image';
                            if (!empty($service['cover_image'])) {
                                if (strpos($service['cover_image'], 'http') === 0) {
                                    $cover_img = $service['cover_image'];
                                } else {
                                    $local_path = '../' . $service['cover_image'];
                                    if (file_exists($local_path)) {
                                        $cover_img = $local_path;
                                    }
                                }
                            }
                            $service_url = '../service-details.php?id=' . $service['id'];
                        ?>
                        <tr class="bg-white hover:bg-gray-50 transition group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <a href="<?php echo $service_url; ?>" class="w-16 h-12 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden border border-gray-200 block">
                                        <img src="<?php echo htmlspecialchars($cover_img); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    </a>
                                    <div class="min-w-0 max-w-xs">
                                        <a href="<?php echo $service_url; ?>" class="text-sm font-medium text-gray-900 truncate block hover:text-blue-600 transition" title="<?php echo htmlspecialchars($service['title']); ?>">
                                            <?php echo htmlspecialchars($service['title']); ?>
                                        </a>
                                        <span class="text-xs text-gray-500"><i class="far fa-eye mr-1"></i> <?php echo $service['view_count']; ?> үзсэн</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900">
                                <?php echo number_format($service['price_min']); ?>₮
                                <?php if($service['price_max']): ?> - <?php echo number_format($service['price_max']); ?>₮<?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-600">
                                <i class="far fa-clock mr-1"></i> <?php echo formatDeliveryTime($service['delivery_time'], $service['delivery_unit']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo getServiceStatusBadge($service['status']); ?>
                                <?php if($service['status'] == 'rejected' && !empty($service['rejection_reason'])): ?>
                                    <div class="text-[10px] text-red-500 mt-1 max-w-[150px] truncate" title="<?php echo htmlspecialchars($service['rejection_reason']); ?>">
                                        <?php echo htmlspecialchars($service['rejection_reason']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center text-yellow-400 text-xs">
                                    <i class="fas fa-star"></i>
                                    <span class="ml-1 text-gray-600 font-medium"><?php echo $service['rating_avg']; ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?php echo $service_url; ?>" class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition" title="Харах"><i class="fas fa-external-link-alt"></i></a>
                                    <a href="edit_service.php?id=<?php echo $service['id']; ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Засах"><i class="fas fa-edit"></i></a>
                                    <a href="delete_service.php?id=<?php echo $service['id']; ?>" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Устгах" onclick="return confirm('Та энэ үйлчилгээг устгахдаа итгэлтэй байна уу?');"><i class="fas fa-trash-alt"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl"><i class="fas fa-briefcase"></i></div>
                    <h3 class="text-lg font-medium text-gray-900">Үйлчилгээ бүртгүүлээгүй байна</h3>
                    <p class="text-gray-500 text-sm mt-1 mb-6">Та өөрийн ур чадвараа ашиглан бусдад үйлчилгээ үзүүлэх боломжтой.</p>
                    <a href="../add_service.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Үйлчилгээ нэмэх
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Advanced Skills Modal -->
<div id="skillsModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm transition-all duration-300">
    <div class="bg-white w-11/12 md:max-w-2xl mx-auto rounded-2xl shadow-2xl z-50 overflow-hidden transform scale-95 transition-transform duration-300" id="modalContent">
        
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <div>
                <h3 class="text-xl font-bold text-gray-900">Профайл засварлах</h3>
                <p class="text-xs text-gray-500">Ур чадвар болон танилцуулгаа шинэчлэх</p>
            </div>
            <button onclick="closeSkillsModal()" class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="p-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
            <form id="skillsForm" class="space-y-6">
                <input type="hidden" name="action" value="update_skills">
                
                <!-- 1. Skills Section -->
                <div>
                    <label class="block text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                        <i class="fas fa-award text-purple-500"></i> Ур чадвар (Skills)
                    </label>
                    
                    <!-- Input Area -->
                    <div class="flex gap-2 mb-3">
                        <input type="text" id="skillInputName" placeholder="Жнь: Photoshop, Translation..." class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none">
                        <select id="skillInputLevel" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-purple-500 outline-none">
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate" selected>Intermediate</option>
                            <option value="Expert">Expert</option>
                        </select>
                        <button type="button" onclick="addSkill()" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 transition">Нэмэх</button>
                    </div>

                    <!-- List Area -->
                    <div id="skillsList" class="space-y-2 mb-2">
                        <!-- Skills will be rendered here via JS -->
                    </div>
                    
                    <!-- Hidden JSON Input -->
                    <input type="hidden" name="skills_json" id="skillsHiddenJSON">
                </div>

                <hr class="border-gray-100">

                <!-- 2. Bio Section -->
                <div>
                    <label class="block text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                        <i class="fas fa-align-left text-blue-500"></i> Товч танилцуулга
                    </label>
                    <div class="relative">
                        <textarea name="bio" rows="6" class="w-full border border-gray-300 rounded-lg p-4 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none leading-relaxed" placeholder="Өөрийн туршлага, ажлын түүх, давуу талуудын талаар дэлгэрэнгүй бичнэ үү..."><?php echo htmlspecialchars($user_bio); ?></textarea>
                        <div class="absolute bottom-2 right-2 text-xs text-gray-400 bg-white px-1">Макс 1000 тэмдэгт</div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                        <i class="fas fa-info-circle"></i> Энэ хэсэг таны профайл хуудас дээр харагдана.
                    </p>
                </div>
            </form>
        </div>

        <!-- Footer Actions -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
            <button type="button" onclick="closeSkillsModal()" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition shadow-sm">Болих</button>
            <button type="button" onclick="submitSkills()" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition shadow-lg shadow-blue-500/30 flex items-center gap-2">
                <span>Хадгалах</span>
                <i class="fas fa-check"></i>
            </button>
        </div>
    </div>
</div>

<script>
    // --- Data Initialization ---
    // PHP-ээс ирсэн array-г JS руу дамжуулах
    let skillsData = <?php echo json_encode($skills_array); ?>; 

    // --- DOM Elements ---
    const skillsListEl = document.getElementById('skillsList');
    const skillInputName = document.getElementById('skillInputName');
    const skillInputLevel = document.getElementById('skillInputLevel');
    const skillsHiddenJSON = document.getElementById('skillsHiddenJSON');

    // --- Functions ---
    function renderSkills() {
        skillsListEl.innerHTML = '';
        if (skillsData.length === 0) {
            skillsListEl.innerHTML = '<div class="text-center py-4 text-gray-400 text-sm italic border-2 border-dashed border-gray-200 rounded-lg">Одоогоор ур чадвар нэмээгүй байна.</div>';
        } else {
            skillsData.forEach((skill, index) => {
                const levelClass = {
                    'Beginner': 'bg-green-100 text-green-700 border-green-200',
                    'Intermediate': 'bg-blue-100 text-blue-700 border-blue-200',
                    'Expert': 'bg-purple-100 text-purple-700 border-purple-200'
                }[skill.level] || 'bg-gray-100 text-gray-700';

                const div = document.createElement('div');
                div.className = 'flex justify-between items-center p-3 bg-white border border-gray-200 rounded-lg shadow-sm group hover:border-blue-300 transition';
                div.innerHTML = `
                    <div class="flex items-center gap-3">
                        <span class="font-medium text-gray-800 text-sm">${skill.name}</span>
                        <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded border ${levelClass}">${skill.level}</span>
                    </div>
                    <button type="button" onclick="removeSkill(${index})" class="text-gray-400 hover:text-red-500 p-1 rounded-full hover:bg-red-50 transition opacity-0 group-hover:opacity-100 focus:opacity-100">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                `;
                skillsListEl.appendChild(div);
            });
        }
        // Update hidden input for form submission
        skillsHiddenJSON.value = JSON.stringify(skillsData);
    }

    function addSkill() {
        const name = skillInputName.value.trim();
        const level = skillInputLevel.value;

        if (name) {
            // Check duplicates
            const exists = skillsData.some(s => s.name.toLowerCase() === name.toLowerCase());
            if (exists) {
                alert('Энэ ур чадвар бүртгэгдсэн байна.');
                return;
            }

            skillsData.push({ name: name, level: level });
            renderSkills();
            skillInputName.value = '';
            skillInputName.focus();
        }
    }

    window.removeSkill = function(index) {
        skillsData.splice(index, 1);
        renderSkills();
    }

    // Input Enter Key Support
    skillInputName.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addSkill();
        }
    });

    // --- Modal Logic ---
    function openSkillsModal() {
        document.getElementById('skillsModal').classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('modalContent').classList.remove('scale-95');
            document.getElementById('modalContent').classList.add('scale-100');
        }, 10);
        renderSkills();
    }

    function closeSkillsModal() {
        document.getElementById('modalContent').classList.remove('scale-100');
        document.getElementById('modalContent').classList.add('scale-95');
        setTimeout(() => {
            document.getElementById('skillsModal').classList.add('hidden');
        }, 200);
    }

    // --- AJAX Submit ---
    function submitSkills() {
        const form = document.getElementById('skillsForm');
        const formData = new FormData(form);
        
        const btn = document.querySelector('button[onclick="submitSkills()"]');
        const originalContent = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Хадгалж байна...';

        fetch('update_skills.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Server Raw Response:", text); // Консол дээр алдааг харах
                    throw new Error("Серверээс буруу хариу ирлээ. (JSON Error)");
                }
            });
        })
        .then(data => {
            if (data.success) {
                btn.innerHTML = '<i class="fas fa-check"></i> Амжилттай!';
                btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                btn.classList.add('bg-green-600', 'hover:bg-green-700');
                
                setTimeout(() => {
                    location.reload(); 
                }, 800);
            } else {
                alert('Алдаа: ' + data.message);
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Сүлжээний алдаа гарлаа: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = originalContent;
        });
    }

    // Initial Render
    renderSkills();
</script>

<?php include '../includes/footer.php'; ?>