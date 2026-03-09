<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class PowerPointFiller
{
    /*
     * `id` INT NOT NULL AUTO_INCREMENT,
	 * `program` VARCHAR(50) NOT NULL,
	 * `title` VARCHAR(50) NOT NULL,
	 * `pm` VARCHAR(50) NOT NULL,
	 * `programname` VARCHAR(50) NOT NULL
     */
    protected $returnString;
    protected $_returnFields = 'program,title,pm,programname';
    protected $_tableName = 'powerpoint_filler';
    
    public function getPPFiller(string $selectedProgram){
        $db = new db();
        $this->returnString = "SELECT $this->_returnFields FROM $this->_tableName WHERE program = $selectedProgram";
        $getAll = $db->query($this->returnString)->fetchAll();
        $db->close();
        return $getAll;
    }
    
}