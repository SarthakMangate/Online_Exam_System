<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../auth/login.php");
    exit();
}
include '../db/db.php';

$teacher_id = $_SESSION['user_id'];

$error = "";
$success = "";

// Handle deletion if a delete request is sent via GET
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // Verify the exam belongs to the teacher
    $checkQuery = "SELECT * FROM exams WHERE id = $delete_id AND teacher_id = $teacher_id";
    $checkResult = $conn->query($checkQuery);
    if ($checkResult->num_rows > 0) {
        $deleteQuery = "DELETE FROM exams WHERE id = $delete_id";
        if ($conn->query($deleteQuery)) {
            $success = "Exam deleted successfully.";
        } else {
            $error = "Error deleting exam: " . $conn->error;
        }
    } else {
        $error = "Exam not found or you do not have permission to delete it.";
    }
}

// Retrieve all exams created by this teacher
$examsQuery = "SELECT * FROM exams WHERE teacher_id = $teacher_id ORDER BY created_at DESC";
$examsResult = $conn->query($examsQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Exams</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include '../include/header.php'; ?>
  <div class="container mt-4">
    <h2 class="mb-4">Manage Exams</h2>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($examsResult && $examsResult->num_rows > 0): ?>
      <table class="table table-bordered table-hover">
        <thead class="table-primary">
          <tr>
            <th>Exam Title</th>
            <th>Created On</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($exam = $examsResult->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($exam['title']); ?></td>
              <td><?php echo htmlspecialchars(date("Y-m-d", strtotime($exam['created_at']))); ?></td>
              <td>
                <a href="edit_exam.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                <a href="manage_exams.php?delete_id=<?php echo $exam['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this exam?');">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="alert alert-warning">No exams found. Please create an exam first.</div>
    <?php endif; ?>
  </div>
  <?php include '../include/footer.php'; ?>
  <!-- Bootstrap Bundle JS (includes Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
