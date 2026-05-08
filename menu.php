<?php
require_once APP_ROOT . '/bin/Model/SYS_LastUpdate.php';
require_once APP_ROOT . '/bin/Model/LMS21Data.php';
require_once APP_ROOT . '/bin/Utilities/helpers.php';

$lastUpdateModel = new SYS_LastUpdate();
$lms21Model = new LMS21Data();

$missingNiinCount = $lms21Model->getMissingNiinsCount();
$lastCavsUpdate = $lastUpdateModel->getLastUpdate('CAVS');
$lastCMProUpdate = $lastUpdateModel->getLastUpdate('CMPro');
$lastProcurementUpdate = $lastUpdateModel->getLastUpdate('Procurement');

$lastCavsUpdateFormatted = $lastCavsUpdate
    ? date('M d, Y g:i A', strtotime($lastCavsUpdate))
    : 'Never';

$lastCMProUpdateFormatted = $lastCMProUpdate
    ? date('M d, Y g:i A', strtotime($lastCMProUpdate))
    : 'Never';

$lastProcurementUpdateFormatted = $lastProcurementUpdate
    ? date('M d, Y g:i A', strtotime($lastProcurementUpdate))
    : 'Never';
?>

<style>
.menu-bar {
    background-color: #808080;
    padding: 10px 14px;
    display: grid;
    grid-template-columns: 1fr auto auto;
    align-items: center;
    gap: 18px;
}

.menu-links {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 18px;
}

.menu-links a {
    color: black;
    text-decoration: none;
    font-weight: bold;
    white-space: nowrap;
}

.menu-links a:hover {
    text-decoration: underline;
}

.location-filter {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 8px;
    background: #f1f3f5;
    border: 1px solid #666;
    border-radius: 6px;
    white-space: nowrap;
}

.location-filter label {
    font-size: 13px;
    font-weight: bold;
    margin: 0;
}

.location-filter select {
    min-width: 120px;
    padding: 4px 6px;
    border: 1px solid #555;
    border-radius: 4px;
    background: #fff;
    font-size: 13px;
}

.menu-updates {
    text-align: right;
    font-size: 12px;
    font-weight: bold;
    color: black;
    line-height: 1.35;
    white-space: nowrap;
}
</style>

<div class="menu-bar">

  <div class="menu-links">
    <a href="index.php">Home</a>
    <a href="backorders.php">Back Orders</a>
    <a href="drive_destruction.php">Drive Destruction</a>
    <a href="inventory.php">Inventory</a>
    <a href="procurements.php">Procurements</a>
    <a href="monthly_tech.php">Repairs</a>
    <a href="monthly_reqs.php">Shipments</a>
    <a href="cog7_repairables.php">Survival Rates</a>
    <a href="upload_excel.php">Upload Link</a>
    <?php if ($missingNiinCount > 0): ?>
        <a href="lms21_missing.php">COG/Price Data Insert</a>
    <?php endif; ?>
  </div>
  
  <?php
    $currentNorthSouthFilter = helpers::getNorthSouthFilter();
    ?>
    
    <form method="post" action="set_location_filter.php" class="location-filter">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
    
        <label for="north_south_filter">Location</label>
    
        <select name="north_south_filter" id="north_south_filter" onchange="this.form.submit()">
            <option value="all" <?= $currentNorthSouthFilter === 'all' ? 'selected' : '' ?>>
                All
            </option>
    
            <option value="north" <?= $currentNorthSouthFilter === 'north' ? 'selected' : '' ?>>
                Chesapeake
            </option>
    
            <option value="south" <?= $currentNorthSouthFilter === 'south' ? 'selected' : '' ?>>
                Charleston
            </option>
        </select>
    </form>

  <div class="menu-updates">
    Last CAVs Update: <?= htmlspecialchars($lastCavsUpdateFormatted ?? 'N/A') ?><br>
    Last CMPro Update: <?= htmlspecialchars($lastCMProUpdateFormatted ?? 'N/A') ?><br>
    Last Procurement Update: <?= htmlspecialchars($lastProcurementUpdateFormatted ?? 'N/A') ?>
  </div>

</div>