<?php
require_once APP_ROOT . '/bin/Model/Repairs.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

$repairsModel = new Repairs();
$fyRange = helpers::getFiscalYearDateRange();

$data = $repairsModel->getTechsRepairValue(
    $fyRange['start_date'],
    $fyRange['end_date']
    );

$labels = [];
$values = [];

foreach ($data as $row) {
    $labels[] = $row['technicalpocname'] ?? 'Unknown';
    $values[] = (float)($row['Value'] ?? 0);
}
?>

<h2><?= htmlspecialchars($fyRange['label']) ?> Repair Value by Tech</h2>

<div style="width:100%; max-width:900px; height:400px;">
    <canvas id="techChart"></canvas>
</div>

<script>
(() => {
    const labels = <?= json_encode($labels) ?>;
    const values = <?= json_encode($values) ?>;

    const canvas = document.getElementById('techChart');
    if (!canvas) return;

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Repair Value',
                data: values
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y', // 👈 horizontal bar (MUCH better for names)
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed.x || 0;
                            return '$' + value.toLocaleString(undefined, {
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