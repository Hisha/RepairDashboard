<?php
require_once APP_ROOT . '/bin/Model/DRMO.php';

$drmoModel = new DRMO();

$availableMonths = $drmoModel->getAvailableDrmoMonths();
$selectedDrmoMonth = $_GET['drmo_month'] ?? ($availableMonths[0]['month_value'] ?? '');

$data = $selectedDrmoMonth !== ''
    ? $drmoModel->getDRMOByMonth($selectedDrmoMonth)
    : [];
    ?>

<h3>DRMO Activity</h3>

<form method="get" action="monthly_reqs.php" style="margin-bottom: 15px;">
    <input type="hidden" name="tab" value="drmo">
    <input type="hidden" name="fy" value="<?= htmlspecialchars((string)$fyRange['fiscal_year']) ?>">

    <label for="drmo_month"><strong>Month:</strong></label>
    <select name="drmo_month" id="drmo_month" onchange="this.form.submit()">
        <?php foreach ($availableMonths as $month): ?>
            <option value="<?= htmlspecialchars($month['month_value']) ?>"
                <?= $month['month_value'] === $selectedDrmoMonth ? 'selected' : '' ?>>
                <?= htmlspecialchars($month['month_label']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<p>
    <a href="monthly_reqs.php?tab=drmo&fy=<?= urlencode((string)$fyRange['fiscal_year']) ?>&drmo_month=<?= urlencode($selectedDrmoMonth) ?>&export=csv">
        Export CSV
    </a>
</p>

<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th>Transaction Date</th>
                <th>NIIN</th>
                <th>Part</th>
                <th>Nomen</th>
                <th>Program</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Document Number</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($data)): ?>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Transaction Date']) ?></td>
                        <td><?= htmlspecialchars($row['NIIN']) ?></td>
                        <td><?= htmlspecialchars($row['Part']) ?></td>
                        <td><?= htmlspecialchars($row['Nomen']) ?></td>
                        <td><?= htmlspecialchars($row['Program']) ?></td>
                        <td><?= number_format((float)$row['Qty']) ?></td>
                        <td>$<?= number_format((float)$row['Unit Price'], 2) ?></td>
                        <td><?= htmlspecialchars($row['Document Number']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No data found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>