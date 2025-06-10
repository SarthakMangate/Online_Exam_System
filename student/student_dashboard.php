<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

include '../db/db.php';

$user_id = $_SESSION['user_id'];

// Updated query: fetch exams along with the most recent admin action for this student and attempted count.
$examsQuery = "
    SELECT e.id, e.title, u.name AS teacher_name,
           (
              SELECT ama.action 
              FROM admin_malpractice_actions ama 
              WHERE ama.exam_id = e.id AND ama.student_id = $user_id 
              ORDER BY ama.action_date DESC 
              LIMIT 1
           ) AS test_status,
           (
              SELECT COUNT(*) 
              FROM student_exams se 
              WHERE se.exam_id = e.id AND se.student_id = $user_id
           ) AS attempted_count
    FROM exams e 
    JOIN users u ON e.teacher_id = u.id 
    ORDER BY e.created_at DESC";
$examsResult = $conn->query($examsQuery);

// Fetch past exam results for the student
$resultsQuery = "SELECT se.score, se.completed_at, e.title 
                 FROM student_exams se 
                 JOIN exams e ON se.exam_id = e.id 
                 WHERE se.student_id = $user_id 
                 ORDER BY se.completed_at DESC";
$resultsResult = $conn->query($resultsQuery);

// Fetch the most recent exam result for detailed view (if exists)
$latestResultQuery = "SELECT se.score, se.completed_at, e.title 
                      FROM student_exams se 
                      JOIN exams e ON se.exam_id = e.id 
                      WHERE se.student_id = $user_id 
                      ORDER BY se.completed_at DESC LIMIT 1";
$latestResult = $conn->query($latestResultQuery)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Dashboard</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include '../include/header.php'; ?>

  <div class="container mt-4">
    <h2 class="mb-4">Student Dashboard</h2>
    <!-- Navigation Tabs for Dashboard Sections -->
    <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="take-exam-tab" data-bs-toggle="tab" data-bs-target="#take-exam" type="button" role="tab" aria-controls="take-exam" aria-selected="true">
          Appear for Exam
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="past-results-tab" data-bs-toggle="tab" data-bs-target="#past-results" type="button" role="tab" aria-controls="past-results" aria-selected="false">
          Past Results
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="current-results-tab" data-bs-toggle="tab" data-bs-target="#current-results" type="button" role="tab" aria-controls="current-results" aria-selected="false">
          Exam Results
        </button>
      </li>
    </ul>
    <div class="tab-content" id="dashboardTabsContent">
<!-- Appear for Exam Section -->
<div class="tab-pane fade show active" id="take-exam" role="tabpanel" aria-labelledby="take-exam-tab">
    <div class="card mt-3 p-3 shadow-sm">
        <h4>Appear for Exam</h4>
        <p>Select an exam from the list below to begin your exam.</p>
        <?php if ($examsResult && $examsResult->num_rows > 0): ?>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Exam Title</th>
                    <th>Teacher</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $examsResult->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                    <td>
                        <?php
                        // If caught in malpractice (action = 'removed'), block access
                        if (isset($row['test_status']) && $row['test_status'] === 'removed') {
                            echo '<button class="btn btn-danger btn-sm" disabled>Removed from Test</button>';
                        }
                        // If attempted & NOT caught in malpractice, show "Already Attempted"
                        elseif ($row['attempted_count'] > 0) {
                            if (isset($row['test_status']) && $row['test_status'] === 'kept') {
                                echo '<a href="take_exam.php?exam_id=' . $row['id'] . '" class="btn btn-success btn-sm">Start Exam</a>';
                            } else {
                                echo '<button class="btn btn-secondary btn-sm" disabled>Already Attempted</button>';
                            }
                        } 
                        // If never attempted and not blocked, allow exam start
                        else {
                            echo '<a href="take_exam.php?exam_id=' . $row['id'] . '" class="btn btn-success btn-sm">Start Exam</a>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="alert alert-warning">No exams available at the moment. Please check back later.</div>
        <?php endif; ?>
    </div>
</div>

      <!-- Past Results Section -->
      <div class="tab-pane fade" id="past-results" role="tabpanel" aria-labelledby="past-results-tab">
        <div class="card mt-3 p-3 shadow-sm">
          <h4>Past Results</h4>
          <p>Review your previous exam performances below.</p>
          <?php if ($resultsResult && $resultsResult->num_rows > 0): ?>
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Exam Title</th>
                <th>Score</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($result = $resultsResult->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($result['title']); ?></td>
                <td><?php echo htmlspecialchars($result['score']); ?></td>
                <td><?php echo htmlspecialchars(date("Y-m-d", strtotime($result['completed_at']))); ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
          <?php else: ?>
            <div class="alert alert-info">You haven't attempted any exams yet.</div>
          <?php endif; ?>
        </div>
      </div>
      <!-- Exam Results Section -->
      <div class="tab-pane fade" id="current-results" role="tabpanel" aria-labelledby="current-results-tab">
        <div class="card mt-3 p-3 shadow-sm">
          <h4>Exam Results</h4>
          <?php if ($latestResult): ?>
            <p>Details of your most recent exam attempt:</p>
            <ul class="list-group">
              <li class="list-group-item"><strong>Exam Title:</strong> <?php echo htmlspecialchars($latestResult['title']); ?></li>
              <li class="list-group-item"><strong>Score:</strong> <?php echo htmlspecialchars($latestResult['score']); ?></li>
              <li class="list-group-item"><strong>Date:</strong> <?php echo htmlspecialchars(date("Y-m-d", strtotime($latestResult['completed_at']))); ?></li>
            </ul>
          <?php else: ?>
            <div class="alert alert-info">Your exam results will appear here once you have attempted an exam.</div>
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
