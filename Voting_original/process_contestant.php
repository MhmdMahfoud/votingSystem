<?php
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'organizer')) {
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
    $event_name = $_POST['event_name'];

    // Handle picture upload
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK &&
        isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {

        $uploadDir = 'uploads/'; // Directory to store uploaded pictures and logos
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Picture upload
        $pictureName = basename($_FILES['picture']['name']);
        $picturePath = $uploadDir . uniqid() . '_' . $pictureName;

        // Logo upload
        $logoName = basename($_FILES['logo']['name']);
        $logoPath = $uploadDir . uniqid() . '_' . $logoName;

        // Move uploaded files
        $pictureUploaded = move_uploaded_file($_FILES['picture']['tmp_name'], $picturePath);
        $logoUploaded = move_uploaded_file($_FILES['logo']['tmp_name'], $logoPath);

        if ($pictureUploaded && $logoUploaded) {
            // Insert contestant data into the database
            $stmt = $conn->prepare("INSERT INTO contestants (first_name, last_name, age, phone, email, picture, event_name, logo) 
                                    VALUES (:first_name, :last_name, :age, :phone, :email, :picture, :event_name, :logo)");
            $stmt->execute([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'age' => $age,
                'phone' => $phone,
                'email' => $email,
                'picture' => $picturePath,
                'event_name' => $event_name,
                'logo' => $logoPath
            ]);

            echo "<script>alert('Contestant created successfully!'); window.location.href='dashboard.php';</script>";
        } else {
            echo "<script>alert('Failed to upload files.'); window.location.href='create_contestant.php';</script>";
        }
    } else {
        echo "<script>alert('Please upload a valid picture and logo.'); window.location.href='create_contestant.php';</script>";
    }
}
?>
