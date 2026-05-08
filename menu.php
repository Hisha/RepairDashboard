<?php
require_once APP_ROOT . '/bin/Model/SYS_LastUpdate.php';
require_once APP_ROOT . '/bin/Model/LMS21Data.php';

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
  padding: 10px;
  display: flex;
  align-items: center;
}

.menu-links a {
  margin-right: 30px;
  color: black;
  text-decoration: none;
  font-weight: bold;
}

.menu-updates {
  margin-left: auto;
  text-align: right;
  font-size: 12px;
  font-weight: bold;
  color: black;
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
    $currentNorthSouthFilter = $_SESSION['north_south_filter'] ?? 'all';
    ?>
    
    <form method="post" action="set_location_filter.php" style="display:inline-block; margin-left:12px;">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
    
        <label for="north_south_filter"><strong>Location:</strong></label>
        <select name="north_south_filter" id="north_south_filter" onchange="this.form.submit()">
            <option value="all" <?= $currentNorthSouthFilter === 'all' ? 'selected' : '' ?>>All</option>
            <option value="north" <?= $currentNorthSouthFilter === 'north' ? 'selected' : '' ?>>Chesapeake</option>
            <option value="south" <?= $currentNorthSouthFilter === 'south' ? 'selected' : '' ?>>Charleston</option>
        </select>
    </form>

  <div class="menu-updates">
    Last CAVs Update: <?= htmlspecialchars($lastCavsUpdateFormatted ?? 'N/A') ?><br>
    Last CMPro Update: <?= htmlspecialchars($lastCMProUpdateFormatted ?? 'N/A') ?><br>
    Last Procurement Update: <?= htmlspecialchars($lastProcurementUpdateFormatted ?? 'N/A') ?>
  </div>

</div>