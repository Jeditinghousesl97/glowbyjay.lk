<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
    <style>
        .gatekeeper-card {
            background: #f5f5f5;
            border-radius: 20px;
            padding: 40px 20px;
            text-align: center;
            margin-top: 40px;
            margin-bottom: 40px;
        }

        .lock-icon {
            font-size: 50px;
            display: block;
            margin-bottom: 20px;
            color: #ff3b30;
        }

        .lock-svg {
            width: 50px;
            height: auto;
            display: block;
            margin: 0 auto 20px auto;
        }

        .warning-text {
            color: #ff3b30;
            font-weight: bold;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .info-text {
            color: #007aff;
            font-size: 13px;
            margin-bottom: 20px;
            line-height: 1.4;
        }

        .btn-green {
            background-color: #25d366;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            max-width: 250px;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 15px;
            display: inline-block;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-orange {
            background-color: #ff9500;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            max-width: 250px;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 30px;
            display: inline-block;
            cursor: pointer;
            text-decoration: none;
        }

        .logo-footer {
            margin-top: 50px;
            margin-bottom: 10px;
        }

        .logo-img {
            height: 40px;
        }

        .warning-footer {
            color: #ff3b30;
            font-size: 10px;
            max-width: 300px;
            margin: 10px auto;
            line-height: 1.4;
        }

        .btn-pink {
            background-color: #ff2d55;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 10px;
            display: inline-block;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="gatekeeper-card">
            <!-- SVG Lock Icon -->
            <svg class="lock-svg" viewBox="0 0 24 24" fill="none" stroke="#ff3b30" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>

            <p class="warning-text">
                This Page is only for<br>
                Asseminate Solutions (pvt) Ltd<br>
                Company Staff or<br>
                Authorized parties
            </p>

            <p class="info-text">
                If you need to make any<br>
                changes, Kindly contact us<br>
                through Whatsapp.
            </p>

            <a href="https://wa.me/94718456999" class="btn-green">071 84 56 999</a>
            <br>
            <a href="<?= BASE_URL ?>admin/dashboard" class="btn-orange">⬅ Go to Dashboard</a>

        </div>

        <!-- Footer -->
        <div style="text-align:center;">
            <div class="logo-footer">
                <img src="<?= BASE_URL ?>assets/icons/Asseminate-Logo.png" class="logo-img" alt="Asseminate">
            </div>

            <p class="warning-footer">
                Authorized Personnel Only: Do not attempt to log in unless you are a member of the Asseminate Solution
                Admin Team.
            </p>

            <a href="<?= BASE_URL ?>settings/login" class="btn-pink">Login as Admin</a>
        </div>
    </div>

    <?php $current_page = 'settings';
    include 'views/layouts/bottom_nav.php'; ?>

</body>

</html>