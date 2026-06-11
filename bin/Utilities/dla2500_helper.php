<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use setasign\Fpdi\Fpdi;

class DLA2500Helper
{
    public static function generate(array $records, bool $debug = false): void
    {
        $pdf = new Fpdi();

        $templateFile = APP_ROOT . '/templates/DLA2500Cert.pdf';

        $pageCount = $pdf->setSourceFile($templateFile);
        $templateId = $pdf->importPage(1);

        foreach (array_chunk($records, 4) as $pageRecords) {

            $pdf->AddPage('P', 'Letter');
            $pdf->useTemplate($templateId, 0, 0, 216);

            foreach ($pageRecords as $index => $record) {

                $offset = self::getSlotOffset($index);

                self::writeCertificate($pdf, $record, $offset);
            }
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="DLA2500.pdf"');

        $pdf->Output('I');
        exit;
    }

    private static function getSlotOffset(int $slot): array
    {
        return match ($slot) {
            0 => ['x' => 3,   'y' => 4],
            1 => ['x' => 111, 'y' => 4],
            2 => ['x' => 3,   'y' => 141],
            3 => ['x' => 111, 'y' => 141],
        };
    }

    private static function writeCertificate(Fpdi $pdf, array $row, array $offset): void
    {
        $x = $offset['x'];
        $y = $offset['y'];
        
        $serial = (string)($row['serial_number'] ?? '');
        $part   = (string)($row['part_number'] ?? '');
        $date   = !empty($row['destruction_date'])
        ? date('m/d/Y', strtotime($row['destruction_date']))
        : date('m/d/Y');
        
        $pdf->SetTextColor(0, 0, 0);
        
        // Main fields
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Text($x + 24, $y + 33, $serial); // Serial No
        $pdf->Text($x + 15, $y + 40, $part);   // Make/Model
        $pdf->Text($x + 16, $y + 57, $date);   // Date
        
        // Checkboxes
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Text($x + 84, $y + 64, 'X');     // Destroy
        $pdf->Text($x + 30, $y + 73, 'X');     // Degauss
        
        // Cert details
        $pdf->SetFont('Helvetica', '', 6.5);
        $pdf->Text($x + 34, $y + 87, 'DATA Security INC., NSA Certified');
        $pdf->Text($x + 33, $y + 100, 'DEGAUSSED/PUNCHED');
        
        // User info
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Text($x + 24, $y + 112, 'KEVIN SMITH');
        $pdf->Text($x + 34, $y + 121, 'MESC Greenbrier Chesapeake, VA');
        $pdf->Text($x + 10, $y + 131, 'kevin.t.smith26.civ@us.navy.mil');
        $pdf->Text($x + 12, $y + 141, '757-320-1927');
        $pdf->Text($x + 56, $y + 141, 'CIV');
    }
    
}