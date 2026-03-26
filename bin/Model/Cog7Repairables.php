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
                        
                -- 12 MONTH
                COALESCE(r12.receipts, 0) AS receipts_12m,
                COALESCE(rep12.repaired, 0) AS repaired_12m,
                COALESCE(rep12.ber, 0) AS ber_12m,
                COALESCE(rep12.eval, 0) AS eval_12m,
                        
                (COALESCE(r12.receipts,0) - (COALESCE(rep12.repaired,0) + COALESCE(rep12.ber,0))) AS open_12m,
                        
                CASE
                    WHEN (COALESCE(rep12.repaired,0) + COALESCE(rep12.ber,0)) > 0
                    THEN COALESCE(rep12.repaired,0) /
                         (COALESCE(rep12.repaired,0) + COALESCE(rep12.ber,0))
                    ELSE NULL
                END AS survival_12m,
                        
                -- LIFETIME
                COALESCE(rall.receipts, 0) AS receipts_all,
                COALESCE(repall.repaired, 0) AS repaired_all,
                COALESCE(repall.ber, 0) AS ber_all,
                COALESCE(repall.eval, 0) AS eval_all,
                        
                (COALESCE(rall.receipts,0) - (COALESCE(repall.repaired,0) + COALESCE(repall.ber,0))) AS open_all,
                        
                CASE
                    WHEN (COALESCE(repall.repaired,0) + COALESCE(repall.ber,0)) > 0
                    THEN COALESCE(repall.repaired,0) /
                         (COALESCE(repall.repaired,0) + COALESCE(repall.ber,0))
                    ELSE NULL
                END AS survival_all
                        
            FROM LMS21Data l
                        
            LEFT JOIN (
                SELECT niin, SUM(qty) AS receipts
                FROM receipts
                WHERE transactiondate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY niin
            ) r12 ON l.niin = r12.niin
                        
            LEFT JOIN (
                SELECT
                    niin,
                    COUNT(CASE WHEN materialcode IN ('A','D','G') THEN 1 END) AS repaired,
                    COUNT(CASE WHEN materialcode = 'H' THEN 1 END) AS ber,
                    COUNT(CASE WHEN materialcode = 'F' THEN 1 END) AS eval
                FROM repairs
                WHERE transactiondate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY niin
            ) rep12 ON l.niin = rep12.niin
                        
            LEFT JOIN (
                SELECT niin, SUM(qty) AS receipts
                FROM receipts
                GROUP BY niin
            ) rall ON l.niin = rall.niin
                        
            LEFT JOIN (
                SELECT
                    niin,
                    COUNT(CASE WHEN materialcode IN ('A','D','G') THEN 1 END) AS repaired,
                    COUNT(CASE WHEN materialcode = 'H' THEN 1 END) AS ber,
                    COUNT(CASE WHEN materialcode = 'F' THEN 1 END) AS eval
                FROM repairs
                GROUP BY niin
            ) repall ON l.niin = repall.niin
                        
            WHERE l.cog LIKE '7%'
                        
            ORDER BY survival_12m ASC, l.niin
            ";
        
        $result = $db->query($sql)->fetchAll();
        $db->close();
        
        return $result;
    }
}