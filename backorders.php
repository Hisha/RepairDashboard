<?php
require_once __DIR__ . "/bootstrap.php";
require_once APP_ROOT . "/bin/Model/BackOrders.php";
require_once APP_ROOT . "/bin/ViewHelpers/table_helper.php";

include 'menu.php';

$reqs = new BackOrders();
$backorders = $reqs->getBackOrderList();

/*
 * Export to Excel-friendly CSV
 * Excel opens CSV files fine, so this is the simplest clean solution.
 */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=backorders_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($backorders)) {
        fputcsv($output, array_keys($backorders[0]));
        
        foreach ($backorders as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

/*
 * Helper to identify high priority rows.
 * Adjust this logic if your system uses different values.
 */
function isHighPriority(string $priority): bool
{
    $priority = strtoupper(trim($priority));
    
    $highPriorityValues = [
        '999',
        'ANORS',
        'CASREP'
    ];
    
    return in_array($priority, $highPriorityValues, true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backordered Requisitions</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 20px;
            background-color: #f8f9fa;
            color: #212529;
        }

        .page-wrap {
            max-width: 1600px;
            margin: 0 auto;
        }

        h1 {
            margin-bottom: 20px;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .search-box input {
            padding: 8px 10px;
            width: 300px;
            max-width: 100%;
            font-size: 14px;
        }

        .export-btn {
            display: inline-block;
            padding: 9px 14px;
            background: #198754;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }

        .export-btn:hover {
            background: #157347;
        }

        .table-wrap {
            overflow-x: auto;
            background: #fff;
            border: 1px solid #ddd;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
        }

        th {
            background: #2c3e50;
            color: #fff;
            text-align: left;
            padding: 10px;
            cursor: pointer;
            position: sticky;
            top: 0;
            white-space: nowrap;
        }

        th:hover {
            background: #1f2d3a;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        tr:nth-child(even) {
            background: #f4f6f8;
        }

        tr:hover {
            background: #eaf2ff;
        }

        tr.high-priority {
            background: #ffe3e3 !important;
        }

        tr.high-priority:hover {
            background: #ffd0d0 !important;
        }

        .sort-indicator {
            margin-left: 6px;
            font-size: 12px;
        }

        .no-results {
            text-align: center;
            padding: 20px;
            font-style: italic;
            display: none;
        }

        .priority-legend {
            font-size: 14px;
            color: #555;
        }

        .priority-chip {
            display: inline-block;
            width: 14px;
            height: 14px;
            background: #ffe3e3;
            border: 1px solid #d99;
            vertical-align: middle;
            margin-right: 6px;
        }
    </style>
</head>
<body>
<div class="page-wrap">
    <h1>Backordered Requisitions</h1>

    <div class="toolbar">
        <div class="search-box">
            <input
                type="text"
                id="tableSearch"
                placeholder="Search backorders..."
                onkeyup="filterTable()"
            >
        </div>

        <div class="priority-legend">
            <span class="priority-chip"></span> High Priority Highlighted
        </div>

        <div>
            <a class="export-btn" href="BackOrder.php?export=csv">Export to Excel</a>
        </div>
    </div>

    <div class="table-wrap">
        <table id="backorderTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">Date Received <span class="sort-indicator"></span></th>
                    <th onclick="sortTable(1)">Program <span class="sort-indicator"></span></th>
                    <th onclick="sortTable(2)">Priority <span class="sort-indicator"></span></th>
                    <th onclick="sortTable(3)">Command <span class="sort-indicator"></span></th>
                    <th onclick="sortTable(4)">Req Number <span class="sort-indicator"></span></th>
                    <th onclick="sortTable(5)">NIIN <span class="sort-indicator"></span></th>
                    <th onclick="sortTable(6)">Nomen <span class="sort-indicator"></span></th>
                    <th onclick="sortTable(7)">QTY <span class="sort-indicator"></span></th>
                    <th onclick="sortTable(8)">Notes <span class="sort-indicator"></span></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($backorders)): ?>
                    <?php foreach ($backorders as $row): ?>
                        <?php $rowClass = isHighPriority((string)($row['Priority'] ?? '')) ? 'high-priority' : ''; ?>
                        <tr class="<?= htmlspecialchars($rowClass) ?>">
                            <td><?= htmlspecialchars((string)($row['Date Received'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Program'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Priority'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Command'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Req Number'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['NIIN'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Nomen'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['QTY'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Notes'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding:20px;">No backordered requisitions found.</td>
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
    const table = document.getElementById('backorderTable');
    const tbody = table.getElementsByTagName('tbody')[0];
    const rows = tbody.getElementsByTagName('tr');
    let visibleCount = 0;

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');

        if (cells.length === 0) {
            continue;
        }

        let match = false;

        for (let j = 0; j < cells.length; j++) {
            const text = cells[j].textContent || cells[j].innerText;
            if (text.toLowerCase().indexOf(filter) > -1) {
                match = true;
                break;
            }
        }

        row.style.display = match ? '' : 'none';

        if (match) {
            visibleCount++;
        }
    }

    document.getElementById('noResultsMessage').style.display = visibleCount === 0 ? 'block' : 'none';
}

function sortTable(columnIndex) {
    const table = document.getElementById('backorderTable');
    const tbody = table.getElementsByTagName('tbody')[0];
    const rows = Array.from(tbody.querySelectorAll('tr'));

    if (rows.length === 0) {
        return;
    }

    let direction = 'asc';
    if (currentSortColumn === columnIndex && currentSortDirection === 'asc') {
        direction = 'desc';
    }

    rows.sort((a, b) => {
        const aCells = a.getElementsByTagName('td');
        const bCells = b.getElementsByTagName('td');

        if (!aCells[columnIndex] || !bCells[columnIndex]) {
            return 0;
        }

        let aText = aCells[columnIndex].textContent.trim();
        let bText = bCells[columnIndex].textContent.trim();

        const aNum = parseFloat(aText.replace(/,/g, ''));
        const bNum = parseFloat(bText.replace(/,/g, ''));

        const aDate = Date.parse(aText);
        const bDate = Date.parse(bText);

        let comparison = 0;

        if (!isNaN(aDate) && !isNaN(bDate)) {
            comparison = aDate - bDate;
        } else if (!isNaN(aNum) && !isNaN(bNum)) {
            comparison = aNum - bNum;
        } else {
            comparison = aText.localeCompare(bText, undefined, { numeric: true, sensitivity: 'base' });
        }

        return direction === 'asc' ? comparison : -comparison;
    });

    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }

    rows.forEach(row => tbody.appendChild(row));

    currentSortColumn = columnIndex;
    currentSortDirection = direction;

    updateSortIndicators(columnIndex, direction);
}

function updateSortIndicators(activeColumn, direction) {
    const headers = document.querySelectorAll('#backorderTable th');

    headers.forEach((header, index) => {
        const indicator = header.querySelector('.sort-indicator');
        if (!indicator) {
            return;
        }

        if (index === activeColumn) {
            indicator.textContent = direction === 'asc' ? '▲' : '▼';
        } else {
            indicator.textContent = '';
        }
    });
}
</script>
</body>
</html>