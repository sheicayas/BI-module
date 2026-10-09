<?php
// BI/security.php
require_once __DIR__ . '/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: /login.php");
  exit;
}

$role = $_SESSION['role'];
$allowed_security = ['admin', 'manager'];
if (!in_array($role, $allowed_security)) {
  http_response_code(403);
  echo "Access denied. Security management requires Admin or Manager role.";
  exit;
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Simulate user roles and permissions
$users = [
  [
    'id' => 1,
    'username' => 'finance_admin',
    'full_name' => 'Juliana Escalante',
    'role' => 'finance',
    'reports_access' => 'All Financial Reports',
    'permissions' => ['view', 'edit', 'export'],
    'status' => 'Active',
    'last_login' => '2025-01-22 14:32:15'
  ],
  [
    'id' => 2,
    'username' => 'sales_manager',
    'full_name' => 'Lorie Aguilar',
    'role' => 'sales',
    'reports_access' => 'Sales & Revenue Reports',
    'permissions' => ['view', 'export'],
    'status' => 'Active',
    'last_login' => '2025-01-22 11:15:42'
  ],
  [
    'id' => 3,
    'username' => 'inventory_analyst',
    'full_name' => 'Levy Fidel',
    'role' => 'inventory',
    'reports_access' => 'Inventory & Stock Reports',
    'permissions' => ['view'],
    'status' => 'Active',
    'last_login' => '2025-01-21 09:48:30'
  ],
  [
    'id' => 4,
    'username' => 'dept_head',
    'full_name' => 'Gov Gamilla',
    'role' => 'dept_head',
    'reports_access' => 'Department-Specific Reports',
    'permissions' => ['view', 'export'],
    'status' => 'Inactive',
    'last_login' => '2025-01-15 16:22:10'
  ],
  [
    'id' => 5,
    'username' => 'bi_analyst',
    'full_name' => 'Khylle Bautista',
    'role' => 'bi_analyst',
    'reports_access' => 'All Analytics & Reports',
    'permissions' => ['view', 'edit', 'export'],
    'status' => 'Active',
    'last_login' => '2025-01-22 13:55:05'
  ],
];

// Simulate sensitive reports with access restrictions
$sensitive_reports = [
  [
    'id' => 1,
    'name' => 'Executive Salary Report',
    'category' => 'Payroll',
    'sensitivity' => 'Highly Confidential',
    'authorized_roles' => ['admin', 'finance'],
    'view_access' => ['admin', 'finance'],
    'edit_access' => ['admin'],
    'export_access' => ['admin'],
    'current_access_level' => 'Restricted'
  ],
  [
    'id' => 2,
    'name' => 'Revenue & Profit Analysis',
    'category' => 'Finance',
    'sensitivity' => 'Confidential',
    'authorized_roles' => ['admin', 'manager', 'finance'],
    'view_access' => ['admin', 'manager', 'finance'],
    'edit_access' => ['admin', 'finance'],
    'export_access' => ['admin', 'finance'],
    'current_access_level' => 'Restricted'
  ],
  [
    'id' => 3,
    'name' => 'Tax Calculation Details',
    'category' => 'Compliance',
    'sensitivity' => 'Confidential',
    'authorized_roles' => ['admin', 'finance'],
    'view_access' => ['admin', 'finance'],
    'edit_access' => ['admin'],
    'export_access' => ['admin', 'finance'],
    'current_access_level' => 'Restricted'
  ],
  [
    'id' => 4,
    'name' => 'Vendor Contracts & Pricing',
    'category' => 'Business',
    'sensitivity' => 'Confidential',
    'authorized_roles' => ['admin', 'manager'],
    'view_access' => ['admin', 'manager'],
    'edit_access' => ['admin'],
    'export_access' => ['admin'],
    'current_access_level' => 'Restricted'
  ],
];

// Simulate access audit logs
$access_logs = [
  ['timestamp' => '2025-01-22 14:32:15', 'user' => 'finance_admin (Juliana Escalante)', 'action' => 'Viewed', 'report' => 'Executive Salary Report', 'result' => '✓ Allowed'],
  ['timestamp' => '2025-01-22 13:15:42', 'user' => 'sales_manager (Lorie Aguilar)', 'action' => 'Attempted Export', 'report' => 'Revenue & Profit Analysis', 'result' => '✗ Denied'],
  ['timestamp' => '2025-01-22 10:48:30', 'user' => 'bi_analyst (Khylle Bautista)', 'action' => 'Viewed', 'report' => 'Revenue & Profit Analysis', 'result' => '✓ Allowed'],
  ['timestamp' => '2025-01-21 16:22:10', 'user' => 'admin', 'action' => 'Modified Permissions', 'report' => 'Tax Calculation Details', 'result' => '✓ Executed'],
  ['timestamp' => '2025-01-21 15:55:05', 'user' => 'inventory_analyst (Levy Fidel)', 'action' => 'Attempted View', 'report' => 'Executive Salary Report', 'result' => '✗ Denied'],
];

// Role permission matrix
$role_permissions = [
  'admin' => ['view' => '✓', 'edit' => '✓', 'export' => '✓', 'delete' => '✓', 'manage_users' => '✓'],
  'manager' => ['view' => '✓', 'edit' => '✓', 'export' => '✓', 'delete' => '✗', 'manage_users' => '✗'],
  'finance' => ['view' => '✓', 'edit' => '✓', 'export' => '✓', 'delete' => '✗', 'manage_users' => '✗'],
  'sales' => ['view' => '✓', 'edit' => '✗', 'export' => '✓', 'delete' => '✗', 'manage_users' => '✗'],
  'inventory' => ['view' => '✓', 'edit' => '✗', 'export' => '✗', 'delete' => '✗', 'manage_users' => '✗'],
  'bi_analyst' => ['view' => '✓', 'edit' => '✓', 'export' => '✓', 'delete' => '✗', 'manage_users' => '✗'],
  'dept_head' => ['view' => '✓', 'edit' => '✗', 'export' => '✓', 'delete' => '✗', 'manage_users' => '✗'],
];

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'users';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Access & Security — Tire Supply BI</title>
<link rel="stylesheet" href="style2.css">
</head>
<body class="security-page">
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
      <a href="compliance.php">Compliance & Audit</a>
      <a class="active" href="security.php">Access & Security</a>
      <a href="/bi/logout.php">Logout</a>
    </nav>
    <div class="topbar-right">
      Signed: <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($role); ?>)
    </div>
  </header>

  <!-- Main Content -->
  <main class="bi-main">
    <!-- Intro Section -->
    <section class="intro-section">
      <h1>Access & Security Management</h1>
      <h2>Role-Based Access Control & Permissions</h2>
      <p>
        Manage user roles, permissions, and access levels to sensitive reports. Monitor access logs 
        and enforce data privacy and integrity across the Business Intelligence platform.
      </p>
    </section>

    <!-- Security Tabs -->
    <div class="security-tabs">
      <a href="?tab=users" class="<?php echo $tab === 'users' ? 'active' : ''; ?>">👥 Users & Roles</a>
      <a href="?tab=reports" class="<?php echo $tab === 'reports' ? 'active' : ''; ?>">📋 Report Access</a>
      <a href="?tab=permissions" class="<?php echo $tab === 'permissions' ? 'active' : ''; ?>">⚙️ Permission Matrix</a>
      <a href="?tab=audit" class="<?php echo $tab === 'audit' ? 'active' : ''; ?>">🔍 Access Audit</a>
    </div>

    <!-- TAB 1: Users & Roles -->
    <div class="tab-content <?php echo $tab === 'users' ? 'active' : ''; ?>">
      <div class="widget-card security">
        <h3>User Accounts & Roles</h3>
        <p style="color: #445b78; margin-bottom: 16px;">
          Manage system users, assign roles, and monitor activity. Click edit to modify user permissions.
        </p>
        
        <div class="user-grid">
          <?php foreach ($users as $user): ?>
          <div class="user-card">
            <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
            <div class="user-meta">@<?php echo htmlspecialchars($user['username']); ?></div>
            
            <span class="badge badge-role">Role: <?php echo ucfirst($user['role']); ?></span>
            <span class="badge <?php echo $user['status'] === 'Active' ? 'badge-active' : 'badge-inactive'; ?>">
              <?php echo $user['status']; ?>
            </span>
            
            <div class="user-permissions">
              <div><strong>Access Level:</strong> <?php echo htmlspecialchars($user['reports_access']); ?></div>
              <div style="margin-top: 6px;">
                <strong>Permissions:</strong> <?php echo implode(', ', array_map('ucfirst', $user['permissions'])); ?>
              </div>
              <div style="margin-top: 6px; font-size: 11px; color: #999;">
                Last login: <?php echo $user['last_login']; ?>
              </div>
            </div>
            
            <button class="btn-small" style="background: var(--accent); color: white; width: 100%; margin-top: 12px; padding: 8px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
              Edit Permissions
            </button>
          </div>
          <?php endforeach; ?>
        </div>

        <button class="btn" style="background: var(--primary); color: white; margin-top: 24px; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
          + Add New User
        </button>
      </div>
    </div>

    <!-- TAB 2: Report Access Control -->
    <div class="tab-content <?php echo $tab === 'reports' ? 'active' : ''; ?>">
      <div class="widget-card security">
        <h3>Sensitive Reports & Access Restrictions</h3>
        <p style="color: #445b78; margin-bottom: 16px;">
          Define which roles can view, edit, or export sensitive reports. Enforce strict access controls.
        </p>
        
        <table class="access-control-table">
          <thead>
            <tr>
              <th>Report Name</th>
              <th>Category</th>
              <th>Sensitivity</th>
              <th>View Access</th>
              <th>Edit Access</th>
              <th>Export Access</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($sensitive_reports as $report): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($report['name']); ?></strong></td>
              <td><?php echo htmlspecialchars($report['category']); ?></td>
              <td>
                <span class="<?php echo strpos($report['sensitivity'], 'Highly') !== false ? 'sensitivity-high' : 'sensitivity-medium'; ?>">
                  <?php echo htmlspecialchars($report['sensitivity']); ?>
                </span>
              </td>
              <td>
                <span class="access-badge access-allowed">
                  <?php echo implode(', ', array_map('ucfirst', $report['view_access'])); ?>
                </span>
              </td>
              <td>
                <span class="access-badge access-allowed">
                  <?php echo implode(', ', array_map('ucfirst', $report['edit_access'])); ?>
                </span>
              </td>
              <td>
                <span class="access-badge access-allowed">
                  <?php echo implode(', ', array_map('ucfirst', $report['export_access'])); ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div style="margin-top: 20px; padding: 16px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
          <strong>Security Note:</strong> These access levels are strictly enforced. Any unauthorized attempts are logged and can be reviewed in the Access Audit tab.
        </div>
      </div>
    </div>

    <!-- TAB 3: Permission Matrix -->
    <div class="tab-content <?php echo $tab === 'permissions' ? 'active' : ''; ?>">
      <div class="widget-card security">
        <h3>Role Permission Matrix</h3>
        <p style="color: #445b78; margin-bottom: 16px;">
          Complete overview of what each role can do within the BI system. ✓ = Allowed, ✗ = Denied
        </p>
        
        <table class="permission-matrix">
          <thead>
            <tr>
              <th>Role</th>
              <th>View Reports</th>
              <th>Edit Reports</th>
              <th>Export Data</th>
              <th>Delete Reports</th>
              <th>Manage Users</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($role_permissions as $role_name => $perms): ?>
            <tr>
              <td><?php echo ucfirst(str_replace('_', ' ', $role_name)); ?></td>
              <td><span class="check-<?php echo $perms['view'] === '✓' ? 'allowed' : 'denied'; ?>"><?php echo $perms['view']; ?></span></td>
              <td><span class="check-<?php echo $perms['edit'] === '✓' ? 'allowed' : 'denied'; ?>"><?php echo $perms['edit']; ?></span></td>
              <td><span class="check-<?php echo $perms['export'] === '✓' ? 'allowed' : 'denied'; ?>"><?php echo $perms['export']; ?></span></td>
              <td><span class="check-<?php echo $perms['delete'] === '✓' ? 'allowed' : 'denied'; ?>"><?php echo $perms['delete']; ?></span></td>
              <td><span class="check-<?php echo $perms['manage_users'] === '✓' ? 'allowed' : 'denied'; ?>"><?php echo $perms['manage_users']; ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="security-features">
          <div class="feature-item">
            <h4>🔐 Data Encryption</h4>
            <p>All sensitive data is encrypted in transit and at rest using AES-256 standards.</p>
          </div>
          <div class="feature-item">
            <h4>📝 Audit Logging</h4>
            <p>Every access attempt, modification, and export is logged with timestamp and user IP.</p>
          </div>
          <div class="feature-item">
            <h4>⏰ Session Management</h4>
            <p>Sessions expire after 30 minutes of inactivity. Multi-factor authentication available.</p>
          </div>
          <div class="feature-item">
            <h4>🚨 Alert System</h4>
            <p>Unusual access patterns trigger automatic alerts to security administrators.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 4: Access Audit Log -->
    <!-- TAB 4: Access Audit Log -->
<div class="tab-content <?php echo $tab === 'audit' ? 'active' : ''; ?>">
  <div class="widget-card security">
    <h3>Access Audit & Activity Log</h3>
    <p style="color: #445b78; margin-bottom: 16px;">
      Complete record of all access attempts, modifications, and data exports for security monitoring.
    </p>
    
    <table class="access-control-table">
      <thead>
        <tr>
          <th>Timestamp</th>
          <th>User</th>
          <th>Action</th>
          <th>Report / Resource</th>
          <th>Result</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($access_logs as $log): ?>
        <tr>
          <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
          <td><?php echo htmlspecialchars($log['user']); ?></td>
          <td><?php echo htmlspecialchars($log['action']); ?></td>
          <td><?php echo htmlspecialchars($log['report']); ?></td>
          <td>
            <span class="<?php echo strpos($log['result'], '✓') !== false ? 'log-result-allowed' : 'log-result-denied'; ?>">
              <?php echo htmlspecialchars($log['result']); ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <button class="btn" style="background: var(--primary); color: white; margin-top: 20px; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
      📥 Export Audit Log
    </button>
  </div>
</div>


  </main>
</div>
</body>
</html>