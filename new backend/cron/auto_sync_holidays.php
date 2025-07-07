<?php
/**
 * Automated Holiday Sync Script
 * Run this script via cron job to automatically sync holidays
 * 
 * Cron job example (runs monthly on the 1st at 2 AM):
 * 0 2 1 * * /usr/bin/php /path/to/your/project/cron/auto_sync_holidays.php
 */

// Set script to run from command line
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

// Include required files
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/HolidayAPI.php';

// Configuration
$SYNC_YEARS_AHEAD = 2; // How many years ahead to sync
$LOG_FILE = dirname(__DIR__) . '/logs/holiday_sync.log';

// Ensure logs directory exists
$logDir = dirname($LOG_FILE);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

/**
 * Log function
 */
function logMessage($message) {
    global $LOG_FILE;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
    echo $logEntry; // Also output to console
}

/**
 * Main sync function
 */
function syncHolidays() {
    global $pdo, $SYNC_YEARS_AHEAD;
    
    try {
        logMessage("Starting automated holiday sync...");
        
        $holidayAPI = new HolidayAPI($pdo);
        
        // Calculate years to sync
        $currentYear = date('Y');
        $endYear = $currentYear + $SYNC_YEARS_AHEAD;
        
        logMessage("Syncing holidays from $currentYear to $endYear");
        
        // Perform sync
        $results = $holidayAPI->autoSyncHolidays($currentYear, $endYear);
        
        // Process results
        $totalSynced = 0;
        $totalErrors = 0;
        
        foreach ($results as $result) {
            $year = $result['year'];
            $synced = $result['total_synced'];
            $errors = count($result['errors']);
            
            $totalSynced += $synced;
            $totalErrors += $errors;
            
            logMessage("Year $year: $synced holidays synced, $errors errors");
            
            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    logMessage("  ERROR: $error");
                }
            }
        }
        
        logMessage("Sync completed: $totalSynced total holidays synced, $totalErrors total errors");
        
        // Update sync status in database
        updateSyncStatus($totalSynced, $totalErrors);
        
        return true;
        
    } catch (Exception $e) {
        logMessage("FATAL ERROR: " . $e->getMessage());
        return false;
    }
}

/**
 * Update sync status in database
 */
function updateSyncStatus($synced, $errors) {
    global $pdo;
    
    try {
        // Create sync log table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS holiday_sync_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sync_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                holidays_synced INT DEFAULT 0,
                errors_count INT DEFAULT 0,
                status ENUM('success', 'partial', 'failed') DEFAULT 'success'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        
        // Determine status
        $status = 'success';
        if ($errors > 0 && $synced > 0) {
            $status = 'partial';
        } elseif ($errors > 0 && $synced == 0) {
            $status = 'failed';
        }
        
        // Insert sync log
        $stmt = $pdo->prepare("
            INSERT INTO holiday_sync_log (holidays_synced, errors_count, status) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$synced, $errors, $status]);
        
        logMessage("Sync status recorded in database");
        
    } catch (Exception $e) {
        logMessage("Failed to update sync status: " . $e->getMessage());
    }
}

/**
 * Clean old logs (keep last 30 days)
 */
function cleanOldLogs() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM holiday_sync_log WHERE sync_date < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
        $deleted = $stmt->rowCount();
        
        if ($deleted > 0) {
            logMessage("Cleaned $deleted old sync log entries");
        }
        
    } catch (Exception $e) {
        logMessage("Failed to clean old logs: " . $e->getMessage());
    }
}

/**
 * Send notification email (optional)
 */
function sendNotification($success, $details) {
    // Implement email notification if needed
    // This is optional and depends on your email setup
    
    $subject = $success ? "Holiday Sync Successful" : "Holiday Sync Failed";
    $message = "Automated holiday sync completed.\n\nDetails:\n$details";
    
    // Uncomment and configure if you want email notifications
    // mail('admin@yoursite.com', $subject, $message);
    
    logMessage("Notification would be sent: $subject");
}

// Main execution
try {
    logMessage("=== Holiday Sync Cron Job Started ===");
    
    // Check database connection
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    // Perform sync
    $success = syncHolidays();
    
    // Clean old logs
    cleanOldLogs();
    
    // Send notification
    $details = "Check logs for full details: $LOG_FILE";
    sendNotification($success, $details);
    
    logMessage("=== Holiday Sync Cron Job Completed ===");
    
    exit($success ? 0 : 1);
    
} catch (Exception $e) {
    logMessage("CRITICAL ERROR: " . $e->getMessage());
    sendNotification(false, $e->getMessage());
    exit(1);
}
?>
