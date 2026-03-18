<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once APP_ROOT . '/bin/Model/Shipments.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

if (!isset($cog)) {
    return;
}

$shipmentsModel = new Shipments();
$fyRange = helpers::getFiscalYearDateRange();

$chartData = $shipmentsModel->getTop10ShipmentsByCog(
    (string)$cog,
    $fyRange['start_date'],
    $fyRange['end_date']
    );

$labels = [];
$values = [];

foreach ($chartData as $row) {
    $labels[] = $row['NIIN'] ?? 'Unknown';
    $values[] = (int)($row['QTY'] ?? 0);
}

$chartId = 'top10ShipmentsCog' . preg_replace('/[^a-zA-Z0-9]/', '', (string)$cog);
?>

<style>
#<?= htmlspecialchars($chartId) ?> {
    cursor: pointer;
}
</style>

<div class="chart-card">
    <h3><?= htmlspecialchars($fyRange['label']) ?> Top 10 Shipped NIINs - <?= htmlspecialchars((string)$cog) ?> COG</h3>

    <?php if (empty($labels)): ?>
        <p>No shipment data found.</p>
    <?php else: ?>
        <div class="chart-canvas-wrap">
            <canvas id="<?= htmlspecialchars($chartId) ?>"></canvas>
        </div>

        <script>
        (() => {
            const chartLabels = <?= json_encode($labels) ?>;
            const chartValues = <?= json_encode($values) ?>;
            const canvas = document.getElementById(<?= json_encode($chartId) ?>);

            if (!canvas) {
                return;
            }

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Quantity Shipped',
                        data: chartValues
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
                                    const value = Number(context.parsed.x || 0);
                                    return 'QTY: ' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    onClick: (event, elements) => {
    					if (!elements.length) {
        					return;
    					}

    					const index = elements[0].index;
    					const niin = chartLabels[index];
    					const cog = <?= json_encode((string)$cog) ?>;
    					const fiscalYear = <?= json_encode((string)$fyRange['fiscal_year']) ?>;

    					window.location.href =
        					'monthly_reqs.php?tab=shipment_data' +
        					'&niin=' + encodeURIComponent(niin) +
        					'&cog=' + encodeURIComponent(cog) +
        					'&fy=' + encodeURIComponent(fiscalYear);
					}
                }
            });
        })();
        </script>
    <?php endif; ?>
</div>