<?php
include_once APP_ROOT . '/bin/Utilities/db.php';

class CavRequisitions
{
    protected $_tableName = 'cav_requisitions';
    
    public function getDistinctRecvMonths()
    {
        $db = new db();
        
        $sql = "
            SELECT DISTINCT
                DATE_FORMAT(date_recv, '%Y-%m') AS month_value,
                DATE_FORMAT(date_recv, '%M %Y') AS month_label
            FROM {$this->_tableName}
            WHERE date_recv IS NOT NULL
            ORDER BY month_value DESC
        ";
        
        return $db->query($sql)->fetchAll();
    }
    
    public function getDDLRecvMonths($selectedValue = '')
    {
        $months = $this->getDistinctRecvMonths();
        
        $html = "<select name='ddlRecvMonth' id='ddlRecvMonth'>";
        $html .= "<option value=''>Select a month.</option>";
        
        foreach ($months as $row) {
            $value = htmlspecialchars($row['month_value']);
            $label = htmlspecialchars($row['month_label']);
            $selected = ($selectedValue === $row['month_value']) ? " selected" : "";
            $html .= "<option value='{$value}'{$selected}>{$label}</option>";
        }
        
        $html .= "</select>";
        
        return $html;
    }
    
    public function getReportDateRanges(string $selectedMonth): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            throw new Exception('Invalid month selection.');
        }
        
        $monthStart = DateTime::createFromFormat('Y-m-d', $selectedMonth . '-01');
        
        if (!$monthStart) {
            throw new Exception('Invalid month selection.');
        }
        
        $monthEnd = clone $monthStart;
        $monthEnd->modify('last day of this month');
        
        $ytdStart = clone $monthStart;
        $ytdStart->modify('-11 months');
        
        $ytdEnd = clone $monthEnd;
        
        return [
            'month_start' => $monthStart->format('Y-m-d'),
            'month_end'   => $monthEnd->format('Y-m-d'),
            'ytd_start'   => $ytdStart->format('Y-m-d'),
            'ytd_end'     => $ytdEnd->format('Y-m-d'),
            
            'month_label' => $monthStart->format('F Y'),
            'month_line'  => $monthStart->format('M d, Y') . ' to ' . $monthEnd->format('M d, Y'),
            'ytd_line'    => $ytdStart->format('M d, Y') . ' to ' . $ytdEnd->format('M d, Y')
        ];
    }
}
?>