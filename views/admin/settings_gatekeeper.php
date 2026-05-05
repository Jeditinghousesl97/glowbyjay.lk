<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Restricted</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
    <style>
        .gatekeeper-container {
            background-color: #f5f5f5;
            border-radius: 20px;
            padding: 30px 20px;
            text-align: center;
            margin-top: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .lock-icon {
            font-size: 40px;
            color: #ff4d4d;
            margin-bottom: 20px;
            border: 2px solid #ff4d4d;
            border-radius: 10px;
            padding: 10px;
            display: inline-block;
            width: 40px;
            height: 30px;
            line-height: 30px;
        }

        .warning-text {
            color: #ff3b30;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .info-text {
            color: #007aff;
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.4;
        }

        .btn-whatsapp {
            background-color: #25d366;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            border: none;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 80%;
            margin: 0 auto 15px auto;
            text-decoration: none;
        }

        .btn-dashboard {
            background-color: #ff9500;
            color: black;
            padding: 12px 20px;
            border-radius: 8px;
            border: none;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            width: 80%;
            margin: 0 auto 40px auto;
            text-decoration: none;
        }

        .admin-login-section {
            margin-top: 40px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .company-logo-area {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 10px;
            font-weight: bold;
            font-size: 20px;
        }

        .sub-warning {
            color: #ff3b30;
            font-size: 11px;
            margin-bottom: 20px;
            padding: 0 20px;
        }

        .btn-login-admin {
            background-color: #ff3b69;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="gatekeeper-container">

            <!-- Lock Icon Placeholder (Using Emoji until SVG found) -->
            <div style="font-size: 40px; margin-bottom: 10px;">🔒</div>

            <p class="warning-text">
                This Page is <strong>only</strong> for<br>
                Asseminate Solutions (pvt) Ltd<br>
                Company Staff or<br>
                Authorized parties
            </p>

            <p class="info-text">
                If you need to make any<br>
                changes, Kindly contact us<br>
                through Whatsapp.
            </p>

            <!-- WhatsApp Button -->
            <a href="https://wa.me/94718456999" class="btn-whatsapp">
                071 84 56 999
            </a>

            <!-- Go to Dashboard -->
            <a href="<?= BASE_URL ?>admin/dashboard" class="btn-dashboard">
                ⬅ Go to Dashboard
            </a>

            <!-- Footer Section -->
            <div class="admin-login-section">
                <div class="company-logo-area">
                    <!-- Simple Logo Placeholder -->
                    <span style="color:#2ba640; font-size:24px;">🍃</span> Asseminate
                </div>

                <p class="sub-warning">
                    Authorized Personnel Only: Do not attempt to log in unless you are a member of the Asseminate
                    Solution Admin Team.
                </p>

                <a href="<?= BASE_URL ?>settings/login" class="btn-login-admin">
                    Login as Admin
                </a>
            </div>
        </div>
    </div>

    <!-- Bottom Nav -->
    <?php $current_page = 'settings';
    include 'views/layouts/bottom_nav.php'; ?>

</body>

</html>