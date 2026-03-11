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
                        'backgroundColor' => ['#014D4E', '#008000', '#40E0D0', '#32CD32', '#00008B'],
                        'borderColor' => '#ffffff',
                        'borderWidth' => 0
                    ]]
                ],
                'options' => [
                    'cutout' => '80%',
                    'layout' => [
                        'padding' => 0
                    ]
                ]
            ];
    }
}