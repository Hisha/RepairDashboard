<?php

class ShippedPieChart
{
    /**
     * Build the Chart.js config array for the shipped pie chart.
     *
     * @param string $outputPath Full server path to the PNG file that Node will create.
     * @param int|float $shipped Regular shipped count/value.
     * @param int|float $shippedBO Shipped backorder count/value.
     * @return array
     */
    public static function build(string $outputPath, int|float $shipped, int|float $shippedBO): array
    {
        $shipped   = max(0, (float) $shipped);
        $shippedBO = max(0, (float) $shippedBO);
        
        // Avoid a completely empty pie chart, which can cause odd rendering behavior.
        if (($shipped + $shippedBO) <= 0) {
            $shipped = 1;
            $shippedBO = 0;
        }
        
        return [
            'type' => 'pie',
            'title' => 'Shipped vs Shipped BO',
            'width' => 900,
            'height' => 650,
            'output' => $outputPath,
            'legendDisplay' => true,
            'legendPosition' => 'right',
            'data' => [
                'labels' => ['Shipped', 'Shipped BO'],
                'datasets' => [[
                    'data' => [$shipped, $shippedBO],
                    'backgroundColor' => ['#4e79a7', '#e15759'],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2
                ]]
            ]
        ];
    }
}