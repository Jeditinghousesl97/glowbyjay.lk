<?php
$endpoint = $endpoint ?? '';
$payherePayload = is_array($payherePayload ?? null) ? $payherePayload : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to Card Payment</title>
    <style>
        :root{--primary:#b9000b;--ink:#1c1b1b;--muted:#6d6665}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f6f3f2;color:var(--ink);font-family:Arial,sans-serif;padding:20px}
        .card{width:min(92vw,520px);background:#fff;border:1px solid rgba(28,27,27,.10);padding:28px 24px;text-align:center}
        h1{margin:0 0 10px;font-size:28px;line-height:1.15}
        p{margin:0;color:var(--muted);line-height:1.7}
        .spinner{width:54px;height:54px;border:4px solid rgba(185,0,11,.12);border-top-color:var(--primary);border-radius:50%;margin:0 auto 18px;animation:spin .8s linear infinite}
        .fallback{margin-top:18px}
        .fallback button{min-height:46px;padding:0 18px;border:1px solid var(--primary);background:var(--primary);color:#fff;font-weight:700;cursor:pointer}
        @keyframes spin{to{transform:rotate(360deg)}}
    </style>
</head>
<body>
    <div class="card">
        <div class="spinner" aria-hidden="true"></div>
        <h1>Redirecting to Card Payment</h1>
        <p>Please wait while we connect you to the secure payment page.</p>

        <form id="payhereCheckoutForm" action="<?= htmlspecialchars($endpoint) ?>" method="POST" style="display:none;">
            <?php foreach ($payherePayload as $key => $value): ?>
                <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars((string) $value, ENT_QUOTES) ?>">
            <?php endforeach; ?>
        </form>

        <div class="fallback">
            <button type="button" onclick="document.getElementById('payhereCheckoutForm').submit();">Continue</button>
        </div>
    </div>

    <script>
        (function () {
            var form = document.getElementById('payhereCheckoutForm');
            if (!form) return;
            window.setTimeout(function () {
                form.submit();
            }, 100);
        })();
    </script>
</body>
</html>
