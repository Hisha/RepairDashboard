<?php
require_once APP_ROOT . '/bin/Model/SYS_LastUpdate.php';

$lastUpdateModel = new SYS_LastUpdate();

$lastCavsUpdate = $lastUpdateModel->getLastUpdate('CAVS');
$lastCMProUpdate = $lastUpdateModel->getLastUpdate('CMPRO');

$lastCavsUpdateFormatted = $lastCavsUpdate
    ? date('M d, Y g:i A', strtotime($lastCavsUpdate))
    : 'Never';

$lastCMProUpdateFormatted = $lastCMProUpdate
    ? date('M d, Y g:i A', strtotime($lastCMProUpdate))
    : 'Never';
?>

<style>
.menu-bar {
  background-color: #808080;
  padding: 10px;
}

.menu-bar a {
  margin-right: 30px;
  color: black;
  text-decoration: none;
  font-weight: bold;
}

.menu-status {
  margin-top: 8px;
  font-size: 13px;
  color: black;
  font-weight: bold;
}
</style>

<div class="menu-bar">
    <a href="index.php">Home</a>
    <a href="monthly_reqs.php">Monthly Reqs</a>
    <a href="upload_excel.php">Upload Link</a>

    <div class="menu-status">
        Last CAVs Update: <?= htmlspecialchars($lastCavsUpdateFormatted) ?>
    </div>
    <div class="menu-status">
        Last CMPro Update: <?= htmlspecialchars($lastCMProUpdateFormatted) ?>
    </div>
</div>