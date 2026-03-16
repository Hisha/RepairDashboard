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
        
        return isset($row['ytdCasrepRT']) ? round((float)$row['ytdCasrepRT'], 2) : 0.0;
    }
    
    public function getYTDAllRT(string $selectedProgram, string $ytdStart, string $ytdEnd): float
    {
        $db = new db();
        
        $sql = "
        SELECT
            AVG(rt) AS ytdAllRT
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_shipped BETWEEN ? AND ?
          AND cav_requisitions.priority IN ('CASREP', 'ANORS', 'SPARE', '999', 'FLEET FAILURE')
    ";
        
        $row = $db->query($sql, $selectedProgram, $ytdStart, $ytdEnd)->fetchArray();
        
        $db->close();
        
        return isset($row['ytdAllRT']) ? round((float)$row['ytdAllRT'], 2) : 0.0;
    }
    
    public function getYTDNonCasrepRT(string $selectedProgram, string $ytdStart, string $ytdEnd): float
    {
        $db = new db();
        
        $sql = "
        SELECT
            AVG(rt) AS ytdNonCasrepRT
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_shipped BETWEEN ? AND ?
          AND cav_requisitions.priority IN ('ANORS', 'SPARE', '999', 'FLEET FAILURE')
    ";
        
        $row = $db->query($sql, $selectedProgram, $ytdStart, $ytdEnd)->fetchArray();
        
        $db->close();
        
        return isset($row['ytdNonCasrepRT']) ? round((float)$row['ytdNonCasrepRT'], 2) : 0.0;
    }
    
    public function getYTDBOShippedRT(string $selectedProgram, string $ytdStart, string $ytdEnd): float
    {
        $db = new db();
        
        $sql = "
        SELECT
            AVG(rt) AS ytdBOShippedRT
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_shipped BETWEEN ? AND ?
          AND cav_requisitions.status = 'B/O SHIPPED'
    ";
        
        $row = $db->query($sql, $selectedProgram, $ytdStart, $ytdEnd)->fetchArray();
        
        $db->close();
        
        return isset($row['ytdBOShippedRT']) ? round((float)$row['ytdBOShippedRT'], 2) : 0.0;
    }
    
    public function getYTDDemandMisses(string $selectedProgram, string $ytdStart, string $ytdEnd): array
    {
        $labels = [];
        $demand = [];
        $misses = [];
        $fillRate = [];
        $goal = [];
        
        $current = strtotime($ytdStart);
        $end = strtotime($ytdEnd);
        
        while ($current <= $end) {
            $monthStart = date('Y-m-01', $current);
            $monthEnd = date('Y-m-t', $current);
            $monthLabel = date('M Y', $current);
            
            $labels[] = $monthLabel;
            
            $good = $this->getYTDFillRateGood($selectedProgram, $monthStart, $monthEnd);
            $missed = $this->getYTDFillRateMissed($selectedProgram, $monthStart, $monthEnd);
            
            $demand[] = $good;
            $misses[] = $missed;
            
            $total = $good + $missed;
            $fillRate[] = $total > 0 ? round(($good / $total) * 100, 1) : 0;
            $goal[] = 85;
            
            $current = strtotime('+1 month', $current);
        }
        
        return [
            'labels' => $labels,
            'demand' => $demand,
            'misses' => $misses,
            'fillRate' => $fillRate,
            'goal' => $goal
        ];
    }
    
    public function getYTDYearlyAverages(string $selectedProgram, string $ytdStart, string $ytdEnd): array
    {
        $labels = [];
        $boshipped = [];
        $casreprt = [];
        $noncasreprt = [];
        $allrt = [];
        $noncasrepgoal = [];
        $casrepgoal = [];
        
        $current = strtotime($ytdStart);
        $end = strtotime($ytdEnd);
        
        while ($current <= $end) {
            $monthStart = date('Y-m-01', $current);
            $monthEnd = date('Y-m-t', $current);
            $monthLabel = date('M Y', $current);
            
            $labels[] = $monthLabel;
            
            $boshipped[] = $this->getYTDBOShippedRT($selectedProgram, $monthStart, $monthEnd);
            $casreprt[] = $this->getYTDCasrepRT($selectedProgram, $monthStart, $monthEnd);
            $noncasreprt[] = $this->getYTDNonCasrepRT($selectedProgram, $monthStart, $monthEnd);
            $allrt[] = $this->getYTDAllRT($selectedProgram, $monthStart, $monthEnd);
            $noncasrepgoal[] = 3;
            $casrepgoal[] = 1;
            
            $current = strtotime('+1 month', $current);
        }
        
        return [
            'labels' => $labels,
            'boshipped' => $boshipped,
            'casreprt' => $casreprt,
            'noncasreprt' => $noncasreprt,
            'allrt' => $allrt,
            'noncasrepgoal' => $noncasrepgoal,
            'casrepgoal' => $casrepgoal
        ];
    }
    
    public function getTop5ByPriority(string $selectedProgram, string $startDate, string $endDate, string|array $priorities): array
    {
        $db = new db();
        
        // Normalize to array
        if (!is_array($priorities)) {
            $priorities = [$priorities];
        }
        
        if (empty($priorities)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($priorities), '?'));
        
        $sql = "
        SELECT
            niin,
            nomen,
            SUM(qty) AS total
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_recv BETWEEN ? AND ?
          AND cav_requisitions.status IN ('SHIPPED', 'PICK UP')
          AND cav_requisitions.priority IN ($placeholders)
        GROUP BY cav_requisitions.niin, cav_requisitions.nomen
        ORDER BY total DESC
        LIMIT 5
    ";
        
        $params = array_merge([$selectedProgram, $startDate, $endDate], $priorities);
        
        $results = call_user_func_array([$db, 'query'], array_merge([$sql], $params))->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
    public function getTop5ByStatusByDate_Recv(string $selectedProgram, string $startDate, string $endDate, string|array $status): array
    {
        $db = new db();
        
        // Normalize to array
        if (!is_array($status)) {
            $status = [$status];
        }
        
        if (empty($status)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($status), '?'));
        
        $sql = "
        SELECT
            niin,
            nomen,
            SUM(qty) AS total
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_recv BETWEEN ? AND ?
          AND cav_requisitions.status IN ($placeholders)
        GROUP BY cav_requisitions.niin, cav_requisitions.nomen
        ORDER BY total DESC
        LIMIT 5
    ";
        
        $params = array_merge([$selectedProgram, $startDate, $endDate], $status);
        
        $results = call_user_func_array([$db, 'query'], array_merge([$sql], $params))->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
    public function getTop5ByStatusByDate_Shipped(string $selectedProgram, string $startDate, string $endDate, string|array $status): array
    {
        $db = new db();
        
        // Normalize to array
        if (!is_array($status)) {
            $status = [$status];
        }
        
        if (empty($status)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($status), '?'));
        
        $sql = "
        SELECT
            niin,
            nomen,
            SUM(qty) AS total
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
          AND cav_requisitions.date_shipped BETWEEN ? AND ?
          AND cav_requisitions.status IN ($placeholders)
        GROUP BY cav_requisitions.niin, cav_requisitions.nomen
        ORDER BY total DESC
        LIMIT 5
    ";
        
        $params = array_merge([$selectedProgram, $startDate, $endDate], $status);
        
        $results = call_user_func_array([$db, 'query'], array_merge([$sql], $params))->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
    public function getBackOrderListByDate_Recv(string $selectedProgram, string $startDate, string $endDate): array
    {
        $db = new db();
        
        $sql = "
        SELECT
            CONCAT(niin, ' (', nomen, ')') AS label,
            SUM(qty) AS total
        FROM cav_requisitions
        INNER JOIN SYS_program_mapping
            ON cav_requisitions.program = SYS_program_mapping.source_program
        WHERE SYS_program_mapping.normalized_program = ?
            AND cav_requisitions.date_recv BETWEEN ? AND ?
            AND cav_requisitions.status = 'BACKORDERED'
        GROUP BY niin, nomen
        ORDER BY total DESC
    ";
        
        $results = $db->query($sql, $selectedProgram, $startDate, $endDate)->fetchAll();
        
        $db->close();
        
        return $results;
    }
    
}
  
?>