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
            margin-bottom: 8px;
        }

        .subtext {
            margin-bottom: 20px;
            color: #555;
            font-size: 14px;
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
            vertical-align: top;
        }

        th {
            background: #f2f2f2;
            position: sticky;
            top: 0;
            z-index: 2;
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

        .good {
            background-color: #d9f2d9;
        }

        .num {
            text-align: right;
            white-space: nowrap;
        }

        .center {
            text-align: center;
        }

        .table-wrap {
            overflow-x: auto;
        }
    </style>
</head>
<body>

<h1>Repairables - Last 12 Months</h1>
<div class="subtext">
    Active NIINs are COG 7 items with at least one shipment in the last 24 months.
</div>

<div class="table-wrap">
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
                } elseif ($row['repair_rate'] !== null && $row['repair_rate'] >= 1.00) {
                    $repairClass = 'good';
                }

                $pipelineClass = '';
                if ((int)$row['pipeline_delta'] > 0) {
                    $pipelineClass = 'warn';
                } elseif ((int)$row['pipeline_delta'] < 0) {
                    $pipelineClass = 'good';
                }
            ?>
            <tr>
                <td><?= htmlspecialchars($row['niin']) ?></td>
                <td><?= htmlspecialchars($row['lrc']) ?></td>
                <td class="num"><?= number_format((float)$row['std_price'], 2) ?></td>
                <td class="num"><?= (int)$row['ship_qty_12m'] ?></td>
                <td class="num"><?= (int)$row['receipt_qty_12m'] ?></td>
                <td class="num"><?= (int)$row['repair_qty_12m'] ?></td>
                <td class="num"><?= (int)$row['fielded_base'] ?></td>
                <td class="num <?= $returnClass ?>">
                    <?= $row['return_rate'] !== null ? number_format($row['return_rate'] * 100, 2) . '%' : 'N/A' ?>
                </td>
                <td class="num <?= $repairClass ?>">
                    <?= $row['repair_rate'] !== null ? number_format($row['repair_rate'] * 100, 2) . '%' : 'N/A' ?>
                </td>
                <td class="num <?= $pipelineClass ?>">
                    <?= (int)$row['pipeline_delta'] ?>
                </td>
                <td class="num"><?= (int)$row['on_hand'] ?></td>
                <td class="center"><?= htmlspecialchars($row['last_ship_date'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

</body>
</html>