<?php

use PhpOffice\PhpPresentation\Slide;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Color;

class ListBuilder
{
    public static function renderNiinNomenList(
        Slide $slide,
        array $rows,
        int $x,
        int $y,
        int $width = 400,
        int $height = 200,
        string $fontName = 'Helvetica',
        int $fontSize = 12,
        string $fontColor = 'FF000000',
        bool $bold = false,
        string $align = 'Alignment::HORIZONTAL_LEFT'
        ): void {
            $shape = $slide->createRichTextShape()
            ->setWidth($width)
            ->setHeight($height)
            ->setOffsetX($x)
            ->setOffsetY($y);
            
            $shape->getActiveParagraph()
            ->getAlignment()
            ->setHorizontal($align);
            
            foreach ($rows as $row) {
                $niin = isset($row['niin']) ? trim((string)$row['niin']) : '';
                $nomen = isset($row['nomen']) ? trim((string)$row['nomen']) : '';
                
                $text = $niin . ' (' . $nomen . ')';
                
                $shape->createTextRun($text)
                ->getFont()
                ->setName($fontName)
                ->setSize($fontSize)
                ->setBold($bold)
                ->setColor(new Color($fontColor));
                
                $shape->createBreak();
            }
    }
}