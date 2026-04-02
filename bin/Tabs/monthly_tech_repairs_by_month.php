<?php
require_once APP_ROOT . '/bin/Model/Repairs.php';

$repairsModel = new Repairs();
$rows = $repairsModel->getRepairsByMonthAndSubgroup($fyRange['start_date'], $fyRange['end_date']);

$lastMonth = '';
$lastSubgroup = '';
$lastCondition = '';
?>

<style>
.repairs-by-month-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 70vh;
    border: 1px solid #ddd;
    background: #fff;
}

.repairs-by-month-table {
    width: 100%;
    min-width: 1000px;
    border-collapse: collapse;
}

.repairs-by-month-table th,
.repairs-by-month-table td {
    padding: 8px 10px;
    border: 1px solid #ddd;
    text-align: left;
    white-space: nowrap;
}

.repairs-by-month-table thead th {
    position: sticky;
    top: 0;
    background: #f1f3f5;
    z-index: 3;
}

.repairs-by-month-table tbody tr:nth-child(even) {
    background: #fafafa;
}

.number-cell {
    text-align: right;
}

.month-row td {
    font-weight: bold;
    background: #e9ecef !important;
}

.subgroup-row td:nth-child(2) {
    padding-left: 20px;
    font-weight: bold;
}

.condition-row td:nth-child(3) {
    padding-left: 40px;
    font-weight: bold;
}

.part-row td:nth-child(4) {
    padding-left: 60px;
}

.filter-summary {
    margin: 10px 0 15px 0;
    padding: 8px 10px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}
</style>

<div class="filter-summary">
    <strong>Fiscal Year:</strong> <?= htmlspecialchars($fyRange['label']) ?><br>
    <strong>Rows:</strong> <?= number_format(count($rows)) ?>
</div>

<div class="repairs-by-month-table-wrap">
    <table class="repairs-by-month-table">
        <thead>
            <tr>
                <th>MonthYear</th>
                <th>SUBGROUPTYPE</th>
                <th>Condition</th>
                <th>NIIN</th>
                <th>Sum of Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($rows)): ?>
                <?php foreach ($rows as $row): ?>

                    <?php if ($row['MonthYear'] !== $lastMonth): ?>
                        <tr class="month-row">
                            <td><?= htmlspecialchars($row['MonthYear']) ?></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <?php
                        $lastMonth = $row['MonthYear'];
                        $lastSubgroup = '';
                        $lastCondition = '';
                        ?>
                    <?php endif; ?>

                    <?php if ($row['SUBGROUPTYPE'] !== $lastSubgroup): ?>
                        <tr class="subgroup-row">
                            <td></td>
                            <td><?= htmlspecialchars($row['SUBGROUPTYPE']) ?></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <?php
                        $lastSubgroup = $row['SUBGROUPTYPE'];
                        $lastCondition = '';
                        ?>
                    <?php endif; ?>

                    <?php if ($row['Condition'] !== $lastCondition): ?>
                        <tr class="condition-row">
                            <td></td>
                            <td></td>
                            <td><?= htmlspecialchars($row['Condition']) ?></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <?php $lastCondition = $row['Condition']; ?>
                    <?php endif; ?>

                    <tr class="part-row">
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><?= htmlspecialchars($row['NIIN']) ?></td>
                        <td class="number-cell"><?= number_format((float)$row['Qty']) ?></td>
                    </tr>

                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No data found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>