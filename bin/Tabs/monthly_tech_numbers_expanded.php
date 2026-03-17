<?php
require_once APP_ROOT . '/bin/Model/Repairs.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

$repairsModel = new Repairs();
$fyRange = helpers::getFiscalYearDateRange();

$data = $repairsModel->getTechsRepairValueExpanded(
    $fyRange['start_date'],
    $fyRange['end_date']
    );
?>

<h2><?= htmlspecialchars($fyRange['label']) ?> Detailed Tech Breakdown</h2>

<?php if (empty($data)): ?>
    <p>No data found.</p>
<?php else: ?>

<table>
    <thead>
    <tr>
        <th>Tech Name</th>
        <th>NIIN</th>
        <th>Condition</th>
        <th>QTY</th>
        <th>Total Value</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($data as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['Tech Name']) ?></td>
            <td><?= htmlspecialchars($row['NIIN']) ?></td>
            <td><?= htmlspecialchars($row['Condition']) ?></td>
            <td><?= number_format((int)$row['QTY']) ?></td>
            <td>$<?= number_format((float)$row['Total Value'], 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php endif; ?>