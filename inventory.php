<?php
require_once __DIR__ . "/bootstrap.php";
require_once APP_ROOT . "/vendor/autoload.php";
require_once APP_ROOT . "/bin/Utilities/xlsx_helper.php";
require_once APP_ROOT . "/bin/Model/Inventory.php";

$inventory = new Inventory();
$inventory = $inventory->getInventory();

/*
 * Export XLSX for Excel
 * Must run before ANY HTML output.
 */
if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    $headers = !empty($inventory) ? array_keys($inventory[0]) : [
        'Part',
        'Nomen',
        'NIIN',
        'Condition Code',
        'Qty',
        'Program',
        'Purpose Code',
        'Location',
        'Unit Price'
    ];
    
    xlsx_helper::download(
        'inventory_' . date('Y-m-d') . '.xlsx',
        $headers,
        $inventory,
        ['Part', 'NIIN', 'Program', 'Location'],
        'inventory'
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
    <title>inventory</title>
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
        
        .completed-row {
            background-color: #A6A6A6 !important;
        }
        
        tr.completed-row:hover {
            background-color: #999999;
        }
    </style>
</head>
<body>

<div class="page-wrap">

    <h1>Inventory</h1>

	<div class="toolbar">
        <div class="search-box">
            <input
                type="text"
                id="tableSearch"
                placeholder="Search Inventory..."
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
        <table id="inventoryTable">
            <thead>
                <tr>
                	<th onclick="sortTable(0)">Part<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(1)">Nomen<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(2)">NIIN<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(3)">Condition Code<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(4)">Qty<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(5)">Program<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(6)">Purpose Code<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(7)">Location<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(8)">Unit Price<span class="sort-indicator"></span></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($inventory)): ?>
                    <?php foreach ($inventory as $row): ?>
                        <tr>
                        	<td><?= htmlspecialchars((string)($row['Part'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Nomen'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['NIIN'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Condition Code'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Qty'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Program'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Purpose Code'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Location'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Unit Price'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="19" style="text-align:center; padding:20px;">
                            No Inventory found.
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
    const rows = document.querySelectorAll('#inventoryTable tbody tr');
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
    const tbody = document.querySelector('#inventoryTable tbody');
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
const inventoryTable = document.getElementById('inventoryTable');

function syncScrollWidths() {
    if (inventoryTable) {
        topScrollInner.style.width = inventoryTable.scrollWidth + 'px';
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