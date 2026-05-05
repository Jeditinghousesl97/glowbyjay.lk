<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
    <style>
        .page-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: #000;
        }

        .style-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .card-header {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .text-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .district-grid {
            display: grid;
            gap: 12px;
        }

        .district-row {
            display: grid;
            grid-template-columns: minmax(180px, 1.4fr) minmax(120px, 1fr) minmax(120px, 1fr);
            gap: 12px;
            align-items: center;
            padding: 12px;
            border-radius: 10px;
            background: #fafafa;
        }

        .district-name {
            font-size: 14px;
            font-weight: 700;
            color: #333;
        }

        .small-label {
            display: block;
            font-size: 11px;
            color: #777;
            margin-bottom: 4px;
        }

        .btn-save {
            background: #007aff;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            margin-bottom: 50px;
        }

        @media (max-width: 720px) {
            .district-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <form action="<?= BASE_URL ?>settings/updateDelivery" method="POST">
        <?= csrf_input() ?>
        <div class="container">
            <div class="page-header">
                <a href="<?= BASE_URL ?>settings/edit" style="text-decoration:none; color:black; font-size:24px;">❮</a>
                <div class="header-title">Delivery Settings</div>
            </div>

            <div class="style-card">
                <div class="card-header">General Delivery Pricing</div>
                <label style="display:flex; align-items:center; gap:8px; margin-bottom:16px; font-size:14px; color:#333;">
                    <input type="checkbox" name="delivery_apply_all_districts" id="applyAllDistricts" value="1" <?= !empty($settings['delivery_apply_all_districts']) ? 'checked' : '' ?>>
                    Apply price to all districts
                </label>

                <div id="allDistrictPricing" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                    <div>
                        <label class="small-label">First 1Kg Price</label>
                        <input type="number" min="0" step="0.01" name="delivery_all_first_kg" class="text-input" value="<?= htmlspecialchars($settings['delivery_all_first_kg'] ?? '0') ?>">
                    </div>
                    <div>
                        <label class="small-label">Per Additional 1Kg</label>
                        <input type="number" min="0" step="0.01" name="delivery_all_additional_kg" class="text-input" value="<?= htmlspecialchars($settings['delivery_all_additional_kg'] ?? '0') ?>">
                    </div>
                </div>
            </div>

            <div class="style-card" id="districtRatesCard">
                <div class="card-header">District Wise Rates</div>
                <div style="font-size:12px; color:#777; margin-bottom:14px;">Set the first 1Kg price and the price for each extra 1Kg for every district.</div>
                <div class="district-grid">
                    <?php foreach ($rates as $rate): ?>
                        <div class="district-row">
                            <div class="district-name"><?= htmlspecialchars($rate['district_name']) ?></div>
                            <div>
                                <label class="small-label">First 1Kg</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="text-input"
                                    name="district_rates[<?= htmlspecialchars($rate['district_name']) ?>][first_kg_price]"
                                    value="<?= htmlspecialchars((string) $rate['first_kg_price']) ?>">
                            </div>
                            <div>
                                <label class="small-label">Per Additional 1Kg</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="text-input"
                                    name="district_rates[<?= htmlspecialchars($rate['district_name']) ?>][additional_kg_price]"
                                    value="<?= htmlspecialchars((string) $rate['additional_kg_price']) ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn-save">Save Delivery Settings</button>
        </div>
    </form>

    <script>
        (function () {
            const applyAll = document.getElementById('applyAllDistricts');
            const districtCard = document.getElementById('districtRatesCard');

            function updateVisibility() {
                districtCard.style.display = applyAll.checked ? 'none' : 'block';
            }

            applyAll.addEventListener('change', updateVisibility);
            updateVisibility();
        }());
    </script>
</body>

</html>
