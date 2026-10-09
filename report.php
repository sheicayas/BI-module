<?php 
// BI/reports.php

// ================================
// DATABASE CONNECTION

date_default_timezone_set('Asia/Manila'); // Ensure correct timezone

$conn = new mysqli("localhost", "root", "", "erp_indang");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ================================
// HANDLE DATE FILTERS
// ================================
$from_date = $_GET['from'] ?? null;
$to_date   = $_GET['to'] ?? null;

// ================================
// GLOBAL REPORT DATE RANGE
// ================================
$report_date_label = "Select Dates";

if (!$from_date || !$to_date) {

    $date_range_sql = "
        SELECT 
            MIN(created_at) AS min_date,
            MAX(created_at) AS max_date
        FROM orders
    ";

    $date_range_result = $conn->query($date_range_sql);

    if ($date_range_result && $row = $date_range_result->fetch_assoc()) {
        if ($row['min_date'] && $row['max_date']) {
            $report_date_label =
                date('Y-m-d', strtotime($row['min_date'])) .
                " — " .
                date('Y-m-d', strtotime($row['max_date']));
        }
    }

} else {
    $report_date_label = "$from_date — $to_date";
}

// ================================
// SALES SUMMARY QUERY
// ================================
$sales_sql = "
SELECT o.order_id, o.total_amount, COALESCE(p.status,'pending') AS payment_status, o.created_at
FROM orders o
LEFT JOIN payments p ON o.id = p.order_id
";
if ($from_date && $to_date) {
    $sales_sql .= " WHERE o.created_at BETWEEN '$from_date' AND '$to_date'";
}
$sales_sql .= " ORDER BY o.created_at DESC";

$sales_result = $conn->query($sales_sql);

$total_orders = 0;
$total_revenue = 0;
$sales_rows_html = "";

if ($sales_result->num_rows > 0) {
    while ($row = $sales_result->fetch_assoc()) {
        $total_orders++;
        $total_revenue += $row['total_amount'];
        $status = ucfirst($row['payment_status']); // Paid / Pending
        $sales_rows_html .= "
            <tr>
                <td>#{$row['order_id']}</td>
                <td>₱" . number_format($row['total_amount'], 2) . "</td>
                <td><span class='status status-" . strtolower($row['payment_status']) . "'>$status</span></td>
                <td>{$row['created_at']}</td>
            </tr>
        ";
    }
}

$sales_summary_html = "
<div class='summary-grid'>
    <div><h4>Total Orders</h4><p>$total_orders</p></div>
    <div><h4>Total Revenue</h4><p>₱" . number_format($total_revenue, 2) . "</p></div>
    <div><h4>Date</h4><p>$report_date_label</p></div>
</div>
";

// ================================
// INVENTORY STOCK QUERY
// ================================
$inventory_sql = "SELECT * FROM inventory";
if ($from_date && $to_date) {
    $inventory_sql .= " WHERE last_movement_at BETWEEN '$from_date' AND '$to_date'";
}

$inventory_result = $conn->query($inventory_sql);

$total_items = 0;
$total_transactions = 0;
$inventory_rows_html = "";
if ($inventory_result->num_rows > 0) {
    while ($row = $inventory_result->fetch_assoc()) {
        $total_items += $row['quantity'];
        $total_transactions++;
        $inventory_rows_html .= "
        <tr>
            <td>{$row['product_id']}</td>
            <td>{$row['size']}</td>
            <td>{$row['quantity']}</td>
            <td>{$row['last_movement_type']}</td>
            <td>{$row['last_movement_qty']}</td>
            <td>{$row['last_movement_at']}</td>
            <td>{$row['warehouse_id']}</td>
            <td>{$row['sold']}</td>
        </tr>";
    }
}

$inventory_summary_html = "
<div class='summary-grid'>
    <div><h4>Total Items</h4><p>$total_items</p></div>
    <div><h4>Total Transactions</h4><p>$total_transactions</p></div>
    <div><h4>Date</h4><p>$report_date_label</p></div>
</div>
";

// ================================
// PROFIT & LOSS QUERY
// ================================
$pl_sql = "
SELECT 
    o.order_id,
    o.total_amount,
    COALESCE(p.status,'pending') AS payment_status,
    o.created_at
