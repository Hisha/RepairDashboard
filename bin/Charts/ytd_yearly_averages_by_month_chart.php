<?php

class YTDYearlyAveragesChart
{
    public static function build(
        string $outputPath,
        array $labels,
        array $boshipped,
        array $casreprt,
        array $noncasreprt,
        array $allrt,
        array $noncasrepgoal,
        array $casrepgoal
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
                            'label' => 'BO Shipped',
                            'data' => $boshipped,
                            'backgroundColor' => '#3B6FB6',
                            'yAxisID' => 'y',
                            'order' => 1
                        ],
                        [
                            'type' => 'line',
                            'label' => 'CASREP RT',
                            'data' => $casreprt,
                            'borderColor' => '#6F42C1',
                            'backgroundColor' => '#6F42C1',
                            'borderWidth' => 3,
                            'pointRadius' => 4,
                            'pointHoverRadius' => 4,
                            'tension' => 0.2,
                            'yAxisID' => 'y1',
                            'order' => 2
                        ],
                        [
                            'type' => 'line',
                            'label' => 'NON CASREP RT',
                            'data' => $noncasreprt,
                            'borderColor' => '#F2A541',
                            'backgroundColor' => '#F2A541',
                            'borderWidth' => 3,
                            'pointRadius' => 4,
                            'pointHoverRadius' => 4,
                            'tension' => 0.2,
                            'yAxisID' => 'y1',
                            'order' => 3
                        ],
                        [
                            'type' => 'line',
                            'label' => 'ALL RT',
                            'data' => $allrt,
                            'borderColor' => '#FFFF00',
                            'backgroundColor' => '#FFFF00',
                            'borderWidth' => 3,
                            'pointRadius' => 4,
                            'pointHoverRadius' => 4,
                            'tension' => 0.2,
                            'yAxisID' => 'y1',
                            'order' => 4
                        ],
                        [
                            'type' => 'line',
                            'label' => 'NON CASREP Goal',
                            'data' => $noncasrepgoal,
                            'borderColor' => '#F2A541',
                            'backgroundColor' => '#F2A541',
                            'borderWidth' => 2,
                            'borderDash' => [8, 6],
                            'pointRadius' => 0,
                            'pointHoverRadius' => 0,
                            'tension' => 0,
                            'yAxisID' => 'y1',
                            'order' => 5
                        ],
                        [
                            'type' => 'line',
                            'label' => 'CASREP Goal',
                            'data' => $casrepgoal,
                            'borderColor' => '#6F42C1',
                            'backgroundColor' => '#6F42C1',
                            'borderWidth' => 2,
                            'borderDash' => [8, 6],
                            'pointRadius' => 0,
                            'pointHoverRadius' => 0,
                            'tension' => 0,
                            'yAxisID' => 'y1',
                            'order' => 6
                        ]
                    ]
                ],
                'scales' => [
                    'x' => [
                        'ticks' => [
                            'font' => [
                                'size' => 14,
                                'family' => 'Helvetica',
                                'weight' => 'bold'
                            ],
                            'color' => '#000000'
                        ]
                    ],
                    'y' => [
                        'beginAtZero' => true,
                        'position' => 'left',
                        'min' => 0,
                        'max' => 7,
                        'ticks' => [
                            'font' => [
                                'size' => 14,
                                'family' => 'Helvetica',
                                'weight' => 'bold'
                            ],
                            'color' => '#000000'
                        ],
                        'title' => [
                            'display' => false,
                            'text' => '',
                            'font' => [
                                'size' => 15,
                                'family' => 'Helvetica',
                                'weight' => 'bold'
                            ],
                            'color' => '#000000'
                        ]
                    ],
                    'y1' => [
                        'beginAtZero' => true,
                        'position' => 'right',
                        'min' => 0,
                        'max' => 80,
                        'ticks' => [
                            'font' => [
                                'size' => 14,
                                'family' => 'Helvetica',
                                'weight' => 'bold'
                            ],
                            'color' => '#000000'
                        ],
                        'grid' => [
                            'drawOnChartArea' => false
                        ],
                        'title' => [
                            'display' => false,
                            'text' => '',
                            'font' => [
                                'size' => 15,
                                'family' => 'Helvetica',
                                'weight' => 'bold'
                            ],
                            'color' => '#000000'
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