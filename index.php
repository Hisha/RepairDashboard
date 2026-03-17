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
            width: 100%;
            padding-left: 10px;
        }

        .chart-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: flex-start;
        }

        .chart-card {
            width: 420px;
            max-width: 100%;
            min-height: 420px;
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

        .chart-canvas-wrap {
            position: relative;
            width: 100%;
            height: 340px;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrap">
        <div class="chart-grid">
            <?php require_once APP_ROOT . '/bin/Charts/backorders_piechart.php'; ?>
            <?php require_once APP_ROOT . '/bin/Charts/repairs_by_program_fy_chart.php'; ?>
        </div>
    </div>
</body>
</html>