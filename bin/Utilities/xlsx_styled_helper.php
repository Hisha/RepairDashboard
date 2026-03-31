<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class xlsx_styled_helper
{
    public static function download(
        string $filename,
        array $headers,
        array $rows,
        array $options = []
        ): void {
            $textColumns = $options['textColumns'] ?? [];
            $sheetTitle = $options['sheetTitle'] ?? 'Report';
            $numberFormats = $options['numberFormats'] ?? [];
            $freezePane = $options['freezePane'] ?? 'A2';
            $autoFilter = $options['autoFilter'] ?? true;
            $autoSize = $options['autoSize'] ?? true;
            
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            if ($sheetTitle !== '') {
                $sheet->setTitle(substr($sheetTitle, 0, 31));
            }
            
            /*
             * Header row
             */
            foreach ($headers as $index => $header) {
                $colLetter = Coordinate::stringFromColumnIndex($index + 1);
                $cell = $colLetter . '1';
                $sheet->setCellValue($cell, $header);
            }
            
            /*
             * Header styling
             */
            $lastColumnLetter = Coordinate::stringFromColumnIndex(count($headers));
            $headerRange = 'A1:' . $lastColumnLetter . '1';
            
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF1F3F5');
            
            $sheet->getStyle($headerRange)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB('FFCCCCCC');
            
            /*
             * Data rows
             */
            $excelRow = 2;
            
            foreach ($rows as $row) {
                $rowType = $row['_row_type'] ?? 'normal';
                $cellStyles = $row['_cell_styles'] ?? [];
                
                foreach ($headers as $index => $header) {
                    $colLetter = Coordinate::stringFromColumnIndex($index + 1);
                    $cell = $colLetter . $excelRow;
                    $value = $row[$header] ?? '';
                    
                    if ($value === null) {
                        $value = '';
                    }
                    
                    if (in_array($header, $textColumns, true)) {
                        $sheet->setCellValueExplicit(
                            $cell,
                            (string)$value,
                            DataType::TYPE_STRING
                            );
                    } else {
                        $sheet->setCellValue($cell, $value);
                    }
                    
                    /*
                     * Column number formats
                     */
                    if (isset($numberFormats[$header])) {
                        $sheet->getStyle($cell)
                        ->getNumberFormat()
                        ->setFormatCode($numberFormats[$header]);
                    }
                    
                    /*
                     * Cell-level styles
                     */
                    if (isset($cellStyles[$header])) {
                        self::applyNamedCellStyle($sheet, $cell, $cellStyles[$header]);
                    }
                }
                
                /*
                 * Row-level styles
                 */
                $rowRange = 'A' . $excelRow . ':' . $lastColumnLetter . $excelRow;
                self::applyRowTypeStyle($sheet, $rowRange, $rowType);
                
                /*
                 * Default borders
                 */
                $sheet->getStyle($rowRange)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setARGB('FFDDDDDD');
                
                $excelRow++;
            }
            
            $lastDataRow = max(1, $excelRow - 1);
            $dataRange = 'A1:' . $lastColumnLetter . $lastDataRow;
            
            /*
             * Freeze pane
             */
            if ($freezePane) {
                $sheet->freezePane($freezePane);
            }
            
            /*
             * Auto filter
             */
            if ($autoFilter && $lastDataRow >= 1) {
                $sheet->setAutoFilter($dataRange);
            }
            
            /*
             * Autosize columns
             */
            if ($autoSize) {
                for ($col = 1; $col <= count($headers); $col++) {
                    $columnLetter = Coordinate::stringFromColumnIndex($col);
                    $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
                }
            }
            
            /*
             * Output
             */
            $safeFilename = self::normalizeFilename($filename);
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');
            header('Expires: 0');
            
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
    }
    
    private static function applyRowTypeStyle($sheet, string $range, string $rowType): void
    {
        switch ($rowType) {
            case 'subtotal':
                $sheet->getStyle($range)->getFont()->setBold(true);
                $sheet->getStyle($range)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE9ECEF');
                break;
                
            case 'grand_total':
                $sheet->getStyle($range)->getFont()->setBold(true);
                $sheet->getStyle($range)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFCED4DA');
                break;
                
            case 'highlight_red':
                $sheet->getStyle($range)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFFFE3E3');
                break;
                
            case 'highlight_yellow':
                $sheet->getStyle($range)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFFFF3CD');
                break;
                
            case 'highlight_green':
                $sheet->getStyle($range)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFD9F2D9');
                break;
            
            case 'highlight_blue':
                $sheet->getStyle($range)->getFont()->setBold(true);
                $sheet->getStyle($range)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFDBE7F3');
                break;
                
            case 'normal':
            default:
                break;
        }
    }
    
    private static function applyNamedCellStyle($sheet, string $cell, string $styleName): void
    {
        switch ($styleName) {
            case 'bad':
                $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFFFD6D6');
                break;
                
            case 'warn':
                $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFFFF3CD');
                break;
                
            case 'good':
                $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFD9F2D9');
                break;
                
            case 'bold':
                $sheet->getStyle($cell)->getFont()->setBold(true);
                break;
                
            default:
                break;
        }
    }
    
    private static function normalizeFilename(string $filename): string
    {
        $filename = trim($filename);
        
        if ($filename === '') {
            $filename = 'report.xlsx';
        }
        
        if (!str_ends_with(strtolower($filename), '.xlsx')) {
            $filename .= '.xlsx';
        }
        
        return $filename;
    }
}