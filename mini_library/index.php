<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check if user exists
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // ✅ Check if password is correct (using password_verify for hashed passwords)
        if ($password === $user['password']) {

            // Login success
            session_regenerate_id(true);
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Store notification data
            $_SESSION['login_status'] = 'success';
            $_SESSION['login_message'] = "Welcome back, {$user['username']}!";

            // Redirect based on role (IMMEDIATELY)
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
            exit;
        } else {
            // ❌ Wrong password
            $_SESSION['login_status'] = 'error';
            $_SESSION['login_message'] = 'Incorrect username or password.';
        }
    } else {
        // ❌ No such user
        $_SESSION['login_status'] = 'error';
        $_SESSION['login_message'] = 'Incorrect username or password.';
    }

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini Library Login</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <!-- Logo at the very top -->
    <div class="top-logo">
        <img src="img/logo.png" alt="Library Logo">      
    </div>

    <!-- Main container -->
    <div class="container">
        <!-- Left Side -->
        <div class="left-side">
            <h1>Welcome to Mini Library</h1>
            <p>
                Explore endless books, borrow with ease, and enjoy reading anytime.
                Discover new stories, gain knowledge, and make learning fun.
                Your next adventure starts here.
                <br><br>
                Log in now and begin!
            </p>
        </div>

        <!-- Right Side -->
        <div class="right-side">
            <div class="login-card">
                <h2>Login to Continue</h2>

                <form action="index.php" method="POST">
                    <div class="input-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required placeholder="Enter username">
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required placeholder="Enter password">
                    </div>             
                    <button type="submit" class="btn-login">Login</button>
                </form>
            </div>
        </div>
    </div>

<!-- ✅ SweetAlert Notification Script -->
<?php if (isset($_SESSION['login_status'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($_SESSION['login_status'] == 'success'): ?>
        Swal.fire({
            title: '✅ Login Successful!',
            text: '<?= $_SESSION['login_message']; ?>',
            icon: 'success',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            background: '#e9fff1',
            color: '#14532d'
        });
    <?php else: ?>
        Swal.fire({
            title: '❌ Login Failed!',
            text: '<?= $_SESSION['login_message']; ?>',
            icon: 'error',
            confirmButtonColor: '#d33',
            background: '#fff0f0',
            color: '#7f1d1d',
            showClass: {
                popup: 'animate__animated animate__shakeX'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOut'
            }
        });
    <?php endif; ?>
});
</script>
<?php
unset($_SESSION['login_status']);
unset($_SESSION['login_message']);
endif;
?>

<!-- Animate.css for shake animation -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

</body>
</html>
