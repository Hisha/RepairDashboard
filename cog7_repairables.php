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
    12M Receipts are a demand signal only. Survival is based on lifetime repair outcomes.
</div>

<div class="table-wrap">
<table>
    <thead>
        <tr>
            <th>NIIN</th>
            <th>LRC</th>
            <th>Std Price</th>
            <th>12M Rec</th>
            <th>Total Rec</th>
            <th>Repaired</th>
            <th>BER</th>
            <th>Eval</th>
            <th>Backlog</th>
            <th>Survival %</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $r): ?>
            <?php
                $cls = '';
                if ($r['survival_rate'] !== null) {
                    if ($r['survival_rate'] < 0.50) {
                        $cls = 'bad';
                    } elseif ($r['survival_rate'] < 0.75) {
                        $cls = 'warn';
                    } else {
                        $cls = 'good';
                    }
                }

                $backlogClass = '';
                if ((int)$r['backlog'] > 100) {
                    $backlogClass = 'bad';
                } elseif ((int)$r['backlog'] > 25) {
                    $backlogClass = 'warn';
                }
            ?>
            <tr>
                <td><?= htmlspecialchars($r['niin']) ?></td>
                <td><?= htmlspecialchars($r['lrc']) ?></td>
                <td class="num"><?= number_format((float)$r['std_price'], 2) ?></td>
                <td class="num"><?= (int)$r['receipts_12m'] ?></td>
                <td class="num"><?= (int)$r['receipts_all'] ?></td>
                <td class="num"><?= (int)$r['repaired_all'] ?></td>
                <td class="num"><?= (int)$r['ber_all'] ?></td>
                <td class="num"><?= (int)$r['eval_all'] ?></td>
                <td class="num <?= $backlogClass ?>"><?= (int)$r['backlog'] ?></td>
                <td class="num <?= $cls ?>">
                    <?= $r['survival_rate'] !== null ? number_format($r['survival_rate'] * 100, 1) . '%' : 'N/A' ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

</body>
</html>