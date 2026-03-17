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
}