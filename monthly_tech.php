<?php
require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/bin/Utilities/xlsx_helper.php';
require_once APP_ROOT . '/bin/Utilities/xlsx_styled_helper.php';
require_once APP_ROOT . '/bin/Model/Repairs.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

$allowedTabs = ['overview', 'tech_numbers', 'tech_numbers_expanded', 'tech_repairs', 'repair_priority', 'battery_tracker', 'repairs_by_month'];
$selectedTab = $_GET['tab'] ?? 'overview';

if (!in_array($selectedTab, $allowedTabs, true)) {
    $selectedTab = 'overview';
}

$repairsModel = new Repairs();
$availableFiscalYears = $repairsModel->getAvailableFiscalYearsDetailed();

$selectedFiscalYear = isset($_GET['fy']) ? (int)$_GET['fy'] : null;
$fyRange = helpers::getFiscalYearDateRange($selectedFiscalYear);

if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    
    switch ($selectedTab) {
        case 'tech_numbers_expanded':
            $rows = $repairsModel->getTechsRepairValueExpanded($fyRange['start_date'], $fyRange['end_date']);
            $filename = 'tech_numbers_expanded_' . date('Y-m-d') . '.xlsx';
            break;
            
        case 'tech_repairs':
            $data = $repairsModel->getRepairsByFiscalYear($fyRange['start_date'], $fyRange['end_date']);
            $filename = 'tech_repairs_' . date('Y-m-d') . '.xlsx';
            
            $groupedData = [];
            $grandTotals = [
                'A Reps' => 0,
                'A Hours' => 0,
                'D Reps' => 0,
                'D Hours' => 0,
                'F Reps' => 0,
                'F Hours' => 0,
                'G Reps' => 0,
                'G Hours' => 0,
                'H Reps' => 0,
                'H Hours' => 0,
                'Total Value' => 0
            ];
            
            foreach ($data as $row) {
                $program = $row['Program'] ?? 'Unknown';
                
                if (!isset($groupedData[$program])) {
                    $groupedData[$program] = [
                        'rows' => [],
                        'subtotal' => [
                            'A Reps' => 0,
                            'A Hours' => 0,
                            'D Reps' => 0,
                            'D Hours' => 0,
                            'F Reps' => 0,
                            'F Hours' => 0,
                            'G Reps' => 0,
                            'G Hours' => 0,
                            'H Reps' => 0,
                            'H Hours' => 0,
                            'Total Value' => 0
                        ]
                    ];
                }
                
                $groupedData[$program]['rows'][] = $row;
                
                foreach ($groupedData[$program]['subtotal'] as $key => $value) {
                    $groupedData[$program]['subtotal'][$key] += (float)($row[$key] ?? 0);
                    $grandTotals[$key] += (float)($row[$key] ?? 0);
                }
            }
            
            $headers = [
                'NIIN',
                'Program',
                'STD Price',
                'A Reps',
                'A Hours',
                'D Reps',
                'D Hours',
                'F Reps',
                'F Hours',
                'G Reps',
                'G Hours',
                'H Reps',
                'H Hours',
                'Total Value'
            ];
            
            $exportRows = [];
            
            foreach ($groupedData as $program => $programData) {
                $exportRows[] = [
                    '_row_type' => 'highlight_blue',
                    'NIIN' => '',
                    'Program' => $program,
                    'STD Price' => '',
                    'A Reps' => '',
                    'A Hours' => '',
                    'D Reps' => '',
                    'D Hours' => '',
                    'F Reps' => '',
                    'F Hours' => '',
                    'G Reps' => '',
                    'G Hours' => '',
                    'H Reps' => '',
                    'H Hours' => '',
                    'Total Value' => ''
                ];
                
                foreach ($programData['rows'] as $row) {
                    $exportRows[] = [
                        '_row_type' => 'normal',
                        'NIIN' => $row['NIIN'] ?? '',
                        'Program' => $row['Program'] ?? '',
                        'STD Price' => (float)($row['STD Price'] ?? 0),
                        'A Reps' => (float)($row['A Reps'] ?? 0),
                        'A Hours' => (float)($row['A Hours'] ?? 0),
                        'D Reps' => (float)($row['D Reps'] ?? 0),
                        'D Hours' => (float)($row['D Hours'] ?? 0),
                        'F Reps' => (float)($row['F Reps'] ?? 0),
                        'F Hours' => (float)($row['F Hours'] ?? 0),
                        'G Reps' => (float)($row['G Reps'] ?? 0),
                        'G Hours' => (float)($row['G Hours'] ?? 0),
                        'H Reps' => (float)($row['H Reps'] ?? 0),
                        'H Hours' => (float)($row['H Hours'] ?? 0),
                        'Total Value' => (float)($row['Total Value'] ?? 0)
                    ];
                }
                
                $exportRows[] = [
                    '_row_type' => 'subtotal',
                    'NIIN' => '',
                    'Program' => $program . ' Subtotal',
                    'STD Price' => '',
                    'A Reps' => $programData['subtotal']['A Reps'],
                    'A Hours' => $programData['subtotal']['A Hours'],
                    'D Reps' => $programData['subtotal']['D Reps'],
                    'D Hours' => $programData['subtotal']['D Hours'],
                    'F Reps' => $programData['subtotal']['F Reps'],
                    'F Hours' => $programData['subtotal']['F Hours'],
                    'G Reps' => $programData['subtotal']['G Reps'],
                    'G Hours' => $programData['subtotal']['G Hours'],
                    'H Reps' => $programData['subtotal']['H Reps'],
                    'H Hours' => $programData['subtotal']['H Hours'],
                    'Total Value' => $programData['subtotal']['Total Value']
                ];
            }
            
            $exportRows[] = [
                '_row_type' => 'grand_total',
                'NIIN' => '',
                'Program' => 'Grand Total',
                'STD Price' => '',
                'A Reps' => $grandTotals['A Reps'],
                'A Hours' => $grandTotals['A Hours'],
                'D Reps' => $grandTotals['D Reps'],
                'D Hours' => $grandTotals['D Hours'],
                'F Reps' => $grandTotals['F Reps'],
                'F Hours' => $grandTotals['F Hours'],
                'G Reps' => $grandTotals['G Reps'],
                'G Hours' => $grandTotals['G Hours'],
                'H Reps' => $grandTotals['H Reps'],
                'H Hours' => $grandTotals['H Hours'],
                'Total Value' => $grandTotals['Total Value']
            ];
            
            xlsx_styled_helper::download(
                $filename,
                $headers,
                $exportRows,
                [
                    'sheetTitle' => 'Tech Repairs',
                    'textColumns' => ['NIIN'],
                    'numberFormats' => [
                        'STD Price' => '$#,##0.00',
                        'A Reps' => '0',
                        'A Hours' => '0.00',
                        'D Reps' => '0',
                        'D Hours' => '0.00',
                        'F Reps' => '0',
                        'F Hours' => '0.00',
                        'G Reps' => '0',
                        'G Hours' => '0.00',
                        'H Reps' => '0',
                        'H Hours' => '0.00',
                        'Total Value' => '$#,##0.00'
                    ]
                ]
                );
            break;
            
        case 'repair_priority':
            $data = $repairsModel->getRepairPriorityReport($fyRange['start_date'], $fyRange['end_date']);
            
            $search = trim((string)($_GET['rp_search'] ?? ''));
            $programFilter = trim((string)($_GET['rp_program'] ?? ''));
            $statusFilter = trim((string)($_GET['rp_status'] ?? ''));
            $minDemandFilter = (float)($_GET['rp_min_demand'] ?? 0);
            
            $filteredData = array_filter($data, function ($row) use ($search, $programFilter, $statusFilter, $minDemandFilter) {
                $niin = strtolower(trim((string)($row['NIIN'] ?? '')));
                $program = strtolower(trim((string)($row['Program'] ?? '')));
                
                $aOnHand = (float)($row['A OnHand'] ?? 0);
                $dOnHand = (float)($row['D OnHand'] ?? 0);
                $gOnHand = (float)($row['G OnHand'] ?? 0);
                $quarterlyDemand = (float)($row['Quarterly Demand'] ?? 0);
                
                if ($aOnHand > $quarterlyDemand) {
                    $rowStatus = 'status-green';
                } elseif ($aOnHand == $quarterlyDemand) {
                    $rowStatus = 'status-yellow';
                } elseif (($aOnHand + $dOnHand + $gOnHand) > $quarterlyDemand) {
                    $rowStatus = 'status-purple';
                } else {
                    $rowStatus = 'status-red';
                }
                
                if ($search !== '' && !str_contains($niin, strtolower($search)) && !str_contains($program, strtolower($search))) {
                    return false;
                }
                
                if ($programFilter !== '' && $program !== strtolower($programFilter)) {
                    return false;
                }
                
                if ($statusFilter !== '' && $rowStatus !== $statusFilter) {
                    return false;
                }
                
                if ($quarterlyDemand < $minDemandFilter) {
                    return false;
                }
                
                return true;
            });
                
                $filename = 'repair_priority_' . date('Y-m-d') . '.xlsx';
                
                $headers = [
                    'NIIN',
                    'Quarterly Demand',
                    'A OnHand',
                    'D OnHand',
                    'G OnHand',
                    'F OnHand',
                    'F Awaiting Vendor',
                    'Last Ship Date',
                    'Program'
                ];
                
                $exportRows = [];
                
                foreach ($filteredData as $row) {
                    $aOnHand = (float)($row['A OnHand'] ?? 0);
                    $dOnHand = (float)($row['D OnHand'] ?? 0);
                    $gOnHand = (float)($row['G OnHand'] ?? 0);
                    $quarterlyDemand = (float)($row['Quarterly Demand'] ?? 0);
                    
                    if ($aOnHand > $quarterlyDemand) {
                        $rowType = 'highlight_green';
                    } elseif ($aOnHand == $quarterlyDemand) {
                        $rowType = 'highlight_yellow';
                    } elseif (($aOnHand + $dOnHand + $gOnHand) > $quarterlyDemand) {
                        $rowType = 'highlight_purple';
                    } else {
                        $rowType = 'highlight_red';
                    }
                    
                    $exportRows[] = [
                        '_row_type' => $rowType,
                        'NIIN' => $row['NIIN'] ?? '',
                        'Quarterly Demand' => $quarterlyDemand,
                        'A OnHand' => $aOnHand,
                        'D OnHand' => $dOnHand,
                        'G OnHand' => $gOnHand,
                        'F OnHand' => (float)($row['F OnHand'] ?? 0),
                        'F Awaiting Vendor' => (float)($row['F Awaiting Vendor'] ?? 0),
                        'Last Ship Date' => $row['LastShipDate'] ?? '',
                        'Program' => $row['Program'] ?? ''
                    ];
                }
                
                xlsx_styled_helper::download(
                    $filename,
                    $headers,
                    $exportRows,
                    [
                        'sheetTitle' => 'Repair Priority',
                        'textColumns' => ['NIIN'],
                        'numberFormats' => [
                            'Quarterly Demand' => '0.00',
                            'A OnHand' => '0',
                            'D OnHand' => '0',
                            'G OnHand' => '0',
                            'F OnHand' => '0',
                            'F Awaiting Vendor' => '0'
                        ]
                    ]
                    );
                break;
            
        case 'battery_tracker':
            require_once APP_ROOT . '/bin/Model/Batteries.php';
            $batteryModel = new Batteries();
            $rows = $batteryModel->getBatteryTracker();
            $filename = 'battery_tracker_' . date('Y-m-d') . '.xlsx';
            break;
            
        case 'repairs_by_month':
            $rows = $repairsModel->getRepairsByMonthAndSubgroup($fyRange['start_date'], $fyRange['end_date']);
            $filename = 'repairs_by_month_' . date('Y-m-d') . '.xlsx';
            break;
            
        default:
            $rows = [];
            $filename = 'export_' . date('Y-m-d') . '.xlsx';
            break;
    }
    
    $headers = [];
    $exportRows = [];
    $textColumns = [];
    $sheetTitle = 'Report';
    
    switch ($selectedTab) {
        case 'tech_numbers_expanded':
            $headers = !empty($rows) ? array_keys($rows[0]) : [];
            $exportRows = $rows;
            $textColumns = ['NIIN', 'Part'];
            $sheetTitle = 'Tech Numbers Expanded';
            break;
            
        case 'tech_repairs':
            $headers = !empty($rows) ? array_keys($rows[0]) : [];
            $exportRows = $rows;
            $textColumns = ['NIIN', 'Part'];
            $sheetTitle = 'Tech Repairs';
            break;
            
        case 'repair_priority':
            $headers = !empty($rows) ? array_keys($rows[0]) : [];
            $exportRows = $rows;
            $textColumns = ['NIIN', 'Part'];
            $sheetTitle = 'Repair Priority';
            break;
            
        case 'battery_tracker':
            $headers = !empty($rows) ? array_keys($rows[0]) : [];
            $exportRows = $rows;
            $textColumns = ['Part'];
            $sheetTitle = 'Battery Tracker';
            break;
            
        case 'repairs_by_month':
            $headers = ['MonthYear', 'SUBGROUPTYPE', 'NIIN', 'Sum of Qty'];
            $textColumns = ['NIIN'];
            $sheetTitle = 'Repairs By Month';
            
            $lastMonth = '';
            $lastSubgroup = '';
            
            foreach ($rows as $row) {
                if ($row['MonthYear'] !== $lastMonth) {
                    $exportRows[] = [
                        'MonthYear' => $row['MonthYear'],
                        'SUBGROUPTYPE' => '',
                        'NIIN' => '',
                        'Sum of Qty' => ''
                    ];
                    $lastMonth = $row['MonthYear'];
                    $lastSubgroup = '';
                }
                
                if ($row['SUBGROUPTYPE'] !== $lastSubgroup) {
                    $exportRows[] = [
                        'MonthYear' => '',
                        'SUBGROUPTYPE' => $row['SUBGROUPTYPE'],
                        'NIIN' => '',
                        'Sum of Qty' => ''
                    ];
                    $lastSubgroup = $row['SUBGROUPTYPE'];
                }
                
                $exportRows[] = [
                    'MonthYear' => '',
                    'SUBGROUPTYPE' => '',
                    'NIIN' => $row['NIIN'],
                    'Sum of Qty' => $row['Qty']
                ];
            }
            break;
            
        default:
            $headers = !empty($rows) ? array_keys($rows[0]) : [];
            $exportRows = $rows;
            $sheetTitle = 'Report';
            break;
    }
    
    xlsx_helper::download(
        $filename,
        $headers,
        $exportRows,
        $textColumns,
        $sheetTitle
        );
}

