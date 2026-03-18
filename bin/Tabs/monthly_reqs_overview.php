<?php
require_once APP_ROOT . '/bin/Model/CavRequisitions.php';

$cavRequisitions = new CavRequisitions();

$data = $cavRequisitions->getBackorderActionList();

$selectedAction = $_GET['action'] ?? '';
$allowedActions = ['REPAIR', 'PARTIAL REPAIR', 'PROCURE', 'IN PROCUREMENT'];

if (!in_array($selectedAction, $allowedActions, true)) {
    $selectedAction = '';
}

$fyParam = $_GET['fy'] ?? '';

$actionCounts = [
    'REPAIR' => 0,
    'PARTIAL REPAIR' => 0,
    'PROCURE' => 0,
    'IN PROCUREMENT' => 0
];

$totalQtyShort = 0;
$repairChartRows = [];
$procureChartRows = [];
$tableRows = [];

foreach ($data as $row) {
    $action = $row['Action'] ?? '';
    $backorderQty = (float)($row['Backorder Qty'] ?? 0);
    $repairableQty = (float)($row['Repairable F Qty'] ?? 0);
    $qtyShort = (float)($row['Qty Short'] ?? 0);

    if (isset($actionCounts[$action])) {
        $actionCounts[$action]++;
    }

    $totalQtyShort += $qtyShort;

    $repairWorkQty = min($repairableQty, $backorderQty);
    if (in_array($action, ['REPAIR', 'PARTIAL REPAIR'], true) && $repairWorkQty > 0) {
        $repairChartRows[] = [
            'NIIN' => $row['NIIN'],
            'Program' => $row['Program'],
            'Value' => $repairWorkQty
        ];
    }

    if (in_array($action, ['PROCURE', 'PARTIAL REPAIR'], true) && $qtyShort > 0) {
        $procureChartRows[] = [
            'NIIN' => $row['NIIN'],
            'Program' => $row['Program'],
            'Value' => $qtyShort
        ];
    }

    if ($selectedAction === '' || $selectedAction === $action) {
        $tableRows[] = $row;
    }
}

usort($repairChartRows, function ($a, $b) {
    return $b['Value'] <=> $a['Value'];
});

usort($procureChartRows, function ($a, $b) {
    return $b['Value'] <=> $a['Value'];
});

$repairChartRows = array_slice($repairChartRows, 0, 10);
$procureChartRows = array_slice($procureChartRows, 0, 10);

$repairLabels = [];
$repairValues = [];

foreach ($repairChartRows as $row) {
    $repairLabels[] = $row['NIIN'] . ' (' . $row['Program'] . ')';
    $repairValues[] = (float)$row['Value'];
}

$procureLabels = [];
$procureValues = [];

foreach ($procureChartRows as $row) {
    $procureLabels[] = $row['NIIN'] . ' (' . $row['Program'] . ')';
    $procureValues[] = (float)$row['Value'];
}

function buildOverviewUrl(string $action = '', string $fy = ''): string
{
    $params = ['tab' => 'overview'];

    if ($fy !== '') {
        $params['fy'] = $fy;
    }

    if ($action !== '') {
        $params['action'] = $action;
    }

    return 'monthly_reqs.php?' . http_build_query($params);
}
?>

<style>
.overview-note {
    margin: 0 0 18px 0;
    padding: 10px 12px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    color: #495057;
}

.overview-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.overview-kpi-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 16px;
}

.overview-kpi-card h3 {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: #495057;
}

.overview-kpi-value {
    font-size: 28px;
    font-weight: bold;
}

.kpi-repair {
    border-left: 6px solid #198754;
}

.kpi-partial {
    border-left: 6px solid #ffc107;
}

.kpi-procure {
    border-left: 6px solid #dc3545;
}

.kpi-inproc {
    border-left: 6px solid #6f42c1;
}

.kpi-short {
    border-left: 6px solid #0d6efd;
}

