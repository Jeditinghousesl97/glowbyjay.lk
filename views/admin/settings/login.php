<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
    <style>
        .login-card {
            text-align: center;
            margin-top: 80px;
        }

        .logo-img {
            height: 50px;
            margin-bottom: 30px;
        }

        .warning-red {
            color: #ff3b30;
            font-size: 12px;
            max-width: 300px;
            margin: 0 auto 40px auto;
            line-height: 1.5;
        }

        .pwd-label {
            font-weight: bold;
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            display: block;
        }

        .pwd-input {
            width: 100%;
            max-width: 300px;
            background: #f0f0f0;
            border: none;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-size: 18px;
            margin-bottom: 20px;
        }

        .footer-note {
            color: #ff959b;
            font-size: 11px;
            max-width: 250px;
            margin: 0 auto 30px auto;
        }

        .btn-login {
            background-color: #ff2d55;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            max-width: 300px;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="login-card">
            <img src="<?= BASE_URL ?>assets/icons/Asseminate-Logo.png" class="logo-img" alt="Asseminate">

            <p class="warning-red">
                Please ensure you enter the correct password. Multiple failed attempts will result in the <span
                    style="text-decoration:underline; font-weight:bold;">permanent suspension</span> of your account.
            </p>

            <form action="<?= BASE_URL ?>settings/authenticate" method="POST">
                <?= csrf_input() ?>
                <label class="pwd-label">Enter Password to Continue</label>
                <input type="password" name="password" class="pwd-input" placeholder="******************" required
                    autofocus>

                <p class="footer-note">
                    Please Double check your Password & Press Login Button
                </p>

                <?php if (isset($error)): ?>
                    <p style="color:red; font-weight:bold; margin-bottom:15px;"><?= $error ?></p>
                <?php endif; ?>

                <button type="submit" class="btn-login">Login as Admin</button>
            </form>

            <br>
            <a href="<?= BASE_URL ?>settings/index" style="color:#aaa; font-size:12px; text-decoration:none;">Cancel</a>
        </div>
    </div>

</body>

</html>
