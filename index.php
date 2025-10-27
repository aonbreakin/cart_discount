<?php
include 'discount_module.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cart = json_decode($_POST['cart'], true);
    $campaigns = json_decode($_POST['campaigns'], true);
    $finalPrice = applyDiscounts($cart, $campaigns);
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Discount Module Demo</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<h2>🛍 Discount Module - PHP</h2>

<form method="POST">
  <label>Shopping Cart (JSON)</label><br>
  <textarea name="cart" rows="6" cols="60">[
  {"name": "T-Shirt", "category": "Clothing", "price": 350},
  {"name": "Hat", "category": "Accessories", "price": 250}
]</textarea><br><br>

  <label>Campaigns (JSON)</label><br>
  <textarea name="campaigns" rows="8" cols="60">{
  "coupon": {"type": "percent", "percent": 10},
  "on_top": [
    {"type": "category", "category": "Clothing", "percent": 15},
    {"type": "points", "points": 60}
  ],
  "seasonal": {"every": 300, "discount": 40}
}</textarea><br><br>

  <button type="submit">Calculate Final Price</button>
</form>

<?php if (isset($finalPrice)): ?>
  <h3>✅ Final Price: <?= $finalPrice ?> THB</h3>
<?php endif; ?>

</body>
</html>