.overview-chart-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
}

.overview-chart-card {
    flex: 1 1 500px;
    min-width: 320px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    box-sizing: border-box;
}

.overview-chart-wrap {
    position: relative;
    width: 100%;
    height: 360px;
}

.overview-filter-links {
    margin: 0 0 15px 0;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.overview-filter-link {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 20px;
    text-decoration: none;
    background: #e9ecef;
    color: #212529;
    font-weight: bold;
}

.overview-filter-link.active {
    background: #0d6efd;
    color: #fff;
}

.overview-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 70vh;
    border: 1px solid #ddd;
    background: #fff;
}

.overview-table {
    width: 100%;
    min-width: 1300px;
    border-collapse: collapse;
}

.overview-table th,
.overview-table td {
    padding: 8px 10px;
    border: 1px solid #ddd;
    text-align: left;
    white-space: nowrap;
}

.overview-table thead th {
    position: sticky;
    top: 0;
    background: #f1f3f5;
    z-index: 3;
}

.overview-table tbody tr:nth-child(even) {
    background: #fafafa;
}

.number-cell {
    text-align: right;
}

.action-repair td {
    background: #d1e7dd !important;
}

.action-partial td {
    background: #fff3cd !important;
}

.action-procure td {
    background: #f8d7da !important;
}

.action-inproc td {
    background: #e2d9f3 !important;
}
</style>

<h2>Overview</h2>

<p class="overview-note">
    Current-state backorder action summary. This section is based on current backorders, repairable F-condition inventory, and active procurements.
</p>

<div class="overview-kpi-grid">
    <div class="overview-kpi-card kpi-repair">
        <h3>REPAIR</h3>
        <div class="overview-kpi-value"><?= number_format($actionCounts['REPAIR']) ?></div>
    </div>

    <div class="overview-kpi-card kpi-partial">
        <h3>PARTIAL REPAIR</h3>
        <div class="overview-kpi-value"><?= number_format($actionCounts['PARTIAL REPAIR']) ?></div>
    </div>

    <div class="overview-kpi-card kpi-procure">
        <h3>PROCURE</h3>
        <div class="overview-kpi-value"><?= number_format($actionCounts['PROCURE']) ?></div>
    </div>

    <div class="overview-kpi-card kpi-inproc">
        <h3>IN PROCUREMENT</h3>
        <div class="overview-kpi-value"><?= number_format($actionCounts['IN PROCUREMENT']) ?></div>
    </div>

    <div class="overview-kpi-card kpi-short">
        <h3>TOTAL QTY SHORT</h3>
        <div class="overview-kpi-value"><?= number_format($totalQtyShort, 0) ?></div>
    </div>
</div>

<div class="overview-chart-grid">
    <div class="overview-chart-card">
        <h3>Top 10 NIINs for In-House Repair</h3>
        <?php if (empty($repairLabels)): ?>
            <p>No repair candidates found.</p>
        <?php else: ?>
            <div class="overview-chart-wrap">
                <canvas id="repairWorkChart"></canvas>
            </div>
        <?php endif; ?>
    </div>

    <div class="overview-chart-card">
        <h3>Top 10 NIINs Still Short / Needing Buy Action</h3>
        <?php if (empty($procureLabels)): ?>
            <p>No procurement shortages found.</p>
        <?php else: ?>
            <div class="overview-chart-wrap">
                <canvas id="procureWorkChart"></canvas>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="overview-filter-links">
    <a class="overview-filter-link <?= $selectedAction === '' ? 'active' : '' ?>"
       href="<?= htmlspecialchars(buildOverviewUrl('', $fyParam)) ?>">All</a>

    <a class="overview-filter-link <?= $selectedAction === 'REPAIR' ? 'active' : '' ?>"
       href="<?= htmlspecialchars(buildOverviewUrl('REPAIR', $fyParam)) ?>">Repair</a>

    <a class="overview-filter-link <?= $selectedAction === 'PARTIAL REPAIR' ? 'active' : '' ?>"
       href="<?= htmlspecialchars(buildOverviewUrl('PARTIAL REPAIR', $fyParam)) ?>">Partial Repair</a>

    <a class="overview-filter-link <?= $selectedAction === 'PROCURE' ? 'active' : '' ?>"
       href="<?= htmlspecialchars(buildOverviewUrl('PROCURE', $fyParam)) ?>">Procure</a>

    <a class="overview-filter-link <?= $selectedAction === 'IN PROCUREMENT' ? 'active' : '' ?>"
       href="<?= htmlspecialchars(buildOverviewUrl('IN PROCUREMENT', $fyParam)) ?>">In Procurement</a>
