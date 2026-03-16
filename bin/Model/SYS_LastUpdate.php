<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class SYS_LastUpdate
{
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