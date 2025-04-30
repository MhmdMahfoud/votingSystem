<?php
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'organizer')) {
    // Redirect to login page if not logged in as admin or organizer
    header("Location: index.php");
    exit();
}

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $age = $_POST['age'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $sponsor_guest = $_POST['sponsor_guest']; 
    $password = $_POST['password'];
    
    // Get the selected dropdown value

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO jury (first_name, last_name, age, phone, email, sponsor_guest, password) VALUES (:first_name, :last_name, :age, :phone, :email, :sponsor_guest, :password)");
    $stmt->execute([
        'first_name' => $first_name,
        'last_name' => $last_name,
        'age' => $age,
        'phone' => $phone,
        'email' => $email,
        'sponsor_guest' => $sponsor_guest,
        'password'=>$password
    ]);

    echo "<script>alert('Jury created successfully!'); window.location.href='dashboard.php';</script>";
}
?>