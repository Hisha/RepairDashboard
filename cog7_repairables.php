<?php
require_once __DIR__ . "/bootstrap.php";
require_once APP_ROOT . "/vendor/autoload.php";
require_once APP_ROOT . "/bin/Utilities/xlsx_styled_helper.php";
require_once APP_ROOT . "/bin/Model/Cog7Repairables.php";

$model = new Cog7Repairables();
$rows = $model->getReport();

$preselectedNiin = trim($_GET['niin'] ?? '');

if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    $search = trim((string)($_GET['search'] ?? ''));
    $lrc = trim((string)($_GET['lrc'] ?? ''));
    $min12 = (int)($_GET['min12'] ?? 0);
    $minAll = (int)($_GET['minAll'] ?? 0);
    
    $filteredRows = array_filter($rows, function ($row) use ($search, $lrc, $min12, $minAll) {
        $niin = strtolower(trim((string)($row['niin'] ?? '')));
        $rowLrc = strtolower(trim((string)($row['lrc'] ?? '')));
        $actions12 = (int)($row['repair_actions_12m'] ?? 0);
        $actionsAll = (int)($row['repair_actions_all'] ?? 0);
        
        if ($search !== '' && !str_contains($niin, strtolower($search)) && !str_contains($rowLrc, strtolower($search))) {
            return false;
        }
        
        if ($lrc !== '' && $rowLrc !== strtolower($lrc)) {
            return false;
        }
        
        if ($actions12 < $min12) {
            return false;
        }
        
        if ($actionsAll < $minAll) {
            return false;
        }
        
        return true;
    });
        
        $exportRows = [];
        foreach ($filteredRows as $row) {
            $cellStyles = [];
            
            if ($row['survival_12m'] !== null) {
                if ($row['survival_12m'] < 0.50) {
                    $cellStyles['12M Survival %'] = 'bad';
                } elseif ($row['survival_12m'] < 0.75) {
                    $cellStyles['12M Survival %'] = 'warn';
                } else {
                    $cellStyles['12M Survival %'] = 'good';
                }
            }
            
            if ($row['survival_all'] !== null) {
                if ($row['survival_all'] < 0.50) {
                    $cellStyles['Lifetime Survival %'] = 'bad';
                } elseif ($row['survival_all'] < 0.75) {
                    $cellStyles['Lifetime Survival %'] = 'warn';
                } else {
                    $cellStyles['Lifetime Survival %'] = 'good';
                }
            }
            
            $exportRows[] = [
                '_row_type' => 'normal',
                '_cell_styles' => $cellStyles,
                'NIIN' => $row['niin'] ?? '',
                'LRC' => $row['lrc'] ?? '',
                'Std Price' => isset($row['std_price']) ? (float)$row['std_price'] : '',
                'Last Ship Date' => $row['last_ship_date'] ?? '',
                '12M Repair Actions' => (int)($row['repair_actions_12m'] ?? 0),
                '12M Survival %' => $row['survival_12m'] !== null ? round(((float)$row['survival_12m']) * 100, 1) : 'N/A',
                'Lifetime Repair Actions' => (int)($row['repair_actions_all'] ?? 0),
                'Lifetime Survival %' => $row['survival_all'] !== null ? round(((float)$row['survival_all']) * 100, 1) : 'N/A',
            ];
        }
        
        $headers = [
            'NIIN',
            'LRC',
            'Std Price',
            'Last Ship Date',
            '12M Repair Actions',
            '12M Survival %',
            'Lifetime Repair Actions',
            'Lifetime Survival %'
        ];
        
        xlsx_styled_helper::download(
            'survival_report_filtered_' . date('Y-m-d') . '.xlsx',
            $headers,
            $exportRows,
            [
                'sheetTitle' => 'Survival Report',
                'textColumns' => ['NIIN'],
                'numberFormats' => [
                    'Std Price' => '$#,##0.00',
                    '12M Repair Actions' => '0',
                    '12M Survival %' => '0.0',
                    'Lifetime Repair Actions' => '0',
                    'Lifetime Survival %' => '0.0'
                ]
            ]
            );
}

$lrcOptions = [];
foreach ($rows as $row) {
    if (!empty($row['lrc'])) {
        $lrcOptions[$row['lrc']] = true;
    }
}
$lrcOptions = array_keys($lrcOptions);
sort($lrcOptions);

include 'menu.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Survival Report</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <button type="button" id="exportXlsx">Export Visible Rows</button>
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
    const exportXlsx = document.getElementById('exportXlsx');
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

    function exportVisibleRowsToXlsx() {
        const params = new URLSearchParams();
    
        params.set('export', 'xlsx');
        params.set('search', searchText.value.trim());
        params.set('lrc', lrcFilter.value.trim());
        params.set('min12', min12mActions.value || '0');
        params.set('minAll', minLifetimeActions.value || '0');
    
        window.location.href = 'cog7_repairables.php?' + params.toString();
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

    exportXlsx.addEventListener('click', exportVisibleRowsToXlsx);

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