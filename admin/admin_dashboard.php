<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
   header("Location: ../auth/login.php");
   exit();
}
include '../db/db.php';

// Retrieve session messages if any.
$error = "";
$success = "";
if (isset($_SESSION['admin_error'])) {
   $error = $_SESSION['admin_error'];
   unset($_SESSION['admin_error']);
}
if (isset($_SESSION['admin_success'])) {
   $success = $_SESSION['admin_success'];
   unset($_SESSION['admin_success']);
}

// Retrieve aggregated malpractice incidents with images (if any).
// This query uses GROUP_CONCAT to combine all image_path values for each violation.
$malpracticeQuery = "SELECT 
    ml.student_id, 
    ml.exam_id,
    u.name AS student_name, 
    e.title AS exam_title,
    MIN(ml.timestamp) AS first_occurrence,
    COUNT(*) AS total_violations,
    -- All violation types for this student+exam, separated by commas
    GROUP_CONCAT(DISTINCT ml.violation_type ORDER BY ml.violation_type ASC SEPARATOR ', ') AS violation_types,
    -- All image paths (if any)
    GROUP_CONCAT(ml.image_path SEPARATOR ',') AS image_paths
FROM malpractice_logs ml
JOIN users u ON ml.student_id = u.id
JOIN exams e ON ml.exam_id = e.id
GROUP BY ml.student_id, ml.exam_id
HAVING COUNT(*) >= 3
ORDER BY first_occurrence DESC
";
$malpracticeResult = $conn->query($malpracticeQuery);

// Retrieve exam list with teacher information.
$examQuery = "SELECT e.id, e.title, e.created_at, u.name AS teacher_name 
              FROM exams e 
              JOIN users u ON e.teacher_id = u.id 
              ORDER BY e.created_at DESC";
$examResult = $conn->query($examQuery);

// Retrieve student exam attempts.
$studentAttemptsQuery = "SELECT se.id, se.student_id, se.exam_id, se.score, se.completed_at, u.name AS student_name, e.title AS exam_title 
                         FROM student_exams se 
                         JOIN users u ON se.student_id = u.id 
                         JOIN exams e ON se.exam_id = e.id 
                         ORDER BY se.completed_at DESC";
$studentAttemptsResult = $conn->query($studentAttemptsQuery);

// Retrieve teacher list.
$teacherQuery = "SELECT id, name, email, created_at FROM users WHERE role = 'teacher' ORDER BY created_at DESC";
$teacherResult = $conn->query($teacherQuery);

// Retrieve student list with attempt status.
$studentQuery = "SELECT u.id, u.name, u.email, u.created_at,
                CASE WHEN se.student_id IS NULL THEN 'No' ELSE 'Yes' END AS attempted
                FROM users u
                LEFT JOIN (SELECT DISTINCT student_id FROM student_exams) se ON u.id = se.student_id
                WHERE u.role = 'student'
                ORDER BY u.created_at DESC";
$studentResult = $conn->query($studentQuery);
?>
<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <title>Admin Dashboard</title>
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
   <link rel="stylesheet" href="../css/style.css">
   <style>
      body {
         background-color: #f8f9fa;
      }

      .container {
         margin-top: 30px;
      }

      .card {
         margin-bottom: 20px;
      }

      .nav-tabs .nav-link {
         font-weight: 500;
         font-size: 1rem;
      }

      .table thead th,
      .table tbody td {
         vertical-align: middle;
         text-align: center;
      }

      .alert {
         margin-top: 20px;
      }

      .evidence-img {
         max-width: 80px;
         cursor: pointer;
         transition: transform 0.3s ease, box-shadow 0.3s ease;
      }

      .evidence-img:hover {
         transform: scale(1.2);
         box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
      }

      /* Modal background */
      .modal-backdrop {
         background: rgba(0, 0, 0, 0.8);
      }

      /* Large image inside the modal */
      .modal-content img {
         max-width: 100%;
         height: auto;
         display: block;
         margin: auto;
         border-radius: 8px;
      }

      /* Close button in the top-right corner */
      .modal-header .btn-close {
         color: white;
         background: rgba(255, 255, 255, 0.8);
         padding: 8px;
         border-radius: 50%;
         font-size: 20px;
      }

      .modal-header .btn-close:hover {
         background: rgba(255, 255, 255, 1);
      }
   </style>
</head>

