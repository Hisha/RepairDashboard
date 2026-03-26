<?php
require_once __DIR__ . "/bootstrap.php";
include 'menu.php';
require_once APP_ROOT . "/bin/Model/Cog7Repairables.php";

$model = new Cog7Repairables();
$rows = $model->getSummary12M();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>COG 7 Repairables</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h1 {
            margin-bottom: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 14px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f2f2f2;
            position: sticky;
            top: 0;
        }

        tr:nth-child(even) {
            background: #fafafa;
        }

        .bad {
            background-color: #ffd6d6;
        }

        .warn {
            background-color: #fff3cd;
        }
    </style>
</head>
<body>

<h1>COG 7 Repairables - Last 12 Months</h1>

<table>
    <thead>
        <tr>
            <th>NIIN</th>
            <th>LRC</th>
			<th>Std Price</th>
			<th>12M Ship</th>
            <th>12M Receipt</th>
            <th>12M Repair</th>
            <th>Fielded Base</th>
            <th>Return Rate</th>
            <th>Repair Rate</th>
            <th>Pipeline Delta</th>
            <th>On Hand</th>
            <th>Last Ship Date</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
            <?php
                $returnClass = '';
                if ($row['return_rate'] !== null && $row['return_rate'] > 0.15) {
                    $returnClass = 'bad';
                }

                $repairClass = '';
                if ($row['repair_rate'] !== null && $row['repair_rate'] < 0.60) {
                    $repairClass = 'warn';
                }
            ?>
            <tr>
                <td><?= htmlspecialchars($row['niin']) ?></td>
                <td><?= htmlspecialchars($row['lrc']) ?></td>
				<td><?= number_format($row['std_price'], 2) ?></td>
                <td><?= (int)$row['ship_qty_12m'] ?></td>
                <td><?= (int)$row['receipt_qty_12m'] ?></td>
                <td><?= (int)$row['repair_qty_12m'] ?></td>
                <td><?= (int)$row['fielded_base'] ?></td>
                <td class="<?= $returnClass ?>">
                    <?= $row['return_rate'] !== null ? number_format($row['return_rate'] * 100, 2) . '%' : '' ?>
                </td>
                <td class="<?= $repairClass ?>">
                    <?= $row['repair_rate'] !== null ? number_format($row['repair_rate'] * 100, 2) . '%' : 'N/A' ?>
                </td>
                <td><?= (int)$row['pipeline_delta'] ?></td>
                <td><?= (int)$row['on_hand'] ?></td>
                <td><?= htmlspecialchars($row['last_ship_date'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>