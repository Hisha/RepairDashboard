<?php
require_once __DIR__ . "/bootstrap.php";
require_once APP_ROOT . "/bin/Model/BackOrders.php";

$selectedPriority = $_GET['priority'] ?? null;

$reqs = new BackOrders();
$backorders = $reqs->getBackOrderList($selectedPriority);

/*
 * Export CSV for Excel
 * Must run before ANY HTML output.
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
 * Highlight high priority rows
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

$exportUrl = $_SERVER['PHP_SELF'] . '?export=csv';

if (!empty($selectedPriority)) {
    $exportUrl .= '&priority=' . urlencode($selectedPriority);
}

include 'menu.php';
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
            background: #f8f9fa;
            color: #212529;
        }

        .page-wrap {
            max-width: 1600px;
            margin: auto;
        }

        h1 {
            margin-bottom: 10px;
        }

        .filter-info {
            margin-bottom: 15px;
            font-size: 14px;
        }

        .clear-filter {
            display: inline-block;
            margin-left: 10px;
            text-decoration: none;
            color: #0d6efd;
            font-weight: bold;
        }

        .clear-filter:hover {
            text-decoration: underline;
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

        .table-wrap {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 75vh;
            background: white;
            border: 1px solid #ddd;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background: #2c3e50;
            color: white;
            padding: 10px;
            text-align: left;
            cursor: pointer;
            white-space: nowrap;
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
            display: none;
            font-style: italic;
        }

        .priority-chip {
            display: inline-block;
            width: 14px;
            height: 14px;
            background: #ffe3e3;
            border: 1px solid #d99;
            margin-right: 6px;
            vertical-align: middle;
        }

        .legend {
            font-size: 14px;
            color: #555;
        }
    </style>
</head>
<body>

<div class="page-wrap">

    <h1>
        Backordered Requisitions
        <?php if (!empty($selectedPriority)): ?>
            - Priority: <?= htmlspecialchars($selectedPriority) ?>
        <?php endif; ?>
    </h1>

    <?php if (!empty($selectedPriority)): ?>
        <div class="filter-info">
            Showing only records for priority <strong><?= htmlspecialchars($selectedPriority) ?></strong>
            <a class="clear-filter" href="backorders.php">Clear Filter</a>
        </div>
    <?php endif; ?>

    <div class="toolbar">
        <div class="search-box">
            <input
                type="text"
                id="tableSearch"
                placeholder="Search backorders..."
                onkeyup="filterTable()"
            >
        </div>

        <div class="legend">
            <span class="priority-chip"></span> High Priority Highlighted
        </div>

        <div>
            <a class="export-btn" href="<?= htmlspecialchars($exportUrl) ?>">Export to Excel</a>
        </div>
    </div>

    <div class="table-wrap">
        <table id="backorderTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">Date Received<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(1)">Program<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(2)">Priority<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(3)">Command<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(4)">Req Number<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(5)">NIIN<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(6)">Nomen<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(7)">QTY<span class="sort-indicator"></span></th>
                    <th onclick="sortTable(8)">Notes<span class="sort-indicator"></span></th>
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
                        <td colspan="9" style="text-align:center; padding:20px;">
                            No backordered requisitions found.
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
    const rows = document.querySelectorAll('#backorderTable tbody tr');
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
    const tbody = document.querySelector('#backorderTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    let dir = 'asc';

    if (currentSortColumn === col && currentSortDirection === 'asc') {
        dir = 'desc';
    }

    rows.sort((a, b) => {
        let A = a.children[col].innerText.trim();
        let B = b.children[col].innerText.trim();

        let numA = parseFloat(A.replace(/,/g, ''));
        let numB = parseFloat(B.replace(/,/g, ''));

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
}

function updateSortIndicators(col, dir) {
    document.querySelectorAll('.sort-indicator').forEach(el => el.textContent = '');

    const arrows = document.querySelectorAll('#backorderTable th .sort-indicator');
    if (arrows[col]) {
        arrows[col].textContent = dir === 'asc' ? '▲' : '▼';
    }
}
</script>

</body>
</html>