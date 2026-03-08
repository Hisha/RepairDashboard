<?php

require_once APP_ROOT . '/bin/Utilities/db.php';

class excelverify
{
    public static function verifyFileName(string $originalFileName): array
    {
        $baseName = trim(pathinfo($originalFileName, PATHINFO_FILENAME));
        
        if ($baseName === '') {
            return [
                'success' => false,
                'message' => 'Filename is empty.'
            ];
        }
        
        $db = new db();
        
        $result = $db->query(
            "SELECT excel_name, table_name, header_row
             FROM excellist
             WHERE excel_name = ?
               AND is_active = 1
             LIMIT 1",
            $baseName
            )->fetchArray();
            
            if (!$result) {
                return [
                    'success' => false,
                    'message' => "Spreadsheet '{$baseName}' is not in the approved list."
                    ];
            }
            
            return [
                'success' => true,
                'excel_key' => $result['excel_name'],
                'table_name' => $result['table_name'],
                `header_row` => (int)$result['needs_cleanup'],
                'message' => 'Spreadsheet verified.'
            ];
    }
}

?>