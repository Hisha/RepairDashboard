<?php
require_once APP_ROOT . '/bin/Model/Repairs.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

$repairsModel = new Repairs();
$selectedFiscalYear = isset($_GET['fy']) ? (int)$_GET['fy'] : null;
$fyRange = helpers::getFiscalYearDateRange($selectedFiscalYear);

$data = $repairsModel->getRepairPriorityReport(
    $fyRange['start_date'],
    $fyRange['end_date']
    );

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=repair_priority_' . $fyRange['label'] . '.csv');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, [
        'NIIN',
        'Program',
        'A OnHand',
        'D OnHand',
        'G OnHand',
        'F OnHand',
        'F Awaiting Vendor',
        'LastShipDate',
        'Quarterly Demand',
        'Status'
    ]);
    
    foreach ($data as $row) {
        $aOnHand = (float)($row['A OnHand'] ?? 0);
        $gOnHand = (float)($row['G OnHand'] ?? 0);
        $quarterlyDemand = (float)($row['Quarterly Demand'] ?? 0);
        
        if ($aOnHand > $quarterlyDemand) {
            $status = 'Green';
        } elseif ($aOnHand == $quarterlyDemand) {
            $status = 'Yellow';
        } elseif (($aOnHand + $gOnHand) > $quarterlyDemand) {
            $status = 'Purple';
        } else {
            $status = 'Red';
        }
        
        fputcsv($output, [
            $row['NIIN'],
            $row['Program'],
            $row['A OnHand'],
            $row['D OnHand'],
            $row['G OnHand'],
            $row['F OnHand'],
            $row['F Awaiting Vendor'],
            $row['LastShipDate'],
            $row['Quarterly Demand'],
            $status
        ]);
    }
    
    fclose($output);
    exit;
}
?>

<style>
.repair-priority-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 70vh;
    border: 1px solid #ddd;
    background: #fff;
}

.repair-priority-table {
    width: 100%;
    min-width: 1200px;
    border-collapse: collapse;
}

.repair-priority-table th,
.repair-priority-table td {
    padding: 8px 10px;
    border: 1px solid #ddd;
    text-align: left;
    white-space: nowrap;
}

.repair-priority-table thead th {
    position: sticky;
    top: 0;
    background: #f1f3f5;
    z-index: 3;
}

.repair-priority-table tbody tr:nth-child(even) {
    background: #fafafa;
}

.number-cell {
    text-align: right;
}

.status-red td {
    background: #f8d7da !important;
}

.status-yellow td {
    background: #fff3cd !important;
}

.status-green td {
    background: #d1e7dd !important;
}

.status-purple td {
    background: #e2d9f3 !important;
}

.priority-legend {
    margin: 10px 0 15px 0;
    font-size: 14px;
}

.legend-item {
    display: inline-block;
    padding: 3px 8px;
    margin: 0 4px;
    border-radius: 4px;
    font-weight: bold;
}

.legend-red {
    background: #f8d7da;
}

.legend-yellow {
    background: #fff3cd;
}

.legend-green {
    background: #d1e7dd;
}

.legend-purple {
    background: #e2d9f3;
}

.priority-legend {
    padding: 8px 10px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}
</style>

<h2><?= htmlspecialchars($fyRange['label']) ?> Repair Priority</h2>

<p class="priority-legend">
    <strong>Legend:</strong>
    <span class="legend-item legend-red">Red</span> = A OnHand below Quarterly Demand |
    <span class="legend-item legend-yellow">Yellow</span> = A OnHand equals Quarterly Demand |
    <span class="legend-item legend-green">Green</span> = A OnHand above Quarterly Demand |
    <span class="legend-item legend-purple">Purple</span> = A OnHand + G OnHand covers Quarterly Demand
</p>

<?php if (empty($data)): ?>
    <p>No data found.</p>
<?php else: ?>
<div class="repair-priority-table-wrap">
    <table class="repair-priority-table">
        <thead>
        <tr>
            <th>NIIN</th>
            <th>Program</th>
            <th>A OnHand</th>
            <th>D OnHand</th>
            <th>G OnHand</th>
            <th>F OnHand</th>
            <th>F Awaiting Vendor</th>
            <th>Last Ship Date</th>
            <th>Quarterly Demand</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $row): ?>
            <?php
            $aOnHand = (float)($row['A OnHand'] ?? 0);
            $gOnHand = (float)($row['G OnHand'] ?? 0);
            $quarterlyDemand = (float)($row['Quarterly Demand'] ?? 0);

            if ($aOnHand > $quarterlyDemand) {
                $rowClass = 'status-green';
                $status = 'Green';
            } elseif ($aOnHand == $quarterlyDemand) {
                $rowClass = 'status-yellow';
                $status = 'Yellow';
            } elseif (($aOnHand + $gOnHand) > $quarterlyDemand) {
                $rowClass = 'status-purple';
                $status = 'Purple';
            } else {
                $rowClass = 'status-red';
                $status = 'Red';
            }
            ?>
            <tr class="<?= htmlspecialchars($rowClass) ?>">
                <td><?= htmlspecialchars($row['NIIN']) ?></td>
                <td><?= htmlspecialchars($row['Program']) ?></td>
                <td class="number-cell"><?= number_format($aOnHand, 2) ?></td>
                <td class="number-cell"><?= number_format((float)$row['D OnHand'], 2) ?></td>
                <td class="number-cell"><?= number_format($gOnHand, 2) ?></td>
                <td class="number-cell"><?= number_format((float)$row['F OnHand'], 2) ?></td>
                <td class="number-cell"><?= number_format((float)$row['F Awaiting Vendor'], 2) ?></td>
                <td><?= htmlspecialchars($row['LastShipDate']) ?></td>
                <td class="number-cell"><?= number_format($quarterlyDemand, 2) ?></td>
                <td><?= htmlspecialchars($status) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>