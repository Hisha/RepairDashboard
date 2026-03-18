<?php
include_once APP_ROOT . '/bin/Utilities/db.php';
include_once APP_ROOT . '/bin/Utilities/helpers.php';

class Shipments
{
    public function getTop10ShipmentsByCog(string $cog, string $startDate, string $endDate): array
    {
        $db = new db();
        
        $sql = "
    SELECT
        shipments.niin AS 'NIIN',
        SUM(shipments.qty) AS 'QTY'
    FROM shipments
    INNER JOIN LMS21Data
        ON shipments.niin = LMS21Data.niin
    WHERE LMS21Data.cog LIKE ?
        AND shipments.transactiondate BETWEEN ? AND ?
    GROUP BY shipments.niin
    ORDER BY QTY DESC
    LIMIT 10
    ";
        
        // Add wildcard here
        $cogParam = $cog . '%';
        
        $results = $db->query($sql, $cogParam, $startDate, $endDate)->fetchAll();
        
        $db->close();
        
        return $results;
    }
}