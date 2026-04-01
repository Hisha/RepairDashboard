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
    
    public function getTechsRepairValueExpanded(string $startDate, string $endDate):array
    {
        $db = new db();
        
        $sql = "
        SELECT
            repairs.technicalpocname AS 'Tech Name',
            repairs.niin AS 'NIIN',
            repairs.materialcode AS 'Condition',
            COUNT(repairs.serialno) AS 'QTY',
            SUM(LMS21Data.std_price) AS 'Total Value'
        FROM repairs
        INNER JOIN LMS21Data
            ON repairs.niin = LMS21Data.niin
        WHERE repairs.transactiondate BETWEEN ? AND ?
        GROUP BY repairs.technicalpocname, repairs.niin, repairs.materialcode 
        ORDER BY repairs.technicalpocname, repairs.niin, repairs.materialcode
    ";
        
        $results = $db->query($sql, $startDate, $endDate)->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
    public function getRepairsByFiscalYear(string $startDate, string $endDate): array
    {
        $db = new db();
        
        $sql = "
    SELECT
        repairs.niin AS 'NIIN',
        SYS_repair_program_mapping.normalized_program AS 'Program',
        LMS21Data.std_price AS 'STD Price',
        SUM(CASE
            WHEN repairs.materialcode = 'A' THEN 1
            ELSE 0
        END) AS 'A Reps',
        SUM(CASE
            WHEN repairs.materialcode = 'A' THEN COALESCE(repairs.Hours, 0)
            ELSE 0
        END) AS 'A Hours',
        SUM(CASE
            WHEN repairs.materialcode = 'D' THEN 1
            ELSE 0
        END) AS 'D Reps',
        SUM(CASE
            WHEN repairs.materialcode = 'D' THEN COALESCE(repairs.Hours, 0)
            ELSE 0
        END) AS 'D Hours',
        SUM(CASE
            WHEN repairs.materialcode = 'F' THEN 1
            ELSE 0
        END) AS 'F Reps',
        SUM(CASE
            WHEN repairs.materialcode = 'F' THEN COALESCE(repairs.Hours, 0)
            ELSE 0
        END) AS 'F Hours',
        SUM(CASE
            WHEN repairs.materialcode = 'G' THEN 1
            ELSE 0
        END) AS 'G Reps',
        SUM(CASE
            WHEN repairs.materialcode = 'G' THEN COALESCE(repairs.Hours, 0)
            ELSE 0
        END) AS 'G Hours',
        SUM(CASE
            WHEN repairs.materialcode = 'H' THEN 1
            ELSE 0
        END) AS 'H Reps',
        SUM(CASE
            WHEN repairs.materialcode = 'H' THEN COALESCE(repairs.Hours, 0)
            ELSE 0
        END) AS 'H Hours',
        SUM(CASE
            WHEN repairs.materialcode = 'A' THEN LMS21Data.std_price
            ELSE 0
        END) AS 'Total Value'
    FROM repairs
    INNER JOIN LMS21Data
        ON repairs.niin = LMS21Data.niin
    INNER JOIN SYS_repair_program_mapping
        ON repairs.subgrouptype = SYS_repair_program_mapping.source_program
    WHERE repairs.transactiondate BETWEEN ? AND ?
    GROUP BY repairs.niin, SYS_repair_program_mapping.normalized_program, LMS21Data.std_price
    ORDER BY SYS_repair_program_mapping.normalized_program, repairs.niin
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
    
    public function getRepairPriorityReport(string $startDate, string $endDate): array
    {
        $db = new db();
        
        $sql = "
    SELECT
        l.niin AS NIIN,
        COALESCE(i.`A OnHand`, 0) AS `A OnHand`,
        COALESCE(i.`D OnHand`, 0) AS `D OnHand`,
        COALESCE(i.`G OnHand`, 0) AS `G OnHand`,
        COALESCE(i.`F OnHand`, 0) AS `F OnHand`,
        COALESCE(i.`F Awaiting Vendor`, 0) AS `F Awaiting Vendor`,
        sh.LastShipDate,
        COALESCE(sh.QuarterlyDemand, 0) AS `Quarterly Demand`,
        l.lrc AS Program
    FROM LMS21Data l
    LEFT JOIN
    (
        SELECT
            inventory.niin AS NIIN,
            SUM(CASE
                WHEN inventory.materialcode = 'A' THEN inventory.onhandqty
                ELSE 0
            END) AS `A OnHand`,
            SUM(CASE
                WHEN inventory.materialcode = 'D' THEN inventory.onhandqty
                ELSE 0
            END) AS `D OnHand`,
            SUM(CASE
                WHEN inventory.materialcode = 'G' THEN inventory.onhandqty
                ELSE 0
            END) AS `G OnHand`,
            SUM(CASE
                WHEN inventory.materialcode = 'F' AND inventory.purposecode <> 'Z' THEN inventory.onhandqty
                ELSE 0
            END) AS `F OnHand`,
            SUM(CASE
                WHEN inventory.materialcode = 'F' AND inventory.purposecode = 'Z' THEN inventory.onhandqty
                ELSE 0
            END) AS `F Awaiting Vendor`
        FROM inventory
        GROUP BY inventory.niin
    ) i
        ON l.niin = i.NIIN
    LEFT JOIN
    (
        SELECT
            s.niin,
            MAX(s.transactiondate) AS LastShipDate,
            ROUND(
                SUM(
                    CASE
                        WHEN s.transactiondate >= DATE_SUB(p.current_fq_start, INTERVAL 15 MONTH)
                         AND s.transactiondate < p.current_fq_start
                        THEN s.qty
                        ELSE 0
                    END
                ) / 5,
                2
            ) AS QuarterlyDemand
        FROM shipments s
        CROSS JOIN (
            SELECT
                CASE
                    WHEN MONTH(CURDATE()) BETWEEN 10 AND 12 THEN STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-10-01'), '%Y-%m-%d')
                    WHEN MONTH(CURDATE()) BETWEEN 1 AND 3  THEN STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-01-01'), '%Y-%m-%d')
                    WHEN MONTH(CURDATE()) BETWEEN 4 AND 6  THEN STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-04-01'), '%Y-%m-%d')
                    ELSE STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-07-01'), '%Y-%m-%d')
                END AS current_fq_start
        ) p
        GROUP BY s.niin
    ) sh
        ON l.niin = sh.niin
    WHERE l.cog LIKE '7%'
    ORDER BY sh.LastShipDate DESC, l.niin ASC
    ";
        
        $results = $db->query($sql)->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
    public function getRepairsByMonthAndSubgroup(string $startDate, string $endDate): array
    {
        $db = new db();
        
        $sql = "
        SELECT
            DATE_FORMAT(transactiondate, '%M %Y') AS MonthYear,
            DATE_FORMAT(transactiondate, '%Y-%m') AS MonthSort,
            SYS_repair_program_mapping.normalized_program AS SUBGROUPTYPE,
            niin AS NIIN,
            COUNT(*) AS Qty
        FROM repairs
        INNER JOIN SYS_repair_program_mapping
            ON repairs.subgrouptype = SYS_repair_program_mapping.source_program
        WHERE transactiondate BETWEEN ? AND ?
        GROUP BY
            DATE_FORMAT(transactiondate, '%Y-%m'),
            DATE_FORMAT(transactiondate, '%M %Y'),
            subgrouptype,
            niin
        ORDER BY
            MonthSort DESC,
            subgrouptype ASC,
            niin ASC
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