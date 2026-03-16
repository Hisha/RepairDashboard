<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class BackOrders
{
    
    public function getBackOrderList(): array
    {
        $db = new db();
        
        $sql = "
        SELECT
            (cav_requisitions.date_recv) as 'Date Received',
            (cav_requisitions.program) as 'Program',
            (cav_requisitions.priority) as 'Priority',
            (cav_requisitions.command) as 'Command',
            (cav_requisitions.req_number) as 'Req Number',
            (cav_requisitions.niin) as 'NIIN',
            (cav_requisitions.nomen) as 'Nomen',
            (cav_requisitions.qty) as 'QTY',
            (cav_requisitions.notes) as 'Notes'
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE cav_requisitions.status = 'BACKORDERED'
        ORDER BY cav_requisitions.date_recv ASC
    ";
        
        $results = $db->query($sql)->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
}