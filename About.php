<?php include 'includes/header.php'; ?>
<?php include 'configure.php'; ?>

<!-- About Section -->
<div class="about" id="About">
    <h1 class="about-title">About<span>Us</span></h1>
    
    <div class="about_main_content">
        
            <div class="about_image">
            <img src="image/Food-Plate.png" alt="Food Plate">
            </div>
            
            <div class="about_main_text">
            <h3>Why Choose us?</h3>
            <p>                
                We’re more than just a food delivery service — we’re your trusted partner in getting fresh, delicious meals whenever you need them.
                With a commitment to quality, reliability, and customer satisfaction, we make food ordering fast, simple, and worry-free.
                Our experienced team ensures timely deliveries, hygienic preparation, and a wide variety of options to suit every taste.
                From convenience to care, we put you first — every order, every time.
            </p>        
            </div>                       
    </div>

    <div class="about_services">
    <h2>Our<span> Services</span></h2>
    <br>
    <div class="about_services_list">
        <div class="about_services_item"><i class="fa-solid fa-clock"></i> - 24/7 Service</div>
        <div class="about_services_item"><i class="fa-solid fa-truck"></i> - Fast Delivery</div>
        <div class="about_services_item"><i class="fa-solid fa-utensils"></i> - Wide Food Variety</div>
        <div class="about_services_item"><i class="fa-solid fa-credit-card"></i> - Multiple Payment Options</div>
        <div class="about_services_item"><i class="fa-solid fa-location-dot"></i> - Real-Time Order Tracking</div>
        <div class="about_services_item"><i class="fa-solid fa-gift"></i> - Exclusive Offers & Discounts</div>
        <div class="about_services_item"><i class="fa-solid fa-phone-volume"></i> - Customer Support</div>
    </div>
    </div>
</div>

    <div class="team">
        <h2>Our<span>Team</span></h2>
        <br>
        
        <div class="team_box">
<?php
$query = "SELECT * FROM team_members ORDER BY Display_Order ASC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($member = mysqli_fetch_assoc($result)) {
?>
            <div class="profile">
                <img src="image/<?= htmlspecialchars($member['Image']) ?>">

                <div class="info">
                    <h4 class="name"><?= htmlspecialchars($member['Name']) ?></h4>
                    <p class="bio"><?= htmlspecialchars($member['Bio']) ?></p>
                    <div class="team_icon">
                        <i class="fa-brands fa-facebook-f"></i>
                        <i class="fa-brands fa-twitter"></i>
                        <i class="fa-brands fa-instagram"></i>
                    </div>

                </div>

            </div>
<?php
    }
}
?>

        </div>

    </div>

<?php include 'includes/footer.php'; ?>               


                

             

                