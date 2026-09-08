<?php include 'includes/header.php'; ?>
<?php include 'configure.php'; ?>

<?php
// Logged-in customers should go straight to placing an order; everyone
// else (guests, or other roles just browsing) still goes through login.
$order_link = (isset($_SESSION['role']) && $_SESSION['role'] === 'customer')
    ? 'customer/browse_menu.php'
    : 'auth/Login.php';
?>

<!-- Main container for the Menu section -->
<div class="menu" id="Menu">
    <h1>Food <span>Varieties</span></h1>
    <br>
    
        <!-- Wrapper for the main content -->
        <div class="main-content-wrapper">  
            
            <!-- Category carousel to filter menu items -->
            <div class="categories-carousel">
            <button class="carousel-btn left"><i class="fa fa-chevron-left"></i></button>
            
            <!-- Carousel track containing food categories -->
            <div class="carousel-track">
                <div class="carousel-item active" data-category="all">
                    <img src="image/cat-all.png" alt="All">
                    <span>All</span>
                </div>
                <div class="carousel-item" data-category="hotpicks">
                    <img src="image/cat-hotpicks.png" alt="Hot Picks">
                    <span>Hot Picks</span>
                </div>
                <div class="carousel-item" data-category="fastfood">
                    <img src="image/cat-fastfood.png" alt="Fast Food">
                    <span>Fast Food</span>
                </div>
                <div class="carousel-item" data-category="pizza">
                    <img src="image/cat-pizza.png" alt="Pizza">
                    <span>Pizza</span>
                </div>
                <div class="carousel-item" data-category="maindishes">
                    <img src="image/cat-maindishes.png" alt="Main Dishes">
                    <span>Main Dishes</span>
                </div>
                <div class="carousel-item" data-category="salads">
                    <img src="image/cat-salads.png" alt="Salads">
                    <span>Salads</span>
                </div>
                <div class="carousel-item" data-category="desserts">
                    <img src="image/cat-desserts.png" alt="Desserts">
                    <span>Desserts</span>
                </div>
                <div class="carousel-item" data-category="beverages">
                    <img src="image/cat-beverages.png" alt="Beverages">
                    <span>Beverages</span>
                </div>                
            </div>

            <button class="carousel-btn right"><i class="fa fa-chevron-right"></i></button>
            </div>        


        <!--Menu-->
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
?>
            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/<?= htmlspecialchars($item['Image']) ?>">
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
                    <a href="<?= htmlspecialchars($order_link) ?>" class="menu_btn">Order Now</a>
                </div>
            </div>
<?php
    }
    echo '</div>' . "\n";
}
?>
        </div>
        </div>

<?php include 'includes/footer.php'; ?>