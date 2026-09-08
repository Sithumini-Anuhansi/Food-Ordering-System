<?php
/**
 * One-time helper: creates a demo account for each role (customer, kitchen,
 * delivery) so you have real, working credentials to test with — without
 * hand-crafting password hashes. Also seeds a handful of demo orders (one
 * in each status: Pending, Preparing, Ready for Delivery, Delivered) for
 * the demo customer, so the kitchen/delivery dashboards, order history,
 * and admin's order list all have real data to demo right away.
 *
 * Deliberately CLI-only: this is NOT reachable over the web, so there's no
 * risk of leaving an account-creation endpoint exposed on a live site.
 *
 * Usage (from the project root):
 *   php data/seed_demo_users.php
 *
 * Safe to re-run — skips any account whose email already exists, and skips
 * the demo orders if the demo customer already has order history.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("This script can only be run from the command line (php data/seed_demo_users.php).\n");
}

require_once __DIR__ . '/../configure.php';

$demo_users = [
    ['role' => 'customer', 'name' => 'Demo Customer', 'email' => 'customer@example.com', 'password' => 'customer123', 'phone' => '0710000001', 'address' => '12 Demo Lane, Colombo'],
    ['role' => 'kitchen',  'name' => 'Demo Kitchen',  'email' => 'kitchen@example.com',  'password' => 'kitchen123',  'phone' => '0710000002', 'address' => 'Kitchen Staff Room'],
    ['role' => 'delivery', 'name' => 'Demo Delivery', 'email' => 'delivery@example.com', 'password' => 'delivery123', 'phone' => '0710000003', 'address' => 'Delivery Bay'],
];

foreach ($demo_users as $u) {
    $check = $conn->prepare("SELECT User_ID FROM users WHERE Email = ?");
    $check->bind_param("s", $u['email']);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if ($exists) {
        echo "Skipped {$u['email']} — already exists.\n";
        continue;
    }

    $hash = password_hash($u['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (Role, Name, Email, Password, Phone_Number, Address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $u['role'], $u['name'], $u['email'], $hash, $u['phone'], $u['address']);

    if ($stmt->execute()) {
        echo "Created {$u['role']} account: {$u['email']} / {$u['password']}\n";
    } else {
        echo "Failed to create {$u['email']}: " . $stmt->error . "\n";
    }
    $stmt->close();
}

/**
 * Demo order history for the demo customer — one order in each status, so
 * the kitchen and delivery dashboards, order history, and admin's order
 * list all have something real to show right after setup.
 */
function get_item($conn, $name)
{
    $stmt = $conn->prepare("SELECT Item_ID, Price FROM food_items WHERE Name = ? LIMIT 1");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row; // ['Item_ID' => ..., 'Price' => ...] or null
}

function create_demo_order($conn, $user_id, $status, $days_ago, $items)
{
    // $items: [ ['name' => ..., 'qty' => ...], ... ]
    $line_items = [];
    $total = 0.0;

    foreach ($items as $it) {
        $food = get_item($conn, $it['name']);
        if (!$food) {
            continue; // seeded menu item not found, skip it
        }
        $line_items[] = ['item_id' => $food['Item_ID'], 'qty' => $it['qty'], 'price' => $food['Price']];
        $total += $food['Price'] * $it['qty'];
    }

    if (empty($line_items)) {
        return null;
    }

    $stmt = $conn->prepare("INSERT INTO orders (User_ID, Total, Status, Order_Time) VALUES (?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))");
    $stmt->bind_param("idsi", $user_id, $total, $status, $days_ago);
    if (!$stmt->execute()) {
        echo "Failed to create demo order: " . $stmt->error . "\n";
        return null;
    }
    $order_id = $stmt->insert_id;
    $stmt->close();

    $item_stmt = $conn->prepare("INSERT INTO order_items (Order_ID, Item_ID, Quantity, Price) VALUES (?, ?, ?, ?)");
    foreach ($line_items as $li) {
        $item_stmt->bind_param("iiid", $order_id, $li['item_id'], $li['qty'], $li['price']);
        $item_stmt->execute();
    }
    $item_stmt->close();

    return $order_id;
}

$customer_row_stmt = $conn->prepare("SELECT User_ID FROM users WHERE Email = ?");
$customer_email = 'customer@example.com';
$customer_row_stmt->bind_param("s", $customer_email);
$customer_row_stmt->execute();
$customer_row = $customer_row_stmt->get_result()->fetch_assoc();
$customer_row_stmt->close();

if ($customer_row) {
    $customer_id = $customer_row['User_ID'];

    $existing = $conn->prepare("SELECT COUNT(*) AS c FROM orders WHERE User_ID = ?");
    $existing->bind_param("i", $customer_id);
    $existing->execute();
    $already_has_orders = (int)$existing->get_result()->fetch_assoc()['c'] > 0;
    $existing->close();

    if ($already_has_orders) {
        echo "Skipped demo orders — demo customer already has order history.\n";
    } else {
        $demo_orders = [
            ['status' => 'Delivered',           'days_ago' => 3, 'items' => [['name' => 'Cheeseburger', 'qty' => 2], ['name' => 'French Fries', 'qty' => 1]]],
            ['status' => 'Delivered',           'days_ago' => 1, 'items' => [['name' => 'Chicken Sandwich', 'qty' => 1], ['name' => 'Onion Rings', 'qty' => 1]]],
            ['status' => 'Ready for Delivery',  'days_ago' => 0, 'items' => [['name' => 'BBQ Pulled Pork Sliders', 'qty' => 2], ['name' => 'Mozzarella Sticks', 'qty' => 1]]],
            ['status' => 'Preparing',           'days_ago' => 0, 'items' => [['name' => 'Spicy Chicken Wings', 'qty' => 1], ['name' => 'Loaded Nachos', 'qty' => 1]]],
            ['status' => 'Pending',             'days_ago' => 0, 'items' => [['name' => 'Fish Fillet Burger', 'qty' => 1], ['name' => 'Chicken Popcorn', 'qty' => 1]]],
        ];

        foreach ($demo_orders as $order) {
            $order_id = create_demo_order($conn, $customer_id, $order['status'], $order['days_ago'], $order['items']);
            if ($order_id) {
                echo "Created demo order #$order_id ({$order['status']}) for the demo customer.\n";
            }
        }
    }
} else {
    echo "Demo customer account not found — skipping demo orders (run this script again after it's created).\n";
}

echo "\nDone. Change these passwords (or delete these accounts) before going anywhere near production.\n";
