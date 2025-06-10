<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
  header("Location: ../auth/login.php");
  exit();
}

include '../db/db.php';

$student_id = $_SESSION['user_id'];

if (!isset($_GET['exam_id'])) {
  echo "Exam ID not provided.";
  exit();
}
$exam_id = intval($_GET['exam_id']);

// Fetch exam details.
$examQuery = "SELECT * FROM exams WHERE id = $exam_id";
$examResult = $conn->query($examQuery);
if ($examResult->num_rows == 0) {
  echo "Exam not found.";
  exit();
}
$exam = $examResult->fetch_assoc();

// Process exam submission.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // If exam is auto-submitted due to malpractice, score is 0.
  if (isset($_POST['auto_submitted']) && $_POST['auto_submitted'] === "true") {
    $score = 0;
  } else {
    $score = 0;
    $totalQuestions = 0;
    $questionsQuery = "SELECT * FROM questions WHERE exam_id = $exam_id";
    $questionsResult = $conn->query($questionsQuery);
    while ($question = $questionsResult->fetch_assoc()) {
      $totalQuestions++;
      $selected = isset($_POST["question_" . $question['id']]) ? $_POST["question_" . $question['id']] : '';
      if ($selected == $question['correct_option']) {
        $score++;
      }
    }
  }
  // Insert exam result.
  $insertQuery = "INSERT INTO student_exams (student_id, exam_id, score) VALUES ($student_id, $exam_id, $score)";
  if ($conn->query($insertQuery)) {
    header("Location: exam_results.php?exam_id=" . $exam_id);
    exit();
  } else {
    $error = "Error saving exam result.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Take Exam - <?php echo htmlspecialchars($exam['title']); ?></title>
  <!-- Bootstrap CSS (v5.3) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../css/style.css">
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background-color: #f8f9fa;
    }

    .exam-container {
      margin-top: 30px;
    }

    #rulesContainer {
      animation: fadeInSlide 0.8s ease-out;
    }

    @keyframes fadeInSlide {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .card {
      border-radius: 10px;
    }

    .card-header {
      font-size: 1.25rem;
      font-weight: 600;
    }

    .btn-primary {
      transition: transform 0.2s ease;
    }

    .btn-primary:hover {
      transform: scale(1.03);
    }

    .video-graph-container {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-bottom: 20px;
    }

    #videoContainer,
    #chartContainer {
      flex: 1 1 320px;
      max-width: 320px;
      position: relative;
    }

    #video {
      border: 2px solid #007bff;
      border-radius: 10px;
      width: 100%;
      height: auto;
    }

    #overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
    }

    #chartContainer canvas {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 5px;
      padding: 5px;
    }

    #stabilizeMsg {
      display: none;
      padding: 15px;
      background: #fff3cd;
      border: 1px solid #ffeeba;
      border-radius: 10px;
      margin-bottom: 20px;
      text-align: center;
      font-weight: bold;
    }
  </style>
</head>

