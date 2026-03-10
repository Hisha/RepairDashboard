<?php

require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/bin/Utilities/ChartRenderer.php';

use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Shape\Drawing\File;

try {
    
    $renderer = new ChartRenderer();
    
    // Generate chart via Node
    $chartConfig = [
        'type' => 'doughnut',
        'title' => 'Current Status',
        'width' => 1000,
        'height' => 700,
        'output' => APP_ROOT . '/chart_renderer/output/ppt_status_chart.png',
        'legendDisplay' => true,
        'legendPosition' => 'right',
        'data' => [
            'labels' => ['Shipped', 'Pending', 'Cancelled'],
            'datasets' => [[
                'data' => [120, 55, 10],
                'backgroundColor' => ['#4e79a7', '#f28e2b', '#e15759'],
                'borderColor' => '#ffffff',
                'borderWidth' => 2
            ]]
        ]
    ];
    
    $chartPath = $renderer->render($chartConfig, 'ppt_chart_test.json');
    
    // Create PowerPoint
    $ppt = new PhpPresentation();
    
    $slide = $ppt->getActiveSlide();
    
    // Title
    $title = $slide->createRichTextShape()
    ->setHeight(50)
    ->setWidth(800)
    ->setOffsetX(50)
    ->setOffsetY(20);
    
    $title->getActiveParagraph()->getAlignment()->setHorizontal('center');
    $title->createTextRun('Monthly Requisition Status')->getFont()->setSize(28);
    
    // Insert chart image
    $shape = new File();
    $shape->setPath($chartPath)
    ->setWidth(700)
    ->setOffsetX(150)
    ->setOffsetY(120);
    
    $slide->addShape($shape);
    
    // Save presentation
    $writer = IOFactory::createWriter($ppt, 'PowerPoint2007');
    
    $file = APP_ROOT . '/chart_renderer/output/test_report.pptx';
    
    $writer->save($file);
    
    // Force download
    header("Content-Type: application/vnd.openxmlformats-officedocument.presentationml.presentation");
    header("Content-Disposition: attachment; filename=Test_Report.pptx");
    readfile($file);
    
} catch (Throwable $e) {
    
    echo "<h2>Error</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}