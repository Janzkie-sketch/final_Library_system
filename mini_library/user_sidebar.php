<?php
// Ensure session is started for username display
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<div class="sidebar">
  <h3>Librarian</h3>
  <a href="user_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="available_books.php"><i class="bi bi-book-fill"></i> Borrow Books</a>

  <a href="return_books.php"><i class="bi bi-arrow-counterclockwise"></i> Return Books</a>
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
