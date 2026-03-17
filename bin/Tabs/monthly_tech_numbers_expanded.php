<?php
require_once APP_ROOT . '/bin/Model/Repairs.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

$repairsModel = new Repairs();
$selectedFiscalYear = isset($_GET['fy']) ? (int)$_GET['fy'] : null;
$fyRange = helpers::getFiscalYearDateRange($selectedFiscalYear);

$data = $repairsModel->getTechsRepairValueExpanded(
    $fyRange['start_date'],
    $fyRange['end_date']
    );

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=tech_numbers_expanded_' . $fyRange['label'] . '.csv');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['Tech Name', 'NIIN', 'Condition', 'QTY', 'Total Value']);
    
    foreach ($data as $row) {
        fputcsv($output, [
            $row['Tech Name'],
            $row['NIIN'],
            $row['Condition'],
            $row['QTY'],
            $row['Total Value']
        ]);
    }
    
    fclose($output);
    exit;
}
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