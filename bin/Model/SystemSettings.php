<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class SystemSettings
{

    /*
     * SystemSettings Table:
     * `SystemSettingsId` INT NOT NULL AUTO_INCREMENT,
     * `Errors_Active` bit(1) NOT NULL
     */
    protected $returnString;

    protected $_insertFields = 'Errors_Active';

    protected $_returnFields = 'SystemSettingsId, Errors_Active';

    protected $_tableName = 'SystemSettings';

    public function insertRecord($errors_active)
    {
        $db = new db();
        $this->returnString = "INSERT into $this->_tableName($this->_insertFields) VALUES ($errors_active)";
        $db->query($this->returnString);
        $lastInsertId = $db->lastInsertID();
        $db->close();
        return $lastInsertId;
    }

    public function updateRecord($errors_active, $systemsettingsid)
    {
        $db = new db();
        $this->returnString = "UPDATE $this->_tableName SET Errors_Active = $errors_active WHERE SystemSettingsId = $systemsettingsid";
        $update = $db->query($this->returnString);
        $db->close();
        return $update->affectedRows();
    }

    public function getSystemSettings()
    {
        $db = new db();
        $this->returnString = "SELECT $this->_returnFields FROM $this->_tableName";
        $getSystemSettings = $db->query($this->returnString)->fetchArray();
        $db->close();
        return $getSystemSettings;
    }
}
?>