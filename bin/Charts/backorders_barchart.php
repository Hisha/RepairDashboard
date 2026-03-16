<?php

class BackOrderChart
{
    public static function build(string $outputPath, array $labels, array $data, int $height = 550): array
    {
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
                    'beginAtZero' => true
                ],
                'y' => [
                    'ticks' => [
                        'autoSkip' => false,
                        'font' => [
                            'size' => 12,
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