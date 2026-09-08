<?php
include '../includes/session_check.php';
checkRole('customer');
include '../configure.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Ordering System - Order Now</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include '../includes/customer_header.php'; ?>

<!-- Same menu layout as the public Menu page, but wired up to actually order -->
<div class="menu" id="Menu">
    <h1>Food <span>Varieties</span></h1>
    <br>

    <div class="main-content-wrapper">

        <div class="categories-carousel">
        <button class="carousel-btn left"><i class="fa fa-chevron-left"></i></button>

        <div class="carousel-track">
            <div class="carousel-item active" data-category="all">
                <img src="../image/cat-all.png" alt="All">
                <span>All</span>
            </div>
            <div class="carousel-item" data-category="hotpicks">
                <img src="../image/cat-hotpicks.png" alt="Hot Picks">
                <span>Hot Picks</span>
            </div>
            <div class="carousel-item" data-category="fastfood">
                <img src="../image/cat-fastfood.png" alt="Fast Food">
                <span>Fast Food</span>
            </div>
            <div class="carousel-item" data-category="pizza">
                <img src="../image/cat-pizza.png" alt="Pizza">
                <span>Pizza</span>
            </div>
            <div class="carousel-item" data-category="maindishes">
                <img src="../image/cat-maindishes.png" alt="Main Dishes">
                <span>Main Dishes</span>
            </div>
            <div class="carousel-item" data-category="salads">
                <img src="../image/cat-salads.png" alt="Salads">
                <span>Salads</span>
            </div>
            <div class="carousel-item" data-category="desserts">
                <img src="../image/cat-desserts.png" alt="Desserts">
                <span>Desserts</span>
            </div>
            <div class="carousel-item" data-category="beverages">
                <img src="../image/cat-beverages.png" alt="Beverages">
                <span>Beverages</span>
            </div>
        </div>

        <button class="carousel-btn right"><i class="fa fa-chevron-right"></i></button>
        </div>

        <?php if (isset($_SESSION['order_error'])): ?>
            <div class="error-message" style="max-width:600px;margin:12px auto;"><?= htmlspecialchars($_SESSION['order_error']) ?></div>
            <?php unset($_SESSION['order_error']); ?>
        <?php endif; ?>

        <form method="POST" action="checkout.php" id="orderForm">
            <?= csrf_field() ?>

            <div class="menu_box">
<?php
$category_order = ['Hot Picks', 'Fast Food', 'Pizza', 'Main Dishes', 'Salads', 'Desserts', 'Beverages'];
$category_slugs = [
    'Hot Picks'   => 'hotpicks',
    'Fast Food'   => 'fastfood',
    'Pizza'       => 'pizza',
    'Main Dishes' => 'maindishes',
    'Salads'      => 'salads',
    'Desserts'    => 'desserts',
    'Beverages'   => 'beverages',
];

$menu_items_by_category = [];
$query = "SELECT * FROM food_items WHERE available = 1 ORDER BY Name ASC";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $menu_items_by_category[$row['Category']][] = $row;
    }
}

foreach ($category_order as $cat_label) {
    if (empty($menu_items_by_category[$cat_label])) continue;
    $slug = isset($category_slugs[$cat_label]) ? $category_slugs[$cat_label] : strtolower(str_replace(' ', '', $cat_label));
    echo '<div data-category="' . htmlspecialchars($slug) . '"><h2>' . htmlspecialchars($cat_label) . '</h2>' . "\n";

    foreach ($menu_items_by_category[$cat_label] as $item) {
        $rating = floatval($item['Rating']);
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
        $item_id = (int)$item['Item_ID'];
        $out_of_stock = ($item['Stock_Quantity'] !== null && (int)$item['Stock_Quantity'] <= 0);
?>
            <div class="menu_card">
                <div class="menu_image">
                    <img src="../image/<?= htmlspecialchars($item['Image']) ?>">
                </div>
                <div class="small_card">
                    <i class="fa-solid fa-heart"></i>
                </div>
                <div class="menu_info">
                    <h5><?= htmlspecialchars($item['Name']) ?></h5>
                    <p><?= htmlspecialchars($item['Description']) ?></p>
                    <h6>$<?= number_format($item['Price'], 2) ?></h6>
                    <div class="menu_icon">
<?php
        for ($s = 0; $s < $full_stars; $s++) {
            echo "                        <i class=\"fa-solid fa-star\"></i>\n";
        }
        if ($half_star) {
            echo "                        <i class=\"fa-solid fa-star-half-stroke\"></i>\n";
        }
?>
                    </div>
                    <?php if ($out_of_stock): ?>
                        <p style="color:#e74c3c; font-weight:bold; margin:4px 0;">Out of stock</p>
                    <?php else: ?>
                        <div class="qty_stepper" style="display:flex; align-items:center; justify-content:center; gap:6px; margin:4px 0;">
                            <button type="button" class="qty_btn qty_minus" style="border:none; background:#eee; width:24px; height:24px; cursor:pointer; font-weight:bold;">-</button>
                            <input type="number" class="qty_input" name="Quantity[<?= $item_id ?>]" min="0" <?= $item['Stock_Quantity'] !== null ? 'max="' . (int)$item['Stock_Quantity'] . '"' : '' ?> value="0" style="width:40px; text-align:center;">
                            <button type="button" class="qty_btn qty_plus" style="border:none; background:#eee; width:24px; height:24px; cursor:pointer; font-weight:bold;">+</button>
                        </div>
                        <button type="submit" class="menu_btn order_now_btn" style="border:none; cursor:pointer; width:100%; font:inherit;">Order Now</button>
                    <?php endif; ?>
                </div>
            </div>
<?php
    }
    echo '</div>' . "\n";
}
?>
            </div>

            <div style="text-align:center; margin: 24px 0;">
                <button type="submit" class="menu_btn" style="border:none; cursor:pointer; width:240px; padding:10px; font:inherit; font-size:1rem;">
                    Review &amp; Place Order
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Per-card quantity steppers, and clicking "Order Now" on a card that's
    // still at 0 bumps just that card to 1 before the form submits — so a
    // single click orders that item without needing to touch the stepper.
    document.querySelectorAll('.menu_card').forEach(function (card) {
        const input = card.querySelector('.qty_input');
        const minus = card.querySelector('.qty_minus');
        const plus = card.querySelector('.qty_plus');
        const orderBtn = card.querySelector('.order_now_btn');

        if (minus && input) {
            minus.addEventListener('click', function () {
                const val = Math.max(0, (parseInt(input.value, 10) || 0) - 1);
                input.value = val;
            });
        }
        if (plus && input) {
            plus.addEventListener('click', function () {
                const max = input.getAttribute('max');
                let val = (parseInt(input.value, 10) || 0) + 1;
                if (max && val > parseInt(max, 10)) val = parseInt(max, 10);
                input.value = val;
            });
        }
        if (orderBtn && input) {
            orderBtn.addEventListener('click', function () {
                if (!input.value || parseInt(input.value, 10) === 0) {
                    input.value = 1;
                }
            });
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
