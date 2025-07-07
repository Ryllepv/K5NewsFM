<?php
require_once 'config/db.php';

// Fetch latest news articles
$stmt = $pdo->prepare("SELECT a.*, GROUP_CONCAT(t.name) AS tags FROM articles a LEFT JOIN article_tags at ON a.id = at.article_id LEFT JOIN tags t ON at.tag_id = t.id GROUP BY a.id ORDER BY a.created_at DESC LIMIT 10");
$stmt->execute();
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch live updates
$stmt = $pdo->query("SELECT * FROM live_updates ORDER BY created_at DESC LIMIT 5");
$liveUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch tags
$stmt = $pdo->query("SELECT * FROM tags ORDER BY name ASC");
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch program schedule
$stmt = $pdo->query("SELECT * FROM program_schedule ORDER BY program_name,
    CASE
        WHEN day_of_week = 'Monday' THEN 1
        WHEN day_of_week = 'Tuesday' THEN 2
        WHEN day_of_week = 'Wednesday' THEN 3
        WHEN day_of_week = 'Thursday' THEN 4
        WHEN day_of_week = 'Friday' THEN 5
        WHEN day_of_week = 'Saturday' THEN 6
        WHEN day_of_week = 'Sunday' THEN 7
        ELSE 8
    END, time_slot");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group programs by name and time slot for compact display
$groupedPrograms = [];
foreach ($schedules as $schedule) {
    $key = $schedule['program_name'] . '|' . $schedule['time_slot'];
    $groupedPrograms[$key]['program_name'] = $schedule['program_name'];
    $groupedPrograms[$key]['time_slot'] = $schedule['time_slot'];
    $groupedPrograms[$key]['days'][] = $schedule['day_of_week'];
}

// Function to format day ranges
function formatDayRange($days) {
    $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $dayAbbr = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    // Sort days according to week order
    usort($days, function($a, $b) use ($dayOrder) {
        return array_search($a, $dayOrder) - array_search($b, $dayOrder);
    });

    // Convert to abbreviations
    $abbrDays = array_map(function($day) use ($dayOrder, $dayAbbr) {
        return $dayAbbr[array_search($day, $dayOrder)];
    }, $days);

    // Create ranges
    if (count($abbrDays) == 1) {
        return $abbrDays[0];
    } elseif (count($abbrDays) == 7) {
        return 'Daily';
    } elseif (array_slice($abbrDays, 0, 5) == ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'] && count($abbrDays) == 5) {
        return 'Mon-Fri';
    } elseif (array_slice($abbrDays, 5, 2) == ['Sat', 'Sun'] && count($abbrDays) == 2) {
        return 'Sat-Sun';
    } else {
        // Check for consecutive ranges
        $ranges = [];
        $start = 0;
        for ($i = 1; $i <= count($abbrDays); $i++) {
            if ($i == count($abbrDays) || array_search($abbrDays[$i], $dayAbbr) != array_search($abbrDays[$i-1], $dayAbbr) + 1) {
                if ($i - $start > 2) {
                    $ranges[] = $abbrDays[$start] . '-' . $abbrDays[$i-1];
                } elseif ($i - $start == 2) {
                    $ranges[] = $abbrDays[$start] . ', ' . $abbrDays[$i-1];
                } else {
                    $ranges[] = $abbrDays[$start];
                }
                $start = $i;
            }
        }
        return implode(', ', $ranges);
    }
}

// --- Weather API (Open-Meteo) ---
$weather = null;
$weatherError = null;
$forecast = [];
$city = 'Olongapo City, Zambales';

// Olongapo City coordinates
$latitude = 14.8292;
$longitude = 120.2828;

// Fetch current weather and 5-day forecast
$weatherUrl = "https://api.open-meteo.com/v1/forecast?latitude=$latitude&longitude=$longitude"
    . "&current_weather=true"
    . "&hourly=temperature_2m,weathercode,relative_humidity_2m,pressure_msl,visibility,apparent_temperature,windspeed_10m"
    . "&daily=temperature_2m_max,temperature_2m_min,weathercode,sunrise,sunset"
    . "&timezone=Asia/Manila";

// Create a context for file_get_contents with timeout
$context = stream_context_create([
    'http' => [
        'timeout' => 10, // 10 seconds timeout
        'user_agent' => 'Mozilla/5.0 (compatible; Weather App)',
        'method' => 'GET'
    ]
]);

$weatherResponse = @file_get_contents($weatherUrl, false, $context);

if ($weatherResponse !== false) {
    $weatherData = json_decode($weatherResponse, true);

    if ($weatherData && isset($weatherData['current_weather'])) {
        $weather = [
            'temp' => $weatherData['current_weather']['temperature'],
            'windspeed' => $weatherData['current_weather']['windspeed'],
            'weathercode' => $weatherData['current_weather']['weathercode'],
            'time' => $weatherData['current_weather']['time']
        ];

        // Build forecast array
        $forecast = [];
        if (isset($weatherData['daily']['time']) && is_array($weatherData['daily']['time'])) {
            for ($i = 0; $i < min(5, count($weatherData['daily']['time'])); $i++) {
                $forecast[] = [
                    'date' => $weatherData['daily']['time'][$i] ?? '',
                    'max' => $weatherData['daily']['temperature_2m_max'][$i] ?? 'N/A',
                    'min' => $weatherData['daily']['temperature_2m_min'][$i] ?? 'N/A',
                    'weathercode' => $weatherData['daily']['weathercode'][$i] ?? 0,
                    'sunrise' => $weatherData['daily']['sunrise'][$i] ?? '',
                    'sunset' => $weatherData['daily']['sunset'][$i] ?? '',
                ];
            }
        }

        // Get extra details for current hour
        $hourlyIndex = null;
        if (isset($weatherData['hourly']['time']) && is_array($weatherData['hourly']['time'])) {
            $hourlyIndex = array_search($weather['time'], $weatherData['hourly']['time']);
        }

        $weather['humidity'] = ($hourlyIndex !== false && $hourlyIndex !== null && isset($weatherData['hourly']['relative_humidity_2m'][$hourlyIndex]))
            ? $weatherData['hourly']['relative_humidity_2m'][$hourlyIndex] : 'N/A';
        $weather['pressure'] = ($hourlyIndex !== false && $hourlyIndex !== null && isset($weatherData['hourly']['pressure_msl'][$hourlyIndex]))
            ? $weatherData['hourly']['pressure_msl'][$hourlyIndex] : 'N/A';
        $weather['visibility'] = ($hourlyIndex !== false && $hourlyIndex !== null && isset($weatherData['hourly']['visibility'][$hourlyIndex]))
            ? $weatherData['hourly']['visibility'][$hourlyIndex] : 'N/A';

    } else {
        $weatherError = "Weather data format not recognized.";
        // For debugging - you can remove this in production
        if ($weatherData && isset($weatherData['error'])) {
            $weatherError .= " API Error: " . $weatherData['reason'];
        }
    }
} else {
    $weatherError = "Could not fetch weather data. Please check your internet connection.";
    // Check if it's a network issue
    if (!function_exists('file_get_contents')) {
        $weatherError = "file_get_contents function is not available.";
    } elseif (!ini_get('allow_url_fopen')) {
        $weatherError = "URL fopen is disabled. Please enable allow_url_fopen in PHP settings.";
    }
}

// Weather code to emoji and description
function weather_icon($code) {
    // Open-Meteo weather codes: https://open-meteo.com/en/docs
    $map = [
        0 => ['☀️', 'Clear sky'],
        1 => ['⛅', 'Mainly clear'],
        2 => ['⛅', 'Partly cloudy'],
        3 => ['☁️', 'Overcast'],
        45 => ['🌫️', 'Fog'],
        48 => ['🌫️', 'Depositing rime fog'],
        51 => ['🌦️', 'Light drizzle'],
        53 => ['🌦️', 'Drizzle'],
        55 => ['🌦️', 'Dense drizzle'],
        56 => ['🌧️', 'Freezing drizzle'],
        57 => ['🌧️', 'Freezing drizzle'],
        61 => ['🌦️', 'Slight rain'],
        63 => ['🌧️', 'Rain'],
        65 => ['🌧️', 'Heavy rain'],
        66 => ['🌧️', 'Freezing rain'],
        67 => ['🌧️', 'Freezing rain'],
        71 => ['🌨️', 'Slight snow'],
        73 => ['🌨️', 'Snow'],
        75 => ['🌨️', 'Heavy snow'],
        77 => ['🌨️', 'Snow grains'],
        80 => ['🌧️', 'Rain showers'],
        81 => ['🌧️', 'Rain showers'],
        82 => ['🌧️', 'Violent rain showers'],
        85 => ['🌨️', 'Snow showers'],
        86 => ['🌨️', 'Heavy snow showers'],
        95 => ['⛈️', 'Thunderstorm'],
        96 => ['⛈️', 'Thunderstorm with hail'],
        99 => ['⛈️', 'Thunderstorm with hail'],
    ];
    return $map[$code] ?? ['❓', 'Unknown'];
}

// Traffic tip fallback
$trafficTip = "Drive safely and follow local traffic rules in Olongapo City.";

// Fetch featured media (admin can add links to this table: featured_media)
$stmt = $pdo->query("SELECT * FROM featured_media ORDER BY id DESC LIMIT 6");
$featuredMedia = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch radio station members for About section
$stmt = $pdo->query("SELECT * FROM station_members ORDER BY id ASC");
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch upcoming events
$stmt = $pdo->query("SELECT * FROM events WHERE event_date >= CURDATE() AND status IN ('upcoming', 'ongoing') ORDER BY event_date ASC, event_time ASC LIMIT 6");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch current month's Philippine holidays
$currentMonth = date('m');
$currentYear = date('Y');
$stmt = $pdo->prepare("SELECT * FROM philippine_holidays WHERE MONTH(date) = ? AND YEAR(date) = ? ORDER BY date ASC");
$stmt->execute([$currentMonth, $currentYear]);
$holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K5 News FM - Backend</title>
    <style>
        /* Minimal CSS */
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px;
            background: #f8f8f8; 
            color: #333;
            line-height: 1.5;
        }
        
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 15px;
        }
        
        header { 
            background: #003366; 
            color: white; 
            padding: 15px; 
            margin-bottom: 20px;
        }
        
        nav { 
            background: #eee; 
            padding: 10px; 
            margin-bottom: 20px;
        }
        
        nav a { 
            color: #003366; 
            text-decoration: none; 
            margin-right: 15px;
            font-weight: bold;
        }
        
        section { 
            background: white; 
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        h1, h2 { 
            color: #003366;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px 12px; 
            text-align: left;
        }
        
        th { 
            background: #003366;
            color: white;
        }
        
        .card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            background: #e6f7ff;
            color: #003366;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>K5 News FM - Backend Data</h1>
        </div>
    </header>
    
    <div class="container">
        <nav>
            <a href="#weather">Weather</a>
            <a href="#news">News</a>
            <a href="#live">Live Updates</a>
            <a href="#events">Events</a>
            <a href="#schedule">Schedule</a>
            <a href="#holidays">Holidays</a>
            <a href="#tags">Tags</a>
            <a href="#media">Featured Media</a>
            <a href="#team">Team</a>
        </nav>
        
        <!-- Weather Section -->
        <section id="weather">
            <h2>Weather Information - <?php echo htmlspecialchars($city); ?></h2>
            <?php if ($weather): ?>
                <div class="card">
                    <h3>Current Weather</h3>
                    <p>
                        <?php echo weather_icon($weather['weathercode'])[0]; ?> 
                        <?php echo round($weather['temp']); ?>°C - 
                        <?php echo weather_icon($weather['weathercode'])[1]; ?>
                    </p>
                    <p>Humidity: <?php echo is_numeric($weather['humidity']) ? htmlspecialchars($weather['humidity']) . '%' : 'N/A'; ?></p>
                    <p>Wind Speed: <?php echo htmlspecialchars($weather['windspeed']); ?> km/h</p>
                    <p>Pressure: <?php echo is_numeric($weather['pressure']) ? htmlspecialchars($weather['pressure']) . ' hPa' : 'N/A'; ?></p>
                    <p>Visibility: 
                    <?php
                        if (isset($weather['visibility']) && is_numeric($weather['visibility'])) {
                            echo round($weather['visibility'] / 1000, 1) . " km";
                        } else {
                            echo "N/A";
                        }
                    ?>
                    </p>
                    <p>Sunrise: <?php echo isset($forecast[0]['sunrise']) ? date('g:i A', strtotime($forecast[0]['sunrise'])) : 'N/A'; ?></p>
                    <p>Sunset: <?php echo isset($forecast[0]['sunset']) ? date('g:i A', strtotime($forecast[0]['sunset'])) : 'N/A'; ?></p>
                </div>
                
                <?php if ($forecast && count($forecast) > 0): ?>
                <h3>5-Day Forecast</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Max Temp (°C)</th>
                            <th>Min Temp (°C)</th>
                            <th>Weather</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($forecast as $day): ?>
                        <tr>
                            <td><?php echo date('D, M j', strtotime($day['date'])); ?></td>
                            <td><?php echo htmlspecialchars($day['max']); ?></td>
                            <td><?php echo htmlspecialchars($day['min']); ?></td>
                            <td><?php echo weather_icon($day['weathercode'])[0]; ?> <?php echo weather_icon($day['weathercode'])[1]; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            <?php else: ?>
                <div class="card">
                    <p><strong>⚠️ Weather Service Unavailable</strong></p>
                    <p><?php echo htmlspecialchars($weatherError); ?></p>
                </div>
            <?php endif; ?>
        </section>

        <!-- News Section -->
        <section id="news">
            <h2>Latest News Articles</h2>
            <div class="grid">
            <?php foreach ($articles as $article): ?>
                <div class="card">
                    <h3><?php echo htmlspecialchars($article['title']); ?></h3>
                    <p><small>📅 <?php echo date('M j, Y', strtotime($article['created_at'])); ?></small></p>
                    <?php if ($article['tags']): ?>
                        <p><small>🏷️ <?php echo htmlspecialchars($article['tags']); ?></small></p>
                    <?php endif; ?>
                    <p>
                        <?php
                        $excerpt = strip_tags($article['content']);
                        echo htmlspecialchars(strlen($excerpt) > 150 ? substr($excerpt, 0, 150) . '...' : $excerpt);
                        ?>
                    </p>
                    <a href="article.php?id=<?php echo $article['id']; ?>">Read more</a>
                </div>
            <?php endforeach; ?>
            </div>
        </section>

        <!-- Live Updates Section -->
        <section id="live">
            <h2>Live Updates</h2>
            <?php if (!empty($liveUpdates)): ?>
                <?php foreach ($liveUpdates as $update): ?>
                    <div class="card">
                        <p><?php echo htmlspecialchars($update['message']); ?></p>
                        <small>Posted: <?php echo date('M j, Y g:i A', strtotime($update['created_at'])); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No live updates at the moment.</p>
            <?php endif; ?>
        </section>

        <!-- Events Section -->
        <section id="events">
            <h2>Upcoming Events</h2>
            <?php if (!empty($events)): ?>
                <div class="grid">
                <?php foreach ($events as $event): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                        <p>
                            📅 <?php echo date('M j, Y', strtotime($event['event_date'])); ?>
                            <?php if ($event['event_time']): ?>
                                at <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                            <?php endif; ?>
                        </p>
                        <?php if ($event['location']): ?>
                            <p>📍 <?php echo htmlspecialchars($event['location']); ?></p>
                        <?php endif; ?>
                        <?php if ($event['description']): ?>
                            <p>
                                <?php
                                $preview = substr(strip_tags($event['description']), 0, 120);
                                echo htmlspecialchars($preview);
                                if (strlen($event['description']) > 120) echo '...';
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No upcoming events at the moment.</p>
            <?php endif; ?>
        </section>

        <!-- Program Schedule Section -->
        <section id="schedule">
            <h2>Program Schedule</h2>
            <?php if (!empty($groupedPrograms)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Program Name</th>
                            <th>Days</th>
                            <th>Time Slot</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($groupedPrograms as $program): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($program['program_name']); ?></td>
                            <td><span class="badge"><?php echo formatDayRange($program['days']); ?></span></td>
                            <td><?php echo htmlspecialchars($program['time_slot']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No program schedule available.</p>
            <?php endif; ?>
        </section>

        <!-- Holidays Section -->
        <section id="holidays">
            <h2>Philippine Holidays - <?php echo date('F Y'); ?></h2>
            <?php if (!empty($holidays)): ?>
                <div class="grid">
                <?php foreach ($holidays as $holiday): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($holiday['name']); ?></h3>
                        <p>📅 <?php echo date('M j, Y', strtotime($holiday['date'])); ?></p>
                        <p>
                            <span class="badge">
                            <?php
                            switch($holiday['type']) {
                                case 'regular': echo 'Regular Holiday'; break;
                                case 'special_non_working': echo 'Special Non-Working'; break;
                                case 'special_working': echo 'Special Working'; break;
                                default: echo ucfirst($holiday['type']);
                            }
                            ?>
                            </span>
                        </p>
                        <?php if ($holiday['description']): ?>
                            <p><small><?php echo htmlspecialchars($holiday['description']); ?></small></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No holidays this month.</p>
            <?php endif; ?>
        </section>

        <!-- Tags Section -->
        <section id="tags">
            <h2>Tags</h2>
            <div>
            <?php foreach ($tags as $tag): ?>
                <span class="badge" style="margin: 5px;">
                    <a href="?tag=<?php echo urlencode($tag['name']); ?>" style="text-decoration: none; color: inherit;">
                        <?php echo htmlspecialchars($tag['name']); ?>
                    </a>
                </span>
            <?php endforeach; ?>
            </div>
        </section>

        <!-- Featured Media Section -->
        <section id="media">
            <h2>Featured Media</h2>
            <?php if (!empty($featuredMedia)): ?>
                <div class="grid">
                <?php foreach ($featuredMedia as $media): ?>
                    <div class="card">
                        <?php if (!empty($media['title'])): ?>
                            <h3><?php echo htmlspecialchars($media['title']); ?></h3>
                        <?php endif; ?>
                        
                        <p>
                            <a href="<?php echo htmlspecialchars($media['url']); ?>" target="_blank">
                                <?php echo htmlspecialchars($media['url']); ?>
                            </a>
                        </p>
                        
                        <?php if (!empty($media['description'])): ?>
                            <p><small><?php echo nl2br(htmlspecialchars($media['description'])); ?></small></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No featured media available.</p>
            <?php endif; ?>
        </section>

        <!-- Team Section -->
        <section id="team">
            <h2>Station Members</h2>
            <?php if (!empty($members)): ?>
                <div class="grid">
                <?php foreach ($members as $member): ?>
                    <div class="card" style="text-align: center;">
                        <h3><?php echo htmlspecialchars($member['name']); ?></h3>
                        <p><strong><?php echo htmlspecialchars($member['position']); ?></strong></p>
                        <?php if (!empty($member['program'])): ?>
                            <p>Host: <?php echo htmlspecialchars($member['program']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No team members available.</p>
            <?php endif; ?>
        </section>
    </div>
    
    <footer style="background: #003366; color: white; padding: 20px; text-align: center; margin-top: 20px;">
        <div class="container">
            &copy; <?php echo date('Y'); ?> K5 News FM - Backend Data
        </div>
    </footer>
</body>
</html>
