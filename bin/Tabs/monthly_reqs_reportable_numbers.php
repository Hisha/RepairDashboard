<?php
require_once APP_ROOT . '/bin/Model/MonthlyReportableNumbers.php';

$model = new MonthlyReportableNumbers();

$availableMonths = $model->getAvailableMonths();
$selectedMonth = $_GET['report_month'] ?? ($availableMonths[0]['month_value'] ?? '');

$data = $selectedMonth !== ''
    ? $model->getMonthlyReportableNumbers($selectedMonth)
    : [];
    
    $selectedMonthLabel = '';
    foreach ($availableMonths as $month) {
        if ($month['month_value'] === $selectedMonth) {
            $selectedMonthLabel = $month['month_label'];
            break;
        }
    }
    ?>

<style>
.reportable-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 70vh;
    border: 1px solid #ddd;
    background: #fff;
}

.reportable-table {
    width: 100%;
    min-width: 1100px;
    border-collapse: collapse;
}

.reportable-table th,
.reportable-table td {
    padding: 8px 10px;
    border: 1px solid #ddd;
    text-align: left;
    white-space: nowrap;
}

.reportable-table thead th {
    position: sticky;
    top: 0;
    background: #f1f3f5;
    z-index: 3;
}

.reportable-table tbody tr:nth-child(even) {
    background: #fafafa;
}

.number-cell {
    text-align: right;
}

.filter-summary {
    margin: 10px 0 15px 0;
    padding: 8px 10px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.export-link {
    display: inline-block;
    margin-bottom: 15px;
    padding: 8px 12px;
    text-decoration: none;
    background: #0d6efd;
    color: #fff;
    border-radius: 4px;
}

.export-link:hover {
    background: #0b5ed7;
}
</style>

<form method="get" action="monthly_reqs.php" style="margin-bottom: 15px;">
    <input type="hidden" name="tab" value="reportable_numbers">
    <input type="hidden" name="fy" value="<?= htmlspecialchars((string)$fyRange['fiscal_year']) ?>">

    <label for="report_month"><strong>Month:</strong></label>
    <select name="report_month" id="report_month" onchange="this.form.submit()">
        <?php foreach ($availableMonths as $month): ?>
            <option value="<?= htmlspecialchars($month['month_value']) ?>"
                <?= $month['month_value'] === $selectedMonth ? 'selected' : '' ?>>
                <?= htmlspecialchars($month['month_label']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<a class="export-link"
   href="monthly_reqs.php?tab=reportable_numbers&fy=<?= urlencode((string)$fyRange['fiscal_year']) ?>&report_month=<?= urlencode($selectedMonth) ?>&export=xlsx">
    Export Excel
</a>

<div class="filter-summary">
    <strong>Selected Month:</strong>
    <?= htmlspecialchars($selectedMonthLabel !== '' ? $selectedMonthLabel : 'None') ?>
    <br>
    <strong>Programs:</strong> <?= number_format(count($data)) ?>
</div>

<div class="reportable-table-wrap">
    <table class="reportable-table">
        <thead>
            <tr>
                <th>Program</th>
                <th>Shipment Count</th>
                <th>Shipped Qty</th>
                <th>Receipt Count</th>
                <th>Receipt Qty</th>
                <th>Canceled Reqs</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($data)): ?>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Program']) ?></td>
                        <td class="number-cell"><?= number_format((float)$row['Shipment Count']) ?></td>
                        <td class="number-cell"><?= number_format((float)$row['Shipped Qty']) ?></td>
                        <td class="number-cell"><?= number_format((float)$row['Receipt Count']) ?></td>
                        <td class="number-cell"><?= number_format((float)$row['Receipt Qty']) ?></td>
                        <td class="number-cell"><?= number_format((float)$row['Canceled Reqs']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No data found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>