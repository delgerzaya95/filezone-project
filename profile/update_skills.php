<?php
// profile/update_skills.php
session_start();

// JSON толгой хэсгийг зарлах (Хөтөч үүнийг JSON гэж ойлгох ёстой)
header('Content-Type: application/json; charset=utf-8');

// Гаралтыг буферт хадгалах (PHP Warning, Notice зэргийг JSON руу оруулахгүйн тулд)
ob_start();

$response = ['success' => false, 'message' => 'Тодорхойгүй алдаа'];

try {
    // 1. Database Connection
    // Зам нь таны фолдерийн бүтцээс хамаарна. my_services.php дээрхтэй ижил замыг ашиглав.
    require_once '../includes/db.php';

    // DB Connection Check
    if (!isset($conn)) {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
    }

    // 2. Authentication Check
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Нэвтрэх шаардлагатай.");
    }

    $user_id = $_SESSION['user_id'];

    // 3. Request Method Check
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Зөвхөн POST хүсэлт хүлээн авна.");
    }

    // 4. Data Processing
    $action = $_POST['action'] ?? '';

    if ($action === 'update_skills') {
        
        // --- A. Update Bio ---
        if (isset($_POST['bio'])) {
            $bio = trim($_POST['bio']);
            // HTML халдлагаас сэргийлэх (Optional: strip_tags эсвэл htmlspecialchars)
            // $bio = htmlspecialchars($bio); 
            
            $sql_bio = "UPDATE users SET bio = ? WHERE id = ?";
            $stmt_bio = $conn->prepare($sql_bio);
            $stmt_bio->bind_param("si", $bio, $user_id);
            if (!$stmt_bio->execute()) {
                throw new Exception("Танилцуулга хадгалахад алдаа гарлаа.");
            }
        }

        // --- B. Update Skills ---
        if (isset($_POST['skills_json'])) {
            $skills_json = $_POST['skills_json'];
            $skills_array = json_decode($skills_json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON өгөгдөл буруу байна.");
            }

            // Transaction эхлүүлэх (Бүгд амжилттай болвол хадгална, үгүй бол буцаана)
            $conn->begin_transaction();

            try {
                // 1. Хуучин ур чадваруудыг устгах (Шинээр бүгдийг нь нэмнэ)
                // Хэрэв user_skills хүснэгт байхгүй бол энд SQL алдаа гарна.
                $sql_delete = "DELETE FROM user_skills WHERE user_id = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bind_param("i", $user_id);
                $stmt_delete->execute();

                // 2. Шинэ ур чадваруудыг нэмэх
                if (!empty($skills_array)) {
                    $sql_insert = "INSERT INTO user_skills (user_id, skill_name, skill_level) VALUES (?, ?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);

                    foreach ($skills_array as $skill) {
                        $name = trim($skill['name']);
                        $level = $skill['level']; // Beginner, Intermediate, Expert

                        if (!empty($name)) {
                            $stmt_insert->bind_param("iss", $user_id, $name, $level);
                            $stmt_insert->execute();
                        }
                    }
                }

                // Бүх зүйл болсон тул баазад бичих
                $conn->commit();

            } catch (Exception $e) {
                // Алдаа гарвал буцаах
                $conn->rollback();
                throw new Exception("Ур чадвар хадгалахад алдаа гарлаа: " . $e->getMessage());
            }
        }

        $response['success'] = true;
        $response['message'] = 'Амжилттай хадгалагдлаа.';
    } else {
        throw new Exception("Буруу үйлдэл (Invalid Action).");
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    // Debug зорилгоор алдааг log руу бичиж болно
    error_log("Update Skills Error: " . $e->getMessage());
}

// Буферийг цэвэрлэх (Өмнө нь ямар нэгэн echo, whitespace байсан бол устгана)
ob_end_clean();

// JSON болгож буцаах
echo json_encode($response);
exit();
?>