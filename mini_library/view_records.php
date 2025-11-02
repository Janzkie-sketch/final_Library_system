<?php
session_start();
include "db.php";

# ✅ Handle delete request here (before HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_id'])) {
    $record_id = intval($_POST['record_id']);

    // Check if record exists
    $check = $conn->prepare("SELECT * FROM borrow_records WHERE id = ?");
    $check->bind_param("i", $record_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['message'] = "Record not found!";
        $_SESSION['message_type'] = "error";
        header("Location: view_records.php");
        exit();
    }

    $record = $result->fetch_assoc();
    $check->close();

    // Optional: ibalik ang available_copies kung wala pa na-return
    if (empty($record['actual_return_date'])) {
        $book_id = $record['book_id'];
        $copies = $record['copies_borrowed'];

        $updateBook = $conn->prepare("UPDATE books SET available_copies = available_copies + ? WHERE id = ?");
        $updateBook->bind_param("ii", $copies, $book_id);
        $updateBook->execute();
        $updateBook->close();
    }

    // Delete the record
    $delete = $conn->prepare("DELETE FROM borrow_records WHERE id = ?");
    $delete->bind_param("i", $record_id);

    if ($delete->execute()) {
        $_SESSION['message'] = "Record deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting record.";
        $_SESSION['message_type'] = "error";
    }

    $delete->close();
    $conn->close();

    header("Location: view_records.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Borrow Records</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background: #fae1e1ff; 
      font-family: 'Poppins', sans-serif;
      color: #333;
    }
    .content-wrapper {
      margin-left: 250px;
      padding: 20px;
    }
    h2 {
      font-weight: 700;
      color: #000;
    }
    .table-container {
      background: #fff;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      padding: 10px;
    }
    .table thead th {
      background-color: #212529;
      color: #fff;
    }
    .search-container {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 15px;
    }
    .search-container input {
      width: 300px;
      border-radius: 5px;
      border: 1px solid #ccc;
      padding: 8px 12px;
    }
  </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="content-wrapper">
  <h2 class="mb-4 text-center">Borrow Records</h2>

  <!-- 🔍 Search Bar -->
  <div class="search-container">
    <input type="text" id="searchInput" placeholder="Search by name, title, or course...">
  </div>

  <div class="container table-container">
    <table id="recordsTable" class="table table-bordered table-striped text-center align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>Book Title</th>
          <th>Author</th>
          <th>Borrower Name</th>
          <th>Course</th>
          <th>Date Borrowed</th>
          <th>Expected Return</th>
          <th>Actual Return</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $sql = "SELECT br.*, b.title, b.author 
                FROM borrow_records br
                JOIN books b ON br.book_id = b.id
                ORDER BY br.date_borrowed DESC";
        $result = $conn->query($sql);
        $counter = 1;

        if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              $status = $row['actual_return_date'] 
                        ? "<span class='badge bg-success'>Returned</span>" 
                        : "<span class='badge bg-danger'>Not Returned</span>";

              echo "
              <tr>
                <td>{$counter}</td>
                <td>{$row['title']}</td>
                <td>{$row['author']}</td>
                <td>{$row['borrower_name']}</td>
                <td>{$row['borrower_course']}</td>
                <td>{$row['date_borrowed']}</td>
                <td>{$row['expected_return_date']}</td>
                <td>" . ($row['actual_return_date'] ?: "<span class='text-danger'>—</span>") . "</td>
                <td>$status</td>
                <td>
                  <form method='POST' class='deleteForm d-inline'>
                    <input type='hidden' name='record_id' value='{$row['id']}'>
                    <button type='button' class='btn btn-danger btn-sm deleteBtn'>
                      <i class='bi bi-trash'></i>
                    </button>
                  </form>
                </td>
              </tr>";
              $counter++;
          }
        } else {
          echo "<tr><td colspan='10' class='text-center'>No borrow records found.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // 🔍 Search functionality
  document.getElementById('searchInput').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#recordsTable tbody tr');
    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(filter) ? '' : 'none';
    });
  });

  // 🗑 SweetAlert Delete Confirmation
  document.querySelectorAll('.deleteBtn').forEach(button => {
    button.addEventListener('click', function() {
      const form = this.closest('form');
      Swal.fire({
        title: 'Are you sure?',
        text: "This record will be permanently deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
</script>

<!-- ✅ SweetAlert Popup After Action -->
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
