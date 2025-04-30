
<?php

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="images\updateDi.jpg">
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <form action="login.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <label for="event_name">Event Name:</label>
            <input type="text" id="event_name" name="event_name" placeholder="Enter the name of the event" required>

            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
