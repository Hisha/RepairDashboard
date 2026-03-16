<?php
require_once __DIR__ . '/bootstrap.php';
include 'menu.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MESC Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 20px;
            background: #f8f9fa;
            color: #212529;
        }

        .dashboard-wrap {
            max-width: 1400px;
            margin: 0 auto;
        }

        .chart-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }

        .chart-card {
            width: 420px;
            max-width: 100%;
            height: 420px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            box-sizing: border-box;
        }

        .chart-card h3 {
            margin-top: 0;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrap">
        <h1>MESC Dashboard</h1>

        <div class="chart-grid">
            <?php include 'backorders_piechart.php'; ?>
        </div>
    </div>
</body>
</html>