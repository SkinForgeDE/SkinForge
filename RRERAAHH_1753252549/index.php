<?php
// Include the mock database connection and data
require_once 'db.php';

// --- Data Fetching ---

// Get the selected server ID from the URL, default to 1 if not set
$server_id = isset($_GET['server']) ? (int)$_GET['server'] : 1;

// Fetch the data for the selected server
$server = getServerData($server_id);

// If no server is found for the given ID, stop execution
if (!$server) {
    die("Server not found.");
}

// Fetch the players currently on the selected server
$online_players = getOnlinePlayers($server_id);

// Fetch the top 10 all-time players for the selected server
$top_all_time_players = getTopPlayers($server_id, 'all_time');

// Fetch the top 10 players from the last 30 days for the selected server
$top_30_days_players = getTopPlayers($server_id, 'last_30_days');


// --- Map Image Logic ---

// An array that maps map names to image URLs.
// You can expand this with your actual map names and images.
$map_images = [
    'Site 09' => 'https://placehold.co/400x300/000000/FFFFFF?text=Site+09',
    'rp_downtown' => 'https://placehold.co/400x300/1a1a1a/FFFFFF?text=Downtown',
    'de_dust2' => 'https://placehold.co/400x300/333333/FFFFFF?text=Dust+II',
    'gm_construct' => 'https://placehold.co/400x300/2a2a2a/FFFFFF?text=Construct',
    // Add more maps here
    'default' => 'https://placehold.co/400x300/000000/FFFFFF?text=Unknown+Map'
];

