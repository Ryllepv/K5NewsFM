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

// Fetch radio station members for About section
$stmt = $pdo->query("SELECT * FROM station_members ORDER BY id ASC");
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch program schedule, ordered by start time
$stmt = $pdo->query("SELECT * FROM program_schedule ORDER BY STR_TO_DATE(SUBSTRING_INDEX(time_slot, ' - ', 1), '%l:%i %p')");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group programs by name and time slot to aggregate days
$groupedPrograms = [];
// Create a map of programs to member details for easy lookup
$memberProgramMap = [];
foreach ($members as $member) {
    if (!empty($member['program'])) {
        // Allow for multiple hosts per program
        $memberProgramMap[$member['program']][] = $member;
    }
}

foreach ($schedules as $schedule) {
    $key = $schedule['program_name'] . '|' . $schedule['time_slot'];
    if (!isset($groupedPrograms[$key])) {
        $programName = $schedule['program_name'];
        $programMembers = isset($memberProgramMap[$programName]) ? $memberProgramMap[$programName] : [];
        
        // Aggregate anchors and get the first image_url found
        $anchors = [];
        $imageUrl = '';
        foreach ($programMembers as $pMember) {
            $anchors[] = $pMember['name'];
            if (empty($imageUrl) && !empty($pMember['image_url'])) {
                $imageUrl = $pMember['image_url'];
            }
        }

        $groupedPrograms[$key] = [
            'program_name' => $programName,
            'time_slot'    => $schedule['time_slot'],
            'anchor'       => !empty($anchors) ? implode(' & ', $anchors) : 'K5 Host',
            'description'  => $schedule['description'] ?? 'Tune in for the latest updates.',
            'image_url'    => $imageUrl,
            'days'         => [],
        ];
    }
    $groupedPrograms[$key]['days'][] = $schedule['day_of_week'];
}

