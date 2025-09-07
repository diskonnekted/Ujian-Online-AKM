<?php
session_start();
require_once '../config/database.php';

// Initialize database connection
$pdo = getDBConnection();

// Log logout activity if user is logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_activities (teacher_id, activity_type, description, ip_address) VALUES (?, 'logout', 'User logged out', ?)");
        $stmt->execute([$_SESSION['admin_id'], $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    } catch (PDOException $e) {
        // Log error but continue with logout
    }
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>