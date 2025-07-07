<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/HolidayAPI.php';

// Check if the user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';
$syncResults = [];

$holidayAPI = new HolidayAPI($pdo);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['sync_year'])) {
        $year = intval($_POST['year']);
        if ($year >= 2020 && $year <= 2030) {
            try {
                $results = $holidayAPI->autoSyncHolidays($year, $year);
                $syncResults = $results;
                $message = "Holiday sync completed for $year!";
            } catch (Exception $e) {
                $error = "Sync failed: " . $e->getMessage();
            }
        } else {
            $error = "Invalid year. Please enter a year between 2020 and 2030.";
        }
    } elseif (isset($_POST['sync_range'])) {
        $startYear = intval($_POST['start_year']);
        $endYear = intval($_POST['end_year']);
        
        if ($startYear >= 2020 && $endYear <= 2030 && $startYear <= $endYear) {
            try {
                $results = $holidayAPI->autoSyncHolidays($startYear, $endYear);
                $syncResults = $results;
                $message = "Holiday sync completed for $startYear-$endYear!";
            } catch (Exception $e) {
                $error = "Sync failed: " . $e->getMessage();
            }
        } else {
            $error = "Invalid year range. Please enter valid years between 2020 and 2030.";
        }
    } elseif (isset($_POST['predict_only'])) {
        $year = intval($_POST['predict_year']);
        if ($year >= 2020 && $year <= 2030) {
            try {
                $predictedHolidays = $holidayAPI->predictRecurringHolidays($year);
                $result = $holidayAPI->syncHolidays($predictedHolidays, $year);
                $syncResults = [[
                    'year' => $year,
                    'api_holidays' => 0,
                    'predicted_holidays' => $result['synced'],
                    'total_synced' => $result['synced'],
                    'errors' => $result['errors']
                ]];
                $message = "Predicted holidays added for $year!";
            } catch (Exception $e) {
                $error = "Prediction failed: " . $e->getMessage();
            }
        }
    }
}