FROM orders o
LEFT JOIN payments p ON o.id = p.order_id
";

if ($from_date && $to_date) {
    $pl_sql .= " WHERE o.created_at BETWEEN '$from_date' AND '$to_date'";
}

$pl_sql .= " ORDER BY o.created_at DESC";

$pl_result = $conn->query($pl_sql);

// ================================
// METRICS
// ================================
$total_orders = 0;

$paid_orders = 0;
$pending_orders = 0;
$failed_orders = 0;
$refunded_orders = 0;

$paid_revenue = 0;
$unpaid_revenue = 0;

$pl_rows_html = "";

if ($pl_result && $pl_result->num_rows > 0) {
    while ($row = $pl_result->fetch_assoc()) {

        $total_orders++;
        $amount = (float)$row['total_amount'];
        $status = strtolower($row['payment_status']);

        switch ($status) {
            case 'paid':
                $paid_orders++;
                $paid_revenue += $amount;
                break;

            case 'pending':
                $pending_orders++;
                $unpaid_revenue += $amount;
                break;

            case 'failed':
                $failed_orders++;
                $unpaid_revenue += $amount;
                break;

            case 'refunded':
                $refunded_orders++;
                break;
        }

        $pl_rows_html .= "
        <tr>
            <td>#{$row['order_id']}</td>
            <td>₱" . number_format($amount, 2) . "</td>
            <td><span class='status status-{$status}'>" . ucfirst($status) . "</span></td>
            <td>{$row['created_at']}</td>
        </tr>";
    }
}

$pl_summary_html = "
<div class='summary-grid'>
    <div>
        <h4>Paid Revenue</h4>
        <p>₱" . number_format($paid_revenue, 2) . "</p>
    </div>

    <div>
        <h4>Unpaid Revenue</h4>
        <p>₱" . number_format($unpaid_revenue, 2) . "</p>
    </div>

    <div>
        <h4>Total Orders</h4>
        <p>$total_orders</p>
    </div>

    <div>
        <h4>Paid Orders</h4>
        <p>$paid_orders</p>
    </div>

    <div>
        <h4>Pending Orders</h4>
        <p>$pending_orders</p>
    </div>

    <div>
        <h4>Failed Orders</h4>
        <p>$failed_orders</p>
    </div>

    <div>
        <h4>Refunded Orders</h4>
        <p>$refunded_orders</p>
    </div>

   <div>
    <h4>Date</h4>
    <p>$report_date_label</p>
  </div>

</div>
";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Standard & Custom Reports — Tire Supply BI</title>
<link rel="stylesheet" href="report.css">

<script>
let selectedReport = null;

function selectReport(el, type) {
  document.querySelectorAll(".report-type-box").forEach(btn => btn.classList.remove("active"));
  el.classList.add("active");
  selectedReport = type;
}

function showReportResults() {
  if (!selectedReport) {
    alert("Please select a report type first.");
    return;
  }

  const reportTitle = document.getElementById("reportTitle");
  const reportBody = document.getElementById("reportBody");
  const reportSummary = document.getElementById("reportSummary");
  const headerEl = document.getElementById("reportHeader");
  const resultCard = document.getElementById("reportResultCard");

  // Get selected dates
  const from = document.getElementById("fromDate").value;
  const to = document.getElementById("toDate").value;

  if (!from || !to) {
      alert("Please select both From and To dates.");
      return;
  }

  if (selectedReport === "Sales Summary") {
      headerEl.innerHTML = "<tr><th>ORDER ID</th><th>TOTAL AMOUNT</th><th>STATUS</th><th>CREATED AT</th></tr>";
      reportBody.innerHTML = `<?php echo $sales_rows_html; ?>`;
      reportSummary.innerHTML = `<?php echo $sales_summary_html; ?>`;
  } else if (selectedReport === "Inventory Stock") {
      headerEl.innerHTML = "<tr><th>Product ID</th><th>Size</th><th>Quantity</th><th>Last Movement Type</th><th>Last Movement Qty</th><th>Last Movement At</th><th>Warehouse ID</th><th>Sold</th></tr>";
      reportBody.innerHTML = `<?php echo $inventory_rows_html; ?>`;
      reportSummary.innerHTML = `<?php echo $inventory_summary_html; ?>`;
  } else if (selectedReport === "Profit & Loss") {
      headerEl.innerHTML = "<tr><th>ORDER ID</th><th>Total Amount</th><th>Status</th><th>Created At</th></tr>";
      reportBody.innerHTML = `<?php echo $pl_rows_html; ?>`;
      reportSummary.innerHTML = `<?php echo $pl_summary_html; ?>`;
  }

  reportTitle.innerText = selectedReport + " Report";
  resultCard.style.display = "block";
}

