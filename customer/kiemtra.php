<?php
require_once __DIR__ . '/../config/db.php';

$ten_db = $conn->query("SELECT DATABASE()")->fetch_row()[0];
echo "<h2>Kết nối thành công tới database: " . htmlspecialchars($ten_db) . "</h2>";

$bang = $conn->query("SHOW TABLES");
while ($b = $bang->fetch_row()) {
    $ten = $b[0];
    $dem = $conn->query("SELECT COUNT(*) FROM `$ten`")->fetch_row()[0];
    echo "<h3>Bảng: " . htmlspecialchars($ten) . " ($dem dòng)</h3><ul>";
    $cot = $conn->query("SHOW COLUMNS FROM `$ten`");
    while ($c = $cot->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($c['Field']) . " (" . htmlspecialchars($c['Type']) . ")</li>";
    }
    echo "</ul>";
}