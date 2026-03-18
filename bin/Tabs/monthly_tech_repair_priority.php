<?php
require_once APP_ROOT . '/bin/Model/Repairs.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

$repairsModel = new Repairs();
$selectedFiscalYear = isset($_GET['fy']) ? (int)$_GET['fy'] : null;
$selectedNiin = $_GET['niin'] ?? '';
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
        'Quarterly Demand',
        'A OnHand',
        'D OnHand',
        'G OnHand',
        'F OnHand',
        'F Awaiting Vendor',
        'Last Ship Date',
        'Program'
    ]);
    
    foreach ($data as $row) {
        $aOnHand = (float)($row['A OnHand'] ?? 0);
        $dOnHand = (float)($row['D OnHand'] ?? 0);
        $gOnHand = (float)($row['G OnHand'] ?? 0);
        $quarterlyDemand = (float)($row['Quarterly Demand'] ?? 0);
        
        fputcsv($output, [
            $row['NIIN'],
            $quarterlyDemand,
            $aOnHand,
            $dOnHand,
            $gOnHand,
            $row['F OnHand'],
            $row['F Awaiting Vendor'],
            $row['LastShipDate'],
            $row['Program']
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
    margin: 10px 0 15px 0;
    font-size: 14px;
    padding: 8px 10px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.highlight-niin td {
    outline: 3px solid #0d6efd;
    outline-offset: -3px;
    font-weight: bold;
}
</style>

<h2><?= htmlspecialchars($fyRange['label']) ?> Repair Priority</h2>

<p class="priority-legend">
    <strong>Legend:</strong>
    <span class="legend-item legend-red">Red</span> = A OnHand below Quarterly Demand |
    <span class="legend-item legend-yellow">Yellow</span> = A OnHand equals Quarterly Demand |
    <span class="legend-item legend-green">Green</span> = A OnHand above Quarterly Demand |
    <span class="legend-item legend-purple">Purple</span> = A OnHand + D OnHand + G OnHand covers Quarterly Demand
</p>

<?php if (empty($data)): ?>
    <p>No data found.</p>
<?php else: ?>
<div class="repair-priority-table-wrap">
    <table class="repair-priority-table">
        <thead>
		<tr>
    		<th>NIIN</th>
    		<th>Quarterly Demand</th>
    		<th>A OnHand</th>
    		<th>D OnHand</th>
    		<th>G OnHand</th>
    		<th>F OnHand</th>
    		<th>F Awaiting Vendor</th>
    		<th>Last Ship Date</th>
    		<th>Program</th>
		</tr>
		</thead>
        <tbody>
        <?php foreach ($data as $row): ?>
    		<?php
            $aOnHand = (float)($row['A OnHand'] ?? 0);
            $dOnHand = (float)($row['D OnHand'] ?? 0);
            $gOnHand = (float)($row['G OnHand'] ?? 0);
            $quarterlyDemand = (float)($row['Quarterly Demand'] ?? 0);

            if ($aOnHand > $quarterlyDemand) {
                $rowClass = 'status-green';
            } elseif ($aOnHand == $quarterlyDemand) {
                $rowClass = 'status-yellow';
            } elseif (($aOnHand + $dOnHand + $gOnHand) > $quarterlyDemand) {
                $rowClass = 'status-purple';
            } else {
                $rowClass = 'status-red';
            }

            $isHighlighted = ($selectedNiin !== '' && (string)$row['NIIN'] === (string)$selectedNiin);
            $combinedRowClass = $rowClass . ($isHighlighted ? ' highlight-niin' : '');
            ?>
    		<tr class="<?= htmlspecialchars($combinedRowClass) ?>"<?= $isHighlighted ? ' id="selected-niin-row"' : '' ?>>
        		<td><?= htmlspecialchars($row['NIIN']) ?></td>
        		<td class="number-cell"><?= number_format($quarterlyDemand, 2) ?></td>
        		<td class="number-cell"><?= number_format($aOnHand, 0) ?></td>
        		<td class="number-cell"><?= number_format($dOnHand, 0) ?></td>
        		<td class="number-cell"><?= number_format($gOnHand, 0) ?></td>
        		<td class="number-cell"><?= number_format((float)$row['F OnHand'], 0) ?></td>
        		<td class="number-cell"><?= number_format((float)$row['F Awaiting Vendor'], 0) ?></td>
        		<td><?= htmlspecialchars($row['LastShipDate']) ?></td>
        		<td><?= htmlspecialchars($row['Program']) ?></td>
    		</tr>
		<?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($selectedNiin)): ?>
<script>
(() => {
    const row = document.getElementById('selected-niin-row');
    if (!row) {
        return;
    }

    row.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
        inline: 'nearest'
    });
})();
</script>
<?php endif; ?>