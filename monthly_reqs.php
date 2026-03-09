<?php

require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/bin/Model/ProgramMapping.php';
require_once APP_ROOT . '/bin/Model/CavRequisitions.php';
require_once APP_ROOT . '/bin/Model/PowerPointFiller.php';

$programMapping = new ProgramMapping();
$cavRequisitions = new CavRequisitions();
$powerPointFiller = new PowerPointFiller();

$message = '';
$error = '';
$reportData = [];

$selectedProgram = $_POST['ddlDistinctNormalizedProgram'] ?? '';
$selectedMonth = $_POST['ddlRecvMonth'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedProgram = trim($selectedProgram);
    $selectedMonth = trim($selectedMonth);
    
    if ($selectedProgram === '') {
        $error = 'Please select a program.';
    } elseif ($selectedMonth === '') {
        $error = 'Please select a reporting month.';
    } else {
        try {
            $dateRanges = $cavRequisitions->getReportDateRanges($selectedMonth);
            $fillerData = $powerPointFiller->getPPFiller($selectedProgram);
            
            $reportData = [
                'program' => $selectedProgram,
                'selected_month' => $selectedMonth,
                'month_label' => $dateRanges['month_label'],
                'month_start' => $dateRanges['month_start'],
                'month_end' => $dateRanges['month_end'],
                'ytd_start' => $dateRanges['ytd_start'],
                'ytd_end' => $dateRanges['ytd_end'],
                'month_line' => $dateRanges['month_line'],
                'ytd_line' => $dateRanges['ytd_line'],
                'title' => $fillerData['title'],
                'pm' => $fillerData['pm'],
                'programname' => $fillerData['programname']
            ];
            
            $message = 'Selections accepted. Report generation logic will be added next.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Requisitions Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .page-wrap {
            padding: 20px;
        }

        .form-block {
            max-width: 500px;
            margin-top: 20px;
        }

        .form-row {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 6px;
        }

        select, button {
            width: 100%;
            max-width: 350px;
            padding: 8px;
            font-size: 14px;
        }

        button {
            cursor: pointer;
            width: auto;
            min-width: 180px;
        }

        .success {
            color: green;
            margin-bottom: 15px;
        }

        .error {
            color: red;
            margin-bottom: 15px;
        }

        .report-preview {
            margin-top: 30px;
            padding: 15px;
            border: 1px solid #ccc;
            max-width: 700px;
            background: #f8f8f8;
        }

        .report-preview table {
            border-collapse: collapse;
            width: 100%;
        }

        .report-preview td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .report-preview td:first-child {
            font-weight: bold;
            width: 180px;
        }
    </style>
</head>
<body>

<?php include(APP_ROOT . '/menu.php'); ?>

<div class="page-wrap">
    <h2>Monthly Requisitions Report</h2>

    <?php if ($message !== ''): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="form-block">
        <form method="post" action="">
            <div class="form-row">
                <label for="ddlDistinctNormalizedProgram">Program</label>
                <?= $programMapping->getDDLDistinctNormalizedProgram($selectedProgram); ?>
            </div>

            <div class="form-row">
                <label for="ddlRecvMonth">Reporting Month</label>
                <?= $cavRequisitions->getDDLRecvMonths($selectedMonth); ?>
            </div>

            <div class="form-row">
                <button type="submit" name="btnGenerateReport">Generate Report</button>
            </div>
        </form>
    </div>

    <?php if (!empty($reportData)): ?>
        <div class="report-preview">
            <h3>Selected Report Values</h3>
            <table>
                <tr>
                    <td>Program</td>
                    <td><?= htmlspecialchars($reportData['program']) ?></td>
                </tr>
                <tr>
                    <td>Month</td>
                    <td><?= htmlspecialchars($reportData['month_label']) ?></td>
                </tr>
                <tr>
                    <td>Month Start</td>
                    <td><?= htmlspecialchars($reportData['month_start']) ?></td>
                </tr>
                <tr>
                    <td>Month End</td>
                    <td><?= htmlspecialchars($reportData['month_end']) ?></td>
                </tr>
                <tr>
                    <td>YTD Start</td>
                    <td><?= htmlspecialchars($reportData['ytd_start']) ?></td>
                </tr>
                <tr>
                    <td>YTD End</td>
                    <td><?= htmlspecialchars($reportData['ytd_end']) ?></td>
                </tr>
                <tr>
                    <td>Month Line</td>
                    <td><?= htmlspecialchars($reportData['month_line']) ?></td>
                </tr>
                <tr>
                    <td>YTD Line</td>
                    <td><?= htmlspecialchars($reportData['ytd_line']) ?></td>
                </tr>
                <tr>
                    <td>Title</td>
                    <td><?= htmlspecialchars($reportData['title']) ?></td>
                </tr>
                <tr>
                    <td>PM</td>
                    <td><?= htmlspecialchars($reportData['pm']) ?></td>
                </tr>
                <tr>
                    <td>Program Name</td>
                    <td><?= htmlspecialchars($reportData['programname']) ?></td>
                </tr>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>