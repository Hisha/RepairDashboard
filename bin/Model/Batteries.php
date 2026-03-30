<?php
include_once APP_ROOT . '/bin/Utilities/db.php';
include_once APP_ROOT . '/bin/Utilities/helpers.php';

class Batteries
{
    public function getBatteryTracker():array
    {
        $db = new db();
        
        $helperS = new helpers();
        $startDate = $helperS->getRollingQuarterStart(5);
        $endDate   = $helperS->getCurrentQuarterEnd();
        
        $sql = "
        SELECT
            b.primarypartno AS 'Part',
            SUM(b.onhandqty) AS 'OnHand Qty',
        
            COALESCE(i.installed_qty, 0) AS 'Installed Qty',
            COALESCE(s.shipped_qty, 0) AS 'Shipped Qty'
        
        FROM batteries b
        
        LEFT JOIN (
            SELECT
                partinstalled,
                SUM(qtyinstalled) AS installed_qty
            FROM installed
            WHERE createdate BETWEEN ? AND ?
            GROUP BY partinstalled
        ) i ON b.primarypartno = i.partinstalled
        
        LEFT JOIN (
            SELECT
                primarypartno,
                SUM(qty) AS shipped_qty
            FROM shipments
            WHERE transactiondate BETWEEN ? AND ?
            GROUP BY primarypartno
        ) s ON b.primarypartno = s.primarypartno
        
        GROUP BY b.primarypartno
        ORDER BY b.primarypartno
    ";
        
        $results = $db->query($sql, $startDate, $endDate, $startDate, $endDate)->fetchAll();
        
        $db->close();
        
        return $results;
    }
}