<body>
   <!-- Bootstrap Modal (Put this anywhere in your HTML, outside the table) -->
   <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
         <div class="modal-content bg-dark">
            <div class="modal-header border-0">
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
               <img id="modalImage" src="" alt="Evidence Image">
            </div>
         </div>
      </div>
   </div>

   <?php include '../include/header.php'; ?>
   <div class="container">
      <h2 class="mb-4 text-center">Admin Dashboard</h2>
      <?php if ($error): ?>
         <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
         <div class="alert alert-success text-center"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <!-- Nav Tabs -->
      <ul class="nav nav-tabs mb-3" id="adminTabs" role="tablist">
         <li class="nav-item" role="presentation">
            <button class="nav-link active" id="malpractice-tab" data-bs-toggle="tab" data-bs-target="#malpractice"
               type="button" role="tab" aria-controls="malpractice" aria-selected="true">
               Malpractice Management
            </button>
         </li>
         <li class="nav-item" role="presentation">
            <button class="nav-link" id="examlist-tab" data-bs-toggle="tab" data-bs-target="#examlist" type="button"
               role="tab" aria-controls="examlist" aria-selected="false">
               Exam List
            </button>
         </li>
         <li class="nav-item" role="presentation">
            <button class="nav-link" id="studentattempts-tab" data-bs-toggle="tab" data-bs-target="#studentattempts"
               type="button" role="tab" aria-controls="studentattempts" aria-selected="false">
               Student Test Results
            </button>
         </li>
         <li class="nav-item" role="presentation">
            <button class="nav-link" id="teacherlist-tab" data-bs-toggle="tab" data-bs-target="#teacherlist"
               type="button" role="tab" aria-controls="teacherlist" aria-selected="false">
               Teacher List
            </button>
         </li>
         <li class="nav-item" role="presentation">
            <button class="nav-link" id="studentlist-tab" data-bs-toggle="tab" data-bs-target="#studentlist"
               type="button" role="tab" aria-controls="studentlist" aria-selected="false">
               Student List
            </button>
         </li>
      </ul>

      <!-- Tab Content -->
      <div class="tab-content" id="adminTabsContent">
         <!-- Malpractice Management Tab -->
         <div class="tab-pane fade show active" id="malpractice" role="tabpanel" aria-labelledby="malpractice-tab">
            <div class="card shadow-sm">
               <div class="card-header bg-secondary text-white">
                  Malpractice Management
               </div>
               <div class="card-body">
                  <p>Review flagged malpractice incidents and update their status.</p>
                  <?php if ($malpracticeResult && $malpracticeResult->num_rows > 0): ?>
                     <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                           <thead class="table-secondary">
                              <tr>
                                 <th>Student Name</th>
                                 <th>Exam Title</th>
                                 <th>Violation Types</th>
                                 <th>First Occurrence</th>
                                 <th>Total Violations</th>
                                 <th>Evidence</th>
                                 <th>Action</th>
                              </tr>
                           </thead>
                           <tbody>
                              <?php while ($row = $malpracticeResult->fetch_assoc()): ?>
                                 <tr>
                                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['exam_title']); ?></td>
                                    <td>
                                       <?php
                                       // If no violation types found, default to "N/A"
                                       echo !empty($row['violation_types'])
                                          ? htmlspecialchars($row['violation_types'])
                                          : "N/A";
                                       ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['first_occurrence']); ?></td>
                                    <td><?php echo htmlspecialchars($row['total_violations']); ?></td>
                                    <td>
                                       <?php
                                       // Show images if they exist
                                       if (!empty($row['image_paths'])) {
                                          $images = explode(',', $row['image_paths']);
                                          foreach ($images as $img) {
                                             if (!empty($img)) {
                                                echo "<img class='evidence-img' src='../uploads/" . htmlspecialchars($img) . "' alt='Evidence'>";
                                             }
                                          }
                                       } else {
                                          echo "N/A";
                                       }
                                       ?>
                                    </td>
                                    <td>
                                       <form method="POST" action="admin_update_malpratice.php"
                                          class="d-flex align-items-center">
                                          <input type="hidden" name="student_id" value="<?php echo $row['student_id']; ?>">
                                          <input type="hidden" name="exam_id" value="<?php echo $row['exam_id']; ?>">
                                          <!-- You might store violation_types in a hidden field, or handle differently -->
                                          <input type="hidden" name="violation_type"
                                             value="<?php echo htmlspecialchars($row['violation_types']); ?>">
                                          <select name="action_status" class="form-select form-select-sm me-2" required>
                                             <option value="">Select Action</option>
                                             <option value="removed">Remove from Test</option>
                                             <option value="kept">Keep in Test</option>
                                          </select>
                                          <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                       </form>
                                    </td>
                                 </tr>
                              <?php endwhile; ?>
                           </tbody>
                        </table>
                     </div>
                  <?php else: ?>
                     <div class="alert alert-info text-center">No malpractice incidents found.</div>
                  <?php endif; ?>
               </div>
            </div>
         </div>


         <!-- Other Tabs (Exam List, Student Test Results, Teacher List, Student List) remain unchanged -->
         <div class="tab-pane fade" id="examlist" role="tabpanel" aria-labelledby="examlist-tab">
            <div class="card shadow-sm">
               <div class="card-header bg-primary text-white">
                  Exam List
               </div>
               <div class="card-body">
                  <p>Review all exams and see which teacher created them.</p>
                  <?php if ($examResult && $examResult->num_rows > 0): ?>
                     <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                           <thead class="table-primary">
                              <tr>
                                 <th>Exam ID</th>
                                 <th>Exam Title</th>
                                 <th>Teacher Name</th>
                                 <th>Created On</th>
                              </tr>
                           </thead>
                           <tbody>
                              <?php while ($exam = $examResult->fetch_assoc()): ?>
                                 <tr>
                                    <td><?php echo htmlspecialchars($exam['id']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['teacher_name']); ?></td>
                                    <td><?php echo htmlspecialchars(date("Y-m-d", strtotime($exam['created_at']))); ?></td>
                                 </tr>
                              <?php endwhile; ?>
                           </tbody>
                        </table>
                     </div>
                  <?php else: ?>
                     <div class="alert alert-warning text-center">No exams found.</div>
                  <?php endif; ?>
               </div>
            </div>
         </div>

         <div class="tab-pane fade" id="studentattempts" role="tabpanel" aria-labelledby="studentattempts-tab">
            <div class="card shadow-sm">
               <div class="card-header bg-info text-white">
                  Student Test Results
               </div>
               <div class="card-body">
                  <p>Review all exam attempts by students.</p>
                  <?php if ($studentAttemptsResult && $studentAttemptsResult->num_rows > 0): ?>
                     <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                           <thead class="table-info">
                              <tr>
                                 <th>Attempt ID</th>
                                 <th>Student Name</th>
                                 <th>Exam Title</th>
                                 <th>Score</th>
                                 <th>Date</th>
                              </tr>
                           </thead>
                           <tbody>
                              <?php while ($attempt = $studentAttemptsResult->fetch_assoc()): ?>
                                 <tr>
                                    <td><?php echo htmlspecialchars($attempt['id']); ?></td>
                                    <td><?php echo htmlspecialchars($attempt['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($attempt['exam_title']); ?></td>
                                    <td><?php echo htmlspecialchars($attempt['score']); ?></td>
                                    <td><?php echo htmlspecialchars(date("Y-m-d", strtotime($attempt['completed_at']))); ?>
                                    </td>
                                 </tr>
                              <?php endwhile; ?>
                           </tbody>
                        </table>
                     </div>
                  <?php else: ?>
                     <div class="alert alert-info text-center">No exam attempts found.</div>
                  <?php endif; ?>
               </div>
            </div>
         </div>

         <div class="tab-pane fade" id="teacherlist" role="tabpanel" aria-labelledby="teacherlist-tab">
            <div class="card shadow-sm">
               <div class="card-header bg-light">
                  <h5 class="mb-0">Teacher List</h5>
               </div>
               <div class="card-body">
                  <p>Review all registered teachers.</p>
                  <?php if ($teacherResult && $teacherResult->num_rows > 0): ?>
                     <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                           <thead class="table-light">
                              <tr>
                                 <th>Teacher ID</th>
                                 <th>Name</th>
                                 <th>Email</th>
                                 <th>Registered On</th>
                              </tr>
                           </thead>
                           <tbody>
                              <?php while ($teacher = $teacherResult->fetch_assoc()): ?>
                                 <tr>
                                    <td><?php echo htmlspecialchars($teacher['id']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                    <td><?php echo htmlspecialchars(date("Y-m-d", strtotime($teacher['created_at']))); ?></td>
                                 </tr>
                              <?php endwhile; ?>
                           </tbody>
                        </table>
                     </div>
                  <?php else: ?>
                     <div class="alert alert-warning text-center">No teachers found.</div>
                  <?php endif; ?>
               </div>
            </div>
         </div>

         <div class="tab-pane fade" id="studentlist" role="tabpanel" aria-labelledby="studentlist-tab">
            <div class="card shadow-sm">
               <div class="card-header bg-light">
                  <h5 class="mb-0">Student List</h5>
               </div>
               <div class="card-body">
                  <p>Review all registered students along with their test attempt status.</p>
                  <?php if ($studentResult && $studentResult->num_rows > 0): ?>
                     <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                           <thead class="table-light">
                              <tr>
                                 <th>Student ID</th>
                                 <th>Name</th>
                                 <th>Email</th>
                                 <th>Registered On</th>
                                 <th>Attempted Test?</th>
                              </tr>
                           </thead>
                           <tbody>
                              <?php while ($student = $studentResult->fetch_assoc()): ?>
                                 <tr>
                                    <td><?php echo htmlspecialchars($student['id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars(date("Y-m-d", strtotime($student['created_at']))); ?></td>
                                    <td><?php echo htmlspecialchars($student['attempted']); ?></td>
                                 </tr>
                              <?php endwhile; ?>
                           </tbody>
                        </table>
                     </div>
                  <?php else: ?>
                     <div class="alert alert-warning text-center">No students found.</div>
                  <?php endif; ?>
               </div>
            </div>
         </div>
      </div>
   </div>
   <?php include '../include/footer.php'; ?>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <!-- Your custom JavaScript for image preview -->
   <script>
      document.addEventListener("DOMContentLoaded", function () {
         const images = document.querySelectorAll(".evidence-img");
         const modalImage = document.getElementById("modalImage");

         images.forEach(img => {
            img.addEventListener("click", function () {
               modalImage.src = this.src;
               new bootstrap.Modal(document.getElementById("imageModal")).show();
            });
         });
      });
   </script>
</body>
</html>
</body>
</html>