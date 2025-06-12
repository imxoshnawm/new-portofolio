<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['table']) || !isset($_GET['id'])) {
        throw new Exception('Missing required parameters');
    }

    $table = $_GET['table'];
    $id = (int)$_GET['id'];

    // گۆڕینی ناوی تەیبڵەکان
    $allowedTables = [
        'projects', 
        'certificates', 
        'achievements', 
        'experience', // گرنگە ئەم تەیبڵە زیاد بکرێت
        'reports'
    ];

    // چێک کردنی تەیبڵی دروست
    if (!in_array($table, $allowedTables)) {
        throw new Exception("Invalid table name: $table");
    }

    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        throw new Exception('Item not found');
    }

    echo json_encode($item);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
