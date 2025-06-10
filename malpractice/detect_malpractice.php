<?php
// Enable error reporting (for development)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include '../db/db.php';

// Define uploads folder
$uploadDir = "../uploads/";

// Ensure the uploads folder exists and is writable
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
if (!is_writable($uploadDir)) {
    error_log("Uploads folder is not writable: " . realpath($uploadDir));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $exam_id = intval($_POST['exam_id']);
    $violation_type = $_POST['violation_type'];
    $student_id = $_SESSION['user_id'];
    
    $image_path = null;
    // Define violation types that should capture images.
    $captureViolations = ['eye_movement', 'no_face_detected', 'multiple_faces_detected'];
    if (in_array($violation_type, $captureViolations) && isset($_POST['image'])) {
        $dataURL = $_POST['image'];
        // Remove data URI prefix if present.
        if (strpos($dataURL, 'base64,') !== false) {
            $dataURL = explode('base64,', $dataURL)[1];
        }
        $imageData = base64_decode($dataURL);
        error_log("Decoded image length: " . strlen($imageData));
        // Generate a unique filename.
        $filename = "warning_" . time() . "_" . rand(1000, 9999) . ".jpg";
        $uploadPath = $uploadDir . $filename;
        if (file_put_contents($uploadPath, $imageData) !== false) {
            error_log("Image saved successfully: " . $uploadPath);
            $image_path = $filename;
        } else {
            error_log("Failed to save image to: " . $uploadPath);
        }
    }
    
    // Insert the malpractice log record.
    $sql = "INSERT INTO malpractice_logs (student_id, exam_id, violation_type, image_path) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo "Error logging malpractice (prepare failed).";
        exit();
    }
    $stmt->bind_param("iiss", $student_id, $exam_id, $violation_type, $image_path);
    if ($stmt->execute()) {
        echo "Malpractice logged.";
    } else {
        error_log("Execute failed: " . $stmt->error);
        echo "Error logging malpractice.";
    }
}
?>