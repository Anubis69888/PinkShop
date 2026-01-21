<?php
require_once 'includes/db.php';
$db = new DB();
$products = $db->read('products');

echo "<h1>Debug Image Paths</h1>";
echo "<pre>";
foreach ($products as $p) {
    echo "Product ID: " . $p['id'] . "\n";
    echo "Name: " . $p['name'] . "\n";
    echo "Stored Image: '" . $p['image'] . "'\n";
    
    // Fix slashes
    $fixedPath = str_replace('\\', '/', $p['image']);
    echo "Fixed Path: '" . $fixedPath . "'\n";
    
    // Check file exists
    $absolutePath = __DIR__ . '/' . $fixedPath;
    echo "Absolute Check: " . $absolutePath . "\n";
    echo "File Exists (PHP): " . (file_exists($absolutePath) ? "YES" : "NO") . "\n";
    
    echo "--------------------------------\n";
}
echo "</pre>";
?>
