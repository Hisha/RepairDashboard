<?php
require_once APP_ROOT . '/bin/Model/Shipments.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

$shipmentsModel = new Shipments();

$selectedFiscalYear = isset($_GET['fy']) ? (int)$_GET['fy'] : null;
$selectedProgram = $_GET['program'] ?? '';
$selectedCog = $_GET['cog'] ?? '';

$fyRange = helpers::getFiscalYearDateRange($selectedFiscalYear);

$programOptions = $shipmentsModel->getDistinctShipmentPrograms(
    $fyRange['start_date'],
    $fyRange['end_date']
);

$programSummary = $shipmentsModel->getProgramShipmentSummary(
    $selectedCog !== '' ? $selectedCog : null,
    $fyRange['start_date'],
    $fyRange['end_date']
);

$niinAnalysis = $shipmentsModel->getNiinShipmentAnalysis(
    $selectedProgram !== '' ? $selectedProgram : null,
    $selectedCog !== '' ? $selectedCog : null,
    $fyRange['start_date'],
    $fyRange['end_date']
);

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=program_niin_analysis_' . $fyRange['label'] . '.csv');

    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'NIIN',
        'Part',
        'Nomen',
        'Program',
        'Total Qty',
        'Shipment Lines',
        'Last Ship Date'
    ]);

    foreach ($niinAnalysis as $row) {
        fputcsv($output, [
            $row['NIIN'],
            $row['Part'],
            $row['Nomen'],
            $row['Program'],
            $row['Total Qty'],
            $row['Shipment Lines'],
            $row['Last Ship Date']
        ]);
    }

    fclose($output);
    exit;
}

$chartLabels = [];
$chartValues = [];

foreach ($programSummary as $row) {
    $chartLabels[] = $row['Program'] ?? 'Unknown';
    $chartValues[] = (int)($row['Total Qty'] ?? 0);
}

$exportUrl = 'monthly_reqs.php?tab=program_niin'
    . '&fy=' . urlencode((string)$fyRange['fiscal_year'])
    . ($selectedProgram !== '' ? '&program=' . urlencode($selectedProgram) : '')
    . ($selectedCog !== '' ? '&cog=' . urlencode($selectedCog) : '')
    . '&export=csv';
?>

<style>
.analysis-filter-wrap {
    margin-bottom: 20px;
    padding: 14px 16px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
}

.analysis-filter-form {
    display: flex;
    gap: 16px;
    align-items: end;
    flex-wrap: wrap;
}

.analysis-filter-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 6px;
}

.analysis-filter-group select {
    min-width: 220px;
    padding: 8px 10px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    background: #fff;
}

.analysis-filter-group button,
.analysis-export-link {
    padding: 9px 12px;
    border: none;
    border-radius: 6px;
    background: #0d6efd;
    color: #fff;
    text-decoration: none;
    font-size: 14px;
    cursor: pointer;
}

.analysis-filter-group button:hover,
.analysis-export-link:hover {
    background: #0b5ed7;
}

.analysis-summary {
    margin: 10px 0 20px 0;
    padding: 8px 10px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.analysis-chart-wrap {
    width: 100%;
    max-width: 1000px;
    height: 420px;
    margin-bottom: 25px;
}

.analysis-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 70vh;
    border: 1px solid #ddd;
    background: #fff;
}

.analysis-table {
    width: 100%;
    min-width: 1100px;
    border-collapse: collapse;
}

.analysis-table th,
.analysis-table td {
    padding: 8px 10px;
    border: 1px solid #ddd;
    text-align: left;
    white-space: nowrap;
}

.analysis-table thead th {
    position: sticky;
    top: 0;
    background: #f1f3f5;
    z-index: 3;
}

.analysis-table tbody tr:nth-child(even) {
    background: #fafafa;
}

.number-cell {
    text-align: right;
}
</style>

<h2><?= htmlspecialchars($fyRange['label']) ?> Program / NIIN Analysis</h2>

