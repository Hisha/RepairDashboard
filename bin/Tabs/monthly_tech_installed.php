<?php
require_once APP_ROOT . '/bin/Model/Installed.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

$installedModel = new Installed();
$selectedFiscalYear = isset($_GET['fy']) ? (int)$_GET['fy'] : null;
$fyRange = helpers::getFiscalYearDateRange($selectedFiscalYear);

$rows = $installedModel->getInstalledSupportByFiscalYear(
    $fyRange['start_date'],
    $fyRange['end_date']
    );
$summary = $installedModel->getInstalledSupportSummary(
    $fyRange['start_date'],
    $fyRange['end_date']
    );

$rowCount = count($rows);
?>

<style>
.installed-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin: 0 0 16px 0;
}

.installed-card {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 10px 14px;
    min-width: 140px;
}

.installed-card .label {
    font-size: 12px;
    color: #555;
    margin-bottom: 2px;
}

.installed-card .value {
    font-size: 18px;
    font-weight: bold;
}

.installed-legend {
    margin: 0 0 15px 0;
    font-size: 14px;
    padding: 8px 10px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.legend-item {
    display: inline-block;
    padding: 3px 8px;
    margin: 0 4px;
    border-radius: 4px;
    font-weight: bold;
}

.legend-red    { background: #f8d7da; }
.legend-yellow { background: #fff3cd; }
.legend-green  { background: #d1e7dd; }

.short-row  { background-color: #f8d7da !important; }
.equal-row  { background-color: #fff3cd !important; }
.ok-row     { background-color: #d1e7dd !important; }

tr.short-row:hover  { background-color: #f1aeb5 !important; }
tr.equal-row:hover  { background-color: #ffe69c !important; }
tr.ok-row:hover     { background-color: #a3cfbb !important; }

.installed-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 70vh;
    border: 1px solid #ddd;
    background: #fff;
}

.installed-table {
    width: 100%;
    min-width: 1100px;
    border-collapse: collapse;
}

.installed-table th,
.installed-table td {
    padding: 8px 10px;
    border: 1px solid #ddd;
    text-align: left;
    white-space: nowrap;
}

.installed-table thead th {
    position: sticky;
    top: 0;
    background: #f1f3f5;
    z-index: 3;
    cursor: pointer;
}

.installed-table tbody tr:nth-child(even):not(.short-row):not(.equal-row):not(.ok-row) {
    background: #fafafa;
}

.number-cell {
    text-align: right;
}

.filter-summary {
    margin: 10px 0 15px 0;
    padding: 8px 10px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.search-box {
    margin-bottom: 12px;
}

.search-box input {
    padding: 8px 10px;
    width: 320px;
    max-width: 100%;
    font-size: 14px;
}
</style>

<div class="filter-summary">
    <strong>Fiscal Year:</strong> <?= htmlspecialchars($fyRange['label']) ?>
    (<?= htmlspecialchars($fyRange['start_date']) ?> – <?= htmlspecialchars($fyRange['end_date']) ?>)<br>
    <strong>Recommendation rule:</strong> Avg monthly installs × 3 months of coverage (rounded up).
    Shelf Gap &gt; 0 means current on-hand is below the recommended support stock.
</div>

<div class="installed-summary">
    <div class="installed-card">
        <div class="label">Distinct Parts Installed</div>
        <div class="value"><?= number_format($summary['distinct_parts']) ?></div>
    </div>
    <div class="installed-card">
        <div class="label">Total Qty Installed</div>
        <div class="value"><?= number_format($summary['total_qty_installed']) ?></div>
    </div>
    <div class="installed-card">
        <div class="label">Total On Shelf</div>
        <div class="value"><?= number_format($summary['total_on_shelf']) ?></div>
    </div>
    <div class="installed-card">
        <div class="label">Total Recommended</div>
        <div class="value"><?= number_format($summary['total_recommended']) ?></div>
    </div>
    <div class="installed-card">
        <div class="label">Parts Short</div>
        <div class="value" style="color:#842029;"><?= number_format($summary['parts_short']) ?></div>
    </div>
    <div class="installed-card">
        <div class="label">Parts OK / Over</div>
        <div class="value" style="color:#0f5132;"><?= number_format($summary['parts_ok']) ?></div>
    </div>
</div>

<p class="installed-legend">
    <strong>Legend:</strong>
    <span class="legend-item legend-red">Red</span> = Short (On Shelf &lt; Recommended)
    <span class="legend-item legend-yellow">Yellow</span> = Exact match
    <span class="legend-item legend-green">Green</span> = At or above recommended
    <span style="margin-left: 12px; color: #555;">Rows: <?= number_format($rowCount) ?></span>
</p>

<div class="search-box">
    <input type="text" id="installedSearch" placeholder="Search part, nomen, NIIN..." onkeyup="filterInstalledTable()">
</div>

<div class="installed-table-wrap">
    <table class="installed-table" id="installedTable">
        <thead>
            <tr>
                <th onclick="sortInstalledTable(0)">Part<span class="sort-indicator"></span></th>
                <th onclick="sortInstalledTable(1)">Nomen<span class="sort-indicator"></span></th>
                <th onclick="sortInstalledTable(2)">NIIN<span class="sort-indicator"></span></th>
                <th onclick="sortInstalledTable(3)" class="number-cell">Qty Installed FY<span class="sort-indicator"></span></th>
                <th onclick="sortInstalledTable(4)" class="number-cell">Prior FY Qty<span class="sort-indicator"></span></th>
                <th onclick="sortInstalledTable(5)" class="number-cell">On Shelf Qty<span class="sort-indicator"></span></th>
                <th onclick="sortInstalledTable(6)" class="number-cell">Avg Monthly<span class="sort-indicator"></span></th>
                <th onclick="sortInstalledTable(7)" class="number-cell">Recommended Shelf<span class="sort-indicator"></span></th>
                <th onclick="sortInstalledTable(8)" class="number-cell">Shelf Gap<span class="sort-indicator"></span></th>
                <th onclick="sortInstalledTable(9)" class="number-cell">Unit Price<span class="sort-indicator"></span></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($rows)): ?>
                <?php foreach ($rows as $row): ?>
                    <?php
                        $gap = (int)($row['Shelf Gap'] ?? 0);
                        $onShelf = (int)($row['On Shelf Qty'] ?? 0);
                        $recommended = (int)($row['Recommended Shelf Qty'] ?? 0);

                        if ($gap > 0) {
                            $rowClass = 'short-row';
                        } elseif ($onShelf === $recommended) {
                            $rowClass = 'equal-row';
                        } else {
                            $rowClass = 'ok-row';
                        }
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td><?= htmlspecialchars((string)($row['Part'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Nomen'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['NIIN'] ?? '')) ?></td>
                        <td class="number-cell"><?= number_format((int)($row['Qty Installed FY'] ?? 0)) ?></td>
                        <td class="number-cell"><?= number_format((int)($row['Qty Installed Prior FY'] ?? 0)) ?></td>
                        <td class="number-cell"><?= number_format($onShelf) ?></td>
                        <td class="number-cell"><?= number_format((float)($row['Avg Monthly Installs'] ?? 0), 2) ?></td>
                        <td class="number-cell"><?= number_format($recommended) ?></td>
                        <td class="number-cell"><?= number_format($gap) ?></td>
                        <td class="number-cell"><?= number_format((float)($row['Unit Price'] ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" style="text-align:center;">No installed data found for this fiscal year.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function filterInstalledTable() {
    var input = document.getElementById('installedSearch');
    var filter = (input.value || '').toUpperCase();
    var table = document.getElementById('installedTable');
    var trs = table.getElementsByTagName('tr');

    for (var i = 1; i < trs.length; i++) {
        var tds = trs[i].getElementsByTagName('td');
        if (!tds.length) continue;
        var match = false;
        for (var j = 0; j < tds.length; j++) {
            if ((tds[j].textContent || tds[j].innerText || '').toUpperCase().indexOf(filter) > -1) {
                match = true;
                break;
            }
        }
        trs[i].style.display = match ? '' : 'none';
    }
}

function sortInstalledTable(colIndex) {
    var table = document.getElementById('installedTable');
    var tbody = table.tBodies[0];
    var rows = Array.prototype.slice.call(tbody.rows, 0);
    var numericCols = [3, 4, 5, 6, 7, 8, 9];
    var isNumeric = numericCols.indexOf(colIndex) !== -1;
    var dir = table.getAttribute('data-sort-dir') === 'asc' && table.getAttribute('data-sort-col') == colIndex ? 'desc' : 'asc';

    rows.sort(function (a, b) {
        var aText = (a.cells[colIndex].textContent || '').replace(/,/g, '').trim();
        var bText = (b.cells[colIndex].textContent || '').replace(/,/g, '').trim();
        var aVal = isNumeric ? parseFloat(aText) || 0 : aText.toUpperCase();
        var bVal = isNumeric ? parseFloat(bText) || 0 : bText.toUpperCase();
        if (aVal < bVal) return dir === 'asc' ? -1 : 1;
        if (aVal > bVal) return dir === 'asc' ? 1 : -1;
        return 0;
    });

    rows.forEach(function (row) { tbody.appendChild(row); });
    table.setAttribute('data-sort-col', colIndex);
    table.setAttribute('data-sort-dir', dir);
}
</script>