<?php
session_start();
include '../db/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $sql = "SELECT * FROM users WHERE email=? AND role=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            
            if ($role == 'student') {
                header("Location: ../student/student_dashboard.php");
            } elseif ($role == 'teacher') {
                header("Location: ../teacher/teacher_dashboard.php");
            } else {
                header("Location: ../admin/admin_dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Invalid email or role.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
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
    /* Centered login card with fade-in animation */
    .login-card {
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
    .login-card h2 {
      font-weight: 600;
    }
    .login-card .form-control {
      border-radius: 5px;
    }
    .login-card .btn-primary {
      border-radius: 5px;
      transition: transform 0.2s ease-in-out;
    }
    .login-card .btn-primary:hover {
      transform: scale(1.03);
    }
    .login-card a {
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="container d-flex justify-content-center align-items-center">
    <div class="card login-card p-4">
      <h2 class="text-center mb-4">Login</h2>
      <?php if(isset($error)) echo "<p class='text-danger text-center'>$error</p>"; ?>
      <form method="POST" action="">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="yourname@example.com" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Enter password" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Role</label>
          <select name="role" class="form-select" required>
            <option value="student">Student</option>
            <option value="teacher">Teacher</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
        <p class="text-center mt-3 mb-0">Don't have an account? <a href="register.php">Register</a></p>
      </form>
    </div>
  </div>
  <!-- Bootstrap Bundle JS (includes Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
