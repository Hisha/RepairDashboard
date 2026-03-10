<?php

require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/vendor/autoload.php';

use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Style\Alignment;

try {
    
    $template = APP_ROOT . '/templates/MonthlyReqsTemplate.pptx';
    
    $reader = IOFactory::createReader('PowerPoint2007');
    $ppt = $reader->load($template);
    
    $slide = $ppt->getSlide(0);
    
    $text = $slide->createRichTextShape()
    ->setHeight(50)
    ->setWidth(600)
    ->setOffsetX(150)
    ->setOffsetY(200);
    
    $text->getActiveParagraph()
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $text->createTextRun('Template Test Successful')->getFont()->setSize(28);
    
    $output = APP_ROOT . '/reports/tmp/template_test_' . uniqid() . '.pptx';
    
    $writer = IOFactory::createWriter($ppt, 'PowerPoint2007');
    $writer->save($output);
    
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.presentationml.presentation');
    header('Content-Disposition: attachment; filename="Template_Test.pptx"');
    header('Content-Length: ' . filesize($output));
    
    readfile($output);
    exit;
    
} catch (Throwable $e) {
    
    echo "<h2>Error</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>;
}