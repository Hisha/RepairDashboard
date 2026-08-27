<?php

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once APP_ROOT . '/bin/Model/NoShipmentInventory.php';

$model = new NoShipmentInventory();
$rows = $model->getReport();
?>

<style>
.no-ship-report {
    width: 100%;
}

.no-ship-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.no-ship-header h2 {
    margin: 0;
}

.no-ship-subtext {
    margin: 5px 0 15px;
    color: #555;
    font-size: 14px;
}

.no-ship-controls {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 12px;
}

.no-ship-controls input,
.no-ship-controls select,
.no-ship-controls button {
    padding: 6px 8px;
    font-size: 14px;
}

.no-ship-table-wrap {
    overflow-x: auto;
}

#noShipmentInventoryTable {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    background: #fff;
}

#noShipmentInventoryTable th,
#noShipmentInventoryTable td {
    border: 1px solid #ccc;
    padding: 6px;
}

#noShipmentInventoryTable th {
    background: #f2f2f2;
    cursor: pointer;
    white-space: nowrap;
}

#noShipmentInventoryTable td.num {
    text-align: right;
}

#noShipmentInventoryTable td.center {
    text-align: center;
}

.no-ship-hidden {
    display: none;
}

.no-ship-count {
    margin-bottom: 8px;
    color: #555;
    font-size: 13px;
}

#printNoShipmentInventory {
    cursor: pointer;
}

.export-button {
    display: inline-block;
    padding: 6px 10px;
    font-size: 14px;
    text-decoration: none;
    color: #fff;
    background: #198754;
    border: 1px solid #198754;
    border-radius: 4px;
    cursor: pointer;
}

.export-button:hover {
    background: #157347;
}
</style>

<div class="no-ship-report">

    <div class="no-ship-header">
        <div>
            <h2>No Shipment Inventory</h2>
        </div>
    </div>

    <div class="no-ship-subtext">
        North inventory with on-hand quantity that has not shipped
        within the last five quarters, or has no shipment history.
    </div>

    <div class="no-ship-controls">

        <input
            type="text"
            id="noShipSearch"
            placeholder="Search NIIN, Part, Nomen or Program"
        >

        <select id="noShipProgramFilter">
            <option value="">All Programs</option>

            <?php
            $programs = [];

            foreach ($rows as $row) {
                if (!empty($row['Program'])) {
                    $programs[$row['Program']] = true;
                }
            }

            $programs = array_keys($programs);
            sort($programs);

            foreach ($programs as $program):
            ?>
                <option value="<?= htmlspecialchars($program) ?>">
                    <?= htmlspecialchars($program) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button
            type="button"
            id="resetNoShipFilters"
        >
            Reset
        </button>
                
        <button
            type="button"
            id="printNoShipmentInventory"
        >
            Print Report
        </button>
        
        <a href="monthly_reqs.php?tab=no_shipment_inventory&fy=<?= urlencode((string)$fyRange['fiscal_year']) ?>&export=xlsx"
            class="export-button"
        >Export Excel</a>
        
    </div>

    <div class="no-ship-count">
        Showing
        <span id="noShipVisibleCount"><?= count($rows) ?></span>
        rows
    </div>

    <div class="no-ship-table-wrap">

        <table id="noShipmentInventoryTable">
            <thead>
                <tr>
                    <th>NIIN</th>
                    <th>Nomen</th>
                    <th>Part</th>
                    <th>Program</th>
                    <th>A OnHand</th>
                    <th>D OnHand</th>
                    <th>F OnHand</th>
                    <th>G OnHand</th>
                    <th>K OnHand</th>
                    <th>Last Ship Date</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr
                        data-niin="<?= htmlspecialchars($row['NIIN'] ?? '') ?>"
                        data-nomen="<?= htmlspecialchars($row['Nomen'] ?? '') ?>"
                        data-part="<?= htmlspecialchars($row['Part'] ?? '') ?>"
                        data-program="<?= htmlspecialchars($row['Program'] ?? '') ?>"
                    >
                        <td>
                            <?= htmlspecialchars($row['NIIN'] ?? '') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['Nomen'] ?? '') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['Part'] ?? '') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['Program'] ?? '') ?>
                        </td>

                        <td class="num">
                            <?= (int)($row['A OnHand'] ?? 0) ?>
                        </td>

                        <td class="num">
                            <?= (int)($row['D OnHand'] ?? 0) ?>
                        </td>

                        <td class="num">
                            <?= (int)($row['F OnHand'] ?? 0) ?>
                        </td>

                        <td class="num">
                            <?= (int)($row['G OnHand'] ?? 0) ?>
                        </td>

                        <td class="num">
                            <?= (int)($row['K OnHand'] ?? 0) ?>
                        </td>

                        <td class="center">
                            <?= !empty($row['LastShipDate'])
                                ? htmlspecialchars($row['LastShipDate'])
                                : 'Never'
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>

