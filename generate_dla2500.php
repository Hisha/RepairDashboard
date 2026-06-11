<?php

require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/bin/Model/DriveDestruction.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$model = new DriveDestruction();

$filters = [
    'search'    => trim($_GET['search'] ?? ''),
    'status'    => trim($_GET['status'] ?? ''),
    'method'    => trim($_GET['method'] ?? ''),
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to'   => trim($_GET['date_to'] ?? ''),
    'sort'      => trim($_GET['sort'] ?? 'destruction_date'),
    'dir'       => trim($_GET['dir'] ?? 'DESC'),
];

$records = $model->getRecords($filters);

$records = array_values(array_filter($records, function ($row) {
    $method = strtoupper(trim((string)($row['destruction_method'] ?? '')));
    $status = strtoupper(trim((string)($row['status'] ?? '')));
    
    return $method !== 'SHREDDED'
        && $status !== 'VOIDED';
}));
    
    function h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
    
    function certDate(array $row): string
    {
        if (!empty($row['destruction_date'])) {
            return date('m/d/Y', strtotime($row['destruction_date']));
        }
        
        return date('m/d/Y');
    }
    
    function renderCert(array $row, string $slotClass): void
    {
        ?>
    <div class="cert <?= h($slotClass) ?>">
        <div class="cert-header">
            CERTIFICATION OF<br>
            INFORMATION TECHNOLOGY DISPOSITION
        </div>

        <div class="checkbox-row">
            <span class="box"></span>
            Check box if hard drive/data storage components have been removed.
        </div>

        <div class="certifies">This certifies this hard drive:</div>

        <div class="row">
            <span class="label">Serial No.</span>
            <span class="line serial-line"><?= h($row['serial_number'] ?? '') ?></span>
            <span class="label">Barcode No.</span>
            <span class="line barcode-line">&nbsp;</span>
        </div>

        <div class="row">
            <span class="label">Make/Model</span>
            <span class="line model-line"><?= h($row['part_number'] ?? '') ?></span>
        </div>

        <div class="compliance">
            was Cleared / Purged / Destroyed in accordance with DoD<br>
            I 8500.01, DOD M 4160.21 Vol 4; and NIST SP800-88 Rev 1
        </div>

        <div class="row">
            <strong><em>(Date)</em></strong>
            <span class="line date-line"><?= h(certDate($row)) ?></span>
        </div>

        <table class="method-table">
            <tr>
                <td><strong>Method Type</strong></td>
                <td><span class="box"></span> Clear</td>
                <td><span class="box"></span> Purge</td>
                <td><span class="box">X</span> Destroy</td>
            </tr>
            <tr>
                <td><strong>Method Used</strong></td>
                <td><span class="box">X</span> Degauss</td>
                <td><span class="box"></span> Overwrite</td>
                <td><span class="box"></span> Block Erase</td>
            </tr>
            <tr>
                <td></td>
                <td><span class="box"></span> Crypto Erase</td>
                <td><span class="box"></span> Other</td>
                <td></td>
            </tr>
        </table>

        <div class="row">
            <span class="label">Software / Degausser</span>
            <span class="line long-line">DATA Security INC., NSA Certified</span>
        </div>
        <div class="note">(Manufacturer, Product Version, Date)</div>

        <div class="row">
            <span class="label">Method of Destruction</span>
            <span class="line long-line">DEGAUSSED/PUNCHED</span>
        </div>
        <div class="note">(e.g., approved metal destruction facility)</div>

        <div class="row">
            <span class="label">DTID No. / Hand Receipt No.</span>
            <span class="line long-line">&nbsp;</span>
        </div>

        <div class="row">
            <span class="label">Printed Name</span>
            <span class="line long-line">KEVIN SMITH</span>
        </div>

        <div class="row">
            <span class="label">Organization Unit Name</span>
            <span class="line long-line">MESC Greenbrier Chesapeake, VA</span>
        </div>

        <div class="row">
            <span class="label">Email</span>
            <span class="line long-line">kevin.t.smith26.civ@us.navy.mil</span>
        </div>

        <div class="row">
            <span class="label">Phone</span>
            <span class="line phone-line">757-320-1927</span>
            <span class="label">Rank/Grade</span>
            <span class="line rank-line">CIV</span>
        </div>

        <div class="signature-row">
            <span class="sig-left">Signature</span>
            <span class="sig-right">Date</span>
        </div>

        <div class="footer">
            DLA FORM 2500, NOV 2022 (Replaces all similar forms)
        </div>
    </div>
    <?php
}

