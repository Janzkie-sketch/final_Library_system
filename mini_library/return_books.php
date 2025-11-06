<?php
session_start();
include "db.php";

// 🧠 Detect if this is an AJAX request (for live search)
if (isset($_POST['ajax'])) {
  $search = mysqli_real_escape_string($conn, $_POST['query']);

  $query = "SELECT 
              br.id AS record_id, 
              br.book_id,
              br.borrower_id,
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
                  br.borrower_id LIKE '%$search%' OR
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
          <td>{$row['borrower_id']}</td>
          <td>{$row['borrower_name']}</td>
          <td>{$row['borrower_course']}</td>
          <td>{$row['copies_borrowed']}</td>
          <td>{$row['date_borrowed']}</td>
          <td>{$row['expected_return_date']}</td>
          <td>
            <div class='d-flex justify-content-center gap-2'>
              <form action='return_process.php' method='POST' class='returnForm'>
                <input type='hidden' name='record_id' value='{$row['record_id']}'>
                <input type='hidden' name='book_id' value='{$row['book_id']}'>
                <input type='hidden' name='copies_borrowed' value='{$row['copies_borrowed']}'>
                <input type='hidden' name='return_book' value='1'>
                <button type='submit' class='btn btn-primary btn-sm'>Return</button>
              </form>

              <button 
                class='btn btn-danger btn-sm lostBookBtn'
                data-record_id='{$row['record_id']}'
                data-book_id='{$row['book_id']}'
                data-borrower_id='{$row['borrower_id']}'
                data-title=\"{$row['title']}\" 
                data-author=\"{$row['author']}\" 
                data-borrower=\"{$row['borrower_name']}\" 
                data-course=\"{$row['borrower_course']}\" 
                data-copies_borrowed=\"{$row['copies_borrowed']}\"
              >
                Lost Book
              </button>
            </div>
          </td>
        </tr>";
      $counter++;
    }
  } else {
    echo "<tr><td colspan='10'>No matching records found.</td></tr>";
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
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <style>
    body { 
      background: #f8f9fa; 
      font-family: 'Poppins', sans-serif; 
    }
    .title { 
      font-weight: 700; 
      color: black; 
    }
    .book-table thead { 
      background: #212529; 
      color: white; 
    }
    .content { 
      margin-left: 250px; 
      background: white; 
      border-radius: 15px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
      margin-top: 50px;
      max-width: 1250px; 
      padding: 30px; 
    }
    .search-bar { 
      max-width: 400px; 
      margin-bottom: 20px; 
    }
      .table thead th {
      background-color: #212529;
      color: #fff;
    }
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
        <th>Borrower ID</th>
        <th>Borrower Name</th>
        <th>Course</th>
        <th>Copies Borrowed</th>
        <th>Date Borrowed</th>
        <th>Expected Return</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody id="book-data">
      <tr><td colspan='10'>Loading data...</td></tr>
    </tbody>
  </table>
</div>

<!-- 📘 Lost Book Modal -->
<div class="modal fade" id="lostBookModal" tabindex="-1" aria-labelledby="lostBookModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="lostBookModalLabel">Report Lost Book</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="lostBookForm">
        <div class="modal-body">
          <div class="mb-2">
            <label>Borrower ID:</label>
            <input type="text" class="form-control" id="lost_borrower_id" name="lost_borrower_id" readonly>
          </div>
          <div class="mb-2">
            <label>Book Title:</label>
            <input type="text" class="form-control" id="lost_title" name="lost_title" readonly>
          </div>
          <div class="mb-2">
            <label>Author:</label>
            <input type="text" class="form-control" id="lost_author" name="lost_author" readonly>
          </div>
          <div class="mb-2">
            <label>Borrower:</label>
            <input type="text" class="form-control" id="lost_borrower" name="lost_borrower" readonly>
          </div>
          <div class="mb-2">
            <label>Course:</label>
            <input type="text" class="form-control" id="lost_course" name="lost_course" readonly>
          </div>
          <div class="mb-2">
            <label>Total Borrowed:</label>
            <input type="text" class="form-control" id="lost_total" name="lost_total" readonly>
          </div>
          <div class="mb-2">
            <label>Copies Lost:</label>
            <input type="number" class="form-control" id="lost_copies" name="lost_copies" min="1" required>
          </div>
          <div class="mb-2">
            <label>Remarks:</label>
            <textarea 
              class="form-control" 
              id="lost_remarks" 
              name="lost_remarks" 
              rows="3" 
              maxlength="50" 
              placeholder="e.g (misplaced, stolen, etc...)"
            ></textarea>
            <small id="remarks_counter" class="text-muted">0 / 50 characters</small>
          </div>
          <input type="hidden" id="lost_record_id" name="record_id">
          <input type="hidden" id="lost_book_id" name="book_id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Submit Lost Book</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ✅ AJAX SCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

  // ✅ Silent Return
  $(document).on("submit", ".returnForm", function(e){
    e.preventDefault();
    let form = $(this);
    $.ajax({
      url: form.attr('action'),
      method: "POST",
      data: form.serialize(),
      success: function(response) {
        if (response.trim() === "success") {
          loadData();
        } else {
          console.error("Return failed:", response);
        }
      },
      error: function() {
        console.error("Server connection error.");
      }
    });
  });

  // 📘 Handle Lost Book Button Click
  $(document).on("click", ".lostBookBtn", function(){
    const totalBorrowed = $(this).data("copies_borrowed");

    $("#lost_record_id").val($(this).data("record_id"));
    $("#lost_book_id").val($(this).data("book_id"));
    $("#lost_borrower_id").val($(this).data("borrower_id"));
    $("#lost_title").val($(this).data("title"));
    $("#lost_author").val($(this).data("author"));
    $("#lost_borrower").val($(this).data("borrower"));
    $("#lost_course").val($(this).data("course"));
    $("#lost_total").val(totalBorrowed);

    // Reset + apply max limit
    $("#lost_copies").val("");
    $("#lost_copies").attr("max", totalBorrowed);

    $("#lost_copies").on("input", function() {
      const entered = parseInt($(this).val());
      if (entered > totalBorrowed) {
        $(this).val(totalBorrowed);
        alert(`⚠️ You can only mark up to ${totalBorrowed} copies as lost.`);
      }
    });

    $("#lost_remarks").val("");
    $("#remarks_counter").text("0 / 50 characters");

    var lostModal = new bootstrap.Modal(document.getElementById('lostBookModal'));
    lostModal.show();
  });

  // ✍️ Remarks live character counter
  $("#lost_remarks").on("input", function() {
    const currentLength = $(this).val().length;
    $("#remarks_counter").text(`${currentLength} / 50 characters`);
    if (currentLength >= 50) {
      $("#remarks_counter").addClass("text-danger");
    } else {
      $("#remarks_counter").removeClass("text-danger");
    }
  });

  // 📤 Handle Lost Book Form Submission
  $("#lostBookForm").on("submit", function(e){
    e.preventDefault();
    $.ajax({
      url: "lost_book_process.php",
      method: "POST",
      data: $(this).serialize(),
      success: function(response){
        if(response.trim() === "success"){
          var lostModalEl = document.getElementById('lostBookModal');
          var modalInstance = bootstrap.Modal.getInstance(lostModalEl);
          modalInstance.hide();
          loadData();
        } else {
          alert("Error processing lost book.");
        }
      },
      error: function(){
        alert("Server error while processing lost book.");
      }
    });
  });
});
</script>
</body>
</html>

