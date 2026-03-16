<?php
require_once __DIR__ . "/bootstrap.php";
require_once APP_ROOT . "/bin/Model/BackOrders.php";
require_once APP_ROOT . "/bin/ViewHelpers/table_helper.php";

include 'menu.php';

$reqs = new BackOrders();
$backorders = $reqs->getBackOrderList();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backordered Requisitions</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 20px;
            background-color: #f8f9fa;
            color: #212529;
        }

        h1 {
            margin-bottom: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
        }

        th {
            background: #2c3e50;
            color: #fff;
            text-align: left;
            padding: 10px;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        tr:nth-child(even) {
            background: #f4f6f8;
        }

        tr:hover {
            background: #eaf2ff;
        }

        .page-wrap {
            max-width: 1400px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="page-wrap">
        <h1>Backordered Requisitions</h1>

        <?php
        renderTable($backorders, [
            'Date Received',
            'Program',
            'Priority',
            'Command',
            'Req Number',
            'NIIN',
            'Nomen',
            'QTY',
            'Notes'
        ], 'No backordered requisitions found.');
        ?>
    </div>
</body>
</html>