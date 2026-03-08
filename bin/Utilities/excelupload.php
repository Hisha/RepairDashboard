<?php

require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/bin/Utilities/db.php';
require_once APP_ROOT . '/bin/Utilities/excelverify.php';
require_once APP_ROOT . '/bin/Utilities/excelformat.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class excelupload
{
    public static function processUpload(array $file): array
    {
        $db = null;
        
        try {
            if (!isset($file['name'], $file['tmp_name'])) {
                throw new Exception('No file received.');
            }
            
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new Exception('File upload failed.');
            }
            
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($extension !== 'xlsx') {
                throw new Exception('Only .xlsx files are allowed.');
            }
            
            $verify = ExcelVerify::verifyFileName($file['name']);
            if (!$verify['success']) {
                throw new Exception($verify['message']);
            }
            
            $excelKey = $verify['excel_key'];
            
            $config = ExcelFormat::getConfig($excelKey);
            if (!$config) {
                throw new Exception("No format definition found for '{$excelKey}'.");
            }
            
            $tableName       = $config['table_name'];
            $expectedHeaders = $config['headers'];
            $dbColumns       = $config['db_columns'];
            $requiredColumns = $config['required_columns'] ?? [];
            $columnTypes     = $config['column_types'] ?? [];
            $createSql       = $config['create_sql'];
            
            if (count($expectedHeaders) !== count($dbColumns)) {
                throw new Exception("Configuration error: headers and db_columns count do not match for '{$excelKey}'.");
            }
            
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
            
            if (empty($rows) || count($rows) < 2) {
                throw new Exception('Spreadsheet is empty or contains no data rows.');
            }
            
            $actualHeaders = array_map(function ($value) {
                return trim((string)$value);
            }, $rows[0]);
                
                if ($actualHeaders !== $expectedHeaders) {
                    throw new Exception(
                        'Header mismatch. Expected: [' . implode(', ', $expectedHeaders) .
                        '] Found: [' . implode(', ', $actualHeaders) . ']'
                        );
                }
                
                // Validate all rows first before truncating anything
                $preparedRows = [];
                $dataRows = array_slice($rows, 1);
                
                foreach ($dataRows as $rowIndex => $row) {
                    if (self::isEmptyRow($row)) {
                        continue;
                    }
                    
                    $normalizedRow = [];
                    
                    foreach ($dbColumns as $i => $columnName) {
                        $rawValue = $row[$i] ?? null;
                        $type = $columnTypes[$columnName] ?? 'string';
                        $normalizedValue = self::normalizeValue($rawValue, $type);
                        
                        if (in_array($columnName, $requiredColumns, true)) {
                            if ($normalizedValue === null || $normalizedValue === '') {
                                throw new Exception("Required value missing for '{$columnName}' on row " . ($rowIndex + 2) . ".");
                            }
                        }
                        
                        $normalizedRow[] = $normalizedValue;
                    }
                    
                    $preparedRows[] = $normalizedRow;
                }
                
                $db = new db();
                
                $tableExists = $db->query(
                    "SELECT COUNT(*) AS cnt
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name = ?",
                    $tableName
                    )->fetchArray();
                    
                    if ((int)$tableExists['cnt'] === 0) {
                        $db->query($createSql);
                    }
                    
                    $db->beginTransaction();
                    
                    $db->query("TRUNCATE TABLE `{$tableName}`");
                    
                    $insertSql = self::buildInsertSql($tableName, $dbColumns);
                    
                    foreach ($preparedRows as $preparedRow) {
                        $params = array_merge([$insertSql], $preparedRow);
                        call_user_func_array([$db, 'query'], self::makeValuesReferenced($params));
                    }
                    
                    $db->commit();
                    
                    return [
                        'success' => true,
                        'message' => "Upload complete. Imported " . count($preparedRows) . " row(s) into '{$tableName}'."
                        ];
                    
        } catch (Exception $e) {
            if ($db) {
                $db->rollback();
            }
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private static function buildInsertSql(string $tableName, array $dbColumns): string
    {
        $columnList = '`' . implode('`, `', $dbColumns) . '`';
        $placeholders = implode(', ', array_fill(0, count($dbColumns), '?'));
        
        return "INSERT INTO `{$tableName}` ({$columnList}) VALUES ({$placeholders})";
    }
    
    private static function normalizeValue($value, string $type)
    {
        $value = trim((string)$value);
        
        if ($value === '') {
            return null;
        }
        
        switch ($type) {
            case 'date':
                $timestamp = strtotime($value);
                return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
                
            case 'decimal':
                $value = str_replace([',', '$'], '', $value);
                return is_numeric($value) ? $value : null;
                
            case 'int':
                return is_numeric($value) ? (int)$value : null;
                
            case 'string':
            default:
                return $value;
        }
    }
    
    private static function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string)$value) !== '') {
                return false;
            }
        }
        return true;
    }
    
    private static function makeValuesReferenced(array $arr): array
    {
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }
}

?>