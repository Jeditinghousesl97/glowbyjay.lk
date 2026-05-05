<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Server Check') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css?v=<?= time() ?>">
    <style>
        .server-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:14px; margin-bottom:20px; }
        .server-card { background:#fff; border-radius:18px; padding:18px; box-shadow:0 4px 20px rgba(0,0,0,0.04); }
        .server-label { font-size:11px; color:#777; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px; }
        .server-value { font-size:24px; font-weight:900; color:#111; }
        .server-value.ok { color:#1d7a40; }
        .server-value.no { color:#d83b31; }
        .server-note { margin-top:6px; font-size:12px; color:#777; line-height:1.5; }
        .server-panel { background:#fff; border-radius:18px; padding:18px; box-shadow:0 4px 20px rgba(0,0,0,0.04); margin-bottom:16px; }
        .server-list { display:grid; gap:10px; }
        .server-item { padding:12px 14px; border:1px solid #f0f0f0; border-radius:14px; font-size:13px; color:#333; line-height:1.6; }
        .server-pill { display:inline-flex; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; }
        .server-pill.ok { background:#ecf8ef; color:#1d7a40; }
        .server-pill.no { background:#fff1f0; color:#d83b31; }
        .server-actions { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
        .server-btn { display:inline-flex; align-items:center; justify-content:center; padding:11px 15px; border-radius:12px; text-decoration:none; font-size:13px; font-weight:800; }
        .server-btn.primary { background:#111; color:#fff; }
        .server-btn.secondary { background:#fff; color:#333; border:1px solid #ececec; }
        .server-code { background:#111; color:#fff; border-radius:14px; padding:14px 16px; font-family:Consolas, monospace; font-size:13px; overflow:auto; }
    </style>
</head>
<body>
<?php include 'views/admin/partials/loader.php'; ?>
<div class="container">
    <div class="page-header" style="margin-bottom:20px;">
        <div>
            <h1 class="page-title">Server Check</h1>
            <p class="shop-subtitle">Use this page on your live hosting to confirm whether the server can support automatic image resize and WebP generation.</p>
        </div>
    </div>

    <div class="server-actions">
        <a href="<?= BASE_URL ?>admin/dashboard" class="server-btn secondary">Back to Dashboard</a>
        <a href="<?= BASE_URL ?>admin/serverCheck" class="server-btn primary">Refresh Check</a>
        <a href="<?= BASE_URL ?>admin/imageOptimizer" class="server-btn secondary">Open Image Optimizer</a>
    </div>

    <div class="server-grid">
        <div class="server-card">
            <div class="server-label">GD Extension</div>
            <div class="server-value <?= !empty($checks['gd_enabled']) ? 'ok' : 'no' ?>"><?= !empty($checks['gd_enabled']) ? 'Enabled' : 'Disabled' ?></div>
            <div class="server-note">Needed for PHP image resize/compression if Imagick is not available.</div>
        </div>
        <div class="server-card">
            <div class="server-label">Imagick Extension</div>
            <div class="server-value <?= !empty($checks['imagick_enabled']) ? 'ok' : 'no' ?>"><?= !empty($checks['imagick_enabled']) ? 'Enabled' : 'Disabled' ?></div>
            <div class="server-note">Alternative image-processing engine for resize/compression.</div>
        </div>
        <div class="server-card">
            <div class="server-label">WebP Support</div>
            <div class="server-value <?= !empty($checks['webp_support']) ? 'ok' : 'no' ?>"><?= !empty($checks['webp_support']) ? 'Available' : 'Unavailable' ?></div>
            <div class="server-note">If available, we can create lighter WebP image files on the server.</div>
        </div>
        <div class="server-card">
            <div class="server-label">AVIF Support</div>
            <div class="server-value <?= !empty($checks['avif_support']) ? 'ok' : 'no' ?>"><?= !empty($checks['avif_support']) ? 'Available' : 'Unavailable' ?></div>
            <div class="server-note">Nice bonus, but not required for the next implementation phase.</div>
        </div>
    </div>

    <div class="server-panel">
        <h3 style="margin:0 0 12px;">Environment Details</h3>
        <div class="server-list">
            <div class="server-item"><strong>PHP Version:</strong> <?= htmlspecialchars((string) ($checks['php_version'] ?? 'Unknown')) ?></div>
            <div class="server-item"><strong>Server Software:</strong> <?= htmlspecialchars((string) ($checks['server_software'] ?? 'Unknown')) ?></div>
            <div class="server-item"><strong>fileinfo Extension:</strong> <span class="server-pill <?= !empty($checks['fileinfo_enabled']) ? 'ok' : 'no' ?>"><?= !empty($checks['fileinfo_enabled']) ? 'Enabled' : 'Disabled' ?></span></div>
            <div class="server-item"><strong>upload_max_filesize:</strong> <?= htmlspecialchars((string) ($checks['upload_max_filesize'] ?? 'Unknown')) ?></div>
            <div class="server-item"><strong>post_max_size:</strong> <?= htmlspecialchars((string) ($checks['post_max_size'] ?? 'Unknown')) ?></div>
            <div class="server-item"><strong>memory_limit:</strong> <?= htmlspecialchars((string) ($checks['memory_limit'] ?? 'Unknown')) ?></div>
            <div class="server-item"><strong>max_execution_time:</strong> <?= htmlspecialchars((string) ($checks['max_execution_time'] ?? 'Unknown')) ?> seconds</div>
        </div>
    </div>

    <div class="server-panel">
        <h3 style="margin:0 0 12px;">What To Look For</h3>
        <div class="server-list">
            <?php foreach (($recommendations ?? []) as $recommendation): ?>
                <div class="server-item"><?= htmlspecialchars($recommendation) ?></div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="server-panel">
        <h3 style="margin:0 0 12px;">How To Open This On Live Site</h3>
        <div class="server-code"><?= htmlspecialchars(BASE_URL . 'admin/serverCheck') ?></div>
        <div class="server-note" style="margin-top:10px;">Login to admin on your hosted website, then open this path. Send me the results or a screenshot, and I’ll tell you whether we can build the full same-server image optimization pipeline next.</div>
    </div>
</div>
</body>
</html>
