<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class BackOrders
{
    
    public function getBackOrderList(?string $priority = null): array
    {
        $db = new db();
        
        $sql = "
        SELECT
            cav_requisitions.date_recv AS 'Date Received',
            cav_requisitions.program AS 'Program',
            cav_requisitions.priority AS 'Priority',
            cav_requisitions.command AS 'Command',
            cav_requisitions.req_number AS 'Req Number',
            cav_requisitions.niin AS 'NIIN',
            cav_requisitions.nomen AS 'Nomen',
            cav_requisitions.qty AS 'QTY',
            cav_requisitions.notes AS 'Notes'
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE cav_requisitions.status = 'BACKORDERED'
    ";
        
        $params = [];
        
        if (!empty($priority)) {
            $sql .= " AND cav_requisitions.priority = ?";
            $params[] = $priority;
        }
        
        $sql .= " ORDER BY cav_requisitions.date_recv ASC";
        
        $results = $db->query($sql, ...$params)->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
    public function fillBackOrdersPieChart(): array
    {
        $db = new db();
        
        $sql = "
        SELECT
            cav_requisitions.priority AS 'Priority',
            COUNT(*) AS 'Qty'
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE cav_requisitions.status = 'BACKORDERED'
        GROUP BY cav_requisitions.priority
        ORDER BY cav_requisitions.priority
    ";
        
        $results = $db->query($sql)->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
}