</div>

<h3><?= $selectedAction !== '' ? htmlspecialchars($selectedAction) . ' Items' : 'All Action Items' ?></h3>

<?php if (empty($tableRows)): ?>
    <p>No action items found.</p>
<?php else: ?>
<div class="overview-table-wrap">
    <table class="overview-table">
        <thead>
        <tr>
            <th>NIIN</th>
            <th>Program</th>
            <th>Nomen</th>
            <th>Backorder Qty</th>
            <th>Repairable F Qty</th>
            <th>F Awaiting Vendor Qty</th>
            <th>Active Procurement Count</th>
            <th>Qty Short</th>
            <th>Priority</th>
            <th>Oldest Backorder Date</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($tableRows as $row): ?>
            <?php
            $rowClass = '';

            switch ($row['Action']) {
                case 'REPAIR':
                    $rowClass = 'action-repair';
                    break;
                case 'PARTIAL REPAIR':
                    $rowClass = 'action-partial';
                    break;
                case 'PROCURE':
                    $rowClass = 'action-procure';
                    break;
                case 'IN PROCUREMENT':
                    $rowClass = 'action-inproc';
                    break;
            }
            ?>
            <tr class="<?= htmlspecialchars($rowClass) ?>">
                <td><?= htmlspecialchars($row['NIIN']) ?></td>
                <td><?= htmlspecialchars($row['Program']) ?></td>
                <td><?= htmlspecialchars($row['Nomen']) ?></td>
                <td class="number-cell"><?= number_format((float)$row['Backorder Qty'], 0) ?></td>
                <td class="number-cell"><?= number_format((float)$row['Repairable F Qty'], 0) ?></td>
                <td class="number-cell"><?= number_format((float)$row['F Awaiting Vendor Qty'], 0) ?></td>
                <td class="number-cell"><?= number_format((float)$row['Active Procurement Count'], 0) ?></td>
                <td class="number-cell"><?= number_format((float)$row['Qty Short'], 0) ?></td>
                <td><?= htmlspecialchars($row['Priority']) ?></td>
                <td><?= htmlspecialchars($row['Oldest Backorder Date']) ?></td>
                <td><?= htmlspecialchars($row['Action']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($repairLabels)): ?>
<script>
(() => {
    const labels = <?= json_encode($repairLabels) ?>;
    const values = <?= json_encode($repairValues) ?>;
    const canvas = document.getElementById('repairWorkChart');

    if (!canvas) {
        return;
    }

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Repair Qty',
                data: values
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Repair Qty: ' + Number(context.parsed.x || 0).toLocaleString();
                        }
                    }
                }
            }
        }
    });
})();
</script>
<?php endif; ?>

<?php if (!empty($procureLabels)): ?>
<script>
(() => {
    const labels = <?= json_encode($procureLabels) ?>;
    const values = <?= json_encode($procureValues) ?>;
    const canvas = document.getElementById('procureWorkChart');

    if (!canvas) {
        return;
    }

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Qty Short',
                data: values
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Qty Short: ' + Number(context.parsed.x || 0).toLocaleString();
                        }
                    }
                }
            }
        }
    });
})();
</script>
<?php endif; ?>