<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class PowerPointFiller
{
    protected $_returnFields = 'program,title,pm,programname';
    protected $_tableName = 'powerpoint_filler';
    
    public function getPPFiller(string $selectedProgram)
    {
        $db = new db();
        
        $sql = "
            SELECT {$this->_returnFields}
            FROM {$this->_tableName}
            WHERE program = ?
            LIMIT 1
        ";
        
        $result = $db->query($sql, $selectedProgram)->fetchArray();
        $db->close();
        
        return $result;
    }
}
?>