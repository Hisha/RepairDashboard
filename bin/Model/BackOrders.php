<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class BackOrders
{
    
    public function getBackOrderList(): array
    {
        $db = new db();
        
        $sql = "
        SELECT
            (date_recv) as 'Date Received',
            (program) as 'Program',
            (priority) as 'Priority',
            (command) as 'Command',
            (req_number) as 'Req Number',
            (niin) as 'NIIN',
            (nomen) as 'Nomen',
            (qty) as 'QTY',
            (notes) as 'Notes'
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE cav_requisitions.status = 'BACKORDERED'
        ORDER BY date_recv ASC
    ";
        
        $results = $db->query($sql)->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
}