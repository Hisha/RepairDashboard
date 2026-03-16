<?php
require_once __DIR__ . "/bootstrap.php";
require_once APP_ROOT . "/bin/Model/BackOrders.php";

$backOrderModel = new BackOrders();
$pieData = $backOrderModel->fillBackOrdersPieChart();

$labels = [];
$values = [];

foreach ($pieData as $row) {
    $labels[] = $row['Priority'];
    $values[] = (int)$row['Qty'];
}
?>

<div class="chart-card">
    <h3>Backorders by Priority</h3>
    <canvas id="backordersPieChart"></canvas>
</div>

<script>
const backordersPieLabels = <?= json_encode($labels) ?>;
const backordersPieValues = <?= json_encode($values) ?>;

const backordersPieCtx = document.getElementById('backordersPieChart');

new Chart(backordersPieCtx, {
    type: 'pie',
    data: {
        labels: backordersPieLabels,
        datasets: [{
            data: backordersPieValues
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        },
        onClick: function(event, elements) {
            if (elements.length > 0) {
                const sliceIndex = elements[0].index;
                const priority = backordersPieLabels[sliceIndex];
                window.location.href = 'backorders.php?priority=' + encodeURIComponent(priority);
            }
        }
    }
});
</script>