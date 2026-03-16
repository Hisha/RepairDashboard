<?php

class SYS_LastUpdate
{
    public function getLastUpdate(string $updatefield): ?string
    {
        $db = new db();
        
        $sql = "
            SELECT uploaddate
            FROM last_update
            WHERE updatefield = ?
            LIMIT 1
        ";
        
        $row = $db->query($sql, $updatefield)->fetchArray();
        $db->close();
        
        return $row['uploaddate'] ?? null;
    }
}