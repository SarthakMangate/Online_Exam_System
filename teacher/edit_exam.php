<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../auth/login.php");
    exit();
}
include '../db/db.php';

$teacher_id = $_SESSION['user_id'];

if (!isset($_GET['exam_id'])) {
    echo "Exam ID not provided.";
    exit();
}

$exam_id = intval($_GET['exam_id']);

// Verify exam belongs to this teacher
$examQuery = "SELECT * FROM exams WHERE id = $exam_id AND teacher_id = $teacher_id";
$examResult = $conn->query($examQuery);
if ($examResult->num_rows == 0) {
    echo "Exam not found or you do not have permission to edit it.";
    exit();
}
$exam = $examResult->fetch_assoc();

// Process form submission
$error = "";
$success = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    // Update exam title
    $updateExamQuery = "UPDATE exams SET title = '$title' WHERE id = $exam_id";
    if ($conn->query($updateExamQuery)) {
        // Update each question
        $question_ids = $_POST['question_id'];
        $question_texts = $_POST['question_text'];
        $option_a = $_POST['option_a'];
        $option_b = $_POST['option_b'];
        $option_c = $_POST['option_c'];
        $option_d = $_POST['option_d'];
        $correct_option = $_POST['correct_option'];
        $allUpdated = true;
        foreach ($question_ids as $index => $q_id) {
            $q_id = intval($q_id);
            $q_text = mysqli_real_escape_string($conn, $question_texts[$index]);
            $a = mysqli_real_escape_string($conn, $option_a[$index]);
            $b = mysqli_real_escape_string($conn, $option_b[$index]);
            $c = mysqli_real_escape_string($conn, $option_c[$index]);
            $d = mysqli_real_escape_string($conn, $option_d[$index]);
            $correct = mysqli_real_escape_string($conn, $correct_option[$index]);
            $updateQuestion = "UPDATE questions 
                               SET question_text = '$q_text', option_a = '$a', option_b = '$b', option_c = '$c', option_d = '$d', correct_option = '$correct'
                               WHERE id = $q_id AND exam_id = $exam_id";
            if (!$conn->query($updateQuestion)) {
                $allUpdated = false;
                break;
            }
        }
        if ($allUpdated) {
            $success = "Exam updated successfully.";
        } else {
            $error = "There was an error updating some questions.";
        }
    } else {
        $error = "Error updating exam: " . $conn->error;
    }
    // Reload exam details
    $examQuery = "SELECT * FROM exams WHERE id = $exam_id AND teacher_id = $teacher_id";
    $examResult = $conn->query($examQuery);
    $exam = $examResult->fetch_assoc();
}

// Retrieve exam questions
$questionsQuery = "SELECT * FROM questions WHERE exam_id = $exam_id";
$questionsResult = $conn->query($questionsQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Exam - <?php echo htmlspecialchars($exam['title']); ?></title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .question-block {
      border: 1px solid #dee2e6;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 15px;
      background-color: #f8f9fa;
    }
  </style>
</head>
<body>
  <?php include '../include/header.php'; ?>
  <div class="container mt-4">
    <h2>Edit Exam</h2>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="POST" action="">
      <div class="mb-3">
        <label for="title" class="form-label">Exam Title</label>
        <input type="text" name="title" id="title" class="form-control" value="<?php echo htmlspecialchars($exam['title']); ?>" required>
      </div>
      <hr>
      <h4>Questions</h4>
      <div id="questions-container">
        <?php if ($questionsResult && $questionsResult->num_rows > 0): ?>
          <?php while ($question = $questionsResult->fetch_assoc()): ?>
            <div class="question-block">
              <input type="hidden" name="question_id[]" value="<?php echo $question['id']; ?>">
              <div class="mb-3">
                <label class="form-label">Question Text</label>
                <textarea name="question_text[]" class="form-control" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
              </div>
              <div class="mb-3 row">
                <div class="col-md-6 mb-2">
                  <label class="form-label">Option A</label>
                  <input type="text" name="option_a[]" class="form-control" value="<?php echo htmlspecialchars($question['option_a']); ?>" required>
                </div>
                <div class="col-md-6 mb-2">
                  <label class="form-label">Option B</label>
                  <input type="text" name="option_b[]" class="form-control" value="<?php echo htmlspecialchars($question['option_b']); ?>" required>
                </div>
                <div class="col-md-6 mb-2">
                  <label class="form-label">Option C</label>
                  <input type="text" name="option_c[]" class="form-control" value="<?php echo htmlspecialchars($question['option_c']); ?>" required>
                </div>
                <div class="col-md-6 mb-2">
                  <label class="form-label">Option D</label>
                  <input type="text" name="option_d[]" class="form-control" value="<?php echo htmlspecialchars($question['option_d']); ?>" required>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Correct Option</label>
                <select name="correct_option[]" class="form-select" required>
                  <option value="A" <?php echo ($question['correct_option'] == 'A') ? 'selected' : ''; ?>>A</option>
                  <option value="B" <?php echo ($question['correct_option'] == 'B') ? 'selected' : ''; ?>>B</option>
                  <option value="C" <?php echo ($question['correct_option'] == 'C') ? 'selected' : ''; ?>>C</option>
                  <option value="D" <?php echo ($question['correct_option'] == 'D') ? 'selected' : ''; ?>>D</option>
                </select>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="alert alert-warning">No questions found for this exam.</div>
        <?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary">Update Exam</button>
    </form>
  </div>
  <?php include '../include/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
