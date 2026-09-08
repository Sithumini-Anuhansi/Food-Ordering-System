<?php
/**
 * Single source of truth for delivery fee + tax, so the checkout preview
 * and the final saved order can never disagree. Swap this out for
 * zone/distance-based delivery pricing later without touching callers.
 */
if (!function_exists('calculate_order_totals')) {
    function calculate_order_totals($subtotal)
    {
        $delivery_fee = 2.50;
        $tax_rate = 0.08; // 8%
        $tax = round($subtotal * $tax_rate, 2);
        $total = round($subtotal + $delivery_fee + $tax, 2);

        return [
            'delivery_fee' => $delivery_fee,
            'tax'          => $tax,
            'total'        => $total,
        ];
    }
}
