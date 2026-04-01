<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class LMS21Data
{
    public function getMissingNiins(): array
    {
        $db = new db();
        
        $sql = "
        SELECT DISTINCT
            i.niin AS NIIN,
            i.subgrouptype AS listed_subgroup,
            s.normalized_program AS normalized_program
        FROM inventory i
        INNER JOIN SYS_repair_program_mapping s
            ON i.subgrouptype = s.source_program
        LEFT JOIN LMS21Data l
            ON i.niin = l.niin
        WHERE l.niin IS NULL
          AND i.niin IS NOT NULL
          AND i.niin <> ''
        ORDER BY i.niin
    ";
        
        $results = $db->query($sql)->fetchAll();
        $db->close();
        
        return $results;
    }
    
    public function getLrcOptions(): array
    {
        $db = new db();
        
        $sql = "
            SELECT DISTINCT normalized_program
            FROM SYS_repair_program_mapping
            WHERE normalized_program IS NOT NULL
              AND normalized_program <> ''
            ORDER BY normalized_program
        ";
        
        $results = $db->query($sql)->fetchAll();
        $db->close();
        
        return $results;
    }
    
    public function insertLms21Data(string $niin, string $cog, string $lrc, float $stdPrice): bool
    {
        $db = new db();
        
        $sql = "
            INSERT INTO LMS21Data (niin, cog, lrc, std_price)
            VALUES (?, ?, ?, ?)
        ";
        
        $result = $db->query($sql, $niin, $cog, $lrc, $stdPrice);
        
        $db->close();
        
        return $result !== false;
    }
}