<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class ProgramMapping
{
    protected $_tableName = 'program_mapping';
    
    public function getDDLDistinctNormalizedProgram($selectedValue = '')
    {
        $data = "<select name='ddlDistinctNormalizedProgram' id='ddlDistinctNormalizedProgram'>";
        $data .= "<option value=''>Select a program.</option>";
        
        $db = new db();
        $sql = "
            SELECT DISTINCT normalized_program
            FROM {$this->_tableName}
            WHERE normalized_program IS NOT NULL
              AND normalized_program <> ''
            ORDER BY normalized_program
        ";
        $results = $db->query($sql)->fetchAll();
        
        foreach ($results as $row) {
            $program = htmlspecialchars($row['normalized_program']);
            $selected = ($selectedValue === $row['normalized_program']) ? " selected" : "";
            $data .= "<option value='{$program}'{$selected}>{$program}</option>";
        }
        
        $data .= "</select>";
        
        return $data;
    }
}
?>