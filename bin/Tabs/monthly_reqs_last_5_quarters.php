<?php
require_once APP_ROOT . '/bin/Model/Shipments.php';

$shipmentsModel = new Shipments();
$selectedNiin = $_GET['niin'] ?? '';

$data = $shipmentsModel->getLast5QuartersPriorityReport();

$programOptions = [];
foreach ($data as $row) {
    $program = trim((string)($row['Program'] ?? ''));
    if ($program !== '') {
        $programOptions[$program] = true;
    }
}
$programOptions = array_keys($programOptions);
sort($programOptions);

$initialSearch = $_GET['l5q_search'] ?? $selectedNiin;
$initialProgram = $_GET['l5q_program'] ?? '';
$initialStatus = $_GET['l5q_status'] ?? '';
$initialMinDemand = $_GET['l5q_min_demand'] ?? '0';
?>

<style>
.last5q-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 70vh;
    border: 1px solid #ddd;
    background: #fff;
}

.last5q-table {
    width: 100%;
    min-width: 1200px;
    border-collapse: collapse;
}

.last5q-table th,
.last5q-table td {
    padding: 8px 10px;
    border: 1px solid #ddd;
    text-align: left;
    white-space: nowrap;
}

.last5q-table thead th {
    position: sticky;
    top: 0;
    background: #f1f3f5;
    z-index: 3;
}

.last5q-table tbody tr:nth-child(even) {
    background: #fafafa;
}

.number-cell {
    text-align: right;
}

.status-red td {
    background: #f8d7da !important;
}

.status-yellow td {
    background: #fff3cd !important;
}

.status-green td {
    background: #d1e7dd !important;
}

.status-purple td {
    background: #e2d9f3 !important;
}

.legend-item {
    display: inline-block;
    padding: 3px 8px;
    margin: 0 4px;
    border-radius: 4px;
    font-weight: bold;
}

