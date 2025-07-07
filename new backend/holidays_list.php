<?php
require_once 'config/db.php';

// Get filter parameters
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$month = isset($_GET['month']) ? intval($_GET['month']) : 0;

// Validate parameters
if ($year < 2020 || $year > 2030) $year = date('Y');
if ($month < 0 || $month > 12) $month = 0;

// Build query based on filters
$whereConditions = ["YEAR(date) = ?"];
$params = [$year];

if ($type !== 'all') {
    $whereConditions[] = "type = ?";
    $params[] = $type;
}

if ($month > 0) {
    $whereConditions[] = "MONTH(date) = ?";
    $params[] = $month;
}

$whereClause = implode(' AND ', $whereConditions);

// Fetch holidays
$stmt = $pdo->prepare("SELECT * FROM philippine_holidays WHERE $whereClause ORDER BY date ASC");
$stmt->execute($params);
$holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available years for dropdown
$stmt = $pdo->query("SELECT DISTINCT YEAR(date) as year FROM philippine_holidays ORDER BY year");
$availableYears = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Group holidays by month
$holidaysByMonth = [];
foreach ($holidays as $holiday) {
    $monthKey = date('n', strtotime($holiday['date']));
    $holidaysByMonth[$monthKey][] = $holiday;
}

$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Philippine Holidays List - <?php echo $year; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; padding: 2em; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header { background: #003366; color: #fff; padding: 2em; text-align: center; }
        .header h1 { margin: 0; font-size: 2em; }
        .filters { background: #f9f9f9; padding: 1.5em; border-bottom: 1px solid #eee; }
        .filter-row { display: flex; gap: 1em; align-items: center; flex-wrap: wrap; justify-content: center; }
        .filter-group { display: flex; flex-direction: column; gap: 0.3em; }
        .filter-group label { font-weight: bold; font-size: 0.9em; }
        select, .btn { padding: 0.5em 1em; border: 1px solid #ddd; border-radius: 4px; }
        .btn { background: #003366; color: #fff; text-decoration: none; cursor: pointer; }
        .btn:hover { background: #1890ff; }
        .btn-secondary { background: #666; }
        .btn-secondary:hover { background: #555; }
        .content { padding: 2em; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1em; margin-bottom: 2em; }
        .stat-card { background: #f0f8ff; border: 1px solid #b3d8fd; border-radius: 6px; padding: 1em; text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #003366; }
        .stat-label { color: #666; font-size: 0.9em; }
        .month-section { margin-bottom: 2em; }
        .month-header { background: #003366; color: #fff; padding: 1em; border-radius: 6px 6px 0 0; font-size: 1.2em; font-weight: bold; }
        .holidays-grid { display: grid; gap: 1em; }
        .holiday-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 1em; border-left: 4px solid #91d5ff; }
        .holiday-regular { border-left-color: #52c41a; }
        .holiday-special_non_working { border-left-color: #fa8c16; }
        .holiday-special_working { border-left-color: #1890ff; }
        .holiday-name { font-weight: bold; font-size: 1.1em; margin-bottom: 0.5em; color: #003366; }
        .holiday-date { color: #666; margin-bottom: 0.5em; }
        .holiday-type { display: inline-block; padding: 0.3em 0.8em; border-radius: 15px; font-size: 0.85em; font-weight: 500; }
        .type-regular { background: #f6ffed; color: #52c41a; }
        .type-special_non_working { background: #fff7e6; color: #fa8c16; }
        .type-special_working { background: #f0f5ff; color: #1890ff; }
        .holiday-description { color: #555; font-size: 0.9em; margin-top: 0.5em; line-height: 1.4; }
        .no-holidays { text-align: center; color: #666; font-style: italic; padding: 2em; }
        .back-link { position: absolute; top: 1em; left: 1em; background: rgba(255,255,255,0.9); padding: 0.5em 1em; border-radius: 4px; text-decoration: none; color: #003366; }
        .back-link:hover { background: #fff; }
        @media (max-width: 768px) {
            .filter-row { flex-direction: column; align-items: stretch; }
            .stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-link">← Back to Home</a>
    
    <div class="container">
        <div class="header">
            <h1>🇵🇭 Philippine Holidays</h1>
            <p>Complete list of official holidays in the Philippines</p>
        </div>

        <div class="filters">
            <form method="GET" class="filter-row">
                <div class="filter-group">
                    <label>Year:</label>
                    <select name="year" onchange="this.form.submit()">
                        <?php foreach ($availableYears as $availableYear): ?>
                            <option value="<?php echo $availableYear; ?>" <?php echo $availableYear == $year ? 'selected' : ''; ?>>
                                <?php echo $availableYear; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Type:</label>
                    <select name="type" onchange="this.form.submit()">
                        <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="regular" <?php echo $type === 'regular' ? 'selected' : ''; ?>>Regular Holidays</option>
                        <option value="special_non_working" <?php echo $type === 'special_non_working' ? 'selected' : ''; ?>>Special Non-Working</option>
                        <option value="special_working" <?php echo $type === 'special_working' ? 'selected' : ''; ?>>Special Working</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Month:</label>
                    <select name="month" onchange="this.form.submit()">
                        <option value="0" <?php echo $month === 0 ? 'selected' : ''; ?>>All Months</option>
                        <?php foreach ($months as $monthNum => $monthName): ?>
                            <option value="<?php echo $monthNum; ?>" <?php echo $month === $monthNum ? 'selected' : ''; ?>>
                                <?php echo $monthName; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <a href="holidays_calendar.php?year=<?php echo $year; ?>" class="btn">📅 Calendar View</a>
                <a href="?year=<?php echo $year; ?>" class="btn btn-secondary">🔄 Reset Filters</a>
            </form>
        </div>

        <div class="content">
            <?php if (!empty($holidays)): ?>
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($holidays); ?></div>
                        <div class="stat-label">Total Holidays</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($holidays, function($h) { return $h['type'] === 'regular'; })); ?></div>
                        <div class="stat-label">Regular Holidays</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($holidays, function($h) { return $h['type'] === 'special_non_working'; })); ?></div>
                        <div class="stat-label">Special Non-Working</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($holidays, function($h) { return $h['type'] === 'special_working'; })); ?></div>
                        <div class="stat-label">Special Working</div>
                    </div>
                </div>

                <?php foreach ($holidaysByMonth as $monthNum => $monthHolidays): ?>
                    <div class="month-section">
                        <div class="month-header">
                            📅 <?php echo $months[$monthNum] . ' ' . $year; ?> (<?php echo count($monthHolidays); ?> holidays)
                        </div>
                        <div class="holidays-grid">
                            <?php foreach ($monthHolidays as $holiday): ?>
                                <div class="holiday-card holiday-<?php echo $holiday['type']; ?>">
                                    <div class="holiday-name"><?php echo htmlspecialchars($holiday['name']); ?></div>
                                    <div class="holiday-date">📅 <?php echo date('l, F j, Y', strtotime($holiday['date'])); ?></div>
                                    <div class="holiday-type type-<?php echo $holiday['type']; ?>">
                                        <?php 
                                        switch($holiday['type']) {
                                            case 'regular': echo 'Regular Holiday'; break;
                                            case 'special_non_working': echo 'Special Non-Working Day'; break;
                                            case 'special_working': echo 'Special Working Holiday'; break;
                                        }
                                        ?>
                                    </div>
                                    <?php if ($holiday['description']): ?>
                                        <div class="holiday-description"><?php echo htmlspecialchars($holiday['description']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-holidays">
                    <h3>No holidays found</h3>
                    <p>No holidays match your current filter criteria.</p>
                    <a href="?year=<?php echo $year; ?>" class="btn">View All Holidays for <?php echo $year; ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
