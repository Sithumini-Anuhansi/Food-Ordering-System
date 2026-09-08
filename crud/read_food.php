<?php
include '../configure.php';

header('Content-Type: application/json');

if (!isset($conn) || !$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

$query = "SELECT * FROM food_items";
$result = mysqli_query($conn, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed.']);
    exit();
}

$food_list = [];
while ($row = mysqli_fetch_assoc($result)) {
    $food_list[] = $row;
}

echo json_encode($food_list, JSON_UNESCAPED_UNICODE);
?>