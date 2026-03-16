<?php
function renderTable(array $rows, ?array $headers = null, string $emptyMessage = 'No records found.'): void
{
    if (empty($rows)) {
        echo '<p>' . htmlspecialchars($emptyMessage) . '</p>';
        return;
    }
    
    if ($headers === null) {
        $headers = array_keys($rows[0]);
    }
    
    echo '<table>';
    echo '<thead><tr>';
    
    foreach ($headers as $header) {
        echo '<th>' . htmlspecialchars($header) . '</th>';
    }
    
    echo '</tr></thead>';
    echo '<tbody>';
    
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($headers as $header) {
            $value = $row[$header] ?? '';
            echo '<td>' . htmlspecialchars((string)$value) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
}