<?php

class excelformat
{
    public static function getConfig(string $excelKey): ?array
    {
        $formats = [
            
            'Inventory' => [
                'table_name' => 'inventory',
                'sheet_name' => 'Report',
                
                'headers' => [
                    'PRIMARYPARTNO',
                    'DESCRIPTION',
                    'NIIN',
                    'MATERIALCODE',
                    'ONHANDQTY',
                    'SUBGROUPTYPE',
                    'PURPOSECODE',
                    'STORAGEBIN',
                    'AVERAGEPRICE'
                ],
                
                'db_columns' => [
                    'primarypartno',
                    'description',
                    'niin',
                    'materialcode',
                    'onhandqty',
                    'subgrouptype',
                    'purposecode',
                    'storagebin',
                    'averageprice'
                ],
                
                'required_columns' => [
                    'primarypartno',
                    'materialcode',
                    'onhandqty',
                    'subgrouptype',
                    'purposecode',
                    'storagebin'
                ],
                
                'column_types' => [
                    'primarypartno'   => 'string',
                    'description' => 'string',
                    'niin'   => 'string',
                    'materialcode'       => 'string',
                    'onhandqty'    => 'int',
                    'subgrouptype'   => 'string',
                    'purposecode'       => 'string',
                    'storagebin'    => 'string',
                    'averageprice'  => 'decimal'
                ],
                
                'create_sql' => "
                    CREATE TABLE `inventory` (
                        `id` INT NOT NULL AUTO_INCREMENT,
                        `primarypartno` VARCHAR(100) NOT NULL,
                        `description` VARCHAR(255) NULL,
                        `niin` VARCHAR(12) NULL,
                        `materialcode` VARCHAR(2) NOT NULL,
                        `onhandqty` INT(11) NOT NULL,
                        `subgrouptype` VARCHAR(50) NOT NULL,
                        `purposecode` VARCHAR(2) NOT NULL,
                        `storagebin` VARCHAR(50) NOT NULL,
                        `averageprice` DECIMAL(12,2) NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                "
            ],
            
            'CAV REQUISITIONS' => [
                'table_name' => 'cav_requisitions',
                'sheet_name' => 'DATA Sheet',
                
                'headers' => [
                    'Program',
                    'DATE RECVD',
                    'DATE SHIPPED',
                    'COMMAND',
                    'REQ #',
                    'PRIORITY',
                    'NIIN',
                    'PART #',
                    'NOMEN',
                    'QTY',
                    'ITEM COST',
                    'STATUS',
                    'NOTES',
                    'RT',
                    'Goal',
                    'On Time'
                ],
                
                'db_columns' => [
                    'program',
                    'date_recv',
                    'date_shipped',
                    'command',
                    'req_number',
                    'priority',
                    'niin',
                    'part_number',
                    'nomen',
                    'qty',
                    'item_cost',
                    'status',
                    'notes',
                    'rt',
                    'goal',
                    'on_time'
                ],
                
                'required_columns' => [
                    'program',
                    'date_recv',
                    'command',
                    'req_number',
                    'priority',
                    'niin',
                    'part_number',
                    'nomen',
                    'qty',
                    'status',
                    'goal',
                    'on_time'
                ],
                
                'column_types' => [
                    'program'    => 'string',
                    'date_recv'    => 'date',
                    'date_shipped'    => 'date',
                    'command'    => 'string',
                    'req_number'    => 'string',
                    'priority'    => 'string',
                    'niin'    => 'string',
                    'part_number'    => 'string',
                    'nomen'    => 'string',
                    'qty'    => 'int',
                    'item_cost'    => 'decimal',
                    'status'    => 'string',
                    'notes'    => 'string',
                    'rt'    => 'int',
                    'goal'    => 'int',
                    'on_time'    => 'int'
                ],
                
                'create_sql' => "
                    CREATE TABLE `cav_requisitions` (
                        `id` INT NOT NULL AUTO_INCREMENT,
                        `program` VARCHAR(100) NOT NULL,
                        `date_recv` DATE NOT NULL,
                        `date_shipped` DATE NULL,
                        `command` VARCHAR(100) NOT NULL,
                        `req_number` VARCHAR(100) NOT NULL,
                        `priority` VARCHAR(50) NOT NULL,
                        `niin` VARCHAR(12) NOT NULL,
                        `part_number` VARCHAR(100) NOT NULL,
                        `nomen` VARCHAR(150) NOT NULL,
                        `qty` INT(11) NOT NULL,
                        `item_cost` DECIMAL(12,2) NULL,
                        `status` VARCHAR(100) NOT NULL,
                        `notes` VARCHAR(255) NULL,
                        `rt` INT(11) NULL,
                        `goal` INT(11) NOT NULL,
                        `on_time` INT(11) NOT NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                "
            ]
            
        ];
        
        return $formats[$excelKey] ?? null;
    }
}

?>