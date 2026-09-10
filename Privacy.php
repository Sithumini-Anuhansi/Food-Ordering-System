<?php include 'includes/header.php'; ?>

<div style="max-width:800px; margin:60px auto; padding:0 24px; line-height:1.7;">
    <h1 style="margin-bottom:8px;">Privacy Policy</h1>
    <p style="color:#888;">Last updated: <?= date('F j, Y') ?></p>

    <p><em>This is a template policy for a demo project — have a lawyer review and
    customize it (especially the data-retention and third-party sections) before
    using it for a real, live service handling real customer data.</em></p>

    <h3>What we collect</h3>
    <p>When you create an account, we collect your name, email address, phone
    number, and delivery address. When you place an order, we store the items
    ordered, delivery details, and payment status (we never store your card
    number directly — card payments are processed by Stripe, and we only keep
    a payment status and Stripe's reference ID for your order).</p>

    <h3>How we use it</h3>
    <p>Your information is used to process and deliver your orders, send
    order-status notifications, and let you log in to view your order
    history. We do not sell your personal information to third parties.</p>

    <h3>Third parties</h3>
    <p>We use Stripe to process card payments and an email provider to send
    account and order notifications. These providers only receive the
    information necessary to perform their function (e.g., Stripe receives
    your order total and email for payment processing).</p>

    <h3>Data retention</h3>
    <p>We retain account and order data for as long as your account is
    active. You can request deletion of your account by contacting us.</p>

    <h3>Your rights</h3>
    <p>You can review and update your profile information at any time from
    your account's Profile page. To request a copy of your data or full
    account deletion, contact us using the details in the footer.</p>

    <h3>Contact</h3>
    <p>Questions about this policy can be sent to the contact email listed in
    the footer below.</p>
</div>

<?php include 'includes/footer.php'; ?>
