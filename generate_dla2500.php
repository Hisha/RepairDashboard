<?php
require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/bin/Model/DriveDestruction.php';

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
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>DLA Form 2500 Certificates</title>

<style>
@page {
    size: letter portrait;
    margin: 0.25in;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    color: #000;
}

.no-print {
    margin: 10px;
}

.page {
    width: 8in;
    height: 10.5in;
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 1fr 1fr;
    gap: 0.12in;
    page-break-after: always;
}

.cert {
    border: 1px dashed #bbb;
    padding: 0.07in;
    font-size: 9px;
    box-sizing: border-box;
    position: relative;
}

.cert-header {
    background: #c9c9c9;
    border: 1px solid #000;
    text-align: center;
    font-weight: bold;
    font-size: 11px;
    padding: 3px;
    line-height: 1.1;
    text-transform: uppercase;
}

.checkbox-row {
    margin-top: 3px;
    display: flex;
    align-items: flex-start;
    gap: 4px;
}

.box {
    display: inline-flex;
    width: 11px;
    height: 11px;
    border: 1px solid #000;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 10px;
    line-height: 1;
}

.certifies {
    font-style: italic;
    font-weight: bold;
    margin: 5px 0 4px;
}

.line-row {
    display: grid;
    grid-template-columns: auto 1fr auto 1fr;
    gap: 4px;
    align-items: end;
    margin: 3px 0;
}

.line-row.two {
    grid-template-columns: auto 1fr;
}

.line {
    border-bottom: 1px solid #000;
    min-height: 12px;
    padding-left: 3px;
    font-weight: bold;
    font-size: 10px;
}

.compliance {
    font-weight: bold;
    font-style: italic;
    margin: 6px 0 2px;
    line-height: 1.15;
}

.method-grid {
    display: grid;
    grid-template-columns: auto 1fr auto 1fr auto 1fr;
    gap: 4px 8px;
    align-items: center;
    margin: 5px 0;
}

.field-note {
    text-align: center;
    font-size: 7px;
    font-style: italic;
    margin-top: -2px;
}

.footer {
    position: absolute;
    left: 0.07in;
    right: 0.07in;
    bottom: 0.05in;
    font-size: 7px;
}

.signature-row {
    display: grid;
    grid-template-columns: 1fr 0.7fr;
    gap: 20px;
    margin-top: 14px;
}

@media print {
    .no-print {
        display: none;
    }

    .page {
        page-break-after: always;
    }
}
</style>
</head>

<body>

<div class="no-print">
    <button onclick="window.print()">Print</button>
    <button onclick="window.close()">Close</button>
    <strong>Records:</strong> <?= number_format(count($records)) ?>
</div>

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
                <span>Check box if hard drive/data storage components have been removed.</span>
            </div>

            <div class="certifies">This certifies this hard drive:</div>

            <div class="line-row">
                <span>Serial No.</span>
                <span class="line"><?= h($row['serial_number'] ?? '') ?></span>
                <span>Barcode No.</span>
                <span class="line"></span>
            </div>

            <div class="line-row two">
                <span>Make/Model</span>
                <span class="line"><?= h($row['part_number'] ?? '') ?></span>
            </div>

            <div class="compliance">
                was Cleared / Purged / Destroyed in accordance with DoD<br>
                I 8500.01, DOD M 4160.21 Vol 4; and NIST SP800-88 Rev 1
            </div>

            <div class="line-row two">
                <span><strong><em>(Date)</em></strong></span>
                <span class="line"><?= h(certDate($row)) ?></span>
            </div>

            <div class="method-grid">
                <strong>Method Type</strong>
                <span><span class="box"></span> Clear</span>
                <span><span class="box"></span> Purge</span>
                <span><span class="box">X</span> Destroy</span>
                <span></span>
                <span></span>

                <strong>Method Used</strong>
                <span><span class="box">X</span> Degauss</span>
                <span><span class="box"></span> Overwrite</span>
                <span><span class="box"></span> Block Erase</span>
                <span><span class="box"></span> Crypto Erase</span>
                <span><span class="box"></span> Other</span>
            </div>

            <div class="line-row two">
                <span>Software / Degausser</span>
                <span class="line">DATA Security INC., NSA Certified</span>
            </div>
            <div class="field-note">(Manufacturer, Product Version, Date)</div>

            <div class="line-row two">
                <span>Method of Destruction</span>
                <span class="line">DEGAUSSED/PUNCHED</span>
            </div>
            <div class="field-note">(e.g., approved metal destruction facility)</div>

            <div class="line-row two">
                <span>DTID No. / Hand Receipt No.</span>
                <span class="line"></span>
            </div>

            <div class="line-row two">
                <span>Printed Name</span>
                <span class="line">KEVIN SMITH</span>
            </div>

            <div class="line-row two">
                <span>Organization Unit Name</span>
                <span class="line">MESC Greenbrier Chesapeake, VA</span>
            </div>

            <div class="line-row two">
                <span>Email</span>
                <span class="line">kevin.t.smith26.civ@us.navy.mil</span>
            </div>

            <div class="line-row">
                <span>Phone</span>
                <span class="line">757-320-1927</span>
                <span>Rank/Grade</span>
                <span class="line">CIV</span>
            </div>

            <div class="signature-row">
                <div>Signature</div>
                <div>Date</div>
            </div>

            <div class="footer">
                DLA FORM 2500, NOV 2022 (Replaces all similar forms)
            </div>
        </div>
    <?php endforeach; ?>

    <?php for ($i = count($pageRecords); $i < 4; $i++): ?>
        <div class="cert"></div>
    <?php endfor; ?>
</div>
<?php endforeach; ?>

<script>
window.onload = function () {
    window.print();
};
</script>

<?php endif; ?>

</body>
</html>