<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

include '../db/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'], $_POST['exam_id'], $_POST['violation_type'], $_POST['action_status'])) {
    $student_id = intval($_POST['student_id']);
    $exam_id = intval($_POST['exam_id']);
    $violation_type = $_POST['violation_type'];
    $action_status = $_POST['action_status']; // 'removed' or 'kept'
    
    // Validate action_status
    if (!in_array($action_status, ['removed', 'kept'])) {
        $_SESSION['admin_error'] = "Invalid action selected.";
        header("Location: admin_dashboard.php");
        exit();
    }
    
    // Insert the admin action record (the latest record determines test availability)
    $query = "INSERT INTO admin_malpractice_actions (student_id, exam_id, action, action_date) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        $_SESSION['admin_error'] = "Preparation failed: " . $conn->error;
        header("Location: admin_dashboard.php");
        exit();
    }
    $stmt->bind_param("iis", $student_id, $exam_id, $action_status);
    
    if ($stmt->execute()) {
        // If the admin chooses "kept", remove the associated malpractice records from the database
        if ($action_status === 'kept') {
            $deleteQuery = "DELETE FROM malpractice_logs WHERE student_id = ? AND exam_id = ? AND violation_type = ?";
            $deleteStmt = $conn->prepare($deleteQuery);
            if ($deleteStmt) {
                $deleteStmt->bind_param("iis", $student_id, $exam_id, $violation_type);
                $deleteStmt->execute();
                $deleteStmt->close();
            }
        }
        $_SESSION['admin_success'] = "Action updated successfully.";
    } else {
        $_SESSION['admin_error'] = "Error updating action: " . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: admin_dashboard.php");
    exit();
} else {
    $_SESSION['admin_error'] = "Invalid request.";
    header("Location: admin_dashboard.php");
    exit();
}
?>