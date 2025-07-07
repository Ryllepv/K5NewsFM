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
    <title>News Portal</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f6f6f6; }
        header, nav, main, footer { max-width: 900px; margin: auto; }
        header { background: #003366; color: #fff; padding: 1em 2em; }
        nav { background: #eaeaea; padding: 1em 2em; margin-bottom: 1em; }
        nav a { margin-right: 1em; text-decoration: none; color: #003366; font-weight: bold; }
        main { background: #fff; padding: 2em; border-radius: 8px; }
        h1, h2 { color: #003366; }
        .news-list li { margin-bottom: 1em; }
        .live-updates { background: #fffbe6; border: 1px solid #ffe58f; padding: 1em; border-radius: 6px; margin-bottom: 1em; }
        .tags-list { margin-bottom: 1em; }
        .tags-list li { display: inline; margin-right: 0.5em; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 2em; }
        th, td { border: 1px solid #bbb; padding: 8px 12px; text-align: left; }
        th { background: #e6f0fa; }
        tr:nth-child(even) { background: #f9f9f9; }
        .search-bar { margin-bottom: 1em; }
        .admin-link { float: right; }
        .weather-block, .traffic-block { background: #e6f7ff; border: 1px solid #91d5ff; padding: 1em; border-radius: 6px; margin-bottom: 1em; }
        .about-section { background: #f0f8ff; border: 1px solid #b3d8fd; border-radius: 8px; padding: 2em; margin-top: 2em; }
        .members-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2em; justify-items: center; padding: 1em 0; }
        .member-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 1.5em;
            width: 100%;
            max-width: 280px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .member-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #91d5ff;
        }
        .member-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #003366, #91d5ff);
        }
        .member-card img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 1em;
            border: 3px solid #91d5ff;
            transition: transform 0.3s ease;
            background: #f0f8ff;
        }
        .member-card:hover img {
            transform: scale(1.05);
        }
        .member-card img[src=""], .member-card img:not([src]), .member-card img[src="#"] {
            background: linear-gradient(135deg, #91d5ff, #003366);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .member-card img[src=""]:before, .member-card img:not([src]):before, .member-card img[src="#"]:before {
            content: "👤";
            font-size: 3em;
            color: white;
        }
        .member-name {
            font-weight: bold;
            font-size: 1.2em;
            margin-bottom: 0.5em;
            color: #003366;
            line-height: 1.3;
        }
        .member-position {
            color: #1890ff;
            font-size: 1em;
            margin-bottom: 0.5em;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .member-program {
            color: #666;
            font-size: 0.95em;
            font-style: italic;
            background: #f8f9fa;
            padding: 0.5em;
            border-radius: 6px;
            margin-top: 0.5em;
        }
        .events-section, .holidays-section {
            background: #f0f8ff;
            border: 1px solid #b3d8fd;
            border-radius: 8px;
            padding: 2em;
            margin-top: 2em;
        }
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5em;
            margin-top: 1.5em;
        }
        .event-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .event-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .event-placeholder {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #91d5ff, #003366);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3em;
            color: white;
        }
        .event-content {
            padding: 1.5em;
        }
        .event-title {
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 0.5em;
            color: #003366;
        }
        .event-date, .event-location {
            color: #666;
            margin-bottom: 0.5em;
            font-size: 0.95em;
        }
        .event-description {
            color: #555;
            font-size: 0.9em;
            line-height: 1.4;
        }
        .holidays-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1em;
            margin-top: 1.5em;
        }
        .holiday-item {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 1em;
            border-left: 4px solid #91d5ff;
        }
        .holiday-name {
            font-weight: bold;
            color: #003366;
            margin-bottom: 0.3em;
        }
        .holiday-date {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 0.3em;
        }
        .holiday-type {
            display: inline-block;
            padding: 0.2em 0.6em;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 500;
        }
        .holiday-regular { background: #f6ffed; color: #52c41a; }
        .holiday-special_non_working { background: #fff7e6; color: #fa8c16; }
        .holiday-special_working { background: #f0f5ff; color: #1890ff; }
        .schedule-container {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .schedule-table th {
            background: #003366;
            color: #fff;
            padding: 1em;
            text-align: left;
            font-weight: bold;
        }
        .schedule-table td {
            padding: 0.8em 1em;
            border-bottom: 1px solid #f0f0f0;
        }
        .schedule-table tr:hover {
            background: #f8f9fa;
        }
        .program-name {
            font-weight: bold;
            color: #003366;
        }
        .program-days {
            color: #1890ff;
            font-weight: 500;
            background: #f0f8ff;
            border-radius: 4px;
            padding: 0.3em 0.6em;
            display: inline-block;
        }
        .program-time {
            color: #666;
            font-family: monospace;
        }
        .no-schedule {
            text-align: center;
            padding: 2em;
            color: #666;
            font-style: italic;
        }
        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5em;
            margin-top: 1em;
        }
        .news-card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .news-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .news-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
        }
        .news-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        .news-card:hover .news-image img {
            transform: scale(1.05);
        }
        .news-content {
            padding: 1.5em;
        }
        .news-content h3 {
            margin: 0 0 1em 0;
            color: #003366;
            font-size: 1.2em;
            line-height: 1.3;
        }
        .news-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1em;
            margin-bottom: 1em;
            font-size: 0.9em;
            color: #666;
        }
        .news-date {
            color: #1890ff;
        }
        .news-tags {
            color: #52c41a;
        }
        .news-excerpt {
            color: #666;
            line-height: 1.5;
            margin-bottom: 1em;
        }
        .read-more {
            color: #003366;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 0.5em;
        }
        .read-more:hover {
            color: #1890ff;
        }
    </style>
</head>
<body>
<header>
    <span style="font-size:1.5em;">📰 News Portal</span>
    <a href="admin/login.php" class="admin-link" style="color:#fff;">Admin Login</a>
</header>
<nav>
    <a href="#news">Latest News</a>
    <a href="#live">Live Updates</a>
    <a href="#events">Events</a>
    <a href="#holidays">Holidays</a>
    <a href="#tags">Tags</a>
    <a href="#schedule">Program Schedule</a>
    <a href="#about">About</a>
</nav>
<main>
    <section class="weather-block">
        <h2>Current Conditions</h2>
        <strong><?php echo htmlspecialchars($city); ?></strong><br>
        <?php if ($weather): ?>
            <span style="font-size:2em;"><?php echo weather_icon($weather['weathercode'])[0]; ?></span>
            <span style="font-size:2em;"><?php echo round($weather['temp']); ?>°C</span>
            <br>
            <?php echo weather_icon($weather['weathercode'])[1]; ?><br>
            Humidity: <?php echo is_numeric($weather['humidity']) ? htmlspecialchars($weather['humidity']) . '%' : 'N/A'; ?><br>
            Wind Speed: <?php echo htmlspecialchars($weather['windspeed']); ?> km/h<br>
            Pressure: <?php echo is_numeric($weather['pressure']) ? htmlspecialchars($weather['pressure']) . ' hPa' : 'N/A'; ?><br>
            Visibility: 
            <?php
                if (isset($weather['visibility']) && is_numeric($weather['visibility'])) {
                    echo round($weather['visibility'] / 1000, 1) . " km";
                } else {
                    echo "N/A";
                }
            ?><br>
            Sunrise: <?php echo isset($forecast[0]['sunrise']) ? date('g:i A', strtotime($forecast[0]['sunrise'])) : 'N/A'; ?><br>
            Sunset: <?php echo isset($forecast[0]['sunset']) ? date('g:i A', strtotime($forecast[0]['sunset'])) : 'N/A'; ?><br>
        <?php else: ?>
            <div style="color:#c00; margin-bottom: 1em;">
                <strong>⚠️ Weather Service Unavailable</strong><br>
                <small><?php echo htmlspecialchars($weatherError); ?></small>
            </div>
            <div style="background: #f0f8ff; padding: 1em; border-radius: 6px; border-left: 4px solid #91d5ff;">
                <strong>📍 Olongapo City, Zambales</strong><br>
                <span style="font-size: 1.5em;">🌤️ Partly Cloudy</span><br>
                <span style="font-size: 1.2em;">~28°C</span><br>
                <small style="color: #666;">
                    Typical weather for this region<br>
                    Humidity: ~75% | Wind: ~15 km/h<br>
                    <em>Live weather data temporarily unavailable</em>
                </small>
            </div>
        <?php endif; ?>
    </section>

    <section class="weather-block">
        <h2>5-Day Forecast</h2>
        <?php if ($forecast && count($forecast) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Max Temp (°C)</th>
                    <th>Min Temp (°C)</th>
                    <th>Weather</th>
                    <th>Sunrise</th>
                    <th>Sunset</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($forecast as $day): ?>
                <tr>
                    <td><?php echo date('D, M j', strtotime($day['date'])); ?></td>
                    <td><?php echo htmlspecialchars($day['max']); ?></td>
                    <td><?php echo htmlspecialchars($day['min']); ?></td>
                    <td><?php echo weather_icon($day['weathercode'])[0]; ?> <?php echo weather_icon($day['weathercode'])[1]; ?></td>
                    <td><?php echo date('g:i A', strtotime($day['sunrise'])); ?></td>
                    <td><?php echo date('g:i A', strtotime($day['sunset'])); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div style="color:#c00; margin-bottom: 1em;">
                <small>Live forecast data temporarily unavailable</small>
            </div>
            <div style="background: #f0f8ff; padding: 1em; border-radius: 6px;">
                <strong>📅 Typical 5-Day Outlook for Olongapo City</strong><br><br>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1em; text-align: center;">
                    <div style="background: white; padding: 0.5em; border-radius: 4px;">
                        <strong>Today</strong><br>
                        🌤️<br>
                        28°/24°C
                    </div>
                    <div style="background: white; padding: 0.5em; border-radius: 4px;">
                        <strong>Tomorrow</strong><br>
                        ⛅<br>
                        29°/25°C
                    </div>
                    <div style="background: white; padding: 0.5em; border-radius: 4px;">
                        <strong>Day 3</strong><br>
                        🌦️<br>
                        27°/23°C
                    </div>
                    <div style="background: white; padding: 0.5em; border-radius: 4px;">
                        <strong>Day 4</strong><br>
                        ☀️<br>
                        30°/26°C
                    </div>
                    <div style="background: white; padding: 0.5em; border-radius: 4px;">
                        <strong>Day 5</strong><br>
                        🌤️<br>
                        28°/24°C
                    </div>
                </div>
                <br>
                <small style="color: #666;"><em>Typical weather patterns for this region</em></small>
            </div>
        <?php endif; ?>
    </section>

    <section id="news">
        <h1>Latest News</h1>
        <form class="search-bar" method="get" action="">
            <input type="text" name="q" placeholder="Search articles..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
            <button type="submit">Search</button>
        </form>
        <div class="news-grid">
        <?php foreach ($articles as $article): ?>
            <article class="news-card">
                <?php if ($article['image_path'] && file_exists($article['image_path'])): ?>
                    <div class="news-image">
                        <img src="<?php echo htmlspecialchars($article['image_path']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" loading="lazy">
                    </div>
                <?php endif; ?>
                <div class="news-content">
                    <h3><?php echo htmlspecialchars($article['title']); ?></h3>
                    <div class="news-meta">
                        <span class="news-date">📅 <?php echo date('M j, Y', strtotime($article['created_at'])); ?></span>
                        <?php if ($article['tags']): ?>
                            <span class="news-tags">🏷️ <?php echo htmlspecialchars($article['tags']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="news-excerpt">
                        <?php
                        $excerpt = strip_tags($article['content']);
                        echo htmlspecialchars(strlen($excerpt) > 150 ? substr($excerpt, 0, 150) . '...' : $excerpt);
                        ?>
                    </div>
                    <a href="article.php?id=<?php echo $article['id']; ?>" class="read-more">Read more →</a>
                </div>
            </article>
        <?php endforeach; ?>
        </div>
    </section>

    <section id="live">
        <h2>Live Updates</h2>
        <div class="live-updates">
            <ul>
            <?php foreach ($liveUpdates as $update): ?>
                <li><?php echo htmlspecialchars($update['message']); ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="events-section" id="events">
        <h2>📅 Upcoming Events</h2>
        <?php if (!empty($events)): ?>
            <div class="events-grid">
                <?php foreach ($events as $event): ?>
                    <div class="event-card">
                        <?php if ($event['image_path'] && file_exists($event['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($event['image_path']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                        <?php else: ?>
                            <div class="event-placeholder">📅</div>
                        <?php endif; ?>
                        <div class="event-content">
                            <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                            <div class="event-date">
                                📅 <?php echo date('M j, Y', strtotime($event['event_date'])); ?>
                                <?php if ($event['event_time']): ?>
                                    at <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                                <?php endif; ?>
                            </div>
                            <?php if ($event['location']): ?>
                                <div class="event-location">📍 <?php echo htmlspecialchars($event['location']); ?></div>
                            <?php endif; ?>
                            <?php if ($event['description']): ?>
                                <div class="event-description">
                                    <?php
                                    $preview = substr(strip_tags($event['description']), 0, 120);
                                    echo htmlspecialchars($preview);
                                    if (strlen($event['description']) > 120) echo '...';
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #666; font-style: italic;">No upcoming events at the moment. Check back soon!</p>
        <?php endif; ?>
    </section>

    <section class="holidays-section" id="holidays">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1em; flex-wrap: wrap; gap: 1em;">
            <h2 style="margin: 0;">🇵🇭 Philippine Holidays - <?php echo date('F Y'); ?></h2>
            <div style="display: flex; gap: 0.5em; flex-wrap: wrap;">
                <a href="holidays_calendar.php" style="background: #003366; color: #fff; padding: 0.5em 1em; border-radius: 4px; text-decoration: none; font-size: 0.9em;">📅 Calendar View</a>
                <a href="holidays_list.php" style="background: #1890ff; color: #fff; padding: 0.5em 1em; border-radius: 4px; text-decoration: none; font-size: 0.9em;">📋 All Holidays</a>
            </div>
        </div>

        <?php if (!empty($holidays)): ?>
            <div class="holidays-list">
                <?php foreach ($holidays as $holiday): ?>
                    <div class="holiday-item">
                        <div class="holiday-name"><?php echo htmlspecialchars($holiday['name']); ?></div>
                        <div class="holiday-date">📅 <?php echo date('M j, Y', strtotime($holiday['date'])); ?></div>
                        <div class="holiday-type holiday-<?php echo $holiday['type']; ?>">
                            <?php
                            switch($holiday['type']) {
                                case 'regular': echo 'Regular Holiday'; break;
                                case 'special_non_working': echo 'Special Non-Working'; break;
                                case 'special_working': echo 'Special Working'; break;
                                default: echo ucfirst($holiday['type']);
                            }
                            ?>
                        </div>
                        <?php if ($holiday['description']): ?>
                            <div style="margin-top: 0.5em; font-size: 0.9em; color: #666;">
                                <?php echo htmlspecialchars($holiday['description']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 1.5em;">
                <a href="holidays_calendar.php?year=<?php echo date('Y'); ?>&month=<?php echo date('n'); ?>" style="background: #f0f8ff; color: #003366; padding: 0.8em 1.5em; border-radius: 6px; text-decoration: none; border: 1px solid #b3d8fd; display: inline-block;">
                    📅 View Full Calendar with All Holidays
                </a>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #666; font-style: italic; margin-bottom: 1.5em;">No holidays this month.</p>
            <div style="text-align: center;">
                <a href="holidays_calendar.php" style="background: #003366; color: #fff; padding: 0.8em 1.5em; border-radius: 6px; text-decoration: none; display: inline-block;">
                    📅 View Holiday Calendar
                </a>
            </div>
        <?php endif; ?>
    </section>

    <section id="tags">
        <h2>Tags</h2>
        <ul class="tags-list">
        <?php foreach ($tags as $tag): ?>
            <li><a href="?tag=<?php echo urlencode($tag['name']); ?>"><?php echo htmlspecialchars($tag['name']); ?></a></li>
        <?php endforeach; ?>
        </ul>
    </section>

    <section id="schedule">
        <h2>📻 Program Schedule</h2>
        <div class="schedule-container">
            <table class="schedule-table">
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
                        <td class="program-name"><?php echo htmlspecialchars($program['program_name']); ?></td>
                        <td class="program-days"><?php echo formatDayRange($program['days']); ?></td>
                        <td class="program-time"><?php echo htmlspecialchars($program['time_slot']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (empty($groupedPrograms)): ?>
                <div class="no-schedule">
                    <p>📻 Program schedule will be updated soon!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section id="featured-media" class="weather-block">
        <h2>Featured Live & Video</h2>
        <div style="display: flex; flex-wrap: wrap; gap: 1em;">
        <?php foreach ($featuredMedia as $media): ?>
            <div style="flex: 1 1 300px; min-width: 300px; max-width: 400px; position:relative;">
                <?php
                // Simple YouTube and Facebook embed preview
                if (preg_match('/youtu\.be\/([^\?&]+)/', $media['url'], $yt) || preg_match('/youtube\.com.*v=([^\?&]+)/', $media['url'], $yt)) {
                    $ytId = $yt[1];
                    echo '<iframe width="100%" height="215" src="https://www.youtube.com/embed/' . htmlspecialchars($ytId) . '" frameborder="0" allowfullscreen></iframe>';
                } elseif (strpos($media['url'], 'facebook.com') !== false) {
                    echo '<div class="fb-video" data-href="' . htmlspecialchars($media['url']) . '" data-width="400" data-show-text="false"></div>';
                } elseif (strpos($media['url'], 'tiktok.com') !== false) {
                    echo '<blockquote class="tiktok-embed" cite="' . htmlspecialchars($media['url']) . '" data-video-id="" style="width: 100%;"></blockquote>';
                } else {
                    // Fallback: just show the link
                    echo '<a href="' . htmlspecialchars($media['url']) . '" target="_blank">' . htmlspecialchars($media['url']) . '</a>';
                }
                ?>
                <?php if (!empty($media['title'])): ?>
                    <div style="font-weight:bold; margin-top:0.5em;">
                        <?php echo htmlspecialchars($media['title']); ?>
                        <?php if (!empty($media['description'])): ?>
                            <a href="javascript:void(0);" class="see-more" data-id="desc-<?php echo $media['id']; ?>">See more</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($media['description'])): ?>
                    <div id="desc-<?php echo $media['id']; ?>" class="media-desc" style="display:none; font-size:0.95em; color:#555; margin-top:0.5em;">
                        <?php echo nl2br(htmlspecialchars($media['description'])); ?>
                        <br>
                        <a href="javascript:void(0);" class="hide-desc" data-id="desc-<?php echo $media['id']; ?>">Hide</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
        <?php if (empty($featuredMedia)): ?>
            <p style="color:#888;">No featured media yet. Admin can add links in the dashboard.</p>
        <?php endif; ?>
    </section>

    <section class="about-section" id="about">
        <h2>About the Station</h2>
        <div class="members-grid">
            <?php foreach ($members as $member): ?>
                <div class="member-card">
                    <img src="<?php echo htmlspecialchars($member['image_url']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                    <div class="member-name"><?php echo htmlspecialchars($member['name']); ?></div>
                    <div class="member-position"><?php echo htmlspecialchars($member['position']); ?></div>
                    <?php if (!empty($member['program'])): ?>
                        <div class="member-program">Host: <?php echo htmlspecialchars($member['program']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<footer style="text-align:center; color:#888; padding:1em 0;">
    &copy; <?php echo date('Y'); ?> News Portal
</footer>

<!-- Facebook SDK for video embeds (optional, only if you use Facebook videos) -->
<div id="fb-root"></div>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v19.0"></script>
<!-- TikTok embed script (optional, only if you use TikTok videos) -->
<script async src="https://www.tiktok.com/embed.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.see-more').forEach(function(link) {
        link.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            document.getElementById(id).style.display = 'block';
            this.style.display = 'none';
        });
    });
    document.querySelectorAll('.hide-desc').forEach(function(link) {
        link.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            document.getElementById(id).style.display = 'none';
            // Show the corresponding see more link again
            var seeMore = document.querySelector('.see-more[data-id="' + id + '"]');
            if (seeMore) seeMore.style.display = 'inline';
        });
    });

    // Handle fallback images for member cards
    document.querySelectorAll('.member-card img').forEach(function(img) {
        img.addEventListener('error', function() {
            this.style.display = 'none';
            var fallback = document.createElement('div');
            fallback.className = 'member-avatar-fallback';
            fallback.style.cssText = `
                width: 120px;
                height: 120px;
                border-radius: 50%;
                background: linear-gradient(135deg, #91d5ff, #003366);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 3em;
                color: white;
                margin: 0 auto 1em auto;
                border: 3px solid #91d5ff;
                transition: transform 0.3s ease;
            `;
            fallback.innerHTML = '👤';
            this.parentNode.insertBefore(fallback, this);
        });

        // Check if image src is empty or placeholder
        if (!this.src || this.src === '' || this.src.includes('#') || this.src === window.location.href) {
            this.dispatchEvent(new Event('error'));
        }
    });
});
</script>
</body>
</html>