<?php
require_once '../includes/AISearch.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (empty($query)) {
    echo json_encode([]);
    exit;
}

$aiSearch = new AISearch();
$results = $aiSearch->search($query);

echo json_encode([
    'success' => true,
    'results' => $results,
    'count' => count($results)
]);
?>
