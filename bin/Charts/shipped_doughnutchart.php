<?php

class ShippedDoughnutChart
{
    public static function build(
        string $outputPath,
        int|float $fleetFailure,
        int|float $nineNineNine,
        int|float $spare,
        int|float $anors,
        int|float $casrep
        ): array {
            $fleetFailure = max(0, (float) $fleetFailure);
            $nineNineNine = max(0, (float) $nineNineNine);
            $spare = max(0, (float) $spare);
            $anors = max(0, (float) $anors);
            $casrep = max(0, (float) $casrep);
            
            if (($fleetFailure + $nineNineNine + $spare + $anors + $casrep) <= 0) {
                $fleetFailure = 1;
                $nineNineNine = 0;
                $spare = 0;
                $anors = 0;
                $casrep = 0;
            }
            
            return [
                'type' => 'doughnut',
                'width' => 900,
                'height' => 650,
                'output' => $outputPath,
                'legendDisplay' => false,
                'data' => [
                    'labels' => ['Fleet Failure', '999', 'Spare', 'ANORS', 'CASREP'],
                    'datasets' => [[
                        'data' => [$fleetFailure, $nineNineNine, $spare, $anors, $casrep],
                        'backgroundColor' => ['#0B6E6E', '#3B6FB6', '#4CAF50', '#F2A541', '#C0392B'],
                        'borderColor' => '#ffffff',
                        'borderWidth' => 0
                    ]]
                ],
                'options' => [
                    'cutout' => '70%',
                    'layout' => [
                        'padding' => 0
                    ]
                ]
            ];
    }
}