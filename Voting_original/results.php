<?php
require 'db.php';

// Get current active event
$event = $conn->query("SELECT * FROM events ORDER BY created_at DESC LIMIT 1")->fetch();
if (!$event) die("No active event found");

// Get rounds for this event ordered by round number
$rounds = $conn->prepare("SELECT * FROM event_rounds WHERE event_id = ? ORDER BY round_number");
$rounds->execute([$event['id']]);
$rounds = $rounds->fetchAll();

// Get all contestants
$contestants = $conn->query("SELECT * FROM contestants")->fetchAll();

// Calculate round winners and cumulative results
$round_winners = [];
$cumulative_results = [];

foreach ($rounds as $round) {
    // Get all scores for this round
    $stmt = $conn->prepare("SELECT c.id, c.first_name, c.last_name, c.picture, AVG(js.score) as avg_score
                          FROM contestants c
                          JOIN jury_scores js ON c.id = js.contestant_id
                          WHERE js.round_id = ?
                          GROUP BY c.id
                          ORDER BY avg_score DESC");
    $stmt->execute([$round['id']]);
    $round_winners[$round['round_number']] = $stmt->fetchAll();
}

// Calculate cumulative scores
foreach ($contestants as $contestant) {
    $cumulative_total = 0;
    $round_details = [];
    
    foreach ($rounds as $round) {
        // Get average score for this round
        $stmt = $conn->prepare("SELECT AVG(score) as avg_score FROM jury_scores 
                              WHERE contestant_id = ? AND round_id = ?");
        $stmt->execute([$contestant['id'], $round['id']]);
        $current_round_avg = $stmt->fetch()['avg_score'] ?? 0;
        
        if ($round['round_number'] == 1) {
            $cumulative_total = $current_round_avg;
        } else {
            $cumulative_total = ($cumulative_total * ($rounds[$round['round_number']-2]['percentage'] / 100)) 
                             + ($current_round_avg * ($round['percentage'] / 100));
        }
        
        $round_details[$round['round_number']] = [
            'score' => round($current_round_avg, 2),
            'percentage' => $round['percentage']
        ];
    }
    
    $cumulative_results[] = [
        'contestant' => $contestant,
        'final_score' => round($cumulative_total, 2),
        'round_details' => $round_details
    ];
}

// Sort cumulative results
usort($cumulative_results, function($a, $b) {
    return $b['final_score'] <=> $a['final_score'];
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Results</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="images\updateDi.jpg">
    <style>
        .results-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
             /* Add this: */
    max-height: 80vh; /* You can change this value based on your design */
    overflow-y: auto;
        }
        .results-section {
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }
        .winner-card {
            background-color: #e6f7e6;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .winner-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #4CAF50;
        }
        .winner-info {
            flex-grow: 1;
        }
        .winner-title {
            color: #4CAF50;
            margin-bottom: 0.5rem;
        }
        .winner-score {
            font-size: 1.5rem;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
        }
        .contestant-info {
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
        }
        .contestant-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .nav-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 1rem;
        }
        .nav-button {
            padding: 0.5rem 1rem;
            background-color: #4361ee;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .nav-button:hover {
            background-color: #3a56d4;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="results-container">
        <h1>Event Results: <?= htmlspecialchars($event['event_name']) ?></h1>
        
        <div class="nav-buttons">
            <a href="#round-winners" class="nav-button">Round Winners</a>
            <a href="#final-results" class="nav-button">Final Results</a>
            <a href="event_setup.php" class="nav-button">back</a>

            
        </div>
        
        <div id="round-winners" class="results-section">
            <h2>Round Winners</h2>
            
            <?php foreach ($round_winners as $round_number => $winners): ?>
            <div style="margin-bottom: 2rem;">
                <h3>Round <?= $round_number ?> Winner</h3>
                
                <?php if (!empty($winners)): ?>
                <div class="winner-card">
                    <img src="<?= htmlspecialchars($winners[0]['picture']) ?>" 
                         class="winner-photo" 
                         alt="<?= htmlspecialchars($winners[0]['first_name'].' '.$winners[0]['last_name']) ?>">
                    <div class="winner-info">
                        <h3 class="winner-title">Round <?= $round_number ?> Champion</h3>
                        <h2><?= htmlspecialchars($winners[0]['first_name'].' '.$winners[0]['last_name']) ?></h2>
                        <div class="winner-score">Score: <?= round($winners[0]['avg_score'], 2) ?></div>
                    </div>
                </div>
                
                <h4 style="margin-top: 1.5rem;">All Contestants in Round <?= $round_number ?></h4>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Contestant</th>
                            <th>Average Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($winners as $index => $contestant): ?>
                        <tr class="<?= $index == 0 ? 'winner' : '' ?>">
                            <td><?= $index + 1 ?></td>
                            <td class="contestant-info">
                                <img src="<?= htmlspecialchars($contestant['picture']) ?>" 
                                     class="contestant-photo">
                                <?= htmlspecialchars($contestant['first_name'].' '.$contestant['last_name']) ?>
                            </td>
                            <td><?= round($contestant['avg_score'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No scores recorded for this round yet.</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div id="final-results" class="results-section">
            <h2>Final Cumulative Results</h2>
            
            <?php if (!empty($cumulative_results)): ?>
            <div class="winner-card">
                <img src="<?= htmlspecialchars($cumulative_results[0]['contestant']['picture']) ?>" 
                     class="winner-photo" 
                     alt="<?= htmlspecialchars($cumulative_results[0]['contestant']['first_name'].' '.$cumulative_results[0]['contestant']['last_name']) ?>">
                <div class="winner-info">
                    <h3 class="winner-title">Event Champion</h3>
                    <h2><?= htmlspecialchars($cumulative_results[0]['contestant']['first_name'].' '.$cumulative_results[0]['contestant']['last_name']) ?></h2>
                    <div class="winner-score">Final Score: <?= $cumulative_results[0]['final_score'] ?></div>
                </div>
            </div>
            
            <h3 style="margin-top: 2rem;">Complete Final Rankings</h3>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Contestant</th>
                        <?php foreach ($rounds as $round): ?>
                        <th>Round <?= $round['round_number'] ?> (<?= $round['percentage'] ?>%)</th>
                        <?php endforeach; ?>
                        <th>Final Score</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($cumulative_results as $index => $result): ?>
    <?php if ($result['final_score'] == 0) continue; ?>

                    <tr class="<?= $index == 0 ? 'winner' : '' ?>">
                        <td><?= $index + 1 ?></td>
                        <td class="contestant-info">
                            <img src="<?= htmlspecialchars($result['contestant']['picture']) ?>" 
                                 class="contestant-photo">
                            <?= htmlspecialchars($result['contestant']['first_name'].' '.$result['contestant']['last_name']) ?>
                        </td>
                        <?php foreach ($rounds as $round): ?>
                        <td><?= $result['round_details'][$round['round_number']]['score'] ?></td>
                        <?php endforeach; ?>
                        <td><strong><?= $result['final_score'] ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No cumulative results available yet.</p>
            <?php endif; ?>
        </div>
    </div>
   
</body>
</html>