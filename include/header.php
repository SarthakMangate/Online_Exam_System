<?php
// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Online Exam Platform</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <!-- Navigation Bar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">Exam Secure System</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
              aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <?php if(isset($_SESSION['user_id'])): ?>
          <ul class="navbar-nav ms-auto">
            <?php if($_SESSION['role'] == 'student'): ?>
              <li class="nav-item">
                <a class="nav-link" href="../student/student_dashboard.php">Dashboard</a>
              </li>
            <?php elseif($_SESSION['role'] == 'teacher'): ?>
              <li class="nav-item">
                <a class="nav-link" href="../teacher/teacher_dashboard.php">Dashboard</a>
              </li>
            <?php elseif($_SESSION['role'] == 'admin'): ?>
              <li class="nav-item">
                <a class="nav-link" href="../admin/admin_dashboard.php">Dashboard</a>
              </li>
            <?php endif; ?>
            <li class="nav-item">
              <a class="nav-link" href="../auth/logout.php">Logout</a>
            </li>
          </ul>
        <?php else: ?>
          <ul class="navbar-nav ms-auto">
            <li class="nav-item">
              <a class="nav-link" href="../auth/login.php">Login</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../auth/register.php">Register</a>
            </li>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </nav>