<body>
  <?php include '../include/header.php'; ?>
  <div class="container exam-container">
    <h2 class="mb-4 text-center"><?php echo htmlspecialchars($exam['title']); ?></h2>
    <?php if (isset($error)): ?>
      <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <!-- Exam Rules Section -->
    <div id="rulesContainer" class="card p-4 shadow-sm">
      <div class="card-header bg-primary text-white">Exam Rules</div>
      <div class="card-body">
        <ul class="list-group list-group-flush">
          <li class="list-group-item">No unauthorized materials are allowed during the exam.</li>
          <li class="list-group-item">Your webcam will be monitored for head pose and eye direction.</li>
          <li class="list-group-item">The first image captured by your webcam will serve as your baseline for your eyes.
          </li>
          <li class="list-group-item">Slight body or head movement is allowed. Only significant deviations of your eyes
            will trigger a warning.</li>
          <li class="list-group-item">If 3 warnings are issued, your exam will be auto‑submitted and marked as
            "Cheated".</li>
          <li class="list-group-item">Please do not switch tabs or copy text during the exam.</li>
        </ul>
        <div class="d-grid mt-3">
          <button id="startExamBtn" class="btn btn-success">Start Exam</button>
        </div>
      </div>
    </div>
    <!-- Stabilization Message -->
    <div id="stabilizeMsg" class="alert alert-warning text-center">Please remain still for 5 seconds... capturing
      baseline.</div>
    <!-- Test Interface: initially hidden -->
    <div id="testContainer" style="display:none;">
      <div class="video-graph-container">
        <div id="videoContainer">
          <video id="video" autoplay muted></video>
          <canvas id="overlay"></canvas>
        </div>
        <div id="chartContainer">
          <canvas id="movementChart"></canvas>
        </div>
      </div>
      <form method="POST" id="examForm">
        <input type="hidden" name="auto_submitted" id="auto_submitted" value="false">
        <?php
        $questionsQuery = "SELECT * FROM questions WHERE exam_id = $exam_id";
        $questionsResult = $conn->query($questionsQuery);
        if ($questionsResult->num_rows > 0):
          $qNumber = 1;
          while ($question = $questionsResult->fetch_assoc()):
            ?>
            <div class="card mb-3 shadow-sm">
              <div class="card-body">
                <h5 class="card-title">Question <?php echo $qNumber++; ?>:</h5>
                <p class="card-text"><?php echo htmlspecialchars($question['question_text']); ?></p>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="question_<?php echo $question['id']; ?>" value="A"
                    id="q<?php echo $question['id']; ?>A">
                  <label class="form-check-label" for="q<?php echo $question['id']; ?>A">A.
                    <?php echo htmlspecialchars($question['option_a']); ?></label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="question_<?php echo $question['id']; ?>" value="B"
                    id="q<?php echo $question['id']; ?>B">
                  <label class="form-check-label" for="q<?php echo $question['id']; ?>B">B.
                    <?php echo htmlspecialchars($question['option_b']); ?></label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="question_<?php echo $question['id']; ?>" value="C"
                    id="q<?php echo $question['id']; ?>C">
                  <label class="form-check-label" for="q<?php echo $question['id']; ?>C">C.
                    <?php echo htmlspecialchars($question['option_c']); ?></label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="question_<?php echo $question['id']; ?>" value="D"
                    id="q<?php echo $question['id']; ?>D">
                  <label class="form-check-label" for="q<?php echo $question['id']; ?>D">D.
                    <?php echo htmlspecialchars($question['option_d']); ?></label>
                </div>
              </div>
            </div>
          <?php endwhile; else: ?>
          <div class="alert alert-warning text-center">No questions available for this exam.</div>
        <?php endif; ?>
        <div class="d-grid">
          <button type="submit" class="btn btn-primary">Submit Exam</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      // When "Start Exam" is clicked, hide rules, show stabilization message,
      // then after 5 seconds hide stabilization and reveal test interface.
      document.getElementById("startExamBtn").addEventListener("click", function () {
        document.getElementById("rulesContainer").style.display = "none";
        document.getElementById("stabilizeMsg").style.display = "block";
        setTimeout(function () {
          document.getElementById("stabilizeMsg").style.display = "none";
          document.getElementById("testContainer").style.display = "block";
        }, 5000);
      });

      // Global flag to disable detection after submission.
      let examSubmitted = false;
      document.getElementById("examForm").addEventListener("submit", function () {
        examSubmitted = true;
      });

      // Warning counter.
      let warningCount = 0;

      // Baseline for eye center (captured after stabilization).
      let baselineEyeCenter = null;
      // Threshold values for significant deviation.
      const movementThreshold = 35; // Only significant deviation triggers warning.
      const consecutiveMovementsRequired = 3; // Number of consecutive frames required.
      let movementCounter = 0;

      // Setup Chart.js line chart.
      let sampleCount = 0;
      const ctx = document.getElementById('movementChart').getContext('2d');
      const movementChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: [],
          datasets: [{
            label: 'Deviation from Baseline (px)',
            data: [],
            borderColor: 'red',
            backgroundColor: 'rgba(255,0,0,0.1)',
            tension: 0.2,
          }]
        },
        options: {
          responsive: true,
          animation: { duration: 300, easing: 'linear' },
          scales: {
            x: { title: { display: true, text: 'Sample #' } },
            y: { beginAtZero: true, title: { display: true, text: 'Distance (px)' } }
          }
        }
      });

      function updateChart(newDistance) {
        sampleCount++;
        movementChart.data.labels.push(sampleCount);
        movementChart.data.datasets[0].data.push(newDistance);
        console.log("Chart updated with new distance: " + newDistance);
        movementChart.update();
      }

      // Debounced tab-switch detection.
      let visibilityTimeout;
      document.addEventListener("visibilitychange", function () {
        if (examSubmitted) return;
        clearTimeout(visibilityTimeout);
        if (document.hidden) {
          visibilityTimeout = setTimeout(() => {
            if (document.hidden && !examSubmitted) {
              recordMalpractice('tab_switch');
            }
          }, 1000);
        }
      });

      // Inactivity detection (90 seconds).
      let inactivityTimeout;
      function resetInactivityTimer() {
        clearTimeout(inactivityTimeout);
        inactivityTimeout = setTimeout(() => {
          if (!examSubmitted) { recordMalpractice('inactivity'); }
        }, 90000);
      }
      document.addEventListener("mousemove", resetInactivityTimer);
      document.addEventListener("keydown", resetInactivityTimer);
      resetInactivityTimer();

      // Prevent copy, cut, and paste.
      document.addEventListener('copy', e => { e.preventDefault(); recordMalpractice('copy_text'); });
      document.addEventListener('cut', e => { e.preventDefault(); recordMalpractice('cut_text'); });
      document.addEventListener('paste', e => { e.preventDefault(); recordMalpractice('paste_text'); });
      document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'c') {
          e.preventDefault();
          recordMalpractice('copy_text');
        }
      });

      function recordMalpractice(violationType, imageData = null) {
        warningCount++;
        alert("Warning: Detected " + violationType + ". (" + warningCount + " of 3 warnings)");
        const examId = <?php echo $exam_id; ?>;
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "../malpractice/detect_malpractice.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        let params = "exam_id=" + examId + "&violation_type=" + violationType;
        const captureViolations = ['eye_movement', 'no_face_detected', 'multiple_faces_detected'];
        if (captureViolations.indexOf(violationType) !== -1 && imageData) {
          params += "&image=" + encodeURIComponent(imageData);
        }
        xhr.send(params);
        if (warningCount >= 3) {
          document.getElementById("auto_submitted").value = "true";
          alert("3 warnings reached. Your exam will be submitted and marked as Cheated.");
          document.getElementById("examForm").submit();
        }
      }


      // Helper function: in_array for JavaScript.
      function in_array(value, array) {
        return array.indexOf(value) !== -1;
      }

      // Setup video and overlay.
      const video = document.getElementById('video');
      const overlay = document.getElementById('overlay');
      const overlayCtx = overlay.getContext('2d');

      async function startVideo() {
        try {
          const stream = await navigator.mediaDevices.getUserMedia({ video: true });
          video.srcObject = stream;
        } catch (err) {
          console.error("Webcam access denied or error:", err);
        }
      }
      startVideo();

      async function captureFrameAndDetect() {
        if (video.paused || video.ended) return;
        const captureCanvas = document.createElement('canvas');
        captureCanvas.width = video.videoWidth;
        captureCanvas.height = video.videoHeight;
        const ctx = captureCanvas.getContext('2d');
        ctx.drawImage(video, 0, 0, captureCanvas.width, captureCanvas.height);
        const dataURL = captureCanvas.toDataURL('image/jpeg');

        try {
          const response = await fetch('http://127.0.0.1:5000/track_eye_movement', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ image: dataURL, session_id: "<?php echo session_id(); ?>" })
          });
          const result = await response.json();
          overlayCtx.clearRect(0, 0, overlay.width, overlay.height);

          if (result.face_count === 0) {
            recordMalpractice('no_face_detected', dataURL);
            baselineEyeCenter = null;
          } else if (result.face_count > 1) {
            recordMalpractice('multiple_faces_detected', dataURL);
            baselineEyeCenter = null;
          } else if (result.eye_center) {
            // (Existing processing for eye_movement)
            overlayCtx.beginPath();
            overlayCtx.arc(result.eye_center.x, result.eye_center.y, 5, 0, 2 * Math.PI);
            overlayCtx.strokeStyle = 'red';
            overlayCtx.lineWidth = 2;
            overlayCtx.stroke();

            if (!baselineEyeCenter) {
              baselineEyeCenter = { ...result.eye_center };
              console.log("Baseline captured:", baselineEyeCenter);
            }

            const dx = result.eye_center.x - baselineEyeCenter.x;
            const dy = result.eye_center.y - baselineEyeCenter.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            console.log("Distance from baseline:", distance);
            updateChart(distance);

            if (distance > movementThreshold) {
              movementCounter++;
              console.log("Movement counter increased:", movementCounter);
            } else {
              movementCounter = Math.max(0, movementCounter - 1);
              console.log("Movement counter decreased:", movementCounter);
            }
            if (movementCounter >= consecutiveMovementsRequired) {
              recordMalpractice('eye_movement', dataURL);
              movementCounter = 0;
            }
          }

        } catch (error) {
          console.error("Error detecting eye movement:", error);
        }
      }

      // Run detection every 2500 ms continuously until exam submission.
      setInterval(captureFrameAndDetect, 2500);
    });
  </script>

  <?php include '../include/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>