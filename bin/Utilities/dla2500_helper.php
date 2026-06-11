<?php

use setasign\Fpdi\Fpdi;

class DLA2500Helper
{
    public static function generate(array $records): void
    {
        $pdf = new Fpdi();

        $templateFile = APP_ROOT . '/templates/DLA2500Cert.pdf';

        $pageCount = $pdf->setSourceFile($templateFile);
        $templateId = $pdf->importPage(1);

        foreach (array_chunk($records, 4) as $pageRecords) {

            $pdf->AddPage('L');
            $pdf->useTemplate($templateId);

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
            0 => ['x' => 0,   'y' => 0],
            1 => ['x' => 140, 'y' => 0],
            2 => ['x' => 0,   'y' => 105],
            3 => ['x' => 140, 'y' => 105],
        };
    }

    private static function writeCertificate(Fpdi $pdf, array $row, array $offset): void
    {
        $x = $offset['x'];
        $y = $offset['y'];

        $pdf->SetFont('Helvetica', '', 8);

        /*
         * Coordinates will need adjustment after first test.
         */

        $pdf->Text($x + 32, $y + 22, $row['serial_number']);
        $pdf->Text($x + 32, $y + 28, $row['part_number']);

        $pdf->Text($x + 85, $y + 28, 'X'); // Destroy
        $pdf->Text($x + 108, $y + 34, 'X'); // Degauss

        $pdf->Text($x + 32, $y + 40, 'DATA Security INC., NSA Certified');
        $pdf->Text($x + 32, $y + 46, 'DEGAUSSED/PUNCHED');

        $pdf->Text($x + 32, $y + 58, 'KEVIN SMITH');
        $pdf->Text($x + 32, $y + 64, 'MESC Greenbrier Chesapeake, VA');
        $pdf->Text($x + 32, $y + 70, 'kevin.t.smith26.civ@us.navy.mil');
        $pdf->Text($x + 32, $y + 76, '757-320-1927');
        $pdf->Text($x + 105, $y + 76, 'CIV');

        $pdf->Text($x + 32, $y + 82, date('m/d/Y'));
    }
}