include 'menu.php';

$exportUrl = 'monthly_tech.php?tab=' . urlencode($selectedTab)
. '&fy=' . urlencode((string)$fyRange['fiscal_year'])
. '&export=xlsx';

if ($selectedTab === 'repair_priority') {
    $exportUrl .= '&rp_search=' . urlencode((string)($_GET['rp_search'] ?? ''));
    $exportUrl .= '&rp_program=' . urlencode((string)($_GET['rp_program'] ?? ''));
    $exportUrl .= '&rp_status=' . urlencode((string)($_GET['rp_status'] ?? ''));
    $exportUrl .= '&rp_min_demand=' . urlencode((string)($_GET['rp_min_demand'] ?? '0'));
}
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

            <?php if (in_array($selectedTab, ['tech_numbers_expanded', 'tech_repairs', 'repair_priority', 'battery_tracker', 'repairs_by_month'], true)): ?>
                <a class="export-link" href="<?= htmlspecialchars($exportUrl) ?>">Export Excel</a>
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
   		   
   		<a class="tab-link <?= $selectedTab === 'battery_tracker' ? 'active' : '' ?>"
   			href="monthly_tech.php?tab=battery_tracker&fy=<?= urlencode((string)$fyRange['fiscal_year']) ?>">Battery Tracker</a>
   			
   		<a class="tab-link <?= $selectedTab === 'repairs_by_month' ? 'active' : '' ?>"
   			href="monthly_tech.php?tab=repairs_by_month&fy=<?= urlencode((string)$fyRange['fiscal_year']) ?>">Repairs by Month</a>
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
                
            case 'battery_tracker':
                require_once APP_ROOT . '/bin/Tabs/monthly_tech_battery_tracker.php';
                break;
                
            case 'repairs_by_month':
                require_once APP_ROOT . '/bin/Tabs/monthly_tech_repairs_by_month.php';
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