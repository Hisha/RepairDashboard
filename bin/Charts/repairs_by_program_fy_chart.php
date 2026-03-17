<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once APP_ROOT . '/bin/Model/Repairs.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

$repairsModel = new Repairs();
$fyRange = helpers::getFiscalYearDateRange();

$chartData = $repairsModel->getRepairedDollarValue(
    $fyRange['start_date'],
    $fyRange['end_date']
    );

$labels = [];
$values = [];
$totalValue = 0;

foreach ($chartData as $row) {
    $labels[] = $row['normalized_program'] ?? 'Unknown';
    $value = (float)($row['Value'] ?? 0);
    $values[] = $value;
    $totalValue += $value;
}
?>

<style>
#repairsByProgramFYChart {
    cursor: pointer;
}
</style>

<div class="chart-card">
    <h3><?= htmlspecialchars($fyRange['label']) ?> Repair Value by Program</h3>

    <?php if (empty($labels)): ?>
        <p>No repair chart data found.</p>
    <?php else: ?>
        <div class="chart-canvas-wrap">
            <canvas id="repairsByProgramFYChart"></canvas>
        </div>

        <script>
        (() => {
            const chartLabels = <?= json_encode($labels) ?>;
            const chartValues = <?= json_encode($values) ?>;
            const totalValue = <?= json_encode($totalValue) ?>;

            const canvas = document.getElementById('repairsByProgramFYChart');
            if (!canvas) {
                return;
            }

            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        data: chartValues
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || 'Unknown';
                                    const value = Number(context.parsed || 0);
                                    const percent = totalValue > 0
                                        ? ((value / totalValue) * 100).toFixed(1)
                                        : '0.0';

                                    return label + ': $' + value.toLocaleString(undefined, {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    }) + ' (' + percent + '%)';
                                }
                            }
                        }
                    },
                    onClick: () => {
                        window.location.href = 'monthly_tech.php';
                    }
                }
            });
        })();
        </script>
    <?php endif; ?>
</div>