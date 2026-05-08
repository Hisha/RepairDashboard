<?php
include_once APP_ROOT . '/bin/Utilities/db.php';
include_once APP_ROOT . '/bin/Utilities/helpers.php';

class MonthlyReportableNumbers
{
    public function getAvailableMonths(): array
    {
        $db = new db();
        
        $sql = "
            SELECT
                month_value,
                month_label,
                MAX(sort_date) AS sort_date
            FROM (
                SELECT
                    DATE_FORMAT(transactiondate, '%Y-%m') AS month_value,
                    DATE_FORMAT(transactiondate, '%M %Y') AS month_label,
                    transactiondate AS sort_date
                FROM shipments
            
                UNION ALL
            
                SELECT
                    DATE_FORMAT(transactiondate, '%Y-%m') AS month_value,
                    DATE_FORMAT(transactiondate, '%M %Y') AS month_label,
                    transactiondate AS sort_date
                FROM receipts
            
                UNION ALL
            
                SELECT
                    DATE_FORMAT(date_recv, '%Y-%m') AS month_value,
                    DATE_FORMAT(date_recv, '%M %Y') AS month_label,
                    date_recv AS sort_date
                FROM cav_requisitions
            ) x
            GROUP BY month_value, month_label
            ORDER BY sort_date DESC
        ";
        
        $results = $db->query($sql)->fetchAll();
        $db->close();
        
        return $results;
    }
    
    public function getMonthlyReportableNumbers(string $selectedMonth): array
    {
        $db = new db();
        
        $repairLocationFilter = helpers::getNorthSouthSql('rpm');
        $programLocationFilter = helpers::getNorthSouthSql('spm');
        
        $sql = "
        SELECT
            programs.normalized_program AS 'Program',
            COALESCE(s.shipment_count, 0) AS 'Shipment Count',
            COALESCE(s.shipped_qty, 0) AS 'Shipped Qty',
            COALESCE(r.receipt_count, 0) AS 'Receipt Count',
            COALESCE(r.received_qty, 0) AS 'Receipt Qty',
            COALESCE(c.canceled_reqs, 0) AS 'Canceled Reqs'
        FROM (
            SELECT normalized_program FROM (
                SELECT DISTINCT rpm.normalized_program
                FROM shipments sh
                INNER JOIN SYS_repair_program_mapping rpm
                    ON sh.subgrouptype = rpm.source_program
                WHERE DATE_FORMAT(sh.transactiondate, '%Y-%m') = ?
                {$repairLocationFilter['sql']}
                
                UNION
                
                SELECT DISTINCT rpm.normalized_program
                FROM receipts rc
                INNER JOIN SYS_repair_program_mapping rpm
                    ON rc.subgrouptype = rpm.source_program
                WHERE DATE_FORMAT(rc.transactiondate, '%Y-%m') = ?
                {$repairLocationFilter['sql']}
                
                UNION
                
                SELECT DISTINCT spm.normalized_program
                FROM cav_requisitions cr
                INNER JOIN SYS_program_mapping spm
                    ON cr.program = spm.source_program
                WHERE DATE_FORMAT(cr.date_recv, '%Y-%m') = ?
                {$programLocationFilter['sql']}
            ) p
        ) programs
        
        LEFT JOIN (
            SELECT
                rpm.normalized_program,
                COUNT(DISTINCT sh.docno) AS shipment_count,
                SUM(sh.qty) AS shipped_qty
            FROM shipments sh
            INNER JOIN SYS_repair_program_mapping rpm
                ON sh.subgrouptype = rpm.source_program
            WHERE DATE_FORMAT(sh.transactiondate, '%Y-%m') = ?
            {$repairLocationFilter['sql']}
            GROUP BY rpm.normalized_program
        ) s ON programs.normalized_program = s.normalized_program
        
        LEFT JOIN (
            SELECT
                rpm.normalized_program,
                COUNT(*) AS receipt_count,
                SUM(rc.qty) AS received_qty
            FROM receipts rc
            INNER JOIN SYS_repair_program_mapping rpm
                ON rc.subgrouptype = rpm.source_program
            WHERE DATE_FORMAT(rc.transactiondate, '%Y-%m') = ?
            {$repairLocationFilter['sql']}
            GROUP BY rpm.normalized_program
        ) r ON programs.normalized_program = r.normalized_program
        
        LEFT JOIN (
            SELECT
                spm.normalized_program,
                COUNT(*) AS canceled_reqs
            FROM cav_requisitions cr
            INNER JOIN SYS_program_mapping spm
                ON cr.program = spm.source_program
            WHERE DATE_FORMAT(cr.date_recv, '%Y-%m') = ?
              AND UPPER(cr.status) = 'CANCELED'
              {$programLocationFilter['sql']}
            GROUP BY spm.normalized_program
        ) c ON programs.normalized_program = c.normalized_program
        
        ORDER BY programs.normalized_program
    ";
              
              $params = array_merge(
                  [$selectedMonth],
                  $repairLocationFilter['params'],
                  
                  [$selectedMonth],
                  $repairLocationFilter['params'],
                  
                  [$selectedMonth],
                  $programLocationFilter['params'],
                  
                  [$selectedMonth],
                  $repairLocationFilter['params'],
                  
                  [$selectedMonth],
                  $repairLocationFilter['params'],
                  
                  [$selectedMonth],
                  $programLocationFilter['params']
                  );
              
              $results = $db->query($sql, ...$params)->fetchAll();
              
              $db->close();
              
              return $results;
    }
}