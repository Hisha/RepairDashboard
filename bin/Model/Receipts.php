<?php
include_once APP_ROOT . '/bin/Utilities/db.php';
include_once APP_ROOT . '/bin/Utilities/helpers.php';

class Receipts
{
    
    private function getRepairLocationFilter(): array
    {
        return helpers::getNorthSouthSql('SYS_repair_program_mapping');
    }
    
    private function getLrcLocationFilter(): array
    {
        $filter = helpers::getNorthSouthFilter();
        
        if ($filter === 'all') {
            return ['sql' => '', 'params' => []];
        }
        
        return [
            'sql' => "
            AND EXISTS (
                SELECT 1
                FROM SYS_repair_program_mapping rpm_filter
                WHERE rpm_filter.normalized_program = l.lrc
                  AND rpm_filter.north_south = ?
            )
        ",
            'params' => [$filter]
        ];
    }
    
    public function getReceiptsByDateRange(string $startDate, string $endDate): array
    {
        $db = new db();
        
        $locationFilter = $this->getRepairLocationFilter();
        
        $sql = "
    SELECT
    	SYS_repair_program_mapping.normalized_program AS 'Program',
        receipts.materialcode AS 'Condition',
        SUM(receipts.qty) AS 'Received'
    FROM receipts
    INNER JOIN SYS_repair_program_mapping
        ON receipts.subgrouptype = SYS_repair_program_mapping.source_program
    WHERE receipts.transactiondate BETWEEN ? AND ?
    {$locationFilter['sql']}
    GROUP BY SYS_repair_program_mapping.normalized_program, receipts.materialcode
    ORDER BY SYS_repair_program_mapping.normalized_program, receipts.materialcode
    ";
    
        $results = $db->query($sql, $startDate, $endDate, ...$locationFilter['params'])->fetchAll();
    
        $db->close();
    
        return $results;
    }
}
