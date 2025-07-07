<?php
/**
 * Holiday API Configuration
 * Set your API keys and preferences here
 */

// HolidayAPI.com Configuration (Primary source)
define('HOLIDAYAPI_KEY', 'YOUR_HOLIDAYAPI_KEY_HERE'); // Replace with your actual API key
define('HOLIDAYAPI_COUNTRY', 'PH'); // Philippines
define('HOLIDAYAPI_ENABLED', true);

// Nager.Date API Configuration (Free backup source)
define('NAGER_ENABLED', true);
define('NAGER_COUNTRY', 'PH');

// Calendarific API Configuration (Optional)
define('CALENDARIFIC_KEY', ''); // Optional backup API
define('CALENDARIFIC_COUNTRY', 'PH');
define('CALENDARIFIC_ENABLED', false);

// API Settings
define('API_TIMEOUT', 15); // Timeout in seconds
define('API_USER_AGENT', 'Mozilla/5.0 (compatible; Holiday Sync)');

// Sync Settings
define('AUTO_SYNC_YEARS_AHEAD', 2); // How many years ahead to sync
define('ENABLE_PREDICTION_FALLBACK', true); // Use prediction if APIs fail
define('LOG_API_RESPONSES', false); // Set to true for debugging

/**
 * Get HolidayAPI configuration
 */
function getHolidayAPIConfig() {
    return [
        'holidayapi' => [
            'url' => 'https://holidayapi.com/v1/holidays',
            'key' => HOLIDAYAPI_KEY,
            'country' => HOLIDAYAPI_COUNTRY,
            'enabled' => HOLIDAYAPI_ENABLED && HOLIDAYAPI_KEY !== 'YOUR_HOLIDAYAPI_KEY_HERE'
        ],
        'nager' => [
            'url' => 'https://date.nager.at/api/v3/publicholidays',
            'country' => NAGER_COUNTRY,
            'enabled' => NAGER_ENABLED
        ],
        'calendarific' => [
            'url' => 'https://calendarific.com/api/v2/holidays',
            'key' => CALENDARIFIC_KEY,
            'country' => CALENDARIFIC_COUNTRY,
            'enabled' => CALENDARIFIC_ENABLED && !empty(CALENDARIFIC_KEY)
        ]
    ];
}

/**
 * Validate API configuration
 */
function validateHolidayAPIConfig() {
    $errors = [];
    
    if (HOLIDAYAPI_KEY === 'YOUR_HOLIDAYAPI_KEY_HERE' || empty(HOLIDAYAPI_KEY)) {
        $errors[] = "HolidayAPI key not configured. Please set HOLIDAYAPI_KEY in config/holiday_api_config.php";
    }
    
    if (!HOLIDAYAPI_ENABLED && !NAGER_ENABLED) {
        $errors[] = "No API sources enabled. Please enable at least one API source.";
    }
    
    return $errors;
}

/**
 * Get API priority order
 */
function getAPIPriorityOrder() {
    $order = [];
    
    if (HOLIDAYAPI_ENABLED && HOLIDAYAPI_KEY !== 'YOUR_HOLIDAYAPI_KEY_HERE') {
        $order[] = 'holidayapi';
    }
    
    if (NAGER_ENABLED) {
        $order[] = 'nager';
    }
    
    if (CALENDARIFIC_ENABLED && !empty(CALENDARIFIC_KEY)) {
        $order[] = 'calendarific';
    }
    
    return $order;
}
?>
