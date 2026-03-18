<?php
require_once __DIR__ . '/bootstrap.php';
include 'menu.php';

require_once APP_ROOT . '/bin/Model/Repairs.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

$allowedTabs = ['overview', 'tech_numbers', 'tech_numbers_expanded', 'tech_repairs', 'repair_priority'];
$selectedTab = $_GET['tab'] ?? 'overview';

if (!in_array($selectedTab, $allowedTabs, true)) {
    $selectedTab = 'overview';
}

$repairsModel = new Repairs();
$availableFiscalYears = $repairsModel->getAvailableFiscalYearsDetailed();

$selectedFiscalYear = isset($_GET['fy']) ? (int)$_GET['fy'] : null;
$fyRange = helpers::getFiscalYearDateRange($selectedFiscalYear);

$exportUrl = 'monthly_tech.php?tab=' . urlencode($selectedTab)
. '&fy=' . urlencode((string)$fyRange['fiscal_year'])
. '&export=csv';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Repairs</title>
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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .page-title {
            margin: 0;
        }

        .page-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .page-controls label {
            font-weight: bold;
        }

        .page-controls select,
        .page-controls a {
            padding: 8px 10px;
            font-size: 14px;
        }

        .export-link {
            text-decoration: none;
            background: #0d6efd;
            color: #fff;
            border-radius: 4px;
            display: inline-block;
        }

        .export-link:hover {
            background: #0b5ed7;
        }

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

        .subtotal-row {
            background: #e9ecef !important;
            font-weight: bold;
        }

        .grand-total-row {
            background: #ced4da !important;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="page-wrap">

    <div class="page-header">
        <h1 class="page-title">Repairs - <?= htmlspecialchars($fyRange['label']) ?></h1>

        <div class="page-controls">
            <form method="get" action="monthly_tech.php">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($selectedTab) ?>">
                <label for="fy">Fiscal Year:</label>
                <select name="fy" id="fy" onchange="this.form.submit()">
                    <?php foreach ($availableFiscalYears as $fy): ?>
                        <option value="<?= htmlspecialchars((string)$fy['fiscal_year']) ?>"
                            <?= $fy['fiscal_year'] === $fyRange['fiscal_year'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fy['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php if (in_array($selectedTab, ['tech_numbers_expanded', 'tech_repairs', 'repair_priority'], true)): ?>
                <a class="export-link" href="<?= htmlspecialchars($exportUrl) ?>">Export CSV</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="tab-bar">
        <a class="tab-link <?= $selectedTab === 'overview' ? 'active' : '' ?>"
           href="monthly_tech.php?tab=overview&fy=<?= urlencode((string)$fyRange['fiscal_year']) ?>">Overview</a>

        <a class="tab-link <?= $selectedTab === 'tech_numbers' ? 'active' : '' ?>"
           href="monthly_tech.php?tab=tech_numbers&fy=<?= urlencode((string)$fyRange['fiscal_year']) ?>">Tech Numbers</a>

        <a class="tab-link <?= $selectedTab === 'tech_numbers_expanded' ? 'active' : '' ?>"
           href="monthly_tech.php?tab=tech_numbers_expanded&fy=<?= urlencode((string)$fyRange['fiscal_year']) ?>">Tech Numbers Expanded</a>

        <a class="tab-link <?= $selectedTab === 'tech_repairs' ? 'active' : '' ?>"
           href="monthly_tech.php?tab=tech_repairs&fy=<?= urlencode((string)$fyRange['fiscal_year']) ?>">Tech Repairs</a>
           
        <a class="tab-link <?= $selectedTab === 'repair_priority' ? 'active' : '' ?>"
   		   href="monthly_tech.php?tab=repair_priority&fy=<?= urlencode((string)$fyRange['fiscal_year']) ?>">Repair Priority</a>
    </div>

    <div class="tab-panel">
        <?php
        switch ($selectedTab) {
            case 'tech_numbers':
                require_once APP_ROOT . '/bin/Tabs/monthly_tech_numbers.php';
                break;

            case 'tech_numbers_expanded':
                require_once APP_ROOT . '/bin/Tabs/monthly_tech_numbers_expanded.php';
                break;

            case 'tech_repairs':
                require_once APP_ROOT . '/bin/Tabs/monthly_tech_repairs.php';
                break;

            case 'repair_priority':
                require_once APP_ROOT . '/bin/Tabs/monthly_tech_repair_priority.php';
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