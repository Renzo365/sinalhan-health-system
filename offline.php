<?php
// offline.php
require_once __DIR__ . '/config/app.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline Mode - Sinalhan Health Center</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0D7377;
            --primary-dark: #072227;
            --primary-light: #14C38E;
            --bg-color: #f4f7f6;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .offline-card {
            background-color: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
            border: 1px solid rgba(13, 115, 119, 0.1);
        }
        .offline-icon {
            font-size: 72px;
            color: var(--primary-color);
            margin-bottom: 20px;
            display: inline-block;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        h1 {
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 26px;
            margin-bottom: 15px;
        }
        p {
            color: #6c757d;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .btn-offline {
            background-color: var(--primary-color);
            color: #ffffff;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 10px;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-offline:hover {
            background-color: var(--primary-dark);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(13, 115, 119, 0.2);
        }
        .btn-secondary-offline {
            color: var(--primary-color);
            background: transparent;
            border: 1px solid var(--primary-color);
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-secondary-offline:hover {
            background-color: rgba(13, 115, 119, 0.05);
            color: var(--primary-color);
        }
    </style>
</head>
<body>

    <div class="offline-card">
        <div class="offline-icon">
            <i class="bi bi-wifi-off"></i>
        </div>
        <h1>No Connection Detected</h1>
        <p>It looks like you are currently offline. However, the system's core client features remain active. You can record new patient registrations locally, and they will be synchronized once your connection returns.</p>
        
        <div class="d-grid gap-3">
            <a href="<?= BASE_URL ?>patients/register_offline.php" class="btn btn-offline">
                <i class="bi bi-person-plus-fill me-2"></i> Register Patient Offline
            </a>
            <a href="javascript:window.location.reload();" class="btn btn-secondary-offline">
                <i class="bi bi-arrow-clockwise me-2"></i> Try Reconnecting
            </a>
        </div>
    </div>

</body>
</html>
