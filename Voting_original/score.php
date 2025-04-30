<?php
session_start();
require 'db.php';

if (!isset($_SESSION['jury_id'])) {
    header('Location: index.php');
    exit();
}

$jury_id = $_SESSION['jury_id'];
$contestant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_round = isset($_GET['round']) ? (int)$_GET['round'] : 1;

// Get contestant details
$contestant = $conn->prepare("SELECT * FROM contestants WHERE id = ?");
$contestant->execute([$contestant_id]);
$contestant = $contestant->fetch();

// Get current active event
$event = $conn->query("SELECT * FROM events ORDER BY created_at DESC LIMIT 1")->fetch();
if (!$event) die("No active event found");

// Get rounds for this event
$rounds = $conn->prepare("SELECT * FROM event_rounds WHERE event_id = ? ORDER BY round_number");
$rounds->execute([$event['id']]);
$rounds = $rounds->fetchAll();

// Get existing scores for this jury and contestant
$existing_scores = [];
$scores = $conn->prepare("SELECT round_id, score FROM jury_scores 
                         WHERE jury_id = ? AND contestant_id = ?");
$scores->execute([$jury_id, $contestant_id]);
while ($row = $scores->fetch()) {
    $existing_scores[$row['round_id']] = $row['score'];
}

// Determine which rounds are completed and which are available
$completed_rounds = [];
$available_round = 1; // Start with round 1

foreach ($rounds as $round) {
    if (isset($existing_scores[$round['id']])) {
        $completed_rounds[$round['round_number']] = true;
        $available_round = $round['round_number'] + 1;
    } else {
        break; // Stop at first uncompleted round
    }
}

// If trying to access a round that's not available, redirect to first available
if ($current_round > $available_round) {
    header("Location: score.php?id=$contestant_id&round=$available_round");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $round_id = (int)$_POST['round_id'];
    $score = (float)$_POST['score'];
    
    // Validate score
    if ($score < 0 || $score > 10) {
        die("Invalid score - must be between 0 and 10");
    }

    try {
        $conn->beginTransaction();
        
        // Check if score exists
        $check = $conn->prepare("SELECT id FROM jury_scores 
                               WHERE jury_id = ? AND contestant_id = ? AND round_id = ?");
        $check->execute([$jury_id, $contestant_id, $round_id]);

        if ($check->rowCount() > 0) {
            // Update existing score
            $stmt = $conn->prepare("UPDATE jury_scores SET score = ? 
                                  WHERE jury_id = ? AND contestant_id = ? AND round_id = ?");
            $stmt->execute([$score, $jury_id, $contestant_id, $round_id]);
        } else {
            // Insert new score
            $stmt = $conn->prepare("INSERT INTO jury_scores 
                                  (jury_id, contestant_id, round_id, score) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$jury_id, $contestant_id, $round_id, $score]);
        }
        
        $conn->commit();
        
        // Redirect to next round if available
        $next_round = $current_round + 1;
        $has_next_round = false;
        foreach ($rounds as $round) {
            if ($round['round_number'] == $next_round) {
                $has_next_round = true;
                break;
            }
        }
        
        if ($has_next_round) {
            header("Location: score.php?id=$contestant_id&round=$next_round");
        } else {
            header("Location: view_contestant.php?id=$contestant_id");
        }
        exit();
        
    } catch (PDOException $e) {
        $conn->rollBack();
        die("Database error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Score Contestant</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .score-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .round-tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .round-tab {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px 4px 0 0;
            cursor: pointer;
            position: relative;
        }
        .round-tab.available {
            background-color: #4361ee;
            color: white;
        }
        .round-tab.completed {
            background-color: #4bb543;
            color: white;
        }
        .round-tab.locked {
            background-color: #f0f0f0;
            color: #999;
            cursor: not-allowed;
        }
        .round-tab.current {
            box-shadow: 0 0 0 2px #ffcc29;
        }
        .round-content {
            display: none;
        }
        .round-content.active {
            display: block;
        }
        .round-score {
            margin-bottom: 1.5rem;
        }
        .round-name {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        input[type="number"] {
            width: 100px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .contestant-info {
            text-align: center;
            margin-bottom: 2rem;
        }
        .contestant-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem;
            display: block;
        }
        .btn {
            padding: 0.5rem 1rem;
            background-color: #4361ee;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #3a56d4;
        }
        .completion-message {
            color: #4bb543;
            font-weight: bold;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="score-container">
        <div class="contestant-info">
            <img src="<?= htmlspecialchars($contestant['picture']) ?>" 
                 alt="Contestant Photo" class="contestant-photo">
            <h2><?= htmlspecialchars($contestant['first_name'].' '.$contestant['last_name']) ?></h2>
        </div>

        <div class="round-tabs">
            <?php foreach ($rounds as $round): 
                $round_num = $round['round_number'];
                $is_completed = isset($completed_rounds[$round_num]);
                $is_available = $round_num <= $available_round;
                $is_current = $round_num == $current_round;
                
                $tab_class = '';
                if ($is_completed) {
                    $tab_class = 'completed';
                } elseif ($is_available) {
                    $tab_class = $is_current ? 'available current' : 'available';
                } else {
                    $tab_class = 'locked';
                }
            ?>
            <button class="round-tab <?= $tab_class ?>" 
                    <?= !$is_available ? 'disabled' : '' ?>
                    onclick="<?= $is_available ? "showRound($round_num)" : "void(0)" ?>">
                Round <?= $round_num ?>
                <?php if ($is_completed): ?>
                <span style="margin-left: 5px;">✓</span>
                <?php endif; ?>
            </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($rounds as $round): 
            $round_num = $round['round_number'];
            $is_current = $round_num == $current_round;
            $is_available = $round_num <= $available_round;
        ?>
        <div class="round-content <?= $is_current ? 'active' : '' ?>" 
             id="round-<?= $round_num ?>">
            <?php if ($is_available): ?>
            <form method="POST" action="">
                <input type="hidden" name="round_id" value="<?= $round['id'] ?>">
                
                <div class="round-score">
                    <div class="round-name">Round <?= $round_num ?> (<?= $round['percentage'] ?>%)</div>
                    <label>Score (0-10):</label>
                    <input type="number" name="score" 
                           min="0" max="10" step="0.1"
                           value="<?= $existing_scores[$round['id']] ?? '' ?>" required>
                </div>

                <button type="submit" class="btn">
                    <?= isset($existing_scores[$round['id']]) ? 'Update Score' : 'Submit Score' ?>
                </button>
                <a href="view_contestant.php?id=<?= $contestant_id ?>" class="btn">Back</a>
                
                <?php if (isset($existing_scores[$round['id']])): ?>
                <div class="completion-message">You've already scored this round</div>
                <?php endif; ?>
            </form>
            <?php else: ?>
            <div class="round-score">
                <p>Please complete Round <?= $round_num - 1 ?> before scoring this round.</p>
                <a href="score.php?id=<?= $contestant_id ?>&round=<?= $available_round ?>" class="btn">
                    Go to Round <?= $available_round ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        function showRound(roundNumber) {
            // Only allow navigation to available rounds
            window.location.href = `?id=<?= $contestant_id ?>&round=${roundNumber}`;
        }
    </script>
</body>
</html>