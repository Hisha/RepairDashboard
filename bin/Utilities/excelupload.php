<?php

require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/bin/Utilities/db.php';
require_once APP_ROOT . '/bin/Utilities/excelverify.php';
require_once APP_ROOT . '/bin/Utilities/excelformat.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class excelupload
{
    public static function processUpload(array $file): array
    {
        $db = null;

        // Extra headroom for larger spreadsheets
        ini_set('memory_limit', '1536M');
        set_time_limit(300);

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
            $expectedHeaders = $config['headers'];
            $dbColumns       = $config['db_columns'];
            $requiredColumns = $config['required_columns'] ?? [];
            $columnTypes     = $config['column_types'] ?? [];
            $createSql       = $config['create_sql'];
            $headerRow       = max(1, (int)($verify['header_row'] ?? 1));

            if (count($expectedHeaders) !== count($dbColumns)) {
                throw new Exception("Configuration error: headers and db_columns count do not match for '{$excelKey}'.");
            }

            // Read spreadsheet in data-only mode to reduce memory usage
            $reader = IOFactory::createReaderForFile($file['tmp_name']);
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();

            $activeSheetTitle = $sheet->getTitle();
            $sheetNames = $spreadsheet->getSheetNames();
            $highestColumn = $sheet->getHighestDataColumn();
            $highestRow = $sheet->getHighestDataRow();

            // Read just the configured header row directly
            $headerRange = 'A' . $headerRow . ':' . $highestColumn . $headerRow;
            $headerCells = $sheet->rangeToArray($headerRange, null, true, true, false);
            $actualHeaders = array_map(function ($value) {
                return trim((string)$value);
            }, $headerCells[0] ?? []);

            // Read full sheet into array for normal processing
            $rows = $sheet->toArray(null, true, true, false);

            // Keep spreadsheet objects alive until after debug checks
            if (empty($rows)) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $sheet, $reader);
                throw new Exception('Spreadsheet is empty.');
            }

            // Make configured header row become row 0
            $rows = array_slice($rows, $headerRow - 1);

            if (empty($rows) || count($rows) < 2) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $sheet, $reader);
                throw new Exception('Spreadsheet is empty or contains no usable data after applying header_row.');
            }

            // TEMP DEBUG: if header mismatch, dump useful workbook/sheet/row info
            if ($actualHeaders !== $expectedHeaders) {
                $debugRows = [];
                $maxDebugRows = min(5, count($rows));

                for ($i = 0; $i < $maxDebugRows; $i++) {
                    $rowValues = array_map(function ($v) {
                        return trim((string)$v);
                    }, $rows[$i]);

                    $debugRows[] = 'Sheet row ' . ($headerRow + $i) . ': [' . implode(' | ', $rowValues) . ']';
                }

                $message =
                    'Header mismatch. ' .
                    'header_row=' . $headerRow .
                    ' | active_sheet=' . $activeSheetTitle .
                    ' | sheets=[' . implode(', ', $sheetNames) . ']' .
                    ' | highest_column=' . $highestColumn .
                    ' | highest_row=' . $highestRow .
                    ' | header_range=' . $headerRange .
                    ' | Expected: [' . implode(', ', $expectedHeaders) . ']' .
                    ' | Found: [' . implode(', ', $actualHeaders) . ']' .
                    ' | Debug Rows: ' . implode(' || ', $debugRows);

                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $sheet, $reader);

                throw new Exception($message);
            }

            // Now free workbook memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet, $reader);

            // Validate and normalize all rows before modifying database data
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
                            $sheetRowNumber = $headerRow + $rowIndex + 1;
                            throw new Exception("Required value missing for '{$columnName}' on spreadsheet row {$sheetRowNumber}.");
                        }
                    }

                    $normalizedRow[] = $normalizedValue;
                }

                $preparedRows[] = $normalizedRow;
            }

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
        if ($value === null) {
            return null;
        }

        // Handle true Excel numeric date serials
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