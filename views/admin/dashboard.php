<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css?v=<?= time() ?>">
    <style>
        /* Specific tweaks for dashboard */
        .welcome-section {
            margin-bottom: 30px;
        }

        .welcome-title {
            font-size: 28px;
            font-weight: 800;
            margin: 0;
        }

        .welcome-sub {
            color: #888;
                        margin: 5px 0 0 0;
        }
        
        /* Action Buttons (Ported from Products List) */
        .trash-icon {
            color: #ff3b30;
            border: 1px solid #ff3b30;
            border-radius: 5px;
            width: 30px;
            height: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            font-size: 16px;
        }

        .dash-card {
            background: #fff;
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }

        .chart-shell {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            background:
                radial-gradient(circle at top left, rgba(0, 122, 255, 0.08), transparent 38%),
                radial-gradient(circle at top right, rgba(255, 152, 0, 0.08), transparent 34%),
                #fafafa;
            border: 1px solid #f0f0f0;
            padding: 18px;
        }

        .chart-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .chart-metric {
            border-radius: 16px;
            padding: 14px;
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(17, 17, 17, 0.05);
        }

        .chart-metric-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #777;
            margin-bottom: 6px;
        }

        .chart-metric-value {
            font-size: 22px;
            font-weight: 800;
            color: #111;
        }

        .chart-metric-sub {
            margin-top: 4px;
            font-size: 12px;
            color: #777;
        }

        .chart-wrap {
            position: relative;
            overflow-x: auto;
            padding-bottom: 4px;
        }

        .chart-tooltip {
            position: absolute;
            top: 18px;
            left: 18px;
            min-width: 170px;
            border-radius: 16px;
            padding: 12px 14px;
            background: rgba(17, 17, 17, 0.92);
            color: #fff;
            box-shadow: 0 14px 30px rgba(0,0,0,0.16);
            pointer-events: none;
            opacity: 0;
            transform: translateY(6px);
            transition: opacity 0.18s ease, transform 0.18s ease;
            z-index: 3;
        }

        .chart-tooltip.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .chart-tooltip-date {
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .chart-tooltip-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 12px;
            margin-top: 4px;
        }

        .chart-tooltip-row span:first-child {
            color: rgba(255,255,255,0.7);
        }

        @media (min-width: 992px) {
            .welcome-section {
                gap: 18px !important;
            }

            .welcome-title {
                font-size: 36px;
            }

            .welcome-sub {
                font-size: 15px;
            }

            .dash-card {
                border-radius: 24px;
                padding: 24px;
                box-shadow: 0 16px 36px rgba(17, 24, 39, 0.06);
                border: 1px solid rgba(17, 24, 39, 0.05);
            }

            .chart-shell {
                padding: 22px;
            }
        }

    </style>
</head>

