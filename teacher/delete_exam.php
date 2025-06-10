<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../auth/login.php");
    exit();
}
include '../db/db.php';

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exam_id'])) {
    $exam_id = intval($_POST['exam_id']);

    // Verify the exam belongs to this teacher
    $checkQuery = "SELECT * FROM exams WHERE id = ? AND teacher_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $exam_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        // Proceed to delete the exam
        $deleteQuery = "DELETE FROM exams WHERE id = ? AND teacher_id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("ii", $exam_id, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $message = "Exam deleted successfully.";
        } else {
            $message = "Failed to delete exam. Please try again.";
        }
    } else {
        $message = "Exam not found or you do not have permission to delete it.";
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Delete Exam</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include '../include/header.php'; ?>
  <div class="container mt-4">
    <h2 class="mb-4">Delete Exam</h2>
    <?php if (!empty($message)): ?>
      <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form action="delete_exam.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this exam?');">
      <div class="mb-3">
        <label for="exam_id" class="form-label">Exam ID</label>
        <input type="number" name="exam_id" id="exam_id" class="form-control" placeholder="Enter Exam ID" required>
      </div>
      <button type="submit" class="btn btn-danger">Delete Exam</button>
    </form>
  </div>
  <?php include '../include/footer.php'; ?>
  <!-- Bootstrap Bundle JS (includes Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
