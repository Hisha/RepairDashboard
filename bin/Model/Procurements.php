<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class Procurements
{
    public function getProcurements():array
    {
        $db = new db();
        
        $sql = "
        SELECT 
	       procurements.program AS 'Program',
            procurements.request_date AS 'Request Date',
            procurements.niin AS 'NIIN',
            procurements.part AS 'Part',
            procurements.nomen AS 'Nomen',
            procurements.qty_requested AS 'Qty Requested',
            procurements.requested_by AS 'Requested By',
            procurements.purchase_vehicle AS 'Purchase Vehicle',
            procurements.item_cost AS 'Item Cost (each)',
            procurements.status AS 'Status',
            procurements.date_submitted AS 'Date Submitted',
            procurements.contract_num AS 'Contract Number',
            procurements.quote_num AS 'Quote Number',
            procurements.po_num AS 'PO Number',
            procurements.qty_ordered AS 'Qty Ordered',
            procurements.award_date AS 'Award Date',
            procurements.edd_date AS 'EDD Date',
            procurements.receive_date AS 'Receive Date',
            procurements.comments AS 'Comments'
        FROM procurements
        ORDER BY procurements.request_date DESC
    ";
        
        $results = $db->query($sql)->fetchAll();
        
        $db->close();
        
        return $results;
    }
}