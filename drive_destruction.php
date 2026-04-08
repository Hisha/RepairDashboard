<?php
require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/bin/Model/DriveDestruction.php';
require_once APP_ROOT . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$drive = new DriveDestruction();

$filters = [
    'search'    => trim($_GET['search'] ?? ''),
    'status'    => trim($_GET['status'] ?? ''),
    'method'    => trim($_GET['method'] ?? ''),
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to'   => trim($_GET['date_to'] ?? ''),
    'sort'      => trim($_GET['sort'] ?? 'destruction_date'),
    'dir'       => trim($_GET['dir'] ?? 'DESC'),
];

if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    $records = $drive->getRecords($filters);
    
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Drive Destruction Log');
    
    $headers = [
        'ID',
        'Part Number',
        'Serial Number',
        'NIIN',
        'Description',
        'Qty',
        'Method',
        'Destruction Date',
        'Destroyer',
        'Destroyer Signed At',
        'Witness',
        'Witness Signed At',
        'Status',
        'Notes',
        'Void Reason',
        'Voided By',
        'Voided At',
        'Created At',
        'Created By',
        'Updated At'
    ];
    
    $col = 1;
    foreach ($headers as $header) {
        $columnLetter = Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($columnLetter . '1', $header);
        $col++;
    }
    
    $rowNum = 2;
    foreach ($records as $row) {
        $sheet->setCellValueExplicit("A{$rowNum}", (string)$row['id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("B{$rowNum}", (string)$row['part_number'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("C{$rowNum}", (string)$row['serial_number'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("D{$rowNum}", (string)($row['niin'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue("E{$rowNum}", $row['description'] ?? '');
        $sheet->setCellValue("F{$rowNum}", $row['quantity'] ?? 1);
        $sheet->setCellValue("G{$rowNum}", $row['destruction_method'] ?? '');
        $sheet->setCellValue("H{$rowNum}", $row['destruction_date'] ?? '');
        $sheet->setCellValue("I{$rowNum}", $row['destroyer_name'] ?? '');
        $sheet->setCellValue("J{$rowNum}", $row['destroyer_signed_at'] ?? '');
        $sheet->setCellValue("K{$rowNum}", $row['witness_name'] ?? '');
        $sheet->setCellValue("L{$rowNum}", $row['witness_signed_at'] ?? '');
        $sheet->setCellValue("M{$rowNum}", $row['status'] ?? '');
        $sheet->setCellValue("N{$rowNum}", $row['notes'] ?? '');
        $sheet->setCellValue("O{$rowNum}", $row['void_reason'] ?? '');
        $sheet->setCellValue("P{$rowNum}", $row['voided_by'] ?? '');
        $sheet->setCellValue("Q{$rowNum}", $row['voided_at'] ?? '');
        $sheet->setCellValue("R{$rowNum}", $row['created_at'] ?? '');
        $sheet->setCellValue("S{$rowNum}", $row['created_by'] ?? '');
        $sheet->setCellValue("T{$rowNum}", $row['updated_at'] ?? '');
        $rowNum++;
    }
    
    foreach (range('A', 'T') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }
    
    $sheet->freezePane('A2');
    $sheet->setAutoFilter("A1:T1");
    
    $filename = 'drive_destruction_log_' . date('Y-m-d_His') . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

$message = '';
$error = '';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function buildQueryString(array $overrides = []): string
{
    $params = $_GET;
    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    return http_build_query($params);
}

function sortLink(string $column): string
{
    $currentSort = $_GET['sort'] ?? 'destruction_date';
    $currentDir  = strtoupper($_GET['dir'] ?? 'DESC');
    
    $nextDir = 'ASC';
    if ($currentSort === $column && $currentDir === 'ASC') {
        $nextDir = 'DESC';
    }
    
    return '?' . buildQueryString([
        'sort' => $column,
        'dir'  => $nextDir
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_record') {
            $partNumber = trim($_POST['part_number'] ?? '');
            $serialNumber = trim($_POST['serial_number'] ?? '');
            $destructionMethod = trim($_POST['destruction_method'] ?? '');
            $destructionDate = trim($_POST['destruction_date'] ?? '');
            
            if ($partNumber === '' || $serialNumber === '' || $destructionMethod === '' || $destructionDate === '') {
                throw new Exception('Part number, serial number, destruction method, and destruction date are required.');
            }
            
            $drive->createRecord([
                'part_number' => $partNumber,
                'serial_number' => $serialNumber,
                'niin' => $_POST['niin'] ?? '',
                'description' => $_POST['description'] ?? '',
                'quantity' => 1,
                'destruction_method' => $destructionMethod,
                'destruction_date' => $destructionDate,
                'notes' => $_POST['notes'] ?? '',
                'created_by' => $_POST['created_by'] ?? '',
            ]);
            
            $message = 'Drive destruction record created successfully.';
        }
        
        if ($action === 'sign_destroyer') {
            $id = intval($_POST['record_id'] ?? 0);
            $fullName = trim($_POST['destroyer_name'] ?? '');
            
            if ($id <= 0 || $fullName === '') {
                throw new Exception('Destroyer signoff requires a valid record and full name.');
            }
            
            $drive->signDestroyer($id, $fullName);
            $message = 'Destroyer signature added successfully.';
        }
        
        if ($action === 'sign_witness') {
            $id = intval($_POST['record_id'] ?? 0);
            $fullName = trim($_POST['witness_name'] ?? '');
            
            if ($id <= 0 || $fullName === '') {
                throw new Exception('Witness signoff requires a valid record and full name.');
            }
            
            $drive->signWitness($id, $fullName);
            $message = 'Witness signature added successfully.';
        }
        
        if ($action === 'void_record') {
            $id = intval($_POST['record_id'] ?? 0);
            $voidReason = trim($_POST['void_reason'] ?? '');
            $performedBy = trim($_POST['performed_by'] ?? '');
            
            if ($id <= 0) {
                throw new Exception('Invalid record selected.');
            }
            
            if ($voidReason === '') {
                throw new Exception('Void reason is required.');
            }
            
            $drive->voidRecord($id, $voidReason, $performedBy);
            $message = 'Record voided successfully.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$records = $drive->getRecords($filters);

require_once APP_ROOT . '/menu.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Drive Destruction Log</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .page-wrap {
            width: 98%;
            margin: 15px auto;
        }

        .section-box {
            background: #fff;
            border: 1px solid #ccc;
            padding: 14px;
            margin-bottom: 18px;
        }

        .message-success {
            background: #e8f5e9;
            color: #1b5e20;
            border: 1px solid #a5d6a7;
            padding: 10px;
            margin-bottom: 12px;
        }

        .message-error {
            background: #ffebee;
            color: #b71c1c;
            border: 1px solid #ef9a9a;
            padding: 10px;
            margin-bottom: 12px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(180px, 1fr));
            gap: 12px;
        }

        .form-grid .full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 4px;
        }

        input[type="text"],
        input[type="date"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 8px;
        }

        textarea {
            min-height: 70px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            padding: 8px 14px;
            border: 1px solid #666;
            background: #f0f0f0;
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #e0e0e0;
        }

        .btn-danger {
            background: #fbe9e7;
            border-color: #d84315;
        }

        .btn-export {
            background: #e3f2fd;
            border-color: #1565c0;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid #ccc;
            background: #fff;
        }

        table.destruction-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1600px;
        }

        table.destruction-table th,
        table.destruction-table td {
            border: 1px solid #ccc;
            padding: 8px;
            vertical-align: top;
            text-align: left;
        }

        table.destruction-table thead th {
            position: sticky;
            top: 0;
            background: #f6f6f6;
            z-index: 2;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
        }

        .status-pending {
            background: #fff8e1;
            color: #8d6e63;
        }

        .status-partially-signed {
            background: #e3f2fd;
            color: #1565c0;
        }

        .status-completed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-voided {
            background: #ffebee;
            color: #c62828;
        }

        .signature-preview {
            max-width: 180px;
            max-height: 50px;
            display: block;
            margin-top: 4px;
            border-bottom: 1px solid #999;
        }

        .mini-form input[type="text"] {
            margin-bottom: 6px;
        }

        .action-stack form {
            margin-bottom: 8px;
        }

        .small-note {
            color: #555;
            font-size: 12px;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .nowrap {
            white-space: nowrap;
        }
    </style>
</head>
<body>
<div class="page-wrap">

    <h1>Drive Destruction Log</h1>

    <?php if ($message !== ''): ?>
        <div class="message-success"><?php echo h($message); ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="message-error"><?php echo h($error); ?></div>
    <?php endif; ?>

    <div class="section-box">
        <h2>Add New Destruction Record</h2>

        <form method="post">
            <input type="hidden" name="action" value="add_record">

            <div class="form-grid">
                <div>
                    <label for="part_number">Part Number</label>
                    <input type="text" id="part_number" name="part_number" required>
                </div>

                <div>
                    <label for="serial_number">Serial Number</label>
                    <input type="text" id="serial_number" name="serial_number" required>
                </div>

                <div>
                    <label for="niin">NIIN</label>
                    <input type="text" id="niin" name="niin">
                </div>

                <div class="full-width">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description">
                </div>

                <div>
                    <label for="destruction_method">Destruction Method</label>
                    <select id="destruction_method" name="destruction_method" required>
                        <option value="Degauss">Degauss</option>
                        <option value="Punch">Punch</option>
                        <option value="Both" selected>Both</option>
                        <option value="Shredded">Shredded</option>
                    </select>
                </div>

                <div>
                    <label for="destruction_date">Destruction Date</label>
                    <input type="date" id="destruction_date" name="destruction_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div>
                    <label for="created_by">Entered By</label>
                    <input type="text" id="created_by" name="created_by">
                </div>

                <div></div>

                <div class="full-width">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes"></textarea>
                </div>

                <div class="full-width">
                    <button type="submit" class="btn">Add Record</button>
                </div>
            </div>
        </form>
    </div>

    <div class="section-box">
        <h2>Filters</h2>

        <form method="get">
            <div class="form-grid">
                <div>
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo h($filters['search']); ?>">
                </div>

                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">-- All --</option>
                        <option value="Pending" <?php echo $filters['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Partially Signed" <?php echo $filters['status'] === 'Partially Signed' ? 'selected' : ''; ?>>Partially Signed</option>
                        <option value="Completed" <?php echo $filters['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Voided" <?php echo $filters['status'] === 'Voided' ? 'selected' : ''; ?>>Voided</option>
                    </select>
                </div>

                <div>
                    <label for="method">Method</label>
                    <select id="method" name="method">
                        <option value="">-- All --</option>
                        <option value="Degauss" <?php echo $filters['method'] === 'Degauss' ? 'selected' : ''; ?>>Degauss</option>
                        <option value="Punch" <?php echo $filters['method'] === 'Punch' ? 'selected' : ''; ?>>Punch</option>
                        <option value="Both" <?php echo $filters['method'] === 'Both' ? 'selected' : ''; ?>>Both</option>
                        <option value="Shredded" <?php echo $filters['method'] === 'Shredded' ? 'selected' : ''; ?>>Shredded</option>
                    </select>
                </div>

                <div>
                    <label for="date_from">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo h($filters['date_from']); ?>">
                </div>

                <div>
                    <label for="date_to">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo h($filters['date_to']); ?>">
                </div>

                <div>
                    <label>&nbsp;</label>
                    <button type="submit" class="btn">Apply Filters</button>
                    <a href="drive_destruction.php" class="btn">Reset</a>
                </div>

                <div>
                    <label>&nbsp;</label>
                    <a href="?<?php echo h(buildQueryString(['export' => 'xlsx'])); ?>" class="btn btn-export">Export XLSX</a>
                </div>
            </div>
        </form>
    </div>

    <div class="section-box">
        <div class="toolbar">
            <h2 style="margin: 0;">Destruction Records</h2>
            <div class="small-note">Showing <?php echo count($records); ?> record(s)</div>
        </div>

        <div class="table-wrap">
            <table class="destruction-table">
                <thead>
                <tr>
                    <th><a href="<?php echo h(sortLink('id')); ?>">ID</a></th>
                    <th><a href="<?php echo h(sortLink('part_number')); ?>">Part Number</a></th>
                    <th><a href="<?php echo h(sortLink('serial_number')); ?>">Serial Number</a></th>
                    <th><a href="<?php echo h(sortLink('niin')); ?>">NIIN</a></th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th><a href="<?php echo h(sortLink('destruction_method')); ?>">Method</a></th>
                    <th><a href="<?php echo h(sortLink('destruction_date')); ?>">Destruction Date</a></th>
                    <th>Destroyer</th>
                    <th>Witness</th>
                    <th><a href="<?php echo h(sortLink('status')); ?>">Status</a></th>
                    <th>Notes</th>
                    <th>Void Reason</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="14">No records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $row): ?>
                        <?php
                        $statusClass = 'status-pending';
                        if ($row['status'] === 'Partially Signed') {
                            $statusClass = 'status-partially-signed';
                        } elseif ($row['status'] === 'Completed') {
                            $statusClass = 'status-completed';
                        } elseif ($row['status'] === 'Voided') {
                            $statusClass = 'status-voided';
                        }
                        ?>
                        <tr>
                            <td class="nowrap"><?php echo h($row['id']); ?></td>
                            <td><?php echo h($row['part_number']); ?></td>
                            <td><?php echo h($row['serial_number']); ?></td>
                            <td><?php echo h($row['niin']); ?></td>
                            <td><?php echo h($row['description']); ?></td>
                            <td><?php echo h($row['quantity']); ?></td>
                            <td><?php echo h($row['destruction_method']); ?></td>
                            <td class="nowrap"><?php echo h($row['destruction_date']); ?></td>

                            <td>
                                <?php if (!empty($row['destroyer_name'])): ?>
                                    <strong><?php echo h($row['destroyer_name']); ?></strong><br>
                                    <span class="small-note"><?php echo h($row['destroyer_signed_at']); ?></span>
                                    <?php if (!empty($row['destroyer_signature_path']) && file_exists(APP_ROOT . '/' . $row['destroyer_signature_path'])): ?>
                                        <img
                                            src="<?php echo h($row['destroyer_signature_path']); ?>"
                                            alt="Destroyer Signature"
                                            class="signature-preview"
                                        >
                                    <?php endif; ?>
                                <?php elseif ($row['status'] !== 'Voided'): ?>
                                    <form method="post" class="mini-form">
                                        <input type="hidden" name="action" value="sign_destroyer">
                                        <input type="hidden" name="record_id" value="<?php echo h($row['id']); ?>">
                                        <input type="text" name="destroyer_name" placeholder="First Last" required>
                                        <button type="submit" class="btn">Sign</button>
                                    </form>
                                <?php else: ?>
                                    <span class="small-note">Voided</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (!empty($row['witness_name'])): ?>
                                    <strong><?php echo h($row['witness_name']); ?></strong><br>
                                    <span class="small-note"><?php echo h($row['witness_signed_at']); ?></span>
                                    <?php if (!empty($row['witness_signature_path']) && file_exists(APP_ROOT . '/' . $row['witness_signature_path'])): ?>
                                        <img
                                            src="<?php echo h($row['witness_signature_path']); ?>"
                                            alt="Witness Signature"
                                            class="signature-preview"
                                        >
                                    <?php endif; ?>
                                <?php elseif ($row['status'] !== 'Voided'): ?>
                                    <form method="post" class="mini-form">
                                        <input type="hidden" name="action" value="sign_witness">
                                        <input type="hidden" name="record_id" value="<?php echo h($row['id']); ?>">
                                        <input type="text" name="witness_name" placeholder="First Last" required>
                                        <button type="submit" class="btn">Sign</button>
                                    </form>
                                <?php else: ?>
                                    <span class="small-note">Voided</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="status-badge <?php echo h($statusClass); ?>">
                                    <?php echo h($row['status']); ?>
                                </span>
                            </td>

                            <td><?php echo nl2br(h($row['notes'])); ?></td>

                            <td>
                                <?php if (!empty($row['void_reason'])): ?>
                                    <?php echo nl2br(h($row['void_reason'])); ?><br>
                                    <span class="small-note">
                                        <?php echo h($row['voided_by']); ?>
                                        <?php if (!empty($row['voided_at'])): ?>
                                            <br><?php echo h($row['voided_at']); ?>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="action-stack">
                                <div style="margin-bottom: 8px;">
                                    <a
                                        href="drive_destruction_certificate.php?id=<?php echo h($row['id']); ?>"
                                        class="btn"
                                        target="_blank"
                                    >Certificate</a>
                                </div>
                            
                                <?php if ($row['status'] !== 'Voided'): ?>
                                    <form method="post" onsubmit="return promptVoidReason(this);">
                                        <input type="hidden" name="action" value="void_record">
                                        <input type="hidden" name="record_id" value="<?php echo h($row['id']); ?>">
                                        <input type="hidden" name="performed_by" value="">
                                        <input type="hidden" name="void_reason" value="">
                                        <button type="submit" class="btn btn-danger">Void</button>
                                    </form>
                                <?php else: ?>
                                    <span class="small-note">Record voided</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function promptVoidReason(form) {
    const reason = prompt('Enter reason for voiding this record:');
    if (reason === null) {
        return false;
    }

    const trimmed = reason.trim();
    if (trimmed === '') {
        alert('Void reason is required.');
        return false;
    }

    form.querySelector('input[name="void_reason"]').value = trimmed;
    return confirm('Void this record with the entered reason?');
}
</script>

</body>
</html>