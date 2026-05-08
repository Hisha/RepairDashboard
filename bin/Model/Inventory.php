<?php
include_once APP_ROOT . '/bin/Utilities/db.php';
include_once APP_ROOT . '/bin/Utilities/helpers.php';

class Inventory
{
    
    private function getRepairLocationFilter(): array
    {
        return helpers::getNorthSouthSql('SYS_repair_program_mapping');
    }
    
    public function getInventory():array
    {
        $db = new db();
        
        $locationFilter = $this->getRepairLocationFilter();
        
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
        INNER JOIN SYS_repair_program_mapping
                ON inventory.subgrouptype = SYS_repair_program_mapping.source_program
        WHERE inventory.onhandqty <> '0'
           {$locationFilter['sql']}
        ORDER BY inventory.primarypartno ASC, inventory.subgrouptype ASC, inventory.materialcode ASC
    ";
        
           $results = $db->query($sql, ...$locationFilter['params'])->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
    public function getRepairableInventoryByNIIN(string $niin): array
    {
        $db = new db();
        
        $sql = "
        SELECT
            primarypartno,
            description,
            subgrouptype,
            storagebin,
            materialcode,
            onhandqty
        FROM inventory
        WHERE niin = ?
          AND materialcode NOT IN ('A', 'H')
          AND onhandqty <> '0'
          AND NOT (
              materialcode = 'F'
              AND purposecode = 'Z'
          )
        ORDER BY onhandqty DESC, primarypartno ASC
    ";
        
          $results = $db->query($sql, $niin)->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
}