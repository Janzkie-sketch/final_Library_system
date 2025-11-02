<?php
session_start();
include "db.php";

// ✅ Get total books
$total_books_query = $conn->query("SELECT COUNT(*) AS total FROM books");
$total_books = $total_books_query ? $total_books_query->fetch_assoc()['total'] : 0;

// ✅ Get issued books
$issued_books_query = $conn->query("SELECT COUNT(*) AS total FROM borrow_records");
$issued_books = $issued_books_query ? $issued_books_query->fetch_assoc()['total'] : 0;

// ✅ Get total users
$total_users_query = $conn->query("SELECT COUNT(*) AS total FROM users");
$total_users = $total_users_query ? $total_users_query->fetch_assoc()['total'] : 0;

// ✅ Compute available books
$available_books_query = $conn->query("SELECT SUM(available_copies) AS available FROM books");
$available_books = $available_books_query ? $available_books_query->fetch_assoc()['available'] : 0;

// ✅ Get recent activities (latest 3 from each)
$recent_borrows = $conn->query("
  SELECT b.title, br.borrower_name, br.date_borrowed 
  FROM borrow_records br
  JOIN books b ON b.id = br.book_id
  ORDER BY br.date_borrowed DESC 
  LIMIT 1
");

$recent_books = $conn->query("
  SELECT title 
  FROM books 
  ORDER BY id DESC 
  LIMIT 1
");

$recent_users = $conn->query("
  SELECT CONCAT(first_name, ' ', last_name) AS fullname 
  FROM users 
  ORDER BY id DESC 
  LIMIT 1
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background: linear-gradient(135deg, #fff5f7, #ffe4e6);
      font-family: 'Poppins', sans-serif;
      color: #333;
    }

    .content {
      padding: 2rem;
      margin-left: 220px;
    }

    .stat-card {
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
      border-radius: 15px;
      transition: all 0.3s ease;
      background: white;
    }

    .stat-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
    }

    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
    }

    .stat-label {
      color: #6c757d;
      font-size: 1rem;
    }

    .recent-activity {
      background: white;
      border-radius: 15px;
      padding: 1.5rem;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    footer {
      margin-top: 3rem;
      color: #888;
      text-align: center;
    }
  </style>
</head>
<body>

  <?php include "sidebar.php"; ?>

  <div class="content">
    <h2 class="mb-5 text-center"> Dashboard Overview</h2>

    <div class="row text-center g-4">
      <div class="col-md-4 col-12">
        <div class="card stat-card p-4">
          <i class="bi bi-book-fill display-5 text-primary mb-2"></i>
          <h4 class="stat-number"><?= $total_books ?></h4>
          <p class="stat-label">All Books</p>
        </div>
      </div>
      <div class="col-md-4 col-12">
        <div class="card stat-card p-4">
          <i class="bi bi-journal-check display-5 text-success mb-2"></i>
          <h4 class="stat-number"><?= $issued_books ?></h4>
          <p class="stat-label">Borrowed Books</p>
        </div>
      </div>
      <div class="col-md-4 col-12">
        <div class="card stat-card p-4">
          <i class="bi bi-people-fill display-5 text-danger mb-2"></i>
          <h4 class="stat-number"><?= $total_users ?></h4>
          <p class="stat-label">Users</p>
        </div>
      </div>
    </div>

    <!-- CHART -->
    <div class="mt-5 bg-white p-4 rounded shadow-sm text-center">
      <h5 class="text-center mb-4">
        <i class="bi bi-pie-chart-fill text-primary"></i> Book Overview
      </h5>
      <div class="d-flex justify-content-center">
        <canvas id="bookChart" style="max-width: 400px; max-height: 400px;"></canvas>
      </div>
    </div>

    <!-- ✅ RECENT ACTIVITIES (Dynamic Now) -->
    <div class="recent-activity mt-5">
      <h5><i class="bi bi-clock-history"></i> Recent Activities</h5>
      <ul class="list-group list-group-flush mt-3">
        <?php 
        // 📕 Recently borrowed books
        if ($recent_borrows->num_rows > 0) {
          while ($row = $recent_borrows->fetch_assoc()) {
            echo "<li class='list-group-item'> Book borrowed: <strong>{$row['title']}</strong> by <em>{$row['borrower_name']}</em> on <small>{$row['date_borrowed']}</small></li>";
          }
        }

        // 🆕 Recently added books
        if ($recent_books->num_rows > 0) {
          while ($row = $recent_books->fetch_assoc()) {
            echo "<li class='list-group-item'> New book added: <strong>{$row['title']}</strong></li>";
          }
        }

        // 👤 Recently registered users
        if ($recent_users->num_rows > 0) {
          while ($row = $recent_users->fetch_assoc()) {
            echo "<li class='list-group-item'> New user registered: <strong>{$row['fullname']}</strong></li>";
          }
        }
        ?>
      </ul>
    </div>

    <footer class="mt-4">Library Management System</footer>
  </div>

  <!-- JS SECTION -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  const ctx = document.getElementById('bookChart');
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['All Books', 'Borrowed Books'],
      datasets: [{
        data: [<?= $total_books ?>, <?= $issued_books ?>],
        backgroundColor: ['#0d6efd', '#dc3545'],
        hoverOffset: 8
      }]
    },
    options: {
      plugins: { legend: { position: 'bottom' } }
    }
  });
  </script>
</body>
</html>
