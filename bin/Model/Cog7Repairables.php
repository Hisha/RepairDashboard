<?php
require_once APP_ROOT . '/bin/Utilities/db.php';

class Cog7Repairables
{
    public function getSummary12M()
    {
        $db = new db();
        
        $sql = "
            SELECT
                l.niin,
                l.nomen,
            
                COALESCE(s.ship_qty_12m, 0) AS ship_qty_12m,
                COALESCE(r.receipt_qty_12m, 0) AS receipt_qty_12m,
                COALESCE(p.repair_qty_12m, 0) AS repair_qty_12m,
            
                COALESCE(f.approx_fielded_base, 0) AS fielded_base,
            
                CASE
                    WHEN COALESCE(f.approx_fielded_base, 0) > 0
                    THEN COALESCE(r.receipt_qty_12m, 0) / f.approx_fielded_base
                    ELSE NULL
                END AS return_rate,
            
                CASE
                    WHEN COALESCE(r.receipt_qty_12m, 0) > 0
                    THEN COALESCE(p.repair_qty_12m, 0) / r.receipt_qty_12m
                    ELSE NULL
                END AS repair_rate,
            
                (COALESCE(r.receipt_qty_12m, 0) - COALESCE(p.repair_qty_12m, 0)) AS pipeline_delta,
            
                COALESCE(inv.on_hand, 0) AS on_hand,
            
                s.last_ship_date
            
            FROM LMS21Data l
            
            INNER JOIN (
                SELECT DISTINCT s.niin
                FROM shipments s
                INNER JOIN LMS21Data l2
                    ON s.niin = l2.niin
                WHERE l2.cog LIKE '7%'
                  AND s.transactiondate >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)
            ) active
                ON l.niin = active.niin
            
            LEFT JOIN (
                SELECT
                    s.niin,
                    SUM(s.qty) AS ship_qty_12m,
                    MAX(s.transactiondate) AS last_ship_date
                FROM shipments s
                WHERE s.transactiondate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY s.niin
            ) s
                ON l.niin = s.niin
            
            LEFT JOIN (
                SELECT
                    r.niin,
                    SUM(r.qty) AS receipt_qty_12m
                FROM receipts r
                WHERE r.transactiondate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY r.niin
            ) r
                ON l.niin = r.niin
            
            LEFT JOIN (
                SELECT
                    p.niin,
                    COUNT(*) AS repair_qty_12m
                FROM repairs p
                WHERE p.transactiondate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY p.niin
            ) p
                ON l.niin = p.niin
            
            LEFT JOIN (
                SELECT
                    s.niin,
                    SUM(s.qty) - COALESCE(rc.total_receipts, 0) AS approx_fielded_base
                FROM shipments s
                LEFT JOIN (
                    SELECT
                        niin,
                        SUM(qty) AS total_receipts
                    FROM receipts
                    GROUP BY niin
                ) rc
                    ON s.niin = rc.niin
                GROUP BY s.niin
            ) f
                ON l.niin = f.niin
            
            LEFT JOIN (
                SELECT
                    i.niin,
                    SUM(i.onhandqty) AS on_hand
                FROM inventory i
                GROUP BY i.niin
            ) inv
                ON l.niin = inv.niin
            
            WHERE l.cog LIKE '7%'
            ORDER BY return_rate DESC, l.niin ASC
        ";
        
        $result = $db->query($sql)->fetchAll();
        $db->close();
        
        return $result;
    }
}