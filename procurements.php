<?php
require_once __DIR__ . "/bootstrap.php";
require_once APP_ROOT . "/vendor/autoload.php";
require_once APP_ROOT . "/bin/Utilities/xlsx_helper.php";
require_once APP_ROOT . "/bin/Model/Procurements.php";

$procure = new Procurements();
$procurements = $procure->getProcurements();

/*
 * Export XLSX for Excel
 * Must run before ANY HTML output.
 */
if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    $headers = !empty($procurements) ? array_keys($procurements[0]) : [
        'Folder',
        'Program',
        'Request Date',
        'NIIN',
        'Part',
        'Nomen',
        'Qty Requested',
        'Requested By',
        'Status',
        'Purchase Vehicle',
        'Item Cost (each)',
        'Date Submitted',
        'Contract Number',
        'Quote Number',
        'PO Number',
        'Qty Ordered',
        'Award Date',
        'EDD Date',
        'Receive Date',
        'Comments'
    ];

    xlsx_helper::download(
        'procurements_' . date('Y-m-d') . '.xlsx',
        $headers,
        $procurements,
        ['NIIN', 'Part', 'Contract Number', 'Quote Number', 'PO Number'],
        'Procurements'
    );
}

$exportUrl = $_SERVER['PHP_SELF'] . '?export=xlsx';

include 'menu.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procurements</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 20px;
            background: #f8f9fa;
            color: #212529;
        }

        .page-wrap {
            max-width: 100%;
            margin: 0;
        }

        h1 {
            margin-bottom: 10px;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .search-box input {
            padding: 8px 10px;
            width: 300px;
            max-width: 100%;
            font-size: 14px;
        }

        .export-btn {
            padding: 9px 14px;
            background: #198754;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }

        .export-btn:hover {
            background: #157347;
        }

        .top-scroll {
            overflow-x: auto;
            overflow-y: hidden;
            height: 18px;
            margin-bottom: 6px;
            background: #fff;
            border: 1px solid #ddd;
            border-bottom: none;
        }

        .top-scroll-inner {
            height: 1px;
        }

        .table-wrap {
            overflow: auto;
            background: white;
            border: 1px solid #ddd;
            max-height: 75vh;
        }

        table {
            border-collapse: collapse;
            width: max-content;
            min-width: 100%;
            table-layout: auto;
        }

        th,
        td {
            white-space: nowrap;
        }

        th {
            background: #2c3e50;
            color: white;
            padding: 10px;
            text-align: left;
            cursor: pointer;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        th:hover {
            background: #1f2d3a;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        td.comments-cell {
            white-space: normal;
            min-width: 250px;
            max-width: 450px;
        }

        tr:nth-child(even) {
            background: #f4f6f8;
        }

        tr:hover {
            background: #eaf2ff;
        }

        .sort-indicator {
            margin-left: 6px;
            font-size: 12px;
        }

        .no-results {
            text-align: center;
            padding: 20px;
            display: none;
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="page-wrap">

    <h1>Procurements</h1>

    <div class="toolbar">
        <div class="search-box">
            <input
                type="text"
                id="tableSearch"
                placeholder="Search procurements..."
                onkeyup="filterTable()"
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
                	<th onclick="sortTable(0)">Folder<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(1)">Program<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(2)">Request Date<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(3)">NIIN<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(4)">Part<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(5)">Nomen<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(6)">Qty Requested<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(7)">Requested By<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(8)">Status<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(9)">Purchase Vehicle<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(10)">Item Cost (each)<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(11)">Date Submitted<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(12)">Contract Number<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(13)">Quote Number<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(14)">PO Number<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(15)">Qty Ordered<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(16)">Award Date<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(17)">EDD Date<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(18)">Receive Date<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(19)">Comments<span class="sort-indicator"></span></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($procurements)): ?>
                    <?php foreach ($procurements as $row): ?>
                        <tr>
                        	<td><?= htmlspecialchars((string)($row['Folder'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Program'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Request Date'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['NIIN'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Part'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Nomen'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Qty Requested'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Requested By'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Status'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Purchase Vehicle'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Item Cost (each)'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Date Submitted'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Contract Number'] ?? '')) ?></td>
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
                        <td colspan="19" style="text-align:center; padding:20px;">
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
</div>

<script>
let currentSortColumn = -1;
let currentSortDirection = 'asc';

function filterTable() {
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

function sortTable(col) {
    const tbody = document.querySelector('#procurementTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    let dir = 'asc';

    if (currentSortColumn === col && currentSortDirection === 'asc') {
        dir = 'desc';
    }

    rows.sort((a, b) => {
        let A = a.children[col].innerText.trim();
        let B = b.children[col].innerText.trim();

        let numA = parseFloat(A.replace(/,/g, '').replace(/\$/g, ''));
        let numB = parseFloat(B.replace(/,/g, '').replace(/\$/g, ''));

        let dateA = Date.parse(A);
        let dateB = Date.parse(B);

        let result = 0;

        if (!isNaN(dateA) && !isNaN(dateB)) {
            result = dateA - dateB;
        } else if (!isNaN(numA) && !isNaN(numB)) {
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

    updateSortIndicators(col, dir);
    syncScrollWidths();
}

function updateSortIndicators(col, dir) {
    document.querySelectorAll('.sort-indicator').forEach(el => el.textContent = '');

    const arrows = document.querySelectorAll('#procurementTable th .sort-indicator');
    if (arrows[col]) {
        arrows[col].textContent = dir === 'asc' ? '▲' : '▼';
    }
}

const topScroll = document.getElementById('topScroll');
const tableWrap = document.getElementById('tableWrap');
const topScrollInner = document.getElementById('topScrollInner');
const procurementTable = document.getElementById('procurementTable');

function syncScrollWidths() {
    if (procurementTable) {
        topScrollInner.style.width = procurementTable.scrollWidth + 'px';
    }
}

let syncingTop = false;
let syncingBottom = false;

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

window.addEventListener('load', syncScrollWidths);
window.addEventListener('resize', syncScrollWidths);
</script>

</body>
</html>