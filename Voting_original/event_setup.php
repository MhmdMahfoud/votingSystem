<?php
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'organizer')) {
    // Redirect to login page if not logged in as admin or organizer
    header("Location: index.php");
    exit();
}

require 'db.php';




if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_name = $_POST['event_name'];
    $rounds = $_POST['rounds'];
    
    // Ensure total percentage equals 100
    $percentages = $_POST['percentages'];
    $total_percentage = array_sum($percentages);
    
    if ($total_percentage != 100) {
        echo "<script>alert('Total percentage must be 100%');</script>";
    } else {
        // Insert event details into database
        $stmt = $conn->prepare("INSERT INTO events (event_name, rounds) VALUES (:event_name, :rounds)");
        $stmt->execute(['event_name' => $event_name, 'rounds' => $rounds]);
        
        $event_id = $conn->lastInsertId();
        
        for ($i = 0; $i < count($percentages); $i++) {
            $stmt = $conn->prepare("INSERT INTO event_rounds (event_id, round_number, percentage) VALUES (:event_id, :round_number, :percentage)");
            $stmt->execute(['event_id' => $event_id, 'round_number' => $i + 1, 'percentage' => $percentages[$i]]);
        }
        
        echo "<script>alert('Event created successfully!'); window.location.href='event_setup.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Setup</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="event_setup.css" rel="stylesheet" >
    <link rel="icon" type="image/x-icon" href="images\updateDi.jpg">
    <style>
        
    </style>
</head>
<body>
    <div class="container">
        <h2>Setup New Event</h2>
        <form method="POST" action="" id="eventForm">
            <div class="form-group">
                <label for="event_name">Event Name:</label>
                <input type="text" id="event_name" name="event_name" required placeholder="Enter event name">
            </div>
            
            <div class="form-group">
                <label for="rounds">Number of Rounds:</label>
                <input type="number" id="rounds" name="rounds" min="1" required placeholder="Enter number of rounds">
            </div>
            
            <div id="rounds_container" class="form-group"></div>
            
            <div class="percentage-total">Total: <span id="totalPercentage">0</span>%</div>
            <div class="percentage-error" id="percentageError">Total percentage must equal 100%</div>
            
           <a href="event_setup.php"> <button type="submit">Create Event</button></a>
           
          


        </form>
        <a href="results.php" class="" type="res"> <button type="submit">Result</button></a>
        
        <a href="index.php" class="back-link">← Back </a>
    </div>
    
    <script>
        document.getElementById('rounds').addEventListener('input', function() {
            let container = document.getElementById('rounds_container');
            container.innerHTML = '';
            let rounds = parseInt(this.value);
            
            if (rounds > 0 && rounds <= 10) { // Limit to 10 rounds max
                for (let i = 1; i <= rounds; i++) {
                    let roundDiv = document.createElement('div');
                    roundDiv.className = 'round-input';
                    roundDiv.innerHTML = `
                        <label for="round_${i}">Round ${i} Percentage:</label>
                        <input type="number" id="round_${i}" name="percentages[]" min="0" max="100" required 
                               oninput="calculateTotal()" placeholder="%">
                    `;
                    container.appendChild(roundDiv);
                }
                calculateTotal();
            }
        });
        
        function calculateTotal() {
            let percentageInputs = document.querySelectorAll('input[name="percentages[]"]');
            let total = 0;
            
            percentageInputs.forEach(input => {
                let value = parseInt(input.value) || 0;
                total += value;
            });
            
            document.getElementById('totalPercentage').textContent = total;
            
            if (total !== 100 && percentageInputs.length > 0) {
                document.getElementById('percentageError').style.display = 'block';
            } else {
                document.getElementById('percentageError').style.display = 'none';
            }
        }
        
        document.getElementById('eventForm').addEventListener('submit', function(e) {
            let total = parseInt(document.getElementById('totalPercentage').textContent);
            let rounds = parseInt(document.getElementById('rounds').value);
            let percentageInputs = document.querySelectorAll('input[name="percentages[]"]');
            
            if (percentageInputs.length > 0 && total !== 100) {
                e.preventDefault();
                document.getElementById('percentageError').style.display = 'block';
                alert('Total percentage must equal 100%');
            }
        });
    </script>
</body>
</html>