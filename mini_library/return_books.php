<?php
session_start();
include "db.php";

// 🧠 Detect if this is an AJAX request (for live search)
if (isset($_POST['ajax'])) {
  $search = mysqli_real_escape_string($conn, $_POST['query']);

  $query = "SELECT 
              br.id AS record_id, 
              br.book_id,
              br.borrower_name, 
              br.borrower_course, 
              br.copies_borrowed,
              br.date_borrowed, 
              br.expected_return_date,
              b.title, 
              b.author
            FROM borrow_records br
            JOIN books b ON br.book_id = b.id
            WHERE br.actual_return_date IS NULL";

  if (!empty($search)) {
    $query .= " AND (
                  b.title LIKE '%$search%' OR
                  b.author LIKE '%$search%' OR
                  br.borrower_name LIKE '%$search%' OR
                  br.borrower_course LIKE '%$search%' OR
                  br.date_borrowed LIKE '%$search%' OR
                  br.expected_return_date LIKE '%$search%'
                )";
  }

  $query .= " ORDER BY br.date_borrowed DESC";
  $result = mysqli_query($conn, $query);
  $counter = 1;

  if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
      echo "
        <tr>
          <td>{$counter}</td>
          <td>{$row['title']}</td>
          <td>{$row['author']}</td>
          <td>{$row['borrower_name']}</td>
          <td>{$row['borrower_course']}</td>
          <td>{$row['copies_borrowed']}</td>
          <td>{$row['date_borrowed']}</td>
          <td>{$row['expected_return_date']}</td>
          <td>
            <form action='return_process.php' method='POST' class='returnForm'>
              <input type='hidden' name='record_id' value='{$row['record_id']}'>
              <input type='hidden' name='book_id' value='{$row['book_id']}'>
              <input type='hidden' name='copies_borrowed' value='{$row['copies_borrowed']}'>
              <input type='hidden' name='return_book' value='1'>
              <button type='submit' class='btn btn-primary btn-sm'>
                Return
              </button>
            </form>
          </td>
        </tr>";
      $counter++;
    }
  } else {
    echo "<tr><td colspan='9'>No matching records found.</td></tr>";
  }

  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Return Books</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <style>
    body { background: #f8f9fa; font-family: 'Poppins', sans-serif; }
    .title { font-weight: 700; color: black; }
    .book-table thead { background: #212529; color: white; }
    .content { margin-left: 250px; background: white; border-radius: 15px;
               box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 50px;
               max-width: 1250px; padding: 30px; }
    .search-bar { max-width: 400px; margin-bottom: 20px; }
  </style>
</head>
<body>

<?php include "user_sidebar.php"; ?>

<div class="content"> 
  <h2 class="mb-4 title">Return Books</h2>

  <div class="d-flex search-bar">
    <input type="text" id="search" class="form-control me-2" placeholder="Search books...">
  </div>

  <table class="table table-bordered table-hover text-center align-middle book-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Book Title</th>
        <th>Author</th>
        <th>Borrower Name</th>
        <th>Course</th>
        <th>Copies Borrowed</th>
        <th>Date Borrowed</th>
        <th>Expected Return</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody id="book-data">
      <tr><td colspan='9'>Loading data...</td></tr>
    </tbody>
  </table>
</div>

<!-- ✅ AJAX SCRIPT -->
<script>
$(document).ready(function(){
  function loadData(query = '') {
    $.ajax({
      url: "return_books.php",
      method: "POST",
      data: { ajax: 1, query: query },
      success: function(data) {
        $("#book-data").html(data);
      }
    });
  }

  loadData(); // initial load

  $("#search").on("keyup", function(){
    var searchTerm = $(this).val();
    loadData(searchTerm);
  });

  // ✅ Automatic return when clicking "Return"
  $(document).on("submit", ".returnForm", function(e){
    e.preventDefault();
    let form = $(this);

    $.ajax({
      url: form.attr('action'),
      method: "POST",
      data: form.serialize(),
      success: function(response) {
        if (response.trim() === "success") {
          Swal.fire({
            icon: 'success',
            title: 'Successfully Returned!',
            text: 'The book has been returned successfully.',
            showConfirmButton: false,
            timer: 2000
          });
          loadData(); // refresh table
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Something went wrong while returning the book.'
          });
        }
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Server Error!',
          text: 'Could not connect to the server.'
        });
      }
    });
  });
});
</script>
</body>
</html>
