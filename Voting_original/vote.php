<?php
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'organizer')) {
    // Redirect to login page if not logged in as admin or organizer
    header("Location: index.php");
    exit();
}
include 'db.php'; // your DB connection

$jury_id = $_POST['jury_id']; // e.g., from session
$contestant_id = $_POST['contestant_id'];
$round = $_POST['round'];
$score = $_POST['score'];

if ($score < 0 || $score > 10) {
    die("Score must be between 0 and 10.");
}

$stmt = $conn->prepare("REPLACE INTO votes (jury_id, contestant_id, round, score) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiid", $jury_id, $contestant_id, $round, $score);

if ($stmt->execute()) {
    echo "Vote saved successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$conn->close();
?>
