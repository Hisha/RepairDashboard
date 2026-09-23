<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class Procurements
{
    public function getProcurements():array
    {
        $db = new db();
        
        $sql = "
        SELECT
            procurements.folder AS 'Folder',
	        procurements.program AS 'Program',
            procurements.request_date AS 'Request Date',
            procurements.niin AS 'NIIN',
            procurements.part AS 'Part',
            procurements.nomen AS 'Nomen',
            procurements.purchase_type AS 'Purchase Type',
            procurements.qty_requested AS 'Qty Requested',
            procurements.requested_by AS 'Requested By',
            procurements.status AS 'Status',
            procurements.purchase_vehicle AS 'Purchase Vehicle',
            procurements.item_cost AS 'Item Cost (each)',
            procurements.extended_cost AS 'Extended Cost',
            procurements.quote_request_date AS 'Quote Request Date',
            procurements.date_submitted AS 'Date Submitted',
            procurements.contract_num AS 'Contract Number',
            procurements.clin_num AS 'Clin Number',
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
    
    public function getBackOrderProcurements():array
    {
        
        $db = new db();
        
        $sql = "
        SELECT
            c.niin AS 'NIIN',
            c.program as 'Program',
            c.support_qty AS 'UCOs',
            c.io_qty AS 'FRWQs',
            COALESCE(i.A_qty, 0) AS 'A On Hand',
            COALESCE(i.D_qty, 0) AS 'D On Hand',
            COALESCE(i.F_qty, 0) AS 'F On Hand',
            COALESCE(i.G_qty, 0) AS 'G On Hand',
            COALESCE(p.requested_qty, 0) AS 'Requested Qty',
            COALESCE(p.on_order_qty, 0) AS 'On Order Qty',
            COALESCE(p.purchase_vehicle, '') AS 'Purchase Vehicle',
            COALESCE(p.contract_info, '') AS 'Contract Info'
        FROM
        (
            SELECT
                niin,
                program,
                SUM(CASE WHEN priority <> 'I.O.' THEN qty ELSE 0 END) AS support_qty,
                SUM(CASE WHEN priority = 'I.O.' THEN qty ELSE 0 END) AS io_qty
            FROM RepairDashboard.cav_requisitions_north
            WHERE status = 'BACKORDERED'
            GROUP BY niin
        ) c
        LEFT JOIN
        (
            SELECT
                niin,
                SUM(qty_requested) AS requested_qty,
                SUM(qty_ordered) AS on_order_qty,
            
                GROUP_CONCAT(
                    DISTINCT CASE
                        WHEN purchase_vehicle <> 'null'
                        THEN purchase_vehicle
                    END
                    ORDER BY purchase_vehicle
                    SEPARATOR ', '
                ) AS purchase_vehicle,
            
                GROUP_CONCAT(
                    DISTINCT CONCAT_WS(
                        ' ',
                        contract_num,
                        clin_num,
                        CONCAT('EDD: ', edd_date)
                    )
                    ORDER BY edd_date
                    SEPARATOR ', '
                ) AS contract_info
            
            FROM RepairDashboard.procurements
            WHERE status NOT IN ('CANCELED', 'COMPLETED')
            GROUP BY niin
        ) p
            ON c.niin = p.niin
        LEFT JOIN
        (
			SELECT 
				niin,
				SUM(CASE WHEN materialcode = 'A' then onhandqty ELSE 0 END) as A_qty,
                SUM(CASE WHEN materialcode = 'D' then onhandqty ELSE 0 END) as D_qty,
                SUM(CASE WHEN materialcode = 'F' AND purposecode <> 'Z' then onhandqty ELSE 0 END) as F_qty,
                SUM(CASE WHEN materialcode = 'G' then onhandqty ELSE 0 END) as G_qty
            FROM RepairDashboard.inventory
            GROUP BY niin
        ) i
			ON c.niin = i.niin;
    ";
        
        $results = $db->query($sql)->fetchAll();
        
        $db->close();
        
        return $results;
        
    }
}
