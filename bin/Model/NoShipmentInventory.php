<?php

require_once APP_ROOT . '/bin/Utilities/db.php';

class NoShipmentInventory
{
    public function getReport(): array
    {
        $db = new db();

        $sql = "
            SELECT 
                inventory.niin AS NIIN,
                inventory.description AS Nomen,
                inventory.primarypartno AS Part,
                SYS_repair_program_mapping.normalized_program AS Program,

                SUM(
                    CASE
                        WHEN inventory.materialcode = 'A'
                        THEN inventory.onhandqty
                        ELSE 0
                    END
                ) AS `A OnHand`,

                SUM(
                    CASE
                        WHEN inventory.materialcode = 'D'
                        THEN inventory.onhandqty
                        ELSE 0
                    END
                ) AS `D OnHand`,

                SUM(
                    CASE
                        WHEN inventory.materialcode = 'F'
                        THEN inventory.onhandqty
                        ELSE 0
                    END
                ) AS `F OnHand`,

                SUM(
                    CASE
                        WHEN inventory.materialcode = 'G'
                        THEN inventory.onhandqty
                        ELSE 0
                    END
                ) AS `G OnHand`,

                SUM(
                    CASE
                        WHEN inventory.materialcode = 'K'
                        THEN inventory.onhandqty
                        ELSE 0
                    END
                ) AS `K OnHand`,

                shipments_latest.LastShipDate

            FROM inventory

            INNER JOIN SYS_repair_program_mapping
                ON inventory.subgrouptype =
                   SYS_repair_program_mapping.source_program

            LEFT JOIN (
                SELECT
                    primarypartno,
                    MAX(transactiondate) AS LastShipDate
                FROM shipments
                GROUP BY primarypartno
            ) shipments_latest
                ON inventory.primarypartno =
                   shipments_latest.primarypartno

            WHERE SYS_repair_program_mapping.north_south = 'north'
              AND SYS_repair_program_mapping.normalized_program <> 'Repair'
              AND (
                    shipments_latest.LastShipDate IS NULL
                    OR shipments_latest.LastShipDate <
                       DATE_SUB(CURDATE(), INTERVAL 15 MONTH)
                  )

            GROUP BY 
                inventory.niin,
                inventory.description,
                inventory.primarypartno,
                SYS_repair_program_mapping.normalized_program,
                shipments_latest.LastShipDate

            HAVING
                   `A OnHand` <> 0
                OR `D OnHand` <> 0
                OR `F OnHand` <> 0
                OR `G OnHand` <> 0
                OR `K OnHand` <> 0

            ORDER BY
                inventory.niin
        ";

        $result = $db->query($sql)->fetchAll();
        $db->close();

        return $result;
    }
}