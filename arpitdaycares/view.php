<?php
$PASSWORD = 'acpc2026nav';
session_start();
$RESPONSES_DIR = __DIR__ . '/responses';

if (isset($_POST['password'])) {
    $_SESSION['authed'] = ($_POST['password'] === $PASSWORD) ? true : false;
    if (!$_SESSION['authed']) $loginError = true;
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: view.php'); exit; }

if (isset($_GET['download']) && !empty($_SESSION['authed'])) {
    $file = basename($_GET['download']);
    $path = $RESPONSES_DIR . '/' . $file;
    if (file_exists($path) && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        readfile($path); exit;
    }
}

function latest_response($dir) {
    $files = glob($dir . '/*.json');
    if (empty($files)) return null;
    rsort($files);
    return json_decode(file_get_contents($files[0]), true);
}

function n($v) { return is_numeric($v) ? (float)$v : 0; }
function money($v) { return '$' . number_format(n($v), 0); }
function pct($a, $b) { return $b > 0 ? round($a / $b * 100) : 0; }
function badge($val, $good, $warn = '') {
    if ($val === $good) return '<span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:8px;font-size:11px;font-weight:600">&#10003;</span>';
    if ($val === $warn) return '<span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:8px;font-size:11px;font-weight:600">?</span>';
    return '<span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:8px;font-size:11px;font-weight:600">&#10007;</span>';
}
function yn($v) {
    if ($v === 'yes' || $v === 'yes-claiming' || $v === 'yes-2025-26') return badge($v, $v);
    if ($v === '?' || $v === 'not-sure' || $v === '' || $v === null) return badge('?', 'x', '?');
    return badge('no', 'yes');
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daycare Diagnostic — Analysis</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:wght@700;800&display=swap" rel="stylesheet">
<style>
:root { --accent:#1a6b4a; --accent-light:#e8f5ee; --bg:#f1f3f6; --card:#fff; --border:#e2e5ea; --text:#1a1a2e; --muted:#6b7280; --red:#dc2626; --green:#16a34a; --yellow:#d97706; }
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',system-ui,sans-serif;background:var(--bg);color:var(--text);line-height:1.5;}
.wrap{max-width:900px;margin:0 auto;padding:20px 20px 80px;}
h1{font-family:'Fraunces',Georgia,serif;font-weight:800;font-size:28px;letter-spacing:-0.03em;}
h2{font-size:18px;font-weight:700;margin-bottom:12px;letter-spacing:-0.01em;}
h3{font-size:14px;font-weight:700;margin-bottom:8px;}
.sub{color:var(--muted);font-size:14px;margin-bottom:20px;}
.card{background:var(--card);border-radius:16px;padding:28px;box-shadow:0 1px 4px rgba(0,0,0,0.06);margin-bottom:20px;}
.top-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;}
.logout{color:var(--muted);font-size:13px;text-decoration:none;}
.logout:hover{color:var(--text);}
table{width:100%;border-collapse:collapse;font-size:13px;}
th{text-align:left;font-weight:700;font-size:11px;color:var(--accent);text-transform:uppercase;letter-spacing:0.05em;padding:6px 8px;border-bottom:2px solid var(--border);}
td{padding:8px;border-bottom:1px solid var(--border);vertical-align:top;}
tr:last-child td{border-bottom:none;}
.num{text-align:right;font-variant-numeric:tabular-nums;font-weight:500;}
.total-row{font-weight:700;background:#f8f9fb;border-top:2px solid var(--border);}
.total-row td{border-bottom:none;}
.highlight{background:#fef3c7;font-weight:700;}
.flag{display:inline-block;padding:3px 10px;border-radius:8px;font-size:11px;font-weight:700;margin:2px;}
.flag-red{background:#fee2e2;color:#991b1b;}
.flag-yellow{background:#fef3c7;color:#92400e;}
.flag-green{background:#dcfce7;color:#166534;}
.metric-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
.metric{background:#f8f9fb;border-radius:12px;padding:16px;text-align:center;border:1px solid var(--border);}
.metric .val{font-size:24px;font-weight:700;color:var(--accent);font-variant-numeric:tabular-nums;}
.metric .lbl{font-size:11px;color:var(--muted);margin-top:2px;font-weight:600;}
.metric .name{font-size:12px;font-weight:700;margin-bottom:6px;}
.btn{font-family:inherit;font-size:13px;font-weight:600;padding:8px 20px;border-radius:10px;cursor:pointer;border:none;transition:all 0.15s;}
.btn-primary{background:var(--accent);color:#fff;}
.btn-outline{background:#fff;border:1.5px solid var(--border);color:var(--text);}
input[type="password"]{font-family:inherit;font-size:14px;padding:10px 14px;width:100%;max-width:300px;border:1.5px solid var(--border);border-radius:10px;outline:none;display:block;margin-bottom:12px;}
.err{color:var(--red);font-size:13px;margin-bottom:8px;}
.actions{display:flex;gap:8px;margin-top:16px;flex-wrap:wrap;}
@media(max-width:600px){.metric-grid{grid-template-columns:1fr 1fr;} table{font-size:11px;} td,th{padding:4px;}}
</style>
</head>
<body>
<div class="wrap">

<?php if (empty($_SESSION['authed'])): ?>
<div class="card" style="max-width:400px;margin:80px auto;">
  <h1 style="margin-bottom:8px;">Daycare Analysis</h1>
  <p class="sub">Enter the password to view Arpit's diagnostic results.</p>
  <?php if (!empty($loginError)): ?><p class="err">Wrong password.</p><?php endif; ?>
  <form method="POST">
    <input type="password" name="password" placeholder="Password" autofocus>
    <button type="submit" class="btn btn-primary">View Analysis</button>
  </form>
</div>

<?php else:
$data = latest_response($RESPONSES_DIR);
$files = glob($RESPONSES_DIR . '/*.json');
rsort($files);
?>

<div class="top-bar">
  <div>
    <h1>Arpit's Daycare Diagnostic</h1>
    <p class="sub" style="margin:4px 0 0;">
      <?php if ($data): ?>
        Submitted: <?= date('M j, Y \a\t g:i A', strtotime($data['submitted_at'] ?? 'now')) ?>
        &middot; v<?= htmlspecialchars($data['survey_version'] ?? '?') ?>
      <?php else: ?>
        No responses yet.
      <?php endif; ?>
    </p>
  </div>
  <div style="display:flex;gap:8px;align-items:center;">
    <?php if (!empty($files)): ?>
      <a href="?download=<?= urlencode(basename($files[0])) ?>" class="btn btn-outline">Download JSON</a>
    <?php endif; ?>
    <a href="?logout=1" class="logout">Logout</a>
  </div>
</div>

<?php if (!$data): ?>
<div class="card" style="text-align:center;padding:60px 20px;color:var(--muted);">
  <p style="font-size:18px;font-weight:600;">Waiting for Arpit to submit the survey.</p>
  <p style="margin-top:8px;">Send him: <strong>acpc.tax/ArpitDaycares/</strong></p>
</div>

<?php else:
$locs = $data['locations'] ?? [];
$fund = $data['funding'] ?? [];
$diag = $data['diagnostics'] ?? [];
$numLocs = count($locs);
?>

<!-- ─── AT A GLANCE ─── -->
<div class="metric-grid">
<?php foreach ($locs as $i => $loc):
  $name = $loc['identity']['name'] ?? ('Location ' . ($i+1));
  $licensed = n($loc['capacity']['licensed'] ?? 0);
  $enrolled = n($loc['capacity']['enrolled'] ?? 0);
  $empty = $licensed - $enrolled;
  $occ = $licensed > 0 ? round($enrolled / $licensed * 100) : 0;
  $rev = n($loc['revenue_monthly']['parent_fees'] ?? 0) + n($loc['revenue_monthly']['affordability_grant'] ?? 0) + n($loc['revenue_monthly']['wage_topup'] ?? 0) + n($loc['revenue_monthly']['other_gov'] ?? 0) + n($loc['revenue_monthly']['other'] ?? 0);
  $exp = n($loc['expenses_monthly']['rent'] ?? 0) + n($loc['expenses_monthly']['payroll'] ?? 0) + n($loc['expenses_monthly']['other'] ?? 0);
  $margin = $rev - $exp;
?>
<div class="metric">
  <div class="name"><?= htmlspecialchars($name) ?></div>
  <div class="val"><?= $occ ?>%</div>
  <div class="lbl">occupancy (<?= (int)$enrolled ?>/<?= (int)$licensed ?>)</div>
  <div style="margin-top:8px;font-size:12px;color:var(--muted);">
    Rev: <?= money($rev) ?><br>
    Exp: <?= money($exp) ?><br>
    <span style="color:<?= $margin >= 0 ? 'var(--green)' : 'var(--red)' ?>;font-weight:700;">
      Net: <?= money($margin) ?>
    </span>
  </div>
</div>
<?php endforeach; ?>
</div>

<!-- ─── CAPACITY GAP ─── -->
<div class="card">
<h2>Capacity & Empty Spots</h2>
<table>
  <tr><th>Location</th><th class="num">Licensed</th><th class="num">Enrolled</th><th class="num">Empty</th><th class="num">Occupancy</th><th class="num">Waitlist</th><th class="num">Est. Monthly Loss</th></tr>
<?php
$totLic = $totEnr = $totEmpty = $totWait = $totLoss = 0;
foreach ($locs as $i => $loc):
  $name = $loc['identity']['name'] ?? ('Loc ' . ($i+1));
  $lic = n($loc['capacity']['licensed'] ?? 0);
  $enr = n($loc['capacity']['enrolled'] ?? 0);
  $emp = max(0, $lic - $enr);
  $wait = n($loc['capacity']['waitlist'] ?? 0);
  $loss = $emp * 326.25;
  $totLic += $lic; $totEnr += $enr; $totEmpty += $emp; $totWait += $wait; $totLoss += $loss;
?>
  <tr<?= $emp > 0 ? ' class="highlight"' : '' ?>>
    <td><?= htmlspecialchars($name) ?></td>
    <td class="num"><?= (int)$lic ?></td>
    <td class="num"><?= (int)$enr ?></td>
    <td class="num"><?= (int)$emp ?></td>
    <td class="num"><?= pct($enr, $lic) ?>%</td>
    <td class="num"><?= (int)$wait ?></td>
    <td class="num" style="color:<?= $emp > 0 ? 'var(--red)' : 'var(--green)' ?>"><?= money($loss) ?></td>
  </tr>
<?php endforeach; ?>
  <tr class="total-row">
    <td>TOTAL</td>
    <td class="num"><?= (int)$totLic ?></td>
    <td class="num"><?= (int)$totEnr ?></td>
    <td class="num"><?= (int)$totEmpty ?></td>
    <td class="num"><?= pct($totEnr, $totLic) ?>%</td>
    <td class="num"><?= (int)$totWait ?></td>
    <td class="num" style="color:var(--red);font-weight:700;"><?= money($totLoss) ?>/mo</td>
  </tr>
</table>
<p style="font-size:11px;color:var(--muted);margin-top:8px;">* Loss estimated at $326.25/mo per empty FT spot (flat parent fee only — actual loss includes grant revenue).</p>
</div>

<!-- ─── REVENUE BREAKDOWN ─── -->
<div class="card">
<h2>Monthly Revenue Breakdown</h2>
<table>
  <tr><th>Source</th><?php foreach($locs as $i=>$l): ?><th class="num"><?= htmlspecialchars($l['identity']['name'] ?? 'Loc '.($i+1)) ?></th><?php endforeach; ?><th class="num">Total</th></tr>
<?php
$sources = [
  ['Parent Fees', 'parent_fees'],
  ['Affordability Grant', 'affordability_grant'],
  ['Wage Top-Up', 'wage_topup'],
  ['Other Gov', 'other_gov'],
  ['Other', 'other'],
];
$colTotals = array_fill(0, $numLocs, 0);
foreach ($sources as [$label, $key]):
  $rowTotal = 0;
?>
  <tr><td><?= $label ?></td>
  <?php foreach ($locs as $i => $loc):
    $v = n($loc['revenue_monthly'][$key] ?? 0);
    $colTotals[$i] += $v; $rowTotal += $v;
  ?>
    <td class="num"><?= money($v) ?></td>
  <?php endforeach; ?>
    <td class="num"><?= money($rowTotal) ?></td>
  </tr>
<?php endforeach; ?>
  <tr class="total-row"><td>TOTAL REVENUE</td>
  <?php $grandRev = 0; foreach ($colTotals as $t): $grandRev += $t; ?><td class="num"><?= money($t) ?></td><?php endforeach; ?>
  <td class="num"><?= money($grandRev) ?></td></tr>
</table>
</div>

<!-- ─── EXPENSES ─── -->
<div class="card">
<h2>Monthly Expenses & Margin</h2>
<table>
  <tr><th>Item</th><?php foreach($locs as $i=>$l): ?><th class="num"><?= htmlspecialchars($l['identity']['name'] ?? 'Loc '.($i+1)) ?></th><?php endforeach; ?><th class="num">Total</th></tr>
<?php
$expSources = [['Rent/Mortgage','rent'],['Payroll','payroll'],['Other','other']];
$expTotals = array_fill(0, $numLocs, 0);
foreach ($expSources as [$label, $key]):
  $rowTotal = 0;
?>
  <tr><td><?= $label ?></td>
  <?php foreach ($locs as $i => $loc):
    $v = n($loc['expenses_monthly'][$key] ?? 0);
    $expTotals[$i] += $v; $rowTotal += $v;
  ?>
    <td class="num"><?= money($v) ?></td>
  <?php endforeach; ?>
    <td class="num"><?= money($rowTotal) ?></td>
  </tr>
<?php endforeach; ?>
  <tr class="total-row"><td>TOTAL EXPENSES</td>
  <?php $grandExp = 0; foreach ($expTotals as $t): $grandExp += $t; ?><td class="num"><?= money($t) ?></td><?php endforeach; ?>
  <td class="num"><?= money($grandExp) ?></td></tr>
  <tr style="font-weight:700;"><td>NET MARGIN</td>
  <?php foreach ($colTotals as $i => $rev):
    $margin = $rev - $expTotals[$i];
  ?>
    <td class="num" style="color:<?= $margin >= 0 ? 'var(--green)' : 'var(--red)' ?>"><?= money($margin) ?></td>
  <?php endforeach; ?>
  <td class="num" style="color:<?= ($grandRev - $grandExp) >= 0 ? 'var(--green)' : 'var(--red)' ?>;font-size:15px;"><?= money($grandRev - $grandExp) ?>/mo</td>
  </tr>
</table>
</div>

<!-- ─── FUNDING STATUS ─── -->
<div class="card">
<h2>Funding Program Status</h2>
<table>
  <tr><th>Program</th><?php foreach($locs as $i=>$l): ?><th style="text-align:center"><?= htmlspecialchars($l['identity']['name'] ?? 'Loc '.($i+1)) ?></th><?php endforeach; ?></tr>
<?php
$fundPrograms = [
  ['Affordability Grant', $fund['affordability']['signed'] ?? []],
  ['ECE Workforce / Wage Top-Up', $fund['workforce']['signed'] ?? []],
  ['Child Care Subsidy (K-6)', $fund['subsidy']['serves'] ?? []],
  ['Inclusive Child Care', $fund['icc']['participating'] ?? []],
  ['Supplemental Fees', $fund['suppFees']['charging'] ?? []],
];
foreach ($fundPrograms as [$name, $vals]):
?>
  <tr><td><?= $name ?></td>
  <?php for ($i = 0; $i < $numLocs; $i++): ?>
    <td style="text-align:center"><?= yn($vals[$i] ?? '') ?></td>
  <?php endfor; ?>
  </tr>
<?php endforeach; ?>
</table>
</div>

<!-- ─── STAFF ANALYSIS ─── -->
<div class="card">
<h2>Staff & Wage Top-Up Potential</h2>
<table>
  <tr><th>Metric</th><?php foreach($locs as $i=>$l): ?><th class="num"><?= htmlspecialchars($l['identity']['name'] ?? 'Loc '.($i+1)) ?></th><?php endforeach; ?><th class="num">Total</th></tr>
<?php
$staffFields = [
  ['Total ECEs', 'total_eces'],
  ['Level 1', 'level_1'],
  ['Level 2', 'level_2'],
  ['Level 3', 'level_3'],
  ['Uncertified', 'uncertified'],
  ['Turnover (12mo)', 'turnover_12mo'],
  ['Vacancies', 'vacancies'],
];
foreach ($staffFields as [$label, $key]):
  $rowTotal = 0;
?>
  <tr><td><?= $label ?></td>
  <?php foreach ($locs as $i => $loc):
    $v = n($loc['staff'][$key] ?? 0); $rowTotal += $v;
  ?>
    <td class="num"><?= (int)$v ?></td>
  <?php endforeach; ?>
    <td class="num"><?= (int)$rowTotal ?></td>
  </tr>
<?php endforeach; ?>
</table>

<?php
$totalL1 = $totalL2 = $totalL3 = 0;
foreach ($locs as $loc) {
    $totalL1 += n($loc['staff']['level_1'] ?? 0);
    $totalL2 += n($loc['staff']['level_2'] ?? 0);
    $totalL3 += n($loc['staff']['level_3'] ?? 0);
}
$currentTopup = ($totalL1 * 2.64 + $totalL2 * 5.05 + $totalL3 * 8.62) * 181;
$ifAllL2 = (($totalL1 + $totalL2) * 5.05 + $totalL3 * 8.62) * 181;
$upgrade = $ifAllL2 - $currentTopup;
if ($totalL1 > 0):
?>
<div style="margin-top:16px;padding:14px;background:#eef2ff;border-radius:10px;border:1px solid rgba(99,102,241,0.15);">
  <h3 style="color:#4338ca;">Wage Top-Up Upgrade Opportunity</h3>
  <p style="font-size:13px;color:#4338ca;line-height:1.6;">
    You have <strong><?= (int)$totalL1 ?> Level 1 ECEs</strong> earning $2.64/hr in top-up.<br>
    If upgraded to Level 2 ($5.05/hr), that's an extra <strong style="font-size:15px;"><?= money($upgrade) ?>/month</strong> in government funding.<br>
    <span style="font-size:11px;">Calculation: <?= (int)$totalL1 ?> ECEs &times; $2.41/hr increase &times; 181 hrs/mo = <?= money($totalL1 * 2.41 * 181) ?>/mo</span>
  </p>
</div>
<?php endif; ?>
</div>

<!-- ─── RED FLAGS ─── -->
<div class="card">
<h2>Red Flags & Action Items</h2>
<?php
$flags = [];

foreach ($locs as $i => $loc) {
    $name = $loc['identity']['name'] ?? ('Loc ' . ($i+1));
    $lic = n($loc['capacity']['licensed'] ?? 0);
    $enr = n($loc['capacity']['enrolled'] ?? 0);
    if ($lic > 0 && ($enr / $lic) < 0.85) $flags[] = ['red', "$name is at " . pct($enr,$lic) . "% occupancy — " . (int)($lic-$enr) . " empty spots"];
    if (n($loc['staff']['vacancies'] ?? 0) > 0) $flags[] = ['yellow', "$name has " . (int)n($loc['staff']['vacancies'] ?? 0) . " open staff vacancies"];
    if (n($loc['staff']['turnover_12mo'] ?? 0) > 3) $flags[] = ['yellow', "$name had " . (int)n($loc['staff']['turnover_12mo'] ?? 0) . " staff turnover in 12 months"];
}

$aff = $fund['affordability']['signed'] ?? [];
for ($i = 0; $i < $numLocs; $i++) {
    if (($aff[$i] ?? '') === 'no' || ($aff[$i] ?? '') === '') {
        $name = $locs[$i]['identity']['name'] ?? ('Loc ' . ($i+1));
        $flags[] = ['red', "$name has NOT signed an Affordability Grant agreement — urgent"];
    }
}

$suppCharging = $fund['suppFees']['charging'] ?? [];
for ($i = 0; $i < $numLocs; $i++) {
    if (($suppCharging[$i] ?? '') !== 'yes') {
        $name = $locs[$i]['identity']['name'] ?? ('Loc ' . ($i+1));
        $flags[] = ['yellow', "$name is not charging supplemental fees — potential missed revenue"];
    }
}

$violations = $diag['violations'] ?? [];
for ($i = 0; $i < $numLocs; $i++) {
    if (($violations[$i] ?? '') === 'yes') {
        $name = $locs[$i]['identity']['name'] ?? ('Loc ' . ($i+1));
        $flags[] = ['red', "$name has licensing violations/conditions — investigate"];
    }
}

$ratios = $diag['meetingRatios'] ?? [];
for ($i = 0; $i < $numLocs; $i++) {
    if (($ratios[$i] ?? '') === 'sometimes') {
        $name = $locs[$i]['identity']['name'] ?? ('Loc ' . ($i+1));
        $flags[] = ['yellow', "$name sometimes falls short on ratio requirements"];
    }
}

if (($diag['ftPtMismatch'] ?? '') === 'yes') $flags[] = ['yellow', 'FT/PT mismatch detected — some children enrolled FT but attending PT hours'];
if (($diag['gstReg'] ?? '') === '?' || ($diag['gstReg'] ?? '') === '') $flags[] = ['yellow', 'GST registration status unknown — needs clarification'];
if (($diag['compensation'] ?? '') === 'nothing' || ($diag['compensation'] ?? '') === 'draws') $flags[] = ['yellow', 'Owner compensation via draws/none — may not be tax-optimal'];
if (($diag['holdco'] ?? '') === '?') $flags[] = ['yellow', 'Holding company status unknown — potential tax planning opportunity'];

if (empty($flags)): ?>
  <p style="color:var(--green);font-weight:600;">No major red flags detected.</p>
<?php else:
  foreach ($flags as [$level, $msg]): ?>
    <div class="flag flag-<?= $level ?>"><?= htmlspecialchars($msg) ?></div><br>
  <?php endforeach;
endif; ?>
</div>

<!-- ─── DIAGNOSTICS SUMMARY ─── -->
<div class="card">
<h2>Business Diagnostics Summary</h2>
<table>
  <tr><th>Item</th><th>Response</th></tr>
  <tr><td>Sibling discounts</td><td><?= htmlspecialchars(($diag['siblingDisc'] ?? '-') . ($diag['siblingDetail'] ?? '' ? ' — ' . $diag['siblingDetail'] : '')) ?></td></tr>
  <tr><td>Staff child discounts</td><td><?= htmlspecialchars(($diag['staffDisc'] ?? '-') . ($diag['staffDetail'] ?? '' ? ' — ' . $diag['staffDetail'] : '')) ?></td></tr>
  <tr><td>Absorbing supplies</td><td><?= htmlspecialchars($diag['absorbSupplies'] ?? '-') ?><?= ($diag['supplyCost'] ?? '') ? ' — ' . money($diag['supplyCost']) . '/mo' : '' ?></td></tr>
  <tr><td>Bookkeeper/Accountant</td><td><?= htmlspecialchars(($diag['hasAccountant'] ?? '-') . ($diag['accountantWho'] ?? '' ? ' — ' . $diag['accountantWho'] : '')) ?></td></tr>
  <tr><td>Accounting software</td><td><?= htmlspecialchars($diag['software'] ?? '-') ?></td></tr>
  <tr><td>Payment method</td><td><?= htmlspecialchars($diag['payMethod'] ?? '-') ?></td></tr>
  <tr><td>Separate books</td><td><?= htmlspecialchars($diag['separateBooks'] ?? '-') ?></td></tr>
  <tr><td>GST registered</td><td><?= htmlspecialchars($diag['gstReg'] ?? '-') ?></td></tr>
  <tr><td>Owner compensation</td><td><?= htmlspecialchars($diag['compensation'] ?? '-') ?></td></tr>
  <tr><td>Corporate structure</td><td><?= htmlspecialchars($diag['corpStructure'] ?? '-') ?></td></tr>
  <tr><td>Small Business Deduction</td><td><?= htmlspecialchars($diag['sbd'] ?? '-') ?></td></tr>
  <tr><td>Personal expenses</td><td><?= htmlspecialchars(($diag['personalExp'] ?? '-') . ($diag['personalDetail'] ?? '' ? ' — ' . $diag['personalDetail'] : '')) ?></td></tr>
  <tr><td>Holding company</td><td><?= htmlspecialchars($diag['holdco'] ?? '-') ?></td></tr>
  <tr><td>Director coverage</td><td><?= htmlspecialchars($diag['directorCoverage'] ?? '-') ?></td></tr>
  <tr><td>Upgrade-eligible staff</td><td><?= htmlspecialchars($diag['upgradeEligible'] ?? '-') ?></td></tr>
  <tr><td>Biggest challenge</td><td><?= htmlspecialchars($diag['challenge'] ?? '-') ?></td></tr>
  <tr><td>Notes</td><td><?= htmlspecialchars($diag['notes'] ?? '-') ?></td></tr>
</table>
</div>

<!-- ─── ALL RESPONSES ─── -->
<div class="card">
<h2>All Submissions</h2>
<?php foreach ($files as $f):
  $fname = basename($f);
  $mtime = date('M j, Y g:i A', filemtime($f));
  $size = round(filesize($f) / 1024, 1);
?>
<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);">
  <div>
    <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($fname) ?></div>
    <div style="font-size:11px;color:var(--muted);"><?= $mtime ?> &middot; <?= $size ?> KB</div>
  </div>
  <a href="?download=<?= urlencode($fname) ?>" class="btn btn-outline" style="font-size:12px;padding:6px 14px;">Download</a>
</div>
<?php endforeach; ?>
</div>

<?php endif; // end if $data ?>
<?php endif; // end if authed ?>

</div>
</body>
</html>
