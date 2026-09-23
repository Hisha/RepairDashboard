<?php
require_once APP_ROOT . '/bin/Model/Procurements.php';

$procure = new Procurements();
$backorderProcurements = $procure->getBackOrderProcurements();

$exportUrl = 'procurements.php?tab=backorder_procurements&export=xlsx';
$rowCount = count($backorderProcurements);
?>

<style>
.backorder-subtext {
    margin: 0 0 12px 0;
    color: #555;
    font-size: 14px;
}

.procurement-legend {
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

.legend-yellow { background: #fff3cd; }

.no-open-procurement-row {
    background-color: #fff3cd !important;
}

tr.no-open-procurement-row:hover {
    background-color: #ffe69c !important;
}

#backorderProcureTable td.contract-info-cell {
    white-space: normal;
    min-width: 280px;
    max-width: 520px;
}

#backorderProcureTable td.number-cell {
    text-align: right;
}
</style>

<h2>Backorder Procurements</h2>

<p class="backorder-subtext">
    Backordered NIINs from CAVS, left-joined to open procurements
    (status not Canceled or Completed). Quantities are rolled up by NIIN.
</p>

<p class="procurement-legend">
    <strong>Legend:</strong>
    <span class="legend-item legend-yellow">Yellow</span> = No open procurement covering this NIIN.
    <span style="margin-left: 12px; color: #555;">Rows: <?= number_format($rowCount) ?></span>
</p>

<div class="toolbar">
    <div class="search-box">
        <input
            type="text"
            id="backorderTableSearch"
            placeholder="Search backorder procurements..."
            onkeyup="filterBackorderTable()"
        >
    </div>

    <div>
        <a class="export-btn" href="<?= htmlspecialchars($exportUrl) ?>">Export to Excel</a>
    </div>
</div>

<div class="top-scroll" id="backorderTopScroll">
    <div class="top-scroll-inner" id="backorderTopScrollInner"></div>
</div>

<div class="table-wrap" id="backorderTableWrap">
    <table id="backorderProcureTable">
        <thead>
            <tr>
                <th onclick="sortBackorderTable(0)">NIIN<span class="sort-indicator"></span></th>
                <th onclick="sortBackorderTable(1)">Support Qty<span class="sort-indicator"></span></th>
                <th onclick="sortBackorderTable(2)">I.O. Qty<span class="sort-indicator"></span></th>
                <th onclick="sortBackorderTable(3)">Requested Qty<span class="sort-indicator"></span></th>
                <th onclick="sortBackorderTable(4)">On Order Qty<span class="sort-indicator"></span></th>
                <th onclick="sortBackorderTable(5)">Purchase Vehicle<span class="sort-indicator"></span></th>
                <th onclick="sortBackorderTable(6)">Contract Info<span class="sort-indicator"></span></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($backorderProcurements)): ?>
                <?php foreach ($backorderProcurements as $row): ?>
                    <?php
                    $requestedQty = (float)($row['Requested Qty'] ?? 0);
                    $onOrderQty = (float)($row['On Order Qty'] ?? 0);
                    $hasOpenProcurement = ($requestedQty > 0 || $onOrderQty > 0);
                    $rowClass = $hasOpenProcurement ? '' : 'no-open-procurement-row';
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td><?= htmlspecialchars((string)($row['NIIN'] ?? '')) ?></td>
                        <td class="number-cell"><?= htmlspecialchars((string)($row['Support Qty'] ?? '')) ?></td>
                        <td class="number-cell"><?= htmlspecialchars((string)($row['I.O. Qty'] ?? '')) ?></td>
                        <td class="number-cell"><?= htmlspecialchars((string)($row['Requested Qty'] ?? '')) ?></td>
                        <td class="number-cell"><?= htmlspecialchars((string)($row['On Order Qty'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Purchase Vehicle'] ?? '')) ?></td>
                        <td class="contract-info-cell"><?= htmlspecialchars((string)($row['Contract Info'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:20px;">
                        No backorder procurements found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div id="backorderNoResultsMessage" class="no-results">
        No matching records found.
    </div>
</div>

<script>
let backorderSortColumn = -1;
let backorderSortDirection = 'asc';

function filterBackorderTable() {
    const input = document.getElementById('backorderTableSearch');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('#backorderProcureTable tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();

        if (text.includes(filter)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    document.getElementById('backorderNoResultsMessage').style.display =
        visibleCount === 0 ? 'block' : 'none';
}

function sortBackorderTable(col) {
    const tbody = document.querySelector('#backorderProcureTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    let dir = 'asc';

    if (backorderSortColumn === col && backorderSortDirection === 'asc') {
        dir = 'desc';
    }

    rows.sort((a, b) => {
        let A = a.children[col] ? a.children[col].innerText.trim() : '';
        let B = b.children[col] ? b.children[col].innerText.trim() : '';

        let numA = parseFloat(A.replace(/,/g, '').replace(/\$/g, ''));
        let numB = parseFloat(B.replace(/,/g, '').replace(/\$/g, ''));

        let result = 0;

        if (!isNaN(numA) && !isNaN(numB) && A !== '' && B !== '') {
            result = numA - numB;
        } else {
            result = A.localeCompare(B, undefined, { numeric: true, sensitivity: 'base' });
        }

        return dir === 'asc' ? result : -result;
    });

    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));

    backorderSortColumn = col;
    backorderSortDirection = dir;

    updateBackorderSortIndicators(col, dir);
    syncBackorderScrollWidths();
}

function updateBackorderSortIndicators(col, dir) {
    document.querySelectorAll('#backorderProcureTable .sort-indicator').forEach(el => el.textContent = '');

    const arrows = document.querySelectorAll('#backorderProcureTable th .sort-indicator');
    if (arrows[col]) {
        arrows[col].textContent = dir === 'asc' ? '▲' : '▼';
    }
}

const backorderTopScroll = document.getElementById('backorderTopScroll');
const backorderTableWrap = document.getElementById('backorderTableWrap');
const backorderTopScrollInner = document.getElementById('backorderTopScrollInner');
const backorderProcureTable = document.getElementById('backorderProcureTable');

function syncBackorderScrollWidths() {
    if (backorderProcureTable && backorderTopScrollInner) {
        backorderTopScrollInner.style.width = backorderProcureTable.scrollWidth + 'px';
    }
}

let backorderSyncingTop = false;
let backorderSyncingBottom = false;

if (backorderTopScroll && backorderTableWrap) {
    backorderTopScroll.addEventListener('scroll', () => {
        if (backorderSyncingBottom) return;
        backorderSyncingTop = true;
        backorderTableWrap.scrollLeft = backorderTopScroll.scrollLeft;
        backorderSyncingTop = false;
    });

    backorderTableWrap.addEventListener('scroll', () => {
        if (backorderSyncingTop) return;
        backorderSyncingBottom = true;
        backorderTopScroll.scrollLeft = backorderTableWrap.scrollLeft;
        backorderSyncingBottom = false;
    });
}

window.addEventListener('load', syncBackorderScrollWidths);
window.addEventListener('resize', syncBackorderScrollWidths);
syncBackorderScrollWidths();
</script>
