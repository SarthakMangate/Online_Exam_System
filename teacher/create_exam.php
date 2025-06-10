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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate exam title
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    if (empty($title)) {
        $error = "Exam title is required.";
    } else {
        // Insert exam record into exams table
        $insertExam = "INSERT INTO exams (title, teacher_id) VALUES ('$title', $teacher_id)";
        if ($conn->query($insertExam)) {
            $exam_id = $conn->insert_id;
            // Retrieve question arrays from POST data
            $question_texts = $_POST['question_text'];
            $option_a = $_POST['option_a'];
            $option_b = $_POST['option_b'];
            $option_c = $_POST['option_c'];
            $option_d = $_POST['option_d'];
            $correct_option = $_POST['correct_option'];

            $numQuestions = count($question_texts);
            $allInserted = true;
            for ($i = 0; $i < $numQuestions; $i++) {
                // Skip question if question text is empty
                if (empty(trim($question_texts[$i]))) continue;
                
                $q_text = mysqli_real_escape_string($conn, $question_texts[$i]);
                $a = mysqli_real_escape_string($conn, $option_a[$i]);
                $b = mysqli_real_escape_string($conn, $option_b[$i]);
                $c = mysqli_real_escape_string($conn, $option_c[$i]);
                $d = mysqli_real_escape_string($conn, $option_d[$i]);
                $correct = mysqli_real_escape_string($conn, $correct_option[$i]);

                $insertQuestion = "INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option)
                                    VALUES ($exam_id, '$q_text', '$a', '$b', '$c', '$d', '$correct')";
                if (!$conn->query($insertQuestion)) {
                    $allInserted = false;
                    break;
                }
            }
            if ($allInserted) {
                $success = "Exam and questions created successfully.";
            } else {
                $error = "Exam created but there was an error inserting questions.";
            }
        } else {
            $error = "Error creating exam: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create Exam</title>
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
    <h2>Create New Exam</h2>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <form method="POST" action="">
      <div class="mb-3">
        <label for="title" class="form-label">Exam Title</label>
        <input type="text" name="title" id="title" class="form-control" required>
      </div>
      <hr>
      <h4>Questions</h4>
      <div id="questions-container">
        <div class="question-block">
          <div class="mb-3">
            <label class="form-label">Question Text</label>
            <textarea name="question_text[]" class="form-control" required></textarea>
          </div>
          <div class="mb-3 row">
            <div class="col-md-6 mb-2">
              <label class="form-label">Option A</label>
              <input type="text" name="option_a[]" class="form-control" required>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Option B</label>
              <input type="text" name="option_b[]" class="form-control" required>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Option C</label>
              <input type="text" name="option_c[]" class="form-control" required>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Option D</label>
              <input type="text" name="option_d[]" class="form-control" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Correct Option</label>
            <select name="correct_option[]" class="form-select" required>
              <option value="A">A</option>
              <option value="B">B</option>
              <option value="C">C</option>
              <option value="D">D</option>
            </select>
          </div>
        </div>
      </div>
      <button type="button" id="add-question" class="btn btn-secondary mb-3">Add Another Question</button>
      <br>
      <button type="submit" class="btn btn-primary">Create Exam</button>
    </form>
  </div>

  <script>
    document.getElementById('add-question').addEventListener('click', function(){
      var container = document.getElementById('questions-container');
      var newQuestion = document.querySelector('.question-block').cloneNode(true);
      // Clear input values in the cloned question block
      newQuestion.querySelectorAll('textarea, input, select').forEach(function(input){
        input.value = '';
      });
      container.appendChild(newQuestion);
    });
  </script>

  <?php include '../include/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
