<?php
require_once APP_ROOT . '/bin/Model/Repairs.php';

$repairsModel = new Repairs();
$data = $repairsModel->getRepairSheetCandidates();
?>

<style>
.repair-sheet-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 70vh;
    border: 1px solid #ddd;
    background: #fff;
}

.repair-sheet-table {
    width: 100%;
    min-width: 1000px;
    border-collapse: collapse;
}

.repair-sheet-table th,
.repair-sheet-table td {
    padding: 8px 10px;
    border: 1px solid #ddd;
    text-align: left;
    white-space: nowrap;
}

.repair-sheet-table thead th {
    position: sticky;
    top: 0;
    background: #f1f3f5;
    z-index: 3;
}

.repair-sheet-table tbody tr:nth-child(even) {
    background: #fafafa;
}

.number-cell {
    text-align: right;
}

.sheet-btn {
    display: inline-block;
    padding: 7px 10px;
    background: #0d6efd;
    color: #fff;
    text-decoration: none;
    border-radius: 4px;
}

.sheet-btn:hover {
    background: #0b5ed7;
}

.filter-summary {
    margin: 10px 0 15px 0;
    padding: 8px 10px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}
</style>

<h2>Repair Sheets</h2>

<div class="filter-summary">
    <strong>Rows:</strong> <?= number_format(count($data)) ?><br>
    <strong>Criteria:</strong> A OnHand below Quarterly Demand and repairable D/F/G inventory exists.
</div>

<div class="repair-sheet-table-wrap">
    <table class="repair-sheet-table">
        <thead>
            <tr>
                <th>NIIN</th>
                <th>Quarterly Demand</th>
                <th>A OnHand</th>
                <th>Repairable Qty</th>
                <th>Last Ship Date</th>
                <th>Program</th>
                <th>Repair Sheet</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($data)): ?>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['NIIN']) ?></td>
                        <td class="number-cell"><?= number_format((float)$row['Quarterly Demand'], 2) ?></td>
                        <td class="number-cell"><?= number_format((float)$row['A OnHand'], 0) ?></td>
                        <td class="number-cell"><?= number_format((float)$row['Repairable Qty'], 0) ?></td>
                        <td><?= htmlspecialchars($row['LastShipDate']) ?></td>
                        <td><?= htmlspecialchars($row['Program']) ?></td>
                        <td>
                            <a class="sheet-btn"
                               target="_blank"
                               href="repair_sheet.php?niin=<?= urlencode($row['NIIN']) ?>">
                                Output Repair Sheet
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No repair sheet candidates found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>