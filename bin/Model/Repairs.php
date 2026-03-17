<?php
include_once APP_ROOT . '/bin/Utilities/db.php';
include_once APP_ROOT . '/bin/Utilities/helpers.php';

class Repairs
{
    public function getTechsRepairValue(string $startDate, string $endDate):array
    {
        $db = new db();
        
        $sql = "
        SELECT
            repairs.technicalpocname,
            SUM(LMS21Data.std_price) AS Value
        FROM repairs
        INNER JOIN LMS21Data
            ON repairs.niin = LMS21Data.niin
        WHERE repairs.materialcode = 'A'
            AND repairs.transactiondate BETWEEN ? AND ?
        GROUP BY repairs.technicalpocname
        ORDER BY repairs.technicalpocname
    ";
        
        $results = $db->query($sql, $startDate, $endDate)->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
    public function getRepairedDollarValue(string $startDate, string $endDate):array
    {
        $db = new db();
        
        $sql = "
        SELECT
            SYS_repair_program_mapping.normalized_program,
            SUM(LMS21Data.std_price) AS Value
        FROM repairs
        INNER JOIN LMS21Data
            ON repairs.niin = LMS21Data.niin
        INNER JOIN SYS_repair_program_mapping
            ON repairs.subgrouptype = SYS_repair_program_mapping.source_program
        WHERE repairs.materialcode = 'A'
            AND repairs.transactiondate BETWEEN ? AND ?
        GROUP BY SYS_repair_program_mapping.normalized_program
        ORDER BY SYS_repair_program_mapping.normalized_program
    ";
        
        $results = $db->query($sql, $startDate, $endDate)->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
    public function getAvailableFiscalYears(): array
    {
        $db = new db();
        
        $sql = "
        SELECT DISTINCT
            CASE
                WHEN MONTH(transactiondate) >= 10 THEN YEAR(transactiondate) + 1
                ELSE YEAR(transactiondate)
            END AS fiscal_year
        FROM repairs
        WHERE transactiondate IS NOT NULL
        ORDER BY fiscal_year DESC
    ";
        
        $results = $db->query($sql)->fetchAll();
        
        $db->close();
        
        $fiscalYears = [];
        
        foreach ($results as $row) {
            $fiscalYears[] = 'FY' . substr((string)$row['fiscal_year'], -2);
        }
        
        return $fiscalYears;
    }
    
    public function getAvailableFiscalYearsDetailed(): array
    {
        $db = new db();
        
        $sql = "
        SELECT DISTINCT
            CASE
                WHEN MONTH(transactiondate) >= 10 THEN YEAR(transactiondate) + 1
                ELSE YEAR(transactiondate)
            END AS fiscal_year
        FROM repairs
        WHERE transactiondate IS NOT NULL
        ORDER BY fiscal_year DESC
    ";
        
        $results = $db->query($sql)->fetchAll();
        
        $db->close();
        
        $fiscalYears = [];
        
        foreach ($results as $row) {
            $range = helpers::getFiscalYearDateRange((int)$row['fiscal_year']);
            
            $fiscalYears[] = [
                'fiscal_year' => $range['fiscal_year'],
                'label' => $range['label'],
                'start_date' => $range['start_date'],
                'end_date' => $range['end_date']
            ];
        }
        
        return $fiscalYears;
    }
    
    public function getTechsRepairValueByFiscalYear(?int $fiscalYear = null): array
    {
        $range = helpers::getFiscalYearDateRange($fiscalYear);
        
        return $this->getTechsRepairValue($range['start_date'], $range['end_date']);
    }
    
    public function getRepairedDollarValueByFiscalYear(?int $fiscalYear = null): array
    {
        $range = helpers::getFiscalYearDateRange($fiscalYear);
        
        return $this->getRepairedDollarValue($range['start_date'], $range['end_date']);
    }
}