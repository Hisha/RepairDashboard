<?php
require_once APP_ROOT . '/bin/Utilities/db.php';
require_once APP_ROOT . '/bin/Utilities/signature_helper.php';

class DriveDestruction
{
    protected string $_tableName = 'drive_destruction_log';
    protected string $_auditTable = 'drive_destruction_audit';
    
    public function getRecords(array $filters = []): array
    {
        $db = new db();
        
        $sql = "
            SELECT
                id,
                part_number,
                serial_number,
                niin,
                description,
                quantity,
                destruction_method,
                destruction_date,
                destroyer_name,
                destroyer_signature_path,
                destroyer_signed_at,
                witness_name,
                witness_signature_path,
                witness_signed_at,
                status,
                notes,
                created_at,
                created_by,
                updated_at
            FROM {$this->_tableName}
            WHERE 1 = 1
        ";
        
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= "
                AND (
                    part_number LIKE ?
                    OR serial_number LIKE ?
                    OR COALESCE(niin, '') LIKE ?
                    OR COALESCE(description, '') LIKE ?
                    OR COALESCE(destroyer_name, '') LIKE ?
                    OR COALESCE(witness_name, '') LIKE ?
                )
            ";
            $search = '%' . $filters['search'] . '%';
            for ($i = 0; $i < 6; $i++) {
                $params[] = $search;
            }
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = ? ";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['method'])) {
            $sql .= " AND destruction_method = ? ";
            $params[] = $filters['method'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND destruction_date >= ? ";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND destruction_date <= ? ";
            $params[] = $filters['date_to'];
        }
        
        $allowedSorts = [
            'id',
            'part_number',
            'serial_number',
            'niin',
            'destruction_date',
            'destruction_method',
            'destroyer_name',
            'witness_name',
            'status',
            'created_at',
            'updated_at'
        ];
        
        $sort = $filters['sort'] ?? 'destruction_date';
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'destruction_date';
        }
        
        $dir = strtoupper($filters['dir'] ?? 'DESC');
        $dir = ($dir === 'ASC') ? 'ASC' : 'DESC';
        
        $sql .= " ORDER BY {$sort} {$dir}, id DESC ";
        
        if (!empty($params)) {
            $result = call_user_func_array([$db, 'query'], array_merge([$sql], $params))->fetchAll();
        } else {
            $result = $db->query($sql)->fetchAll();
        }
        
        $db->close();
        return $result;
    }
    
    public function getRecordById(int $id): ?array
    {
        $db = new db();
        
        $sql = "SELECT * FROM {$this->_tableName} WHERE id = ?";
        $row = $db->query($sql, $id)->fetchArray();
        
        $db->close();
        return !empty($row) ? $row : null;
    }
    
    public function createRecord(array $data): int
    {
        $db = new db();
        
        $sql = "
            INSERT INTO {$this->_tableName} (
                part_number,
                serial_number,
                niin,
                description,
                quantity,
                destruction_method,
                destruction_date,
                notes,
                status,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)
        ";
        
        $partNumber = trim($data['part_number']);
        $serialNumber = trim($data['serial_number']);
        $niin = $this->nullIfEmpty($data['niin'] ?? null);
        $description = $this->nullIfEmpty($data['description'] ?? null);
        $quantity = intval($data['quantity'] ?? 1);
        $method = trim($data['destruction_method']);
        $date = trim($data['destruction_date']);
        $notes = $this->nullIfEmpty($data['notes'] ?? null);
        $createdBy = $this->nullIfEmpty($data['created_by'] ?? null);
        
        $db->query(
            $sql,
            $partNumber,
            $serialNumber,
            $niin,
            $description,
            $quantity,
            $method,
            $date,
            $notes,
            $createdBy
            );
        
        $recordId = (int)$db->lastInsertID();
        $db->close();
        
        $this->addAuditEntry(
            $recordId,
            'Record Created',
            $createdBy,
            'Initial destruction record created.'
            );
        
        return $recordId;
    }
    
    public function signDestroyer(int $id, string $fullName): void
    {
        $record = $this->getRecordById($id);
        if (!$record) {
            throw new Exception('Record not found.');
        }
        
        if ($record['status'] === 'Completed' || $record['status'] === 'Voided') {
            throw new Exception('This record can no longer be signed.');
        }
        
        $fullName = SignatureHelper::sanitizeName($fullName);
        $signaturePath = SignatureHelper::generateSignatureImage($fullName, $id, 'destroyer');
        
        $db = new db();
        
        $sql = "
            UPDATE {$this->_tableName}
            SET
                destroyer_name = ?,
                destroyer_signature_path = ?,
                destroyer_signed_at = NOW(),
                status = CASE
                    WHEN witness_name IS NOT NULL AND witness_name <> '' THEN 'Completed'
                    ELSE 'Partially Signed'
                END
            WHERE id = ?
        ";
        
        $db->query($sql, $fullName, $signaturePath, $id);
        $db->close();
        
        $this->addAuditEntry(
            $id,
            'Destroyer Signed',
            $fullName,
            'Destroyer attestation added.'
            );
    }
    
    public function signWitness(int $id, string $fullName): void
    {
        $record = $this->getRecordById($id);
        if (!$record) {
            throw new Exception('Record not found.');
        }
        
        if ($record['status'] === 'Completed' || $record['status'] === 'Voided') {
            throw new Exception('This record can no longer be signed.');
        }
        
        $fullName = SignatureHelper::sanitizeName($fullName);
        $signaturePath = SignatureHelper::generateSignatureImage($fullName, $id, 'witness');
        
        $db = new db();
        
        $sql = "
            UPDATE {$this->_tableName}
            SET
                witness_name = ?,
                witness_signature_path = ?,
                witness_signed_at = NOW(),
                status = CASE
                    WHEN destroyer_name IS NOT NULL AND destroyer_name <> '' THEN 'Completed'
                    ELSE 'Partially Signed'
                END
            WHERE id = ?
        ";
        
        $db->query($sql, $fullName, $signaturePath, $id);
        $db->close();
        
        $this->addAuditEntry(
            $id,
            'Witness Signed',
            $fullName,
            'Witness attestation added.'
            );
    }
    
    public function voidRecord(int $id, ?string $performedBy = null): void
    {
        $record = $this->getRecordById($id);
        if (!$record) {
            throw new Exception('Record not found.');
        }
        
        $db = new db();
        $sql = "UPDATE {$this->_tableName} SET status = 'Voided' WHERE id = ?";
        $db->query($sql, $id);
        $db->close();
        
        $this->addAuditEntry(
            $id,
            'Record Voided',
            $performedBy,
            'Record marked as voided.'
            );
    }
    
    public function getAuditEntries(int $recordId): array
    {
        $db = new db();
        
        $sql = "
            SELECT id, record_id, action, performed_by, action_timestamp, details
            FROM {$this->_auditTable}
            WHERE record_id = ?
            ORDER BY action_timestamp DESC, id DESC
        ";
        
        $rows = $db->query($sql, $recordId)->fetchAll();
        $db->close();
        
        return $rows;
    }
    
    public function addAuditEntry(int $recordId, string $action, ?string $performedBy = null, ?string $details = null): void
    {
        $db = new db();
        
        $sql = "
            INSERT INTO {$this->_auditTable} (
                record_id,
                action,
                performed_by,
                details
            ) VALUES (?, ?, ?, ?)
        ";
        
        $db->query($sql, $recordId, $action, $performedBy, $details);
        $db->close();
    }
    
    protected function nullIfEmpty($value): ?string
    {
        if ($value === null) {
            return null;
        }
        
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }
}