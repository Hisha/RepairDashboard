<?php
require_once APP_ROOT . '/bin/Model/Repairs.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

$repairsModel = new Repairs();
$selectedFiscalYear = isset($_GET['fy']) ? (int)$_GET['fy'] : null;
$fyRange = helpers::getFiscalYearDateRange($selectedFiscalYear);

$view = (isset($_GET['view']) && $_GET['view'] === 'weekly') ? 'weekly' : 'full';

// Weekly: request date back one week. Full: fiscal year range.
if ($view === 'weekly') {
    $endDate    = date('Y-m-d');
    $startDate  = date('Y-m-d', strtotime('-7 days'));
    $rangeLabel = $startDate . ' – ' . $endDate . ' (last 7 days)';
    $heading    = 'Weekly Tech Repairs';
} else {
    $startDate  = $fyRange['start_date'];
    $endDate    = $fyRange['end_date'];
    $rangeLabel = $fyRange['label'] . ' (' . $startDate . ' – ' . $endDate . ')';
    $heading    = $fyRange['label'] . ' Tech Repairs';
}

$data = $repairsModel->getRepairsByFiscalYear($startDate, $endDate);

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

$baseQuery = 'tab=tech_repairs&fy=' . urlencode((string)$fyRange['fiscal_year']);
$fullUrl   = 'monthly_tech.php?' . $baseQuery;
$weeklyUrl = 'monthly_tech.php?' . $baseQuery . '&view=weekly';
?>

<style>
.tech-repairs-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 12px;
}

.tech-repairs-toolbar h2 {
    margin: 0;
}

.view-toggle {
    display: flex;
    gap: 8px;
}

.view-btn {
    display: inline-block;
    padding: 8px 14px;
    font-size: 14px;
    font-weight: bold;
    text-decoration: none;
    border-radius: 4px;
    border: 1px solid #0d6efd;
    color: #0d6efd;
    background: #fff;
}

.view-btn:hover {
    background: #e7f1ff;
}

.view-btn.active {
    background: #0d6efd;
    color: #fff;
}

.range-info {
    margin: 0 0 14px 0;
    padding: 8px 12px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
}

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
    min-width: <?= $view === 'weekly' ? '420px' : '1400px' ?>;
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

.tech-repairs-table tbody tr:nth-child(even):not(.program-header-row):not(.subtotal-row):not(.grand-total-row) {
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

<div class="tech-repairs-toolbar">
    <h2><?= htmlspecialchars($heading) ?></h2>
    <div class="view-toggle">
        <a class="view-btn <?= $view === 'full' ? 'active' : '' ?>"
           href="<?= htmlspecialchars($fullUrl) ?>">Full Detail</a>
        <a class="view-btn <?= $view === 'weekly' ? 'active' : '' ?>"
           href="<?= htmlspecialchars($weeklyUrl) ?>">Weekly Count</a>
    </div>
</div>

<div class="range-info">
    <strong>Date range:</strong> <?= htmlspecialchars($rangeLabel) ?>
    <?php if ($view === 'weekly'): ?>
        &nbsp;|&nbsp; High-level summary by Program (A Reps &amp; Total Value only)
    <?php endif; ?>
</div>

<?php if (empty($groupedData)): ?>
    <p>No data found.</p>
<?php elseif ($view === 'weekly'): ?>
<div class="tech-repairs-table-wrap">
    <table class="tech-repairs-table">
        <thead>
        <tr>
            <th>Program</th>
            <th class="number-cell">A Reps</th>
            <th class="number-cell">Value</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($groupedData as $program => $programData): ?>
            <tr>
                <td><?= htmlspecialchars($program) ?></td>
                <td class="number-cell"><?= number_format($programData['subtotal']['A Reps']) ?></td>
                <td class="number-cell">$<?= number_format($programData['subtotal']['Total Value'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr class="grand-total-row">
            <td>Totals:</td>
            <td class="number-cell"><?= number_format($grandTotals['A Reps']) ?></td>
            <td class="number-cell">$<?= number_format($grandTotals['Total Value'], 2) ?></td>
        </tr>
        </tbody>
    </table>
</div>
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