<script>
(() => {

    const table =
        document.getElementById('noShipmentInventoryTable');

    if (!table) {
        return;
    }

    const tbody = table.querySelector('tbody');
    const rows = Array.from(
        tbody.querySelectorAll('tr')
    );

    const search =
        document.getElementById('noShipSearch');

    const programFilter =
        document.getElementById('noShipProgramFilter');

    const resetButton =
        document.getElementById('resetNoShipFilters');

    const visibleCount =
        document.getElementById('noShipVisibleCount');

    const printButton =
        document.getElementById('printNoShipmentInventory');


    function applyFilters() {

        const searchValue =
            search.value.trim().toLowerCase();

        const programValue =
            programFilter.value.trim().toLowerCase();

        let shown = 0;

        rows.forEach(row => {

            const niin =
                (row.dataset.niin || '').toLowerCase();

            const nomen =
                (row.dataset.nomen || '').toLowerCase();

            const part =
                (row.dataset.part || '').toLowerCase();

            const program =
                (row.dataset.program || '').toLowerCase();

            let matches = true;

            if (
                searchValue &&
                !niin.includes(searchValue) &&
                !nomen.includes(searchValue) &&
                !part.includes(searchValue) &&
                !program.includes(searchValue)
            ) {
                matches = false;
            }

            if (
                programValue &&
                program !== programValue
            ) {
                matches = false;
            }

            row.classList.toggle(
                'no-ship-hidden',
                !matches
            );

            if (matches) {
                shown++;
            }
        });

        visibleCount.textContent = shown;
    }


    search.addEventListener(
        'input',
        applyFilters
    );

    programFilter.addEventListener(
        'change',
        applyFilters
    );

    resetButton.addEventListener(
        'click',
        () => {
            search.value = '';
            programFilter.value = '';
            applyFilters();
        }
    );


    /*
     * Column sorting
     */
    const headers =
        table.querySelectorAll('thead th');

    const directions = {};

    headers.forEach((header, index) => {

        directions[index] = 1;

        header.addEventListener('click', () => {

            const dir = directions[index];

            const currentRows =
                Array.from(
                    tbody.querySelectorAll('tr')
                );

            currentRows.sort((a, b) => {

                let valueA =
                    a.children[index]
                        .textContent
                        .trim();

                let valueB =
                    b.children[index]
                        .textContent
                        .trim();

                const cleanA =
                    valueA.replace(/,/g, '');

                const cleanB =
                    valueB.replace(/,/g, '');

                const numA = Number(cleanA);
                const numB = Number(cleanB);

                if (
                    cleanA !== '' &&
                    cleanB !== '' &&
                    !Number.isNaN(numA) &&
                    !Number.isNaN(numB)
                ) {
                    return (
                        numA - numB
                    ) * dir;
                }

                const dateA =
                    Date.parse(valueA);

                const dateB =
                    Date.parse(valueB);

                if (
                    valueA !== 'Never' &&
                    valueB !== 'Never' &&
                    !Number.isNaN(dateA) &&
                    !Number.isNaN(dateB)
                ) {
                    return (
                        dateA - dateB
                    ) * dir;
                }

                return valueA.localeCompare(
                    valueB
                ) * dir;
            });

            currentRows.forEach(row => {
                tbody.appendChild(row);
            });

            headers.forEach(h => {
                h.textContent =
                    h.textContent
                        .replace(/[▲▼]/g, '')
                        .trim();
            });

            header.textContent +=
                dir === 1 ? ' ▲' : ' ▼';

            directions[index] *= -1;
        });
    });


    /*
     * Print only currently visible rows.
     */
    printButton.addEventListener(
        'click',
        () => {

            const visibleRows =
                Array.from(
                    tbody.querySelectorAll('tr')
                ).filter(row =>
                    !row.classList.contains(
                        'no-ship-hidden'
                    )
                );

            if (visibleRows.length === 0) {
                alert(
                    'There are no visible rows to print.'
                );
                return;
            }

            const printWindow = window.open(
                '',
                'NoShipmentInventoryPrint',
                'width=1200,height=800'
            );

            if (!printWindow) {
                alert(
                    'The print window was blocked.'
                );
                return;
            }

            const headerHtml =
                table.querySelector('thead').outerHTML;

            const bodyHtml =
                visibleRows
                    .map(row => row.outerHTML)
                    .join('');

            printWindow.document.open();

            printWindow.document.write(`
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>No Shipment Inventory</title>

    <style>
        @page {
            size: landscape;
            margin: 0.35in;
        }

        body {
            font-family:
                Arial,
                Helvetica,
                sans-serif;

            margin: 0;
            color: #000;
        }

        h2 {
            margin: 0 0 4px 0;
        }

        .subtitle {
            margin-bottom: 14px;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        th,
        td {
            border: 1px solid #555;
            padding: 4px;
        }

        th {
            background: #eee;
        }

        td:nth-child(n+5):nth-child(-n+9) {
            text-align: right;
        }

        td:last-child {
            text-align: center;
        }

        thead {
            display: table-header-group;
        }

        tr {
            break-inside: avoid;
        }
    </style>
</head>

<body>

    <h2>No Shipment Inventory</h2>

    <div class="subtitle">
        North inventory with on-hand quantity that
        has not shipped within the last five quarters,
        or has no shipment history.
    </div>

    <table>
        ${headerHtml}
        <tbody>
            ${bodyHtml}
        </tbody>
    </table>

    <script>
        window.addEventListener('load', () => {
            window.focus();
            window.print();
        });

        window.addEventListener('afterprint', () => {
            window.close();
        });
    <\/script>

</body>
</html>
            `);

            printWindow.document.close();
        }
    );

})();
</script>