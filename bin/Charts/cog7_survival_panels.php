<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once APP_ROOT . '/bin/Model/Cog7Repairables.php';

$model = new Cog7Repairables();

$top10Worst = $model->getTop10Worst();
$top10Highest = $model->getTop10Highest12MActions();
?>

<style>
.table-mini {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.table-mini th,
.table-mini td {
    border: 1px solid #ddd;
    padding: 4px;
    text-align: left;
}

.table-mini th {
    background: #f2f2f2;
}

.table-mini .num {
    text-align: right;
}

.bad { background:#ffd6d6; }
.warn { background:#fff3cd; }
.good { background:#d9f2d9; }
</style>

<!-- 🔴 Top 10 Worst Survival -->
<div class="chart-card">
    <h3>Top 10 Lowest 12M Survival</h3>

    <?php if (empty($top10Worst)): ?>
        <p>No data found.</p>
    <?php else: ?>
        <table class="table-mini">
            <thead>
                <tr>
                    <th>NIIN</th>
                    <th>12M</th>
                    <th>Survival</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top10Worst as $r): ?>
                    <?php
                        $cls = '';
                        if ($r['survival_12m'] !== null) {
                            if ($r['survival_12m'] < 0.50) $cls = 'bad';
                            elseif ($r['survival_12m'] < 0.75) $cls = 'warn';
                            else $cls = 'good';
                        }
                    ?>
                    <tr>
                        <td>
                            <a href="cog7_repairables.php?niin=<?= urlencode($r['niin']) ?>">
                                <?= htmlspecialchars($r['niin']) ?>
                            </a>
                        </td>
                        <td class="num"><?= (int)$r['repair_actions_12m'] ?></td>
                        <td class="num <?= $cls ?>">
                            <?= $r['survival_12m'] !== null
                                ? number_format($r['survival_12m'] * 100, 1) . '%'
                                : 'N/A' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- 🔵 Top 10 Highest Repair Actions -->
<div class="chart-card">
    <h3>Top 10 Highest 12M Repair Actions</h3>

    <?php if (empty($top10Highest)): ?>
        <p>No data found.</p>
    <?php else: ?>
        <table class="table-mini">
            <thead>
                <tr>
                    <th>NIIN</th>
                    <th>12M</th>
                    <th>Survival</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top10Highest as $r): ?>
                    <?php
                        $cls = '';
                        if ($r['survival_12m'] !== null) {
                            if ($r['survival_12m'] < 0.50) $cls = 'bad';
                            elseif ($r['survival_12m'] < 0.75) $cls = 'warn';
                            else $cls = 'good';
                        }
                    ?>
                    <tr>
                        <td>
                            <a href="cog7_repairables.php?niin=<?= urlencode($r['niin']) ?>">
                                <?= htmlspecialchars($r['niin']) ?>
                            </a>
                        </td>
                        <td class="num"><?= (int)$r['repair_actions_12m'] ?></td>
                        <td class="num <?= $cls ?>">
                            <?= $r['survival_12m'] !== null
                                ? number_format($r['survival_12m'] * 100, 1) . '%'
                                : 'N/A' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>