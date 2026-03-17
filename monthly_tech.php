<?php
require_once __DIR__ . '/bootstrap.php';
include 'menu.php';

$allowedTabs = ['overview', 'tech_numbers', 'tech_numbers_expanded'];
$selectedTab = $_GET['tab'] ?? 'overview';

if (!in_array($selectedTab, $allowedTabs, true)) {
    $selectedTab = 'overview';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Tech</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 20px;
            background: #f8f9fa;
            color: #212529;
        }

        .page-wrap {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Tabs */
        .tab-bar {
            display: flex;
            gap: 6px;
            align-items: flex-end;
        }

        .tab-link {
            padding: 12px 20px;
            text-decoration: none;
            color: #212529;
            background: #d9dee3;
            border: 1px solid #bfc7cf;
            border-bottom: none;
            border-radius: 10px 10px 0 0;
            font-weight: bold;
        }

        .tab-link.active {
            background: #ffffff;
            position: relative;
            top: 1px;
            z-index: 2;
        }

        .tab-panel {
            background: #ffffff;
            border: 1px solid #bfc7cf;
            border-radius: 0 8px 8px 8px;
            padding: 20px;
            min-height: 500px;
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 8px 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f1f3f5;
        }

        tr:nth-child(even) {
            background: #fafafa;
        }
    </style>
</head>
<body>
<div class="page-wrap">

    <!-- Tabs -->
    <div class="tab-bar">
        <a class="tab-link <?= $selectedTab === 'overview' ? 'active' : '' ?>"
           href="monthly_tech.php?tab=overview">Overview</a>

        <a class="tab-link <?= $selectedTab === 'tech_numbers' ? 'active' : '' ?>"
           href="monthly_tech.php?tab=tech_numbers">Tech Numbers</a>

        <a class="tab-link <?= $selectedTab === 'tech_numbers_expanded' ? 'active' : '' ?>"
           href="monthly_tech.php?tab=tech_numbers_expanded">Tech Numbers Expanded</a>
    </div>

    <!-- Content -->
    <div class="tab-panel">
        <?php
        switch ($selectedTab) {

            case 'tech_numbers':
                require_once APP_ROOT . '/bin/Tabs/monthly_tech_numbers.php';
                break;

            case 'tech_numbers_expanded':
                require_once APP_ROOT . '/bin/Tabs/monthly_tech_numbers_expanded.php';
                break;

            case 'overview':
            default:
                require_once APP_ROOT . '/bin/Tabs/monthly_tech_overview.php';
                break;
        }
        ?>
    </div>

</div>
</body>
</html>