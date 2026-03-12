<?php

use PhpOffice\PhpPresentation\Slide\Slide;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Fill;

class TableBuilder
{
    public static function renderMonthlyDataTable(
        Slide $slide,
        array $tableData,
        int $xStart,
        int $yStart,
        int $colWidth = 65,
        int $rowHeight = 22,
        int $labelWidth = 120
        ): void {
            $rowIndex = 0;
            
            foreach ($tableData as $rowLabel => $rowValues) {
                $labelCell = $slide->createRichTextShape()
                ->setWidth($labelWidth)
                ->setHeight($rowHeight)
                ->setOffsetX($xStart - $labelWidth - 10)
                ->setOffsetY($yStart + ($rowIndex * $rowHeight));
                
                $labelCell->getActiveParagraph()
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                $labelCell->createTextRun($rowLabel)
                ->getFont()
                ->setName('Helvetica')
                ->setBold(true)
                ->setSize(10)
                ->setColor(new Color('FF000000'));
                
                foreach ($rowValues as $i => $value) {
                    $cell = $slide->createRichTextShape()
                    ->setWidth($colWidth)
                    ->setHeight($rowHeight)
                    ->setOffsetX($xStart + ($i * $colWidth))
                    ->setOffsetY($yStart + ($rowIndex * $rowHeight));
                    
                    $cell->getActiveParagraph()
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    
                    $textColor = 'FF000000';
                    
                    if ($rowLabel === 'Fill Rate %') {
                        if ($value >= 93) {
                            $bgColor = 'FF00B050';
                            $textColor = 'FFFFFFFF';
                        } elseif ($value >= 86) {
                            $bgColor = 'FFFFFF00';
                        } else {
                            $bgColor = 'FFFF0000';
                            $textColor = 'FFFFFFFF';
                        }
                        
                        $cell->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->setStartColor(new Color($bgColor));
                    }
                    
                    $displayValue = ($rowLabel === 'Fill Rate %')
                    ? $value . '%'
                        : $value;
                        
                        $cell->createTextRun((string)$displayValue)
                        ->getFont()
                        ->setName('Helvetica')
                        ->setSize(10)
                        ->setBold(true)
                        ->setColor(new Color($textColor));
                        
                        $cell->getBorder()
                        ->setLineWidth(0.5)
                        ->setColor(new Color('FF808080'));
                }
                
                $rowIndex++;
            }
    }
}