<?php
require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/bin/Utilities/ChartRenderer.php';

try {
    $renderer = new ChartRenderer();
    
    $chartConfig = [
        'type' => 'doughnut',
        'title' => 'Current Status',
        'width' => 1000,
        'height' => 700,
        'output' => APP_ROOT . '/chart_renderer/output/php_status_doughnut.png',
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
    
    $pngPath = $renderer->render($chartConfig, 'php_test_doughnut.json');
    
    echo "<h2>Success</h2>";
    echo "<p>Chart created at: {$pngPath}</p>";
    echo "<p><img src='/dashboard/chart_renderer/output/php_status_doughnut.png' style='max-width:700px;'></p>";
    
} catch (Throwable $e) {
    echo "<h2>Error</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}