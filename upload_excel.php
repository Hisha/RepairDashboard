<?php

require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/bin/Utilities/excelupload.php';

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $result = excelupload::processUpload($_FILES['excel_file']);
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Excel File</title>
    <style>
        body { font-family: Arial; margin:40px; }
        .success { color:green; }
        .error { color:red; }
    </style>
</head>
<header>
        <?php include(APP_ROOT . '/menu.php'); ?>
</header>
<body>

<h2>Upload Excel Spreadsheet</h2>

<?php if ($result): ?>

    <div class="<?= $result['success'] ? 'success' : 'error' ?>">
        <?= htmlspecialchars($result['message']) ?>
    </div>

<?php endif; ?>

<form method="post" enctype="multipart/form-data">

    <label>Select Excel File:</label><br><br>

    <input 
        type="file" 
        name="excel_file" 
        accept=".xls,.xlsx"
        required
    >

    <br><br>

    <button type="submit">Upload</button>

</form>

</body>
</html>