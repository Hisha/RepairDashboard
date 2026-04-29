<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class Inventory
{
    
    public function getInventory():array
    {
        $db = new db();
        
        $sql = "
        SELECT
        	inventory.primarypartno AS 'Part',
            inventory.description AS 'Nomen',
            inventory.niin AS 'NIIN',
            inventory.materialcode AS 'Condition Code',
            inventory.onhandqty AS 'Qty',
            inventory.subgrouptype AS 'Program',
            inventory.purposecode AS 'Purpose Code',
            inventory.storagebin AS 'Location',
            inventory.averageprice AS 'Unit Price'
        FROM inventory
        ORDER BY inventory.primarypartno ASC, inventory.subgrouptype ASC, inventory.materialcode ASC
    ";
        
        $results = $db->query($sql)->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
}