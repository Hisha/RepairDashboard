<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class helpers
{
    public static function getFiscalYearFromDate(?string $date = null): int
    {
        $dt = $date ? new DateTime($date) : new DateTime();
        
        $year = (int)$dt->format('Y');
        $month = (int)$dt->format('n');
        
        if ($month >= 10) {
            return $year + 1;
        }
        
        return $year;
    }
    
    public static function getFiscalYearDateRange(?int $fiscalYear = null): array
    {
        if ($fiscalYear === null) {
            $fiscalYear = self::getFiscalYearFromDate();
        }
        
        $startYear = $fiscalYear - 1;
        
        return [
            'fiscal_year' => $fiscalYear,
            'label' => 'FY' . substr((string)$fiscalYear, -2),
            'start_date' => $startYear . '-10-01',
            'end_date' => $fiscalYear . '-09-30'
        ];
    }
    
    function getCurrentQuarterStart(): string
    {
        $today = new DateTime();
        
        $month = (int)$today->format('n');
        $year  = (int)$today->format('Y');
        
        if ($month >= 10) {
            $startMonth = 10;
        } elseif ($month >= 7) {
            $startMonth = 7;
        } elseif ($month >= 4) {
            $startMonth = 4;
        } else {
            $startMonth = 1;
        }
        
        return sprintf('%04d-%02d-01', $year, $startMonth);
    }
    
    function getRollingQuarterStart(int $quartersBack = 5): string
    {
        $start = new DateTime(getCurrentQuarterStart());
        $monthsToSubtract = ($quartersBack - 1) * 3;
        $start->modify("-{$monthsToSubtract} months");
        
        return $start->format('Y-m-d');
    }
    
    function getCurrentQuarterEnd(): string
    {
        $start = new DateTime(getCurrentQuarterStart());
        $start->modify('+3 months');
        $start->modify('-1 day');
        
        return $start->format('Y-m-d');
    }
    
}