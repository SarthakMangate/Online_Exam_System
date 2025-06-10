<?php
session_start();
include '../db/db.php'; // Adjust the path as needed

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Escape user input for security
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // Check if a user with this email already exists
    $check_query = "SELECT * FROM users WHERE email='$email'";
    $check_result = mysqli_query($conn, $check_query);
    if (mysqli_num_rows($check_result) > 0) {
        $error = "User already exists with this email.";
    } else {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user record into the database
        $insert_query = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$hashed_password', '$role')";
        if (mysqli_query($conn, $insert_query)) {
            header("Location: login.php");
            exit();
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register</title>
  <!-- Bootstrap CSS (v5.3) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    /* Plain background */
    body {
      background-color: #f8f9fa;
      min-height: 100vh;
      display: flex;
      align-items: center;
    }
    /* Centered registration card with fade-in animation */
    .register-card {
      max-width: 400px;
      width: 100%;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
      opacity: 0;
      transform: translateY(-20px);
      animation: fadeInSlide 0.8s forwards;
    }
    @keyframes fadeInSlide {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .register-card h2 {
      font-weight: 600;
    }
    .register-card .form-control {
      border-radius: 5px;
    }
    .register-card .btn-primary {
      border-radius: 5px;
      transition: transform 0.2s ease-in-out;
    }
    .register-card .btn-primary:hover {
      transform: scale(1.03);
    }
    .register-card a {
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="container d-flex justify-content-center align-items-center">
    <div class="card register-card p-4">
      <h2 class="text-center mb-4">Register</h2>
      <?php if(isset($error)) echo "<p class='text-danger text-center'>$error</p>"; ?>
      <form method="POST" action="">
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Role</label>
          <select name="role" class="form-select" required>
            <option value="student">Student</option>
            <option value="teacher">Teacher</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary w-100">Register</button>
        <p class="text-center mt-3">Already have an account? <a href="login.php">Login</a></p>
      </form>
    </div>
  </div>
  <!-- Bootstrap Bundle JS (includes Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>