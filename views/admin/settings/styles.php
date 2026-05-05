<?php
if (!function_exists('renderStyleColorField')) {
    function renderStyleColorField(array $styles, string $name, string $label, string $default): void
    {
        $value = (string) ($styles[$name] ?? $default);
        ?>
        <div class="color-field">
            <label class="control-label" for="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($label) ?></label>
            <div class="color-picker-row" data-color-row>
                <input
                    id="<?= htmlspecialchars($name) ?>"
                    type="color"
                    name="<?= htmlspecialchars($name) ?>"
                    class="color-input"
                    value="<?= htmlspecialchars($value) ?>"
                    data-color-input>
                <input
                    type="text"
                    class="color-text"
                    value="<?= htmlspecialchars($value) ?>"
                    readonly
                    data-color-text>
            </div>
        </div>
        <?php
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Global Styles') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
    <style>
        body{
            background:#f4f4f4;
        }
        .page-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .header-title {
            font-size: 20px;
            font-weight: 800;
            color: #111;
            letter-spacing: .02em;
        }
        .page-note{
            margin:0 0 18px;
            color:#666;
            font-size:13px;
            line-height:1.7;
        }
        .style-card {
            background: #fff;
            border: 1px solid #ececec;
            padding: 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,.04);
            margin-bottom: 18px;
        }
        .card-header {
            font-weight: 800;
            font-size: 15px;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #efefef;
            letter-spacing: .03em;
        }
        .card-grid{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:16px;
        }
        .control-group {
            margin-bottom: 0;
        }
        .control-label {
            font-size: 12px;
            font-weight: 800;
            color: #555;
            margin-bottom: 7px;
            display: block;
            letter-spacing: .18em;
            text-transform: uppercase;
        }
        .color-field{
            display:grid;
            gap:7px;
        }
        .color-picker-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .color-input {
            width: 42px;
            height: 42px;
            padding: 0;
            border: none;
            cursor: pointer;
            background: transparent;
        }
        .color-text {
            flex: 1;
            min-width: 0;
            height: 42px;
            padding: 0 12px;
            border: 1px solid #ddd;
            background: #fafafa;
            color: #555;
            font-size: 13px;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .section-header-block {
            width: 100%;
            font-size: 17px;
            font-weight: 800;
            color: #222;
            border-bottom: 2px solid #111;
            padding-bottom: 8px;
            margin: 34px 0 18px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .btn-save {
            background: #b9000b;
            color: white;
            border: none;
            padding: 14px 18px;
            width: 100%;
            font-weight: 800;
            font-size: 15px;
            cursor: pointer;
            margin: 18px 0 46px;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .palette-helper{
            margin-top: 12px;
            color:#777;
            font-size:12px;
            line-height:1.7;
        }
        @media (max-width: 780px){
            .card-grid{
                grid-template-columns:1fr;
            }
        }
    </style>
</head>

<body>
    <form action="<?= BASE_URL ?>settings/updateStyles" method="POST">
        <?= csrf_input() ?>
        <div class="container">
            <div class="page-header">
                <a href="<?= BASE_URL ?>settings/edit" style="text-decoration:none; color:black; font-size:24px;">â®</a>
                <div class="header-title">Global Color Palette</div>
            </div>

            <p class="page-note">
                Use this page to control the main website palette from one place. Typography, layout sizes, and other non-color controls have been removed to keep the settings focused.
            </p>

            <div class="section-header-block">1. Core Colors</div>
            <div class="style-card">
                <div class="card-grid">
                    <?php renderStyleColorField($styles, 'primary_color', 'Primary Color', '#b9000b'); ?>
                    <?php renderStyleColorField($styles, 'secondary_color', 'Secondary Color', '#1f1f1f'); ?>
                    <?php renderStyleColorField($styles, 'accent_red', 'Accent / Sale Red', '#e31a1a'); ?>
                    <?php renderStyleColorField($styles, 'bg_color', 'Page Background', '#ffffff'); ?>
                    <?php renderStyleColorField($styles, 'surface_color', 'Surface Color', '#fafafa'); ?>
                    <?php renderStyleColorField($styles, 'ink_color', 'Text Color', '#1c1b1b'); ?>
                    <?php renderStyleColorField($styles, 'muted_color', 'Muted Text', '#6d6665'); ?>
                    <?php renderStyleColorField($styles, 'border_color', 'Border Color', '#eceaea'); ?>
                </div>
            </div>

            <div class="section-header-block">2. Header & Footer</div>
            <div class="style-card">
                <div class="card-grid">
                    <?php renderStyleColorField($styles, 'header_bg', 'Header Background', '#ffffff'); ?>
                    <?php renderStyleColorField($styles, 'header_text', 'Header Text', '#1c1b1b'); ?>
                    <?php renderStyleColorField($styles, 'footer_bg', 'Footer Background', '#ffffff'); ?>
                    <?php renderStyleColorField($styles, 'footer_text', 'Footer Text', '#1c1b1b'); ?>
                    <?php renderStyleColorField($styles, 'footer_link', 'Footer Link', '#b9000b'); ?>
                </div>
            </div>

            <div class="section-header-block">3. Navigation</div>
            <div class="style-card">
                <div class="card-grid">
                    <?php renderStyleColorField($styles, 'nav_mobile_bg', 'Mobile Nav Background', '#ffffff'); ?>
                    <?php renderStyleColorField($styles, 'nav_mobile_icon_color', 'Mobile Nav Icon', '#999999'); ?>
                    <?php renderStyleColorField($styles, 'nav_mobile_active_color', 'Mobile Active Color', '#b9000b'); ?>
                    <?php renderStyleColorField($styles, 'nav_desktop_bg', 'Desktop Nav Background', '#ffffff'); ?>
                    <?php renderStyleColorField($styles, 'nav_desktop_link_color', 'Desktop Nav Link', '#666666'); ?>
                </div>
            </div>

            <div class="section-header-block">4. Buttons & Floating Actions</div>
            <div class="style-card">
                <div class="card-grid">
                    <?php renderStyleColorField($styles, 'btn_addcart_bg', 'Add to Cart Background', '#111111'); ?>
                    <?php renderStyleColorField($styles, 'btn_addcart_text', 'Add to Cart Text', '#ffffff'); ?>
                    <?php renderStyleColorField($styles, 'btn_ordernow_bg', 'Buy Now Background', '#b9000b'); ?>
                    <?php renderStyleColorField($styles, 'btn_ordernow_text', 'Buy Now Text', '#ffffff'); ?>
                    <?php renderStyleColorField($styles, 'btn_cart_whatsapp_bg', 'Cart WhatsApp Background', '#25d366'); ?>
                    <?php renderStyleColorField($styles, 'btn_cart_whatsapp_text', 'Cart WhatsApp Text', '#ffffff'); ?>
                    <?php renderStyleColorField($styles, 'btn_cart_cod_bg', 'Cart COD Background', '#111111'); ?>
                    <?php renderStyleColorField($styles, 'btn_cart_cod_text', 'Cart COD Text', '#ffffff'); ?>
                    <?php renderStyleColorField($styles, 'btn_cart_payhere_bg', 'Card Payments Background', '#111111'); ?>
                    <?php renderStyleColorField($styles, 'btn_cart_payhere_text', 'Card Payments Text', '#ffffff'); ?>
                    <?php renderStyleColorField($styles, 'btn_cart_koko_bg', 'KOKO Background', '#fff3dc'); ?>
                    <?php renderStyleColorField($styles, 'btn_cart_koko_text', 'KOKO Text', '#111111'); ?>
                    <?php renderStyleColorField($styles, 'floating_cart_bg', 'Floating Cart Background', '#7c4af0'); ?>
                    <?php renderStyleColorField($styles, 'floating_cart_text', 'Floating Cart Text', '#ffffff'); ?>
                </div>
                <div class="palette-helper">These colors are applied across the shared site chrome and payment/action buttons.</div>
            </div>

            <button type="submit" class="btn-save">Save Palette</button>
        </div>
    </form>

    <script>
        document.querySelectorAll('[data-color-row]').forEach(function (row) {
            const input = row.querySelector('[data-color-input]');
            const text = row.querySelector('[data-color-text]');
            if (!input || !text) {
                return;
            }

            const sync = function () {
                text.value = input.value;
            };

            input.addEventListener('input', sync);
            sync();
        });
    </script>
</body>
</html>
