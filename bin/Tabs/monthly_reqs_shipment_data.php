<?php
require_once APP_ROOT . '/bin/Model/Shipments.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

$shipmentsModel = new Shipments();

$selectedFiscalYear = isset($_GET['fy']) ? (int)$_GET['fy'] : null;
$selectedNiin = $_GET['niin'] ?? null;
$selectedCog = $_GET['cog'] ?? null;

$fyRange = helpers::getFiscalYearDateRange($selectedFiscalYear);

$data = $shipmentsModel->getShipmentsListByFiscalYear(
    $selectedNiin,
    $selectedCog,
    $fyRange['start_date'],
    $fyRange['end_date']
);
?>

<style>
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
    min-width: 1100px;
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

.shipment-data-table tbody tr:nth-child(even) {
    background: #fafafa;
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

<h2><?= htmlspecialchars($fyRange['label']) ?> Shipment Data</h2>

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

<a class="export-link" href="monthly_reqs.php?tab=shipment_data&fy=<?= urlencode((string)$fyRange['fiscal_year']) ?><?= !empty($selectedCog) ? '&cog=' . urlencode($selectedCog) : '' ?><?= !empty($selectedNiin) ? '&niin=' . urlencode($selectedNiin) : '' ?>&export=csv">Export CSV</a>

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