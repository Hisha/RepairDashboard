<?php
require_once __DIR__ . "/bootstrap.php";
require_once APP_ROOT . "/vendor/autoload.php";
require_once APP_ROOT . "/bin/Utilities/xlsx_helper.php";
require_once APP_ROOT . "/bin/Model/Procurements.php";

$allowedTabs = ['procurements', 'backorder_procurements'];
$selectedTab = $_GET['tab'] ?? 'procurements';

if (!in_array($selectedTab, $allowedTabs, true)) {
    $selectedTab = 'procurements';
}

/*
 * Export XLSX for Excel
 * Must run before ANY HTML output.
 */
if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    $procure = new Procurements();
    
    if ($selectedTab === 'backorder_procurements') {
        $rows = $procure->getBackOrderProcurements();
        
        $headers = !empty($rows) ? array_keys($rows[0]) : [
            'NIIN',
            'Support Qty',
            'I.O. Qty',
            'Requested Qty',
            'On Order Qty',
            'Purchase Vehicle',
            'Contract Info'
        ];
        
        xlsx_helper::download(
            'backorder_procurements_' . date('Y-m-d') . '.xlsx',
            $headers,
            $rows,
            ['NIIN', 'Purchase Vehicle', 'Contract Info'],
            'Backorder Procurements'
            );
    } else {
        $rows = $procure->getProcurements();
        
        $headers = !empty($rows) ? array_keys($rows[0]) : [
            'Folder',
            'Program',
            'Request Date',
            'NIIN',
            'Part',
            'Nomen',
            'Purchase Type',
            'Qty Requested',
            'Requested By',
            'Status',
            'Purchase Vehicle',
            'Item Cost (each)',
            'Extended Cost',
            'Quote Request Date',
            'Date Submitted',
            'Contract Number',
            'Clin Number',
            'Quote Number',
            'PO Number',
            'Qty Ordered',
            'Award Date',
            'EDD Date',
            'Receive Date',
            'Comments'
        ];
        
        xlsx_helper::download(
            'procurements_' . date('Y-m-d') . '.xlsx',
            $headers,
            $rows,
            ['NIIN', 'Part', 'Contract Number', 'Quote Number', 'PO Number'],
            'Procurements'
            );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procurements</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 20px;
            background: #f8f9fa;
            color: #212529;
        }

        .page-wrap {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
        }

        .page-title {
            margin: 0 0 15px 0;
        }

        .tab-bar {
            display: flex;
            gap: 6px;
            align-items: flex-end;
            margin-bottom: 0;
        }

        .tab-link {
            display: inline-block;
            padding: 12px 20px;
            text-decoration: none;
            color: #212529;
            background: #d9dee3;
            border: 1px solid #bfc7cf;
            border-bottom: none;
            border-radius: 10px 10px 0 0;
            font-weight: bold;
        }

        .tab-link:hover {
            background: #e7ebef;
        }

        .tab-link.active {
            background: #ffffff;
            position: relative;
            top: 1px;
            z-index: 2;
        }

        .tab-content {
            background: #ffffff;
            border: 1px solid #bfc7cf;
            border-radius: 0 8px 8px 8px;
            padding: 20px;
            min-height: 500px;
            box-sizing: border-box;
        }

        .tab-content h2 {
            margin-top: 0;
            margin-bottom: 10px;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .search-box input {
            padding: 8px 10px;
            width: 300px;
            max-width: 100%;
            font-size: 14px;
        }

        .export-btn {
            padding: 9px 14px;
            background: #198754;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }

        .export-btn:hover {
            background: #157347;
        }

        .top-scroll {
            overflow-x: auto;
            overflow-y: hidden;
            height: 18px;
            margin-bottom: 6px;
            background: #fff;
            border: 1px solid #ddd;
            border-bottom: none;
        }

        .top-scroll-inner {
            height: 1px;
        }

        .table-wrap {
            overflow: auto;
            background: white;
            border: 1px solid #ddd;
            max-height: 75vh;
        }

        .tab-content table {
            border-collapse: collapse;
            width: max-content;
            min-width: 100%;
            table-layout: auto;
        }

        .tab-content th,
        .tab-content td {
            white-space: nowrap;
        }

        .tab-content th {
            background: #2c3e50;
            color: white;
            padding: 10px;
            text-align: left;
            cursor: pointer;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .tab-content th:hover {
            background: #1f2d3a;
        }

        .tab-content td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        .tab-content tbody tr:nth-child(even) {
            background: #f4f6f8;
        }

        .tab-content tbody tr:hover {
            background: #eaf2ff;
        }

        .sort-indicator {
            margin-left: 6px;
            font-size: 12px;
        }

        .no-results {
            text-align: center;
            padding: 20px;
            display: none;
            font-style: italic;
        }
    </style>
</head>
<body>
<?php include 'menu.php'; ?>

<div class="page-wrap">
    <h1 class="page-title">Procurements</h1>

    <div class="tab-bar">
        <a class="tab-link <?= $selectedTab === 'procurements' ? 'active' : '' ?>"
           href="procurements.php?tab=procurements">All Procurements</a>

        <a class="tab-link <?= $selectedTab === 'backorder_procurements' ? 'active' : '' ?>"
           href="procurements.php?tab=backorder_procurements">Backorder Procurements</a>
    </div>

    <div class="tab-content">
        <?php
        switch ($selectedTab) {
            case 'backorder_procurements':
                require_once APP_ROOT . '/bin/Tabs/procure_backorderprocurements.php';
                break;
            case 'procurements':
            default:
                require_once APP_ROOT . '/bin/Tabs/procure_procurements.php';
                break;
        }
        ?>
    </div>
</div>

</body>
</html>
