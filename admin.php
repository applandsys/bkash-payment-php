<?php
require_once "config.php";
session_start();
date_default_timezone_set("Asia/Dhaka");

// Secure session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_regenerate_id(true);

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Admin login check
if (!isset($_SESSION['admin'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
        hash_equals($_POST['user'], ADMIN_USER) &&
        hash_equals($_POST['pass'], ADMIN_PASS)) {
        $_SESSION['admin'] = true;
        session_regenerate_id(true);
    } else {
        echo "<form method='POST' style='padding: 50px; font-family: sans-serif; text-align: center;'>
                <h2 style='margin-bottom: 20px;'>Admin Login</h2>
                <input name='user' placeholder='Username' class='form-control mb-2'><br>
                <input name='pass' type='password' placeholder='Password' class='form-control mb-2'><br>
                <button type='submit' class='btn btn-primary'>Login</button>
              </form>";
        exit;
    }
}

// Toggle order status
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($_GET['toggle'] === 'completed') {
        $conn->query("UPDATE order_lists SET status = 'completed' WHERE id = $id");
    }
    header("Location: admin.php?page=" . ($_GET['page'] ?? 1));
    exit;
}

// Payment summaries
$todayTotal = $yesterdayTotal = $monthTotal = 0;
$todayQuery = "SELECT SUM(amount) AS total FROM order_lists WHERE status = 'paid' AND DATE(created_at) = CURDATE()";
$yesterdayQuery = "SELECT SUM(amount) AS total FROM order_lists WHERE status = 'paid' AND DATE(created_at) = CURDATE() - INTERVAL 1 DAY";
$monthQuery = "SELECT SUM(amount) AS total FROM order_lists WHERE status = 'paid' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";

if ($res = $conn->query($todayQuery)) $todayTotal = $res->fetch_assoc()['total'] ?? 0;
if ($res = $conn->query($yesterdayQuery)) $yesterdayTotal = $res->fetch_assoc()['total'] ?? 0;
if ($res = $conn->query($monthQuery)) $monthTotal = $res->fetch_assoc()['total'] ?? 0;

// Pagination
$limit = 25;
$page = isset($_GET['page']) && $_GET['page'] > 0 ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$countResult = $conn->query("SELECT COUNT(*) as total FROM order_lists");
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

$query = "SELECT * FROM order_lists ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

$orders = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $timestamp = strtotime($row['created_at']) + (6 * 60 * 60);
        $row['created_at_formatted'] = date("j F Y, h:i A", $timestamp);
        $orders[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>bKash Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background-color: #f6f6f6; font-family: 'Segoe UI', sans-serif; }
    .header-bar { background-color: #e2136e; color: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 25px; }
    .header-bar h2 { margin: 0; font-weight: 600; }
    .btn-refresh { background-color: #c51162; color: white; border: none; }
    .btn-refresh:hover { background-color: #a6004d; }
    .table-wrapper { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.05); overflow-x: auto; }
    th { background-color: #f8bbd0; color: #880e4f; }
    .pagination a { margin: 0 4px; }
    .btn-sm { font-size: 0.875rem; font-weight: 600; }
  </style>
</head>
<body>

<div class="container">
  <div class="header-bar d-flex justify-content-between align-items-center">
    <h2>📦 Dashboard</h2>
    <a href="?logout=true" class="btn btn-light">🚪 Logout</a>
  </div>

  <div class="row text-center mb-4">
    <div class="col-md-4 mb-2">
      <div class="p-3 bg-white rounded shadow-sm border-start border-success border-4">
        <h6 class="text-muted mb-1">আজকের মোট পেমেন্ট</h6>
        <h3 class="text-success mb-0">৳<?= number_format($todayTotal, 2) ?></h3>
      </div>
    </div>
    <div class="col-md-4 mb-2">
      <div class="p-3 bg-white rounded shadow-sm border-start border-warning border-4">
        <h6 class="text-muted mb-1">গতকালকের মোট পেমেন্ট</h6>
        <h3 class="text-warning mb-0">৳<?= number_format($yesterdayTotal, 2) ?></h3>
      </div>
    </div>
    <div class="col-md-4 mb-2">
      <div class="p-3 bg-white rounded shadow-sm border-start border-primary border-4">
        <h6 class="text-muted mb-1">এই মাসের মোট পেমেন্ট</h6>
        <h3 class="text-primary mb-0">৳<?= number_format($monthTotal, 2) ?></h3>
      </div>
    </div>
  </div>

  <div class="table-wrapper mb-4">
    <div class="d-flex justify-content-end mb-2">
      <form method="GET">
        <button type="submit" class="btn btn-refresh">🔄 Refresh</button>
      </form>
    </div>

    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <th>Time</th>
          <th>NID</th>
          <th>DOB</th>
          <th>WhatsApp</th>
          <th>Amount</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $row): ?>
          <?php
            $isCompleted = $row['status'] === 'completed';
            $badgeColor  = $isCompleted ? 'background-color:#e2136e;' : 'background-color:#f06292;';
            $actionLabel = !$isCompleted ? '✅ Mark Complete' : '';
            $toggleTo = !$isCompleted ? 'completed' : '';
          ?>
          <tr>
            <td><?= $row['created_at_formatted'] ?></td>
            <td><?= htmlspecialchars($row['nid']) ?></td>
            <td><?= htmlspecialchars($row['dob']) ?></td>
            <td><?= htmlspecialchars($row['whatsappNumber']) ?></td>
            <td><?= htmlspecialchars($row['amount']) ?></td>
            <td><span class="badge px-3 py-2 text-white" style="<?= $badgeColor ?> border-radius:6px;">
              <?= $isCompleted ? '✅ Completed' : '❗ Incomplete' ?></span></td>
            <td>
              <?php if (!$isCompleted): ?>
              <a href="?toggle=<?= $toggleTo ?>&id=<?= $row['id'] ?>&page=<?= $page ?>"
                 class="btn btn-sm text-white" style="background-color:#ffc107; border:none; border-radius:5px;">
                 <?= $actionLabel ?>
              </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <nav>
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?>">Page <?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>