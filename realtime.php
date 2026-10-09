<?php
// ================================
// DATABASE CONNECTION
// ================================
$conn = new mysqli("localhost", "root", "", "erp_indang");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ================================
// KPI QUERIES
// ================================

// Total Revenue
$revenue_sql = "SELECT SUM(total_amount) AS total_revenue FROM orders";
$revenue_result = $conn->query($revenue_sql);
$total_revenue = 0;
if ($revenue_result && $revenue_result->num_rows > 0) {
    $row = $revenue_result->fetch_assoc();
    $total_revenue = $row['total_revenue'];
}

// Total Orders
$orders_sql = "SELECT COUNT(*) AS total_orders FROM orders";
$orders_result = $conn->query($orders_sql);
$total_orders = 0;
if ($orders_result && $orders_result->num_rows > 0) {
    $row = $orders_result->fetch_assoc();
    $total_orders = $row['total_orders'];
}

// Low Stock Alerts (<10)
$lowstock_sql = "SELECT COUNT(*) AS low_stock_count FROM inventory WHERE quantity < 10";
$lowstock_result = $conn->query($lowstock_sql);
$low_stock = 0;
if ($lowstock_result && $lowstock_result->num_rows > 0) {
    $row = $lowstock_result->fetch_assoc();
    $low_stock = $row['low_stock_count'];
}

// ================================
// Recent Sales Orders
// ================================
$sales_sql = "
SELECT o.order_id, o.total_amount, COALESCE(p.status,'pending') AS payment_status, o.created_at
FROM orders o
LEFT JOIN payments p ON o.id = p.order_id
ORDER BY o.created_at DESC
LIMIT 10
";
$sales_result = $conn->query($sales_sql);

// ================================
// Recent Accounts Receivable
// ================================
$receivable_sql = "
SELECT p.id AS invoice_id, p.amount, p.status, p.created_at
FROM payments p
ORDER BY p.created_at DESC
LIMIT 10
";
$receivable_result = $conn->query($receivable_sql);

// ================================
// All Orders with Invoice Details
// ================================
$all_orders_sql = "
SELECT o.order_id, o.total_amount, COALESCE(p.status,'pending') AS payment_status, o.created_at, p.id AS invoice_id, p.amount AS invoice_amount, p.created_at AS invoice_created_at
FROM orders o
LEFT JOIN payments p ON o.id = p.order_id
ORDER BY o.created_at DESC
LIMIT 10
";
$all_orders_result = $conn->query($all_orders_sql);

// ================================
// DECISION-MAKING METRICS
// ================================
$decision_sql = "
SELECT 
    COUNT(*) AS pending_count,
    SUM(o.total_amount) AS pending_amount
FROM orders o
LEFT JOIN payments p ON o.id = p.order_id
WHERE COALESCE(p.status,'pending') = 'pending'
";
$decision_result = $conn->query($decision_sql);
$pending_count = 0;
$pending_amount = 0;

if ($decision_result && $decision_result->num_rows > 0) {
    $row = $decision_result->fetch_assoc();
    $pending_count = $row['pending_count'] ?? 0;
    $pending_amount = $row['pending_amount'] ?? 0;
}
$risk_level = "Low";
$risk_class = "low";
$action_text = "Monitor receivables regularly";

if ($pending_amount >= 100000) {
    $risk_level = "High";
    $risk_class = "high";
    $action_text = "Immediate collection follow-up required";
} elseif ($pending_amount >= 50000) {
    $risk_level = "Medium";
    $risk_class = "medium";
    $action_text = "Strengthen collection efforts";
}

// ================================
// INVENTORY DECISION
// ================================
$inventory_sql = "
SELECT COUNT(*) AS low_items
FROM inventory
WHERE quantity < 10
";
$inventory_result = $conn->query($inventory_sql);
$low_items = 0;

if ($inventory_result && $row = $inventory_result->fetch_assoc()) {
    $low_items = $row['low_items'];
}

// Inventory risk logic
$inventory_risk = "Low";
$inventory_class = "low";
$inventory_action = "Inventory levels are stable";

if ($low_items >= 10) {
    $inventory_risk = "High";
    $inventory_class = "high";
    $inventory_action = "Immediate restocking required";
} elseif ($low_items >= 5) {
    $inventory_risk = "Medium";
    $inventory_class = "medium";
    $inventory_action = "Plan restocking soon";
}

// ================================
// SALES TREND DECISION
// ================================
$sales_trend_sql = "
SELECT SUM(total_amount) AS today_sales
FROM orders
WHERE DATE(created_at) = CURDATE()
";
$sales_trend_result = $conn->query($sales_trend_sql);
$today_sales = 0;

if ($sales_trend_result && $row = $sales_trend_result->fetch_assoc()) {
    $today_sales = $row['today_sales'] ?? 0;
}

