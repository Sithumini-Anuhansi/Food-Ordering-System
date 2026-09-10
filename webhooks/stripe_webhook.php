<?php
/**
 * Public webhook endpoint — Stripe's servers POST here directly, with no
 * session/cookie, so this deliberately does NOT go through session_check.php.
 * Security instead comes entirely from verifying the Stripe-Signature
 * header against STRIPE_WEBHOOK_SECRET (see includes/stripe.php).
 *
 * Configure this URL in the Stripe dashboard (or via the Stripe CLI for
 * local testing): https://yourdomain.com/webhooks/stripe_webhook.php
 */
require_once __DIR__ . '/../configure.php';
require_once __DIR__ . '/../includes/stripe.php';

$payload = file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$webhook_secret = env('STRIPE_WEBHOOK_SECRET', '');

if (!stripe_verify_webhook_signature($payload, $sig_header, $webhook_secret)) {
    http_response_code(400);
    error_log("Stripe webhook: signature verification failed");
    echo json_encode(['error' => 'Invalid signature']);
    exit();
}

$event = json_decode($payload, true);
if (!$event || !isset($event['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Malformed payload']);
    exit();
}

if ($event['type'] === 'checkout.session.completed') {
    $session = $event['data']['object'] ?? [];
    $order_id = intval($session['metadata']['order_id'] ?? 0);
    $payment_status = ($session['payment_status'] ?? '') === 'paid' ? 'Paid' : 'Unpaid';

    if ($order_id > 0) {
        $stmt = $conn->prepare("UPDATE orders SET Payment_Status = ? WHERE ID = ? AND Stripe_Session_ID = ?");
        $stmt->bind_param("sis", $payment_status, $order_id, $session['id']);
        $stmt->execute();
        $stmt->close();
        error_log("Stripe webhook: order #$order_id marked $payment_status");
    }
}

http_response_code(200);
echo json_encode(['received' => true]);
