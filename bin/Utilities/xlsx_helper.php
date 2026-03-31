<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class xlsx_helper
{
    public static function download(
        string $filename,
        array $headers,
        array $rows,
        array $textColumns = [],
        ?string $sheetTitle = 'Report'
        ): void {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            if ($sheetTitle !== null && $sheetTitle !== '') {
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
             * Data rows
             */
            $rowNum = 2;
            foreach ($rows as $row) {
                foreach ($headers as $index => $header) {
                    $colLetter = Coordinate::stringFromColumnIndex($index + 1);
                    $cell = $colLetter . $rowNum;
                    
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
                }
                
                $rowNum++;
            }
            
            /*
             * Styling
             */
            $lastColumnLetter = Coordinate::stringFromColumnIndex(count($headers));
            $lastRow = max(1, count($rows) + 1);
            
            $headerRange = 'A1:' . $lastColumnLetter . '1';
            $dataRange = 'A1:' . $lastColumnLetter . $lastRow;
            
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF1F3F5');
            
            /*
             * Freeze top row
             */
            $sheet->freezePane('A2');
            
            /*
             * Auto filter
             */
            $sheet->setAutoFilter($dataRange);
            
            /*
             * Autosize columns
             */
            for ($col = 1; $col <= count($headers); $col++) {
                $columnLetter = Coordinate::stringFromColumnIndex($col);
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
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