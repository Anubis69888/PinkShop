<?php
require_once 'includes/db.php';
$db = new DB();
$allProducts = $db->read('products');

echo "<h1>Debug Product Images</h1>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Name</th><th>Image Path</th><th>File Exists?</th><th>Preview</th></tr>";

foreach ($allProducts as $product) {
    $imgPath = $product['image'] ?? '';
    $fullPath = __DIR__ . '/' . $imgPath;
    $exists = file_exists($fullPath) ? '✅ YES' : '❌ NO';
    
    echo "<tr>";
    echo "<td>{$product['id']}</td>";
    echo "<td>" . htmlspecialchars($product['name']) . "</td>";
    echo "<td><code>" . htmlspecialchars($imgPath) . "</code></td>";
    echo "<td>{$exists}</td>";
    echo "<td><img src='" . htmlspecialchars($imgPath) . "' style='max-width: 100px; max-height: 100px;' onerror=\"this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22><rect fill=%22%23f0f0f0%22 width=%22100%22 height=%22100%22/><text x=%2250%22 y=%2250%22 text-anchor=%22middle%22>No Image</text></svg>'\"></td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Raw Image Paths from JSON:</h2>";
echo "<pre>";
foreach ($allProducts as $product) {
    echo "ID {$product['id']}: " . var_export($product['image'] ?? 'N/A', true) . "\n";
}
echo "</pre>";
?>
