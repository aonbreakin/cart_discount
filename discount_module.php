<?php

function applyDiscounts($cart, $campaigns) {
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'];
    }

    // --- Apply Coupon (Fixed / Percentage) ---
    if (isset($campaigns['coupon'])) {
        $coupon = $campaigns['coupon'];
        if ($coupon['type'] === 'fixed') {
            $total -= $coupon['amount'];
        } elseif ($coupon['type'] === 'percent') {
            $total -= $total * ($coupon['percent'] / 100);
        }
    }

    // --- Apply On Top (Category / Points) ---
    if (isset($campaigns['on_top'])) {
        foreach ($campaigns['on_top'] as $onTop) {
            if ($onTop['type'] === 'category') {
                $discount = 0;
                foreach ($cart as $item) {
                    if (strtolower($item['category']) === strtolower($onTop['category'])) {
                        $discount += $item['price'] * ($onTop['percent'] / 100);
                    }
                }
                $total -= $discount;
            } elseif ($onTop['type'] === 'points') {
                $maxDiscount = $total * 0.2;
                $discount = min($onTop['points'], $maxDiscount);
                $total -= $discount;
            }
        }
    }

    // --- Apply Seasonal ---
    if (isset($campaigns['seasonal'])) {
        $seasonal = $campaigns['seasonal'];
        $steps = floor($total / $seasonal['every']);
        $total -= $steps * $seasonal['discount'];
    }

    return max(0, round($total, 2));
}
?>