<body>
 <?php require_once ROOT_PATH . 'helpers/ImageHelper.php'; ?>
 <!-- Global Loader Injection -->
    <?php include 'views/admin/partials/loader.php'; ?>
    <div class="container">
        <!-- Header -->
        <div class="page-header"
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div class="welcome-section" style="margin-bottom: 0; display:flex; align-items:center; gap:15px;">
                <!-- Favicon Injection (with safe fallback) -->
                <?php
                $dashboardLogo = ImageHelper::settingsImageUrl((string) ($settings['shop_favicon'] ?? ''), '');
                if ($dashboardLogo === '') {
                    $dashboardLogo = ImageHelper::settingsImageUrl((string) ($settings['shop_logo'] ?? ''), '');
                }
                if ($dashboardLogo !== ''):
                    $dashboardLogoFile = basename((string) parse_url($dashboardLogo, PHP_URL_PATH));
                ?>
                    <?= ImageHelper::renderResponsivePicture(
                        $dashboardLogoFile,
                        $dashboardLogo,
                        [
                            'alt' => 'Website Favicon',
                            'loading' => 'eager',
                            'decoding' => 'async',
                            'fetchpriority' => 'high',
                            'style' => 'width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd;'
                        ],
                        'logo'
                    ) ?>
                <?php endif; ?>

                <div>
                    <h1 class="welcome-title">
                        <?= !empty($settings['shop_name']) ? htmlspecialchars($settings['shop_name']) : 'Welcome back!' ?>
                    </h1>
                    <p class="welcome-sub"><?= $_SESSION['username'] ?? 'Shop Owner' ?></p>
                </div>
            </div>

            <!-- Header Right Side -->
            <div style="display: flex; gap: 10px; align-items: center;">
                <!-- Logout Button -->
                <a href="<?= BASE_URL ?>auth/logout"
                    style="background-color: #ff3b30; color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: bold;">Logout</a>
            </div>
        </div>

        <?php
        $chartRows = $chart_rows ?? [];
        $maxRevenue = 0;
        $maxPaidRevenue = 0;
        $maxOrders = 0;
        $periodGrossRevenue = 0;
        $periodPaidRevenue = 0;
        $periodOrders = 0;
        $peakDay = null;
        foreach ($chartRows as $chartRow) {
            $maxRevenue = max($maxRevenue, (float) ($chartRow['gross_total'] ?? 0));
            $maxPaidRevenue = max($maxPaidRevenue, (float) ($chartRow['paid_total'] ?? 0));
            $maxOrders = max($maxOrders, (int) ($chartRow['orders_count'] ?? 0));
            $periodGrossRevenue += (float) ($chartRow['gross_total'] ?? 0);
            $periodPaidRevenue += (float) ($chartRow['paid_total'] ?? 0);
            $periodOrders += (int) ($chartRow['orders_count'] ?? 0);
            if ($peakDay === null || (float) ($chartRow['gross_total'] ?? 0) > (float) ($peakDay['gross_total'] ?? 0)) {
                $peakDay = $chartRow;
            }
        }
        $maxRevenueSeries = max($maxRevenue, $maxPaidRevenue);

        $chartWidth = 860;
        $chartHeight = 300;
        $chartPaddingX = 52;
        $chartPaddingTop = 18;
        $chartPaddingBottom = 42;
        $chartRightAxisWidth = 44;
        $usableWidth = max(1, $chartWidth - ($chartPaddingX * 2));
        $usableHeight = max(1, $chartHeight - $chartPaddingTop - $chartPaddingBottom);
        $pointCount = count($chartRows);
        $revenuePoints = [];
        $paidRevenuePoints = [];
        $orderPoints = [];
        $xLabels = [];
        $tooltipRows = [];
        $revenueFillPoints = [];
        $paidFillPoints = [];
        $leftAxisLabels = [];
        $rightAxisLabels = [];

        for ($step = 0; $step <= 4; $step++) {
            $ratio = $step / 4;
            $labelY = round($chartHeight - $chartPaddingBottom - ($usableHeight * $ratio), 2);
            $leftAxisLabels[] = [
                'y' => $labelY,
                'value' => ($maxRevenueSeries > 0 ? ($maxRevenueSeries * $ratio) : 0)
            ];
            $rightAxisLabels[] = [
                'y' => $labelY,
                'value' => ($maxOrders > 0 ? ($maxOrders * $ratio) : 0)
            ];
        }

        foreach ($chartRows as $index => $row) {
            $x = $chartPaddingX + ($pointCount > 1 ? ($usableWidth / ($pointCount - 1)) * $index : ($usableWidth / 2));
            $revenueValue = (float) ($row['gross_total'] ?? 0);
            $paidValue = (float) ($row['paid_total'] ?? 0);
            $orderValue = (float) ($row['orders_count'] ?? 0);
            $revenueY = $chartPaddingTop + ($usableHeight - (($maxRevenueSeries > 0 ? $revenueValue / $maxRevenueSeries : 0) * $usableHeight));
            $paidY = $chartPaddingTop + ($usableHeight - (($maxRevenueSeries > 0 ? $paidValue / $maxRevenueSeries : 0) * $usableHeight));
            $orderY = $chartPaddingTop + ($usableHeight - (($maxOrders > 0 ? $orderValue / $maxOrders : 0) * $usableHeight));

            $revenuePoints[] = round($x, 2) . ',' . round($revenueY, 2);
            $paidRevenuePoints[] = round($x, 2) . ',' . round($paidY, 2);
            $orderPoints[] = round($x, 2) . ',' . round($orderY, 2);
            $revenueFillPoints[] = round($x, 2) . ',' . round($revenueY, 2);
            $paidFillPoints[] = round($x, 2) . ',' . round($paidY, 2);
            $xLabels[] = [
                'x' => round($x, 2),
                'label' => date('M d', strtotime((string) $row['report_date']))
            ];
            $tooltipRows[] = [
                'x' => round($x, 2),
                'date' => date('D, M d', strtotime((string) $row['report_date'])),
                'gross' => number_format($revenueValue, 2),
                'paid' => number_format($paidValue, 2),
                'orders' => (int) $orderValue
            ];
        }

        if (!empty($revenueFillPoints)) {
            $revenueFillPoints[] = round($chartWidth - $chartPaddingX, 2) . ',' . ($chartHeight - $chartPaddingBottom);
            $revenueFillPoints[] = round($chartPaddingX, 2) . ',' . ($chartHeight - $chartPaddingBottom);
        }

        if (!empty($paidFillPoints)) {
            $paidFillPoints[] = round($chartWidth - $chartPaddingX, 2) . ',' . ($chartHeight - $chartPaddingBottom);
            $paidFillPoints[] = round($chartPaddingX, 2) . ',' . ($chartHeight - $chartPaddingBottom);
        }

        $averageDailyRevenue = $pointCount > 0 ? $periodGrossRevenue / $pointCount : 0;
        $conversionRate = $periodGrossRevenue > 0 ? (($periodPaidRevenue / $periodGrossRevenue) * 100) : 0;
        $currencySymbol = htmlspecialchars($settings['currency_symbol'] ?? 'LKR');
        ?>

        <div class="dash-card" style="margin-bottom:18px;">
            <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start; margin-bottom:14px;">
                <div>
                    <h3 style="margin:0;">Sales Snapshot</h3>
                    <p style="margin:4px 0 0; font-size:12px; color:#888;">Last 7 days gross sales and order count.</p>
                </div>
                <a href="<?= BASE_URL ?>order/reports" style="text-decoration:none; background:#111; color:#fff; padding:10px 14px; border-radius:999px; font-size:13px; font-weight:700;">Accounting & Reporting</a>
            </div>

            <?php if (empty($chartRows)): ?>
                <div style="padding:14px; border-radius:14px; background:#fafafa; color:#777;">No order data available yet.</div>
            <?php else: ?>
                <div class="chart-metrics">
                    <div class="chart-metric">
                        <div class="chart-metric-label">14-Day Gross</div>
                        <div class="chart-metric-value"><?= $currencySymbol ?> <?= number_format($periodGrossRevenue, 2) ?></div>
                        <div class="chart-metric-sub">All recorded order value</div>
                    </div>
                    <div class="chart-metric">
                        <div class="chart-metric-label">14-Day Paid</div>
                        <div class="chart-metric-value"><?= $currencySymbol ?> <?= number_format($periodPaidRevenue, 2) ?></div>
                        <div class="chart-metric-sub"><?= number_format($conversionRate, 1) ?>% collected</div>
                    </div>
                    <div class="chart-metric">
                        <div class="chart-metric-label">Orders in Period</div>
                        <div class="chart-metric-value"><?= (int) $periodOrders ?></div>
                        <div class="chart-metric-sub"><?= $currencySymbol ?> <?= number_format($averageDailyRevenue, 2) ?> avg daily revenue</div>
                    </div>
                    <div class="chart-metric">
                        <div class="chart-metric-label">Peak Day</div>
                        <div class="chart-metric-value"><?= $peakDay ? htmlspecialchars(date('M d', strtotime((string) $peakDay['report_date']))) : '-' ?></div>
                        <div class="chart-metric-sub"><?= $peakDay ? $currencySymbol . ' ' . number_format((float) ($peakDay['gross_total'] ?? 0), 2) : 'No data yet' ?></div>
                    </div>
                </div>

                <div class="chart-shell">
                    <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:center; margin-bottom:14px; font-size:12px;">
                        <div style="display:flex; align-items:center; gap:8px; color:#111; font-weight:700;">
                            <span style="width:14px; height:4px; border-radius:999px; background:#007aff; display:inline-block;"></span>
                            Gross Revenue
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; color:#111; font-weight:700;">
                            <span style="width:14px; height:4px; border-radius:999px; background:#22c55e; display:inline-block;"></span>
                            Paid Revenue
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; color:#111; font-weight:700;">
                            <span style="width:14px; height:4px; border-radius:999px; background:#ff9800; display:inline-block;"></span>
                            Orders
                        </div>
                    </div>

                    <div class="chart-wrap" data-chart-root>
                        <div class="chart-tooltip" data-chart-tooltip>
                            <div class="chart-tooltip-date" data-chart-date></div>
                            <div class="chart-tooltip-row"><span>Gross</span><strong data-chart-gross></strong></div>
                            <div class="chart-tooltip-row"><span>Paid</span><strong data-chart-paid></strong></div>
                            <div class="chart-tooltip-row"><span>Orders</span><strong data-chart-orders></strong></div>
                        </div>

                        <svg viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" style="width:100%; min-width:780px; height:auto; display:block;">
                            <defs>
                                <linearGradient id="revenueStroke" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#111111"/>
                                    <stop offset="100%" stop-color="#007aff"/>
                                </linearGradient>
                                <linearGradient id="revenueFill" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="rgba(0,122,255,0.24)"/>
                                    <stop offset="100%" stop-color="rgba(0,122,255,0.02)"/>
                                </linearGradient>
                                <linearGradient id="paidStroke" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#1a7f46"/>
                                    <stop offset="100%" stop-color="#22c55e"/>
                                </linearGradient>
                                <linearGradient id="paidFill" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="rgba(34,197,94,0.18)"/>
                                    <stop offset="100%" stop-color="rgba(34,197,94,0.02)"/>
                                </linearGradient>
                                <linearGradient id="ordersStroke" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#ffb300"/>
                                    <stop offset="100%" stop-color="#ff6f00"/>
                                </linearGradient>
                            </defs>

                            <line x1="<?= $chartPaddingX ?>" y1="<?= $chartHeight - $chartPaddingBottom ?>" x2="<?= $chartWidth - $chartPaddingX ?>" y2="<?= $chartHeight - $chartPaddingBottom ?>" stroke="#ececec" stroke-width="1" />
                            <line x1="<?= $chartPaddingX ?>" y1="<?= $chartPaddingTop ?>" x2="<?= $chartPaddingX ?>" y2="<?= $chartHeight - $chartPaddingBottom ?>" stroke="#e6e6e6" stroke-width="1" />
                            <line x1="<?= $chartWidth - $chartRightAxisWidth ?>" y1="<?= $chartPaddingTop ?>" x2="<?= $chartWidth - $chartRightAxisWidth ?>" y2="<?= $chartHeight - $chartPaddingBottom ?>" stroke="#f2f2f2" stroke-width="1" />

                            <?php foreach ([0, 0.25, 0.5, 0.75, 1] as $guide): ?>
                                <?php $guideY = round($chartPaddingTop + ($usableHeight - ($usableHeight * $guide)), 2); ?>
                                <line x1="<?= $chartPaddingX ?>" y1="<?= $guideY ?>" x2="<?= $chartWidth - $chartPaddingX ?>" y2="<?= $guideY ?>" stroke="#f5f5f5" stroke-width="1" stroke-dasharray="4 6" />
                            <?php endforeach; ?>

                            <?php foreach ($leftAxisLabels as $leftAxisLabel): ?>
                                <text x="<?= $chartPaddingX - 10 ?>" y="<?= $leftAxisLabel['y'] + 4 ?>" text-anchor="end" font-size="11" fill="#777777"><?= htmlspecialchars(number_format((float) $leftAxisLabel['value'], 0)) ?></text>
                            <?php endforeach; ?>

                            <?php foreach ($rightAxisLabels as $rightAxisLabel): ?>
                                <text x="<?= $chartWidth - $chartRightAxisWidth + 10 ?>" y="<?= $rightAxisLabel['y'] + 4 ?>" text-anchor="start" font-size="11" fill="#777777"><?= (int) round((float) $rightAxisLabel['value']) ?></text>
                            <?php endforeach; ?>

                            <?php if (!empty($revenuePoints)): ?>
                                <polygon fill="url(#revenueFill)" points="<?= htmlspecialchars(implode(' ', $revenueFillPoints)) ?>" />
                                <polyline fill="none" stroke="url(#revenueStroke)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" points="<?= htmlspecialchars(implode(' ', $revenuePoints)) ?>" />
                            <?php endif; ?>

                            <?php if (!empty($paidRevenuePoints)): ?>
                                <polygon fill="url(#paidFill)" points="<?= htmlspecialchars(implode(' ', $paidFillPoints)) ?>" />
                                <polyline fill="none" stroke="url(#paidStroke)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" points="<?= htmlspecialchars(implode(' ', $paidRevenuePoints)) ?>" />
                            <?php endif; ?>

                            <?php if (!empty($orderPoints)): ?>
                                <polyline fill="none" stroke="url(#ordersStroke)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" points="<?= htmlspecialchars(implode(' ', $orderPoints)) ?>" />
                            <?php endif; ?>

                            <?php foreach ($chartRows as $index => $row): ?>
                                <?php
                                [$revX, $revY] = explode(',', $revenuePoints[$index]);
                                [$paidX, $paidY] = explode(',', $paidRevenuePoints[$index]);
                                [$ordX, $ordY] = explode(',', $orderPoints[$index]);
                                $tooltip = $tooltipRows[$index];
                                ?>
                                <line x1="<?= $revX ?>" y1="<?= $chartPaddingTop ?>" x2="<?= $revX ?>" y2="<?= $chartHeight - $chartPaddingBottom ?>" stroke="rgba(17,17,17,0.04)" stroke-width="1" />
                                <circle cx="<?= $revX ?>" cy="<?= $revY ?>" r="4.5" fill="#007aff" />
                                <circle cx="<?= $paidX ?>" cy="<?= $paidY ?>" r="4.5" fill="#22c55e" />
                                <circle cx="<?= $ordX ?>" cy="<?= $ordY ?>" r="4.5" fill="#ff9800" />
                                <circle
                                    cx="<?= $revX ?>"
                                    cy="<?= $chartHeight / 2 ?>"
                                    r="16"
                                    fill="transparent"
                                    data-chart-point="1"
                                    data-date="<?= htmlspecialchars($tooltip['date']) ?>"
                                    data-gross="<?= htmlspecialchars($currencySymbol . ' ' . $tooltip['gross']) ?>"
                                    data-paid="<?= htmlspecialchars($currencySymbol . ' ' . $tooltip['paid']) ?>"
                                    data-orders="<?= (int) $tooltip['orders'] ?>"
                                />
                            <?php endforeach; ?>

                            <?php foreach ($xLabels as $label): ?>
                                <text x="<?= $label['x'] ?>" y="<?= $chartHeight - 12 ?>" text-anchor="middle" font-size="11" fill="#777777"><?= htmlspecialchars($label['label']) ?></text>
                            <?php endforeach; ?>

                            <text x="<?= $chartPaddingX ?>" y="12" text-anchor="start" font-size="11" fill="#777777">Revenue</text>
                            <text x="<?= $chartWidth - $chartRightAxisWidth ?>" y="12" text-anchor="start" font-size="11" fill="#777777">Orders</text>
                        </svg>
                    </div>
                </div>

                <div style="display:grid; gap:8px; margin-top:10px;">
                    <?php foreach ($chartRows as $row): ?>
                        <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; font-size:12px; color:#666;">
                            <strong style="color:#111;"><?= htmlspecialchars(date('M d', strtotime((string) $row['report_date']))) ?></strong>
                            <span>Gross <?= $currencySymbol ?> <?= number_format((float) ($row['gross_total'] ?? 0), 2) ?> | Paid <?= $currencySymbol ?> <?= number_format((float) ($row['paid_total'] ?? 0), 2) ?> | <?= (int) ($row['orders_count'] ?? 0) ?> orders</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Stats Grid -->
        <style>
            .stat-card-link {
                text-decoration: none;
                color: inherit;
                transition: transform 0.2s;
                display: block;
            }

            .stat-card-link:hover {
                transform: translateY(-5px);
            }
        </style>
        <div class="stats-grid">
            <a href="<?= BASE_URL ?>category/index" class="stat-card stat-card-link">
                <h2 class="stat-number"><?= $stats['categories'] ?? 0 ?></h2>
                <p class="stat-label">Categories</p>
            </a>
            <a href="<?= BASE_URL ?>product/index" class="stat-card stat-card-link">
                <h2 class="stat-number"><?= $stats['products'] ?? 0 ?></h2>
                <p class="stat-label">Products</p>
            </a>
            <a href="<?= BASE_URL ?>sizeGuide/index" class="stat-card stat-card-link">
                <h2 class="stat-number"><?= $stats['size_guides'] ?? 0 ?></h2>
                <p class="stat-label">Size Guides</p>
            </a>
            <a href="<?= BASE_URL ?>feedback/index" class="stat-card stat-card-link">
                <h2 class="stat-number"><?= $stats['feedbacks'] ?? 0 ?></h2>
                <p class="stat-label">Feedbacks</p>
            </a>
            <a href="<?= BASE_URL ?>order/manage" class="stat-card stat-card-link">
                <h2 class="stat-number"><?= $stats['orders'] ?? 0 ?></h2>
                <p class="stat-label">Orders</p>
            </a>
            <a href="<?= BASE_URL ?>stock/index" class="stat-card stat-card-link">
                <h2 class="stat-number"><?= $stats['low_stock'] ?? 0 ?></h2>
                <p class="stat-label">Low Stock</p>
            </a>
            <a href="<?= BASE_URL ?>stock/report" class="stat-card stat-card-link">
                <h2 class="stat-number"><?= $stats['tracked_products'] ?? 0 ?></h2>
                <p class="stat-label">Stock Report</p>
            </a>
            <a href="<?= BASE_URL ?>order/reports" class="stat-card stat-card-link">
                <h2 class="stat-number"><?= (int) ($stats['orders'] ?? 0) ?></h2>
                <p class="stat-label">Accounting</p>
            </a>
        </div>

        <!-- Products Section -->
        <h3 class="section-title">Products in your Store</h3>

        <div class="product-list-container">
            <!-- Header Row  -->
            <div
                style="background:#eee; padding: 10px; border-radius: 6px; font-size:12px; color:#666; margin-bottom:10px;">
                Products
            </div>

            <?php if (empty($latest_products)): ?>
                <p style="text-align:center; padding:20px; color:#999;">No products yet.</p>
            <?php else: ?>
                                <?php foreach ($latest_products as $product): ?>
                    <div class="product-item">
                        <div style="display:flex; flex-direction:column; gap:5px; margin-right:15px;">
                            <a href="<?= BASE_URL ?>product/edit/<?= $product['id'] ?>" class="trash-icon"
                                style="color:#00c4b4; border-color:#00c4b4;">
                                ✏️
                            </a>
                            <a href="<?= BASE_URL ?>product/delete/<?= $product['id'] ?>" class="trash-icon"
                                onclick="if(confirm('Delete this item?')){ showGlobalLoader(); return true; } else { return false; }">
                                🗑
                            </a>
                        </div>
                        <?php
                        $dashboardImage = ImageHelper::uploadUrl($product['main_image'] ?? '', 'https://via.placeholder.com/160?text=Product');
                        ?>
                        <?= ImageHelper::renderResponsivePicture(
                            $product['main_image'] ?? '',
                            $dashboardImage,
                            [
                                'class' => 'product-thumb',
                                'alt' => 'Img',
                                'loading' => 'lazy',
                                'decoding' => 'async',
                                'fetchpriority' => 'low'
                            ],
                            'admin_thumb'
                        ) ?>
                        <div style="flex: 1; display: flex; justify-content: space-between; align-items: center;">
                            <div class="product-info" style="flex: unset;">
                                <h4 class="product-name"><?= htmlspecialchars($product['title']) ?></h4>
                                <p class="product-category"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></p>
                            </div>
                            
                            <!-- Visibility Toggle -->
                            <a href="<?= BASE_URL ?>product/toggleActive/<?= $product['id'] ?>" 
                               class="toggle-btn <?= $product['is_active'] ? 'active' : '' ?>" 
                               title="Toggle Visibility" 
                               onclick="showGlobalLoader();">
                                <div class="toggle-circle"></div>
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bottom Nav -->
    <?php $current_page = 'dashboard';
    include 'views/layouts/bottom_nav.php'; ?>

    <script>
        (function () {
            const root = document.querySelector('[data-chart-root]');
            if (!root) {
                return;
            }

            const tooltip = root.querySelector('[data-chart-tooltip]');
            const dateNode = root.querySelector('[data-chart-date]');
            const grossNode = root.querySelector('[data-chart-gross]');
            const paidNode = root.querySelector('[data-chart-paid]');
            const ordersNode = root.querySelector('[data-chart-orders]');
            const points = root.querySelectorAll('[data-chart-point]');

            if (!tooltip || !dateNode || !grossNode || !paidNode || !ordersNode || !points.length) {
                return;
            }

            const showTooltip = (point) => {
                const rootBox = root.getBoundingClientRect();
                const pointBox = point.getBoundingClientRect();
                const left = pointBox.left - rootBox.left - 24;
                const maxLeft = Math.max(16, root.clientWidth - tooltip.offsetWidth - 16);

                dateNode.textContent = point.dataset.date || '';
                grossNode.textContent = point.dataset.gross || '';
                paidNode.textContent = point.dataset.paid || '';
                ordersNode.textContent = point.dataset.orders || '0';
                tooltip.style.left = Math.min(Math.max(left, 16), maxLeft) + 'px';
                tooltip.classList.add('is-visible');
            };

            const hideTooltip = () => {
                tooltip.classList.remove('is-visible');
            };

            points.forEach((point, index) => {
                point.addEventListener('mouseenter', () => showTooltip(point));
                point.addEventListener('focus', () => showTooltip(point));
                point.addEventListener('mouseleave', hideTooltip);
                point.addEventListener('blur', hideTooltip);

                if (index === points.length - 1) {
                    showTooltip(point);
                }
            });
        })();
    </script>
</body>

</html>