<div class="analysis-filter-wrap">
    <form class="analysis-filter-form" method="get" action="monthly_reqs.php">
        <input type="hidden" name="tab" value="program_niin">
        <input type="hidden" name="fy" value="<?= htmlspecialchars((string)$fyRange['fiscal_year']) ?>">

        <div class="analysis-filter-group">
            <label for="program">Program</label>
            <select name="program" id="program">
                <option value="">All Programs</option>
                <?php foreach ($programOptions as $option): ?>
                    <option value="<?= htmlspecialchars($option['Program']) ?>"
                        <?= $selectedProgram === $option['Program'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($option['Program']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="analysis-filter-group">
            <label for="cog">COG</label>
            <select name="cog" id="cog">
                <option value="">All COGs</option>
                <option value="1" <?= $selectedCog === '1' ? 'selected' : '' ?>>1 COG</option>
                <option value="7" <?= $selectedCog === '7' ? 'selected' : '' ?>>7 COG</option>
            </select>
        </div>

        <div class="analysis-filter-group">
            <button type="submit">Apply</button>
        </div>

        <div class="analysis-filter-group">
            <a class="analysis-export-link" href="<?= htmlspecialchars($exportUrl) ?>">Export CSV</a>
        </div>
    </form>
</div>

<div class="analysis-summary">
    <strong>Filters:</strong>
    FY = <?= htmlspecialchars($fyRange['label']) ?>
    | Program = <?= $selectedProgram !== '' ? htmlspecialchars($selectedProgram) : 'All Programs' ?>
    | COG = <?= $selectedCog !== '' ? htmlspecialchars($selectedCog) : 'All COGs' ?>
</div>

<?php if (empty($chartLabels)): ?>
    <p>No program shipment summary data found.</p>
<?php else: ?>
    <div class="analysis-chart-wrap">
        <canvas id="programShipmentSummaryChart"></canvas>
    </div>

    <script>
    (() => {
        const labels = <?= json_encode($chartLabels) ?>;
        const values = <?= json_encode($chartValues) ?>;
        const fiscalYear = <?= json_encode((string)$fyRange['fiscal_year']) ?>;
        const selectedCog = <?= json_encode($selectedCog) ?>;

        const canvas = document.getElementById('programShipmentSummaryChart');
        if (!canvas) {
            return;
        }

        canvas.style.cursor = 'pointer';

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Qty Shipped',
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
                    title: {
                        display: true,
                        text: 'Top Programs by Qty Shipped'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Qty: ' + Number(context.parsed.x || 0).toLocaleString();
                            }
                        }
                    }
                },
                onClick: (event, elements) => {
                    if (!elements.length) {
                        return;
                    }

                    const index = elements[0].index;
                    const program = labels[index];

                    let url = 'monthly_reqs.php?tab=program_niin'
                        + '&fy=' + encodeURIComponent(fiscalYear)
                        + '&program=' + encodeURIComponent(program);

                    if (selectedCog !== '') {
                        url += '&cog=' + encodeURIComponent(selectedCog);
                    }

                    window.location.href = url;
                }
            }
        });
    })();
    </script>
<?php endif; ?>

<h3>
    <?= $selectedProgram !== '' ? htmlspecialchars($selectedProgram) . ' NIIN Breakdown' : 'Top NIINs Overall' ?>
</h3>

<?php if (empty($niinAnalysis)): ?>
    <p>No NIIN shipment analysis data found.</p>
<?php else: ?>
<div class="analysis-table-wrap">
    <table class="analysis-table">
        <thead>
        <tr>
            <th>NIIN</th>
            <th>Part</th>
            <th>Nomen</th>
            <th>Program</th>
            <th>Total Qty</th>
            <th>Shipment Lines</th>
            <th>Last Ship Date</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($niinAnalysis as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['NIIN']) ?></td>
                <td><?= htmlspecialchars($row['Part']) ?></td>
                <td><?= htmlspecialchars($row['Nomen']) ?></td>
                <td><?= htmlspecialchars($row['Program']) ?></td>
                <td class="number-cell"><?= number_format((float)$row['Total Qty'], 0) ?></td>
                <td class="number-cell"><?= number_format((float)$row['Shipment Lines'], 0) ?></td>
                <td><?= htmlspecialchars($row['Last Ship Date']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>