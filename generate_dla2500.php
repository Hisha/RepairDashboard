<?php

require_once __DIR__ . '/bootstrap.php';

require_once APP_ROOT . '/vendor/autoload.php';

require_once APP_ROOT . '/bin/Model/DriveDestruction.php';
require_once APP_ROOT . '/bin/Utilities/dla2500_helper.php';

$model = new DriveDestruction();

$filters = [
    'search'    => trim($_GET['search'] ?? ''),
    'status'    => trim($_GET['status'] ?? ''),
    'method'    => trim($_GET['method'] ?? ''),
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to'   => trim($_GET['date_to'] ?? ''),
    'sort'      => trim($_GET['sort'] ?? 'destruction_date'),
    'dir'       => trim($_GET['dir'] ?? 'DESC')
];

$records = $model->getRecords($filters);

$records = array_filter(
    $records,
    function ($row) {
        
        $method = strtoupper(trim($row['destruction_method'] ?? ''));
        
        return $method !== 'SHREDDED';
    }
    );

if (empty($records)) {
    die('No records found.');
}

//DLA2500Helper::generate(array_values($records));
DLA2500Helper::generate(array_values($records));