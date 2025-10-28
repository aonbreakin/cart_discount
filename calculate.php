<?php
// calculate.php
header('Content-Type: application/json');

require_once 'discount_module.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid JSON input']);
    exit;
}

$items = $input['items'] ?? [];
$discounts = $input['discounts'] ?? [];
$vat_rate = isset($input['vat_rate']) ? floatval($input['vat_rate']) : 0.0;

// Basic validation
if (!is_array($items) || count($items) == 0) {
    http_response_code(400);
    echo json_encode(['error'=>'No items provided']);
    exit;
}

// Validate discounts: only one coupon-type (fixed_amount_coupon or percentage_coupon)
$couponCount = 0;
foreach ($discounts as $d) {
    if (!isset($d['type'])) continue;
    if (in_array($d['type'], ['fixed_amount_coupon','percentage_coupon'])) $couponCount++;
}
if ($couponCount > 1) {
    http_response_code(400);
    echo json_encode(['error'=>'Only one coupon-type discount may be applied (fixed or percent).']);
    exit;
}

// Now call discount calculation (we allow discounts in the order provided by user)
try {
    $result = apply_discounts($items, $discounts, $vat_rate);
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: '.$e->getMessage()]);
}
