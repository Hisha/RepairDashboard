<?php
require_once APP_ROOT . '/bin/Model/Repairs.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

$repairsModel = new Repairs();
$selectedFiscalYear = isset($_GET['fy']) ? (int)$_GET['fy'] : null;
$fyRange = helpers::getFiscalYearDateRange($selectedFiscalYear);

$data = $repairsModel->getRepairsByFiscalYear(
    $fyRange['start_date'],
    $fyRange['end_date']
    );

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
        $groupedData[$program]['subtotal'][$key] += (float)$row[$key];
        $grandTotals[$key] += (float)$row[$key];
    }
}

?>

<style>
.tech-repairs-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 70vh;
    border: 1px solid #ddd;
    background: #fff;
}

.tech-repairs-table {
    width: 100%;
    min-width: 1400px;
    border-collapse: collapse;
}

.tech-repairs-table th,
.tech-repairs-table td {
    padding: 8px 10px;
    border: 1px solid #ddd;
    text-align: left;
    white-space: nowrap;
}

.tech-repairs-table thead th {
    position: sticky;
    top: 0;
    background: #f1f3f5;
    z-index: 3;
}

.tech-repairs-table tbody tr:nth-child(even) {
    background: #fafafa;
}

.program-header-row td {
    background: #dbe7f3 !important;
    font-weight: bold;
    font-size: 15px;
    border-top: 2px solid #adb5bd;
}

.subtotal-row td {
    background: #e9ecef !important;
    font-weight: bold;
}

.grand-total-row td {
    background: #ced4da !important;
    font-weight: bold;
}

.number-cell {
    text-align: right;
}
</style>

<h2><?= htmlspecialchars($fyRange['label']) ?> Tech Repairs</h2>

<?php if (empty($groupedData)): ?>
    <p>No data found.</p>
<?php else: ?>
<div class="tech-repairs-table-wrap">
    <table class="tech-repairs-table">
        <thead>
        <tr>
            <th>NIIN</th>
            <th>Program</th>
            <th>STD Price</th>
            <th>A Reps</th>
            <th>A Hours</th>
            <th>D Reps</th>
            <th>D Hours</th>
            <th>F Reps</th>
            <th>F Hours</th>
            <th>G Reps</th>
            <th>G Hours</th>
            <th>H Reps</th>
            <th>H Hours</th>
            <th>Total Value</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($groupedData as $program => $programData): ?>
            <tr class="program-header-row">
                <td colspan="14"><?= htmlspecialchars($program) ?></td>
            </tr>

            <?php foreach ($programData['rows'] as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['NIIN']) ?></td>
                    <td><?= htmlspecialchars($row['Program']) ?></td>
                    <td class="number-cell">$<?= number_format((float)$row['STD Price'], 2) ?></td>
                    <td class="number-cell"><?= number_format((float)$row['A Reps']) ?></td>
                    <td class="number-cell"><?= number_format((float)$row['A Hours'], 2) ?></td>
                    <td class="number-cell"><?= number_format((float)$row['D Reps']) ?></td>
                    <td class="number-cell"><?= number_format((float)$row['D Hours'], 2) ?></td>
                    <td class="number-cell"><?= number_format((float)$row['F Reps']) ?></td>
                    <td class="number-cell"><?= number_format((float)$row['F Hours'], 2) ?></td>
                    <td class="number-cell"><?= number_format((float)$row['G Reps']) ?></td>
                    <td class="number-cell"><?= number_format((float)$row['G Hours'], 2) ?></td>
                    <td class="number-cell"><?= number_format((float)$row['H Reps']) ?></td>
                    <td class="number-cell"><?= number_format((float)$row['H Hours'], 2) ?></td>
                    <td class="number-cell">$<?= number_format((float)$row['Total Value'], 2) ?></td>
                </tr>
            <?php endforeach; ?>

            <tr class="subtotal-row">
                <td></td>
                <td><?= htmlspecialchars($program) ?> Subtotal</td>
                <td></td>
                <td class="number-cell"><?= number_format($programData['subtotal']['A Reps']) ?></td>
                <td class="number-cell"><?= number_format($programData['subtotal']['A Hours'], 2) ?></td>
                <td class="number-cell"><?= number_format($programData['subtotal']['D Reps']) ?></td>
                <td class="number-cell"><?= number_format($programData['subtotal']['D Hours'], 2) ?></td>
                <td class="number-cell"><?= number_format($programData['subtotal']['F Reps']) ?></td>
                <td class="number-cell"><?= number_format($programData['subtotal']['F Hours'], 2) ?></td>
                <td class="number-cell"><?= number_format($programData['subtotal']['G Reps']) ?></td>
                <td class="number-cell"><?= number_format($programData['subtotal']['G Hours'], 2) ?></td>
                <td class="number-cell"><?= number_format($programData['subtotal']['H Reps']) ?></td>
                <td class="number-cell"><?= number_format($programData['subtotal']['H Hours'], 2) ?></td>
                <td class="number-cell">$<?= number_format($programData['subtotal']['Total Value'], 2) ?></td>
            </tr>
        <?php endforeach; ?>

        <tr class="grand-total-row">
            <td></td>
            <td>Grand Total</td>
            <td></td>
            <td class="number-cell"><?= number_format($grandTotals['A Reps']) ?></td>
            <td class="number-cell"><?= number_format($grandTotals['A Hours'], 2) ?></td>
            <td class="number-cell"><?= number_format($grandTotals['D Reps']) ?></td>
            <td class="number-cell"><?= number_format($grandTotals['D Hours'], 2) ?></td>
            <td class="number-cell"><?= number_format($grandTotals['F Reps']) ?></td>
            <td class="number-cell"><?= number_format($grandTotals['F Hours'], 2) ?></td>
            <td class="number-cell"><?= number_format($grandTotals['G Reps']) ?></td>
            <td class="number-cell"><?= number_format($grandTotals['G Hours'], 2) ?></td>
            <td class="number-cell"><?= number_format($grandTotals['H Reps']) ?></td>
            <td class="number-cell"><?= number_format($grandTotals['H Hours'], 2) ?></td>
            <td class="number-cell">$<?= number_format($grandTotals['Total Value'], 2) ?></td>
        </tr>
        </tbody>
    </table>
</div>
<?php endif; ?>