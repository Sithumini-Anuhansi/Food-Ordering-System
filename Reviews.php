<?php include 'includes/header.php'; ?>
<?php include 'configure.php'; ?>

    <!--Review-->
    <section class="review" id="Review">
        <h1>Customer<span>Review</span></h1>

        <div class="review_box">

<?php
$query = "SELECT * FROM reviews WHERE Approved = 1 ORDER BY Display_Order ASC, Review_ID DESC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($review = mysqli_fetch_assoc($result)) {
        $rating = floatval($review['Rating']);
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
?>
            <div class="review_card">

                <div class="review_profile">
                    <img src="image/<?= htmlspecialchars($review['Image'] ?: 'Food-Plate.png') ?>">
                </div>

                <div class="review_text">
                    <h2 class="name"><?= htmlspecialchars($review['Name']) ?></h2>

                    <div class="review_icon">
<?php
        for ($s = 0; $s < $full_stars; $s++) {
            echo "                        <i class=\"fa-solid fa-star\"></i>\n";
        }
        if ($half_star) {
            echo "                        <i class=\"fa-solid fa-star-half-stroke\"></i>\n";
        }
?>
                    </div>

                    <div class="review_social">
                        <i class="fa-brands fa-facebook-f"></i>
                        <i class="fa-brands fa-instagram"></i>
                        <i class="fa-brands fa-twitter"></i>
                        <i class="fa-brands fa-linkedin-in"></i>
                    </div>

                    <p>
                        "<?= htmlspecialchars($review['Review_Text']) ?>"
                    </p>
                </div>
            </div>
<?php
    }
}
?>

        </div>
    </div>
</section> 

<?php include 'includes/footer.php'; ?>
