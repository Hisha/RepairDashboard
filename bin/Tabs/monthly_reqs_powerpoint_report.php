<h2>Monthly Requisitions Report</h2>

<?php if ($message !== ''): ?>
    <div class="success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-block">
    <form method="post" action="monthly_reqs.php">
        <input type="hidden" name="tab" value="powerpoint_report">

        <div class="form-row">
            <label for="ddlDistinctNormalizedProgram">Program</label>
            <?= $programMapping->getDDLDistinctNormalizedProgram($selectedProgram); ?>
        </div>

        <div class="form-row">
            <label for="ddlRecvMonth">Reporting Month</label>
            <?= $cavRequisitions->getDDLRecvMonths($selectedMonth); ?>
        </div>

        <div class="form-row">
            <button type="submit" name="btnGenerateReport">Generate Report</button>
        </div>
    </form>
</div>