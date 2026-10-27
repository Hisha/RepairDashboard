<?php
require_once APP_ROOT . '/bin/Model/Shipments.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

$shipmentsModel = new Shipments();

$selectedFiscalYear = isset($_GET['fy']) ? (int)$_GET['fy'] : null;
$selectedNiin = $_GET['niin'] ?? null;
$selectedCog = $_GET['cog'] ?? null;

$fyRange = helpers::getFiscalYearDateRange($selectedFiscalYear);

$view = (isset($_GET['view']) && $_GET['view'] === 'weekly') ? 'weekly' : 'full';

// Weekly: request date back one week. Full: fiscal year range.
if ($view === 'weekly') {
    $endDate    = date('Y-m-d');
    $startDate  = date('Y-m-d', strtotime('-7 days'));
    $rangeLabel = $startDate . ' – ' . $endDate . ' (last 7 days)';
    $heading    = 'Weekly Shipment Count';
} else {
    $startDate  = $fyRange['start_date'];
    $endDate    = $fyRange['end_date'];
    $rangeLabel = $fyRange['label'] . ' (' . $startDate . ' – ' . $endDate . ')';
    $heading    = $fyRange['label'] . ' Shipment Data';
}

// For weekly, ignore NIIN filter (high-level aggregate); keep COG if provided.
$niinFilter = ($view === 'weekly') ? null : $selectedNiin;

$data = $shipmentsModel->getShipmentsListByFiscalYear(
    $niinFilter,
    $selectedCog,
    $startDate,
    $endDate
);

// Weekly view: group by Program + Condition, sum Qty
$groupedData = [];
$grandTotalQty = 0;

if ($view === 'weekly') {
    foreach ($data as $row) {
        $program   = trim((string)($row['Program'] ?? 'Unknown'));
        $condition = trim((string)($row['Condition'] ?? ''));
        $qty       = (float)($row['Qty'] ?? 0);

        $key = $program . "\0" . $condition;

        if (!isset($groupedData[$key])) {
            $groupedData[$key] = [
                'Program'   => $program,
                'Condition' => $condition,
                'Qty'       => 0
            ];
        }

        $groupedData[$key]['Qty'] += $qty;
        $grandTotalQty += $qty;
    }

    // Sort by Program (natural), then Condition
    uasort($groupedData, function ($a, $b) {
        $cmp = strnatcasecmp($a['Program'], $b['Program']);
        if ($cmp !== 0) {
            return $cmp;
        }
        return strnatcasecmp($a['Condition'], $b['Condition']);
    });
}

$baseQuery = 'tab=shipment_data&fy=' . urlencode((string)$fyRange['fiscal_year']);
if (!empty($selectedCog)) {
    $baseQuery .= '&cog=' . urlencode($selectedCog);
}
if (!empty($selectedNiin) && $view !== 'weekly') {
    $baseQuery .= '&niin=' . urlencode($selectedNiin);
}
$fullUrl   = 'monthly_reqs.php?' . $baseQuery;
$weeklyUrl = 'monthly_reqs.php?' . $baseQuery . '&view=weekly';

$exportQuery = $baseQuery . ($view === 'weekly' ? '&view=weekly' : '') . '&export=xlsx';
?>

<style>
.shipment-data-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 12px;
}

.shipment-data-toolbar h2 {
    margin: 0;
}

.view-toggle {
    display: flex;
    gap: 8px;
}

.view-btn {
    display: inline-block;
    padding: 8px 14px;
    font-size: 14px;
    font-weight: bold;
    text-decoration: none;
    border-radius: 4px;
    border: 1px solid #0d6efd;
    color: #0d6efd;
    background: #fff;
}

.view-btn:hover {
    background: #e7f1ff;
}

.view-btn.active {
    background: #0d6efd;
    color: #fff;
}

.range-info {
    margin: 0 0 14px 0;
    padding: 8px 12px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
}

.shipment-data-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 70vh;
    border: 1px solid #ddd;
    background: #fff;
}

.shipment-data-table {
    width: 100%;
    min-width: <?= $view === 'weekly' ? '420px' : '1100px' ?>;
    border-collapse: collapse;
}

.shipment-data-table th,
.shipment-data-table td {
    padding: 8px 10px;
    border: 1px solid #ddd;
    text-align: left;
    white-space: nowrap;
}

.shipment-data-table thead th {
    position: sticky;
    top: 0;
    background: #f1f3f5;
    z-index: 3;
}

.shipment-data-table tbody tr:nth-child(even):not(.grand-total-row) {
    background: #fafafa;
}

