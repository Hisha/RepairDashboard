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
    <title>Survival Report</title>
    <style>
        body { font-family: Arial; margin:20px; }
        table { border-collapse: collapse; width:100%; }
        th, td { border:1px solid #ccc; padding:6px; }
        th { background:#f2f2f2; position: sticky; top:0; }
        .bad { background:#ffd6d6; }
        .warn { background:#fff3cd; }
        .good { background:#d9f2d9; }
    </style>
</head>
<body>

<h2>Survival Report</h2>

<table>
<thead>
<tr>
    <th>NIIN</th>
    <th>LRC</th>
    <th>Std Price</th>

    <th>12M Rec</th>
    <th>12M Rep</th>
    <th>12M BER</th>
    <th>12M Eval</th>
    <th>12M Open</th>
    <th>12M Survival</th>

    <th>All Rec</th>
    <th>All Rep</th>
    <th>All BER</th>
    <th>All Eval</th>
    <th>All Open</th>
    <th>All Survival</th>
</tr>
</thead>

<tbody>
<?php foreach($rows as $r): ?>

<?php
$cls12 = ($r['survival_12m'] !== null && $r['survival_12m'] < 0.6) ? 'bad' : '';
$clsAll = ($r['survival_all'] !== null && $r['survival_all'] < 0.6) ? 'bad' : '';
?>

<tr>
<td><?= $r['niin'] ?></td>
<td><?= $r['lrc'] ?></td>
<td><?= number_format($r['std_price'],2) ?></td>

<td><?= $r['receipts_12m'] ?></td>
<td><?= $r['repaired_12m'] ?></td>
<td><?= $r['ber_12m'] ?></td>
<td><?= $r['eval_12m'] ?></td>
<td><?= $r['open_12m'] ?></td>
<td class="<?= $cls12 ?>">
<?= $r['survival_12m'] !== null ? number_format($r['survival_12m']*100,1).'%' : 'N/A' ?>
</td>

<td><?= $r['receipts_all'] ?></td>
<td><?= $r['repaired_all'] ?></td>
<td><?= $r['ber_all'] ?></td>
<td><?= $r['eval_all'] ?></td>
<td><?= $r['open_all'] ?></td>
<td class="<?= $clsAll ?>">
<?= $r['survival_all'] !== null ? number_format($r['survival_all']*100,1).'%' : 'N/A' ?>
</td>

</tr>

<?php endforeach; ?>
</tbody>
</table>

</body>
</html>