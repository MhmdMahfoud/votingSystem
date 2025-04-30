<?php
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'organizer')) {
    // Redirect to login page if not logged in as admin or organizer
    header("Location: index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="images\updateDi.jpg">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <h2 class="text">Welcome, <?php echo $_SESSION['username']; ?>!</h2>
        <div class="buttons">
            <button onclick="location.href='create_jury.php'">Create New Jury</button>
            <button onclick="location.href='create_contestant.php'">Create New Contestant</button>
            <button onclick="location.href='index.php'">back</button>
     
        </div>
    </div>
</body>
</html>