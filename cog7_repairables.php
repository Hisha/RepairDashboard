<?php
require_once __DIR__ . "/bootstrap.php";
include 'menu.php';
require_once APP_ROOT . "/bin/Model/Cog7Repairables.php";

$model = new Cog7Repairables();
$rows = $model->getReport();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Survival Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { margin-bottom: 8px; }
        .subtext { color: #555; margin-bottom: 18px; font-size: 14px; }
        .table-wrap { overflow-x: auto; }

        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f2f2f2; position: sticky; top: 0; z-index: 2; }

        .bad { background: #ffd6d6; }
        .warn { background: #fff3cd; }
        .good { background: #d9f2d9; }

        .num { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
    </style>
</head>
<body>

<h2>Survival Report</h2>
<div class="subtext">
    Shows COG 7 NIINs shipped in the last 3 years, sorted by most recent ship date.
    Survival % = percent of repair actions that ended in A, D, or G condition.
</div>

<div class="table-wrap">
<table>
    <thead>
        <tr>
            <th>NIIN</th>
            <th>LRC</th>
            <th>Std Price</th>
            <th>Last Ship Date</th>
            <th>12M Repair Actions</th>
            <th>12M Survival %</th>
            <th>Lifetime Repair Actions</th>
            <th>Lifetime Survival %</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $r): ?>
            <?php
                $cls12 = '';
                if ($r['survival_12m'] !== null) {
                    if ($r['survival_12m'] < 0.50) {
                        $cls12 = 'bad';
                    } elseif ($r['survival_12m'] < 0.75) {
                        $cls12 = 'warn';
                    } else {
                        $cls12 = 'good';
                    }
                }

                $clsAll = '';
                if ($r['survival_all'] !== null) {
                    if ($r['survival_all'] < 0.50) {
                        $clsAll = 'bad';
                    } elseif ($r['survival_all'] < 0.75) {
                        $clsAll = 'warn';
                    } else {
                        $clsAll = 'good';
                    }
                }
            ?>
            <tr>
                <td><?= htmlspecialchars($r['niin']) ?></td>
                <td><?= htmlspecialchars($r['lrc']) ?></td>
                <td class="num"><?= number_format((float)$r['std_price'], 2) ?></td>
                <td class="center"><?= htmlspecialchars($r['last_ship_date']) ?></td>
                <td class="num"><?= (int)$r['repair_actions_12m'] ?></td>
                <td class="num <?= $cls12 ?>">
                    <?= $r['survival_12m'] !== null ? number_format($r['survival_12m'] * 100, 1) . '%' : 'N/A' ?>
                </td>
                <td class="num"><?= (int)$r['repair_actions_all'] ?></td>
                <td class="num <?= $clsAll ?>">
                    <?= $r['survival_all'] !== null ? number_format($r['survival_all'] * 100, 1) . '%' : 'N/A' ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

</body>
</html>