// Get current holiday statistics (with fallback for missing source column)
try {
    $stmt = $pdo->query("SELECT
        COUNT(*) as total,
        COUNT(CASE WHEN source = 'api' OR source = 'nager_api' THEN 1 END) as from_api,
        COUNT(CASE WHEN source = 'prediction' THEN 1 END) as predicted,
        COUNT(CASE WHEN source IS NULL OR source = '' OR source = 'manual' THEN 1 END) as manual
        FROM philippine_holidays");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback if source column doesn't exist yet
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM philippine_holidays");
    $total = $stmt->fetchColumn();
    $stats = [
        'total' => $total,
        'from_api' => 0,
        'predicted' => 0,
        'manual' => $total
    ];
}

// Get recent sync activity
$stmt = $pdo->query("SELECT DISTINCT YEAR(date) as year, COUNT(*) as count 
    FROM philippine_holidays 
    GROUP BY YEAR(date) 
    ORDER BY year DESC 
    LIMIT 10");
$yearStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Holiday API Sync - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; }
        header { background: #003366; color: #fff; padding: 1em 2em; }
        main { max-width: 1000px; margin: 2em auto; background: #fff; padding: 2em; border-radius: 8px; }
        h1, h2 { color: #003366; }
        .btn { display: inline-block; padding: 0.5em 1.2em; background: #003366; color: #fff; border-radius: 4px; text-decoration: none; margin-bottom: 1em; border: none; cursor: pointer; }
        .btn:hover { background: #1890ff; }
        .btn-success { background: #52c41a; }
        .btn-warning { background: #fa8c16; }
        .btn-danger { background: #ff4d4f; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1em; margin: 2em 0; }
        .stat-card { background: #f0f8ff; border: 1px solid #b3d8fd; border-radius: 6px; padding: 1em; text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #003366; }
        .stat-label { color: #666; font-size: 0.9em; }
        .sync-section { background: #f9f9f9; border: 1px solid #ddd; border-radius: 6px; padding: 1.5em; margin: 1em 0; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1em; align-items: end; }
        label { display: block; margin-bottom: 0.3em; font-weight: bold; }
        input, select { padding: 0.5em; border: 1px solid #ddd; border-radius: 4px; width: 100%; }
        .msg { color: green; margin-bottom: 1em; padding: 0.5em; background: #f0f8ff; border: 1px solid #91d5ff; border-radius: 4px; }
        .error { color: red; margin-bottom: 1em; padding: 0.5em; background: #fff2f0; border: 1px solid #ffccc7; border-radius: 4px; }
        .results { margin-top: 2em; }
        .result-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 1em; margin: 1em 0; }
        .result-success { border-left: 4px solid #52c41a; }
        .result-warning { border-left: 4px solid #fa8c16; }
        .result-error { border-left: 4px solid #ff4d4f; }
        .api-info { background: #e6f7ff; border: 1px solid #91d5ff; border-radius: 6px; padding: 1em; margin: 1em 0; }
    </style>
</head>
<body>
<header>
    <span style="font-size:1.5em;">🔄 Holiday API Sync</span>
    <a href="index.php?tab=holidays" style="float:right; color:#fff; text-decoration:none;">← Back to Holidays</a>
</header>
<main>
    <h1>Automated Holiday Sync</h1>
    
    <?php if ($message): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php
    // Load API configuration
    require_once '../config/holiday_api_config.php';
    $apiConfig = getHolidayAPIConfig();
    $configErrors = validateHolidayAPIConfig();
    ?>

    <div class="api-info">
        <h3>🤖 Automated Holiday Sources</h3>

        <?php if (!empty($configErrors)): ?>
            <div style="background: #fff2f0; border: 1px solid #ffccc7; color: #d32f2f; padding: 1em; border-radius: 4px; margin-bottom: 1em;">
                <strong>⚠️ Configuration Issues:</strong>
                <ul>
                    <?php foreach ($configErrors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1em;">
            <div style="background: <?php echo $apiConfig['holidayapi']['enabled'] ? '#f6ffed' : '#f0f0f0'; ?>; border: 1px solid <?php echo $apiConfig['holidayapi']['enabled'] ? '#b7eb8f' : '#d9d9d9'; ?>; border-radius: 4px; padding: 1em;">
                <h4 style="margin: 0 0 0.5em 0; color: <?php echo $apiConfig['holidayapi']['enabled'] ? '#52c41a' : '#666'; ?>;">
                    🌐 HolidayAPI.com <?php echo $apiConfig['holidayapi']['enabled'] ? '(Primary)' : '(Disabled)'; ?>
                </h4>
                <p style="margin: 0; font-size: 0.9em;">
                    Premium API with comprehensive holiday data<br>
                    Status: <?php echo $apiConfig['holidayapi']['enabled'] ? '<span style="color: #52c41a;">✅ Enabled</span>' : '<span style="color: #ff4d4f;">❌ Disabled</span>'; ?>
                </p>
            </div>

            <div style="background: <?php echo $apiConfig['nager']['enabled'] ? '#f0f8ff' : '#f0f0f0'; ?>; border: 1px solid <?php echo $apiConfig['nager']['enabled'] ? '#91d5ff' : '#d9d9d9'; ?>; border-radius: 4px; padding: 1em;">
                <h4 style="margin: 0 0 0.5em 0; color: <?php echo $apiConfig['nager']['enabled'] ? '#1890ff' : '#666'; ?>;">
                    🆓 Nager.Date API <?php echo $apiConfig['nager']['enabled'] ? '(Backup)' : '(Disabled)'; ?>
                </h4>
                <p style="margin: 0; font-size: 0.9em;">
                    Free public holiday API<br>
                    Status: <?php echo $apiConfig['nager']['enabled'] ? '<span style="color: #1890ff;">✅ Enabled</span>' : '<span style="color: #ff4d4f;">❌ Disabled</span>'; ?>
                </p>
            </div>

            <div style="background: #fff7e6; border: 1px solid #ffd591; border-radius: 4px; padding: 1em;">
                <h4 style="margin: 0 0 0.5em 0; color: #fa8c16;">🔮 Prediction Algorithm</h4>
                <p style="margin: 0; font-size: 0.9em;">
                    Calculates recurring holidays and Easter-based dates<br>
                    Status: <span style="color: #52c41a;">✅ Always Available</span>
                </p>
            </div>
        </div>

        <div style="margin-top: 1em; padding: 1em; background: #f9f9f9; border-radius: 4px;">
            <strong>📋 How it works:</strong>
            <ol style="margin: 0.5em 0 0 0;">
                <li><strong>HolidayAPI.com</strong> - Primary source (if configured)</li>
                <li><strong>Nager.Date</strong> - Free backup source</li>
                <li><strong>Prediction</strong> - Algorithm-based fallback</li>
                <li><strong>Manual Override</strong> - Admin-added holidays take precedence</li>
            </ol>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Holidays</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['from_api']; ?></div>
            <div class="stat-label">From API</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['predicted']; ?></div>
            <div class="stat-label">Predicted</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['manual']; ?></div>
            <div class="stat-label">Manual</div>
        </div>
    </div>

    <div class="sync-section">
        <h2>🔄 Sync Single Year</h2>
        <form method="post">
            <div class="form-row">
                <div>
                    <label>Year:</label>
                    <input type="number" name="year" min="2020" max="2030" value="<?php echo date('Y'); ?>" required>
                </div>
                <div>
                    <button type="submit" name="sync_year" class="btn btn-success">🔄 Sync Year</button>
                </div>
            </div>
        </form>
        <small>Fetches holidays from API and adds predicted holidays as backup</small>
    </div>

    <div class="sync-section">
        <h2>📅 Sync Year Range</h2>
        <form method="post">
            <div class="form-row">
                <div>
                    <label>Start Year:</label>
                    <input type="number" name="start_year" min="2020" max="2030" value="<?php echo date('Y'); ?>" required>
                </div>
                <div>
                    <label>End Year:</label>
                    <input type="number" name="end_year" min="2020" max="2030" value="<?php echo date('Y') + 2; ?>" required>
                </div>
                <div>
                    <button type="submit" name="sync_range" class="btn btn-warning">📅 Sync Range</button>
                </div>
            </div>
        </form>
        <small>Syncs multiple years at once (useful for initial setup)</small>
    </div>

    <div class="sync-section">
        <h2>🔮 Prediction Only</h2>
        <form method="post">
            <div class="form-row">
                <div>
                    <label>Year:</label>
                    <input type="number" name="predict_year" min="2020" max="2030" value="<?php echo date('Y') + 1; ?>" required>
                </div>
                <div>
                    <button type="submit" name="predict_only" class="btn">🔮 Predict Holidays</button>
                </div>
            </div>
        </form>
        <small>Uses prediction algorithm only (when API is unavailable)</small>
    </div>

    <?php if (!empty($syncResults)): ?>
        <div class="results">
            <h2>📊 Sync Results</h2>
            <?php foreach ($syncResults as $result): ?>
                <div class="result-card <?php echo $result['total_synced'] > 0 ? 'result-success' : 'result-warning'; ?>">
                    <h3>Year <?php echo $result['year']; ?></h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1em; margin: 1em 0;">
                        <div><strong>API Holidays:</strong> <?php echo $result['api_holidays']; ?></div>
                        <div><strong>Predicted:</strong> <?php echo $result['predicted_holidays']; ?></div>
                        <div><strong>Total Synced:</strong> <?php echo $result['total_synced']; ?></div>
                    </div>
                    <?php if (!empty($result['errors'])): ?>
                        <div style="background: #fff2f0; padding: 0.5em; border-radius: 4px; margin-top: 1em;">
                            <strong>Errors:</strong>
                            <ul>
                                <?php foreach ($result['errors'] as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div style="margin-top: 2em;">
        <h2>📈 Holiday Coverage by Year</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1em;">
            <?php foreach ($yearStats as $yearStat): ?>
                <div style="background: #f0f8ff; border: 1px solid #b3d8fd; border-radius: 4px; padding: 1em; text-align: center;">
                    <div style="font-weight: bold; color: #003366;"><?php echo $yearStat['year']; ?></div>
                    <div style="color: #666;"><?php echo $yearStat['count']; ?> holidays</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="margin-top: 2em; padding: 1em; background: #f0f8ff; border-radius: 4px; border-left: 4px solid #91d5ff;">
        <h3>💡 How It Works</h3>
        <ol>
            <li><strong>API First:</strong> Tries to fetch official holidays from Nager.Date API</li>
            <li><strong>Prediction Backup:</strong> Uses algorithms to calculate recurring holidays</li>
            <li><strong>Smart Merging:</strong> Avoids duplicates and preserves manual entries</li>
            <li><strong>Easter Calculation:</strong> Automatically calculates Easter-based holidays</li>
            <li><strong>Special Days:</strong> Calculates floating holidays like National Heroes Day</li>
        </ol>
    </div>
</main>
</body>
</html>
