<?php
include "db.php";
session_start();

// ADD USER (no password hashing)
if (isset($_POST['add'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $password = $_POST['password']; // store as plain text ❗

    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, role, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $first_name, $last_name, $username, $role, $password);
    $stmt->execute();

    $_SESSION['message'] = "User successfully added!";
    $_SESSION['message_type'] = "success";
    header("Location: manage_users.php");
    exit();
}


// UPDATE USER
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, username=?, role=? WHERE id=?");
    $stmt->bind_param("ssssi", $first_name, $last_name, $username, $role, $id);
    $stmt->execute();

    $_SESSION['message'] = "User details updated successfully!";
    $_SESSION['message_type'] = "info";
    header("Location: manage_users.php");
    exit();
}

// DELETE USER
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM users WHERE id = $id");

    $_SESSION['message'] = "User deleted successfully!";
    $_SESSION['message_type'] = "error";
    header("Location: manage_users.php");
    exit();
}

// FETCH USERS
$result = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    body {
      background: #fae1e1ff;
      font-family: 'Poppins', sans-serif;
      color: #333;
    }
    .content {
      margin-left: 250px;
      margin-top: 40px;
      max-width: 1150px;
      padding: 20px;
    }
    .card {
      background: #fff;
      border: none;
      border-radius: 15px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      padding: 25px;
    }
    h2 {
      font-weight: 700;
      color: #000;
    }
    .btn-primary {
      background-color: #007bff;
      border: none;
    }
    .btn-danger {
      background-color: #dc3545;
      border: none;
    }
    .badge.admin {
      background-color: #17a2b8;
    }
    .badge.librarian {
      background-color: #28a745;
    }
    .search-box {
      width: 250px;
      margin-left: auto;
    }
    .table thead th {
      background-color: #212529;
      color: #fff;
    }
    .table-container {
      background: #fff;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="content">
  <h2 class="text-center mb-4">Manage Users</h2>

  <div class="row g-4">
    <!-- CREATE ACCOUNT -->
    <div class="col-md-4">
      <div class="card">
        <h5 class="text-center mb-3 fw-semibold">Create Account</h5>
        <form method="POST">
          <input type="text" name="first_name" class="form-control mb-2" placeholder="First Name" required>
          <input type="text" name="last_name" class="form-control mb-2" placeholder="Last Name" required>
          <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
          <select name="role" class="form-select mb-2" required>
            <option value="">Select Role</option>
            <option value="admin">Admin</option>
            <option value="librarian">Librarian</option>
          </select>
          <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
          <button type="submit" name="add" class="btn btn-primary w-100">Add User</button>
        </form>
      </div>
    </div>

    <!-- USER LIST -->
    <div class="col-md-8">
      <div class="card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-semibold mb-0">Existing Accounts</h5>
          <input type="text" id="searchInput" class="form-control search-box" placeholder="Search...">
        </div>

        <div class="table-responsive table-container">
          <table class="table table-striped align-middle" id="userTable">
            <thead class="table-dark text-center">
              <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td class="text-center"><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['first_name'] . " " . $row['last_name']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td class="text-center">
                      <span class="badge <?= $row['role'] == 'admin' ? 'admin' : 'librarian' ?>">
                        <?= ucfirst($row['role']) ?>
                      </span>
                    </td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-warning me-2 editBtn"
                              data-id="<?= $row['id'] ?>"
                              data-fname="<?= htmlspecialchars($row['first_name']) ?>"
                              data-lname="<?= htmlspecialchars($row['last_name']) ?>"
                              data-username="<?= htmlspecialchars($row['username']) ?>"
                              data-role="<?= $row['role'] ?>">
                        <i class="bi bi-pencil-square"></i> Edit
                      </button>
                      <a href="manage_users.php?delete=<?= $row['id'] ?>" 
                         class="btn btn-sm btn-danger"
                         onclick="return confirm('Are you sure you want to delete this user?');">
                         <i class="bi bi-trash3"></i> Delete
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="5" class="text-center text-muted">No users found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 🧩 EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="editId">
          <input type="text" name="first_name" id="editFname" class="form-control mb-2" placeholder="First Name" required>
          <input type="text" name="last_name" id="editLname" class="form-control mb-2" placeholder="Last Name" required>
          <input type="text" name="username" id="editUsername" class="form-control mb-2" placeholder="Username" required>
          <select name="role" id="editRole" class="form-select mb-2" required>
            <option value="admin">Admin</option>
            <option value="librarian">Librarian</option>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="update" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // 🔍 LIVE SEARCH
  document.getElementById("searchInput").addEventListener("keyup", function() {
    let filter = this.value.toLowerCase();
    document.querySelectorAll("#userTable tbody tr").forEach(row => {
      row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
    });
  });

  // 📝 EDIT MODAL
  const editModal = new bootstrap.Modal(document.getElementById('editModal'));
  document.querySelectorAll(".editBtn").forEach(btn => {
    btn.addEventListener("click", () => {
      document.getElementById("editId").value = btn.dataset.id;
      document.getElementById("editFname").value = btn.dataset.fname;
      document.getElementById("editLname").value = btn.dataset.lname;
      document.getElementById("editUsername").value = btn.dataset.username;
      document.getElementById("editRole").value = btn.dataset.role;
      editModal.show();
    });
  });
</script>

<!-- ✅ SweetAlert Notification -->
<?php if (isset($_SESSION['message'])): ?>
<script>
Swal.fire({
  icon: '<?= $_SESSION['message_type']; ?>',
  title: '<?= ($_SESSION['message_type'] == "error") ? "Deleted!" : "Success!"; ?>',
  text: '<?= $_SESSION['message']; ?>',
  confirmButtonColor: '#3085d6',
  confirmButtonText: 'OK'
});
</script>
<?php unset($_SESSION['message']); unset($_SESSION['message_type']); endif; ?>

</body>
</html>
