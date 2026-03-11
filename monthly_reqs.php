<?php

require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/bin/Charts/shipped_piechart.php';
require_once APP_ROOT . '/bin/Utilities/ChartRenderer.php';
require_once APP_ROOT . '/bin/Model/SYS_ProgramMapping.php';
require_once APP_ROOT . '/bin/Model/CavRequisitions.php';
require_once APP_ROOT . '/bin/Model/SYS_PowerPointFiller.php';

use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Shape\Drawing\File;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Fill;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Border;

$programMapping = new SYS_ProgramMapping();
$cavRequisitions = new CavRequisitions();
$powerPointFiller = new SYS_PowerPointFiller();
$renderer = new ChartRenderer();

$message = '';
$error = '';
$reportData = [];

$selectedProgram = $_POST['ddlDistinctNormalizedProgram'] ?? '';
$selectedMonth = $_POST['ddlRecvMonth'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnGenerateReport'])) {
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
            
            // If getPPFiller() returns fetchAll(), use the first row
            if (isset($fillerData[0]) && is_array($fillerData[0])) {
                $fillerData = $fillerData[0];
            }
            
            $pieData = $cavRequisitions->getShippedPieData(
                $selectedProgram,
                $dateRanges['month_start'],
                $dateRanges['month_end']
                );
            
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
            
            $chartOutput = APP_ROOT . '/reports/tmp/shipped_pie_' . uniqid() . '.png';
            
            $chartConfig = ShippedPieChart::build(
                $chartOutput,
                $pieData['shipped'],
                $pieData['shippedBO']
                );
            
            $shipped = (int)$pieData['shipped'];
            $shippedBO = (int)$pieData['shippedBO'];
            
            $total = $shipped + $shippedBO;
            
            if ($total > 0) {
                $shippedPct = round(($shipped / $total) * 100, 1);
                $shippedBOPct = round(($shippedBO / $total) * 100, 1);
            } else {
                $shippedPct = 0;
                $shippedBOPct = 0;
            }
            
            $chartJsonName = 'shipped_pie_' . uniqid() . '.json';
            $shippedPiePath = $renderer->render($chartConfig, $chartJsonName);
            
            $template = APP_ROOT . '/templates/MonthlyReqsTemplate.pptx';
            
            $reader = IOFactory::createReader('PowerPoint2007');
            $ppt = $reader->load($template);
            
            $slide = $ppt->getSlide(0);
            
            $chartShape = new File();
            $chartShape->setPath($shippedPiePath)
            ->setWidth(350)
            ->setOffsetX(300)
            ->setOffsetY(200);
            
            $slide->addShape($chartShape);
            
            $lblBOShip = $slide->createRichTextShape()
            ->setHeight(60)
            ->setWidth(95)
            ->setOffsetX(485)
            ->setOffsetY(145);
            
            // center text
            $lblBOShip->getActiveParagraph()
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // white fill
            $lblBOShip->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('FFFFFFFF'));
            
            // blue border
            $lblBOShip->getBorder()
            ->setLineStyle(\PhpOffice\PhpPresentation\Style\Border::LINE_SINGLE)
            ->setLineWidth(1.5)
            ->setColor(new Color('FF2F5597'));
            
            // text
            $lblBOShip->createTextRun("B/O Shipped")->getFont()->setName('Calibri')->setSize(11);
            $lblBOShip->createBreak();
            $lblBOShip->createTextRun($pieData['shippedBO'] . " Reqs")->getFont()->setName('Calibri')->setSize(11);
            $lblBOShip->createBreak();
            $lblBOShip->createTextRun($shippedBOPct . "%")->getFont()->setName('Calibri')->setSize(11);
                        
            $lblShipped = $slide->createRichTextShape()
            ->setHeight(60)
            ->setWidth(85)
            ->setOffsetX(385)
            ->setOffsetY(445);
            
            // center text
            $lblShipped->getActiveParagraph()
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // white fill
            $lblShipped->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('FFFFFFFF'));
            
            // blue border
            $lblShipped->getBorder()
            ->setLineStyle(\PhpOffice\PhpPresentation\Style\Border::LINE_SINGLE)
            ->setLineWidth(1.5)
            ->setColor(new Color('FF2F5597'));
            
            $lblShipped->createTextRun("Shipped ")->getFont()->setName('Calibri')->setSize(11);
            $lblShipped->createBreak();
            $lblShipped->createTextRun($pieData['shipped'] . " Reqs")->getFont()->setName('Calibri')->setSize(11);
            $lblShipped->createBreak();
            $lblShipped->createTextRun($shippedPct . "%")->getFont()->setName('Calibri')->setSize(11);
            
            $totalReqsShipped = $pieData['shipped'] + $pieData['shippedBO'];
            
            $lblReqsShipped = $slide->createRichTextShape()
            ->setHeight(50)
            ->setWidth(150)
            ->setOffsetX(395)
            ->setOffsetY(295);
            $lblReqsShipped->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblReqsShipped->createTextRun($totalReqsShipped)->getFont()->setName('Aptos Narrow')->setColor(new Color('FF00008B'))->setSize(16);
            $lblReqsShipped->createBreak();
            $lblReqsShipped->createTextRun("Reqs Shipped")->getFont()->setName('Aptos Narrow')->setColor(new Color('FF00008B'))->setSize(16);
            
            $lblTitle = $slide->createRichTextShape()
            ->setHeight(50)
            ->setWidth(500)
            ->setOffsetX(455)
            ->setOffsetY(30);
            $lblTitle->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblTitle->createTextRun($fillerData['title'])->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(32);
            
            $lblPM = $slide->createRichTextShape()
            ->setHeight(50)
            ->setWidth(575)
            ->setOffsetX(390)
            ->setOffsetY(80);
            $lblPM->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblPM->createTextRun("{$fillerData['pm']}  {$fillerData['programname']}")->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(32);
            
            $monthStart = date('M d, Y', strtotime($dateRanges['month_start']));
            $monthEnd   = date('M d, Y', strtotime($dateRanges['month_end']));
            $ytdStart   = date('M d, Y', strtotime($dateRanges['ytd_start']));
            $ytdEnd     = date('M d, Y', strtotime($dateRanges['ytd_end']));
            
            $lblReportPeriod = $slide->createRichTextShape()
            ->setHeight(120)
            ->setWidth(375)
            ->setOffsetX(575)
            ->setOffsetY(140);
            $lblReportPeriod->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblReportPeriod->createTextRun("Report Period")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FF000000'))->setSize(16);
            $lblReportPeriod->createBreak();
            $lblReportPeriod->createTextRun("Month: " . $monthStart . " to " . $monthEnd)->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FFFF991C'))->setSize(16);
            $lblReportPeriod->createBreak();
            $lblReportPeriod->createTextRun("YTD: " . $ytdStart . " to " . $ytdEnd)->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FF00008B'))->setSize(16);
            
            $lblMOARequirements = $slide->createRichTextShape()
            ->setHeight(130)
            ->setWidth(190)
            ->setOffsetX(700)
            ->setOffsetY(270);
            $lblMOARequirements->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblMOARequirements->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFE4E8D3'));
            $lblMOARequirements->getBorder()->setLineStyle(\PhpOffice\PhpPresentation\Style\Border::LINE_SINGLE)->setLineWidth(1.5)->setColor(new Color('FF9BBB59'));
            $lblMOARequirements->createTextRun("MOA Requirements")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FF000000'))->setSize(12);
            $lblMOARequirements->createBreak();
            $lblMOARequirements->createTextRun("0 >= 270 Days")->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(12);
            $lblMOARequirements->createBreak();
            $lblMOARequirements->createTextRun("85% - % Fill Rate")->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(12);
            $lblMOARequirements->createBreak();
            $lblMOARequirements->createTextRun("1 - Day RT CAREPs")->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(12);
            $lblMOARequirements->createBreak();
            $lblMOARequirements->createTextRun("3 - Day RT (Non-CASREP)")->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(12);
            $lblMOARequirements->createBreak();
            $lblMOARequirements->createTextRun("90 - Day RT Backorders")->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(12);
            
            $output = APP_ROOT . '/reports/tmp/monthly_report_' . uniqid() . '.pptx';
            
            $writer = IOFactory::createWriter($ppt, 'PowerPoint2007');
            $writer->save($output);
            
            if (ob_get_length()) {
                ob_end_clean();
            }
            
            $reportName = str_replace('/', '-', $selectedProgram) . " - " . $dateRanges['month_label'] . " - Monthly Report";
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.presentationml.presentation');
            header('Content-Disposition: attachment; filename="' . $reportName . '.pptx"');
            header('Content-Length: ' . filesize($output));
            
            readfile($output);
            exit;
            
            /* $message = 'Selections accepted and shipped pie chart generated successfully.'; */
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
    <?php if (!empty($reportData['shipped_pie_path'])): ?>
    <?php $chartUrl = str_replace(APP_ROOT, '/dashboard', $reportData['shipped_pie_path']); ?>
    <div class="report-preview">
        <h3>Generated Shipped Pie Chart</h3>
        <table>
            <tr>
                <td>Shipped</td>
                <td><?= htmlspecialchars((string)$reportData['shipped']) ?></td>
            </tr>
            <tr>
                <td>B/O Shipped</td>
                <td><?= htmlspecialchars((string)$reportData['shippedBO']) ?></td>
            </tr>
        </table>
        <p style="margin-top:15px;">
            <img src="<?= htmlspecialchars($chartUrl) ?>" alt="Shipped Pie Chart" style="max-width:700px;">
        </p>
    </div>
<?php endif; ?>
</div>

</body>
</html>