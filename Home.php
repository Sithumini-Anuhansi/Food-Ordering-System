<?php include 'includes/header.php'; ?>

<?php
// Logged-in customers should go straight to placing an order; everyone
// else (guests, or other roles just browsing) still goes through login.
$order_link = (isset($_SESSION['role']) && $_SESSION['role'] === 'customer')
    ? 'customer/browse_menu.php'
    : 'auth/Login.php';
?>
    
<!-- Main section of the homepage -->
<div class="main">
    <h1 class="main-heading">Enjoy Fresh<span>Food</span> the Easy Way</h1>
    <div class="main-content">
        <div class="main_text">
            <p>
                Discover a convenient and reliable way to get your favorite meals delivered right to your door.
                Our platform offers a seamless experience—from browsing a wide variety of dishes to quick and secure ordering.
                We prioritize freshness, flavor, and satisfaction, ensuring every bite brings you joy.
                Whether you're craving a quick lunch or planning a family dinner, we've got something delicious for everyone!
            </p>
        </div>
        <div class="main_image">
            <img src="image/main_img.png" alt="Main Food" height="500" width="500"> 
        </div>
    </div>
</div>

<!-- Deals section displaying featured food offers -->
<div class="deals" id="deals">
    <h2>Top<span> Deals</span></h2>
    <br>

    <!-- Container for all deal images and descriptions -->
    <div class="deals_image_box">

        <!-- Individual deal items -->
        <div class="deals_image">
            <img src="image/deal01.jpg" alt="Deal 1">
            <p>
                Enjoy our mouth-watering pizza loaded with cheese and your favorite toppings. Freshly baked just for you! 
            </p>
            <a href="<?= htmlspecialchars($order_link) ?>" class="deals_btn">Order Now</a>
        </div>

        <div class="deals_image">
            <br>
            <img src="image/deal02.jpg" alt="Deal 2">
            <p>
                Savor the perfect blend of fragrant rice, fresh vegetables, and tender meat or seafood – a true Sri Lankan favorite!
            </p>
            <a href="<?= htmlspecialchars($order_link) ?>" class="deals_btn">Order Now</a>
        </div>

        <div class="deals_image">
            <img src="image/deal03.jpg" alt="Deal 3">
            <p>
               Soft, warm, and oven-fresh bread – perfect for your breakfast, tea time, or any meal of the day! 
            </p>
            <a href="<?= htmlspecialchars($order_link) ?>" class="deals_btn">Order Now</a>
        </div>

        <div class="deals_image">
            <img src="image/deal04.jpg" alt="Deal 4">
            <p>
                A creamy mix of tender beans topped with melted cheese – rich in flavor and perfect for any meal!
            </p>
            <a href="<?= htmlspecialchars($order_link) ?>" class="deals_btn">Order Now</a>
        </div>

        <div class="deals_image">
            <img src="image/deal05.jpg" alt="Deal 5">
            <p>
                Crispy, spicy tacos paired with an ice-cold Coke – the perfect combo to satisfy your cravings!
            </p>
            <a href="<?= htmlspecialchars($order_link) ?>" class="deals_btn">Order Now</a>
        </div>

        <div class="deals_image">
            <img src="image/deal06.jpg" alt="Deal 6">
            <p>
                A nourishing vegetarian dish made with protein-rich ulundu (urad dal), slow-cooked with island spices – perfect with rice or roti!
            </p>
            <a href="<?= htmlspecialchars($order_link) ?>" class="deals_btn">Order Now</a>
        </div>

        <div class="deals_image">
            <img src="image/deal07.jpg" alt="Deal 7">
            <p>
                Aromatic biriyani rice served with spicy chicken or egg curry, flavorful hodi, and traditional sides – a full Sri Lankan-style feast on your plate!
            </p>
            <a href="<?= htmlspecialchars($order_link) ?>" class="deals_btn">Order Now</a>
        </div>

        <div class="deals_image">
            <img src="image/deal08.jpg" alt="Deal 8">
            <p>
                Bold, spicy, and full of heat! This Kochchi Rice is cooked with Sri Lanka’s hottest chillies – a must-try for spice lovers.
            </p>
            <a href="<?= htmlspecialchars($order_link) ?>" class="deals_btn">Order Now</a>
        </div>

        <div class="deals_image">
            <img src="image/deal09.jpg" alt="Deal 9">
            <p>
                Enjoy a delicious meal with 20% discount on all orders! Hurry, limited time only.
            </p>
            <a href="<?= htmlspecialchars($order_link) ?>" class="deals_btn">Order Now</a>
        </div>

        <div class="deals_image">
            <img src="image/deal10.jpg" alt="Deal 10">
            <p>
                  Cool down with our zesty, freshly made lemonade – a perfect thirst quencher!
                  Crunchy fried chicken with spicy mayo and crisp veggies for a flavor kick.    
            </p>
            <a href="<?= htmlspecialchars($order_link) ?>" class="deals_btn">Order Now</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>