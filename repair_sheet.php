<?php
require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/bin/Model/Repairs.php';
require_once APP_ROOT . '/bin/Model/Inventory.php';

$niin = trim($_GET['niin'] ?? '');

if ($niin === '') {
    die('NIIN is required.');
}

$repairsModel = new Repairs();
$inventoryModel = new Inventory();

$candidates = $repairsModel->getRepairSheetCandidates();

$selected = null;
foreach ($candidates as $row) {
    if ((string)$row['NIIN'] === (string)$niin) {
        $selected = $row;
        break;
    }
}

if ($selected === null) {
    die('No repair sheet candidate found for this NIIN.');
}

$inventoryRows = $inventoryModel->getRepairableInventoryByNIIN($niin);

$quarterlyDemand = (float)($selected['Quarterly Demand'] ?? 0);
$aOnHand = (float)($selected['A OnHand'] ?? 0);
$repairableQty = (float)($selected['Repairable Qty'] ?? 0);

$needed = max(0, ceil($quarterlyDemand - $aOnHand));
$qtyToRepair = min($needed, $repairableQty);

$description = '';

foreach ($inventoryRows as $row) {
    $candidateDescription = trim((string)($row['description'] ?? ''));
    
    if ($candidateDescription !== '') {
        $description = $candidateDescription;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Repair Sheet - <?= htmlspecialchars($niin) ?></title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 30px;
            color: #212529;
        }

        .sheet {
            max-width: 1000px;
            margin: 0 auto;
        }

        h1 {
            margin-bottom: 5px;
        }

        .meta {
            margin: 15px 0 25px 0;
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 8px 12px;
        }

        .label {
            font-weight: bold;
            background: #f1f3f5;
            padding: 8px;
            border: 1px solid #ddd;
        }

        .value {
            padding: 8px;
            border: 1px solid #ddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 8px 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f1f3f5;
        }

        .number-cell {
            text-align: right;
        }

        .print-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 12px;
            background: #0d6efd;
            color: #fff;
            border: 0;
            border-radius: 4px;
            cursor: pointer;
        }

        @media print {
            .print-btn {
                display: none;
            }

            body {
                margin: 0.4in;
            }
        }
    </style>
</head>
<body>
<div class="sheet">
    <button class="print-btn" onclick="window.print()">Print Repair Sheet</button>

    <h1>Repair Sheet</h1>
    <p>Generated: <?= htmlspecialchars(date('Y-m-d H:i')) ?></p>

    <div class="meta">
        <div class="label">NIIN</div>
        <div class="value"><?= htmlspecialchars($niin) ?></div>

        <div class="label">Description / Part</div>
        <div class="value"><?= htmlspecialchars($description) ?></div>

        <div class="label">Program</div>
        <div class="value"><?= htmlspecialchars($selected['Program']) ?></div>

        <div class="label">Quarterly Demand</div>
        <div class="value"><?= number_format($quarterlyDemand, 2) ?></div>

        <div class="label">A OnHand</div>
        <div class="value"><?= number_format($aOnHand, 0) ?></div>

        <div class="label">Last Ship Date</div>
        <div class="value"><?= htmlspecialchars($selected['LastShipDate']) ?></div>

        <div class="label">How Many To Repair</div>
        <div class="value"><strong><?= number_format($qtyToRepair, 0) ?></strong></div>
    </div>

    <h2>Repairable Inventory</h2>

    <table>
        <thead>
            <tr>
                <th>Primary Part No</th>
                <th>Subgroup</th>
                <th>Storage Bin</th>
                <th>Condition</th>
                <th>Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($inventoryRows)): ?>
                <?php foreach ($inventoryRows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['primarypartno']) ?></td>
                        <td><?= htmlspecialchars($row['subgrouptype']) ?></td>
                        <td><?= htmlspecialchars($row['storagebin']) ?></td>
                        <td><?= htmlspecialchars($row['materialcode']) ?></td>
                        <td class="number-cell"><?= number_format((float)$row['onhandqty'], 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No repairable inventory found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>