<?php
require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/bin/Model/BackOrders.php';

$backOrderModel = new BackOrders();
$pieData = $backOrderModel->fillBackOrdersPieChart();

$labels = [];
$values = [];

foreach ($pieData as $row) {
    $labels[] = $row['Priority'] ?? 'Unknown';
    $values[] = (int)($row['Qty'] ?? 0);
}
?>

<div class="chart-card">
    <h3>Backorders by Priority</h3>
    <?php echo '<p>chart file loaded</p>'; ?>

    <?php if (empty($labels)): ?>
        <p>No backorder chart data found.</p>
    <?php else: ?>
        <div class="chart-canvas-wrap">
            <canvas id="backordersPieChart"></canvas>
        </div>

        <script>
        (() => {
            const pieLabels = <?= json_encode($labels) ?>;
            const pieValues = <?= json_encode($values) ?>;

            const canvas = document.getElementById('backordersPieChart');
            if (!canvas) {
                return;
            }

            new Chart(canvas, {
                type: 'pie',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieValues
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
                    onClick: (event, elements) => {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const priority = pieLabels[index];
                            window.location.href = 'backorders.php?priority=' + encodeURIComponent(priority);
                        }
                    }
                }
            });
        })();
        </script>
    <?php endif; ?>
</div>