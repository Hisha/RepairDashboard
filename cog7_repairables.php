<?php
require_once __DIR__ . "/bootstrap.php";
include 'menu.php';
require_once APP_ROOT . "/bin/Model/Cog7Repairables.php";

$model = new Cog7Repairables();
$rows = $model->getReport();

$preselectedNiin = trim($_GET['niin'] ?? '');

$lrcOptions = [];
foreach ($rows as $row) {
    if (!empty($row['lrc'])) {
        $lrcOptions[$row['lrc']] = true;
    }
}
$lrcOptions = array_keys($lrcOptions);
sort($lrcOptions);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Survival Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { margin-bottom: 8px; }
        .subtext { color: #555; margin-bottom: 18px; font-size: 14px; }
        .table-wrap { overflow-x: auto; }

        .controls {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
            align-items: end;
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        label {
            font-size: 13px;
            font-weight: bold;
        }

        input, select, button {
            padding: 6px 8px;
            font-size: 14px;
        }

        button {
            cursor: pointer;
        }

        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f2f2f2; position: sticky; top: 0; z-index: 2; }

        .bad { background: #ffd6d6; }
        .warn { background: #fff3cd; }
        .good { background: #d9f2d9; }

        .num { text-align: right; white-space: nowrap; }
        .center { text-align: center; }

        .hidden-row {
            display: none;
        }

        .results-count {
            margin-bottom: 10px;
            font-size: 14px;
            color: #444;
        }
    </style>
</head>
<body>

<h2>Survival Report</h2>
<div class="subtext">
    Shows COG 7 NIINs shipped in the last 3 years, sorted by most recent ship date.
    Survival % = percent of repair actions that ended in A, D, or G condition.
</div>

<div class="controls">
    <div class="control-group">
        <label for="searchText">Search NIIN / LRC</label>
        <input
            type="text"
            id="searchText"
            placeholder="Type NIIN or LRC"
            value="<?= htmlspecialchars($preselectedNiin) ?>"
        >
    </div>

    <div class="control-group">
        <label for="lrcFilter">LRC</label>
        <select id="lrcFilter">
            <option value="">All</option>
            <?php foreach ($lrcOptions as $lrc): ?>
                <option value="<?= htmlspecialchars($lrc) ?>"><?= htmlspecialchars($lrc) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="control-group">
        <label for="min12mActions">Min 12M Repair Actions</label>
        <input type="number" id="min12mActions" min="0" value="0">
    </div>

    <div class="control-group">
        <label for="minLifetimeActions">Min Lifetime Repair Actions</label>
        <input type="number" id="minLifetimeActions" min="0" value="0">
    </div>

    <div class="control-group">
        <button type="button" id="resetFilters">Reset Filters</button>
    </div>

    <div class="control-group">
        <button type="button" id="exportCsv">Export Visible Rows</button>
    </div>
</div>

<div class="results-count">
    Showing <span id="visibleCount">0</span> rows
</div>

<div class="table-wrap">
<table id="survivalTable">
    <thead>
        <tr>
            <th class="sortable">NIIN</th>
            <th class="sortable">LRC</th>
            <th class="sortable">Std Price</th>
            <th class="sortable">Last Ship Date</th>
            <th class="sortable">12M Repair Actions</th>
            <th class="sortable">12M Survival %</th>
            <th class="sortable">Lifetime Repair Actions</th>
            <th class="sortable">Lifetime Survival %</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $r): ?>
            <?php
                $cls12 = '';
                if ($r['survival_12m'] !== null) {
                    if ($r['survival_12m'] < 0.50) {
                        $cls12 = 'bad';
                    } elseif ($r['survival_12m'] < 0.75) {
                        $cls12 = 'warn';
                    } else {
                        $cls12 = 'good';
                    }
                }

                $clsAll = '';
                if ($r['survival_all'] !== null) {
                    if ($r['survival_all'] < 0.50) {
                        $clsAll = 'bad';
                    } elseif ($r['survival_all'] < 0.75) {
                        $clsAll = 'warn';
                    } else {
                        $clsAll = 'good';
                    }
                }
            ?>
            <tr
                data-niin="<?= htmlspecialchars($r['niin']) ?>"
                data-lrc="<?= htmlspecialchars($r['lrc']) ?>"
                data-actions12="<?= (int)$r['repair_actions_12m'] ?>"
                data-actionsall="<?= (int)$r['repair_actions_all'] ?>"
            >
                <td><?= htmlspecialchars($r['niin']) ?></td>
                <td><?= htmlspecialchars($r['lrc']) ?></td>
                <td class="num"><?= number_format((float)$r['std_price'], 2) ?></td>
                <td class="center"><?= htmlspecialchars($r['last_ship_date']) ?></td>
                <td class="num"><?= (int)$r['repair_actions_12m'] ?></td>
                <td class="num <?= $cls12 ?>">
                    <?= $r['survival_12m'] !== null ? number_format($r['survival_12m'] * 100, 1) . '%' : 'N/A' ?>
                </td>
                <td class="num"><?= (int)$r['repair_actions_all'] ?></td>
                <td class="num <?= $clsAll ?>">
                    <?= $r['survival_all'] !== null ? number_format($r['survival_all'] * 100, 1) . '%' : 'N/A' ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<script>
(function () {
    const searchText = document.getElementById('searchText');
    const lrcFilter = document.getElementById('lrcFilter');
    const min12mActions = document.getElementById('min12mActions');
    const minLifetimeActions = document.getElementById('minLifetimeActions');
    const resetFilters = document.getElementById('resetFilters');
    const exportCsv = document.getElementById('exportCsv');
    const visibleCount = document.getElementById('visibleCount');
    const table = document.getElementById('survivalTable');
    const rows = Array.from(table.querySelectorAll('tbody tr'));

    function applyFilters() {
        const search = searchText.value.trim().toLowerCase();
        const lrc = lrcFilter.value.trim().toLowerCase();
        const min12 = parseInt(min12mActions.value || '0', 10);
        const minAll = parseInt(minLifetimeActions.value || '0', 10);

        let shown = 0;

        rows.forEach(row => {
            const niin = (row.dataset.niin || '').toLowerCase();
            const rowLrc = (row.dataset.lrc || '').toLowerCase();
            const actions12 = parseInt(row.dataset.actions12 || '0', 10);
            const actionsAll = parseInt(row.dataset.actionsall || '0', 10);

            let matches = true;

            if (search && !(niin.includes(search) || rowLrc.includes(search))) {
                matches = false;
            }

            if (lrc && rowLrc !== lrc) {
                matches = false;
            }

            if (actions12 < min12) {
                matches = false;
            }

            if (actionsAll < minAll) {
                matches = false;
            }

            row.classList.toggle('hidden-row', !matches);

            if (matches) {
                shown++;
            }
        });

        visibleCount.textContent = shown;
    }

    function escapeCsv(value) {
        const str = String(value ?? '');
        if (str.includes('"') || str.includes(',') || str.includes('\n')) {
            return '"' + str.replace(/"/g, '""') + '"';
        }
        return str;
    }

    function exportVisibleRowsToCsv() {
        const visibleRows = rows.filter(row => !row.classList.contains('hidden-row'));
        const headerCells = Array.from(table.querySelectorAll('thead th'));
        const headers = headerCells.map(th => th.textContent.trim());

        const csvLines = [];
        csvLines.push(headers.map(escapeCsv).join(','));

        visibleRows.forEach(row => {
            const cells = Array.from(row.querySelectorAll('td'));
            const values = cells.map(td => td.textContent.trim());
            csvLines.push(values.map(escapeCsv).join(','));
        });

        const csvContent = csvLines.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = 'survival_report_filtered.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    searchText.addEventListener('input', applyFilters);
    lrcFilter.addEventListener('change', applyFilters);
    min12mActions.addEventListener('input', applyFilters);
    minLifetimeActions.addEventListener('input', applyFilters);

    resetFilters.addEventListener('click', function () {
        searchText.value = '';
        lrcFilter.value = '';
        min12mActions.value = '0';
        minLifetimeActions.value = '0';
        applyFilters();
    });

    exportCsv.addEventListener('click', exportVisibleRowsToCsv);

    applyFilters();
})();

// COLUMN SORTING
(function () {
    const table = document.getElementById('survivalTable');
    const headers = table.querySelectorAll('thead th.sortable');
    const tbody = table.querySelector('tbody');
    const directions = {};

    headers.forEach((header, index) => {
        directions[index] = 1;
        header.style.cursor = 'pointer';

        header.addEventListener('click', () => {
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const dir = directions[index];

            headers.forEach(h => {
                h.textContent = h.textContent.replace(/[▲▼]/g, '').trim();
            });

            rows.sort((a, b) => {
                let A = a.children[index].textContent.trim();
                let B = b.children[index].textContent.trim();

                // Handle N/A
                if (A === 'N/A') A = '';
                if (B === 'N/A') B = '';

                // Remove percent signs and commas
                const cleanA = A.replace('%', '').replace(/,/g, '');
                const cleanB = B.replace('%', '').replace(/,/g, '');

                const numA = parseFloat(cleanA);
                const numB = parseFloat(cleanB);

                if (!isNaN(numA) && !isNaN(numB)) {
                    return (numA - numB) * dir;
                }

                const dateA = Date.parse(A);
                const dateB = Date.parse(B);

                if (!isNaN(dateA) && !isNaN(dateB)) {
                    return (dateA - dateB) * dir;
                }

                return A.localeCompare(B) * dir;
            });

            rows.forEach(row => tbody.appendChild(row));

            header.textContent = header.textContent.replace(/[▲▼]/g, '').trim() + (dir === 1 ? ' ▲' : ' ▼');
            directions[index] *= -1;
        });
    });
})();
</script>

</body>
</html>