$pages = array_chunk($records, 4);

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@page {
    margin: 0.12in;
}

body {
    margin: 0;
    padding: 0;
    font-family: Arial, Helvetica, sans-serif;
    color: #000;
}

.page {
    position: relative;
    width: 8.26in;
    height: 10.76in;
    page-break-after: always;
}

.cert {
    position: absolute;
    width: 4.05in;
    height: 5.25in;
    border: 1px dashed #aaa;
    padding: 0.03in;
    box-sizing: border-box;
    font-size: 8px;
    overflow: hidden;
}

.slot-1 {
    left: 0;
    top: 0;
}

.slot-2 {
    left: 4.13in;
    top: 0;
}

.slot-3 {
    left: 0;
    top: 5.38in;
}

.slot-4 {
    left: 4.13in;
    top: 5.38in;
}

.cert-header {
    background: #c9c9c9;
    border: 1px solid #000;
    text-align: center;
    font-weight: bold;
    font-size: 10px;
    padding: 3px;
    line-height: 1.1;
}

.checkbox-row {
    margin-top: 3px;
}

.box {
    display: inline-block;
    width: 10px;
    height: 10px;
    border: 1px solid #000;
    text-align: center;
    font-weight: bold;
    font-size: 9px;
    line-height: 10px;
    vertical-align: middle;
}

.certifies {
    font-style: italic;
    font-weight: bold;
    margin: 4px 0;
}

.row {
    clear: both;
    margin: 3px 0;
}

.label {
    display: inline-block;
    white-space: nowrap;
}

.line {
    display: inline-block;
    border-bottom: 1px solid #000;
    min-height: 11px;
    padding-left: 3px;
    font-weight: bold;
}

.serial-line {
    width: 0.95in;
}

.barcode-line {
    width: 0.95in;
}

.model-line {
    width: 2.75in;
}

.long-line {
    width: 2.25in;
}

.date-line {
    width: 1.2in;
}

.compliance {
    font-weight: bold;
    font-style: italic;
    margin: 5px 0 2px;
    line-height: 1.15;
}

.method-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 3px;
    margin-bottom: 3px;
}

.method-table td {
    font-size: 8px;
    padding: 1px 2px;
    white-space: nowrap;
}

.note {
    text-align: center;
    font-size: 6.5px;
    font-style: italic;
    margin-top: -2px;
}

.phone-line {
    width: 0.95in;
}

.rank-line {
    width: 0.65in;
}

.signature-row {
    margin-top: 9px;
}

.sig-left {
    display: inline-block;
    width: 1.7in;
}

.sig-right {
    display: inline-block;
    width: 1.0in;
    text-align: center;
}

.footer {
    margin-top: 7px;
    font-size: 6.5px;
}
</style>
</head>
<body>

<?php if (empty($records)): ?>
    <p>No non-shredded records found for the selected filters.</p>
<?php else: ?>

<?php foreach ($pages as $pageRecords): ?>
    <?php
    $slots = $pageRecords;
    while (count($slots) < 4) {
        $slots[] = null;
    }
    ?>

    <div class="page">
        <?php if ($slots[0] !== null) renderCert($slots[0], 'slot-1'); ?>
        <?php if ($slots[1] !== null) renderCert($slots[1], 'slot-2'); ?>
        <?php if ($slots[2] !== null) renderCert($slots[2], 'slot-3'); ?>
        <?php if ($slots[3] !== null) renderCert($slots[3], 'slot-4'); ?>
    </div>
<?php endforeach; ?>

<?php endif; ?>

</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->setPaper('letter', 'portrait');
$dompdf->loadHtml($html);
$dompdf->render();

$dompdf->stream('DLA2500_Certificates.pdf', [
    'Attachment' => false
]);

exit;