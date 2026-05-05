<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <meta name="robots" content="noindex,nofollow,noarchive">
    <!-- Use inline styles to guarantee it renders even if settings/CSS fail to load -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            text-align: center;
            padding: 20px;
        }
        h1 {
            font-size: 80px;
            margin: 0;
            color: #ff3b30;
        }
        h2 {
            font-size: 24px;
            margin: 10px 0 20px 0;
        }
        p {
            color: #666;
            margin-bottom: 30px;
            max-width: 400px;
        }
        .btn-home {
            background-color: #007aff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-home:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>404</h1>
    <h2>Page Not Found</h2>
    <p>We're sorry, the page you requested could not be found. Please go back to the homepage.</p>
    
    <!-- Use the globally guaranteed BASE_URL constant defined in config/config.php -->
    <a href="<?= BASE_URL ?>" class="btn-home">Return to Homepage</a>
</body>
</html>