// Sales trend logic
$sales_risk = "Normal";
$sales_class = "low";
$sales_action = "Sales performance is acceptable";

if ($today_sales < 20000) {
    $sales_risk = "Low Sales";
    $sales_class = "high";
    $sales_action = "Consider promotions or sales campaigns";
}
// Count how many rows are returned for each table
$sales_records = $sales_result ? $sales_result->num_rows : 0;
$receivable_records = $receivable_result ? $receivable_result->num_rows : 0;
$all_orders_records = $all_orders_result ? $all_orders_result->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Real-Time Analytics — Tire Supply BI</title>
<link rel="stylesheet" href="realtime.css">
</head>

<body class="realtime-page">
<div class="bi-shell">

  <!-- Topbar -->
  <header class="bi-topbar">
    <div class="topbar-left">
      <div class="brand">Tire Supply BI</div>
    </div>
    <nav class="topbar-nav">
      <a href="index.php">Overview</a>
      <a href="report.php">Standard & Custom</a>
      <a class="active" href="realtime.php">Real-Time</a>
      <a href="compliance.php">Compliance & Audit</a>
    </nav>
  </header>

  <main class="bi-main">

    <!-- Header Section -->
    <div class="intro-section centered">
      <div class="header-content">
        <h1>Real-Time Analytics Dashboard</h1>
        <h2>Live system metrics</h2>
      </div>
    </div>

    <!-- KPI Section Box -->
    <div class="kpi-box">
      <div class="kpi-box-header">
        <h3>Live KPIs</h3>
      </div>

      <div class="kpi-refresh-controls">
        <button class="btn-refresh" id="manualRefreshBtn">🔄 Refresh Now</button>
        
        <div class="auto-refresh-toggle">
          <label>
            <input type="checkbox" id="autoRefreshToggle">
            <span>Auto-refresh</span>
          </label>
        </div>

        <div class="refresh-interval">
          <label>Interval:</label>
          <select id="refreshInterval">
            <option value="5">5 seconds</option>
            <option value="10" selected>10 seconds</option>
            <option value="15">15 seconds</option>
            <option value="30">30 seconds</option>
            <option value="60">1 minute</option>
          </select>
        </div>
      </div>

      <!-- KPI Cards Grid -->
      <div class="kpi-grid">
        <div class="kpi-card green">
          <div class="kpi-header">
            <span class="kpi-icon">💰</span>
            <span class="kpi-label">Revenue Total</span>
          </div>
          <div class="kpi-value">₱<span id="stockValue"><?php echo number_format($total_revenue,2); ?></span></div>
          <div class="kpi-desc">Across all warehouses</div>
        </div>

        <div class="kpi-card blue">
          <div class="kpi-header">
            <span class="kpi-icon">📦</span>
            <span class="kpi-label">Orders</span>
          </div>
          <div class="kpi-value"><span id="totalUnits"><?php echo $total_orders; ?></span></div>
          <div class="kpi-desc">Total orders generated</div>
        </div>

        <div class="kpi-card orange">
          <div class="kpi-header">
            <span class="kpi-icon">⚠️</span>
            <span class="kpi-label">Low Stock Alerts</span>
          </div>
          <div class="kpi-value"><span id="lowStock"><?php echo $low_stock; ?></span></div>
          <div class="kpi-desc">Requires attention</div>
        </div>
      </div>
    </div>

    <!-- ===================== -->
    <!-- Generated Results -->
    <!-- ===================== -->
    <div class="generated-results">
        <div class="section-header">
          <h2>Generated Results</h2>
      
      <!-- Recent Sales Orders -->
      <div class="result-card sales">
        <div class="section-header">
          <h3>📈Recent Sales Orders</h3>
          <span class="timestamp">Records: <?php echo $sales_records; ?></span>
        </div>
        <div class="table-wrap">
          <table class="bi-table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Created At</th>
              </tr>
              
            </thead>
            <tbody>
              <?php
              if($sales_result && $sales_result->num_rows>0){
                  while($row = $sales_result->fetch_assoc()){
                      $status = strtolower($row['payment_status']);
                      echo "<tr>
                          <td>#{$row['order_id']}</td>
                          <td>₱".number_format($row['total_amount'],2)."</td>
                          <td><span class='status status-$status'>".ucfirst($status)."</span></td>
                          <td>{$row['created_at']}</td>
                      </tr>";
                  }
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Accounts Receivable -->
      <div class="result-card  receivable">
        <div class="section-header">
          <h3>💳Recent Accounts Receivable</h3>
          <span class="timestamp">Records: <?php echo $receivable_records; ?></span>
        </div>
    
        <div class="table-wrap">
          <table class="bi-table">
            <thead>
              <tr>
                <th>INVOICE </th>
                <th>AMOUNT</th>
                <th>STATUS</th>
                <th>CREATED AT</th>
              </tr>
            </thead>
            <tbody>
              <?php
              if($receivable_result && $receivable_result->num_rows>0){
                  while($row = $receivable_result->fetch_assoc()){
                      $status = strtolower($row['status']);
                      echo "<tr>
                          <td>#{$row['invoice_id']}</td>
                          <td>₱".number_format($row['amount'],2)."</td>
                          <td><span class='status status-$status'>".ucfirst($status)."</span></td>
                          <td>{$row['created_at']}</td>
                      </tr>";
                  }
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- All Orders with Invoice Details -->
      <div class="result-card all-orders">
        <div class="section-header">
          <h3>🧾All Orders with Invoice Details</h3>
          <span class="timestamp">Records: <?php echo $all_orders_records; ?></span>
        </div>
        
        <div class="table-wrap">
          <table class="bi-table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Order Amount</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Invoice</th>
                <th>Invoice Amount</th>
                <th>Invoice Created At</th>
              </tr>
            </thead>
            <tbody>
              <?php
              if($all_orders_result && $all_orders_result->num_rows>0){
                  while($row = $all_orders_result->fetch_assoc()){
                      $status = strtolower($row['payment_status']);
                      echo "<tr>
                          <td>#{$row['order_id']}</td>
                          <td>₱".number_format($row['total_amount'],2)."</td>
                          <td><span class='status status-$status'>".ucfirst($status)."</span></td>
                          <td>{$row['created_at']}</td>
                          <td>#{$row['invoice_id']}</td>
                          <td>₱".number_format($row['invoice_amount'],2)."</td>
                          <td>{$row['invoice_created_at']}</td>
                      </tr>";
                  }
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

    </div> <!-- end generated-results -->

     <!-- DECISION INSIGHTS -->
<div class="decision-insights">
  <h2>Decision-Making Insights</h2>

  <!-- Finance Decision -->
  <div class="insight-card">
    <div class="insight-header">
      <h3>⏳Pending Invoices</h3>
      <span class="status status-<?php echo $risk_class; ?>">
        <?php echo $risk_level; ?> Risk
      </span>
    </div>
    <p>
      <?php echo $pending_count; ?> unpaid invoices totaling
      ₱<?php echo number_format($pending_amount,2); ?>
    </p>
    <p><strong>Action:</strong> <?php echo $action_text; ?></p>
  </div>

  <!-- Inventory Decision -->
  <div class="insight-card">
    <div class="insight-header">
      <h3>⚠️Inventory Risk</h3>
      <span class="status status-<?php echo $inventory_class; ?>">
        <?php echo $inventory_risk; ?> Risk
      </span>
    </div>
    <p><?php echo $low_items; ?> items are below minimum stock level</p>
    <p><strong>Action:</strong> <?php echo $inventory_action; ?></p>
  </div>

  <!-- Sales Decision -->
  <div class="insight-card">
    <div class="insight-header">
      <h3>💰Sales Performance</h3>
      <span class="status status-<?php echo $sales_class; ?>">
        <?php echo $sales_risk; ?>
      </span>
    </div>
    <p>Today's sales total: ₱<?php echo number_format($today_sales,2); ?></p>
    <p><strong>Action:</strong> <?php echo $sales_action; ?></p>
  </div>

</div>


<script>
// ======================
// MANUAL REFRESH
// ======================
document.getElementById("manualRefreshBtn").onclick = () => {
  location.reload();
};

// ======================
// AUTO REFRESH LOGIC
// ======================
let interval = null;

const autoToggle = document.getElementById("autoRefreshToggle");
const intervalSelect = document.getElementById("refreshInterval");

// Restore saved settings
window.onload = () => {
  const autoEnabled = localStorage.getItem("autoRefresh") === "true";
  const savedInterval = localStorage.getItem("refreshInterval");

  if (savedInterval) {
    intervalSelect.value = savedInterval;
  }

  if (autoEnabled) {
    autoToggle.checked = true;
    startAutoRefresh();
  }
};

// Start auto refresh
function startAutoRefresh() {
  clearInterval(interval);
  const seconds = intervalSelect.value * 1000;

  interval = setInterval(() => {
    location.reload();
  }, seconds);
}

// Stop auto refresh
function stopAutoRefresh() {
  clearInterval(interval);
}

// Toggle auto refresh
autoToggle.addEventListener("change", function () {
  localStorage.setItem("autoRefresh", this.checked);

  if (this.checked) {
    startAutoRefresh();
  } else {
    stopAutoRefresh();
  }
});

// Change interval
intervalSelect.addEventListener("change", function () {
  localStorage.setItem("refreshInterval", this.value);

  if (autoToggle.checked) {
    startAutoRefresh();
  }
});
</script>


</body>
</html>
