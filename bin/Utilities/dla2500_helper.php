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
        
        $pdf->SetFont('Helvetica', '', 6);
        $pdf->Text($x + 24, $y + 33, $serial);
        $pdf->Text($x + 18, $y + 43, $part);
        $pdf->Text($x + 18, $y + 60, $date);
        
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Text($x + 78, $y + 66, 'X'); // Destroy
        $pdf->Text($x + 30, $y + 76, 'X'); // Degauss
        
        $pdf->SetFont('Helvetica', '', 5);
        $pdf->Text($x + 36, $y + 92, 'DATA Security INC., NSA Certified');
        $pdf->Text($x + 40, $y + 105, 'DEGAUSSED/PUNCHED');
        
        $pdf->SetFont('Helvetica', '', 5.5);
        $pdf->Text($x + 25, $y + 119, 'KEVIN SMITH');
        $pdf->Text($x + 36, $y + 128, 'MESC Greenbrier Chesapeake, VA');
        $pdf->Text($x + 12, $y + 138, 'kevin.t.smith26.civ@us.navy.mil');
        $pdf->Text($x + 14, $y + 148, '757-320-1927');
        $pdf->Text($x + 57, $y + 148, 'CIV');
    }
    
}