// Function to check if a program is currently live
function isProgramLive($time_slot, $days) {
    date_default_timezone_set('Asia/Manila');
    $now = new DateTime();
    $currentDay = $now->format('l');
    $currentTime = $now->format('H:i');

    if (!in_array($currentDay, $days)) {
        return false;
    }

    list($start, $end) = explode(' - ', $time_slot);
    try {
        $startTime = (new DateTime($start))->format('H:i');
        $endTime = (new DateTime($end))->format('H:i');
        if ($endTime < $startTime) { // Overnight
            return ($currentTime >= $startTime || $currentTime < $endTime);
        }
        return ($currentTime >= $startTime && $currentTime < $endTime);
    } catch (Exception $e) {
        return false;
    }
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
    <meta name="description" content="K5 News Radio 88.7 FM - Your trusted source for breaking news, local updates, and community coverage in Olongapo. Listen live 24/7.">
    <meta name="keywords" content="K5 News Radio, 88.7 FM, Olongapo news, breaking news, local radio, Philippines news, live radio">
    <meta name="author" content="K5 News Radio">

    <title>K5 News Radio 88.7 FM - Breaking News & Local Coverage | Olongapo</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Roboto+Slab:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/k5-news-radio.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/K5 LOGO K5 LANG.png">
</head>

<body>


    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <img src="assets/K5 LOGO OLONGAPO.png" alt="K5 News Radio" height="50" class="me-2">
                <div class="brand-text">
                    <span class="brand-name">K5 NEWS RADIO</span>
                    <span class="brand-tagline">88.7 FM • Olongapo's Voice</span>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#articles">Articles</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#listen">Live</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#programs">Programs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#weather">Weather</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#community">Community</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="hero-background">
            <div class="hero-overlay"></div>
        </div>
        
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-8">
                    <div class="hero-content">
                        <div class="hero-badge">
                            <i class="bi bi-broadcast"></i>
                            <span>LIVE ON 88.7 FM • OLONGAPO</span>
                        </div>
                        
                        <h1 class="hero-title">
                            Your Trusted Source for
                            <span class="text-danger">Breaking News</span>
                        </h1>
                        
                        <p class="hero-description">
                            Broadcasting on 88.7 FM, K5 News Radio delivers real-time news coverage, weather updates,
                            and community stories that matter to Olongapo and surrounding areas. Stay informed, stay connected.
                        </p>
                        
                        <div class="hero-stats">
                            <div class="stat-item">
                                <div class="stat-number">24/7</div>
                                <div class="stat-label">Live Coverage</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">50K+</div>
                                <div class="stat-label">Daily Listeners</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">15+</div>
                                <div class="stat-label">Years Serving</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Articles Section -->
    <section id="articles" class="articles-section py-5">
        <div class="container">
            
            <div class="section-header text-center mb-5">
                <h2 class="section-title">
                    <i class="bi bi-newspaper text-primary me-2"></i>
                    Latest Articles
                </h2>
                <p class="section-subtitle">In-depth stories and featured content from our newsroom</p>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="articles-container">
                        <div class="articles-scroll-wrapper">
                            <div class="articles-grid">
                        <?php if (empty($articles)): ?>
                            <p>No articles found.</p>
                        <?php else: ?>
                            <?php foreach ($articles as $index => $article): ?>
                                <article class="article-card <?php echo $index === 0 ? 'featured-article' : ''; ?>">
                                    <div class="article-image">
                                        <img src="<?php echo !empty($article['image_path']) && file_exists($article['image_path']) ? htmlspecialchars($article['image_path']) : 'assets/placeholder.png'; ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                                        <?php
                                        $tags_array = !empty($article['tags']) ? explode(',', $article['tags']) : [];
                                        if (!empty($tags_array)):
                                            $first_tag = strtolower(trim($tags_array[0]));
                                            $badge_class = 'default'; // Fallback class
                                            if (in_array($first_tag, ['infrastructure', 'weather', 'business', 'community', 'sports', 'education'])) {
                                                $badge_class = $first_tag;
                                            }
                                        ?>
                                            <div class="article-category-badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars(ucfirst($first_tag)); ?></div>
                                        <?php endif; ?>
                                        <div class="reading-time">
                                            <?php echo round(str_word_count($article['content']) / 200); ?> min read
                                        </div>
                                    </div>
                                    <div class="article-content">
                                        <h3><a href="article.php?id=<?php echo $article['id']; ?>" style="text-decoration:none; color:inherit;"><?php echo htmlspecialchars($article['title']); ?></a></h3>
                                        <p class="article-excerpt">
                                            <?php
                                            $excerpt = strip_tags($article['content']);
                                            echo htmlspecialchars(strlen($excerpt) > 150 ? substr($excerpt, 0, 150) . '...' : $excerpt);
                                            ?>
                                        </p>
                                        <div class="article-meta">
                                            <div class="author">
                                                <!-- Author info can be added if available in the database -->
                                                <img src="assets/K5 LOGO K5 LANG.png" alt="Author" class="author-avatar">
                                                <div class="author-info">
                                                    <span class="author-name">K5 News Team</span>
                                                    <span class="publish-date"><?php echo date('F j, Y', strtotime($article['created_at'])); ?></span>
                                                </div>
                                            </div>
                                            <div class="article-stats">
                                                <a href="article.php?id=<?php echo $article['id']; ?>" class="read-more-link">Read More <i class="bi bi-arrow-right-circle"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                </div>
                <div class="articles-navigation mt-4 mb-5">
                    <button class="load-more-btn">
                        <span>Load More Articles</span>
                        <i class="bi bi-arrow-down"></i>
                    </button>
                </div>
            </div>
                
                <div class="col-lg-4">
                    <div class="auto-scroll-images">
                        <div class="scroll-container">
                            <img src="assets/placeholder.png" alt="Image 1" class="scroll-image">
                            <img src="assets/placeholder.png" alt="Image 2" class="scroll-image">
                            <img src="assets/placeholder.png" alt="Image 3" class="scroll-image">
                            <img src="assets/placeholder.png" alt="Image 4" class="scroll-image">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="section-divider"></div>

    <!-- Featured Live & Video Section -->
    <section id="listen" class="featured-media-section py-5 bg-dark text-white">
        <div class="container">
            <?php 
            // Get only the first/most recent video at the section level
            $media = !empty($featuredMedia) ? reset($featuredMedia) : null;
            ?>
            <div class="row">
                <!-- Watch Live Information Column -->
                <div class="col-lg-5">
                    <div class="watch-live-heading">
                        <h2 class="display-5 mb-3 fw-bold">Watch Live</h2>
                        <div class="d-flex align-items-center mb-2">
                            <span class="watch-live-divider"></span>
                            <span class="ms-2 text-danger fw-bold">LIVE BROADCASTING</span>
                        </div>
                    </div>
                    
                    <p class="lead mb-4 watch-live-description">
                        <?php echo !empty($media['description']) ? htmlspecialchars($media['description']) : 'Watch K5 News Radio live on 88.7 FM for continuous coverage of breaking news, weather updates, and community stories from Olongapo and Central Luzon.'; ?>
                    </p>
                    
                    <?php if (!empty($media) && !empty($media['url'])): ?>
                    <!-- Watch Live Button -->
                    <a href="<?php echo htmlspecialchars($media['url']); ?>" target="_blank" class="btn btn-danger d-block mb-4 py-3 fw-bold watch-live-btn">
                        <i class="bi bi-play-circle-fill me-2"></i> WATCH LIVE NOW
                    </a>
                    <?php endif; ?>
                    
                    <!-- FM Radio Info Card -->
                    <div class="info-card mb-4 p-4">
                        <div class="d-flex align-items-center">
                            <div class="me-3 text-center info-card-icon">
                                <i class="bi bi-broadcast text-danger fs-3"></i>
                            </div>
                            <div>
                                <h3 class="fs-5 mb-1 fw-bold">FM Radio</h3>
                                <p class="mb-0 text-light fm-station-text">88.7 FM Olongapo</p>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Video Player Column - Only showing the most recent video -->
                <div class="col-lg-7">
                    <?php if (!empty($media)): ?>
                        <div class="current-stream-header">
                            <h3 class="text-end mb-2 now-streaming-label">NOW STREAMING</h3>
                            <h2 class="text-end mb-2 stream-title"><?php echo !empty($media['title']) ? htmlspecialchars($media['title']) : 'Live Broadcast'; ?></h2>
                            <p class="text-end mb-4 stream-date"><?php echo date('F j, Y'); ?></p>
                        </div>
                        
                        <div class="video-container mb-3 main-video-container">
                            <?php
                            // Simple YouTube and Facebook embed preview
                            if (preg_match('/youtu\.be\/([^\?&]+)/', $media['url'], $yt) || preg_match('/youtube\.com.*v=([^\?&]+)/', $media['url'], $yt)) {
                                $ytId = $yt[1];
                                echo '<iframe class="main-video-player" src="https://www.youtube.com/embed/' . htmlspecialchars($ytId) . '" frameborder="0" allowfullscreen></iframe>';
                            }                            elseif (strpos($media['url'], 'facebook.com') !== false) {
                                // Direct link to Facebook video instead of embedded player
                                echo '<div class="placeholder-video d-flex align-items-center justify-content-center">
                                    <div class="text-center">
                                        <i class="bi bi-facebook text-danger social-video-icon"></i>
                                        <h4 class="mt-3">Facebook Video</h4>
                                        <p class="text-muted">Click to watch on Facebook</p>
                                        <a href="' . htmlspecialchars($media['url']) . '" target="_blank" class="btn btn-danger mt-2">Watch on Facebook</a>
                                    </div>
                                </div>';
                            } elseif (strpos($media['url'], 'tiktok.com') !== false) {
                                echo '<div class="social-video-wrapper">';
                                echo '<blockquote class="tiktok-embed" cite="' . htmlspecialchars($media['url']) . '" data-video-id=""></blockquote>';
                                echo '</div>';
                            } else {
                                // Fallback: just show the link
                                echo '<div class="placeholder-video d-flex align-items-center justify-content-center">';
                                echo '<div class="text-center">';
                                echo '<i class="bi bi-play-circle-fill text-danger social-video-icon"></i>';
                                echo '<h4 class="mt-3">K5 News Radio Live Stream</h4>';
                                echo '<p class="text-muted">Click to watch</p>';
                                echo '<a href="' . htmlspecialchars($media['url']) . '" target="_blank" class="btn btn-danger mt-2">Watch Live</a>';
                                echo '</div></div>';
                            }
                            ?>
                        </div>
                        
                        <!-- Live indicator and viewer count -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="live-badge py-1 px-3 bg-danger rounded-pill">
                                <span class="live-pulse me-2"></span> 
                                LIVE
                            </div>
                            <div class="text-muted viewer-count">
                                <i class="bi bi-eye me-1"></i> Streaming now
                            </div>
                        </div>
                        

                        

                        <!-- Description moved to the left column -->
                        
                    <?php else: ?>
                        <div class="current-stream-header">
                            <h3 class="text-end mb-2">BROADCAST</h3>
                            <h2 class="text-end mb-2">K5 News Radio</h2>
                            <p class="text-end mb-4"><?php echo date('F j, Y'); ?></p>
                        </div>
                        
                        <div class="video-container mb-3 no-stream-container">
                            <div class="placeholder-video d-flex align-items-center justify-content-center">
                                <div class="text-center">
                                    <i class="bi bi-broadcast text-danger no-stream-icon"></i>
                                    <h4 class="mt-3">No Live Stream Available</h4>
                                    <p class="text-muted">Check back soon for our next broadcast</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Programs Section -->
    <section id="programs" class="programs-section py-4">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">
                    <i class="bi bi-calendar3 text-primary me-2"></i>
                    Daily Programs
                </h2>
                <p class="section-subtitle">Meet our news anchors and discover their programs</p>
            </div>

            <div class="programs-grid">
                <?php if (empty($groupedPrograms)): ?>
                    <p class="text-center">Program schedule is not available at the moment. Please check back later.</p>
                <?php else: ?>
                    <?php foreach ($groupedPrograms as $program): ?>
                        <?php $isLive = isProgramLive($program['time_slot'], $program['days']); ?>
                        <div class="program-card <?php echo $isLive ? 'current-program' : ''; ?>">
                            <div class="program-card-header">
                                <div class="program-time">
                                    <i class="bi bi-clock"></i>
                                    <span><?php echo htmlspecialchars($program['time_slot']); ?></span>
                                </div>
                                <?php if ($isLive): ?>
                                    <div class="program-status live">
                                        <i class="bi bi-broadcast"></i>
                                        <span>LIVE NOW</span>
                                    </div>
                                <?php else: ?>
                                    <div class="program-status upcoming">
                                        <i class="bi bi-calendar-event"></i>
                                        <span><?php echo formatDayRange($program['days']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="program-thumbnail">
                                <img src="<?php echo !empty($program['image_url']) && file_exists($program['image_url']) ? htmlspecialchars($program['image_url']) : 'assets/placeholder.png'; ?>" alt="<?php echo htmlspecialchars($program['anchor']); ?>">
                                <div class="program-overlay">
                                    <div class="anchor-names"><?php echo htmlspecialchars($program['anchor']); ?></div>
                                </div>
                            </div>

                            <div class="program-content">
                                <h3><?php echo htmlspecialchars($program['program_name']); ?></h3>
                                <p class="program-description"><?php echo htmlspecialchars($program['description']); ?></p>
                                <!-- Tags can be dynamically generated if available -->
                                <div class="program-features">
                                    <span class="feature-tag">News</span>
                                    <span class="feature-tag">Local</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Community Section -->
    <section id="community" class="community-section py-5 bg-light">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">
                    <i class="bi bi-people text-success me-2"></i>
                    Community Hub
                </h2>
                <p class="section-subtitle">Connecting Olongapo through news, events, and stories</p>
            </div>

            <div class="row">
                <!-- Upcoming Events Column -->
                <div class="col-lg-7">
                    <div class="community-events">
                        <h3>Upcoming Events</h3>
                        <div class="events-grid">
                            <?php if (empty($events)): ?>
                                <p class="text-center">No upcoming events at the moment. Check back soon!</p>
                            <?php else: ?>
                                <?php foreach ($events as $event): ?>
                                    <div class="event-card">
                                        <div class="event-date">
                                            <span class="month"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                                            <span class="day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                                        </div>
                                        <div class="event-info">
                                            <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                                            <p><?php echo htmlspecialchars(substr($event['description'], 0, 100)); ?>...</p>
                                            <div class="event-meta">
                                                <span><i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                                                <span><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['location']); ?></span>
                                            </div>
                                        </div>
                                        <div class="event-actions">
                                            <button class="btn btn-outline-primary btn-sm">Learn More</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Live Updates Column -->
                <div class="col-lg-5">
                    <section id="live" class="ms-lg-4">
                        <h3 class="mb-4">Live Updates</h3>
                        <div class="live-updates card shadow">
                            <ul class="list-group list-group-flush">
                            <?php if (empty($liveUpdates)): ?>
                                <li class="list-group-item">No live updates available.</li>
                            <?php else: ?>
                                <?php foreach ($liveUpdates as $update): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <p class="mb-1"><?php echo htmlspecialchars($update['message']); ?></p>
                                            <?php if (!empty($update['created_at'])): ?>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($update['created_at'])); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($update['source'])): ?>
                                            <small class="d-block text-muted">Source: <?php echo htmlspecialchars($update['source']); ?></small>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </ul>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </section>

    <!-- Weather Section -->
    <section id="weather" class="weather-section py-5">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title">
                    <i class="bi bi-cloud-sun text-primary me-2"></i>Weather Update
                </h2>
                <p class="section-subtitle">Current conditions and forecast for Olongapo City</p>
            </div>

            <div class="row g-4">
                <!-- Current Conditions Card -->
                <div class="col-md-5">
                    <div class="card shadow h-100">
                        <div class="card-body p-4">
                            <h3 class="mb-4">Current Conditions</h3>
                            
                            <?php if ($weather): ?>
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-brightness-high text-danger" style="font-size: 3rem;"></i>
                                    </div>
                                    <div>
                                        <h2 class="display-4 mb-0 fw-bold text-danger"><?php echo round($weather['temp']); ?></h2>
                                    </div>
                                    <div class="ms-2">
                                        <span class="fs-5"><?php echo weather_icon($weather['weathercode'])[1]; ?></span>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="text-danger me-2">
                                                <i class="bi bi-droplet"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted small">Humidity</div>
                                                <div class="fw-bold"><?php echo $weather['humidity']; ?>%</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="text-danger me-2">
                                                <i class="bi bi-wind"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted small">Wind Speed</div>
                                                <div class="fw-bold"><?php echo $weather['windspeed']; ?> km/h</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="text-danger me-2">
                                                <i class="bi bi-thermometer-half"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted small">Real Feel</div>
                                                <div class="fw-bold">
                                                    <?php 
                                                    $apparentTemp = ($hourlyIndex !== false && $hourlyIndex !== null && isset($weatherData['hourly']['apparent_temperature'][$hourlyIndex])) 
                                                        ? round($weatherData['hourly']['apparent_temperature'][$hourlyIndex]) : round($weather['temp']); 
                                                    echo $apparentTemp;
                                                    ?>°C
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="text-danger me-2">
                                                <i class="bi bi-umbrella"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted small">Precipitation</div>
                                                <div class="fw-bold">0%</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <?php echo htmlspecialchars($weatherError ?? 'Weather data unavailable'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 5-Day Forecast Card -->
                <div class="col-md-7">
                    <div class="card shadow h-100">
                        <div class="card-body p-4">
                            <h3 class="mb-4">5-Day Forecast</h3>
                            
                            <?php if (!empty($forecast)): ?>
                                <div class="row text-center">
                                    <?php 
                                    $daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                                    foreach ($forecast as $index => $day): 
                                        if ($index >= 5) continue; // Only show 5 days
                                        $date = new DateTime($day['date']);
                                        $dayOfWeek = $daysOfWeek[$date->format('w')];
                                        // If today's forecast is included
                                        if ($index === 0 && $date->format('Y-m-d') === date('Y-m-d')) {
                                            $dayOfWeek = 'Today';
                                        }
                                        
                                        // Format the date as "Day Month"
                                        $formattedDate = $date->format('j M');

                                        // Determine background color based on temperature
                                        $maxTemp = round($day['max']);
                                        $bgColor = '';
                                        $textClass = '';
                                        
                                        if ($maxTemp >= 35) {
                                            $bgColor = 'bg-danger bg-opacity-10';
                                            $textClass = 'text-danger'; // Keep the red color
                                        } elseif ($maxTemp >= 30) {
                                            $bgColor = 'bg-warning bg-opacity-10';
                                            $textClass = ''; // Replace with inline style
                                        } elseif ($maxTemp >= 25) {
                                            $bgColor = 'bg-success bg-opacity-10';
                                            $textClass = ''; // Replace with inline style
                                        } elseif ($maxTemp < 20) {
                                            $bgColor = 'bg-info bg-opacity-10';
                                            $textClass = ''; // Replace with inline style
                                        }
                                        
                                        // Get the weather condition
                                        $weatherCode = $day['weathercode'];
                                        $weatherCondition = weather_icon($weatherCode)[1];
                                    ?>
                                        <div class="col px-1">
                                            <div class="forecast-day rounded border shadow-sm p-2 h-100 <?php echo $bgColor; ?>" 
                                                 style="transition: all 0.2s ease-in-out;">
                                                
                                                <div class="day-header border-bottom pb-1 mb-2">
                                                    <h5 class="mb-0 fw-bold <?php echo $index === 0 ? 'text-danger' : 'text-dark'; ?>">
                                                        <?php echo $dayOfWeek; ?>
                                                    </h5>
                                                    <p class="text-dark small mb-0"><?php echo $formattedDate; ?></p>
                                                </div>
                                                
                                                <div class="weather-icon my-2">
                                                    <?php
                                                    // Weather code to appropriate icon
                                                    if ($weatherCode == 0) { // Clear sky
                                                        echo '<i class="bi bi-sun-fill text-warning" style="font-size: 2.5rem; filter: drop-shadow(0 0 3px rgba(255,193,7,0.5));"></i>';
                                                    } elseif (in_array($weatherCode, [1, 2])) { // Partly cloudy
                                                        echo '<i class="bi bi-cloud-sun-fill text-secondary" style="font-size: 2.5rem; filter: drop-shadow(0 0 2px rgba(108,117,125,0.5));"></i>';
                                                    } elseif ($weatherCode == 3) { // Overcast
                                                        echo '<i class="bi bi-cloud-fill text-secondary" style="font-size: 2.5rem; filter: drop-shadow(0 0 2px rgba(108,117,125,0.5));"></i>';
                                                    } elseif (in_array($weatherCode, [45, 48])) { // Fog
                                                        echo '<i class="bi bi-cloud-fog-fill text-secondary" style="font-size: 2.5rem; filter: drop-shadow(0 0 2px rgba(108,117,125,0.5));"></i>';
                                                    } elseif (in_array($weatherCode, [51, 53, 55, 56, 57])) { // Drizzle
                                                        echo '<i class="bi bi-cloud-drizzle-fill text-info" style="font-size: 2.5rem; filter: drop-shadow(0 0 2px rgba(13,202,240,0.5));"></i>';
                                                    } elseif (in_array($weatherCode, [61, 63, 65, 66, 67, 80, 81, 82])) { // Rain
                                                        echo '<i class="bi bi-cloud-rain-fill text-info" style="font-size: 2.5rem; filter: drop-shadow(0 0 2px rgba(13,202,240,0.5));"></i>';
                                                    } elseif (in_array($weatherCode, [71, 73, 75, 77, 85, 86])) { // Snow
                                                        echo '<i class="bi bi-cloud-snow-fill text-info" style="font-size: 2.5rem; filter: drop-shadow(0 0 2px rgba(13,202,240,0.5));"></i>';
                                                    } elseif (in_array($weatherCode, [95, 96, 99])) { // Thunderstorm
                                                        echo '<i class="bi bi-cloud-lightning-rain-fill text-danger" style="font-size: 2.5rem; filter: drop-shadow(0 0 2px rgba(220,53,69,0.5));"></i>';
                                                    } else { // Default
                                                        echo '<i class="bi bi-cloud-fill text-secondary" style="font-size: 2.5rem; filter: drop-shadow(0 0 2px rgba(108,117,125,0.5));"></i>';
                                                    }
                                                    ?>
                                                </div>
                                                
                                                <p class="condition-text small mb-2 text-dark"><?php echo $weatherCondition; ?></p>
                                                
                                                <div class="temperature-container py-1">
                                                    <?php if ($maxTemp >= 35): ?>
                                                        <span class="fw-bold fs-4 text-danger"><?php echo round($day['max']); ?>°</span>
                                                    <?php elseif ($maxTemp >= 30): ?>
                                                        <span class="fw-bold fs-4" style="color: #996600;"><?php echo round($day['max']); ?>°</span>
                                                    <?php elseif ($maxTemp >= 25): ?>
                                                        <span class="fw-bold fs-4" style="color: #1a5928;"><?php echo round($day['max']); ?>°</span>
                                                    <?php else: ?>
                                                        <span class="fw-bold fs-4" style="color: #0a4b6c;"><?php echo round($day['max']); ?>°</span>
                                                    <?php endif; ?>
                                                
                                                    <div class="temp-range d-flex align-items-center justify-content-center gap-2">
                                                        <span class="text-dark small">Low: <?php echo round($day['min']); ?>°</span>
                                                        <div class="temp-bar" 
                                                             style="height: 4px; width: 40%; background: linear-gradient(to right, #8fbafe, #dc3545); 
                                                                    border-radius: 2px;"></div>
                                                        <span class="text-dark small">High: <?php echo round($day['max']); ?>°</span>
                                                    </div>
                                                </div>
                                                
                                                <?php if (isset($day['sunrise']) && isset($day['sunset'])): ?>
                                                <div class="sun-times d-flex justify-content-around mt-2 pt-2 border-top small">
                                                    <div class="sunrise">
                                                        <i class="bi bi-sunrise text-warning"></i>
                                                        <span class="text-dark"><?php echo (new DateTime($day['sunrise']))->format('g:i a'); ?></span>
                                                    </div>
                                                    <div class="sunset">
                                                        <i class="bi bi-sunset text-danger"></i>
                                                        <span class="text-dark"><?php echo (new DateTime($day['sunset']))->format('g:i a'); ?></span>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Forecast data unavailable
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

        <!-- About Section -->
    <section id="about" class="about-section py-5">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title"><i class="bi bi-people-fill text-primary me-2"></i>Meet the K5 Team</h2>
                <p class="section-subtitle">The voices behind your favorite station</p>
            </div>
            <div class="members-grid">
                <?php foreach ($members as $member): ?>
                    <div class="member-card">
                        <?php 
                        // Check if image URL exists and file exists
                        $imageUrl = !empty($member['image_url']) && file_exists($member['image_url']) 
                            ? htmlspecialchars($member['image_url']) 
                            : 'assets/placeholder.png'; 
                        ?>
                        <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                        <div class="member-name"><?php echo htmlspecialchars($member['name']); ?></div>
                        <div class="member-position"><?php echo htmlspecialchars($member['position']); ?></div>
                        <?php if (!empty($member['program'])): ?>
                            <div class="member-program">Host: <?php echo htmlspecialchars($member['program']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section py-5 bg-dark text-white">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">
                    <i class="bi bi-telephone text-danger me-2"></i>
                    Contact K5 News Radio
                </h2>
                <p class="section-subtitle">Get in touch with our news team</p>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="contact-form-card">
                        <h3>Send Us a Message</h3>
                        <form id="contactForm" class="contact-form">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstName" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <select class="form-select" id="subject" required>
                                    <option value="">Choose a subject</option>
                                    <option value="news-tip">News Tip</option>
                                    <option value="interview-request">Interview Request</option>
                                    <option value="advertising">Advertising Inquiry</option>
                                    <option value="feedback">Feedback</option>
                                    <option value="technical">Technical Issue</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" rows="5" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="bi bi-send me-2"></i>
                                Send Message
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="contact-info">
                        <h3>Contact Information</h3>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h5>Address</h5>
                                <p>123 Rizal Avenue<br>Olongapo City, Zambales<br>Philippines 2200</p>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="bi bi-telephone"></i>
                            </div>
                            <div class="contact-details">
                                <h5>Phone</h5>
                                <p>Main: (047) 224-5678<br>News Hotline: (047) 224-NEWS<br>Text: 0917-123-4567</p>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h5>Email</h5>
                                <p>General: info@k5radio.com<br>News: news@k5radio.com<br>Advertising: ads@k5radio.com</p>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="bi bi-broadcast"></i>
                            </div>
                            <div class="contact-details">
                                <h5>Frequency</h5>
                                <p>FM 88.7 Olongapo<br>Online Stream Available<br>24/7 Broadcasting</p>
                            </div>
                        </div>

                        <div class="social-links">
                            <h5>Follow Us</h5>
                            <div class="social-icons">
                                <a href="#" class="social-link facebook">
                                    <i class="bi bi-facebook"></i>
                                </a>
                                <a href="#" class="social-link twitter">
                                    <i class="bi bi-twitter"></i>
                                </a>
                                <a href="#" class="social-link instagram">
                                    <i class="bi bi-instagram"></i>
                                </a>
                                <a href="#" class="social-link youtube">
                                    <i class="bi bi-youtube"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer bg-dark text-white py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="footer-brand">
                        <img src="assets/K5 LOGO K5 LANG.png" alt="K5 News Radio" height="40" class="me-2">
                        <div class="footer-info">
                            <div class="footer-frequency">88.7 FM</div>
                            <span>&copy; 2024 K5 News Radio. All rights reserved.</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="footer-links">
                        <a href="#" class="footer-link">Privacy Policy</a>
                        <a href="#" class="footer-link">Terms of Service</a>
                        <a href="#" class="footer-link">Advertise With Us</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- News Alerts Modal -->
    <div class="modal fade" id="newsAlertsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">News Alerts Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Stay informed with breaking news alerts delivered directly to your phone or email.</p>
                    <form id="alertsForm">
                        <div class="mb-3">
                            <label for="alertEmail" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="alertEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="alertPhone" class="form-label">Phone Number (Optional)</label>
                            <input type="tel" class="form-control" id="alertPhone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alert Types</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="breakingNews" checked>
                                <label class="form-check-label" for="breakingNews">Breaking News</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="weatherAlerts">
                                <label class="form-check-label" for="weatherAlerts">Weather Alerts</label>
                            </div>

                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger">Subscribe to Alerts</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Facebook SDK for video embeds -->
    <div id="fb-root"></div>
    <script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v19.0"></script>
    
    <!-- TikTok embed script -->
    <script async src="https://www.tiktok.com/embed.js"></script>
    
    <!-- Custom JS -->
    <script src="js/k5-news-radio.js"></script>
</body>
</html>