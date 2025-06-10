<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../auth/login.php");
    exit();
}
include '../db/db.php';

$teacher_id = $_SESSION['user_id'];

// Fetch exams created by this teacher
$examsQuery = "SELECT * FROM exams WHERE teacher_id = $teacher_id ORDER BY created_at DESC";
$examsResult = $conn->query($examsQuery);

// Fetch student results for exams created by this teacher
// Added a subquery to fetch the latest admin action for each exam attempt (test_status)
$resultsQuery = "SELECT se.score, se.completed_at, e.title AS exam_title, u.name AS student_name,
                 (SELECT ama.action 
                    FROM admin_malpractice_actions ama 
                    WHERE ama.exam_id = e.id AND ama.student_id = u.id 
                    ORDER BY ama.action_date DESC LIMIT 1) AS test_status
                 FROM student_exams se 
                 JOIN exams e ON se.exam_id = e.id 
                 JOIN users u ON se.student_id = u.id 
                 WHERE e.teacher_id = $teacher_id 
                 ORDER BY se.completed_at DESC";
$resultsResult = $conn->query($resultsQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Teacher Dashboard</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include '../include/header.php'; ?>

  <div class="container mt-4">
    <h2 class="mb-4">Teacher Dashboard</h2>
    <!-- Navigation Tabs for Dashboard Sections -->
    <ul class="nav nav-tabs" id="teacherDashboardTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="create-test-tab" data-bs-toggle="tab" data-bs-target="#create-test" type="button" role="tab" aria-controls="create-test" aria-selected="true">
          Create Test
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="manage-exams-tab" data-bs-toggle="tab" data-bs-target="#manage-exams" type="button" role="tab" aria-controls="manage-exams" aria-selected="false">
          Manage Exams
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="student-results-tab" data-bs-toggle="tab" data-bs-target="#student-results" type="button" role="tab" aria-controls="student-results" aria-selected="false">
          Student Results
        </button>
      </li>
    </ul>
    <div class="tab-content" id="teacherDashboardTabsContent">
      <!-- Create Test Section -->
      <div class="tab-pane fade show active" id="create-test" role="tabpanel" aria-labelledby="create-test-tab">
        <div class="card mt-3 p-3 shadow-sm">
          <h4>Create Test</h4>
          <p>Click the button below to create a new test.</p>
          <a href="create_exam.php" class="btn btn-success">Create New Test</a>
        </div>
      </div>
      <!-- Manage Exams Section -->
      <div class="tab-pane fade" id="manage-exams" role="tabpanel" aria-labelledby="manage-exams-tab">
        <div class="card mt-3 p-3 shadow-sm">
          <h4>Manage Exams</h4>
          <?php if ($examsResult && $examsResult->num_rows > 0): ?>
          <table class="table table-bordered table-hover mt-3">
            <thead>
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
                  <!-- Update these links to point to your edit and delete functionality -->
                  <a href="edit_exam.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                  <a href="delete_exam.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this exam?')">Delete</a>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
          <?php else: ?>
            <div class="alert alert-warning">No exams found. Please create a test first.</div>
          <?php endif; ?>
        </div>
      </div>
      <!-- Student Results Section -->
      <div class="tab-pane fade" id="student-results" role="tabpanel" aria-labelledby="student-results-tab">
        <div class="card mt-3 p-3 shadow-sm">
          <h4>Student Results</h4>
          <?php if ($resultsResult && $resultsResult->num_rows > 0): ?>
          <table class="table table-striped table-bordered mt-3">
            <thead>
              <tr>
                <th>Student Name</th>
                <th>Exam Title</th>
                <th>Score</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $resultsResult->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                <td><?php echo htmlspecialchars($row['exam_title']); ?></td>
                <td>
                  <?php
                    // If the latest admin action is 'removed', display "Cheated"
                    echo (isset($row['test_status']) && $row['test_status'] === 'removed') 
                         ? "Cheated" 
                         : htmlspecialchars($row['score']);
                  ?>
                </td>
                <td><?php echo htmlspecialchars(date("Y-m-d", strtotime($row['completed_at']))); ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
          <?php else: ?>
            <div class="alert alert-info">No student results found.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <?php include '../include/footer.php'; ?>
  <!-- Bootstrap Bundle JS (includes Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>