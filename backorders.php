<?php
require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/bin/Utilities/xlsx_helper.php';
require_once APP_ROOT . '/bin/Model/BackOrders.php';

$selectedTab = ($_GET['tab'] ?? 'all') === 'aging' ? 'aging' : 'all';
$selectedPriority = trim((string)($_GET['priority'] ?? ''));
$agingBucket = (string)($_GET['age'] ?? '30plus');
$ageRanges = [
    '30plus' => ['label' => 'All Over 30 Days', 'min' => 31, 'max' => null],
    '31-60'  => ['label' => '31–60 Days', 'min' => 31, 'max' => 60],
    '61-90'  => ['label' => '61–90 Days', 'min' => 61, 'max' => 90],
    '91-120' => ['label' => '91–120 Days', 'min' => 91, 'max' => 120],
    '121plus'=> ['label' => '121+ Days', 'min' => 121, 'max' => null],
];
if (!isset($ageRanges[$agingBucket])) {
    $agingBucket = '30plus';
}

function backordersUrl(string $tab, string $age, string $priority = '', bool $export = false): string
{
    $params = ['tab' => $tab];
    if ($tab === 'aging') $params['age'] = $age;
    if ($priority !== '') $params['priority'] = $priority;
    if ($export) $params['export'] = 'xlsx';
    return 'backorders.php?' . http_build_query($params);
}

function isHighPriority(string $priority): bool
{
    return in_array(strtoupper(trim($priority)), ['999', 'ANORS', 'CASREP'], true);
}

$reqs = new BackOrders();
$today = new DateTimeImmutable('today');
if ($selectedTab === 'aging') {
    $range = $ageRanges[$agingBucket];
    // Inclusive date boundaries: older than 30 days starts at 31 days.
    $endDate = $today->modify('-' . $range['min'] . ' days')->format('Y-m-d');
    $startDate = $range['max'] === null
    ? '1000-01-01'
        : $today->modify('-' . $range['max'] . ' days')->format('Y-m-d');
        $backorders = $reqs->getBackOrderListByDateRange($startDate, $endDate);
        if ($selectedPriority !== '') {
            $backorders = array_values(array_filter($backorders, static fn($r) => (string)($r['Priority'] ?? '') === $selectedPriority));
        }
} else {
    $backorders = $reqs->getBackOrderList($selectedPriority !== '' ? $selectedPriority : null);
}

if (($_GET['export'] ?? '') === 'xlsx') {
    $headers = ['Date Received', 'Program', 'Priority', 'Command', 'Req Number', 'NIIN', 'Nomen', 'QTY', 'Notes'];
    $filename = $selectedTab === 'aging' ? 'backorders_aging_' . $agingBucket . '_' : 'backorders_';
    xlsx_helper::download($filename . date('Y-m-d') . '.xlsx', $headers, $backorders, ['Req Number', 'NIIN'], 'Backorders');
    exit;
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
        .page-header { display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:15px; }
        .page-header h1 { margin:0; }
        .tab-bar { display:flex; gap:6px; align-items:flex-end; flex-wrap:wrap; }
        .tab-link { padding:12px 20px; text-decoration:none; color:#212529; background:#d9dee3; border:1px solid #bfc7cf; border-bottom:none; border-radius:10px 10px 0 0; font-weight:bold; }
        .tab-link.active { background:#fff; position:relative; top:1px; z-index:2; }
        .tab-panel { background:#fff; border:1px solid #bfc7cf; border-radius:0 8px 8px 8px; padding:20px; min-height:500px; }
        .aging-controls { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:15px; }
        .aging-link { padding:8px 12px; background:#e9ecef; color:#212529; text-decoration:none; border:1px solid #ced4da; border-radius:5px; }
        .aging-link.active { background:#0d6efd; border-color:#0d6efd; color:white; }
        .aging-info { margin:0 0 15px; color:#555; }
        .count { font-weight:bold; }
    </style>
</head>
<body>
<div class="page-wrap">
    <div class="page-header">
        <h1>Backordered Requisitions</h1>
        <span class="count"><?= number_format(count($backorders)) ?> records</span>
    </div>
    <div class="tab-bar">
        <a class="tab-link <?= $selectedTab === 'all' ? 'active' : '' ?>" href="<?= htmlspecialchars(backordersUrl('all', '30plus', $selectedPriority)) ?>">All Backorders</a>
        <a class="tab-link <?= $selectedTab === 'aging' ? 'active' : '' ?>" href="<?= htmlspecialchars(backordersUrl('aging', $agingBucket, $selectedPriority)) ?>">Aging Backorders</a>
    </div>
    <div class="tab-panel">
        <?php if ($selectedTab === 'aging'): ?>
            <div class="aging-controls">
                <?php foreach ($ageRanges as $key => $range): ?>
                    <a class="aging-link <?= $agingBucket === $key ? 'active' : '' ?>" href="<?= htmlspecialchars(backordersUrl('aging', $key, $selectedPriority)) ?>"><?= htmlspecialchars($range['label']) ?></a>
                <?php endforeach; ?>
            </div>
            <p class="aging-info">Showing currently backordered requisitions received <?= htmlspecialchars($ageRanges[$agingBucket]['label']) ?> ago, measured in calendar days through <?= htmlspecialchars($today->format('Y-m-d')) ?>.</p>
        <?php endif; ?>
        <?php if ($selectedPriority !== ''): ?>
            <div class="filter-info">Showing priority <strong><?= htmlspecialchars($selectedPriority) ?></strong>
                <a class="clear-filter" href="<?= htmlspecialchars(backordersUrl($selectedTab, $agingBucket)) ?>">Clear Filter</a>
            </div>
        <?php endif; ?>
        <div class="toolbar">
            <div class="search-box"><input type="text" id="tableSearch" placeholder="Search backorders..." oninput="filterTable()"></div>
            <div class="legend"><span class="priority-chip"></span> High Priority Highlighted</div>
            <a class="export-btn" href="<?= htmlspecialchars(backordersUrl($selectedTab, $agingBucket, $selectedPriority, true)) ?>">Export to Excel</a>
        </div>
        <div class="table-wrap">
            <table id="backorderTable">
                <thead><tr>
                    <?php foreach (['Date Received', 'Program', 'Priority', 'Command', 'Req Number', 'NIIN', 'Nomen', 'QTY', 'Notes'] as $i => $header): ?>
                        <th onclick="sortTable(<?= $i ?>)"><?= htmlspecialchars($header) ?><span class="sort-indicator"></span></th>
                    <?php endforeach; ?>
                </tr></thead>
                <tbody>
                    <?php foreach ($backorders as $row): ?>
                        <tr class="<?= isHighPriority((string)($row['Priority'] ?? '')) ? 'high-priority' : '' ?>">
                            <?php foreach (['Date Received', 'Program', 'Priority', 'Command', 'Req Number', 'NIIN', 'Nomen', 'QTY', 'Notes'] as $column): ?>
                                <td><?= htmlspecialchars((string)($row[$column] ?? '')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div id="noResultsMessage" class="no-results">No matching records found.</div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', filterTable);
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