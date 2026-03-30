<?php
require_once __DIR__ . "/../../bootstrap.php";
require_once APP_ROOT . "/bin/Model/Batteries.php";

$batt = new Batteries();
$data = $batt->getBatteryTracker();

?>

<div class="tab-pane fade show active" id="battery_tracker">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Battery Tracker (Last 5 Quarters)</h4>
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