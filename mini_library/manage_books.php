<?php
session_start();
include "db.php";

// ADD BOOK
if (isset($_POST['add'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $isbn = $_POST['isbn'];
    $publisher = $_POST['publisher'];
    $year_published = $_POST['year_published'];
    $available_copies = $_POST['available_copies'];

    // Check duplicate ISBN
    $check_isbn = $conn->prepare("SELECT * FROM books WHERE isbn = ?");
    $check_isbn->bind_param("s", $isbn);
    $check_isbn->execute();
    $result = $check_isbn->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['message'] = "❌ ISBN already exists! Please use a unique number.";
        $_SESSION['message_type'] = "error";
        header("Location: manage_books.php");  
        exit();
    }

    // Insert
    $sql = "INSERT INTO books (title, author, isbn, publisher, year_published, available_copies, status) 
            VALUES ('$title', '$author', '$isbn', '$publisher', '$year_published', '$available_copies', 'Available')";
    $conn->query($sql);

    $_SESSION['message'] = "Book added successfully!";
    $_SESSION['message_type'] = "success";
    header("Location: manage_books.php");
    exit();
}

// UPDATE BOOK
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $isbn = $_POST['isbn'];
    $publisher = $_POST['publisher'];
    $year_published = $_POST['year_published'];
    $available_copies = $_POST['available_copies'];

    $conn->query("UPDATE books 
                  SET title='$title', author='$author', isbn='$isbn', publisher='$publisher', 
                      year_published='$year_published', available_copies='$available_copies' 
                  WHERE id=$id");

    $_SESSION['message'] = "Book updated successfully!";
    $_SESSION['message_type'] = "info";
    header("Location: manage_books.php");
    exit();
}

// DELETE BOOK
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM borrow_records WHERE book_id=$id");
    $conn->query("DELETE FROM books WHERE id=$id");

    $_SESSION['message'] = "Book deleted successfully!";
    $_SESSION['message_type'] = "success";
    header("Location: manage_books.php");
    exit();
}

// GET BOOK DATA FOR EDIT
$editData = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $editData = $conn->query("SELECT * FROM books WHERE id=$id")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Manage Books</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="manage_books.css">
  <style>
    body {
      background: #fae1e1ff; 
      font-family: 'Poppins', sans-serif;
      color: #333;
    }
    .content {  
      margin-left: 250px;
      padding: 30px;
    }
    .card {
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .table thead {
      background: #343a40;
      color: #fff;
    }
    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
    }
    .page-header h2 {
      margin: 0;
      font-weight: bold;
      color: #333;
    }
    .table {
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    }
    .table thead {
      background-color: #fce4ec;
      color: #333;
    }
  </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="content">
  <h2 class="mb-5 text-center"> Manage Books</h2>

  <!-- ADD / EDIT FORM -->
  <div class="card p-4 mb-4">
    <form method="POST">
      <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Book Title</label>
          <input type="text" name="title" value="<?= $editData['title'] ?? '' ?>" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Author</label>
          <input type="text" name="author" value="<?= $editData['author'] ?? '' ?>" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">ISBN</label>
          <input type="number" name="isbn" id="isbn" value="<?= $editData['isbn'] ?? '' ?>" class="form-control" oninput="if(this.value.length > 5) this.value = this.value.slice(0, 5);" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Publisher</label>
          <input type="text" name="publisher" value="<?= $editData['publisher'] ?? '' ?>" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Year Published</label>
          <input type="date" name="year_published" value="<?= $editData['year_published'] ?? '' ?>" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Available Copies</label>
          <input type="number" name="available_copies" value="<?= $editData['available_copies'] ?? '' ?>" class="form-control" min="0" max="200" required>
        </div>

        <div class="col-md-12 d-flex gap-2 mt-3">
          <?php if ($editData): ?>
            <button type="submit" name="update" class="btn btn-success">
              <i class="bi bi-check2-circle"></i> Update
            </button>
            <a href="manage_books.php" class="btn btn-outline-secondary">
              <i class="bi bi-x-circle"></i> Cancel
            </a>
          <?php else: ?>
            <button type="submit" name="add" class="btn btn-primary">
              <i class="bi bi-plus-circle"></i> Add Book
            </button>
          <?php endif; ?>
        </div>
      </div>
    </form>
  </div>

  <!-- SEARCH -->
  <div class="mb-3 d-flex justify-content-end">
    <input type="text" id="searchInput" class="form-control form-control-sm" style="max-width: 330px;" placeholder="Search books...">
  </div>

  <!-- BOOKS TABLE -->
  <div class="table-responsive rounded shadow-sm">
    <table class="table table-bordered table-striped" id="booksTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Author</th>
          <th>Available Copies</th> 
          <th>Status</th>
          <th>ISBN</th>
          <th>Publisher</th>
          <th>Year Published</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $result = $conn->query("SELECT * FROM books");
        if ($result->num_rows > 0) {
          $counter = 1;
          while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$counter}</td>
                    <td>{$row['title']}</td>
                    <td>{$row['author']}</td>
                    <td>{$row['available_copies']}</td>
                    <td><span class='badge bg-" . ($row['status'] == 'Available' ? "success" : "danger") . "'>{$row['status']}</span></td>
                    <td>{$row['isbn']}</td>
                    <td>{$row['publisher']}</td>
                    <td>{$row['year_published']}</td>
                    <td class='text-center'>
                      <a href='manage_books.php?edit={$row['id']}' class='btn btn-warning btn-sm'><i class='bi bi-pencil-square'></i></a>
                      <a href='manage_books.php?delete={$row['id']}' class='btn btn-danger btn-sm btn-delete'><i class='bi bi-trash'></i></a>
                    </td>
                  </tr>";
            $counter++;
          }
        } else {
          echo "<tr><td colspan='9' class='text-center text-muted'>No books found</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- SEARCH FUNCTION -->
<script>
document.getElementById("searchInput").addEventListener("keyup", function() {
  let filter = this.value.toLowerCase();
  let rows = document.querySelectorAll("#booksTable tbody tr");
  rows.forEach(row => {
    let text = row.textContent.toLowerCase();
    row.style.display = text.includes(filter) ? "" : "none";
  });
});
</script>

<!-- SWEETALERT DELETE CONFIRMATION -->
<script>
document.querySelectorAll('.btn-delete').forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    const url = this.getAttribute('href');

    Swal.fire({
      title: 'Are you sure?',
      text: "This book will be permanently deleted!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = url;
      }
    });
  });
});
</script>

<!-- SWEETALERT POPUP MESSAGE -->
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
