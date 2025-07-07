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

// Helper function for image paths
function get_image_path($path, $type = 'general') {
    if (empty($path) || !file_exists($path)) {
        switch ($type) {
            case 'article':
                return 'assets/img/default-news.jpg';
            case 'event':
                return 'assets/img/default-event.jpg';
            case 'member':
                return 'assets/img/default-profile.jpg';
            default:
                return 'assets/img/placeholder.jpg';
        }
    }
    return $path;
}

// Function to create excerpts
function get_excerpt($content, $length = 150) {
    $excerpt = strip_tags($content);
    if (strlen($excerpt) > $length) {
        return substr($excerpt, 0, $length) . '...';
    }
    return $excerpt;
}

// Function to embed media
function embed_media($url) {
    if (preg_match('/youtu\.be\/([^\?&]+)/', $url, $yt) || preg_match('/youtube\.com.*v=([^\?&]+)/', $url, $yt)) {
        $ytId = $yt[1];
        return '<iframe width="100%" height="215" src="https://www.youtube.com/embed/' . htmlspecialchars($ytId) . '" frameborder="0" allowfullscreen></iframe>';
    } elseif (strpos($url, 'facebook.com') !== false) {
        return '<div class="fb-video" data-href="' . htmlspecialchars($url) . '" data-width="400" data-show-text="false"></div>';
    } elseif (strpos($url, 'tiktok.com') !== false) {
        return '<blockquote class="tiktok-embed" cite="' . htmlspecialchars($url) . '" data-video-id="" style="width: 100%;"></blockquote>';
    } else {
        return '<a href="' . htmlspecialchars($url) . '" target="_blank">' . htmlspecialchars($url) . '</a>';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K5 News FM - Backend Check</title>
    <style>
        /* Minimal CSS for readability */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f0f0f0;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        header {
            background-color: #003366;
            color: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        h1, h2, h3 {
            color: #003366;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            text-align: left;
            padding: 8px 12px;
            border: 1px solid #ddd;
        }
        
        th {
            background-color: #003366;
            color: white;
        }
        
        tr:nth-child(even) {
            background-color: #f5f5f5;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        pre {
            background-color: #f8f9fa;
            border: 1px solid #eee;
            border-radius: 3px;
            padding: 10px;
            overflow: auto;
        }
        
        .box {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>K5 News FM - Backend Check</h1>
            <p>This page verifies that the backend data retrieval is working properly.</p>
        </header>
        
        <section>
            <h2>Database Connection</h2>
            <?php if (isset($pdo)): ?>
                <div class="status success">✅ Connection successful</div>
            <?php else: ?>
                <div class="status error">❌ Connection failed</div>
            <?php endif; ?>
        </section>
        
        <section>
            <h2>News Articles</h2>
            <?php if (!empty($articles)): ?>
                <div class="status success">✅ Retrieved <?php echo count($articles); ?> articles</div>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Tags</th>
                        <th>Image</th>
                    </tr>
                    <?php foreach (array_slice($articles, 0, 5) as $article): ?>
                        <tr>
                            <td><?php echo $article['id']; ?></td>
                            <td><?php echo htmlspecialchars($article['title']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($article['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($article['tags'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if (!empty($article['image_path']) && file_exists($article['image_path'])): ?>
                                    ✅ Image exists
                                <?php else: ?>
                                    ❌ No image or missing
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php if (count($articles) > 5): ?>
                    <p>...and <?php echo count($articles) - 5; ?> more articles</p>
                <?php endif; ?>
            <?php else: ?>
                <div class="status error">❌ No articles found</div>
            <?php endif; ?>
        </section>
        
        <section>
            <h2>Live Updates</h2>
            <?php if (!empty($liveUpdates)): ?>
                <div class="status success">✅ Retrieved <?php echo count($liveUpdates); ?> live updates</div>
                <ul>
                    <?php foreach ($liveUpdates as $update): ?>
                        <li>
                            <strong><?php echo date('Y-m-d H:i', strtotime($update['created_at'])); ?>:</strong>
                            <?php echo htmlspecialchars($update['message']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="status warning">⚠️ No live updates found</div>
            <?php endif; ?>
        </section>
        
        <section>
            <h2>Weather API</h2>
            <?php if ($weather): ?>
                <div class="status success">✅ Weather data retrieved successfully</div>
                <div class="box">
                    <h3><?php echo htmlspecialchars($city); ?></h3>
                    <p>
                        <?php echo weather_icon($weather['weathercode'])[0]; ?> 
                        <?php echo round($weather['temp']); ?>°C - 
                        <?php echo weather_icon($weather['weathercode'])[1]; ?>
                    </p>
                    <p>Humidity: <?php echo is_numeric($weather['humidity']) ? htmlspecialchars($weather['humidity']) . '%' : 'N/A'; ?></p>
                    <p>Wind Speed: <?php echo htmlspecialchars($weather['windspeed']); ?> km/h</p>
                </div>
                
                <?php if (!empty($forecast)): ?>
                    <h3>Forecast</h3>
                    <table>
                        <tr>
                            <th>Date</th>
                            <th>Max</th>
                            <th>Min</th>
                            <th>Weather</th>
                        </tr>
                        <?php foreach ($forecast as $day): ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($day['date'])); ?></td>
                                <td><?php echo $day['max']; ?>°C</td>
                                <td><?php echo $day['min']; ?>°C</td>
                                <td>
                                    <?php echo weather_icon($day['weathercode'])[0]; ?> 
                                    <?php echo weather_icon($day['weathercode'])[1]; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            <?php else: ?>
                <div class="status error">❌ Failed to retrieve weather data</div>
                <p><?php echo htmlspecialchars($weatherError); ?></p>
            <?php endif; ?>
        </section>
        
        <section>
            <h2>Program Schedule</h2>
            <?php if (!empty($groupedPrograms)): ?>
                <div class="status success">✅ Retrieved <?php echo count($groupedPrograms); ?> program schedules</div>
                <table>
                    <tr>
                        <th>Program</th>
                        <th>Days</th>
                        <th>Time</th>
                    </tr>
                    <?php foreach (array_slice($groupedPrograms, 0, 5) as $program): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($program['program_name']); ?></td>
                            <td><?php echo formatDayRange($program['days']); ?></td>
                            <td><?php echo htmlspecialchars($program['time_slot']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php if (count($groupedPrograms) > 5): ?>
                    <p>...and <?php echo count($groupedPrograms) - 5; ?> more programs</p>
                <?php endif; ?>
            <?php else: ?>
                <div class="status warning">⚠️ No program schedules found</div>
            <?php endif; ?>
        </section>
        
        <section>
            <h2>Station Members</h2>
            <?php if (!empty($members)): ?>
                <div class="status success">✅ Retrieved <?php echo count($members); ?> team members</div>
                <table>
                    <tr>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Program</th>
                        <th>Image</th>
                    </tr>
                    <?php foreach (array_slice($members, 0, 5) as $member): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['name']); ?></td>
                            <td><?php echo htmlspecialchars($member['position']); ?></td>
                            <td><?php echo htmlspecialchars($member['program'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if (!empty($member['image_url'])): ?>
                                    Image URL exists
                                <?php else: ?>
                                    No image
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php if (count($members) > 5): ?>
                    <p>...and <?php echo count($members) - 5; ?> more team members</p>
                <?php endif; ?>
            <?php else: ?>
                <div class="status warning">⚠️ No team members found</div>
            <?php endif; ?>
        </section>
        
        <section>
            <h2>Events</h2>
            <?php if (!empty($events)): ?>
                <div class="status success">✅ Retrieved <?php echo count($events); ?> upcoming events</div>
                <table>
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Location</th>
                    </tr>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['title']); ?></td>
                            <td>
                                <?php echo date('Y-m-d', strtotime($event['event_date'])); ?>
                                <?php if (!empty($event['event_time'])): ?>
                                    at <?php echo date('H:i', strtotime($event['event_time'])); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($event['location'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <div class="status warning">⚠️ No upcoming events found</div>
            <?php endif; ?>
        </section>
        
        <section>
            <h2>Holidays</h2>
            <?php if (!empty($holidays)): ?>
                <div class="status success">✅ Retrieved <?php echo count($holidays); ?> holidays for <?php echo date('F Y'); ?></div>
                <table>
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Type</th>
                    </tr>
                    <?php foreach ($holidays as $holiday): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($holiday['name']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($holiday['date'])); ?></td>
                            <td>
                                <?php
                                switch($holiday['type']) {
                                    case 'regular': echo 'Regular Holiday'; break;
                                    case 'special_non_working': echo 'Special Non-Working'; break;
                                    case 'special_working': echo 'Special Working'; break;
                                    default: echo ucfirst($holiday['type']);
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <div class="status warning">⚠️ No holidays found for current month</div>
            <?php endif; ?>
        </section>
        
        <section>
            <h2>Tags</h2>
            <?php if (!empty($tags)): ?>
                <div class="status success">✅ Retrieved <?php echo count($tags); ?> tags</div>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <?php foreach ($tags as $tag): ?>
                        <span style="background: #e6f7ff; color: #003366; padding: 3px 10px; border-radius: 3px;">
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="status warning">⚠️ No tags found</div>
            <?php endif; ?>
        </section>
        
        <section>
            <h2>Featured Media</h2>
            <?php if (!empty($featuredMedia)): ?>
                <div class="status success">✅ Retrieved <?php echo count($featuredMedia); ?> media items</div>
                <table>
                    <tr>
                        <th>Title</th>
                        <th>URL</th>
                        <th>Type</th>
                    </tr>
                    <?php foreach ($featuredMedia as $media): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($media['title'] ?? 'Untitled'); ?></td>
                            <td><?php echo htmlspecialchars($media['url']); ?></td>
                            <td>
                                <?php
                                if (preg_match('/youtu\.be\/|youtube\.com/', $media['url'])) {
                                    echo 'YouTube';
                                } elseif (strpos($media['url'], 'facebook.com') !== false) {
                                    echo 'Facebook';
                                } elseif (strpos($media['url'], 'tiktok.com') !== false) {
                                    echo 'TikTok';
                                } else {
                                    echo 'Other';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <div class="status warning">⚠️ No featured media found</div>
            <?php endif; ?>
        </section>
        
        <section>
            <h2>Helper Functions</h2>
            <div class="status info">ℹ️ Available helper functions</div>
            <ul>
                <li><strong>get_image_path($path, $type)</strong> - Handles image paths with fallbacks</li>
                <li><strong>get_excerpt($content, $length)</strong> - Creates content excerpts</li>
                <li><strong>formatDayRange($days)</strong> - Formats array of days into readable ranges</li>
                <li><strong>weather_icon($code)</strong> - Converts weather codes to emoji and descriptions</li>
                <li><strong>embed_media($url)</strong> - Creates embeds for various media URLs</li>
            </ul>
        </section>
    </div>
    
    <footer style="text-align: center; margin-top: 20px; padding: 10px; background: #003366; color: white;">
        K5 News FM Backend Check - <?php echo date('Y-m-d H:i:s'); ?>
    </footer>
</body>
</html>
