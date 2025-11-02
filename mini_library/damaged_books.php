<?php
session_start();
include "db.php";

// ✅ FETCH TABLE DATA (AJAX)
if (isset($_GET['action']) && $_GET['action'] == 'fetch') {
  $query = isset($_GET['query']) ? trim($_GET['query']) : '';
  $sql = "SELECT * FROM damage_books";

  if (!empty($query)) {
    $sql .= " WHERE book_title LIKE ? OR author LIKE ? OR isbn LIKE ? OR publisher LIKE ?";
    $stmt = $conn->prepare($sql);
    $like = "%$query%";
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
  } else {
    $result = $conn->query($sql);
  }

  echo '<table class="damaged-table">
          <thead>
            <tr>
              <th>Book Title</th>
              <th>Author</th>
              <th>ISBN</th>
              <th>Publisher</th>
              <th>Year Published</th>
              <th>Copies</th>
              <th>Issue</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>';
  if ($result && $result->num_rows > 0) {
    while ($book = $result->fetch_assoc()) {
      echo '<tr>
              <td>'.htmlspecialchars($book['book_title']).'</td>
              <td>'.htmlspecialchars($book['author']).'</td>
              <td>'.htmlspecialchars($book['isbn']).'</td>
              <td>'.htmlspecialchars($book['publisher']).'</td>
              <td>'.htmlspecialchars($book['year_published']).'</td>
              <td>'.htmlspecialchars($book['available_copies']).'</td>
              <td>'.htmlspecialchars($book['book_issue']).'</td>
              <td>
                <button class="btn-edit" data-id="'.$book['id'].'"><i class="bi bi-pencil-square"></i></button>
                <button class="btn-delete" data-id="'.$book['id'].'"><i class="bi bi-trash"></i></button>
              </td>
            </tr>';
    }
  } else {
    echo '<tr><td colspan="8" style="text-align:center;">No damaged books found</td></tr>';
  }
  echo '</tbody></table>';
  exit();
}

// ✅ DELETE
if (isset($_GET['delete'])) {
  $delete_id = intval($_GET['delete']);
  $stmt = $conn->prepare("DELETE FROM damage_books WHERE id = ?");
  $stmt->bind_param("i", $delete_id);
  $stmt->execute();
  echo "deleted";
  exit();
}

