<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $event_name = trim($_POST['event_name']); // Event name entered by jury

    // Admin login
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();

    if ($admin && $password === $admin['password']) {
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'admin';
        header('Location: dashboard.php');
        exit();
    }

    // Jury login
    $stmt = $conn->prepare("SELECT * FROM jury WHERE first_name = :first_name AND password = :password");
    $stmt->execute(['first_name' => $username, 'password' => $password]);
    $jury = $stmt->fetch();

    if ($jury) {
        // Verify event exists
        $eventStmt = $conn->prepare("SELECT COUNT(*) FROM contestants WHERE event_name = :event_name");
        $eventStmt->execute(['event_name' => $event_name]);
        $eventExists = $eventStmt->fetchColumn();

        if ($eventExists > 0) {
            $_SESSION['first_name'] = $jury['first_name'];
            $_SESSION['jury_id'] = $jury['id'];
            $_SESSION['role'] = 'jury';
            $_SESSION['event_name'] = $event_name;

            header('Location: view_contestant.php');
            exit();
        } else {
            echo "<script>alert('Invalid event name. Please try again.'); window.location.href='index.php';</script>";
            exit();
        }
    }

    // Organizer login
    $stmt = $conn->prepare("SELECT * FROM organizer WHERE name = :name AND password = :password");
    $stmt->execute(['name' => $username, 'password' => $password]);
    $organizer = $stmt->fetch();

    if ($organizer) {
        $_SESSION['name'] = $organizer['name'];
        $_SESSION['organizer_id'] = $organizer['id'];
        $_SESSION['role'] = 'organizer';
        header('Location: event_setup.php');
        exit();
    }

    // Invalid login
    echo "<script>alert('Invalid username or password.'); window.location.href='index.php';</script>";
}
?>
