<?php

require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/bin/Model/SYS_LastUpdate.php';
require_once APP_ROOT . '/bin/Utilities/db.php';
require_once APP_ROOT . '/bin/Utilities/excelverify.php';
require_once APP_ROOT . '/bin/Utilities/excelformat.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

$sysLastUpdate = new SYS_LastUpdate();

class excelupload
{
    
    public static function processUpload(array $file): array
    {
        $db = null;
        $spreadsheet = null;
        $sheet = null;
        $reader = null;

        ini_set('memory_limit', '1536M');
        set_time_limit(600);

        try {
            if (!isset($file['name'], $file['tmp_name'])) {
                throw new Exception('No file received.');
            }

            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
                throw new Exception(
                    'File upload failed. PHP upload error code: ' .
                    $errorCode . ' - ' . self::getUploadErrorMessage($errorCode)
                );
            }

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['xls', 'xlsx'], true)) {
                throw new Exception('Only .xls and .xlsx files are allowed.');
            }

            $verify = excelverify::verifyFileName($file['name']);
            if (!$verify['success']) {
                throw new Exception($verify['message']);
            }

            $excelKey = $verify['excel_key'];

            $config = excelformat::getConfig($excelKey);
            if (!$config) {
                throw new Exception("No format definition found for '{$excelKey}'.");
            }

            $tableName       = $config['table_name'];
            $sheetName       = $config['sheet_name'] ?? null;
            $updateField     = $config['updatefield'] ?? null;
            $expectedHeaders = $config['headers'];
            $dbColumns       = $config['db_columns'];
            $requiredColumns = $config['required_columns'] ?? [];
            $columnTypes     = $config['column_types'] ?? [];
            $createSql       = $config['create_sql'];
            $headerRow       = max(1, (int)($verify['header_row'] ?? 1));

            if (count($expectedHeaders) !== count($dbColumns)) {
                throw new Exception("Configuration error: headers and db_columns count do not match for '{$excelKey}'.");
            }

            $reader = IOFactory::createReaderForFile($file['tmp_name']);
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($file['tmp_name']);
            $availableSheets = $spreadsheet->getSheetNames();

            if ($sheetName !== null && trim($sheetName) !== '') {
                $sheet = $spreadsheet->getSheetByName($sheetName);

                if ($sheet === null) {
                    throw new Exception(
                        "Configured sheet '{$sheetName}' was not found in workbook. Available sheets: [" .
                        implode(', ', $availableSheets) . ']'
                    );
                }
            } else {
                $sheet = $spreadsheet->getActiveSheet();
            }

            $columnCount = count($dbColumns);

            // Validate headers
            $actualHeaders = [];
            for ($col = 1; $col <= $columnCount; $col++) {
                $cellRef = Coordinate::stringFromColumnIndex($col) . $headerRow;
                $cell = $sheet->getCell($cellRef);
                $actualHeaders[] = trim((string) self::getCellDisplayValue($cell));
            }

            if ($actualHeaders !== $expectedHeaders) {
                throw new Exception(
                    'Header mismatch. Expected: [' . implode(', ', $expectedHeaders) .
                    '] Found: [' . implode(', ', $actualHeaders) . ']'
                );
            }

            $preparedRows = [];
            $highestRow = $sheet->getHighestDataRow();
            $emptyRowStreak = 0;
            $maxEmptyRowStreak = 200;

            for ($rowNum = $headerRow + 1; $rowNum <= $highestRow; $rowNum++) {
                $rawRow = [];

                for ($col = 1; $col <= $columnCount; $col++) {
                    $cellRef = Coordinate::stringFromColumnIndex($col) . $rowNum;
                    $cell = $sheet->getCell($cellRef);
                    $rawRow[] = self::getCellDisplayValue($cell);
                }

                if (self::isEmptyRow($rawRow)) {
                    $emptyRowStreak++;
                    if ($emptyRowStreak >= $maxEmptyRowStreak) {
                        break;
                    }
                    continue;
                }

                $emptyRowStreak = 0;
                $normalizedRow = [];

                foreach ($dbColumns as $i => $columnName) {
                    $rawValue = $rawRow[$i] ?? null;
                    $type = $columnTypes[$columnName] ?? 'string';
                    $normalizedValue = self::normalizeValue($rawValue, $type);

                    if (in_array($columnName, $requiredColumns, true)) {
                        if ($normalizedValue === null || $normalizedValue === '') {
                            throw new Exception("Required value missing for '{$columnName}' on spreadsheet row {$rowNum}.");
                        }
                    }

                    $normalizedRow[] = $normalizedValue;
                }

                $preparedRows[] = $normalizedRow;
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet, $reader);
            $spreadsheet = null;
            $sheet = null;
            $reader = null;

            if (empty($preparedRows)) {
                throw new Exception('No data rows were found to import.');
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

            $sysLastUpdate->updateLastUpdate($updateField);

            return [
                'success' => true,
                'message' => "Upload complete. Imported " . count($preparedRows) . " row(s) into '{$tableName}'."
            ];

        } catch (Exception $e) {
            if ($db) {
                $db->rollback();
            }

            if ($spreadsheet !== null) {
                $spreadsheet->disconnectWorksheets();
            }

            unset($spreadsheet, $sheet, $reader);

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

    private static function getCellDisplayValue(Cell $cell)
    {
        $value = $cell->getValue();

        if ($value === null) {
            return null;
        }

        // Formula cell: prefer cached calculated value first, then calculate if needed
        if (is_string($value) && strlen($value) > 0 && $value[0] === '=') {
            $cached = $cell->getOldCalculatedValue();
            if ($cached !== null) {
                return $cached;
            }

            try {
                $calculated = $cell->getCalculatedValue();

                if (is_object($calculated) && method_exists($calculated, '__toString')) {
                    return (string)$calculated;
                }

                return $calculated;
            } catch (Exception $e) {
                return null;
            }
        }

        return $value;
    }

    private static function normalizeValue($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        if ($type === 'date' && is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (Exception $e) {
                return null;
            }
        }

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
                $value = str_replace([','], '', $value);
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

    private static function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_OK:
                return 'There is no error.';
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize limit in php.ini.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE limit in the HTML form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            default:
                return 'Unknown upload error.';
        }
    }
}

?>