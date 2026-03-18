<?php
require_once APP_ROOT . '/bin/Model/SYS_LastUpdate.php';

$lastUpdateModel = new SYS_LastUpdate();

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
    <a href="monthly_reqs.php">Shipments</a>
    <a href="monthly_tech.php">Repairs</a>
    <a href="procurements.php">Procurements</a>
    <a href="upload_excel.php">Upload Link</a>
  </div>

  <div class="menu-updates">
    Last CAVs Update: <?= htmlspecialchars($lastCavsUpdateFormatted ?? 'N/A') ?><br>
    Last CMPro Update: <?= htmlspecialchars($lastCMProUpdateFormatted ?? 'N/A') ?><br>
    Last Procurement Update: <?= htmlspecialchars($lastProcurementUpdateFormatted ?? 'N/A') ?>
  </div>

</div>