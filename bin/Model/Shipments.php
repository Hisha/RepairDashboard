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
    
    
}