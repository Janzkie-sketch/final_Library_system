<div class="sidebar">
  <h3>Admin</h3>
  <a href="admin_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="manage_books.php"><i class="bi bi-journal-bookmark-fill"></i> Manage Books</a>
  <a href="view_records.php"><i class="bi bi-view-list"></i> View Records</a>
  <a href="manage_users.php"><i class="bi bi-people-fill"></i> Manage Users</a>
  <a href="damaged_books.php"><i class="bi bi-exclamation-triangle-fill"></i> Damaged Books</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>


  <h6>
    Hello, 
    <?php 
    if (isset($_SESSION['username'])) {
        echo htmlspecialchars($_SESSION['username']); 
    } else {
        echo "Guest";
    }
    ?>
  </h6>
</div>

<link rel="stylesheet" href="css/sidebar.css">
