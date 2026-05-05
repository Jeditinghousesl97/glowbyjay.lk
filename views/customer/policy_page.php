<?php
require_once 'views/layouts/customer_layout.php';
customer_layout_start();
?>

<div style="max-width: 900px; margin: 60px auto 0; padding: 24px 0 40px;">
    <div style="background: #fff; border-radius: 24px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04); padding: 24px;">
        <h1 style="font-size: 28px; line-height: 1.15; margin-bottom: 10px; color: #111;"><?= htmlspecialchars($heading) ?></h1>
        <p style="font-size: 14px; color: #666; line-height: 1.7; margin-bottom: 24px;">
            Please review this page carefully before placing an order or using our services.
        </p>

        <div style="font-size: 14px; color: #555; line-height: 1.9;">
            <?= nl2br(htmlspecialchars($content ?? '')) ?>
        </div>
    </div>
</div>

<?php customer_layout_end(); ?>
