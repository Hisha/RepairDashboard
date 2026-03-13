<?php

require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/bin/Charts/shipped_piechart.php';
require_once APP_ROOT . '/bin/Charts/shipped_doughnutchart.php';
require_once APP_ROOT . '/bin/Charts/ytd_demand_misses_chart.php';
require_once APP_ROOT . '/bin/Charts/ytd_yearly_averages_by_month_chart.php';
require_once APP_ROOT . '/bin/Presentations/ListBuilder.php';
require_once APP_ROOT . '/bin/Presentations/TableBuilder.php';
require_once APP_ROOT . '/bin/Utilities/ChartRenderer.php';
require_once APP_ROOT . '/bin/Model/SYS_ProgramMapping.php';
require_once APP_ROOT . '/bin/Model/CavRequisitions.php';
require_once APP_ROOT . '/bin/Model/SYS_PowerPointFiller.php';

use PhpOffice\PhpPresentation\IOFactory;
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
            
            $ytdTwoSeventyReqs = $cavRequisitions->getYTDTwoSeventyReqs($selectedProgram, $dateRanges['ytd_start'], $dateRanges['ytd_end']);
            $ytdFillRateGood = (int)$cavRequisitions->getYTDFillRateGood($selectedProgram, $dateRanges['ytd_start'], $dateRanges['ytd_end']);
            $ytdFillRateMissed = (int)$cavRequisitions->getYTDFillRateMissed($selectedProgram, $dateRanges['ytd_start'], $dateRanges['ytd_end']);
            $ytdFillRateTotal = $ytdFillRateGood + $ytdFillRateMissed;
            $ytdFillRate = round(($ytdFillRateGood/ $ytdFillRateTotal) *100,2);
            $ytdCasrepRT = $cavRequisitions->getYTDCasrepRT($selectedProgram, $dateRanges['ytd_start'], $dateRanges['ytd_end']);
            $ytdAllRT = $cavRequisitions->getYTDAllRT($selectedProgram, $dateRanges['ytd_start'], $dateRanges['ytd_end']);
            
            $mthlyNiinChanges = $cavRequisitions->getNiinChangeReqs($selectedProgram, $dateRanges['month_start'], $dateRanges['month_end']);
            $mthlyCanceledReqs = $cavRequisitions->getCanceledReqs($selectedProgram, $dateRanges['month_start'], $dateRanges['month_end']);
            $mthlyPendingReqs = $cavRequisitions->getPendingReqs($selectedProgram, $dateRanges['month_start'], $dateRanges['month_end']);
            $mthlyDISReqs = $cavRequisitions->getDISReqs($selectedProgram, $dateRanges['month_start'], $dateRanges['month_end']);
            $mthlyBackOrderReqs = $cavRequisitions->getBackorderReqs($selectedProgram, $dateRanges['month_start'], $dateRanges['month_end']);
            
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
            $slide2 = $ppt->getSlide(1);
            $slide3 = $ppt->getSlide(2);
            $slide4 = $ppt->getSlide(3);
            $slide5 = $ppt->getSlide(4);
            
            /*********************************************************************************************
             * Slide 1 Filler                                                                            *
             *********************************************************************************************/
            
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
            
            $lblSlide1Title = $slide->createRichTextShape()
            ->setHeight(50)
            ->setWidth(500)
            ->setOffsetX(455)
            ->setOffsetY(30);
            $lblSlide1Title->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblSlide1Title->createTextRun($fillerData['title'])->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(32);
            
            $lblSlide1PM = $slide->createRichTextShape()
            ->setHeight(50)
            ->setWidth(575)
            ->setOffsetX(390)
            ->setOffsetY(80);
            $lblSlide1PM->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblSlide1PM->createTextRun("{$fillerData['pm']}  {$fillerData['programname']}")->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(32);
            
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
            ->setOffsetY(490);
            $lblYTDMetrics->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $lblYTDMetrics->createTextRun("YTD Metrics (Last 12 Month Avg)")->getFont()->setName('Calibri')->setBold(true)->setUnderline(Font::UNDERLINE_SINGLE)->setColor(new Color('FF000000'))->setSize(14);
            
            $lblTwoSeventy = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(400)
            ->setOffsetX(30)
            ->setOffsetY(520);
            $lblTwoSeventy->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $lblTwoSeventy->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FF385D8A'));
            $lblTwoSeventy->createTextRun("Number of Requisitions Exceeding 270 Days")->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(16);
            
            $lblTwoSeventyData = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(80)
            ->setOffsetX(430)
            ->setOffsetY(520);
            $lblTwoSeventyData->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblTwoSeventyData->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FF385D8A'));
            $lblTwoSeventyData->createTextRun($ytdTwoSeventyReqs)->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(14);
            
            $lblFillRate = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(400)
            ->setOffsetX(30)
            ->setOffsetY(550);
            $lblFillRate->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $lblFillRate->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FF4472C4'));
            $lblFillRate->createTextRun("Fill Rate")->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(16);
            
            $lblFillRateData = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(80)
            ->setOffsetX(430)
            ->setOffsetY(550);
            $lblFillRateData->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblFillRateData->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FF4472C4'));
            $lblFillRateData->createTextRun($ytdFillRate . "%")->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(14);
            
            $lblCasrepRT = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(400)
            ->setOffsetX(30)
            ->setOffsetY(580);
            $lblCasrepRT->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $lblCasrepRT->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FF385D8A'));
            $lblCasrepRT->createTextRun("CASREP RT Avg (ACasRT)*")->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(16);
            
            $lblCasrepRTData = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(80)
            ->setOffsetX(430)
            ->setOffsetY(580);
            $lblCasrepRTData->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblCasrepRTData->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FF385D8A'));
            $lblCasrepRTData->createTextRun($ytdCasrepRT)->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(14);
            
            $lblAllRT = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(400)
            ->setOffsetX(30)
            ->setOffsetY(610);
            $lblAllRT->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $lblAllRT->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FF4472C4'));
            $lblAllRT->createTextRun("All RT Avg (AlRT)*")->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(16);
            
            $lblAllRTData = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(80)
            ->setOffsetX(430)
            ->setOffsetY(610);
            $lblAllRTData->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblAllRTData->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FF4472C4'));
            $lblAllRTData->createTextRun($ytdAllRT)->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(16);
            
            $lblMonthlyMetrics = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(400)
            ->setOffsetX(540)
            ->setOffsetY(460);
            $lblMonthlyMetrics->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblMonthlyMetrics->createTextRun("Monthly Metrics Exception Snapshot")->getFont()->setName('Calibri')->setBold(true)->setUnderline(Font::UNDERLINE_SINGLE)->setColor(new Color('FF000000'))->setSize(14);
            
            $lblNiinChange = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(300)
            ->setOffsetX(560)
            ->setOffsetY(490);
            $lblNiinChange->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblNiinChange->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFFFC000'));
            $lblNiinChange->createTextRun("NIIN Changes")->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(16);
            
            $lblNiinChangeData = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(70)
            ->setOffsetX(860)
            ->setOffsetY(490);
            $lblNiinChangeData->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblNiinChangeData->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFFFC000'));
            $lblNiinChangeData->createTextRun($mthlyNiinChanges)->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(16);
            
            $lblCanceledReqs = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(300)
            ->setOffsetX(560)
            ->setOffsetY(520);
            $lblCanceledReqs->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblCanceledReqs->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFFFFF00'));
            $lblCanceledReqs->createTextRun("Canceled Reqs")->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(16);
            
            $lblCanceledReqsData = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(70)
            ->setOffsetX(860)
            ->setOffsetY(520);
            $lblCanceledReqsData->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblCanceledReqsData->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFFFFF00'));
            $lblCanceledReqsData->createTextRun($mthlyCanceledReqs)->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(16);
            
            $lblPendingReqs = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(300)
            ->setOffsetX(560)
            ->setOffsetY(550);
            $lblPendingReqs->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblPendingReqs->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFFFC000'));
            $lblPendingReqs->createTextRun("Pending Reqs")->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(16);
            
            $lblPendingReqsData = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(70)
            ->setOffsetX(860)
            ->setOffsetY(550);
            $lblPendingReqsData->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblPendingReqsData->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFFFC000'));
            $lblPendingReqsData->createTextRun($mthlyPendingReqs)->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(16);
            
            $lblDISReqs = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(300)
            ->setOffsetX(560)
            ->setOffsetY(580);
            $lblDISReqs->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblDISReqs->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFFFFF00'));
            $lblDISReqs->createTextRun("DRMO, I.O, Surge Buy Reqs")->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(16);
            
            $lblDISReqsData = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(70)
            ->setOffsetX(860)
            ->setOffsetY(580);
            $lblDISReqsData->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblDISReqsData->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFFFFF00'));
            $lblDISReqsData->createTextRun($mthlyDISReqs)->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(16);
            
            $lblBackOrderReqs = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(300)
            ->setOffsetX(560)
            ->setOffsetY(610);
            $lblBackOrderReqs->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblBackOrderReqs->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFFFC000'));
            $lblBackOrderReqs->createTextRun("New reqs on Back Order")->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(16);
            
            $lblBackOrderReqsData = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(70)
            ->setOffsetX(860)
            ->setOffsetY(610);
            $lblBackOrderReqsData->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblBackOrderReqsData->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFFFC000'));
            $lblBackOrderReqsData->createTextRun($mthlyBackOrderReqs)->getFont()->setName('Calibri')->setColor(new Color('FF000000'))->setSize(16);
            
            $lblSlide1Disclaimer = $slide->createRichTextShape()
            ->setHeight(30)
            ->setWidth(800)
            ->setOffsetX(80)
            ->setOffsetY(640);
            $lblSlide1Disclaimer->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblSlide1Disclaimer->createTextRun("Metrics do not include Requisitions with: Status of Back Ordered, Canceled, Pending; Priorities of DRMO, I.O, Surge Buy.")->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FF385D8A'))->setSize(10);
            $lblSlide1Disclaimer->createBreak();
            $lblSlide1Disclaimer->createTextRun("*Does not ")->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FF385D8A'))->setSize(10);
            $lblSlide1Disclaimer->createTextRun("include: ")->getFont()->setName('Helvetica')->setBold(true)->setUnderline(Font::UNDERLINE_DOUBLE)->setColor(new Color('FF385D8A'))->setSize(10);
            $lblSlide1Disclaimer->createTextRun("Status of Back Order Shipped")->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FF385D8A'))->setSize(10);
            
            /*********************************************************************************************
             * Slide 1 Filler                                                                            *
             *********************************************************************************************/
            
            /*********************************************************************************************
             * Slide 2 Filler                                                                            *
             *********************************************************************************************/
            
            $ytdData = $cavRequisitions->getYTDDemandMisses(
                $selectedProgram,
                $dateRanges['ytd_start'],
                $dateRanges['ytd_end']
                );
            
            $ytdChartOutput = APP_ROOT . '/reports/tmp/ytd_demand_misses_' . uniqid() . '.png';
            
            $ytdChartConfig = YTDDemandMissesChart::build(
                $ytdChartOutput,
                $ytdData['labels'],
                $ytdData['demand'],
                $ytdData['misses'],
                $ytdData['fillRate'],
                $ytdData['goal']
                );
            
            $ytdChartJsonName = 'ytd_demand_misses_' . uniqid() . '.json';
            $ytdChartPath = $renderer->render($ytdChartConfig, $ytdChartJsonName);
            
            $ytdShape = new File();
            $ytdShape->setName('YTD Demand Misses Fill Rate')
            ->setDescription('YTD demand, misses, fill rate combo chart')
            ->setPath($ytdChartPath)
            ->setWidth(800)
            ->setOffsetX(80)
            ->setOffsetY(150);
            
            $slide2->addShape($ytdShape);
            
            $tableData = [
                'Demand' => $ytdData['demand'],
                'Misses' => $ytdData['misses'],
                'Fill Rate %' => $ytdData['fillRate']
            ];
            
            $labelColors = [
                'Demand' => 'FF3B6FB6',
                'Misses' => 'FFC0392B',
                'Fill Rate %' => 'FF2E8B57'
            ];
            
            TableBuilder::renderMonthlyDataTable(
                $slide2,
                $tableData,
                123,  // xStart
                600,  // yStart
                59,   // colWidth
                20,   // rowHeight
                120,   // labelWidth
                $labelColors
                );
            
            $lblSlide2Title = $slide2->createRichTextShape()
            ->setHeight(50)
            ->setWidth(500)
            ->setOffsetX(455)
            ->setOffsetY(30);
            $lblSlide2Title->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblSlide2Title->createTextRun($fillerData['title'])->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(32);
            
            $lblSlide2PM = $slide2->createRichTextShape()
            ->setHeight(50)
            ->setWidth(575)
            ->setOffsetX(390)
            ->setOffsetY(80);
            $lblSlide2PM->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblSlide2PM->createTextRun("{$fillerData['pm']}  {$fillerData['programname']}")->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(32);
            
            $lblFillRateAvg = $slide2->createRichTextShape()
            ->setHeight(70)
            ->setWidth(160)
            ->setOffsetX(110)
            ->setOffsetY(150);
            $lblFillRateAvg->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblFillRateAvg->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FF2E8B57'));
            $lblFillRateAvg->createTextRun("12 Mnth Avg")->getFont()->setName('Calibri')->setBold(true)->setUnderline(Font::UNDERLINE_SINGLE)->setColor(new Color('FF000000'))->setSize(11);
            $lblFillRateAvg->createBreak();
            $lblFillRateAvg->createTextRun("Fill Rate")->getFont()->setName('Calibri')->setBold(true)->setUnderline(Font::UNDERLINE_SINGLE)->setColor(new Color('FF000000'))->setSize(11);
            $lblFillRateAvg->createBreak();
            $lblFillRateAvg->createTextRun($ytdFillRate . "% (AFR)")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FF000000'))->setSize(18);
            
            $lblAFRGoal = $slide2->createRichTextShape()
            ->setHeight(60)
            ->setWidth(130)
            ->setOffsetX(800)
            ->setOffsetY(190);
            $lblAFRGoal->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblAFRGoal->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFD62728'));
            $lblAFRGoal->createTextRun("Goal")->getFont()->setName('Calibri')->setBold(true)->setUnderline(Font::UNDERLINE_SINGLE)->setColor(new Color('FFFFFFFF'))->setSize(11);
            $lblAFRGoal->createBreak();
            $lblAFRGoal->createTextRun("85% (AFR)")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(18);
            
            $lblSlide2Disclaimer = $slide2->createRichTextShape()
            ->setHeight(30)
            ->setWidth(800)
            ->setOffsetX(80)
            ->setOffsetY(655);
            $lblSlide2Disclaimer->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblSlide2Disclaimer->createTextRun("Metrics do not include Requisitions with: Status of Back Ordered, Canceled, Pending; Priorities of DRMO, I.O, Surge Buy.")->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FF385D8A'))->setSize(10);
            
            /*********************************************************************************************
             * Slide 2 Filler                                                                            *
             *********************************************************************************************/
            
            /*********************************************************************************************
             * Slide 3 Filler                                                                            *
             *********************************************************************************************/
            
            $ytdAverageData = $cavRequisitions->getYTDYearlyAverages(
                $selectedProgram,
                $dateRanges['ytd_start'],
                $dateRanges['ytd_end']
                );
            
            $ytdAverageChartOutput = APP_ROOT . '/reports/tmp/ytd_yearly_averages_by_month_' . uniqid() . '.png';
            
            $ytdAverageChartConfig = YTDYearlyAveragesChart::build(
                $ytdAverageChartOutput,
                $ytdAverageData['labels'],
                $ytdAverageData['boshipped'],
                $ytdAverageData['casreprt'],
                $ytdAverageData['noncasreprt'],
                $ytdAverageData['allrt'],
                $ytdAverageData['noncasrepgoal'],
                $ytdAverageData['casrepgoal']
                );
            
            $ytdAverageChartJsonName = 'ytd_yearly_averages_by_month_' . uniqid() . '.json';
            $ytdAverageChartPath = $renderer->render($ytdAverageChartConfig, $ytdAverageChartJsonName);
            
            $ytdAverageShape = new File();
            $ytdAverageShape->setName('YTD Yearly Averages By Month')
            ->setDescription('YTD yearly averages by month')
            ->setPath($ytdAverageChartPath)
            ->setWidth(725)
            ->setOffsetX(200)
            ->setOffsetY(150);
            
            $slide3->addShape($ytdAverageShape);
            
            $tableAverageData = [
                'UCORT Avg (AUCORT)^' => $ytdAverageData['boshipped'],
                'CASREP RT Avg (ACasRT)*' => $ytdAverageData['casreprt'],
                'RT Avg NON CASREP*' => $ytdAverageData['noncasreprt'],
                'RT Avg All (AlRT)*' => $ytdAverageData['allrt']
            ];
            
            $labelAverageColors = [
                'UCORT Avg (AUCORT)^' => 'FF3B6FB6',
                'CASREP RT Avg (ACasRT)*' => 'FF6F42C1',
                'RT Avg NON CASREP*' => 'FFF2A541',
                'RT Avg All (AlRT)*' => 'FF2E8B57'
            ];
            
            TableBuilder::renderMonthlyDataTable(
                $slide3,
                $tableAverageData,
                220,  // xStart
                560,  // yStart
                57,   // colWidth
                20,   // rowHeight
                195,   // labelWidth
                $labelAverageColors
                );
            
            $lblSlide3Title = $slide3->createRichTextShape()
            ->setHeight(50)
            ->setWidth(500)
            ->setOffsetX(455)
            ->setOffsetY(30);
            $lblSlide3Title->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblSlide3Title->createTextRun($fillerData['title'])->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(32);
            
            $lblSlide3PM = $slide3->createRichTextShape()
            ->setHeight(50)
            ->setWidth(575)
            ->setOffsetX(390)
            ->setOffsetY(80);
            $lblSlide3PM->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblSlide3PM->createTextRun("{$fillerData['pm']}  {$fillerData['programname']}")->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(32);
            
            $lblCasrepGoal = $slide3->createRichTextShape()
            ->setHeight(50)
            ->setWidth(110)
            ->setOffsetX(55)
            ->setOffsetY(475);
            $lblCasrepGoal->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblCasrepGoal->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FF6F42C1'));
            $lblCasrepGoal->createTextRun("CASREP RT")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(11);
            $lblCasrepGoal->createBreak();
            $lblCasrepGoal->createTextRun("Goal < 1 Day")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(11);
            
            $lblNonCasrepGoal = $slide3->createRichTextShape()
            ->setHeight(50)
            ->setWidth(120)
            ->setOffsetX(50)
            ->setOffsetY(360);
            $lblNonCasrepGoal->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblNonCasrepGoal->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFF2A541'));
            $lblNonCasrepGoal->createTextRun("NON CASREP RT")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(11);
            $lblNonCasrepGoal->createBreak();
            $lblNonCasrepGoal->createTextRun("Goal < 3 Day")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(11);
            
            $lblUCOGoal = $slide3->createRichTextShape()
            ->setHeight(50)
            ->setWidth(110)
            ->setOffsetX(700)
            ->setOffsetY(150);
            $lblUCOGoal->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblUCOGoal->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FF3B6FB6'));
            $lblUCOGoal->createTextRun("AUCORT")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(11);
            $lblUCOGoal->createBreak();
            $lblUCOGoal->createTextRun("Goal < 90 Day")->getFont()->setName('Calibri')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(11);            
            
            $lblSlide3Disclaimer = $slide3->createRichTextShape()
            ->setHeight(30)
            ->setWidth(800)
            ->setOffsetX(80)
            ->setOffsetY(640);
            $lblSlide3Disclaimer->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblSlide3Disclaimer->createTextRun("Metrics do not include Requisitions with: Status of Back Ordered, Canceled, Pending; Priorities of DRMO, I.O, Surge Buy.")->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FF385D8A'))->setSize(10);
            $lblSlide3Disclaimer->createBreak();
            $lblSlide3Disclaimer->createTextRun("^Includes Status of Back Order, Pending; *Does not ")->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FF385D8A'))->setSize(10);
            $lblSlide3Disclaimer->createTextRun("include: ")->getFont()->setName('Helvetica')->setBold(true)->setUnderline(Font::UNDERLINE_DOUBLE)->setColor(new Color('FF385D8A'))->setSize(10);
            $lblSlide3Disclaimer->createTextRun("Status of Back Order Shipped")->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FF385D8A'))->setSize(10);
            
            /*********************************************************************************************
             * Slide 3 Filler                                                                            *
             *********************************************************************************************/
            
            /*********************************************************************************************
             * Slide 4 Filler                                                                            *
             *********************************************************************************************/
            
            $lblSlide4Title = $slide4->createRichTextShape()
            ->setHeight(50)
            ->setWidth(500)
            ->setOffsetX(455)
            ->setOffsetY(30);
            $lblSlide4Title->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblSlide4Title->createTextRun($fillerData['title'])->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(32);
            
            $boxPriority = $slide4->createRichTextShape()
            ->setHeight(500)
            ->setWidth(420)
            ->setOffsetX(50)
            ->setOffsetY(145);
            $boxPriority->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFB7DEEB'));
            
            $boxStatus = $slide4->createRichTextShape()
            ->setHeight(500)
            ->setWidth(420)
            ->setOffsetX(495)
            ->setOffsetY(145);
            $boxStatus->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFFECB00'));
            
            $lbltop5PriorityTitle = $slide4->createRichTextShape()
            ->setWidth(420)
            ->setHeight(30)
            ->setOffsetX(80)
            ->setOffsetY(145);
            $lbltop5PriorityTitle->createTextRun('Most Requested Parts by')->getFont()->setName('Helvetica')->setSize(12)->setColor(new Color('FF000000'));
            $lbltop5PriorityTitle->createBreak();
            $lbltop5PriorityTitle->createTextRun('Priority')->getFont()->setName('Helvetica')->setSize(12)->setBold(true)->setColor(new Color('FF000000'));
            
            $lbltop5CasrepTitle = $slide4->createRichTextShape()
            ->setWidth(420)
            ->setHeight(30)
            ->setOffsetX(80)
            ->setOffsetY(190);
            $lbltop5CasrepTitle->createTextRun('CASREPS')->getFont()->setName('Helvetica')->setSize(12)->setBold(true)->setColor(new Color('FF000000'));
            
            $top5Casrep = $cavRequisitions->getTop5ByPriority(
                $selectedProgram,
                $dateRanges['month_start'],
                $dateRanges['month_end'],
                'CASREP'
                );
            
            ListBuilder::renderNiinNomenList(
                $slide4,
                $top5Casrep,
                80,   // x
                210,  // y
                500,  // width
                180,  // height
                'Helvetica',
                12,
                'FF000000',
                false
                );
            
            $lbltop5Anors999Title = $slide4->createRichTextShape()
            ->setWidth(420)
            ->setHeight(30)
            ->setOffsetX(80)
            ->setOffsetY(320);
            $lbltop5Anors999Title->createTextRun('ANORS/999')->getFont()->setName('Helvetica')->setSize(12)->setBold(true)->setColor(new Color('FF000000'));
            
            $top5Anors999 = $cavRequisitions->getTop5ByPriority(
                $selectedProgram,
                $dateRanges['month_start'],
                $dateRanges['month_end'],
                ['999', 'ANORS']
                );
            
            ListBuilder::renderNiinNomenList(
                $slide4,
                $top5Anors999,
                80,   // x
                340,  // y
                500,  // width
                180,  // height
                'Helvetica',
                12,
                'FF000000',
                false,
                Alignment::HORIZONTAL_LEFT
                );
            
            $lbltop5FleetFailureTitle = $slide4->createRichTextShape()
            ->setWidth(420)
            ->setHeight(30)
            ->setOffsetX(80)
            ->setOffsetY(450);
            $lbltop5FleetFailureTitle->createTextRun('Fleet Failure')->getFont()->setName('Helvetica')->setSize(12)->setBold(true)->setColor(new Color('FF000000'));
            
            $top5FleetFailure = $cavRequisitions->getTop5ByPriority(
                $selectedProgram,
                $dateRanges['month_start'],
                $dateRanges['month_end'],
                'Fleet Failure'
                );
            
            ListBuilder::renderNiinNomenList(
                $slide4,
                $top5FleetFailure,
                80,   // x
                470,  // y
                500,  // width
                180,  // height
                'Helvetica',
                12,
                'FF000000',
                false,
                Alignment::HORIZONTAL_LEFT
                );
            
            $lbltop5StatusTitle = $slide4->createRichTextShape()
            ->setWidth(420)
            ->setHeight(30)
            ->setOffsetX(490)
            ->setOffsetY(145);
            $lbltop5StatusTitle->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lbltop5StatusTitle->createTextRun('Most Requested Parts by')->getFont()->setName('Helvetica')->setSize(12)->setColor(new Color('FF000000'));
            $lbltop5StatusTitle->createBreak();
            $lbltop5StatusTitle->createTextRun('Disposition')->getFont()->setName('Helvetica')->setSize(12)->setBold(true)->setColor(new Color('FF000000'));
            
            $lbltop5BackordersTitle = $slide4->createRichTextShape()
            ->setWidth(420)
            ->setHeight(30)
            ->setOffsetX(490)
            ->setOffsetY(190);
            $lbltop5BackordersTitle->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lbltop5BackordersTitle->createTextRun('Backorders')->getFont()->setName('Helvetica')->setSize(12)->setBold(true)->setColor(new Color('FF000000'));
            
            $top5Backorders = $cavRequisitions->getTop5ByStatus(
                $selectedProgram,
                $dateRanges['month_start'],
                $dateRanges['month_end'],
                'BACKORDERED'
                );
            
            ListBuilder::renderNiinNomenList(
                $slide4,
                $top5Backorders,
                410,   // x
                210,  // y
                500,  // width
                180,  // height
                'Helvetica',
                12,
                'FF000000',
                false,
                Alignment::HORIZONTAL_RIGHT
                );
            
            $lblSlide4PM = $slide4->createRichTextShape()
            ->setHeight(50)
            ->setWidth(575)
            ->setOffsetX(390)
            ->setOffsetY(80);
            $lblSlide4PM->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblSlide4PM->createTextRun("{$fillerData['pm']}  {$fillerData['programname']}")->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(32);
            
            $lblSlide4Disclaimer = $slide4->createRichTextShape()
            ->setHeight(30)
            ->setWidth(800)
            ->setOffsetX(80)
            ->setOffsetY(640);
            $lblSlide4Disclaimer->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblSlide4Disclaimer->createTextRun("Metrics do not include Requisitions with: Status of Back Ordered, Canceled, Pending; Priorities of DRMO, I.O, Surge Buy.")->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FF385D8A'))->setSize(10);
            $lblSlide4Disclaimer->createBreak();
            $lblSlide4Disclaimer->createTextRun("^Includes Status of Back Order, Pending ")->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FF385D8A'))->setSize(10);
            
            /*********************************************************************************************
             * Slide 4 Filler                                                                            *
             *********************************************************************************************/
            
            /*********************************************************************************************
             * Slide 5 Filler                                                                            *
             *********************************************************************************************/
            
            $lblSlide5Title = $slide5->createRichTextShape()
            ->setHeight(50)
            ->setWidth(500)
            ->setOffsetX(455)
            ->setOffsetY(30);
            $lblSlide5Title->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblSlide5Title->createTextRun($fillerData['title'])->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(32);
            
            $lblSlide5PM = $slide5->createRichTextShape()
            ->setHeight(50)
            ->setWidth(575)
            ->setOffsetX(390)
            ->setOffsetY(80);
            $lblSlide5PM->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $lblSlide5PM->createTextRun("{$fillerData['pm']}  {$fillerData['programname']}")->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FFFFFFFF'))->setSize(32);
            
            $lblSlide5Disclaimer = $slide5->createRichTextShape()
            ->setHeight(30)
            ->setWidth(800)
            ->setOffsetX(80)
            ->setOffsetY(655);
            $lblSlide5Disclaimer->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lblSlide5Disclaimer->createTextRun("Metrics include Requisitions only with Status of Back Ordered.")->getFont()->setName('Helvetica')->setBold(true)->setColor(new Color('FF385D8A'))->setSize(10);
            
            /*********************************************************************************************
             * Slide 5 Filler                                                                            *
             *********************************************************************************************/
            
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