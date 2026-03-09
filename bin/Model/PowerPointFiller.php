<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class PowerPointFiller
{
    protected $_tableName = 'powerpoint_filler';
    
    public function getPPFiller(string $selectedProgram){
        $db = new db();
        $this->returnString = "SELECT * FROM $this->_tableName WHERE program = $selectedProgram";
        $getAll = $db->query($this->returnString)->fetchAll();
        $db->close();
        return $getAll;
    }
    
}