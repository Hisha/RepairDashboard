<?php

class ShippedDoughnutChart
{
    /**
     * Build the Chart.js config array for the shipped doughnut chart.
     *
     * @param string $outputPath Full server path to the PNG file that Node will create.
     * @param int|float $fleetFailure Fleet Failure shipped count/value.
     * @param int|float $nineNineNine 999 shipped count/value.
     * @param int|float $spare Spare shipped count/value.
     * @param int|float $anors ANORS shipped count/value.
     * @param int|float $casrep CASREP shipped count/value.
     * @return array
     */
    public static function build(string $outputPath, int|float $fleetFailure, int|float $nineNineNine, int|float $spare, int|float $anors, int|float $casrep): array
    {
        $fleetFailure   = max(0, (float) $fleetFailure);
        $nineNineNine = max(0, (float) $nineNineNine);
        $spare = max(0, (float) $spare);
        $anors = max(0, (float) $anors);
        $casrep = max(0, (float) $casrep);
        
        // Avoid a completely empty pie chart, which can cause odd rendering behavior.
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
            'cutout' => '80%',
            'data' => [
                'labels' => ['Fleet Failure', '999', 'Spare', 'ANORS', 'CASREP'],
                'datasets' => [[
                    'data' => [$fleetFailure, $nineNineNine, $spare, $anors, $casrep],
                    'backgroundColor' => ['#014D4E', '#008000', '#40E0D0', '#32CD32', '#00008B'],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2
                ]]
            ]
        ];
    }
}