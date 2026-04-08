<?php
require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/bin/Model/DriveDestruction.php';

$drive = new DriveDestruction();

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    exit('Invalid record ID.');
}

$record = $drive->getRecordById($id);
if (!$record) {
    exit('Record not found.');
}

$auditEntries = $drive->getAuditEntries($id);

$isVoided = ($record['status'] === 'Voided');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Drive Destruction Certificate - Record #<?php echo h($record['id']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #000;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .certificate-wrap {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #333;
            padding: 30px;
            box-sizing: border-box;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .print-btn{
            display: inline-block;
            padding: 10px 16px;
            border: 1px solid #333;
            background: #eee;
            color: #000;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .print-btn:hover,
        .back-btn:hover {
            background: #ddd;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }

        .header h1 {
            margin: 0 0 8px 0;
            font-size: 28px;
            letter-spacing: 0.5px;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: normal;
        }

        .void-banner {
            margin: 20px 0;
            padding: 14px;
            border: 2px solid #b71c1c;
            background: #ffebee;
            color: #b71c1c;
            font-weight: bold;
            text-align: center;
            font-size: 18px;
        }

        .section-title {
            margin: 25px 0 10px 0;
            font-size: 18px;
            border-bottom: 1px solid #999;
            padding-bottom: 5px;
        }

        table.detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.detail-table th,
        table.detail-table td {
            border: 1px solid #999;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }

        table.detail-table th {
            width: 220px;
            background: #f0f0f0;
        }

        .statement-box {
            border: 1px solid #999;
            padding: 15px;
            line-height: 1.5;
            margin-top: 10px;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .signature-block {
            border-top: 1px solid #000;
            padding-top: 10px;
            min-height: 150px;
        }

        .signature-label {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .signature-image {
            max-width: 300px;
            max-height: 80px;
            display: block;
            margin-bottom: 10px;
        }

        .signature-meta {
            font-size: 14px;
            line-height: 1.5;
        }

        .audit-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .audit-table th,
        .audit-table td {
            border: 1px solid #999;
            padding: 8px;
            text-align: left;
            vertical-align: top;
            font-size: 13px;
        }

        .audit-table th {
            background: #f0f0f0;
        }

        .footer-note {
            margin-top: 25px;
            font-size: 12px;
            color: #555;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .top-bar {
                display: none;
            }

            .certificate-wrap {
                border: none;
                margin: 0;
                max-width: none;
                padding: 0;
            }

            a {
                text-decoration: none;
                color: #000;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-wrap">
        <div class="top-bar">
            <button class="print-btn" onclick="window.print()">Print Certificate</button>
        </div>

        <div class="header">
            <h1>Drive Destruction Certificate</h1>
            <h2>Record #<?php echo h($record['id']); ?></h2>
        </div>

        <?php if ($isVoided): ?>
            <div class="void-banner">
                VOIDED RECORD
                <?php if (!empty($record['void_reason'])): ?>
                    <br>
                    Reason: <?php echo h($record['void_reason']); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="section-title">Item Information</div>
        <table class="detail-table">
            <tr>
                <th>Part Number</th>
                <td><?php echo h($record['part_number']); ?></td>
            </tr>
            <tr>
                <th>Serial Number</th>
                <td><?php echo h($record['serial_number']); ?></td>
            </tr>
            <tr>
                <th>NIIN</th>
                <td><?php echo h($record['niin']); ?></td>
            </tr>
            <tr>
                <th>Description</th>
                <td><?php echo h($record['description']); ?></td>
            </tr>
            <tr>
                <th>Quantity</th>
                <td><?php echo h($record['quantity'] ?: 1); ?></td>
            </tr>
        </table>

        <div class="section-title">Destruction Information</div>
        <table class="detail-table">
            <tr>
                <th>Destruction Method</th>
                <?php
                $methodMap = [
                    'Both' => 'Degaussed and Punched',
                    'Degauss' => 'Degaussed',
                    'Punch' => 'Punched',
                    'Shredded' => 'Shredded'
                ];
                
                $methodDisplay = $methodMap[$record['destruction_method']] ?? $record['destruction_method'];
                ?>
                <td><?php echo h($methodDisplay); ?></td>
            </tr>
            <tr>
                <th>Date of Destruction</th>
                <td><?php echo h($record['destruction_date']); ?></td>
            </tr>
            <tr>
                <th>Status</th>
                <td><?php echo h($record['status']); ?></td>
            </tr>
            <tr>
                <th>Notes</th>
                <td><?php echo nl2br(h($record['notes'])); ?></td>
            </tr>
            <?php if ($isVoided): ?>
                <tr>
                    <th>Void Reason</th>
                    <td><?php echo nl2br(h($record['void_reason'])); ?></td>
                </tr>
                <tr>
                    <th>Voided By / At</th>
                    <td>
                        <?php echo h($record['voided_by']); ?>
                        <?php if (!empty($record['voided_at'])): ?>
                            <br><?php echo h($record['voided_at']); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </table>

        <div class="section-title">Certification Statement</div>
        <div class="statement-box">
            I certify that the media identified above was processed for destruction using the method listed on this record,
            in accordance with local procedures and applicable requirements for rendering the media unusable and unsuitable
            for reuse. The signatures below attest to the destruction action and witness/approval of the recorded event.
        </div>

        <div class="signature-grid">
            <div class="signature-block">
                <div class="signature-label">Destroyer</div>

                <?php if (!empty($record['destroyer_signature_path']) && file_exists(APP_ROOT . '/' . $record['destroyer_signature_path'])): ?>
                    <img
                        src="<?php echo h($record['destroyer_signature_path']); ?>"
                        alt="Destroyer Signature"
                        class="signature-image"
                    >
                <?php endif; ?>

                <div class="signature-meta">
                    <strong>Name:</strong> <?php echo h($record['destroyer_name']); ?><br>
                    <strong>Signed At:</strong> <?php echo h($record['destroyer_signed_at']); ?>
                </div>
            </div>

            <div class="signature-block">
                <div class="signature-label">Witness / Approver</div>

                <?php if (!empty($record['witness_signature_path']) && file_exists(APP_ROOT . '/' . $record['witness_signature_path'])): ?>
                    <img
                        src="<?php echo h($record['witness_signature_path']); ?>"
                        alt="Witness Signature"
                        class="signature-image"
                    >
                <?php endif; ?>

                <div class="signature-meta">
                    <strong>Name:</strong> <?php echo h($record['witness_name']); ?><br>
                    <strong>Signed At:</strong> <?php echo h($record['witness_signed_at']); ?>
                </div>
            </div>
        </div>

        <div class="section-title">Record Tracking</div>
        <table class="detail-table">
            <tr>
                <th>Created By</th>
                <td><?php echo h($record['created_by']); ?></td>
            </tr>
            <tr>
                <th>Created At</th>
                <td><?php echo h($record['created_at']); ?></td>
            </tr>
            <tr>
                <th>Last Updated</th>
                <td><?php echo h($record['updated_at']); ?></td>
            </tr>
        </table>

        <div class="section-title">Audit Trail</div>
        <table class="audit-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Action</th>
                    <th>Performed By</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($auditEntries)): ?>
                    <tr>
                        <td colspan="4">No audit entries found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($auditEntries as $entry): ?>
                        <tr>
                            <td><?php echo h($entry['action_timestamp']); ?></td>
                            <td><?php echo h($entry['action']); ?></td>
                            <td><?php echo h($entry['performed_by']); ?></td>
                            <td><?php echo nl2br(h($entry['details'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer-note">
            This certificate was generated from the Drive Destruction Log dashboard.
        </div>
    </div>
</body>
</html>