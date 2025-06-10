<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}
include '../db/db.php';
$user_id = $_SESSION['user_id'];

// Retrieve past exam results along with the latest admin action per exam.
$query = "SELECT se.score, se.completed_at, e.title,
           (SELECT ama.action 
            FROM admin_malpractice_actions ama 
            WHERE ama.exam_id = e.id AND ama.student_id = $user_id 
            ORDER BY ama.action_date DESC LIMIT 1) AS test_status
          FROM student_exams se 
          JOIN exams e ON se.exam_id = e.id 
          WHERE se.student_id = $user_id 
          ORDER BY se.completed_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Past Results</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include '../include/header.php'; ?>
  <div class="container mt-4">
    <h2 class="mb-4">Past Results</h2>
    <?php if ($result->num_rows > 0): ?>
      <table class="table table-bordered table-hover">
        <thead>
          <tr>
            <th>Exam Title</th>
            <th>Score</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['title']); ?></td>
              <td><?php echo (isset($row['test_status']) && $row['test_status'] === 'removed') ? "Cheated" : htmlspecialchars($row['score']); ?></td>
              <td><?php echo htmlspecialchars(date("Y-m-d", strtotime($row['completed_at']))); ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="alert alert-info">You haven't taken any exams yet.</div>
    <?php endif; ?>
  </div>
  <?php include '../include/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>