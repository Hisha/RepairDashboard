<?php

require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/bin/Charts/shipped_piechart.php';
require_once APP_ROOT . '/bin/Utilities/ChartRenderer.php';

try {
    $renderer = new ChartRenderer();
    
    $shipped = 120;
    $shippedBO = 35;
    
    $config = ShippedPieChart::build(
        APP_ROOT . '/chart_renderer/output/shipped_pie_test.png',
        $shipped,
        $shippedBO
        );
    
    $pngPath = $renderer->render($config, 'shipped_pie_test.json');
    
    echo '<h2>Success</h2>';
    echo '<p>Chart created at: ' . htmlspecialchars($pngPath) . '</p>';
    echo '<img src="/dashboard/chart_renderer/output/shipped_pie_test.png" style="max-width:700px;">';
    
} catch (Throwable $e) {
    echo '<h2>Error</h2>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}