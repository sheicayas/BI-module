<?php 
// =========================
// BI/index.php — Dynamic Overview Dashboard
// =========================

// =========================
// Database Connection
// =========================
$conn = new mysqli("localhost", "root", "", "erp_indang");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// =========================
// Define Report Tables
// Only tables that are considered reports
// =========================
$reportTables = ['orders', 'payments', 'inventory']; 

$totalReports = 0;
foreach ($reportTables as $table) {
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM `$table`");
    if ($res && $res->fetch_assoc()['cnt'] > 0) {
        $totalReports++;
    }
}

// =========================
// Fetch Total Users
// =========================
$usersResult = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalUsers = $usersResult ? $usersResult->fetch_assoc()['total'] : 0;

// =========================
// Fetch Total Orders
// =========================
$ordersResult = $conn->query("SELECT COUNT(*) AS total FROM orders");
$totalOrders = $ordersResult ? $ordersResult->fetch_assoc()['total'] : 0;

// =========================
// Count Active Data Sources (tables with at least 1 row)
// =========================
$dataSources = 0;
$tablesResult = $conn->query("SHOW TABLES");
if ($tablesResult) {
    while ($row = $tablesResult->fetch_array()) {
        $tableName = $row[0];
        $countResult = $conn->query("SELECT COUNT(*) AS cnt FROM `$tableName`");
        if ($countResult && $countResult->fetch_assoc()['cnt'] > 0) {
            $dataSources++;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Tire Supply BI — Overview</title>
<link rel="stylesheet" href="style2.css">
</head>

<body class="dashboard-page">
<div class="bi-shell">

  <!-- ================= TOP BAR ================= -->
  <header class="bi-topbar">
    <div class="topbar-left">
      <div class="brand">Tire Supply BI</div>
    </div>

    <nav class="topbar-nav">
      <a class="active" href="index.php">Overview</a>
      <a href="report.php">Standard & Custom</a>
      <a href="realtime.php">Real-Time</a>
      <a href="compliance.php">Compliance</a>
    </nav>
  </header>

  <!-- ================= MAIN ================= -->
  <main class="bi-main">

    <!-- ================= HERO ================= -->
    <section class="intro-section centered">
      <h1>Welcome to BI & Reports</h1>
      <p>
        Access powerful business intelligence with operational reports
        designed to support accurate decision making across Tire Supply organization.
      </p>
    </section>

    <!-- ================= SUMMARY CARDS ================= -->
    <section class="stats-grid">

      <div class="stat-card">
        <h3><?= $totalReports ?></h3>
        <p>Total Reports</p>
      </div>

      <div class="stat-card">
        <h3><?= $totalUsers ?></h3>
        <p>Users</p>
      </div>

      <div class="stat-card">
        <h3><?= $dataSources ?></h3>
        <p>Data Sources</p>
      </div>

      <div class="stat-card">
        <h3><?= $totalOrders ?></h3>
        <p>Total Orders</p>
      </div>

    </section>

    <!-- ================= MODULE SECTION ================= -->
    <section class="bi-feature-grid">

      <div class="feature-card">
        <div class="icon">📑</div>
        <h3>Standard & Custom Reports <span class="badge">Core</span></h3>
        <p>Generate structured reports.</p>
        <button class="btn btn-primary" onclick="location.href='report.php'">
          Explore Module →
        </button>
      </div>

      <div class="feature-card">
        <div class="icon">⚡</div>
        <h3>Real-Time Analytics <span class="badge">Live</span></h3>
        <p>Monitor live data streams and operational metrics.</p>
        <button class="btn btn-primary" onclick="location.href='realtime.php'">
          Explore Module →
        </button>
      </div>

      <div class="feature-card">
        <div class="icon">📜</div>
        <h3>Compliance & Audit <span class="badge">Secure</span></h3>
        <p>Review transactions, and audit trails for accountability.</p>
        <button class="btn btn-primary" onclick="location.href='compliance.php'">
          Explore Module →
        </button>
      </div>

    </section>

  </main>
</div>
</body>
</html>
