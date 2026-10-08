<?php
include_once APP_ROOT . '/bin/Utilities/db.php';
include_once APP_ROOT . '/bin/Utilities/helpers.php';

/**
 * Installed parts analysis for shelf-support planning.
 *
 * Uses the installed table (parts consumed on repair WOs) plus current
 * inventory on-hand to show FY install activity and recommend shelf stock.
 */
class Installed
{
    /**
     * Default months of coverage used when calculating recommended shelf qty.
     * Adjust here if leadership wants a different buffer (e.g. 6).
     */
    private const COVERAGE_MONTHS = 9;

    /**
     * Aggregate installs for a fiscal year range, join current shelf stock,
     * and compute a recommended on-shelf quantity based on install rate.
     *
     * @param string $startDate YYYY-MM-DD (FY start, typically 1 Oct)
     * @param string $endDate   YYYY-MM-DD (FY end, typically 30 Sep)
     * @return array
     */
    public function getInstalledSupportByFiscalYear(string $startDate, string $endDate): array
    {
        $db = new db();

        // Months in the selected FY window (used for average monthly rate).
        // Cap at 12 so partial current FY still produces a sensible rate.
        $start = new DateTime($startDate);
        $end   = new DateTime($endDate);
        $monthsInRange = max(1, (int)$start->diff($end)->m + ($start->diff($end)->y * 12) + 1);
        if ($monthsInRange > 12) {
            $monthsInRange = 12;
        }

        $coverageMonths = self::COVERAGE_MONTHS;

        /*
         * Query strategy:
         *  1) Aggregate installs in the FY by partinstalled
         *  2) Left-join total on-hand from inventory (all condition codes)
         *  3) Also pull prior-FY installs for a simple trend signal
         *  4) Compute avg monthly rate and recommended shelf qty in SQL
         */
        $sql = "
        SELECT
            i.partinstalled                                          AS 'Part',
            COALESCE(MAX(i.partnomen), inv.nomen, '')                AS 'Nomen',
            COALESCE(inv.niin, '')                                   AS 'NIIN',
            SUM(i.qtyinstalled)                                      AS 'Qty Installed FY',
            COALESCE(MAX(prior.prior_qty), 0)                        AS 'Qty Installed Prior FY',
            COALESCE(inv.on_hand, 0)                                 AS 'On Shelf Qty',
            ROUND(SUM(i.qtyinstalled) / ?, 2)                        AS 'Avg Monthly Installs',
            CEIL((SUM(i.qtyinstalled) / ?) * ?)                      AS 'Recommended Shelf Qty',
            CEIL((SUM(i.qtyinstalled) / ?) * ?) - COALESCE(inv.on_hand, 0)
                                                                     AS 'Shelf Gap',
            COALESCE(inv.unit_price, MAX(i.partcost), 0)             AS 'Unit Price'
        FROM installed i
        LEFT JOIN (
            SELECT
                primarypartno,
                MAX(description)   AS nomen,
                MAX(niin)          AS niin,
                SUM(onhandqty)     AS on_hand,
                MAX(averageprice)  AS unit_price
            FROM inventory
            WHERE onhandqty IS NOT NULL
            GROUP BY primarypartno
        ) inv ON inv.primarypartno = i.partinstalled
        LEFT JOIN (
            SELECT
                partinstalled,
                SUM(qtyinstalled) AS prior_qty
            FROM installed
            WHERE createdate BETWEEN DATE_SUB(?, INTERVAL 1 YEAR) AND DATE_SUB(?, INTERVAL 1 YEAR)
            GROUP BY partinstalled
        ) prior ON prior.partinstalled = i.partinstalled
        WHERE i.createdate BETWEEN ? AND ?
          AND i.partinstalled IS NOT NULL
          AND TRIM(i.partinstalled) <> ''
        GROUP BY i.partinstalled, inv.nomen, inv.niin, inv.on_hand, inv.unit_price
        ORDER BY SUM(i.qtyinstalled) DESC, i.partinstalled ASC
        ";

        $results = $db->query(
            $sql,
            $monthsInRange,          // avg monthly divisor
            $monthsInRange,          // recommended divisor
            $coverageMonths,         // coverage multiplier
            $monthsInRange,          // gap divisor
            $coverageMonths,         // gap multiplier
            $startDate,              // prior FY start (DATE_SUB)
            $endDate,                // prior FY end   (DATE_SUB)
            $startDate,              // current FY start
            $endDate                 // current FY end
        )->fetchAll();

        $db->close();

        return $results;
    }

    /**
     * Summary totals for the FY (header cards / export footer).
     */
    public function getInstalledSupportSummary(string $startDate, string $endDate): array
    {
        $rows = $this->getInstalledSupportByFiscalYear($startDate, $endDate);

        $summary = [
            'distinct_parts'       => count($rows),
            'total_qty_installed'  => 0,
            'total_on_shelf'       => 0,
            'total_recommended'    => 0,
            'parts_short'          => 0,   // shelf gap > 0
            'parts_ok'             => 0,
        ];

        foreach ($rows as $row) {
            $summary['total_qty_installed'] += (int)($row['Qty Installed FY'] ?? 0);
            $summary['total_on_shelf']      += (int)($row['On Shelf Qty'] ?? 0);
            $summary['total_recommended']   += (int)($row['Recommended Shelf Qty'] ?? 0);

            $gap = (int)($row['Shelf Gap'] ?? 0);
            if ($gap > 0) {
                $summary['parts_short']++;
            } else {
                $summary['parts_ok']++;
            }
        }

        return $summary;
    }
}