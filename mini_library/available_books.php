<?php
session_start();
include "add_record.php";

// 🧠 If AJAX search request
if (isset($_POST['ajax'])) {
    $search = mysqli_real_escape_string($conn, $_POST['query']);

    $query = "SELECT * FROM books WHERE available_copies > 0";

    if (!empty($search)) {
        $query .= " AND (
                      title LIKE '%$search%' OR
                      author LIKE '%$search%' OR
                      publisher LIKE '%$search%' OR
                      year_published LIKE '%$search%' OR
                      status LIKE '%$search%'
                    )";
    }

    $query .= " ORDER BY title ASC";

    $result = $conn->query($query);
    $counter = 1;
    $output = '';

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $disabled = $row['available_copies'] <= 0 ? 'disabled' : '';
            $output .= "
            <tr>
              <td>{$counter}</td>
              <td>{$row['title']}</td>
              <td>{$row['author']}</td>
              <td>{$row['available_copies']}</td>
              <td>{$row['status']}</td>
              <td>{$row['publisher']}</td>
              <td>{$row['year_published']}</td>
              <td>
                <button class='btn btn-primary btn-sm borrowBtn'
                  data-bs-toggle='modal'
                  data-bs-target='#borrowModal'
                  data-id='{$row['id']}'
                  data-title='{$row['title']}'
                  data-copies='{$row['available_copies']}'
                  $disabled>
                  Borrow
                </button>
              </td>
            </tr>";
            $counter++;
        }
    } else {
        $output = "<tr><td colspan='8'>No matching books found.</td></tr>";
    }

    echo $output;
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Available Books</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    body {
      background: #f8f9fa;
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
  </style>
</head>
<body>

<?php include "user_sidebar.php"; ?>
  
<div class="content"> 
  <h2 class="mb-4 title">Available Books</h2>

  <!-- ✅ Search Bar -->
  <div class="d-flex search-bar">
    <input type="text" id="search" class="form-control me-2" placeholder="Search books...">
  </div>

  <table class="table table-bordered table-hover text-center align-middle book-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Book Title</th>
        <th>Book Author</th>
        <th>Copies</th>
        <th>Status</th>
        <th>Publisher</th>
        <th>Year Published</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody id="book-data">
      <tr><td colspan="8">Loading available books...</td></tr>
    </tbody>
  </table>
</div>


<!-- ✅ Borrow Modal -->
<div class="modal fade" id="borrowModal" tabindex="-1" aria-labelledby="borrowModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="borrowModalLabel">Borrow Book</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="book_id" id="bookIdInput">
          <div class="mb-3">
            <label class="form-label">Book Title</label>
            <input type="text" class="form-control" id="bookTitleInput" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Borrower ID</label>
            <input type="text" name="borrower_id" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Borrower Name</label>
            <input type="text" name="borrower_name" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Course</label>
            <select name="course" class="form-select" required>
              <option value=""></option>
              <option value="BSIT">BSIT - Information Technology</option>
              <option value="BSCRIM">BSCRIM - Criminology</option>
              <option value="BECED">BECED - Early Childhood Education</option>
              <option value="BSHM">BSHM - Hospitality Management</option>
              <option value="BEED">BEED - Elementary Education</option>
              <option value="BPA">BPA - Public Administration</option>
              <option value="BSA">BSA - Entrepreneurship</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Copies to Borrow (Max <span id="maxCopiesDisplay"></span>)</label>
            <input type="number" name="copies_borrowed" id="copiesInput" class="form-control" min="1" required onkeydown="return false;">
          </div>
          <div class="mb-3">
            <label class="form-label">Return Date</label>
            <input type="date" name="return_date" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="confirm_borrow" class="btn btn-primary">Confirm Borrow</button>
        </div>
      </form>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ✅ Borrow Modal JS -->
<script>
  const borrowModal = document.getElementById('borrowModal')
  borrowModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget
    const bookId = button.getAttribute('data-id')
    const bookTitle = button.getAttribute('data-title')
    const copies = button.getAttribute('data-copies')

    document.getElementById('bookIdInput').value = bookId
    document.getElementById('bookTitleInput').value = bookTitle
    document.getElementById('maxCopiesDisplay').innerText = copies
    document.getElementById('copiesInput').max = copies
  })
</script>

<!-- ✅ Live Search (AJAX) -->
<script>
$(document).ready(function(){
  function loadBooks(query = '') {
    $.ajax({
      url: "available_books.php",
      method: "POST",
      data: { ajax: 1, query: query },
      success: function(data) {
        $("#book-data").html(data);
      }
    });
  }

  loadBooks(); // Load all available books on page load

  $("#search").on("keyup", function(){
    var term = $(this).val();
    loadBooks(term);
  });
});
</script>

<!-- ✅ SweetAlert Messages -->
<?php if (isset($_SESSION['message'])): ?>
<script>
Swal.fire({
  icon: '<?= $_SESSION['message_type']; ?>',
  title: '<?= ($_SESSION['message_type'] == "error") ? "Error!" : "Success!"; ?>',
  text: '<?= $_SESSION['message']; ?>',
  confirmButtonColor: '#3085d6',
  confirmButtonText: 'OK'
});
</script>
<?php
unset($_SESSION['message']);
unset($_SESSION['message_type']);
endif;
?>
</body>
</html>
