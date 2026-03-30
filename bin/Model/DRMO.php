<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class DRMO
{
    public function getAvailableDrmoMonths(): array
    {
        $db = new db();
        
        $sql = "
            SELECT
                DATE_FORMAT(date, '%Y-%m') AS month_value,
                DATE_FORMAT(date, '%M %Y') AS month_label,
                MAX(date) AS sort_date
            FROM drmo
            GROUP BY DATE_FORMAT(date, '%Y-%m'), DATE_FORMAT(date, '%M %Y')
            ORDER BY sort_date DESC
        ";
        
        $results = $db->query($sql)->fetchAll();
        $db->close();
        
        return $results;
    }
    
    public function getDRMOByMonth(string $selectedMonth): array
    {
        $db = new db();
        
        $sql = "
            SELECT
                date AS 'Transaction Date',
                niin AS 'NIIN',
                part AS 'Part',
                nomenclature AS 'Nomen',
                program AS 'Program',
                qty AS 'Qty',
                unit_price AS 'Unit Price',
                document_number AS 'Document Number'
            FROM drmo
            WHERE DATE_FORMAT(date, '%Y-%m') = ?
            ORDER BY date DESC, niin ASC
        ";
        
        $results = $db->query($sql, $selectedMonth)->fetchAll();
        $db->close();
        
        return $results;
    }
}