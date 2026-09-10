<?php
/**
 * Minimal Stripe integration using Stripe's plain REST API over cURL —
 * no Composer/Stripe PHP SDK dependency. Covers exactly what this app
 * needs: creating a Checkout Session, and verifying webhook signatures.
 *
 * Requires STRIPE_SECRET_KEY in .env. Get free test-mode keys (no real
 * business/bank account needed) from:
 * https://dashboard.stripe.com/test/apikeys
 */

if (!function_exists('stripe_is_configured')) {
    function stripe_is_configured()
    {
        return env('STRIPE_SECRET_KEY', '') !== '';
    }
}

if (!function_exists('stripe_api_request')) {
    /**
     * @param string $method 'GET' or 'POST'
     * @param string $path   e.g. '/v1/checkout/sessions'
     * @param array  $fields Form fields (Stripe's API takes form-encoded
     *                       data, including bracket notation for nested
     *                       params like line_items[0][price_data][...]).
     */
    function stripe_api_request($method, $path, $fields = [])
    {
        $secret_key = env('STRIPE_SECRET_KEY', '');
        if ($secret_key === '') {
            throw new \RuntimeException('Stripe is not configured (STRIPE_SECRET_KEY is empty).');
        }
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('The PHP curl extension is required for Stripe integration.');
        }

        $url = 'https://api.stripe.com' . $path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $secret_key . ':');
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        }

        $response = curl_exec($ch);
        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("Stripe request failed: $err");
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($status >= 400) {
            $message = $data['error']['message'] ?? "HTTP $status";
            throw new \RuntimeException("Stripe error: $message");
        }
        return $data;
    }
}

if (!function_exists('stripe_create_checkout_session')) {
    /**
     * Creates a Stripe-hosted Checkout page for the given order and
     * returns ['id' => ..., 'url' => ...] to redirect the customer to.
     * The order's total (already including delivery fee + tax) is charged
     * as a single line item — Stripe doesn't need our menu's line-item
     * breakdown, just the amount actually owed.
     */
    function stripe_create_checkout_session($order_id, $amount, $currency, $customer_email, $success_url, $cancel_url)
    {
        $fields = [
            'mode' => 'payment',
            'customer_email' => $customer_email,
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'metadata[order_id]' => $order_id,
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][unit_amount]' => (int)round($amount * 100), // Stripe uses the smallest currency unit
            'line_items[0][price_data][product_data][name]' => "Order #$order_id",
        ];
        return stripe_api_request('POST', '/v1/checkout/sessions', $fields);
    }
}

if (!function_exists('stripe_verify_webhook_signature')) {
    /**
     * Verifies the Stripe-Signature header per Stripe's documented scheme:
     * https://stripe.com/docs/webhooks/signatures
     * Prevents anyone but Stripe from POSTing fake "payment succeeded"
     * events at the webhook endpoint.
     */
    function stripe_verify_webhook_signature($payload, $sig_header, $webhook_secret, $tolerance_seconds = 300)
    {
        if ($webhook_secret === '' || $sig_header === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $sig_header) as $pair) {
            $kv = explode('=', $pair, 2);
            if (count($kv) === 2) {
                $parts[$kv[0]] = $kv[1];
            }
        }
        if (empty($parts['t']) || empty($parts['v1'])) {
            return false;
        }

        $timestamp = $parts['t'];
        if (abs(time() - (int)$timestamp) > $tolerance_seconds) {
            return false; // too old — replay protection
        }

        $signed_payload = $timestamp . '.' . $payload;
        $expected_sig = hash_hmac('sha256', $signed_payload, $webhook_secret);

        return hash_equals($expected_sig, $parts['v1']);
    }
}
