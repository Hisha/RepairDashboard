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
    
    public function getPieData_Shipped(string $selectedProgram, string $startDate, string $endDate): int
    {
        $db = new db();
        
        $sql = "
        SELECT
            COUNT(*) AS shipped
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_shipped BETWEEN ? AND ?
          AND cav_requisitions.priority IN ('CASREP', 'ANORS', 'SPARE', '999', 'FLEET FAILURE')
          AND cav_requisitions.status IN ('Shipped', 'PICK UP')
    ";
        
        $row = $db->query($sql, $selectedProgram, $startDate, $endDate)->fetchArray();
        
        $db->close();
        
        return isset($row['shipped']) ? (int)$row['shipped'] : 0;
    }
    
    public function getPieData_BOShipped(string $selectedProgram, string $startDate, string $endDate): int
    {
        $db = new db();
        
        $sql = "
        SELECT
            COUNT(*) AS boShipped
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_shipped BETWEEN ? AND ?
          AND cav_requisitions.priority IN ('CASREP', 'ANORS', 'SPARE', '999', 'FLEET FAILURE')
          AND cav_requisitions.status = 'B/O SHIPPED'
    ";
        
        $row = $db->query($sql, $selectedProgram, $startDate, $endDate)->fetchArray();
        
        $db->close();
        
        return isset($row['boShipped']) ? (int)$row['boShipped'] : 0;
    }
    
    public function getShippedDoughnutData(string $selectedProgram, string $startDate, string $endDate): array
    {
        $db = new db();
        
        $sql = "
        SELECT
            SUM(CASE
                WHEN cav_requisitions.priority = 'FLEET FAILURE' THEN 1
                ELSE 0
            END) AS fleetFailure,
            SUM(CASE
                WHEN cav_requisitions.priority = '999' THEN 1
                ELSE 0
            END) AS nineNineNine,
            SUM(CASE
                WHEN cav_requisitions.priority = 'SPARE' THEN 1
                ELSE 0
            END) AS spare,
            SUM(CASE
                WHEN cav_requisitions.priority = 'ANORS' THEN 1
                ELSE 0
            END) AS anors,
            SUM(CASE
                WHEN cav_requisitions.priority = 'CASREP' THEN 1
                ELSE 0
            END) AS casrep
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_shipped BETWEEN ? AND ?
          AND cav_requisitions.status IN ('SHIPPED', 'PICK UP', 'B/O SHIPPED')
    ";
        
        $row = $db->query($sql, $selectedProgram, $startDate, $endDate)->fetchArray();
        
        $db->close();
        
        return [
            'fleetFailure'   => isset($row['fleetFailure']) ? (int)$row['fleetFailure'] : 0,
            'nineNineNine' => isset($row['nineNineNine']) ? (int)$row['nineNineNine'] : 0,
            'spare' => isset($row['spare']) ? (int)$row['spare'] : 0,
            'anors' => isset($row['anors']) ? (int)$row['anors'] : 0,
            'casrep' => isset($row['casrep']) ? (int)$row['casrep'] : 0,
        ];
    }
    
    public function getYTDReqsRecvd(string $selectedProgram, string $ytdStart, string $ytdEnd): int
    {
        $db = new db();
        
        $sql = "
        SELECT
            COUNT(*) AS ytdReqsRecvd
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_shipped BETWEEN ? AND ?
    ";
        
        $row = $db->query($sql, $selectedProgram, $ytdStart, $ytdEnd)->fetchArray();
        
        $db->close();
        
        return isset($row['ytdReqsRecvd']) ? (int)$row['ytdReqsRecvd'] : 0;
    }
    
    public function getYTDUniqueNiins(string $selectedProgram, string $ytdStart, string $ytdEnd): int
    {
        $db = new db();
        
        $sql = "
        SELECT
            COUNT(DISTINCT niin) AS ytdUniqueNiins
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_shipped BETWEEN ? AND ?
    ";
        
        $row = $db->query($sql, $selectedProgram, $ytdStart, $ytdEnd)->fetchArray();
        
        $db->close();
        
        return isset($row['ytdUniqueNiins']) ? (int)$row['ytdUniqueNiins'] : 0;
    }
    
    public function getYTDTotalNiins(string $selectedProgram, string $ytdStart, string $ytdEnd): int
    {
        $db = new db();
        
        $sql = "
        SELECT
            SUM(qty) AS ytdTotalNiins
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_shipped BETWEEN ? AND ?
    ";
        
        $row = $db->query($sql, $selectedProgram, $ytdStart, $ytdEnd)->fetchArray();
        
        $db->close();
        
        return isset($row['ytdTotalNiins']) ? (int)$row['ytdTotalNiins'] : 0;
    }
    
    public function getNiinChangeReqs(string $selectedProgram, string $startDate, string $endDate): int
    {
        $db = new db();
        
        $sql = "
        SELECT
            COUNT(*) AS niinChangeReqs
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_recv BETWEEN ? AND ?
          AND cav_requisitions.status = 'NIIN CHANGE'
    ";
        
        $row = $db->query($sql, $selectedProgram, $startDate, $endDate)->fetchArray();
        
        $db->close();
        
        return isset($row['niinChangeReqs']) ? (int)$row['niinChangeReqs'] : 0;
    }
    
    public function getCanceledReqs(string $selectedProgram, string $startDate, string $endDate): int
    {
        $db = new db();
        
        $sql = "
        SELECT
            COUNT(*) AS canceledReqs
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_recv BETWEEN ? AND ?
          AND cav_requisitions.status = 'CANCELED'
    ";
        
        $row = $db->query($sql, $selectedProgram, $startDate, $endDate)->fetchArray();
        
        $db->close();
        
        return isset($row['canceledReqs']) ? (int)$row['canceledReqs'] : 0;
    }
    
    public function getPendingReqs(string $selectedProgram, string $startDate, string $endDate): int
    {
        $db = new db();
        
        $sql = "
        SELECT
            COUNT(*) AS pendingReqs
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_recv BETWEEN ? AND ?
          AND cav_requisitions.status = 'PENDING'
    ";
        
        $row = $db->query($sql, $selectedProgram, $startDate, $endDate)->fetchArray();
        
        $db->close();
        
        return isset($row['pendingReqs']) ? (int)$row['pendingReqs'] : 0;
    }
    
    public function getDISReqs(string $selectedProgram, string $startDate, string $endDate): int
    {
        $db = new db();
        
        $sql = "
        SELECT
            COUNT(*) AS disReqs
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_recv BETWEEN ? AND ?
          AND cav_requisitions.priority IN ('DRMO', 'I.O.', 'SURGE BUY')
    ";
        
        $row = $db->query($sql, $selectedProgram, $startDate, $endDate)->fetchArray();
        
        $db->close();
        
        return isset($row['disReqs']) ? (int)$row['disReqs'] : 0;
    }
    
    public function getBackorderReqs(string $selectedProgram, string $startDate, string $endDate): int
    {
        $db = new db();
        
        $sql = "
        SELECT
            COUNT(*) AS backorderReqs
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_recv BETWEEN ? AND ?
          AND cav_requisitions.status = 'BACKORDERED'
    ";
        
        $row = $db->query($sql, $selectedProgram, $startDate, $endDate)->fetchArray();
        
        $db->close();
        
        return isset($row['backorderReqs']) ? (int)$row['backorderReqs'] : 0;
    }
    
    public function getYTDTwoSeventyReqs(string $selectedProgram, string $ytdStart, string $ytdEnd): int
    {
        $db = new db();
        
        $sql = "
        SELECT
            COUNT(*) AS ytdTwoSeventyReqs
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_shipped BETWEEN ? AND ?
          AND cav_requisitions.rt >= '270'
    ";
        
        $row = $db->query($sql, $selectedProgram, $ytdStart, $ytdEnd)->fetchArray();
        
        $db->close();
        
        return isset($row['ytdTwoSeventyReqs']) ? (int)$row['ytdTwoSeventyReqs'] : 0;
    }
    
    public function getYTDFillRateGood(string $selectedProgram, string $ytdStart, string $ytdEnd): int
    {
        $db = new db();
        
        $sql = "
        SELECT
            COUNT(*) AS ytdFillRateGood
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_shipped BETWEEN ? AND ?
          AND cav_requisitions.on_time = 1
    ";
        
        $row = $db->query($sql, $selectedProgram, $ytdStart, $ytdEnd)->fetchArray();
        
        $db->close();
        
        return isset($row['ytdFillRateGood']) ? (int)$row['ytdFillRateGood'] : 0;
    }
    
    public function getYTDFillRateMissed(string $selectedProgram, string $ytdStart, string $ytdEnd): int
    {
        $db = new db();
        
        $sql = "
        SELECT
            COUNT(*) AS ytdFillRateMissed
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_shipped BETWEEN ? AND ?
          AND cav_requisitions.on_time = 0
    ";
        
        $row = $db->query($sql, $selectedProgram, $ytdStart, $ytdEnd)->fetchArray();
        
        $db->close();
        
        return isset($row['ytdFillRateMissed']) ? (int)$row['ytdFillRateMissed'] : 0;
    }
    
    public function getYTDCasrepRT(string $selectedProgram, string $ytdStart, string $ytdEnd): float
    {
        $db = new db();
        
        $sql = "
        SELECT
            AVG(rt) AS ytdCasrepRT
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_shipped BETWEEN ? AND ?
          AND cav_requisitions.priority = 'CASREP'
    ";
        
        $row = $db->query($sql, $selectedProgram, $ytdStart, $ytdEnd)->fetchArray();
        
        $db->close();
        
        return isset($row['ytdCasrepRT']) ? (float)$row['ytdCasrepRT'],2 : 0.0;
    }
        
}
  
?>