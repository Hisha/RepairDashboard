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
            
            COALESCE(r12.receipts, 0) AS receipts_12m,
            
            COALESCE(rall.receipts, 0) AS receipts_all,
            COALESCE(repall.repaired, 0) AS repaired_all,
            COALESCE(repall.ber, 0) AS ber_all,
            COALESCE(repall.eval, 0) AS eval_all,
            
            GREATEST(
                COALESCE(rall.receipts, 0) - (COALESCE(repall.repaired, 0) + COALESCE(repall.ber, 0)),
                0
            ) AS backlog,
            
            CASE
                WHEN (COALESCE(repall.repaired, 0) + COALESCE(repall.ber, 0)) > 0
                THEN COALESCE(repall.repaired, 0) /
                     (COALESCE(repall.repaired, 0) + COALESCE(repall.ber, 0))
                ELSE NULL
            END AS survival_rate
            
        FROM LMS21Data l
            
        LEFT JOIN (
            SELECT
                niin,
                SUM(qty) AS receipts
            FROM receipts
            WHERE transactiondate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY niin
        ) r12
            ON l.niin = r12.niin
            
        LEFT JOIN (
            SELECT
                niin,
                SUM(qty) AS receipts
            FROM receipts
            GROUP BY niin
        ) rall
            ON l.niin = rall.niin
            
        LEFT JOIN (
            SELECT
                niin,
                SUM(CASE WHEN materialcode IN ('A', 'D', 'G') THEN 1 ELSE 0 END) AS repaired,
                SUM(CASE WHEN materialcode = 'H' THEN 1 ELSE 0 END) AS ber,
                SUM(CASE WHEN materialcode = 'F' THEN 1 ELSE 0 END) AS eval
            FROM repairs
            GROUP BY niin
        ) repall
            ON l.niin = repall.niin
            
        WHERE l.cog LIKE '7%'
          AND (
                COALESCE(rall.receipts, 0) > 0
                OR COALESCE(repall.repaired, 0) > 0
                OR COALESCE(repall.ber, 0) > 0
          )
            
        ORDER BY
            survival_rate ASC,
            backlog DESC,
            receipts_12m DESC,
            l.niin ASC
        ";
        
        $result = $db->query($sql)->fetchAll();
        $db->close();
        
        return $result;
    }
}