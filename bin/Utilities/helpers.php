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
    
    public static function getCurrentQuarterStart(): string
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
    
    public static function getRollingQuarterStart(int $quartersBack = 5): string
    {
        $start = new DateTime(self::getCurrentQuarterStart());
        $monthsToSubtract = ($quartersBack - 1) * 3;
        $start->modify("-{$monthsToSubtract} months");
        
        return $start->format('Y-m-d');
    }
    
    public static function getCurrentQuarterEnd(): string
    {
        $start = new DateTime(self::getCurrentQuarterStart());
        $start->modify('+3 months');
        $start->modify('-1 day');
        
        return $start->format('Y-m-d');
    }
    
    public static function getNorthSouthFilter(): string
    {
        $value = $_SESSION['north_south_filter'] ?? 'all';
        
        return in_array($value, ['all', 'north', 'south'], true)
        ? $value
        : 'all';
    }
    
    public static function getNorthSouthSql(string $alias = ''): array
    {
        $filter = self::getNorthSouthFilter();
        
        if ($filter === 'all') {
            return [
                'sql' => '',
                'params' => []
            ];
        }
        
        $prefix = $alias !== '' ? $alias . '.' : '';
        
        return [
            'sql' => " AND {$prefix}north_south = ? ",
            'params' => [$filter]
            ];
    }
    
    public static function getFederalHolidays(int $year): array
    {
        $holidays = [];
        
        // Fixed-date holidays, with observed dates
        $fixed = [
            "$year-01-01",
            "$year-07-04",
            "$year-11-11",
            "$year-12-25"
        ];
        
        if ($year >= 2021) {
            $fixed[] = "$year-06-19";
        }
        
        foreach ($fixed as $date) {
            $holiday = new DateTimeImmutable($date);
            $weekday = (int)$holiday->format('N');
            
            if ($weekday === 6) {
                $holiday = $holiday->modify('-1 day');
            } elseif ($weekday === 7) {
                $holiday = $holiday->modify('+1 day');
            }
            
            $holidays[] = $holiday->format('Y-m-d');
        }
        
        // Floating federal holidays
        $holidays[] = (new DateTimeImmutable(
            "third monday of january $year"
            ))->format('Y-m-d');
            
            $holidays[] = (new DateTimeImmutable(
                "third monday of february $year"
                ))->format('Y-m-d');
                
                $holidays[] = (new DateTimeImmutable(
                    "last monday of may $year"
                    ))->format('Y-m-d');
                    
                    $holidays[] = (new DateTimeImmutable(
                        "first monday of september $year"
                        ))->format('Y-m-d');
                        
                        $holidays[] = (new DateTimeImmutable(
                            "second monday of october $year"
                            ))->format('Y-m-d');
                            
                            $holidays[] = (new DateTimeImmutable(
                                "fourth thursday of november $year"
                                ))->format('Y-m-d');
                                
                                return $holidays;
    }
    
    public static function getBusinessDays(string $startDate, string $endDate): int
    {
        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);
        
        if ($end < $start) {
            return 0;
        }
        
        // Include adjacent years to handle observed
        // New Year's Day crossing year boundaries.
        $holidays = [];
        
        for ($year = (int)$start->format('Y') - 1;
        $year <= (int)$end->format('Y') + 1;
        $year++) {
            $holidays = array_merge(
                $holidays,
                self::getFederalHolidays($year)
                );
        }
        
        $holidays = array_flip($holidays);
        $businessDays = 0;
        
        // Exclude start date, include end date
        for ($date = $start->modify('+1 day');
        $date <= $end;
        $date = $date->modify('+1 day')) {
            
            $weekday = (int)$date->format('N');
            $dateString = $date->format('Y-m-d');
            
            if ($weekday <= 5 && !isset($holidays[$dateString])) {
                $businessDays++;
            }
        }
        
        return $businessDays;
    }
    
    public static function getGoalDays(
        ?string $priority,
        ?string $priorityCode
        ): ?int {
            if (strcasecmp(trim($priority ?? ''), 'CASREP') === 0) {
                return 1;
            }
            
            $code = trim($priorityCode ?? '');
            
            if (!preg_match('/^(0[1-9]|1[0-5])$/', $code)) {
                return null;
            }
            
            $code = (int)$code;
            
            if ($code <= 3) {
                return 2;
            }
            
            if ($code <= 8) {
                return 3;
            }
            
            return 8;
    }
    
    public static function calculate(
        ?string $priority,
        ?string $priorityCode,
        ?string $dateRecv,
        ?string $dateShipped
        ): array {
            $goalDays = self::getGoalDays(
                $priority,
                $priorityCode
                );
            
            $responseTime = null;
            $onTime = null;
            
            if (
                !empty($dateRecv) &&
                !empty($dateShipped)
                ) {
                    $received = new DateTimeImmutable($dateRecv);
                    $shipped = new DateTimeImmutable($dateShipped);
                    
                    if ($shipped >= $received) {
                        $responseTime = helpers::getBusinessDays(
                            $dateRecv,
                            $dateShipped
                            );
                    }
                }
                
                if ($responseTime !== null && $goalDays !== null) {
                    $onTime = ($responseTime <= $goalDays) ? 1 : 0;
                }
                
                return [
                    'response_time' => $responseTime,
                    'goal_days' => $goalDays,
                    'on_time' => $onTime
                ];
    }
    
}