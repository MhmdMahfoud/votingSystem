<?php
session_start();
require 'db.php';

// Check session and event name
if (!isset($_SESSION['jury_id']) || !isset($_SESSION['event_name'])) {
    echo "Access denied. Please login with event name.";
    exit();
}

$event_name = $_SESSION['event_name'];

// Get current contestant ID from URL or start with the first for this event
$current_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch current contestant by ID and event_name
$stmt = $conn->prepare("SELECT * FROM contestants WHERE id = :id AND event_name = :event_name");
$stmt->execute(['id' => $current_id, 'event_name' => $event_name]);
$contestant = $stmt->fetch();

// If no valid contestant, fetch first from this event
if (!$contestant) {
    $stmt = $conn->prepare("SELECT * FROM contestants WHERE event_name = :event_name ORDER BY id ASC LIMIT 1");
    $stmt->execute(['event_name' => $event_name]);
    $contestant = $stmt->fetch();
    $current_id = $contestant ? $contestant['id'] : 0;
}

// Get next contestant ID for this event
$stmt = $conn->prepare("SELECT id FROM contestants WHERE id > :id AND event_name = :event_name ORDER BY id ASC LIMIT 1");
$stmt->execute(['id' => $current_id, 'event_name' => $event_name]);
$next_id = $stmt->fetchColumn();

// If no next, get first again for looping
if (!$next_id) {
    $stmt = $conn->prepare("SELECT id FROM contestants WHERE event_name = :event_name ORDER BY id ASC LIMIT 1");
    $stmt->execute(['event_name' => $event_name]);
    $next_id = $stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contestant Profile</title>
    <link rel="icon" type="image/x-icon" href="images\updateDi.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #ffcc29;
            --dark-color: #2b2d42;
            --light-color: #f8f9fa;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--dark-color);
        }
        
        .profile-container {
            max-width: 800px;
            width: 90%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
        }
        
        .profile-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            margin: 0 auto;
            display: block;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            top: -30px;
            background: white;
        }
        
        .profile-body {
            padding: 30px;
            padding-top: 0;
        }
        
        .contestant-name {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark-color);
            text-align: center;
        }
        
        .contestant-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .detail-card {
            background: var(--light-color);
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .detail-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        
        .btn-vote {
            background-color: var(--accent-color);
            color: #000;
            flex-grow: 1;
            margin: 0 10px;
        }
        
        .btn-vote:hover {
            background-color: #ffb400;
            transform: translateY(-2px);
        }
        
        .btn-next {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-next:hover {
            background-color: var(--secondary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .progress-indicator {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .contestant-details {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <?php if ($contestant): ?>
    <div class="profile-container">
        <div class="profile-header">
            <h1>Contestant Profile</h1>
        </div>
        
        <img src="<?= htmlspecialchars($contestant['picture']); ?>" alt="Profile Picture" class="profile-picture">
        
        <div class="profile-body">
        <h2 class="contestant-name"><?= htmlspecialchars($contestant['first_name'] . ' ' . $contestant['last_name']); ?></h2>
            
            <div class="contestant-details">
                <div class="detail-card">
                    <div class="detail-label">Age</div>
                    <div class="detail-value"><?= htmlspecialchars($contestant['age']); ?></div>
                </div>

                <!--  -->
           
                <!--  -->
                
                <div class="detail-card">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value"><?= htmlspecialchars($contestant['phone']); ?></div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><?= htmlspecialchars($contestant['email']); ?></div>
                </div>
                
                <?php if (!empty($contestant['bio'])): ?>
                <div class="detail-card" style="grid-column: 1 / -1">
                    <div class="detail-label">Bio</div>
                    <div class="detail-value"><?= htmlspecialchars($contestant['bio']); ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="action-buttons">
                <a href="score.php?id=<?= $contestant['id']; ?>" class="btn btn-vote">Vote Now</a>
                <a href="?id=<?= $next_id ?>" class="btn btn-next">Next Contestant </a><br>
                <a href="index.php" class="btn btn-back"  style="background-color:red;margin:5px">Back </a>
         
            </div>
            
            <div class="progress-indicator">
                Viewing contestant <?= $current_id ?> of <?= $stmt = $conn->query("SELECT COUNT(*) FROM contestants")->fetchColumn(); ?>
            </div>
         
        </div>
    </div>
    <?php else: ?>
    <div class="profile-container">
        <div class="profile-header">
            <h1>No Contestants Found</h1>
        </div>
        <div class="profile-body text-center">
            <p>There are currently no contestants in the system.</p>
            <a href="admin_dashboard.php" class="btn btn-primary">Return to Dashboard</a>
        </div>
    </div>
    <?php endif; ?>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>