<?php
include '../configure.php';

header('Content-Type: application/json');

$query = "SELECT * FROM food_items WHERE available = 1";
$result = mysqli_query($conn, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed.']);
    exit;
}

$menu = [];
while ($row = mysqli_fetch_assoc($result)) {
    $menu[] = $row;
}

echo json_encode($menu, JSON_UNESCAPED_UNICODE);
?>