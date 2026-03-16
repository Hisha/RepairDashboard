<?php

class BackOrderChart
{
    public static function build(string $outputPath, array $labels, array $data): array
    {
        return [
            'type' => 'bar',
            'width' => 1000,
            'height' => 650,
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
                        'font' => [
                            'size' => 12,
                            'family' => 'Helvetica'
                        ]
                    ]
                ]
            ]
        ];
    }
}