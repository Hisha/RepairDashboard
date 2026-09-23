<?php
require_once APP_ROOT . '/bin/Model/Procurements.php';

$procure = new Procurements();
$procurements = $procure->getProcurements();

$exportUrl = 'procurements.php?tab=procurements&export=xlsx';
$rowCount = count($procurements);
?>

<style>
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

.legend-grey { background: #A6A6A6; }

.completed-row {
    background-color: #A6A6A6 !important;
}

tr.completed-row:hover {
    background-color: #999999;
}

#procurementTable td.comments-cell {
    white-space: normal;
    min-width: 250px;
    max-width: 450px;
}
</style>

<h2>All Procurements</h2>

<p class="procurement-legend">
    <strong>Legend:</strong>
    <span class="legend-item legend-grey">Grey</span> = Procurement Completed.
    <span style="margin-left: 12px; color: #555;">Rows: <?= number_format($rowCount) ?></span>
</p>

<div class="toolbar">
    <div class="search-box">
        <input
            type="text"
            id="tableSearch"
            placeholder="Search procurements..."
            onkeyup="filterProcurementTable()"
        >
    </div>

    <div>
        <a class="export-btn" href="<?= htmlspecialchars($exportUrl) ?>">Export to Excel</a>
    </div>
</div>

<div class="top-scroll" id="topScroll">
    <div class="top-scroll-inner" id="topScrollInner"></div>
</div>

<div class="table-wrap" id="tableWrap">
    <table id="procurementTable">
        <thead>
            <tr>
                <th onclick="sortProcurementTable(0)">Folder<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(1)">Program<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(2)">Request Date<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(3)">NIIN<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(4)">Part<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(5)">Nomen<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(6)">Purchase Type<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(7)">Qty Requested<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(8)">Requested By<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(9)">Status<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(10)">Purchase Vehicle<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(11)">Item Cost (each)<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(12)">Extended Cost<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(13)">Quote Request Date<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(14)">Date Submitted<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(15)">Contract Number<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(16)">Clin Number<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(17)">Quote Number<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(18)">PO Number<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(19)">Qty Ordered<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(20)">Award Date<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(21)">EDD Date<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(22)">Receive Date<span class="sort-indicator"></span></th>
                <th onclick="sortProcurementTable(23)">Comments<span class="sort-indicator"></span></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($procurements)): ?>
                <?php foreach ($procurements as $row): ?>
                    <tr class="<?= (isset($row['Status']) && strtoupper(trim((string)$row['Status'])) === 'COMPLETED') ? 'completed-row' : '' ?>">
                        <td><?= htmlspecialchars((string)($row['Folder'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Program'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Request Date'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['NIIN'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Part'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Nomen'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Purchase Type'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Qty Requested'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Requested By'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Status'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Purchase Vehicle'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Item Cost (each)'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Extended Cost'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Quote Request Date'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Date Submitted'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Contract Number'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Clin Number'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Quote Number'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['PO Number'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Qty Ordered'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Award Date'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['EDD Date'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['Receive Date'] ?? '')) ?></td>
                        <td class="comments-cell"><?= htmlspecialchars((string)($row['Comments'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="24" style="text-align:center; padding:20px;">
                        No procurements found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div id="noResultsMessage" class="no-results">
        No matching records found.
    </div>
</div>

<script>
let currentSortColumn = -1;
let currentSortDirection = 'asc';

function filterProcurementTable() {
    const input = document.getElementById('tableSearch');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('#procurementTable tbody tr');
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

    document.getElementById('noResultsMessage').style.display =
        visibleCount === 0 ? 'block' : 'none';
}

function sortProcurementTable(col) {
    const tbody = document.querySelector('#procurementTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    let dir = 'asc';

    if (currentSortColumn === col && currentSortDirection === 'asc') {
        dir = 'desc';
    }

    rows.sort((a, b) => {
        let A = a.children[col] ? a.children[col].innerText.trim() : '';
        let B = b.children[col] ? b.children[col].innerText.trim() : '';

        let numA = parseFloat(A.replace(/,/g, '').replace(/\$/g, ''));
        let numB = parseFloat(B.replace(/,/g, '').replace(/\$/g, ''));

        let dateA = Date.parse(A);
        let dateB = Date.parse(B);

        let result = 0;

        if (!isNaN(dateA) && !isNaN(dateB) && A !== '' && B !== '') {
            result = dateA - dateB;
        } else if (!isNaN(numA) && !isNaN(numB) && A !== '' && B !== '') {
            result = numA - numB;
        } else {
            result = A.localeCompare(B, undefined, { numeric: true, sensitivity: 'base' });
        }

        return dir === 'asc' ? result : -result;
    });

    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));

    currentSortColumn = col;
    currentSortDirection = dir;

    updateProcurementSortIndicators(col, dir);
    syncProcurementScrollWidths();
}

function updateProcurementSortIndicators(col, dir) {
    document.querySelectorAll('#procurementTable .sort-indicator').forEach(el => el.textContent = '');

    const arrows = document.querySelectorAll('#procurementTable th .sort-indicator');
    if (arrows[col]) {
        arrows[col].textContent = dir === 'asc' ? '▲' : '▼';
    }
}

const topScroll = document.getElementById('topScroll');
const tableWrap = document.getElementById('tableWrap');
const topScrollInner = document.getElementById('topScrollInner');
const procurementTable = document.getElementById('procurementTable');

function syncProcurementScrollWidths() {
    if (procurementTable && topScrollInner) {
        topScrollInner.style.width = procurementTable.scrollWidth + 'px';
    }
}

let syncingTop = false;
let syncingBottom = false;

if (topScroll && tableWrap) {
    topScroll.addEventListener('scroll', () => {
        if (syncingBottom) return;
        syncingTop = true;
        tableWrap.scrollLeft = topScroll.scrollLeft;
        syncingTop = false;
    });

    tableWrap.addEventListener('scroll', () => {
        if (syncingTop) return;
        syncingBottom = true;
        topScroll.scrollLeft = tableWrap.scrollLeft;
        syncingBottom = false;
    });
}

window.addEventListener('load', syncProcurementScrollWidths);
window.addEventListener('resize', syncProcurementScrollWidths);
syncProcurementScrollWidths();
</script>
