<?php
require_once __DIR__ . "/../../bootstrap.php";
require_once APP_ROOT . "/bin/Model/Batteries.php";

$batt = new Batteries();
$data = $batt->getBatteryTracker();

/*
 * CSV Export (must happen before HTML output)
 */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=battery_tracker_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($data)) {
        // Headers
        fputcsv($output, array_keys($data[0]));
        
        // Rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}
?>

<div class="tab-pane fade show active" id="battery_tracker">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Battery Tracker (Last 5 Quarters)</h4>

        <a href="?tab=battery_tracker&export=csv" class="btn btn-sm btn-success">
            Export CSV
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-striped table-bordered table-sm">
            <thead class="sticky-top bg-dark">
                <tr>
                    <th>Part</th>
                    <th>OnHand Qty</th>
                    <th>Installed Qty (5Q)</th>
                    <th>Shipped Qty (5Q)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data)): ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Part']) ?></td>
                            <td><?= number_format($row['OnHand Qty']) ?></td>
                            <td><?= number_format($row['Installed Qty']) ?></td>
                            <td><?= number_format($row['Shipped Qty']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">No data found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>