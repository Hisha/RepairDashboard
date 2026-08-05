<?php

require_once APP_ROOT . '/bin/Utilities/db.php';

class Battery853Labels
{
    /**
     * Create a batch of new battery labels.
     *
     * @return array<int, array<string, mixed>>
     */
    public function createBatch(string $buildDate, int $quantity): array
    {
        $validatedDate = $this->validateBuildDate($buildDate);

        if ($quantity < 1 || $quantity > 100) {
            throw new InvalidArgumentException(
                'Quantity must be between 1 and 100.'
            );
        }

        $nextChargeDate = $validatedDate
            ->modify('+365 days')
            ->format('Y-m-d');

        $lifeEndDate = $validatedDate
            ->modify('+1460 days')
            ->format('Y-m-d');

        $normalizedBuildDate = $validatedDate->format('Y-m-d');

        $db = new db();
        $createdLabels = [];

        try {
            $db->query('START TRANSACTION');

            for ($i = 0; $i < $quantity; $i++) {
                /*
                 * Serial is temporarily NULL so MariaDB can allocate the
                 * auto-increment ID first.
                 *
                 * This requires serial to allow NULL during creation.
                 */
                $insertSql = "
                    INSERT INTO battery_853_labels (
                        build_date,
                        next_charge_date,
                        life_end_date,
                        serial
                    ) VALUES (
                        '{$normalizedBuildDate}',
                        '{$nextChargeDate}',
                        '{$lifeEndDate}',
                        NULL
                    )
                ";

                $db->query($insertSql);

                $idResult = $db->query("
                    SELECT LAST_INSERT_ID() AS new_id
                ")->fetchAll();

                if (
                    empty($idResult) ||
                    !isset($idResult[0]['new_id'])
                ) {
                    throw new RuntimeException(
                        'MariaDB did not return the new label ID.'
                    );
                }

                $newId = (int)$idResult[0]['new_id'];
                $serial = 'NIWC000' . $newId;

                $updateSql = "
                    UPDATE battery_853_labels
                    SET serial = '{$serial}'
                    WHERE id = {$newId}
                ";

                $db->query($updateSql);

                $createdLabels[] = [
                    'id' => $newId,
                    'build_date' => $normalizedBuildDate,
                    'next_charge_date' => $nextChargeDate,
                    'life_end_date' => $lifeEndDate,
                    'serial' => $serial,
                ];
            }

            $db->query('COMMIT');
        } catch (Throwable $exception) {
            try {
                $db->query('ROLLBACK');
            } catch (Throwable $rollbackException) {
                // Preserve the original exception.
            }

            $db->close();
            throw $exception;
        }

        $db->close();

        return $createdLabels;
    }

    private function validateBuildDate(
        string $buildDate
    ): DateTimeImmutable {
        $buildDate = trim($buildDate);

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $buildDate
        );

        $errors = DateTimeImmutable::getLastErrors();

        /*
         * getLastErrors() returns false when there are no errors
         * in newer PHP versions.
         */
        $hasErrors = is_array($errors) && (
            $errors['warning_count'] > 0 ||
            $errors['error_count'] > 0
        );

        if (
            !$date ||
            $hasErrors ||
            $date->format('Y-m-d') !== $buildDate
        ) {
            throw new InvalidArgumentException(
                'A valid build date is required.'
            );
        }

        return $date;
    }
}