.grand-total-row td {
    background: #ced4da !important;
    font-weight: bold;
}

.number-cell {
    text-align: right;
}

.highlight-niin td {
    outline: 3px solid #0d6efd;
    outline-offset: -3px;
    font-weight: bold;
}

.filter-summary {
    margin: 10px 0 15px 0;
    padding: 8px 10px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.export-link {
    display: inline-block;
    margin-bottom: 15px;
    padding: 8px 12px;
    text-decoration: none;
    background: #0d6efd;
    color: #fff;
    border-radius: 4px;
}

.export-link:hover {
    background: #0b5ed7;
}
</style>

<div class="shipment-data-toolbar">
    <h2><?= htmlspecialchars($heading) ?></h2>
    <div class="view-toggle">
        <a class="view-btn <?= $view === 'full' ? 'active' : '' ?>"
           href="<?= htmlspecialchars($fullUrl) ?>">Full Detail</a>
        <a class="view-btn <?= $view === 'weekly' ? 'active' : '' ?>"
           href="<?= htmlspecialchars($weeklyUrl) ?>">Weekly Count</a>
    </div>
</div>

<div class="range-info">
    <strong>Date range:</strong> <?= htmlspecialchars($rangeLabel) ?>
    <?php if ($view === 'weekly'): ?>
        &nbsp;|&nbsp; High-level summary by Program and Condition
    <?php endif; ?>
</div>

<?php if ($view !== 'weekly'): ?>
<div class="filter-summary">
    <strong>Filters:</strong>
    FY = <?= htmlspecialchars($fyRange['label']) ?>
    <?php if (!empty($selectedCog)): ?>
        | COG = <?= htmlspecialchars($selectedCog) ?>
    <?php endif; ?>
    <?php if (!empty($selectedNiin)): ?>
        | NIIN = <?= htmlspecialchars($selectedNiin) ?>
    <?php endif; ?>
</div>
<?php elseif (!empty($selectedCog)): ?>
<div class="filter-summary">
    <strong>Filters:</strong>
    COG = <?= htmlspecialchars($selectedCog) ?>
</div>
<?php endif; ?>

<a class="export-link" href="monthly_reqs.php?<?= htmlspecialchars($exportQuery) ?>">Export Excel</a>

<?php if ($view === 'weekly'): ?>
    <?php if (empty($groupedData)): ?>
        <p>No shipment data found for the last 7 days.</p>
    <?php else: ?>
    <div class="shipment-data-table-wrap">
        <table class="shipment-data-table">
            <thead>
            <tr>
                <th>Program</th>
                <th>Condition</th>
                <th class="number-cell">Qty</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($groupedData as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['Program']) ?></td>
                    <td><?= htmlspecialchars($row['Condition']) ?></td>
                    <td class="number-cell"><?= number_format($row['Qty'], 0) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="grand-total-row">
                <td colspan="2">Totals:</td>
                <td class="number-cell"><?= number_format($grandTotalQty, 0) ?></td>
            </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
<?php else: ?>
    <?php if (empty($data)): ?>
        <p>No shipment data found.</p>
    <?php else: ?>
    <div class="shipment-data-table-wrap">
        <table class="shipment-data-table">
            <thead>
            <tr>
                <th>Ship Date</th>
                <th>NIIN</th>
                <th>Part</th>
                <th>Nomen</th>
                <th>Qty</th>
                <th>Program</th>
                <th>Condition</th>
                <th>Issued To</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($data as $row): ?>
                <?php
                $isHighlighted = (!empty($selectedNiin) && (string)$row['NIIN'] === (string)$selectedNiin);
                ?>
                <tr<?= $isHighlighted ? ' class="highlight-niin" id="selected-niin-row"' : '' ?>>
                    <td><?= htmlspecialchars($row['Ship Date']) ?></td>
                    <td><?= htmlspecialchars($row['NIIN']) ?></td>
                    <td><?= htmlspecialchars($row['Part']) ?></td>
                    <td><?= htmlspecialchars($row['Nomen']) ?></td>
                    <td class="number-cell"><?= number_format((float)$row['Qty'], 0) ?></td>
                    <td><?= htmlspecialchars($row['Program']) ?></td>
                    <td><?= htmlspecialchars($row['Condition']) ?></td>
                    <td><?= htmlspecialchars($row['Issued To']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($selectedNiin)): ?>
    <script>
    (() => {
        const row = document.getElementById('selected-niin-row');
        if (!row) {
            return;
        }

        row.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
            inline: 'nearest'
        });
    })();
    </script>
    <?php endif; ?>
<?php endif; ?>