.legend-red { background: #f8d7da; }
.legend-yellow { background: #fff3cd; }
.legend-green { background: #d1e7dd; }
.legend-purple { background: #e2d9f3; }

.priority-legend {
    margin: 10px 0 15px 0;
    font-size: 14px;
    padding: 8px 10px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.highlight-niin td {
    outline: 3px solid #0d6efd;
    outline-offset: -3px;
    font-weight: bold;
}

.filter-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin: 10px 0 15px 0;
    align-items: end;
    padding: 10px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.filter-group label {
    font-size: 13px;
    font-weight: bold;
}

.filter-group input,
.filter-group select,
.filter-group button {
    padding: 6px 8px;
    font-size: 14px;
}

.filter-group button {
    cursor: pointer;
}

.results-count {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #444;
}

.hidden-row {
    display: none;
}
</style>

<h2>Last 5 Quarters NIIN Priority</h2>

<p class="priority-legend">
    <strong>Legend:</strong>
    <span class="legend-item legend-red">Red</span> = A OnHand below Quarterly Demand |
    <span class="legend-item legend-yellow">Yellow</span> = A OnHand equals Quarterly Demand |
    <span class="legend-item legend-green">Green</span> = A OnHand above Quarterly Demand |
    <span class="legend-item legend-purple">Purple</span> = A OnHand + D OnHand + G OnHand covers Quarterly Demand
</p>

<?php if (empty($data)): ?>
    <p>No data found.</p>
<?php else: ?>

<div class="filter-toolbar">
    <div class="filter-group">
        <label for="last5qSearch">Search NIIN / Program</label>
        <input
            type="text"
            id="last5qSearch"
            placeholder="Type NIIN or Program"
            value="<?= htmlspecialchars($initialSearch) ?>"
        >
    </div>

    <div class="filter-group">
        <label for="last5qProgram">Program</label>
        <select id="last5qProgram">
            <option value="">All</option>
            <?php foreach ($programOptions as $program): ?>
                <option value="<?= htmlspecialchars($program) ?>" <?= $program === $initialProgram ? 'selected' : '' ?>>
                    <?= htmlspecialchars($program) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-group">
        <label for="last5qStatus">Status Color</label>
        <select id="last5qStatus">
            <option value="">All</option>
            <option value="status-red" <?= $initialStatus === 'status-red' ? 'selected' : '' ?>>Red</option>
            <option value="status-yellow" <?= $initialStatus === 'status-yellow' ? 'selected' : '' ?>>Yellow</option>
            <option value="status-green" <?= $initialStatus === 'status-green' ? 'selected' : '' ?>>Green</option>
            <option value="status-purple" <?= $initialStatus === 'status-purple' ? 'selected' : '' ?>>Purple</option>
        </select>
    </div>

    <div class="filter-group">
        <label for="last5qMinDemand">Min Quarterly Demand</label>
        <input type="number" id="last5qMinDemand" min="0" step="0.01" value="<?= htmlspecialchars((string)$initialMinDemand) ?>">
    </div>

    <div class="filter-group">
        <button type="button" id="last5qReset">Reset Filters</button>
    </div>
</div>

<div class="results-count">
    Showing <span id="last5qVisibleCount">0</span> rows
</div>

<div class="last5q-table-wrap">
    <table class="last5q-table" id="last5qTable">
        <thead>
        <tr>
            <th>NIIN</th>
            <th>Quarterly Demand</th>
            <th>A OnHand</th>
            <th>D OnHand</th>
            <th>G OnHand</th>
            <th>F OnHand</th>
            <th>F Awaiting Vendor</th>
            <th>Last Ship Date</th>
            <th>Program</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $row): ?>
            <?php
            $aOnHand = (float)($row['A OnHand'] ?? 0);
            $dOnHand = (float)($row['D OnHand'] ?? 0);
            $gOnHand = (float)($row['G OnHand'] ?? 0);
            $quarterlyDemand = (float)($row['Quarterly Demand'] ?? 0);

            if ($aOnHand > $quarterlyDemand) {
                $rowClass = 'status-green';
            } elseif ($aOnHand == $quarterlyDemand) {
                $rowClass = 'status-yellow';
            } elseif (($aOnHand + $dOnHand + $gOnHand) > $quarterlyDemand) {
                $rowClass = 'status-purple';
            } else {
                $rowClass = 'status-red';
            }

            $isHighlighted = ($selectedNiin !== '' && (string)$row['NIIN'] === (string)$selectedNiin);
            $combinedRowClass = $rowClass . ($isHighlighted ? ' highlight-niin' : '');
            ?>
            <tr
                class="<?= htmlspecialchars($combinedRowClass) ?>"
                <?= $isHighlighted ? ' id="selected-last5q-row"' : '' ?>
                data-niin="<?= htmlspecialchars((string)$row['NIIN']) ?>"
                data-program="<?= htmlspecialchars((string)$row['Program']) ?>"
                data-status="<?= htmlspecialchars($rowClass) ?>"
                data-quarterly-demand="<?= htmlspecialchars((string)$quarterlyDemand) ?>"
            >
                <td><?= htmlspecialchars($row['NIIN']) ?></td>
                <td class="number-cell"><?= number_format($quarterlyDemand, 2) ?></td>
                <td class="number-cell"><?= number_format($aOnHand, 0) ?></td>
                <td class="number-cell"><?= number_format($dOnHand, 0) ?></td>
                <td class="number-cell"><?= number_format($gOnHand, 0) ?></td>
                <td class="number-cell"><?= number_format((float)$row['F OnHand'], 0) ?></td>
                <td class="number-cell"><?= number_format((float)$row['F Awaiting Vendor'], 0) ?></td>
                <td><?= htmlspecialchars($row['LastShipDate']) ?></td>
                <td><?= htmlspecialchars($row['Program']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($data)): ?>
<script>
(() => {
    const searchInput = document.getElementById('last5qSearch');
    const programFilter = document.getElementById('last5qProgram');
    const statusFilter = document.getElementById('last5qStatus');
    const minDemandFilter = document.getElementById('last5qMinDemand');
    const resetButton = document.getElementById('last5qReset');
    const visibleCount = document.getElementById('last5qVisibleCount');
    const rows = Array.from(document.querySelectorAll('#last5qTable tbody tr'));

    function updateUrlAndExportLink() {
        const url = new URL(window.location.href);

        url.searchParams.set('tab', 'last_5_quarters');
        url.searchParams.set('fy', '<?= htmlspecialchars((string)$fyRange['fiscal_year']) ?>');
        url.searchParams.set('l5q_search', searchInput.value.trim());
        url.searchParams.set('l5q_program', programFilter.value.trim());
        url.searchParams.set('l5q_status', statusFilter.value.trim());
        url.searchParams.set('l5q_min_demand', minDemandFilter.value || '0');

        history.replaceState({}, '', url.toString());

        const exportLink = document.querySelector('.page-controls .export-link');
        if (exportLink) {
            const exportUrl = new URL(url.toString());
            exportUrl.searchParams.set('export', 'xlsx');
            exportLink.href = exportUrl.toString();
        }
    }

    function applyFilters() {
        const search = searchInput.value.trim().toLowerCase();
        const program = programFilter.value.trim().toLowerCase();
        const status = statusFilter.value.trim();
        const minDemand = parseFloat(minDemandFilter.value || '0');

        let shown = 0;

        rows.forEach(row => {
            const niin = (row.dataset.niin || '').toLowerCase();
            const rowProgram = (row.dataset.program || '').toLowerCase();
            const rowStatus = row.dataset.status || '';
            const quarterlyDemand = parseFloat(row.dataset.quarterlyDemand || '0');

            let matches = true;

            if (search && !(niin.includes(search) || rowProgram.includes(search))) {
                matches = false;
            }

            if (program && rowProgram !== program) {
                matches = false;
            }

            if (status && rowStatus !== status) {
                matches = false;
            }

            if (quarterlyDemand < minDemand) {
                matches = false;
            }

            row.classList.toggle('hidden-row', !matches);

            if (matches) {
                shown++;
            }
        });

        visibleCount.textContent = shown;
        updateUrlAndExportLink();
    }

    resetButton.addEventListener('click', () => {
        searchInput.value = '';
        programFilter.value = '';
        statusFilter.value = '';
        minDemandFilter.value = '0';
        applyFilters();
    });

    searchInput.addEventListener('input', applyFilters);
    programFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    minDemandFilter.addEventListener('input', applyFilters);

    applyFilters();

    const selectedRow = document.getElementById('selected-last5q-row');
    if (selectedRow && !selectedRow.classList.contains('hidden-row')) {
        selectedRow.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
            inline: 'nearest'
        });
    }
})();
</script>
<?php endif; ?>