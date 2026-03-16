<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class SYS_LastUpdate
{
    public function updateLastUpdate(string $updatefield): void
    {
        $db = new db();
        
        $sql = "
        INSERT INTO SYS_last_update (updatefield, uploaddate)
        VALUES (?, NOW())
        ON DUPLICATE KEY UPDATE
            uploaddate = NOW()
    ";
        
        $db->query($sql, $updatefield);
        
        $db->close();
    }
    
    public function getLastUpdate(string $updatefield): ?string
    {
        $db = new db();
        
        $sql = "
            SELECT uploaddate
            FROM SYS_last_update
            WHERE updatefield = ?
            LIMIT 1
        ";
        
        $row = $db->query($sql, $updatefield)->fetchArray();
        $db->close();
        
        return $row['uploaddate'] ?? null;
    }
}