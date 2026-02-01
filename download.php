<?php
// Энэ файл нь ямар ч HTML код агуулах ёсгүй. Зөвхөн файл татах үйлдэл хийнэ.

// 1. Session & DB
session_start();
require_once 'includes/db.php';

// Туслах функц: MIME төрөл тодорхойлох (mime_content_type байхгүй үед ашиглана)
function get_mime_type($filename) {
    $idx = explode('.', $filename);
    $count_explode = count($idx);
    $idx = strtolower($idx[$count_explode-1]);

    $mimetypes = array( 
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',

        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );

    if (isset($mimetypes[$idx])) {
        return $mimetypes[$idx];
    } else {
        return 'application/octet-stream';
    }
}

// 2. ID шалгах
if (!isset($_GET['file_id']) || empty($_GET['file_id'])) {
    die("Файлын ID олдсонгүй.");
}

$file_id = intval($_GET['file_id']);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

try {
    // 3. Файлын мэдээлэл авах
    $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ?");
    $stmt->execute([$file_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        die("Файл олдсонгүй.");
    }

    // 4. ТАТАХ ЭРХ ШАЛГАХ (Access Control)
    $can_download = false;

    // A. Үнэгүй файл
    if ($file['price'] <= 0) {
        $can_download = true;
    }
    // B. Өөрийнх нь файл
    elseif ($user_id && $file['user_id'] == $user_id) {
        $can_download = true;
    }
    // C. Админ
    elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $can_download = true;
    }
    // D. Худалдаж авсан эсэх
    elseif ($user_id) {
        $stmt_check = $pdo->prepare("SELECT id FROM transactions WHERE user_id = ? AND file_id = ? AND status = 'success'");
        $stmt_check->execute([$user_id, $file_id]);
        if ($stmt_check->rowCount() > 0) {
            $can_download = true;
        }
    }

    if (!$can_download) {
        // Эрхгүй бол file-details хуудас руу буцаана
        header("Location: file-details.php?id=" . $file_id);
        exit;
    }

    // 5. ТАТАЛТЫН ТООГ НЭМЭХ
    $update_stmt = $pdo->prepare("UPDATE files SET download_count = download_count + 1 WHERE id = ?");
    $update_stmt->execute([$file_id]);

    // 6. ФАЙЛ ТАТУУЛАХ ЛОГИК

    // Хэрэв Гадаад холбоос (External Link) байвал тийш нь үсэргэнэ
    if (isset($file['is_external']) && $file['is_external'] == 1 && !empty($file['external_link'])) {
        header("Location: " . $file['external_link']);
        exit;
    }

    // Хэрэв Дотоод файл (Local File) байвал
    $filepath = $file['file_url']; // Жишээ нь: uploads/files/22/110/3.1.docx

    // Файл байгаа эсэхийг шалгах
    if (file_exists($filepath)) {
        // MIME төрлийг тодорхойлох
        if (function_exists('mime_content_type')) {
            $mime_type = mime_content_type($filepath);
        } else {
            $mime_type = get_mime_type($filepath);
        }
        
        // Файлын нэрийг цэвэрлэх
        $filename = basename($filepath);

        // Headers тохируулах (Force Download)
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        
        // Buffer цэвэрлэх
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        flush();
        readfile($filepath);
        exit;
    } else {
        die("Уучлаарай, файл сервер дээрээс олдсонгүй (Path: $filepath).");
    }

} catch (Exception $e) {
    die("Системийн алдаа: " . $e->getMessage());
}
?>