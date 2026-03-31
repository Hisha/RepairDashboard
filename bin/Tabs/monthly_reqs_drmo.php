<?php
require_once APP_ROOT . '/bin/Model/DRMO.php';

$drmoModel = new DRMO();

$availableMonths = $drmoModel->getAvailableDrmoMonths();
$selectedDrmoMonth = $_GET['drmo_month'] ?? ($availableMonths[0]['month_value'] ?? '');

$data = $selectedDrmoMonth !== ''
    ? $drmoModel->getDRMOByMonth($selectedDrmoMonth)
    : [];
    
    $selectedDrmoMonthLabel = '';
    foreach ($availableMonths as $month) {
        if ($month['month_value'] === $selectedDrmoMonth) {
            $selectedDrmoMonthLabel = $month['month_label'];
            break;
        }
    }
    ?>

<style>
.drmo-data-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 70vh;
    border: 1px solid #ddd;
    background: #fff;
}

.drmo-data-table {
    width: 100%;
    min-width: 1100px;
    border-collapse: collapse;
}

.drmo-data-table th,
.drmo-data-table td {
    padding: 8px 10px;
    border: 1px solid #ddd;
    text-align: left;
    white-space: nowrap;
}

.drmo-data-table thead th {
    position: sticky;
    top: 0;
    background: #f1f3f5;
    z-index: 3;
}

.drmo-data-table tbody tr:nth-child(even) {
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
    <input type="hidden" name="tab" value="drmo">
    <input type="hidden" name="fy" value="<?= htmlspecialchars((string)$fyRange['fiscal_year']) ?>">

    <label for="drmo_month"><strong>Month:</strong></label>
    <select name="drmo_month" id="drmo_month" onchange="this.form.submit()">
        <?php foreach ($availableMonths as $month): ?>
            <option value="<?= htmlspecialchars($month['month_value']) ?>"
                <?= $month['month_value'] === $selectedDrmoMonth ? 'selected' : '' ?>>
                <?= htmlspecialchars($month['month_label']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<a class="export-link"
   href="monthly_reqs.php?tab=drmo&fy=<?= urlencode((string)$fyRange['fiscal_year']) ?>&drmo_month=<?= urlencode($selectedDrmoMonth) ?>&export=xlsx">
    Export Excel
</a>

<div class="filter-summary">
    <strong>Selected Month:</strong>
    <?= htmlspecialchars($selectedDrmoMonthLabel !== '' ? $selectedDrmoMonthLabel : 'None') ?>
    <br>
    <strong>Rows:</strong> <?= number_format(count($data)) ?>
</div>

<div class="drmo-data-table-wrap">
    <table class="drmo-data-table">
        <thead>
            <tr>
                <th>Transaction Date</th>
                <th>NIIN</th>
                <th>Part</th>
                <th>Nomen</th>
                <th>Program</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Document Number</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($data)): ?>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Transaction Date']) ?></td>
                        <td><?= htmlspecialchars($row['NIIN']) ?></td>
                        <td><?= htmlspecialchars($row['Part']) ?></td>
                        <td><?= htmlspecialchars($row['Nomen']) ?></td>
                        <td><?= htmlspecialchars($row['Program']) ?></td>
                        <td class="number-cell"><?= number_format((float)$row['Qty']) ?></td>
                        <td class="number-cell">$<?= number_format((float)$row['Unit Price'], 2) ?></td>
                        <td><?= htmlspecialchars($row['Document Number']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No data found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>