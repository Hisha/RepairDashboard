<?php

class excelformat
{
    public static function getConfig(string $excelKey): ?array
    {
        $formats = [
            
            'Requisitions' => [
                'table_name' => 'requisitions',
                
                'headers' => [
                    'Req Number',
                    'Requested By',
                    'Department',
                    'Status',
                    'Open Date',
                    'Close Date',
                    'Amount'
                ],
                
                'db_columns' => [
                    'req_number',
                    'requested_by',
                    'department',
                    'status',
                    'open_date',
                    'close_date',
                    'amount'
                ],
                
                'required_columns' => [
                    'req_number',
                    'requested_by'
                ],
                
                'column_types' => [
                    'req_number'   => 'string',
                    'requested_by' => 'string',
                    'department'   => 'string',
                    'status'       => 'string',
                    'open_date'    => 'date',
                    'close_date'   => 'date',
                    'amount'       => 'decimal'
                ],
                
                'create_sql' => "
                    CREATE TABLE `requisitions` (
                        `id` INT NOT NULL AUTO_INCREMENT,
                        `req_number` VARCHAR(100) NOT NULL,
                        `requested_by` VARCHAR(255) NOT NULL,
                        `department` VARCHAR(255) NULL,
                        `status` VARCHAR(100) NULL,
                        `open_date` DATE NULL,
                        `close_date` DATE NULL,
                        `amount` DECIMAL(12,2) NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                "
            ],
            
            'OpenPOs' => [
                'table_name' => 'open_pos',
                
                'headers' => [
                    'PO Number',
                    'Vendor',
                    'Buyer',
                    'PO Date',
                    'Total'
                ],
                
                'db_columns' => [
                    'po_number',
                    'vendor',
                    'buyer',
                    'po_date',
                    'total'
                ],
                
                'required_columns' => [
                    'po_number'
                ],
                
                'column_types' => [
                    'po_number' => 'string',
                    'vendor'    => 'string',
                    'buyer'     => 'string',
                    'po_date'   => 'date',
                    'total'     => 'decimal'
                ],
                
                'create_sql' => "
                    CREATE TABLE `open_pos` (
                        `id` INT NOT NULL AUTO_INCREMENT,
                        `po_number` VARCHAR(100) NOT NULL,
                        `vendor` VARCHAR(255) NULL,
                        `buyer` VARCHAR(255) NULL,
                        `po_date` DATE NULL,
                        `total` DECIMAL(12,2) NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                "
            ]
            
        ];
        
        return $formats[$excelKey] ?? null;
    }
}

?>