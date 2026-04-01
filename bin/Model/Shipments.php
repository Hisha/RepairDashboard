<?php
include_once APP_ROOT . '/bin/Utilities/db.php';
include_once APP_ROOT . '/bin/Utilities/helpers.php';

class Shipments
{
    public function getTop10ShipmentsByCog(string $cog, string $startDate, string $endDate): array
    {
        $db = new db();
        
        $sql = "
    SELECT
        shipments.niin AS 'NIIN',
        SUM(shipments.qty) AS 'QTY'
    FROM shipments
    INNER JOIN LMS21Data
        ON shipments.niin = LMS21Data.niin
    WHERE LMS21Data.cog LIKE ?
        AND shipments.transactiondate BETWEEN ? AND ?
    GROUP BY shipments.niin
    ORDER BY QTY DESC
    LIMIT 10
    ";
        
        // Add wildcard here
        $cogParam = $cog . '%';
        
        $results = $db->query($sql, $cogParam, $startDate, $endDate)->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
    public function getShipmentsListByFiscalYear(?string $niin = null, ?string $cog = null, string $startDate, string $endDate): array
        {
            $db = new db();
            
            $sql = "
    SELECT
        shipments.transactiondate AS 'Ship Date',
        shipments.niin AS 'NIIN',
        shipments.primarypartno AS 'Part',
        shipments.description AS 'Nomen',
        shipments.qty AS 'Qty',
        SYS_repair_program_mapping.normalized_program AS 'Program',
        shipments.materialcode AS 'Condition',
        shipments.issuelocation AS 'Issued To'
    FROM shipments
    INNER JOIN SYS_repair_program_mapping
        ON shipments.subgrouptype = SYS_repair_program_mapping.source_program
    INNER JOIN LMS21Data
        ON shipments.niin = LMS21Data.niin
    WHERE shipments.transactiondate BETWEEN ? AND ?
    ";
            
            $params = [$startDate, $endDate];
            
            // Optional NIIN filter
            if (!empty($niin)) {
                $sql .= " AND shipments.niin = ?";
                $params[] = $niin;
            }
            
            // Optional COG filter (LIKE)
            if (!empty($cog)) {
                $sql .= " AND LMS21Data.cog LIKE ?";
                $params[] = $cog . '%';
            }
            
            $sql .= "
    ORDER BY
        shipments.transactiondate DESC,
        SYS_repair_program_mapping.normalized_program ASC,
        shipments.niin ASC
    ";
            
            $results = $db->query($sql, ...$params)->fetchAll();
            
            $db->close();
            
            return $results;
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
    FROM shipments
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
    
    public function getProgramShipmentSummary(?string $cog = null, string $startDate, string $endDate): array
    {
        $db = new db();
        
        $sql = "
    SELECT
        SYS_repair_program_mapping.normalized_program AS 'Program',
        SUM(shipments.qty) AS 'Total Qty'
    FROM shipments
    INNER JOIN SYS_repair_program_mapping
        ON shipments.subgrouptype = SYS_repair_program_mapping.source_program
    INNER JOIN LMS21Data
        ON shipments.niin = LMS21Data.niin
    WHERE shipments.transactiondate BETWEEN ? AND ?
    ";
        
        $params = [$startDate, $endDate];
        
        if (!empty($cog)) {
            $sql .= " AND LMS21Data.cog LIKE ?";
            $params[] = $cog . '%';
        }
        
        $sql .= "
    GROUP BY SYS_repair_program_mapping.normalized_program
    ORDER BY `Total Qty` DESC, SYS_repair_program_mapping.normalized_program ASC
    ";
        
        $results = $db->query($sql, ...$params)->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
    public function getNiinShipmentAnalysis(?string $program = null, ?string $cog = null, string $startDate, string $endDate): array
    {
        $db = new db();
        
        $sql = "
    SELECT
        shipments.niin AS 'NIIN',
        shipments.primarypartno AS 'Part',
        shipments.description AS 'Nomen',
        SYS_repair_program_mapping.normalized_program AS 'Program',
        SUM(shipments.qty) AS 'Total Qty',
        COUNT(*) AS 'Total Reqs',
        MAX(shipments.transactiondate) AS 'Last Ship Date'
    FROM shipments
    INNER JOIN SYS_repair_program_mapping
        ON shipments.subgrouptype = SYS_repair_program_mapping.source_program
    INNER JOIN LMS21Data
        ON shipments.niin = LMS21Data.niin
    WHERE shipments.transactiondate BETWEEN ? AND ?
    ";
        
        $params = [$startDate, $endDate];
        
        if (!empty($program)) {
            $sql .= " AND SYS_repair_program_mapping.normalized_program = ?";
            $params[] = $program;
        }
        
        if (!empty($cog)) {
            $sql .= " AND LMS21Data.cog LIKE ?";
            $params[] = $cog . '%';
        }
        
        $sql .= "
    GROUP BY
        shipments.niin,
        shipments.primarypartno,
        shipments.description,
        SYS_repair_program_mapping.normalized_program
    ORDER BY `Total Qty` DESC, shipments.niin ASC
    ";
        
        $results = $db->query($sql, ...$params)->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
    public function getDistinctShipmentPrograms(string $startDate, string $endDate): array
    {
        $db = new db();
        
        $sql = "
    SELECT DISTINCT
        SYS_repair_program_mapping.normalized_program AS 'Program'
    FROM shipments
    INNER JOIN SYS_repair_program_mapping
        ON shipments.subgrouptype = SYS_repair_program_mapping.source_program
    WHERE shipments.transactiondate BETWEEN ? AND ?
    ORDER BY SYS_repair_program_mapping.normalized_program ASC
    ";
        
        $results = $db->query($sql, $startDate, $endDate)->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
    public function getLast5QuartersPriorityReport(): array
    {
        $db = new db();
        
        $sql = "
    SELECT
        sh.niin AS NIIN,
        COALESCE(i.`A OnHand`, 0) AS `A OnHand`,
        COALESCE(i.`D OnHand`, 0) AS `D OnHand`,
        COALESCE(i.`G OnHand`, 0) AS `G OnHand`,
        COALESCE(i.`F OnHand`, 0) AS `F OnHand`,
        COALESCE(i.`F Awaiting Vendor`, 0) AS `F Awaiting Vendor`,
        sh.LastShipDate,
        COALESCE(sh.QuarterlyDemand, 0) AS `Quarterly Demand`,
        sh.Program
    FROM
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
            ) AS QuarterlyDemand,
            MAX(rpm.normalized_program) AS Program
        FROM shipments s
        INNER JOIN SYS_repair_program_mapping rpm
            ON s.subgrouptype = rpm.source_program
        CROSS JOIN (
            SELECT
                CASE
                    WHEN MONTH(CURDATE()) BETWEEN 10 AND 12 THEN STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-10-01'), '%Y-%m-%d')
                    WHEN MONTH(CURDATE()) BETWEEN 1 AND 3  THEN STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-01-01'), '%Y-%m-%d')
                    WHEN MONTH(CURDATE()) BETWEEN 4 AND 6  THEN STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-04-01'), '%Y-%m-%d')
                    ELSE STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-07-01'), '%Y-%m-%d')
                END AS current_fq_start
        ) p
        WHERE s.transactiondate >= DATE_SUB(p.current_fq_start, INTERVAL 15 MONTH)
          AND s.transactiondate < p.current_fq_start
        GROUP BY s.niin
    ) sh
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
        ON sh.niin = i.NIIN
    ORDER BY sh.LastShipDate DESC, sh.niin ASC
    ";
        
        $results = $db->query($sql)->fetchAll();
        $db->close();
        
        return $results;
    }
    
}