// Export Function
function exportReport(type) {
  const from = document.getElementById("fromDate").value;
  const to = document.getElementById("toDate").value;

  if (!selectedReport) {
    alert("Please select a report first!");
    return;
  }

  window.location.href = `export_report.php?type=${type}&report=${encodeURIComponent(selectedReport)}&from=${from}&to=${to}`;
}

// Schedule Function
function setSchedule() {
  if (!selectedReport) {
    alert("Please select a report first!");
    return;
  }

  const frequency = document.getElementById("scheduleSelect").value;
  const time = document.getElementById("scheduleTime")?.value || "";
  const email = document.getElementById("scheduleEmail")?.value || "";

  if (!time || !email) {
    alert("Please complete all schedule fields.");
    return;
  }

  alert(`Schedule Saved!\n\nReport: ${selectedReport}\nFrequency: ${frequency}\nTime: ${time}\nEmail: ${email}`);
}
</script>
</head>
<body class="reports-page">
<div class="bi-shell">

<header class="bi-topbar">
    <div class="topbar-left">
      <div class="brand">Tire Supply BI</div>
    </div>
    <nav class="topbar-nav">
      <a href="index.php">Overview</a>
      <a class="active" href="reports.php">Standard & Custom</a>
      <a href="realtime.php">Real-Time</a>
      <a href="compliance.php">Compliance & Audit</a>
    </nav>
</header>

<main class="bi-main">
    <section class="intro-section centered">
      <h1>Standard & Custom Reports</h1>
      <h2>Generate and manage BI reports.</h2>
    </section>

    <div class="report-flex">
      <!-- LEFT COLUMN -->
      <div class="report-column">
        <div class="report-card">
          <h3>📊 Predefined Reports</h3>
          <div class="report-type-row">
            <div class="report-type-box" onclick="selectReport(this,'Sales Summary')">Sales Summary</div>
            <div class="report-type-box" onclick="selectReport(this,'Inventory Stock')">Inventory Stock</div>
            <div class="report-type-box" onclick="selectReport(this,'Profit & Loss')">Profit & Loss</div>
          </div>
        </div>

        <div class="report-card">
          <h3>🧩 Custom Filters</h3>
          <div class="date-row">
            <label>From</label><input type="date" id="fromDate" value="<?php echo $from_date; ?>">
            <label>To</label><input type="date" id="toDate" value="<?php echo $to_date; ?>">
          </div>
          <button class="btn btn-primary" onclick="showReportResults()">Generate Report</button>
        </div>

        <section id="reportResultCard" class="report-result-card" style="display:none;">
          <h3 id="reportTitle"></h3>
          <div id="reportSummary"></div>
          <div class="table-wrap">
            <table>
              <thead id="reportHeader"></thead>
              <tbody id="reportBody"></tbody>
            </table>
          </div>
        </section>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="report-column small">
        <div class="report-card">
          <h3>💾 Export</h3>
          <div class="export-icons">
            <button class="export-btn pdf" onclick="exportReport('pdf')">📄 PDF</button>
            <button class="export-btn excel" onclick="exportReport('excel')">📊 Excel</button>
            <button class="export-btn csv" onclick="exportReport('csv')">🧾 CSV</button>
          </div>
        </div>

        <div class="report-card">
          <h3>🕒 Schedule</h3>
          <label>Email</label>
          <input type="email" id="scheduleEmail" placeholder="Enter email">
          <label>Time</label>
          <input type="time" id="scheduleTime">
          <select id="scheduleSelect">
            <option>Daily</option>
            <option>Weekly</option>
            <option>Monthly</option>
          </select>
          <button class="btn btn-primary" onclick="setSchedule()">Set Schedule</button>
        </div>
      </div>
    </div>
</main>
</div>
</body>
</html>
