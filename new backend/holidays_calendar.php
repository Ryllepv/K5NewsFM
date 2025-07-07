<?php
require_once 'config/db.php';

// Get year and month from URL parameters
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

// Validate year and month
if ($year < 2020 || $year > 2030) $year = date('Y');
if ($month < 1 || $month > 12) $month = date('n');

// Get holidays for the current month
$stmt = $pdo->prepare("SELECT * FROM philippine_holidays WHERE YEAR(date) = ? AND MONTH(date) = ? ORDER BY date");
$stmt->execute([$year, $month]);
$holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create array of holiday dates for easy lookup
$holidayDates = [];
foreach ($holidays as $holiday) {
    $day = date('j', strtotime($holiday['date']));
    $holidayDates[$day] = $holiday;
}

// Calendar calculations
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$monthName = date('F', $firstDay);
$daysInMonth = date('t', $firstDay);
$dayOfWeek = date('w', $firstDay);

// Navigation dates
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Philippine Holidays Calendar - <?php echo $monthName . ' ' . $year; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; padding: 2em; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header { background: #003366; color: #fff; padding: 1.5em; text-align: center; }
        .header h1 { margin: 0; font-size: 1.6em; }
        .nav-controls { display: flex; justify-content: space-between; align-items: center; margin-top: 1em; }
        .nav-btn { background: #1890ff; color: #fff; padding: 0.4em 0.8em; border-radius: 4px; text-decoration: none; font-size: 0.9em; }
        .nav-btn:hover { background: #40a9ff; }
        .calendar { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: #ddd; margin: 1.5em; }
        .day-header { background: #003366; color: #fff; padding: 0.6em; text-align: center; font-weight: bold; font-size: 0.9em; }
        .day-cell { background: #fff; min-height: 80px; padding: 0.4em; position: relative; border: 1px solid #eee; }
        .day-number { font-weight: bold; margin-bottom: 0.3em; font-size: 0.9em; }
        .holiday { background: #fff2f0; border-left: 3px solid #ff4d4f; }
        .holiday-regular { border-left-color: #52c41a; background: #f6ffed; }
        .holiday-special_non_working { border-left-color: #fa8c16; background: #fff7e6; }
        .holiday-special_working { border-left-color: #1890ff; background: #f0f5ff; }
        .holiday-name { font-size: 0.7em; color: #333; font-weight: 500; line-height: 1.1; margin-bottom: 0.2em; }
        .holiday-type { font-size: 0.6em; padding: 0.1em 0.3em; border-radius: 6px; display: inline-block; }
        .type-regular { background: #52c41a; color: #fff; }
        .type-special_non_working { background: #fa8c16; color: #fff; }
        .type-special_working { background: #1890ff; color: #fff; }
        .today { background: #e6f7ff; border: 2px solid #1890ff; }
        .other-month { background: #f9f9f9; color: #ccc; }
        .legend { display: flex; justify-content: center; gap: 2em; padding: 1em; background: #f9f9f9; flex-wrap: wrap; }
        .legend-item { display: flex; align-items: center; gap: 0.5em; }
        .legend-color { width: 20px; height: 20px; border-radius: 3px; }
        .back-link { position: absolute; top: 1em; left: 1em; background: rgba(255,255,255,0.9); padding: 0.4em 0.8em; border-radius: 4px; text-decoration: none; color: #003366; font-size: 0.9em; }
        .back-link:hover { background: #fff; }
        @media (max-width: 768px) {
            .calendar { margin: 1em; }
            .day-cell { min-height: 60px; padding: 0.2em; }
            .day-number { font-size: 0.8em; }
            .holiday-name { font-size: 0.6em; }
            .holiday-type { font-size: 0.5em; }
            .nav-controls { flex-direction: column; gap: 0.5em; }
            .legend { flex-direction: column; align-items: center; gap: 0.5em; }
            .header { padding: 1em; }
            .header h1 { font-size: 1.3em; }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-link">← Back to Home</a>
    
    <div class="container">
        <div class="header">
            <h1>🇵🇭 Philippine Holidays Calendar</h1>
            <div class="nav-controls">
                <a href="?year=<?php echo $prevYear; ?>&month=<?php echo $prevMonth; ?>" class="nav-btn">← <?php echo date('F Y', mktime(0,0,0,$prevMonth,1,$prevYear)); ?></a>
                <h2><?php echo $monthName . ' ' . $year; ?></h2>
                <a href="?year=<?php echo $nextYear; ?>&month=<?php echo $nextMonth; ?>" class="nav-btn"><?php echo date('F Y', mktime(0,0,0,$nextMonth,1,$nextYear)); ?> →</a>
            </div>
            <div style="margin-top: 1em;">
                <a href="holidays_list.php" class="nav-btn">📋 View All Holidays</a>
                <a href="?year=<?php echo date('Y'); ?>&month=<?php echo date('n'); ?>" class="nav-btn">📅 Today</a>
            </div>
        </div>

        <div class="calendar">
            <!-- Day headers -->
            <div class="day-header">Sun</div>
            <div class="day-header">Mon</div>
            <div class="day-header">Tue</div>
            <div class="day-header">Wed</div>
            <div class="day-header">Thu</div>
            <div class="day-header">Fri</div>
            <div class="day-header">Sat</div>

            <?php
            // Add empty cells for days before the first day of the month
            for ($i = 0; $i < $dayOfWeek; $i++) {
                echo '<div class="day-cell other-month"></div>';
            }

            // Add days of the month
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $isToday = ($year == date('Y') && $month == date('n') && $day == date('j'));
                $isHoliday = isset($holidayDates[$day]);
                
                $classes = ['day-cell'];
                if ($isToday) $classes[] = 'today';
                if ($isHoliday) {
                    $classes[] = 'holiday';
                    $classes[] = 'holiday-' . $holidayDates[$day]['type'];
                }
                
                echo '<div class="' . implode(' ', $classes) . '">';
                echo '<div class="day-number">' . $day . '</div>';
                
                if ($isHoliday) {
                    $holiday = $holidayDates[$day];
                    echo '<div class="holiday-name">' . htmlspecialchars($holiday['name']) . '</div>';
                    echo '<div class="holiday-type type-' . $holiday['type'] . '">';
                    switch($holiday['type']) {
                        case 'regular': echo 'Regular'; break;
                        case 'special_non_working': echo 'Special'; break;
                        case 'special_working': echo 'Working'; break;
                    }
                    echo '</div>';
                }
                
                echo '</div>';
            }

            // Fill remaining cells
            $totalCells = $dayOfWeek + $daysInMonth;
            $remainingCells = 42 - $totalCells; // 6 rows × 7 days = 42 cells
            for ($i = 0; $i < $remainingCells; $i++) {
                echo '<div class="day-cell other-month"></div>';
            }
            ?>
        </div>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background: #52c41a;"></div>
                <span>Regular Holiday</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #fa8c16;"></div>
                <span>Special Non-Working</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #1890ff;"></div>
                <span>Special Working</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #1890ff; border: 2px solid #003366;"></div>
                <span>Today</span>
            </div>
        </div>
    </div>

    <script>
    // Add keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') {
            window.location.href = '?year=<?php echo $prevYear; ?>&month=<?php echo $prevMonth; ?>';
        } else if (e.key === 'ArrowRight') {
            window.location.href = '?year=<?php echo $nextYear; ?>&month=<?php echo $nextMonth; ?>';
        }
    });
    </script>
</body>
</html>
