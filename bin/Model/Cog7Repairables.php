<?php
require_once APP_ROOT . '/bin/Utilities/db.php';

class Cog7Repairables
{
    public function getReport()
    {
        $db = new db();
        
        $sql = "
        SELECT
            l.niin,
            l.lrc,
            l.std_price,
            s.last_ship_date,
            
            COALESCE(rep12.repair_actions_12m, 0) AS repair_actions_12m,
            CASE
                WHEN COALESCE(rep12.repair_actions_12m, 0) > 0
                THEN COALESCE(rep12.success_actions_12m, 0) / COALESCE(rep12.repair_actions_12m, 0)
                ELSE NULL
            END AS survival_12m,
            
            COALESCE(repall.repair_actions_all, 0) AS repair_actions_all,
            CASE
                WHEN COALESCE(repall.repair_actions_all, 0) > 0
                THEN COALESCE(repall.success_actions_all, 0) / COALESCE(repall.repair_actions_all, 0)
                ELSE NULL
            END AS survival_all
            
        FROM LMS21Data l
            
        INNER JOIN (
            SELECT
                niin,
                MAX(transactiondate) AS last_ship_date
            FROM shipments
            WHERE transactiondate >= DATE_SUB(CURDATE(), INTERVAL 36 MONTH)
            GROUP BY niin
        ) s
            ON l.niin = s.niin
            
        LEFT JOIN (
            SELECT
                niin,
                COUNT(*) AS repair_actions_12m,
                SUM(CASE WHEN materialcode IN ('A', 'D', 'G') THEN 1 ELSE 0 END) AS success_actions_12m
            FROM repairs
            WHERE transactiondate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY niin
        ) rep12
            ON l.niin = rep12.niin
            
        LEFT JOIN (
            SELECT
                niin,
                COUNT(*) AS repair_actions_all,
                SUM(CASE WHEN materialcode IN ('A', 'D', 'G') THEN 1 ELSE 0 END) AS success_actions_all
            FROM repairs
            GROUP BY niin
        ) repall
            ON l.niin = repall.niin
            
        WHERE l.cog LIKE '7%'
          AND (
                COALESCE(rep12.repair_actions_12m, 0) > 0
                OR COALESCE(repall.repair_actions_all, 0) > 0
          )
            
        ORDER BY s.last_ship_date DESC, l.niin ASC
        ";
        
        $result = $db->query($sql)->fetchAll();
        $db->close();
        
        return $result;
    }
}