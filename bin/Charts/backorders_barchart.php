<?php

class BackOrderChart
{
    public static function build(
        string $outputPath,
        array $labels,
        array $data,
        int $height = 550,
        int $fontSize = 12
        ): array {
            return [
                'type' => 'bar',
                'width' => 900,
                'height' => $height,
                'output' => $outputPath,
                'indexAxis' => 'y',
                'legendDisplay' => false,
                'data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'data' => $data,
                        'backgroundColor' => '#3B6FB6'
                    ]]
                ],
                'scales' => [
                    'x' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'font' => [
                                'size' => $fontSize,
                                'family' => 'Helvetica'
                            ]
                        ]
                    ],
                    'y' => [
                        'ticks' => [
                            'autoSkip' => false,
                            'font' => [
                                'size' => $fontSize,
                                'family' => 'Helvetica'
                            ]
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