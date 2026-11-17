<?php
require_once __DIR__ . '/bootstrap.php';
require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/bin/Model/InternalNumbers.php';
require_once APP_ROOT . '/bin/Model/SYS_PowerPointFiller.php';
require_once APP_ROOT . '/bin/Model/SYS_ProgramMapping.php';

function niEsc($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function niPercent($part, $total): float { return (float)$total > 0 ? round((float)$part / (float)$total * 100, 2) : 0.0; }
function niTable(array $rows): void {
    if (!$rows) { echo '<p class="muted">No data for this period.</p>'; return; }
    $first = reset($rows);
    if (!is_array($first)) { echo '<p>' . niEsc(implode(', ', array_map('strval', $rows))) . '</p>'; return; }
    $keys = array_keys($first);
    echo '<div class="table-scroll"><table><thead><tr>';
    foreach ($keys as $key) echo '<th>' . niEsc($key) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($keys as $key) {
            $value = $row[$key] ?? '';
            echo '<td>' . niEsc(is_array($value) ? json_encode($value) : $value) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

$programMapping = new SYS_ProgramMapping();
$internalNumbers = new InternalNumbers();
$powerPointFiller = new SYS_PowerPointFiller();
$selectedProgram = trim((string)($_POST['ddlDistinctNormalizedProgram'] ?? ''));
$selectedMonth = trim((string)($_POST['ddlRecvMonth'] ?? ''));
$error = '';
$report = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnGenerateReport'])) {
    if ($selectedProgram === '' || $selectedMonth === '') {
        $error = 'Please select a program and reporting month.';
    } else {
        try {
            $dates = $internalNumbers->getReportDateRanges($selectedMonth);
            $filler = $powerPointFiller->getPPFiller($selectedProgram);
            if (isset($filler[0]) && is_array($filler[0])) $filler = $filler[0];
            $ms = $dates['month_start']; $me = $dates['month_end'];
            $ys = $dates['ytd_start']; $ye = $dates['ytd_end'];
            $shipped = (int)$internalNumbers->getPieData_Shipped($selectedProgram, $ms, $me);
            $boShipped = (int)$internalNumbers->getPieData_BOShipped($selectedProgram, $ms, $me);
            $doughnut = $internalNumbers->getShippedDoughnutData($selectedProgram, $ms, $me);
            $good = (int)$internalNumbers->getYTDFillRateGood($selectedProgram, $ys, $ye);
            $missed = (int)$internalNumbers->getYTDFillRateMissed($selectedProgram, $ys, $ye);
            $report = [
                'dates' => $dates, 'filler' => is_array($filler) ? $filler : [],
                'shipped' => $shipped, 'boShipped' => $boShipped, 'doughnut' => $doughnut,
                'totalReceived' => $internalNumbers->getYTDReqsRecvd($selectedProgram, $ys, $ye),
                'uniqueNiins' => $internalNumbers->getYTDUniqueNiins($selectedProgram, $ys, $ye),
                'totalNiins' => $internalNumbers->getYTDTotalNiins($selectedProgram, $ys, $ye),
                'over270' => $internalNumbers->getYTDTwoSeventyReqs($selectedProgram, $ys, $ye),
                'fillRate' => niPercent($good, $good + $missed),
                'casrepRT' => $internalNumbers->getYTDCasrepRT($selectedProgram, $ys, $ye),
                'allRT' => $internalNumbers->getYTDAllRT($selectedProgram, $ys, $ye),
                'exceptions' => [
                    'NIIN Changes' => $internalNumbers->getNiinChangeReqs($selectedProgram, $ms, $me),
                    'Canceled Reqs' => $internalNumbers->getCanceledReqs($selectedProgram, $ms, $me),
                    'Pending Reqs' => $internalNumbers->getPendingReqs($selectedProgram, $ms, $me),
                    'DRMO, I.O, Surge Buy Reqs' => $internalNumbers->getDISReqs($selectedProgram, $ms, $me),
                    'ESRS Case Reqs' => $internalNumbers->getESRSReqs($selectedProgram, $ms, $me),
                    'New Reqs on Back Order' => $internalNumbers->getBackorderReqs($selectedProgram, $ms, $me),
                ],
                'demand' => $internalNumbers->getYTDDemandMisses($selectedProgram, $ys, $ye),
                'averages' => $internalNumbers->getYTDYearlyAverages($selectedProgram, $ys, $ye),
                'top5' => [
                    'CASREPs' => $internalNumbers->getTop5ByPriority($selectedProgram, $ms, $me, 'CASREP'),
                    'ANORS / 999' => $internalNumbers->getTop5ByPriority($selectedProgram, $ms, $me, ['999', 'ANORS']),
                    'Fleet Failure' => $internalNumbers->getTop5ByPriority($selectedProgram, $ms, $me, 'Fleet Failure'),
                    'Backorders' => $internalNumbers->getTop5ByStatusByDate_Recv($selectedProgram, $ms, $me, 'BACKORDERED'),
                    'B/O Shipped' => $internalNumbers->getTop5ByStatusByDate_Shipped($selectedProgram, $ms, $me, 'B/O SHIPPED'),
                    'Shipped / Pick Up' => $internalNumbers->getTop5ByStatusByDate_Shipped($selectedProgram, $ms, $me, ['SHIPPED', 'PICK UP']),
                ],
                'backorders' => $internalNumbers->getBackOrderListByDate_Recv($selectedProgram, $ms, $me),
            ];
        } catch (Throwable $e) {
            error_log('numbersInternal report error: ' . $e->getMessage());
            $error = 'Unable to generate the report. Please check the server error log.';
        }
    }
}
include 'menu.php';
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Internal Program Report</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#f8f9fa;color:#212529;margin:20px}.wrap{max-width:1500px;margin:auto}
.panel{background:white;border:1px solid #d6dce2;border-radius:9px;padding:22px;margin:18px 0;box-shadow:0 1px 3px #00000008}
.form-row{display:flex;align-items:end;gap:18px;flex-wrap:wrap}.field{display:flex;flex-direction:column;gap:7px}.field label{font-weight:700}
select,button{padding:10px 12px;font-size:15px;border-radius:5px;border:1px solid #bfc7cf}button{background:#0d6efd;color:white;border-color:#0d6efd;cursor:pointer;font-weight:700}
.error{background:#f8d7da;color:#842029;padding:12px;border-radius:5px}.muted{color:#697586}.meta{color:#5c6672}
.metrics{display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:13px}.metric{border:1px solid #dce2e8;border-radius:8px;padding:16px;background:#f8fafc}
.metric .value{font-size:28px;font-weight:700;color:#244a78;margin-top:8px}.metric .name{font-size:13px;color:#4f5d6b}
.charts{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,460px),1fr));gap:18px}.chartbox{position:relative;height:340px}
.table-scroll{overflow:auto;max-height:500px}table{border-collapse:collapse;width:100%;font-size:14px}th,td{padding:9px 12px;text-align:left;border-bottom:1px solid #e0e5ea}th{background:#eef2f6;position:sticky;top:0}tr:nth-child(even){background:#fafbfd}
.subgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,430px),1fr));gap:15px}.subpanel{border:1px solid #e0e5ea;border-radius:7px;padding:14px;min-width:0}h1{margin:0 0 12px}h2{margin:0 0 16px}h3{margin:0 0 10px}
@media print{.form-panel,button{display:none}.panel{break-inside:avoid;box-shadow:none}}
</style></head><body><div class="wrap">
<h1>Internal Program Report</h1>
<div class="panel form-panel"><form method="post" action="numbersInternal.php" class="form-row">
<div class="field"><label for="ddlDistinctNormalizedProgram">Program</label><?= $programMapping->getDDLDistinctNormalizedProgram($selectedProgram) ?></div>
<div class="field"><label for="ddlRecvMonth">Reporting Month</label><?= $cavRequisitions->getDDLRecvMonths($selectedMonth) ?></div>
<button type="submit" name="btnGenerateReport" value="1">Generate Report</button></form></div>
<?php if ($error !== ''): ?><div class="error"><?= niEsc($error) ?></div><?php endif; ?>
<?php if ($report !== null): $d = $report['dates']; $f = $report['filler']; ?>
<div class="panel"><h2><?= niEsc($f['title'] ?? $selectedProgram) ?> — <?= niEsc($d['month_label'] ?? $selectedMonth) ?></h2>
<p class="meta"><?= niEsc(trim(($f['pm'] ?? '') . ' ' . ($f['programname'] ?? ''))) ?></p>
<p><strong>Month:</strong> <?= niEsc($d['month_start']) ?> to <?= niEsc($d['month_end']) ?> &nbsp; <strong>YTD:</strong> <?= niEsc($d['ytd_start']) ?> to <?= niEsc($d['ytd_end']) ?></p></div>
<div class="panel"><h2>Requisitions Shipped</h2><div class="metrics">
<?php foreach (['Shipped'=>$report['shipped'],'B/O Shipped'=>$report['boShipped'],'Total Shipped'=>$report['shipped']+$report['boShipped'],'Shipped %'=>niPercent($report['shipped'],$report['shipped']+$report['boShipped']).'%'] as $label=>$value): ?>
<div class="metric"><div class="name"><?= niEsc($label) ?></div><div class="value"><?= niEsc($value) ?></div></div><?php endforeach; ?></div>
<div class="charts"><div><h3>Shipment Status</h3><div class="chartbox"><canvas id="shipChart"></canvas></div></div><div><h3>Shipped by Priority</h3><div class="chartbox"><canvas id="priorityChart"></canvas></div></div></div></div>
<div class="panel"><h2>Year-to-Date Performance</h2><div class="metrics">
<?php foreach (['Requisitions Received'=>$report['totalReceived'],'Unique NIINs'=>$report['uniqueNiins'],'Total NIINs'=>$report['totalNiins'],'Requisitions Over 270 Days'=>$report['over270'],'Fill Rate'=>$report['fillRate'].'%','CASREP RT Average'=>$report['casrepRT'],'All RT Average'=>$report['allRT']] as $label=>$value): ?>
<div class="metric"><div class="name"><?= niEsc($label) ?></div><div class="value"><?= niEsc($value) ?></div></div><?php endforeach; ?></div>
<p class="muted">MOA targets from the original report: 85% fill rate, CASREP response under 1 day, non-CASREP response under 3 days, backorders under 90 days, and no requisitions at or beyond 270 days.</p></div>
<div class="panel"><h2>Monthly Exceptions</h2><div class="metrics">
<?php foreach ($report['exceptions'] as $label=>$value): ?><div class="metric"><div class="name"><?= niEsc($label) ?></div><div class="value"><?= niEsc($value) ?></div></div><?php endforeach; ?></div></div>
<div class="panel"><h2>YTD Demand, Misses and Fill Rate</h2><div class="chartbox"><canvas id="demandChart"></canvas></div><h3>Monthly Data</h3><?php
$monthly = []; $demand = $report['demand'];
foreach (($demand['labels'] ?? []) as $i=>$label) $monthly[] = ['Month'=>$label,'Demand'=>$demand['demand'][$i] ?? '','Misses'=>$demand['misses'][$i] ?? '','Fill Rate %'=>$demand['fillRate'][$i] ?? ''];
niTable($monthly);
?></div>
<div class="panel"><h2>YTD Response Time Trends</h2><div class="chartbox"><canvas id="averagesChart"></canvas></div><h3>Monthly Averages</h3><?php
$avg = $report['averages']; $avgRows=[];
foreach (($avg['labels'] ?? []) as $i=>$label) $avgRows[]=['Month'=>$label,'B/O Shipped RT'=>$avg['boshipped'][$i] ?? '','CASREP RT'=>$avg['casreprt'][$i] ?? '','Non-CASREP RT'=>$avg['noncasreprt'][$i] ?? '','All RT'=>$avg['allrt'][$i] ?? ''];
niTable($avgRows);
?></div>
<div class="panel"><h2>Most Requested Parts</h2><div class="subgrid">
<?php foreach ($report['top5'] as $title=>$rows): ?><div class="subpanel"><h3><?= niEsc($title) ?></h3><?php niTable(is_array($rows) ? $rows : []); ?></div><?php endforeach; ?>
</div></div>
<div class="panel"><h2>Backorders by Date Received</h2><div class="chartbox" style="height:<?= max(350, count($report['backorders'])*32) ?>px"><canvas id="backorderChart"></canvas></div>
<?php niTable($report['backorders']); ?></div>
<script>
const r = <?= json_encode($report, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE) ?>;
const palette=['#3b6fb6','#c0392b','#2e8b57','#f2a541','#6f42c1','#0b6e6e'];
const defaults={responsive:true,maintainAspectRatio:false};
function chart(id,type,labels,datasets,options={}){new Chart(document.getElementById(id),{type,data:{labels,datasets},options:{...defaults,...options}})}
chart('shipChart','pie',['Shipped','B/O Shipped'],[{data:[r.shipped,r.boShipped],backgroundColor:palette.slice(0,2)}]);
const p=r.doughnut||{};
chart('priorityChart','doughnut',['Fleet Failure','999','Spare','ANORS','CASREP'],[{data:[p.fleetFailure||0,p.nineNineNine||0,p.spare||0,p.anors||0,p.casrep||0],backgroundColor:palette.slice(0,5)}]);
const dem=r.demand||{};
chart('demandChart','bar',dem.labels||[],[
{label:'Demand',data:dem.demand||[],backgroundColor:palette[0],yAxisID:'y'},
{label:'Misses',data:dem.misses||[],backgroundColor:palette[1],yAxisID:'y'},
{label:'Fill Rate %',data:dem.fillRate||[],type:'line',borderColor:palette[2],yAxisID:'percent',tension:.2},
{label:'Goal %',data:dem.goal||[],type:'line',borderColor:palette[3],borderDash:[6,4],pointRadius:0,yAxisID:'percent'}
],{scales:{y:{beginAtZero:true},percent:{position:'right',min:0,max:100,grid:{drawOnChartArea:false}}}});
const a=r.averages||{};
chart('averagesChart','line',a.labels||[],[
{label:'B/O Shipped RT',data:a.boshipped||[],borderColor:palette[0]},
{label:'CASREP RT',data:a.casreprt||[],borderColor:palette[4]},
{label:'Non-CASREP RT',data:a.noncasreprt||[],borderColor:palette[3]},
{label:'All RT',data:a.allrt||[],borderColor:palette[2]},
{label:'CASREP Goal',data:a.casrepgoal||[],borderColor:palette[1],borderDash:[5,5]},
{label:'Non-CASREP Goal',data:a.noncasrepgoal||[],borderColor:palette[5],borderDash:[5,5]}
],{interaction:{mode:'index',intersect:false}});
const bo=r.backorders||[];
chart('backorderChart','bar',bo.map(x=>x.label||''),[{label:'Backorders',data:bo.map(x=>Number(x.total||0)),backgroundColor:palette[0]}],{indexAxis:'y',plugins:{legend:{display:false}},scales:{x:{beginAtZero:true}}});
</script>
<?php endif; ?>
</div></body></html>
