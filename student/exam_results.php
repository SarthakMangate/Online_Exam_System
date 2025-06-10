<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}
include '../db/db.php';
$user_id = $_SESSION['user_id'];

if (!isset($_GET['exam_id'])) {
    echo "Exam ID not provided.";
    exit();
}
$exam_id = intval($_GET['exam_id']);

// Fetch the most recent result for this exam and student along with the latest admin action.
$query = "SELECT se.score, se.completed_at, e.title,
           (SELECT ama.action 
            FROM admin_malpractice_actions ama 
            WHERE ama.exam_id = e.id AND ama.student_id = $user_id 
            ORDER BY ama.action_date DESC LIMIT 1) AS test_status
          FROM student_exams se 
          JOIN exams e ON se.exam_id = e.id 
          WHERE se.student_id = $user_id AND e.id = $exam_id 
          ORDER BY se.completed_at DESC LIMIT 1";
$result = $conn->query($query);
if ($result->num_rows == 0) {
    echo "Result not found.";
    exit();
}
$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Exam Result - <?php echo htmlspecialchars($row['title']); ?></title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include '../include/header.php'; ?>
  <div class="container mt-4">
    <h2 class="mb-4">Exam Result</h2>
    <div class="card p-4 shadow-sm">
      <h4><?php echo htmlspecialchars($row['title']); ?></h4>
      <ul class="list-group mt-3">
        <li class="list-group-item"><strong>Score:</strong> <?php echo (isset($row['test_status']) && $row['test_status'] === 'removed') ? "Cheated" : htmlspecialchars($row['score']); ?></li>
        <li class="list-group-item"><strong>Date:</strong> <?php echo htmlspecialchars(date("Y-m-d", strtotime($row['completed_at']))); ?></li>
      </ul>
      <a href="student_dashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
    </div>
  </div>
  <?php include '../include/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>