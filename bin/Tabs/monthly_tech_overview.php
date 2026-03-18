<?php
require_once APP_ROOT . '/bin/Model/Repairs.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

$repairsModel = new Repairs();
$selectedFiscalYear = isset($_GET['fy']) ? (int)$_GET['fy'] : null;
$fyRange = helpers::getFiscalYearDateRange($selectedFiscalYear);

$data = $repairsModel->getRepairPriorityReport(
    $fyRange['start_date'],
    $fyRange['end_date']
    );

$priorityRows = [];

foreach ($data as $row) {
    $aOnHand = (float)($row['A OnHand'] ?? 0);
    $gOnHand = (float)($row['G OnHand'] ?? 0);
    $quarterlyDemand = (float)($row['Quarterly Demand'] ?? 0);
    $shortfall = $quarterlyDemand - $aOnHand;
    
    if ($shortfall > 0) {
        if ($aOnHand == $quarterlyDemand) {
            $color = '#fff3cd';
        } elseif (($aOnHand + $gOnHand) > $quarterlyDemand) {
            $color = '#e2d9f3';
        } else {
            $color = '#f8d7da';
        }
        
        $priorityRows[] = [
            'niin' => $row['NIIN'],
            'program' => $row['Program'],
            'shortfall' => $shortfall,
            'color' => $color
        ];
    }
}

usort($priorityRows, function ($a, $b) {
    return $b['shortfall'] <=> $a['shortfall'];
});
    
    $top10 = array_slice($priorityRows, 0, 10);
    
    $chartLabels = [];
    $chartValues = [];
    $chartColors = [];
    
    foreach ($top10 as $item) {
        $chartLabels[] = $item['niin'] . ' (' . $item['program'] . ')';
        $chartValues[] = round($item['shortfall'], 2);
        $chartColors[] = $item['color'];
    }
    ?>

<h2><?= htmlspecialchars($fyRange['label']) ?> Repair Priority Overview</h2>

<p style="margin-top:-10px; margin-bottom:15px; color:#555;">
    This chart highlights the top 10 NIINs where demand exceeds available A-condition inventory.
    Bar length represents the shortfall (Quarterly Demand - A OnHand).
</p>

<p style="margin-top:-10px; margin-bottom:15px; font-size:14px;">
    <strong>Color Guide:</strong>
    <span style="background:#f8d7da; padding:3px 6px; border-radius:4px;">Red</span> = Not enough stock |
    <span style="background:#e2d9f3; padding:3px 6px; border-radius:4px;">Purple</span> = Covered with G stock |
    <span style="background:#fff3cd; padding:3px 6px; border-radius:4px;">Yellow</span> = Exact match
</p>

<?php if (empty($chartLabels)): ?>
    <p>No priority repair data found.</p>
<?php else: ?>
<div style="width:100%; max-width:1000px; height:450px;">
    <canvas id="topPriorityRepairsChart"></canvas>
</div>

<script>
(() => {
    const labels = <?= json_encode($chartLabels) ?>;
    const values = <?= json_encode($chartValues) ?>;
    const colors = <?= json_encode($chartColors) ?>;

    const canvas = document.getElementById('topPriorityRepairsChart');
    if (!canvas) {
        return;
    }

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Repair Shortfall',
                data: values,
                backgroundColor: colors
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
                            return 'Shortfall: ' + Number(context.parsed.x || 0).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    }
                }
            }
        }
    });
})();
</script>
<?php endif; ?>