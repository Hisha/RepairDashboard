<?php
require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/bin/Model/DriveDestruction.php';

header('Content-Type: application/json; charset=utf-8');

$drive = new DriveDestruction();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }
    
    $action = trim($_POST['action'] ?? '');
    $recordId = intval($_POST['record_id'] ?? 0);
    
    if ($recordId <= 0) {
        throw new Exception('Invalid record ID.');
    }
    
    if ($action === 'sign_destroyer') {
        $fullName = trim($_POST['destroyer_name'] ?? '');
        if ($fullName === '') {
            throw new Exception('Destroyer name is required.');
        }
        
        $drive->signDestroyer($recordId, $fullName);
    } elseif ($action === 'sign_witness') {
        $fullName = trim($_POST['witness_name'] ?? '');
        if ($fullName === '') {
            throw new Exception('Witness name is required.');
        }
        
        $drive->signWitness($recordId, $fullName);
    } else {
        throw new Exception('Unsupported action.');
    }
    
    $record = $drive->getRecordSummaryById($recordId);
    if (!$record) {
        throw new Exception('Record not found after update.');
    }
    
    echo json_encode([
        'success' => true,
        'record' => $record
    ]);
    exit;
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}