<?php

require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/bin/Charts/shipped_piechart.php';
require_once APP_ROOT . '/bin/Charts/shipped_doughnutchart.php';
require_once APP_ROOT . '/bin/Utilities/ChartRenderer.php';
require_once APP_ROOT . '/bin/Model/SYS_ProgramMapping.php';
require_once APP_ROOT . '/bin/Model/CavRequisitions.php';
require_once APP_ROOT . '/bin/Model/SYS_PowerPointFiller.php';

use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Shape\Drawing\Base64;
use PhpOffice\PhpPresentation\Shape\Drawing\File;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Fill;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Font;

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
            
            $pieData_Shipped = $cavRequisitions->getPieData_Shipped($selectedProgram, $dateRanges['month_start'], $dateRanges['month_end']);
            $pieData_BOShipped = $cavRequisitions->getPieData_BOShipped($selectedProgram, $dateRanges['month_start'], $dateRanges['month_end']);
            
           $doughnutData = $cavRequisitions->getShippedDoughnutData(
                $selectedProgram,
                $dateRanges['month_start'],
                $dateRanges['month_end'],
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
            
            $ytdTotalReqsRecvd = $cavRequisitions->getYTDReqsRecvd($selectedProgram, $dateRanges['ytd_start'], $dateRanges['ytd_end']);
            $ytdUniqueNiins = $cavRequisitions->getYTDUniqueNiins($selectedProgram, $dateRanges['ytd_start'], $dateRanges['ytd_end']);
            $ytdTotalNiins = $cavRequisitions->getYTDTotalNiins($selectedProgram, $dateRanges['ytd_start'], $dateRanges['ytd_end']);
            
            $chartOutput = APP_ROOT . '/reports/tmp/shipped_pie_' . uniqid() . '.png';
            
            $chartConfig = ShippedPieChart::build(
                $chartOutput,
                $pieData_Shipped,
                $pieData_BOShipped
                );
            
            $shipped = (int)$pieData_Shipped;
            $shippedBO = (int)$pieData_BOShipped;
            
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
            
            $doughnutOutput = APP_ROOT . '/reports/tmp/shipped_doughnut_' . uniqid() . '.png';
            
            $doughnutConfig = ShippedDoughnutChart::build(
                $doughnutOutput,
                $doughnutData['fleetFailure'],
                $doughnutData['nineNineNine'],
                $doughnutData['spare'],
                $doughnutData['anors'],
                $doughnutData['casrep']
                );
            
            $shippedFleeteFailure = (int)$doughnutData['fleetFailure'];
            $shippedNineNineNine = (int)$doughnutData['nineNineNine'];
            $shippedSpare = (int)$doughnutData['spare'];
            $shippedANORS = (int)$doughnutData['anors'];
            $shippedCASREP = (int)$doughnutData['casrep'];
            
            $totalShipped = $shippedFleeteFailure + $shippedNineNineNine + $shippedSpare + $shippedANORS + $shippedCASREP;
            
            if ($totalShipped > 0){
                $shippedFleeteFailurePct = round(($shippedFleeteFailure / $totalShipped) * 100, 1);
                $shippedNineNineNinePct = round(($shippedNineNineNine / $totalShipped) * 100, 1);
                $shippedSparePct = round(($shippedSpare / $totalShipped) * 100, 1);
                $shippedANORSPct = round(($shippedANORS / $totalShipped) * 100, 1);
                $shippedCASREPPct = round(($shippedCASREP / $totalShipped) * 100, 1);
            }else{
                $shippedFleeteFailurePct = 0;
                $shippedNineNineNinePct = 0;
                $shippedSparePct = 0;
                $shippedANORSPct = 0;
                $shippedCASREPPct = 0;
            }
            
            $doughnutJsonName = 'shipped_doughnut_' . uniqid() . '.json';
            $shippedDoughnutPath = $renderer->render($doughnutConfig, $doughnutJsonName);
            
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
                        
            $doughnutShape = new File();
            $doughnutShape->setName('Shipped Doughnut')
            ->setDescription('Shipped doughnut chart')
            ->setPath($shippedDoughnutPath)
            ->setWidth(300)
            ->setOffsetX(325)
            ->setOffsetY(220);
            
            $slide->addShape($doughnutShape);
            
            $lblBOShip = $slide->createRichTextShape()
            ->setHeight(60)
            ->setWidth(95)
            ->setOffsetX(485)
            ->setOffsetY(145);
            $lblBOShip->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblBOShip->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFFFFFFF'));
            $lblBOShip->getBorder()->setLineStyle(\PhpOffice\PhpPresentation\Style\Border::LINE_SINGLE)->setLineWidth(1.5)->setColor(new Color('FF2F5597'));
            $lblBOShip->createTextRun("B/O Shipped")->getFont()->setName('Calibri')->setSize(11);
            $lblBOShip->createBreak();
            $lblBOShip->createTextRun($pieData_BOShipped . " Reqs")->getFont()->setName('Calibri')->setSize(11);
            $lblBOShip->createBreak();
            $lblBOShip->createTextRun($shippedBOPct . "%")->getFont()->setName('Calibri')->setSize(11);
                        
            $lblShipped = $slide->createRichTextShape()
            ->setHeight(60)
            ->setWidth(85)
            ->setOffsetX(385)
            ->setOffsetY(445);
            $lblShipped->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblShipped->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFFFFFFF'));
            $lblShipped->getBorder()->setLineStyle(\PhpOffice\PhpPresentation\Style\Border::LINE_SINGLE)->setLineWidth(1.5)->setColor(new Color('FF2F5597'));
            $lblShipped->createTextRun("Shipped ")->getFont()->setName('Calibri')->setSize(11);
            $lblShipped->createBreak();
            $lblShipped->createTextRun($pieData_Shipped . " Reqs")->getFont()->setName('Calibri')->setSize(11);
            $lblShipped->createBreak();
            $lblShipped->createTextRun($shippedPct . "%")->getFont()->setName('Calibri')->setSize(11);
            
            $totalReqsShipped = $pieData_Shipped + $pieData_BOShipped;
            
            $lblReqsShipped = $slide->createRichTextShape()
            ->setHeight(50)
            ->setWidth(150)
            ->setOffsetX(400)
            ->setOffsetY(300);
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
            $lblReportPeriod->createTextRun("Report Period")->getFont()->setName('Calibri')->setBold(true)->setUnderline(Font::UNDERLINE_SINGLE)->setColor(new Color('FF000000'))->setSize(16);
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
            
            $lblFleetFailure = $slide->createRichTextShape()
            ->setHeight(60)
            ->setWidth(100)
            ->setOffsetX(575)
            ->setOffsetY(395);
            $lblFleetFailure->getActiveParagraph()
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblFleetFailure->createTextRun("Fleet Failure ")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FF0B6E6E'))->setSize(11);
            $lblFleetFailure->createBreak();
            $lblFleetFailure->createTextRun($doughnutData['fleetFailure'] . " Reqs")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FF0B6E6E'))->setSize(11);
            $lblFleetFailure->createBreak();
            $lblFleetFailure->createTextRun($shippedFleeteFailurePct . "%")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FF0B6E6E'))->setSize(11);
            
            $lblCASREP = $slide->createRichTextShape()
            ->setHeight(60)
            ->setWidth(85)
            ->setOffsetX(350)
            ->setOffsetY(150);
            $lblCASREP->getActiveParagraph()
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblCASREP->createTextRun("CASREP")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FFC0392B'))->setSize(11);
            $lblCASREP->createBreak();
            $lblCASREP->createTextRun($doughnutData['casrep'] . " Reqs")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FFC0392B'))->setSize(11);
            $lblCASREP->createBreak();
            $lblCASREP->createTextRun($shippedCASREPPct . "%")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FFC0392B'))->setSize(11);
            
            $lblANORS = $slide->createRichTextShape()
            ->setHeight(60)
            ->setWidth(85)
            ->setOffsetX(290)
            ->setOffsetY(200);
            $lblANORS->getActiveParagraph()
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblANORS->createTextRun("ANORS")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FFF2A541'))->setSize(11);
            $lblANORS->createBreak();
            $lblANORS->createTextRun($doughnutData['anors'] . " Reqs")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FFF2A541'))->setSize(11);
            $lblANORS->createBreak();
            $lblANORS->createTextRun($shippedANORSPct . "%")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FFF2A541'))->setSize(11);
            
            $lblSpare = $slide->createRichTextShape()
            ->setHeight(60)
            ->setWidth(85)
            ->setOffsetX(275)
            ->setOffsetY(280);
            $lblSpare->getActiveParagraph()
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblSpare->createTextRun("Spare")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FF4CAF50'))->setSize(11);
            $lblSpare->createBreak();
            $lblSpare->createTextRun($doughnutData['spare'] . " Reqs")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FF4CAF50'))->setSize(11);
            $lblSpare->createBreak();
            $lblSpare->createTextRun($shippedSparePct . "%")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FF4CAF50'))->setSize(11);
            
            $lblNineNineNine = $slide->createRichTextShape()
            ->setHeight(60)
            ->setWidth(85)
            ->setOffsetX(280)
            ->setOffsetY(350);
            $lblNineNineNine->getActiveParagraph()
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblNineNineNine->createTextRun("999")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FF3B6FB6'))->setSize(11);
            $lblNineNineNine->createBreak();
            $lblNineNineNine->createTextRun($doughnutData['nineNineNine'] . " Reqs")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FF3B6FB6'))->setSize(11);
            $lblNineNineNine->createBreak();
            $lblNineNineNine->createTextRun($shippedNineNineNinePct . "%")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FF3B6FB6'))->setSize(11);
            
            $lblTotalReqsRecvd = $slide->createRichTextShape()
            ->setHeight(80)
            ->setWidth(250)
            ->setOffsetX(30)
            ->setOffsetY(160);
            $lblTotalReqsRecvd->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblTotalReqsRecvd->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FF4472C4'));
            $lblTotalReqsRecvd->getBorder()->setLineStyle(\PhpOffice\PhpPresentation\Style\Border::LINE_SINGLE)->setLineWidth(1.5)->setColor(new Color('FF385D8A'));
            $lblTotalReqsRecvd->createTextRun("Total Reqs Received YTD")->getFont()->setName('Calibri')->setColor(new Color('FFFFFFFF'))->setSize(16);
            $lblTotalReqsRecvd->createBreak();
            $lblTotalReqsRecvd->createTextRun($ytdTotalReqsRecvd)->getFont()->setName('Calibri')->setColor(new Color('FFFFFFFF'))->setSize(24);
            
            $lblUniqueNiins = $slide->createRichTextShape()
            ->setHeight(80)
            ->setWidth(200)
            ->setOffsetX(30)
            ->setOffsetY(260);
            $lblUniqueNiins->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblUniqueNiins->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FF4472C4'));
            $lblUniqueNiins->getBorder()->setLineStyle(\PhpOffice\PhpPresentation\Style\Border::LINE_SINGLE)->setLineWidth(1.5)->setColor(new Color('FF385D8A'));
            $lblUniqueNiins->createTextRun("Unique NIINs YTD")->getFont()->setName('Calibri')->setColor(new Color('FFFFFFFF'))->setSize(16);
            $lblUniqueNiins->createBreak();
            $lblUniqueNiins->createTextRun($ytdUniqueNiins)->getFont()->setName('Calibri')->setColor(new Color('FFFFFFFF'))->setSize(24);
            
            $lblTotalNiins = $slide->createRichTextShape()
            ->setHeight(80)
            ->setWidth(200)
            ->setOffsetX(30)
            ->setOffsetY(360);
            $lblTotalNiins->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblTotalNiins->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FF4472C4'));
            $lblTotalNiins->getBorder()->setLineStyle(\PhpOffice\PhpPresentation\Style\Border::LINE_SINGLE)->setLineWidth(1.5)->setColor(new Color('FF385D8A'));
            $lblTotalNiins->createTextRun("Total NIINs YTD")->getFont()->setName('Calibri')->setColor(new Color('FFFFFFFF'))->setSize(16);
            $lblTotalNiins->createBreak();
            $lblTotalNiins->createTextRun($ytdTotalNiins)->getFont()->setName('Calibri')->setColor(new Color('FFFFFFFF'))->setSize(24);
            
            $lblYTDMetrics = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(400)
            ->setOffsetX(30)
            ->setOffsetY(500);
            $lblYTDMetrics->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $lblYTDMetrics->createTextRun("YTD Metrics (Last 12 Month Avg)")->getFont()->setName('Calibri')->setBold(true)->setUnderline(Font::UNDERLINE_SINGLE)->setColor(new Color('FF000000'))->setSize(14);
            
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