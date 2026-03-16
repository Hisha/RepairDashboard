<?php

class excelformat
{
    public static function getConfig(string $excelKey): ?array
    {
        $formats = [
            
            'Batteries' => [
                'table_name' => 'batteries',
                'sheet_name' => 'Report',
                'updatefield' => 'CMPro',
                
                'headers' => [
                    'PRIMARYPARTNO',
                    'DESCRIPTION',
                    'NIIN',
                    'MATERIALCODE',
                    'ONHANDQTY',
                    'SUBGROUPTYPE',
                    'PURPOSECODE',
                    'STORAGEBIN'
                ],
                
                'db_columns' => [
                    'primarypartno',
                    'description',
                    'niin',
                    'materialcode',
                    'onhandqty',
                    'subgrouptype',
                    'purposecode',
                    'storagebin'
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
                    'storagebin'    => 'string'
                ],
                
                'create_sql' => "
                    CREATE TABLE `batteries` (
                        `id` INT NOT NULL AUTO_INCREMENT,
                        `primarypartno` VARCHAR(100) NOT NULL,
                        `description` VARCHAR(255) NULL,
                        `niin` VARCHAR(12) NULL,
                        `materialcode` VARCHAR(2) NOT NULL,
                        `onhandqty` INT(11) NOT NULL,
                        `subgrouptype` VARCHAR(50) NOT NULL,
                        `purposecode` VARCHAR(2) NOT NULL,
                        `storagebin` VARCHAR(50) NOT NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                "
            ],
            
            'CAV REQUISITIONS' => [
                'table_name' => 'cav_requisitions',
                'sheet_name' => 'DATA Sheet',
                'updatefield' => 'CAVS',
                
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
                    'req_number',
                    'priority',
                    'niin',
                    'part_number',
                    'qty',
                    'status'
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
                        `command` VARCHAR(100) NULL,
                        `req_number` VARCHAR(100) NOT NULL,
                        `priority` VARCHAR(50) NOT NULL,
                        `niin` VARCHAR(12) NOT NULL,
                        `part_number` VARCHAR(100) NOT NULL,
                        `nomen` VARCHAR(150) NULL,
                        `qty` INT(11) NOT NULL,
                        `item_cost` DECIMAL(12,2) NULL,
                        `status` VARCHAR(100) NOT NULL,
                        `notes` VARCHAR(255) NULL,
                        `rt` INT(11) NULL,
                        `goal` INT(11) NULL,
                        `on_time` INT(11) NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                "
            ],
            
            'DRMO' => [
                'table_name' => 'drmo',
                'sheet_name' => 'Report',
                'updatefield' => 'CMPro',
                
                'headers' => [
                    'Date',
                    'Document Number',
                    'NIIN',
                    'Part',
                    'Nomenclature',
                    'QTY',
                    'Unit Price',
                    'Program'
                ],
                
                'db_columns' => [
                    'date',
                    'document_number',
                    'niin',
                    'part',
                    'nomenclature',
                    'qty',
                    'unit_price',
                    'program'
                ],
                
                'required_columns' => [
                    'date',
                    'document_number',
                    'part',
                    'program'
                ],
                
                'column_types' => [
                    'date'   => 'date',
                    'document_number' => 'string',
                    'niin'   => 'string',
                    'part'       => 'string',
                    'nomenclature'    => 'string',
                    'qty'   => 'int',
                    'unit_price'       => 'decimal',
                    'program'    => 'string'
                ],
                
                'create_sql' => "
                    CREATE TABLE `drmo` (
                        `id` INT NOT NULL AUTO_INCREMENT,
                        `date` DATE NOT NULL,
                        `document_number` VARCHAR(255) NOT NULL,
                        `niin` VARCHAR(12) NULL,
                        `part` VARCHAR(100) NOT NULL,
                        `nomenclature` VARCHAR(255) NULL,
                        `qty` INT(11) NULL,
                        `unit_price` DECIMAL(12,2) NULL,
                        `program` VARCHAR(50) NOT NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                "
            ],
            
            'Installed' => [
                'table_name' => 'installed',
                'sheet_name' => 'Report',
                'updatefield' => 'CMPro',
                
                'headers' => [
                    'CREATEDATE',
                    'WONO',
                    'PARENTPART',
                    'PARTINSTALLED',
                    'PARTNOMEN',
                    'QTYINSTALLED',
                    'PARTCOST'
                ],
                
                'db_columns' => [
                    'createdate',
                    'wono',
                    'parentpart',
                    'partinstalled',
                    'partnomen',
                    'qtyinstalled',
                    'partcost'
                ],
                
                'required_columns' => [
                    'createdate',
                    'wono',
                    'parentpart',
                    'partinstalled'
                ],
                
                'column_types' => [
                    'createdate'   => 'date',
                    'wono' => 'string',
                    'parentpart'   => 'string',
                    'partinstalled'       => 'string',
                    'partnomen'    => 'string',
                    'qtyinstalled'   => 'int',
                    'partcost'       => 'decimal'
                ],
                
                'create_sql' => "
                    CREATE TABLE `installed` (
                        `id` INT NOT NULL AUTO_INCREMENT,
                        `createdate` DATE NOT NULL,
                        `wono` VARCHAR(255) NOT NULL,
                        `parentpart` VARCHAR(100) NOT NULL,
                        `partinstalled` VARCHAR(100) NOT NULL,
                        `partnomen` VARCHAR(255) NULL,
                        `qtyinstalled` INT(11) NULL,
                        `partcost` DECIMAL(12,2) NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                "
            ],
            
            'Inventory' => [
                'table_name' => 'inventory',
                'sheet_name' => 'Report',
                'updatefield' => 'CMPro',
                
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
            
            'Procurement Tracker2026' => [
                'table_name' => 'procurements',
                'sheet_name' => 'DATA Sheet',
                'updatefield' => 'Procurement',
                
                'headers' => [
                    'Program',
                    'REQUEST DATE',
                    'NIIN',
                    'PART #',
                    'NOMEN',
                    'QTY REQUESTED',
                    'REQUESTED BY',
                    'PURCHASE VEHICLE',
                    'ITEM COST (EACH)',
                    'STATUS',
                    'DATE SUBMITTED',
                    'CONTRACT #',
                    'QUOTE #',
                    'PO #',
                    'QTY ORDERED',
                    'AWARD DATE',
                    'EDD DATE',
                    'RECEIVE DATE',
                    'COMMENTS'
                ],
                
                'db_columns' => [
                    'program',
                    'request_date',
                    'niin',
                    'part',
                    'nomen',
                    'qty_requested',
                    'requested_by',
                    'purchase_vehicle',
                    'item_cost',
                    'status',
                    'date_submitted',
                    'contract_num',
                    'quote_num',
                    'po_num',
                    'qty_ordered',
                    'award_date',
                    'edd_date',
                    'receive_date',
                    'comments'
                ],
                
                'required_columns' => [
                    'program',
                    'request_date',
                    'niin',
                    'part',
                    'nomen',
                    'qty_requested',
                    'requested_by'
                ],
                
                'column_types' => [
                    'program'   => 'string',
                    'request_date'   => 'date',
                    'niin'   => 'string',
                    'part'   => 'string',
                    'nomen'   => 'string',
                    'qty_requested'   => 'int',
                    'requested_by'   => 'string',
                    'purchase_vehicle'   => 'string',
                    'item_cost'   => 'decimal',
                    'status'   => 'string',
                    'date_submitted'   => 'date',
                    'contract_num'   => 'string',
                    'quote_num'   => 'string',
                    'po_num'   => 'string',
                    'qty_ordered'   => 'int',
                    'award_date'   => 'date',
                    'edd_date'   => 'date',
                    'receive_date'   => 'date',
                    'comments'   => 'string'
                ],
                
                'create_sql' => "
                    CREATE TABLE `procurements` (
                        `id` INT NOT NULL AUTO_INCREMENT,
                        `program` VARCHAR(12) NOT NULL,
                        `request_date` DATE NOT NULL,
                        `niin` VARCHAR(12) NOT NULL,
                        `part` VARCHAR(100) NOT NULL,
                        `nomen` VARCHAR(255) NOT NULL,
                        `qty_requested` INT(11) NOT NULL,
                        `requested_by` VARCHAR(100) NOT NULL,
                        `purchase_vehicle` VARCHAR(100) NULL,
                        `item_cost` DECIMAL(12,2) NULL,
                        `status` VARCHAR(50) NULL,
                        `date_submitted` DATE NULL,
                        `contract_num` VARCHAR(100) NULL,
                        `quote_num` VARCHAR(100) NULL,
                        `po_num` VARCHAR(100) NULL,
                        `qty_ordered` INT(11) NULL,
                        `award_date` DATE NULL,
                        `edd_date` DATE NULL,
                        `receive_date` DATE NULL,
                        `comments` TEXT NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                "
            ],
            
            'Receipts' => [
                'table_name' => 'receipts',
                'sheet_name' => 'Report',
                'updatefield' => 'CMPro',
                
                'headers' => [
                    'SIMINVADJ_TRANSACTIONDATE',
                    'PRIMARYPARTNO',
                    'DESCRIPTION',
                    'NIIN',
                    'SIMINVADJ_ADDQTY',
                    'SIMINVADJ_SUBGROUPTYPE',
                    'SIMINVADJ_MATERIALCODE'
                ],
                
                'db_columns' => [
                    'transactiondate',
                    'primarypartno',
                    'description',
                    'niin',
                    'qty',
                    'subgrouptype',
                    'materialcode'
                ],
                
                'required_columns' => [
                    'transactiondate',
                    'primarypartno',
                    'qty',
                    'subgrouptype',
                    'materialcode'
                ],
                
                'column_types' => [
                    'transactiondate'   => 'date',
                    'primarypartno' => 'string',
                    'description'   => 'string',
                    'niin'       => 'string',
                    'qty'    => 'int',
                    'subgrouptype'   => 'string',
                    'materialcode'       => 'string'
                ],
                
                'create_sql' => "
                    CREATE TABLE `receipts` (
                        `id` INT NOT NULL AUTO_INCREMENT,
                        `transactiondate` DATE NOT NULL,
                        `primarypartno` VARCHAR(100) NULL,
                        `description` VARCHAR(255) NULL,
                        `niin` VARCHAR(12) NULL,
                        `qty` INT(11) NOT NULL,
                        `subgrouptype` VARCHAR(50) NOT NULL,
                        `materialcode` VARCHAR(2) NOT NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                "
            ],
            
            'Repairs' => [
                'table_name' => 'repairs',
                'sheet_name' => 'Report',
                'updatefield' => 'CMPro',
                
                'headers' => [
                    'TRANSACTIONDATE',
                    'WONO',
                    'PARTNOTOBUILD',
                    'NIIN',
                    'UNITPRICE',
                    'SERIALNO',
                    'SUBGROUPTYPE',
                    'MATERIALCODE',
                    'HOURS',
                    'TECHNICALPOCNAME',
                    'PROBLEM',
                    'CORRECTIVEACTION'
                ],
                
                'db_columns' => [
                    'transactiondate',
                    'wono',
                    'partnotobuild',
                    'niin',
                    'unitprice',
                    'serialno',
                    'subgrouptype',
                    'materialcode',
                    'hours',
                    'technicalpocname',
                    'problem',
                    'correctiveaction'
                ],
                
                'required_columns' => [
                    'transactiondate',
                    'wono',
                    'partnotobuild',
                    'serialno',
                    'subgrouptype',
                    'materialcode'
                ],
                
                'column_types' => [
                    'transactiondate'   => 'date',
                    'wono'  => 'string',
                    'partnotobuild'  => 'string',
                    'niin'  => 'string',
                    'unitprice'  => 'decimal',
                    'serialno'  => 'string',
                    'subgrouptype'  => 'string',
                    'materialcode'  => 'string',
                    'hours'  => 'decimal',
                    'technicalpocname'  => 'string',
                    'problem'  => 'string',
                    'correctiveaction'  => 'string'
                ],
                
                'create_sql' => "
                    CREATE TABLE `repairs` (
                        `id` INT NOT NULL AUTO_INCREMENT,
                        `transactiondate` DATE NOT NULL,
                        `wono` VARCHAR(50) NOT NULL,
                        `partnotobuild` VARCHAR(100) NOT NULL,
                        `niin` VARCHAR(12) NULL,
                        `unitprice` DECIMAL(12,2) NULL,
                        `serialno` VARCHAR(100) NOT NULL,
                        `subgrouptype` VARCHAR(50) NOT NULL,
                        `materialcode` VARCHAR(2) NOT NULL,
                        `hours` DECIMAL(12,2) NULL,
                        `technicalpocname` VARCHAR(100) NULL,
                        `problem` TEXT NULL,
                        `correctiveaction` TEXT NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                "
            ],
            
            'Shipments' => [
                'table_name' => 'shipments',
                'sheet_name' => 'Report',
                'updatefield' => 'CMPro',
                
                'headers' => [
                    'SIMINVADJ_TRANSACTIONDATE',
                    'PRIMARYPARTNO',
                    'DESCRIPTION',
                    'NIIN',
                    'SIMINVADJ_SUBQTY',
                    'SIMINVADJ_SUBGROUPTYPE',
                    'SIMINVADJ_MATERIALCODE',
                    'SIMINVADJ_ISSUELOCATION'
                ],
                
                'db_columns' => [
                    'transactiondate',
                    'primarypartno',
                    'description',
                    'niin',
                    'qty',
                    'subgrouptype',
                    'materialcode',
                    'issuelocation'
                ],
                
                'required_columns' => [
                    'transactiondate',
                    'primarypartno',
                    'qty',
                    'subgrouptype',
                    'materialcode',
                    'issuelocation'
                ],
                
                'column_types' => [
                    'transactiondate'   => 'date',
                    'primarypartno' => 'string',
                    'description'   => 'string',
                    'niin'       => 'string',
                    'qty'    => 'int',
                    'subgrouptype'   => 'string',
                    'materialcode'       => 'string',
                    'issuelocation'      => 'string'
                ],
                
                'create_sql' => "
                    CREATE TABLE `shipments` (
                        `id` INT NOT NULL AUTO_INCREMENT,
                        `transactiondate` DATE NOT NULL,
                        `primarypartno` VARCHAR(100) NULL,
                        `description` VARCHAR(255) NULL,
                        `niin` VARCHAR(12) NULL,
                        `qty` INT(11) NOT NULL,
                        `subgrouptype` VARCHAR(50) NOT NULL,
                        `materialcode` VARCHAR(2) NOT NULL,
                        `issuelocation` VARCHAR(50) NOT NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                "
            ]
            
        ];
        
        return $formats[$excelKey] ?? null;
    }
}

?>