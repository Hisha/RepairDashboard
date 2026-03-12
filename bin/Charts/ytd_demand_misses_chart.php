<?php

class YTDDemandMissesChart
{
    public static function build(
        string $outputPath,
        array $labels,
        array $demand,
        array $misses,
        array $fillRate,
        array $goal
        ): array {
            return [
                'type' => 'bar',
                'width' => 1200,
                'height' => 700,
                'output' => $outputPath,
                'legendDisplay' => false,
                'data' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'type' => 'bar',
                            'label' => 'Demand',
                            'data' => $demand,
                            'backgroundColor' => '#3B6FB6',
                            'yAxisID' => 'y'
                        ],
                        [
                            'type' => 'bar',
                            'label' => 'Misses',
                            'data' => $misses,
                            'backgroundColor' => '#C0392B',
                            'yAxisID' => 'y'
                        ],
                        [
                            'type' => 'line',
                            'label' => 'Fill Rate %',
                            'data' => $fillRate,
                            'borderColor' => '#2E8B57',
                            'backgroundColor' => '#2E8B57',
                            'borderWidth' => 3,
                            'pointRadius' => 4,
                            'pointHoverRadius' => 4,
                            'tension' => 0.2,
                            'yAxisID' => 'y1'
                        ],
                        [
                            'type' => 'line',
                            'label' => 'Fill Rate Goal %',
                            'data' => $goal,
                            'borderColor' => '#D62728',
                            'backgroundColor' => '#D62728',
                            'borderWidth' => 2,
                            'borderDash' => [8, 6],
                            'pointRadius' => 0,
                            'pointHoverRadius' => 0,
                            'tension' => 0,
                            'yAxisID' => 'y1'
                        ]
                    ]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'position' => 'left',
                        'min' => 0,
                        'max' => 500,
                        'title' => [
                            'display' => true,
                            'text' => 'Demand and Misses'
                        ]
                    ],
                    'y1' => [
                        'beginAtZero' => true,
                        'position' => 'right',
                        'min' => 0,
                        'max' => 100,
                        'grid' => [
                            'drawOnChartArea' => false
                        ],
                        'title' => [
                            'display' => true,
                            'text' => 'Fill Rate and Fill Rate Goal Percentage'
                        ]
                    ]
                ],
                'options' => [
                    'layout' => [
                        'padding' => 10
                    ]
                ]
            ];
    }
}