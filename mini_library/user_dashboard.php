<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Library Management Dashboard</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="css/user_dashboard.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <?php include "user_sidebar.php"; ?>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <?php 
    include "db.php"; // ✅ Connect to your DB (library_db)

    // ===== GET STATS FROM DATABASE =====

    // Total Books
    $totalBooksQuery = $conn->query("SELECT COUNT(*) AS total FROM books");
    $totalBooks = $totalBooksQuery->fetch_assoc()['total'] ?? 0;

    // Returned Books (books that have actual_return_date NOT NULL)
    $returnedBooksQuery = $conn->query("SELECT COUNT(*) AS returned FROM borrow_records WHERE actual_return_date IS NOT NULL");
    $returnedBooks = $returnedBooksQuery->fetch_assoc()['returned'] ?? 0;

    // Available Books (sum of available_copies)
    $availableBooksQuery = $conn->query("SELECT COUNT(*) AS available FROM books");
    $availableBooks = $availableBooksQuery->fetch_assoc()['available'] ?? 0;

    // Borrowed Books (books not yet returned)
    $borrowedBooksQuery = $conn->query("SELECT COUNT(*) AS borrowed FROM borrow_records WHERE actual_return_date IS NULL");
    $borrowedBooks = $borrowedBooksQuery->fetch_assoc()['borrowed'] ?? 0;
    ?>

    <section class="dashboard-container">
      <div class="dashboard-header">
        <h1>Library Dashboard</h1>
        <p>Overview of library activities and book records</p>
      </div>

      <!-- STATS CARDS -->
      <div class="stats-cards">
        <div class="card">
          <h2><i class="bi bi-book"></i> Total Books</h2>
          <p id="totalBooks"><?php echo $totalBooks; ?></p>
        </div>
        <div class="card">
          <h2><i class="bi bi-check-circle"></i> Returned Books</h2>
          <p id="returnedBooks"><?php echo $returnedBooks; ?></p>
        </div>
        <div class="card">
          <h2><i class="bi bi-clock-history"></i> Borrowed Books</h2>
          <p id="borrowedBooks"><?php echo $borrowedBooks; ?></p>
        </div>
        <div class="card">
          <h2><i class="bi bi-collection"></i> Books Available</h2>
          <p id="availableBooks"><?php echo $availableBooks; ?></p>
        </div>
      </div>

      <!-- BAR CHART -->
      <div class="chart-container">
        <canvas id="booksChart"></canvas>
      </div>
    </section>
  </div>

<script>
  const totalBooks = <?php echo $totalBooks; ?>;
  const returnedBooks = <?php echo $returnedBooks; ?>;
  const borrowedBooks = <?php echo $borrowedBooks; ?>;
  const availableBooks = <?php echo $availableBooks; ?>;

  const ctx = document.getElementById('booksChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Total Books', 'Returned Books', 'Borrowed Books', 'Available Books'],
      datasets: [{
        label: 'Library Statistics',
        data: [totalBooks, returnedBooks, borrowedBooks, availableBooks],
        backgroundColor: ['#4ecdc4','#1a535c','#ff6b6b','#ffe66d'],
        borderRadius: 5
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        title: { display: true, text: 'Book Distribution Overview', font: { size: 18 } },
        tooltip: {
          callbacks: {
            label: context => `${context.label}: ${context.parsed.y} books`
          }
        }
      },
      scales: {
        y: { beginAtZero: true, ticks: { color: '#333' } },
        x: { ticks: { color: '#333' } }
      }
    }
  });
</script>

</body>
</html>