// Get the image for the current map, or a default if not found
$map_image_url = isset($map_images[$server['map']]) ? $map_images[$server['map']] : $map_images['default'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Status Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Basic Reset and Body Styling */
        body {
            background-color: #101010;
            color: #e0e0e0;
            font-family: 'Orbitron', sans-serif;
            margin: 0;
            padding: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        /* Main Grid Container */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: auto 1fr;
            gap: 1.5rem;
            width: 100%;
            max-width: 1200px;
        }

        /* General Card Styling */
        .card {
            background-color: #1a1a1a;
            border: 1px solid #8A2BE2; /* Purple border */
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 0 15px rgba(138, 43, 226, 0.3);
        }

        /* Card Header Styling */
        .card h2 {
            margin-top: 0;
            color: #9370DB; /* Lighter Purple */
            font-size: 1.5rem;
            border-bottom: 1px solid #8A2BE2;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }

        /* Specific Grid Item Placement */
        .server-info { grid-column: 1 / 2; grid-row: 1 / 3; }
        .map-info { grid-column: 2 / 3; grid-row: 1 / 2; }
        .server-selector { grid-column: 3 / 4; grid-row: 1 / 2; }
        .top-players-all-time { grid-column: 2 / 3; grid-row: 2 / 3; }
        .top-players-30-days { grid-column: 3 / 4; grid-row: 2 / 3; }
        
        /* Server Info Card */
        .server-info h1 {
            color: #9370DB;
            margin-top: 0;
            font-size: 2rem;
        }
        .server-info .player-count {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
        }
        .server-info .player-list {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 500px;
            overflow-y: auto;
        }
        .server-info .player-list li {
            padding: 0.5rem;
            border-bottom: 1px solid #333;
        }
        .server-info .player-list li:last-child {
            border-bottom: none;
        }

        /* Map Info Card */
        .map-info {
            text-align: center;
        }
        .map-info img {
            width: 100%;
            max-width: 300px;
            height: auto;
            border-radius: 10px;
            border: 2px solid #9370DB;
            margin-top: 1rem;
        }

        /* Server Selector Card */
        .server-selector .selector-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        .server-selector .server-icon {
            background-color: #2a2a2a;
            border: 1px solid #444;
            border-radius: 8px;
            padding: 0.5rem;
            text-align: center;
            text-decoration: none;
            color: #e0e0e0;
            transition: background-color 0.3s, border-color 0.3s;
        }
        .server-selector .server-icon img {
            width: 50px;
            height: 50px;
        }
        .server-selector .server-icon.active,
        .server-selector .server-icon:hover {
            background-color: #8A2BE2;
            border-color: #9370DB;
            color: #fff;
        }

        /* Top Players Card */
        .top-players-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .top-players-list li {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem;
            border-bottom: 1px solid #333;
        }
        .top-players-list li:last-child {
            border-bottom: none;
        }
        .top-players-list .score {
            color: #9370DB;
        }

        /* Responsive Design */
        @media (max-width: 900px) {
            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
            }
            .server-info { grid-column: 1 / 3; grid-row: 1 / 2; }
            .map-info { grid-column: 1 / 2; grid-row: 2 / 3; }
            .server-selector { grid-column: 2 / 3; grid-row: 2 / 3; }
            .top-players-all-time { grid-column: 1 / 2; grid-row: 3 / 4; }
            .top-players-30-days { grid-column: 2 / 3; grid-row: 3 / 4; }
        }

        @media (max-width: 600px) {
            body {
                padding: 1rem;
            }
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            /* All items stack in a single column */
            .server-info, .map-info, .server-selector, .top-players-all-time, .top-players-30-days {
                grid-column: 1 / 2;
                grid-row: auto;
            }
        }

    </style>
</head>
<body>

    <div class="dashboard-grid">

        <!-- Server Info -->
        <div class="card server-info">
            <h1><?php echo htmlspecialchars($server['name']); ?></h1>
            <p class="player-count"><?php echo $server['players'] . ' / ' . $server['maxplayers']; ?> Players Online</p>
            <h2>Player List</h2>
            <ul class="player-list">
                <?php if (count($online_players) > 0): ?>
                    <?php foreach ($online_players as $player): ?>
                        <li><?php echo htmlspecialchars($player['name']); ?> - Score: <?php echo $player['score']; ?></li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>No players online.</li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Map Info -->
        <div class="card map-info">
            <h2>Map</h2>
            <p><?php echo htmlspecialchars($server['map']); ?></p>
            <img src="<?php echo $map_image_url; ?>" alt="Current Map Image">
        </div>

        <!-- Server Selector -->
        <div class="card server-selector">
            <h2>Server Selector</h2>
            <div class="selector-grid">
                <?php 
                $all_servers = getAllServers();
                foreach($all_servers as $s): 
                    $is_active = ($s['id'] == $server_id) ? 'active' : '';
                ?>
                <a href="?server=<?php echo $s['id']; ?>" class="server-icon <?php echo $is_active; ?>">
                    <img src="<?php echo $s['icon_url']; ?>" alt="<?php echo htmlspecialchars($s['short_name']); ?> Icon">
                    <span><?php echo htmlspecialchars($s['short_name']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Top 10 All Time -->
        <div class="card top-players-all-time">
            <h2>Top 10 Player of All Time</h2>
            <ol class="top-players-list">
                <?php if (count($top_all_time_players) > 0): ?>
                    <?php foreach ($top_all_time_players as $player): ?>
                        <li>
                            <span><?php echo htmlspecialchars($player['name']); ?></span>
                            <span class="score"><?php echo number_format($player['score']); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>No data available.</li>
                <?php endif; ?>
            </ol>
        </div>

        <!-- Top 10 Last 30 Days -->
        <div class="card top-players-30-days">
            <h2>Top 10 Player Last 30 Days</h2>
            <ol class="top-players-list">
                 <?php if (count($top_30_days_players) > 0): ?>
                    <?php foreach ($top_30_days_players as $player): ?>
                        <li>
                            <span><?php echo htmlspecialchars($player['name']); ?></span>
                            <span class="score"><?php echo number_format($player['score']); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>No data available.</li>
                <?php endif; ?>
            </ol>
        </div>

    </div>

</body>
</html>
