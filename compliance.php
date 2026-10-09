<?php
// BI/compliance.php
// Standalone Compliance & Audit Dashboard (no login, no db)
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Compliance & Audit — Tire Supply BI</title>
<link rel="stylesheet" href="style2.css">
</head>

<body class="compliance-page">
<div class="bi-shell">

  <!-- Topbar -->
  <header class="bi-topbar">
    <div class="topbar-left">
      <div class="brand">Tire Supply BI</div>
    </div>
    <nav class="topbar-nav">
      <a href="index.php">Overview</a>
      <a href="report.php">Standard & Custom</a>
      <a href="realtime.php">Real-Time Analytics</a>
      <a class="active" href="compliance.php">Compliance & Audit</a>
      <a href="security.php">Access & Security</a>
    </nav>
    
  </header>

  <main class="bi-main">

<?php
/* ===============================
   SIMULATED COMPLIANCE DATA
================================ */
$compliance_reports = [
  ['id'=>1,'report_name'=>'VAT Report (Q4 2024)','report_type'=>'VAT','status'=>'Submitted','generated_date'=>'2024-12-15','due_date'=>'2025-01-31','compliance'=>'Yes','amount'=>'₱45,230.50'],
  ['id'=>2,'report_name'=>'Income Tax Summary (2024)','report_type'=>'Income Tax','status'=>'Pending','generated_date'=>'2025-01-10','due_date'=>'2025-03-15','compliance'=>'On Track','amount'=>'₱128,450.75'],
  ['id'=>3,'report_name'=>'Financial Statement (Year-End 2024)','report_type'=>'Financial Statement','status'=>'Submitted','generated_date'=>'2025-01-05','due_date'=>'2025-02-28','compliance'=>'Yes','amount'=>'₱2,150,320.00'],
  ['id'=>4,'report_name'=>'Audit Trail Report (Jan 2025)','report_type'=>'Audit Trail','status'=>'Active','generated_date'=>'2025-01-20','due_date'=>'N/A','compliance'=>'Ongoing','amount'=>'N/A'],
  ['id'=>5,'report_name'=>'Sales Tax Report (Q4 2024)','report_type'=>'Sales Tax','status'=>'Submitted','generated_date'=>'2024-12-20','due_date'=>'2025-01-15','compliance'=>'Yes','amount'=>'₱32,150.25'],
  ['id'=>6,'report_name'=>'Regulatory Compliance Checklist','report_type'=>'Regulatory','status'=>'Submitted','generated_date'=>'2025-01-15','due_date'=>'2025-03-31','compliance'=>'Yes','amount'=>'N/A']
];

$audit_logs = [
  ['timestamp'=>'2025-01-22 14:32','user'=>'Finance Admin','action'=>'Generated VAT Report','report'=>'VAT Report (Q4 2024)','status'=>'Success'],
  ['timestamp'=>'2025-01-21 10:15','user'=>'Manager','action'=>'Viewed Income Tax Summary','report'=>'Income Tax Summary (2024)','status'=>'Success'],
  ['timestamp'=>'2025-01-20 09:48','user'=>'Finance Admin','action'=>'Exported Financial Statement','report'=>'Financial Statement (Year-End 2024)','status'=>'Success'],
  ['timestamp'=>'2025-01-19 16:22','user'=>'BI Analyst','action'=>'Viewed Audit Trail','report'=>'Audit Trail Report (Jan 2025)','status'=>'Success'],
  ['timestamp'=>'2025-01-18 13:55','user'=>'Admin','action'=>'Modified Report Access','report'=>'Sales Tax Report (Q4 2024)','status'=>'Success']
];

$filter = $_GET['filter'] ?? 'all';
$filtered = ($filter === 'all')
  ? $compliance_reports
  : array_filter($compliance_reports, fn($r) => $r['report_type'] === $filter);
?>

<!-- Intro -->
<section class="intro-section centered">
  <h1>Compliance & Audit Reports</h1>
  <h2>Regulatory & Tax Compliance Monitoring</h2>
  <p>Track tax filings, audits, regulatory requirements, and compliance status.</p>
</section>

<!-- Summary -->
<section class="summary-stats">
  <div class="stat-item"><div class="value"><?=count($compliance_reports)?></div><div class="label">Total Reports</div></div>
  <div class="stat-item"><div class="value"><?=count(array_filter($compliance_reports, fn($r)=>$r['status']=='Submitted'))?></div><div class="label">Submitted</div></div>
  <div class="stat-item"><div class="value"><?=count(array_filter($compliance_reports, fn($r)=>$r['compliance']=='Yes'))?></div><div class="label">Compliant</div></div>
  <div class="stat-item"><div class="value"><?=count(array_filter($compliance_reports, fn($r)=>$r['status']=='Pending'))?></div><div class="label">Pending</div></div>
</section>

<!-- Reports -->
<section class="compliance-section">
<div class="widget-card compliance">
<h3>📋 Compliance Reports</h3>

<form method="get" class="filter-controls">
  <select name="filter" onchange="this.form.submit()">
    <option value="all">All Types</option>
    <option value="VAT" <?=$filter=='VAT'?'selected':''?>>VAT</option>
    <option value="Income Tax" <?=$filter=='Income Tax'?'selected':''?>>Income Tax</option>
    <option value="Financial Statement" <?=$filter=='Financial Statement'?'selected':''?>>Financial Statement</option>
    <option value="Sales Tax" <?=$filter=='Sales Tax'?'selected':''?>>Sales Tax</option>
    <option value="Audit Trail" <?=$filter=='Audit Trail'?'selected':''?>>Audit Trail</option>
  </select>
  <button type="button" onclick="location.href='compliance.php'">Reset</button>
</form>

<table>
<thead>
<tr>
<th>Report</th><th>Type</th><th>Status</th><th>Generated</th><th>Due</th><th>Compliance</th><th>Amount</th><th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach($filtered as $r): ?>
<tr>
<td><?=htmlspecialchars($r['report_name'])?></td>
<td><?=$r['report_type']?></td>
<td><span class="status-badge"><?=$r['status']?></span></td>
<td><?=date('M d, Y',strtotime($r['generated_date']))?></td>
<td><?=$r['due_date']=='N/A'?'N/A':date('M d, Y',strtotime($r['due_date']))?></td>
<td><?=$r['compliance']?></td>
<td><?=$r['amount']?></td>
<td>
  <button class="btn-small" onclick="notify('Viewing <?=$r['report_name']?>')">View</button>
  <button class="btn-small" onclick="notify('Exported <?=$r['report_name']?>')">Export</button>
  <button class="btn-small" onclick="notify('PDF downloaded')">PDF</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<button class="btn" onclick="notify('New compliance report generation started')">
+ Generate New Report
</button>
</div>
</section>

<!-- Audit Trail -->
<section class="compliance-section">
<div class="widget-card compliance">
<h3>🔍 Audit Trail</h3>
<table>
<thead><tr><th>Time</th><th>User</th><th>Action</th><th>Report</th><th>Status</th></tr></thead>
<tbody>
<?php foreach($audit_logs as $log): ?>
<tr>
<td><?=$log['timestamp']?></td>
<td><?=$log['user']?></td>
<td><?=$log['action']?></td>
<td><?=$log['report']?></td>
<td><?=$log['status']?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</section>

</main>
</div>

<script>
function notify(msg){
  alert(msg);
}
</script>

</body>
</html>