// ✅ EDIT FETCH
$id = $title = $author = $isbn = $publisher = $year = $copies = $issue = "";
if (isset($_GET['edit'])) {
  $edit_id = intval($_GET['edit']);
  $stmt = $conn->prepare("SELECT * FROM damage_books WHERE id = ?");
  $stmt->bind_param("i", $edit_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $book = $result->fetch_assoc();

  if ($book) {
    $id = $book['id'];
    $title = $book['book_title'];
    $author = $book['author'];
    $isbn = $book['isbn'];
    $publisher = $book['publisher'];
    $year = $book['year_published'];
    $copies = $book['available_copies'];
    $issue = $book['book_issue'];
  }
}

// ✅ ADD or UPDATE RECORD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $id = $_POST['id'];
  $title = trim($_POST['title']);
  $author = trim($_POST['author']);
  $isbn = trim($_POST['isbn']);
  $publisher = trim($_POST['publisher']);
  $copies = intval($_POST['copies']);
  $issue = trim($_POST['issue']);

  // Properly handle date
  $year = !empty($_POST['year']) ? $_POST['year'] : NULL;

  if (!empty($id)) {
    $stmt = $conn->prepare("UPDATE damage_books 
                            SET book_title=?, author=?, isbn=?, publisher=?, year_published=?, available_copies=?, book_issue=?
                            WHERE id=?");
    $stmt->bind_param("sssssssi", $title, $author, $isbn, $publisher, $year, $copies, $issue, $id);
    $stmt->execute();
    echo "updated";
  } else {
    $stmt = $conn->prepare("INSERT INTO damage_books (book_title, author, isbn, publisher, year_published, available_copies, book_issue)
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssis", $title, $author, $isbn, $publisher, $year, $copies, $issue);
    $stmt->execute();
    echo "saved";
  }
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Damaged Books</title>
  <link rel="stylesheet" href="css/damaged_books.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <style>
    body {
      font-family: "Poppins", sans-serif;
      margin: 0;
      background: #fae1e1ff;
      display: flex;
    }
  </style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
  <h2><i class="bi bi-exclamation-triangle-fill"></i> Damaged Books</h2>

  <form id="bookForm" method="POST" class="damaged-form">
    <input type="hidden" name="id" value="<?= htmlspecialchars($id); ?>">

    <div class="form-group">
      <label>Book Title</label>
      <input type="text" name="title" value="<?= htmlspecialchars($title); ?>" required>
    </div>

    <div class="form-group">
      <label>Author</label>
      <input type="text" name="author" value="<?= htmlspecialchars($author); ?>" required>
    </div>

    <div class="form-group">
      <label>ISBN</label>
      <input type="number" id="isbn" name="isbn" value="<?= htmlspecialchars($isbn); ?>" placeholder="Enter 5-digit ISBN" required>
    </div>

    <div class="form-group">
      <label>Publisher</label>
      <input type="text" name="publisher" value="<?= htmlspecialchars($publisher); ?>" required>
    </div>

    <div class="form-group">
      <label>Year Published</label>
      <input type="date" id="year" name="year"
             value="<?= !empty($year) ? htmlspecialchars($year) : date('Y-m-d'); ?>"
             required>
    </div>

    <div class="form-group">
      <label>Available Copies</label>
      <input type="number" name="copies" value="<?= htmlspecialchars($copies); ?>" required>
    </div>

    <div class="form-group">
      <label>Book Issue</label>
      <textarea name="issue" rows="4" required><?= htmlspecialchars($issue); ?></textarea>
    </div>

    <button type="submit" class="btn-submit">
      <i class="bi bi-save"></i> <?= !empty($id) ? "Update Record" : "Save Record" ?>
    </button>
    <?php if (!empty($id)): ?>
      <a href="damaged_books.php" class="btn-cancel"><i class="bi bi-x-circle"></i> Cancel</a>
    <?php endif; ?>
  </form>

  <hr>

  <div class="search-bar">
    <input type="text" id="search" placeholder="Search by title, author, ISBN, or publisher...">
    <button type="button"><i class="bi bi-search"></i></button>
  </div>

  <h3><i class="bi bi-book-half"></i> List of Damaged Books</h3>
  <div id="bookTable"></div>
</div>

<script>
  // ✅ Limit ISBN to 5 digits
  const isbnInput = document.getElementById("isbn");
  isbnInput.addEventListener("input", function() {
    this.value = this.value.replace(/\D/g, "");
    if (this.value.length > 5) this.value = this.value.slice(0, 5);
  });

  // ✅ Load all books
  function loadBooks(query = "") {
    $.get("damaged_books.php", { action: "fetch", query: query }, function(data) {
      $("#bookTable").html(data);
    });
  }

  $("#search").on("keyup", function() {
    loadBooks($(this).val());
  });

  $(document).ready(function() {
    loadBooks();

    // ✅ Add/Edit record with SweetAlert
    $("#bookForm").on("submit", function(e) {
      e.preventDefault();
      $.ajax({
        url: "damaged_books.php",
        type: "POST",
        data: new FormData(this),
        contentType: false,
        processData: false,
        success: function(response) {
          loadBooks();
          $("#bookForm")[0].reset();
          let msg = "Record saved successfully!";
          if (response.trim() === "updated") msg = "Record updated successfully!";
          Swal.fire({
            icon: "success",
            title: "Success!",
            text: msg,
            timer: 1500,
            showConfirmButton: false
          });
        }
      });
    });

    // ✅ Delete confirmation
    $(document).on("click", ".btn-delete", function() {
      const id = $(this).data("id");
      Swal.fire({
        title: "Are you sure?",
        text: "This record will be deleted!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete it!"
      }).then((result) => {
        if (result.isConfirmed) {
          $.get("damaged_books.php", { delete: id }, function(response) {
            if (response.trim() === "deleted") {
              loadBooks();
              Swal.fire({
                icon: "success",
                title: "Deleted!",
                text: "The record has been deleted.",
                timer: 1500,
                showConfirmButton: false
              });
            }
          });
        }
      });
    });

    // ✅ Edit redirect
    $(document).on("click", ".btn-edit", function() {
      const id = $(this).data("id");
      window.location.href = "damaged_books.php?edit=" + id;
    });
  });
</script>

</body>
</html>
