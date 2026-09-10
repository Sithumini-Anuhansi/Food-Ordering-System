<?php
require_once __DIR__ . '/mailer.php';

if (!function_exists('notify_order_status_change')) {
    /** Never let a notification failure break the status update itself. */
    function notify_order_status_change($conn, $order_id, $new_status)
    {
        try {
            $stmt = $conn->prepare(
                "SELECT u.Email, u.Name FROM orders o
                 JOIN users u ON o.User_ID = u.User_ID
                 WHERE o.ID = ?"
            );
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $customer = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$customer) return;

            $friendly = [
                'Preparing'          => "Your order is now being prepared.",
                'Ready for Delivery' => "Your order is ready and will be out for delivery shortly.",
                'Delivered'          => "Your order has been delivered. Enjoy your meal!",
                'Cancelled'          => "Your order has been cancelled.",
            ];
            $message = $friendly[$new_status] ?? ("Your order status is now: " . $new_status);

            $body = "<p>Hi " . htmlspecialchars($customer['Name']) . ",</p>"
                . "<p>$message</p>"
                . "<p>Order #$order_id</p>";

            send_email($customer['Email'], "Order #$order_id update: $new_status", $body);
        } catch (\Throwable $e) {
            error_log("Order status notification failed for order #$order_id: " . $e->getMessage());
        }
    }
}
