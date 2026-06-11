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
    
    $pages = array_chunk($records, 4);
    
    ob_start();
    ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@page {
    margin: 0.15in;
}

body {
    margin: 0;
    padding: 0;
    font-family: Arial, Helvetica, sans-serif;
    color: #000;
}

.page {
    width: 8.2in;
    height: 10.7in;
    page-break-after: always;
}

.cert {
    width: 3.95in;
    height: 5.15in;
    float: left;
    margin: 0.03in;
    border: 1px dashed #aaa;
    padding: 0.04in;
    box-sizing: border-box;
    font-size: 8px;
    overflow: hidden;
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
    width: 1.05in;
}

.barcode-line {
    width: 1.05in;
}

.model-line {
    width: 2.85in;
}

.long-line {
    width: 2.35in;
}

.date-line {
    width: 1.25in;
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
    width: 1.05in;
}

.rank-line {
    width: 0.75in;
}

.signature-row {
    margin-top: 10px;
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
    margin-top: 8px;
    font-size: 6.5px;
}
</style>
</head>
<body>

<?php if (empty($records)): ?>
    <p>No non-shredded records found for the selected filters.</p>
<?php else: ?>

<?php foreach ($pages as $pageRecords): ?>
<div class="page">
    <?php foreach ($pageRecords as $row): ?>
        <div class="cert">
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
                <span class="line barcode-line"></span>
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
                <span class="line long-line"></span>
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
    <?php endforeach; ?>

    <?php for ($i = count($pageRecords); $i < 4; $i++): ?>
        <div class="cert"></div>
    <?php endfor; ?>

    <div style="clear: both;"></div>
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