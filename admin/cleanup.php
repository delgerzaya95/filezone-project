<?php
// admin/cleanup.php
// Энэ скрипт нь 24 цагаас дээш хугацаанд хадгалагдсан ТҮР файлуудыг устгана.

$temp_dir = '../uploads/temp/'; // Chunk upload хийдэг түр хавтас

if (!is_dir($temp_dir)) {
    die("Temp folder not found.");
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($temp_dir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);

$count = 0;
$now = time();

echo "<h1>System Cleanup Report</h1>";

foreach ($files as $fileinfo) {
    // 24 цаг = 86400 секунд
    if ($now - $fileinfo->getMTime() >= 86400) {
        if ($fileinfo->isFile()) {
            unlink($fileinfo->getRealPath());
            echo "Deleted file: " . $fileinfo->getRealPath() . "<br>";
            $count++;
        } else {
            // Хоосон хавтаснуудыг бас устгана
            rmdir($fileinfo->getRealPath());
            echo "Deleted folder: " . $fileinfo->getRealPath() . "<br>";
        }
    }
}

echo "<hr>";
echo "<strong>Total cleaned items: $count</strong>";
?>
