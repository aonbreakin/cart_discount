<?php
// discount_module.php — updated to support item quantity

function apply_discounts(array $items, array $discounts, float $vat_rate = 0.0) : array {
    $steps = [];
    $total_before = 0.0;

    // compute subtotal per item considering quantity
    foreach ($items as $it) {
        $price = floatval($it['amount'] ?? 0);
        $qty = intval($it['qty'] ?? 1);
        $subtotal = $price * $qty;
        $total_before += $subtotal;
    }
    $steps[] = [
        'title' => 'Initial cart total',
        'detail' => number_format($total_before,2) . ' THB (includes quantities)'
    ];

    $current_total = $total_before;

    foreach ($discounts as $d) {
        $type = $d['type'] ?? null;
        $p = $d['params'] ?? [];
        if (!$type) continue;

        switch ($type) {
            case 'fixed_amount_coupon':
                $amount = floatval($p['amount'] ?? 0);
                $applied = min($amount, $current_total);
                $current_total -= $applied;
                $steps[] = ['title'=>"Fixed Coupon (-{$applied} THB)", 'detail'=>"Subtract {$amount} THB"];
                break;

            case 'percentage_coupon':
                $percent = floatval($p['percent'] ?? 0);
                $disc = $current_total * ($percent/100);
                $current_total -= $disc;
                $steps[] = ['title'=>"Percentage Coupon (-{$disc} THB)", 'detail'=>"$percent% off entire cart"];
                break;

            case 'percentage_by_category':
                $cat = $p['category'] ?? '';
                $percent = floatval($p['percent'] ?? 0);
                $cat_total = 0.0;
                foreach ($items as $it) {
                    if (strcasecmp(trim($it['category']), trim($cat)) === 0) {
                        $cat_total += floatval($it['amount']) * intval($it['qty']);
                    }
                }
                $disc = $cat_total * ($percent/100);
                $current_total -= $disc;
                $steps[] = ['title'=>"Category $cat (-{$disc} THB)", 'detail'=>"$percent% off $cat (total $cat_total)"];
                break;

            case 'discount_by_points':
                $points = floatval($p['points'] ?? 0);
                $rate = floatval($p['rate'] ?? 1);
                $maxp = floatval($p['max_percent'] ?? 20);
                $max = $current_total * ($maxp/100);
                $available = $points * $rate;
                $apply = min($available, $max);
                $current_total -= $apply;
                $steps[] = ['title'=>"Points (-{$apply} THB)", 'detail'=>"$points pts @{$rate}THB/pt capped {$maxp}%"];
                break;

            case 'seasonal':
                $every = floatval($p['every'] ?? 0);
                $discY = floatval($p['discount'] ?? 0);
                if ($every>0) {
                    $times = floor($current_total / $every);
                    $apply = $times * $discY;
                    $current_total -= $apply;
                    $steps[] = ['title'=>"Seasonal (-{$apply} THB)", 'detail'=>"Every {$every} THB => -{$discY} THB × {$times}"];
                }
                break;
        }
        if ($current_total < 0) $current_total = 0;
    }

    $vat = $current_total * ($vat_rate/100);
    $after = $current_total + $vat;

    return [
        'steps'=>$steps,
        'total_before'=>round($total_before,2),
        'total_without_vat'=>round($current_total,2),
        'vat_rate'=>$vat_rate,
        'vat_amount'=>round($vat,2),
        'total_after'=>round($after,2)
    ];
}
