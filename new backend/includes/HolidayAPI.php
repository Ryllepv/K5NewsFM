<?php
/**
 * Holiday API Integration Class
 * Fetches and manages holidays from multiple sources with automation
 */

class HolidayAPI {
    private $pdo;
    private $apiSources;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;

        // Load configuration
        require_once dirname(__DIR__) . '/config/holiday_api_config.php';
        $this->apiSources = getHolidayAPIConfig();
    }
    
    /**
     * Fetch holidays from HolidayAPI.com (Primary source)
     */
    public function fetchFromHolidayAPI($year) {
        $apiKey = $this->apiSources['holidayapi']['key'];
        $country = $this->apiSources['holidayapi']['country'];

        if (empty($apiKey) || $apiKey === 'YOUR_HOLIDAYAPI_KEY_HERE') {
            throw new Exception("HolidayAPI key not configured. Please set your API key in the constructor.");
        }

        $url = $this->apiSources['holidayapi']['url'] . "?key={$apiKey}&country={$country}&year={$year}&format=json";

        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0 (compatible; Holiday Sync)',
                'method' => 'GET'
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new Exception("Failed to fetch holidays from HolidayAPI.com");
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['holidays'])) {
            throw new Exception("Invalid response from HolidayAPI.com: " . ($data['error'] ?? 'Unknown error'));
        }

        return $this->parseHolidayAPIResponse($data['holidays']);
    }

    /**
     * Parse HolidayAPI.com response
     */
    private function parseHolidayAPIResponse($holidays) {
        $parsedHolidays = [];

        foreach ($holidays as $holiday) {
            $parsedHolidays[] = [
                'name' => $holiday['name'],
                'date' => $holiday['date'],
                'type' => $this->mapHolidayAPIType($holiday),
                'description' => $holiday['name'] . (isset($holiday['observed']) ? ' (Observed: ' . $holiday['observed'] . ')' : ''),
                'is_recurring' => !isset($holiday['observed']), // Non-observed holidays are usually recurring
                'month_day' => date('m-d', strtotime($holiday['date'])),
                'source' => 'holidayapi'
            ];
        }

        return $parsedHolidays;
    }

    /**
     * Map HolidayAPI types to our system
     */
    private function mapHolidayAPIType($holiday) {
        // HolidayAPI.com provides different data structure
        if (isset($holiday['public']) && $holiday['public']) {
            return 'regular';
        } elseif (isset($holiday['type'])) {
            switch (strtolower($holiday['type'])) {
                case 'public':
                case 'national':
                    return 'regular';
                case 'observance':
                case 'optional':
                    return 'special_working';
                default:
                    return 'special_non_working';
            }
        } else {
            return 'regular'; // Default for HolidayAPI
        }
    }

    /**
     * Fetch holidays from Nager.Date API (Backup source)
     */
    public function fetchFromNager($year) {
        $url = $this->apiSources['nager']['url'] . '/' . $year . '/' . $this->apiSources['nager']['country'];
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (compatible; Holiday Sync)',
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("Failed to fetch holidays from Nager API");
        }
        
        $data = json_decode($response, true);
        if (!$data) {
            throw new Exception("Invalid response from Nager API");
        }
        
        return $this->parseNagerResponse($data);
    }
    
    /**
     * Parse Nager API response
     */
    private function parseNagerResponse($data) {
        $holidays = [];
        
        foreach ($data as $holiday) {
            $holidays[] = [
                'name' => $holiday['name'],
                'date' => $holiday['date'],
                'type' => $this->mapHolidayType($holiday['types'] ?? []),
                'description' => $holiday['localName'] ?? $holiday['name'],
                'is_recurring' => true,
                'month_day' => date('m-d', strtotime($holiday['date'])),
                'source' => 'nager_api'
            ];
        }
        
        return $holidays;
    }
    
    /**
     * Map API holiday types to our system
     */
    private function mapHolidayType($types) {
        if (in_array('Public', $types)) {
            return 'regular';
        } elseif (in_array('Bank', $types) || in_array('School', $types)) {
            return 'special_non_working';
        } else {
            return 'special_working';
        }
    }
    
    /**
     * Predict recurring holidays for future years
     */
    public function predictRecurringHolidays($year) {
        $holidays = [];
        
        // Fixed date holidays
        $fixedHolidays = [
            ['name' => 'New Year\'s Day', 'month' => 1, 'day' => 1, 'type' => 'regular'],
            ['name' => 'EDSA People Power Revolution Anniversary', 'month' => 2, 'day' => 25, 'type' => 'special_non_working'],
            ['name' => 'Araw ng Kagitingan (Day of Valor)', 'month' => 4, 'day' => 9, 'type' => 'regular'],
            ['name' => 'Labor Day', 'month' => 5, 'day' => 1, 'type' => 'regular'],
            ['name' => 'Independence Day', 'month' => 6, 'day' => 12, 'type' => 'regular'],
            ['name' => 'Ninoy Aquino Day', 'month' => 8, 'day' => 21, 'type' => 'special_non_working'],
            ['name' => 'All Saints\' Day', 'month' => 11, 'day' => 1, 'type' => 'special_non_working'],
            ['name' => 'Bonifacio Day', 'month' => 11, 'day' => 30, 'type' => 'regular'],
            ['name' => 'Feast of the Immaculate Conception', 'month' => 12, 'day' => 8, 'type' => 'special_non_working'],
            ['name' => 'Christmas Eve', 'month' => 12, 'day' => 24, 'type' => 'special_non_working'],
            ['name' => 'Christmas Day', 'month' => 12, 'day' => 25, 'type' => 'regular'],
            ['name' => 'Rizal Day', 'month' => 12, 'day' => 30, 'type' => 'regular'],
            ['name' => 'New Year\'s Eve', 'month' => 12, 'day' => 31, 'type' => 'special_non_working']
        ];
        
        foreach ($fixedHolidays as $holiday) {
            $date = sprintf('%04d-%02d-%02d', $year, $holiday['month'], $holiday['day']);
            $holidays[] = [
                'name' => $holiday['name'],
                'date' => $date,
                'type' => $holiday['type'],
                'description' => $holiday['name'],
                'is_recurring' => true,
                'month_day' => sprintf('%02d-%02d', $holiday['month'], $holiday['day']),
                'source' => 'prediction'
            ];
        }
        
        // Calculate Easter-based holidays
        $easterHolidays = $this->calculateEasterHolidays($year);
        $holidays = array_merge($holidays, $easterHolidays);
        
        // Calculate National Heroes Day (last Monday of August)
        $heroesDay = $this->calculateNationalHeroesDay($year);
        $holidays[] = $heroesDay;
        
        return $holidays;
    }
    
    /**
     * Calculate Easter-based holidays
     */
    private function calculateEasterHolidays($year) {
        $easter = easter_date($year);
        $easterDate = date('Y-m-d', $easter);
        
        $holidays = [];
        
        // Maundy Thursday (3 days before Easter)
        $maundyThursday = date('Y-m-d', strtotime($easterDate . ' -3 days'));
        $holidays[] = [
            'name' => 'Maundy Thursday',
            'date' => $maundyThursday,
            'type' => 'regular',
            'description' => 'Thursday before Easter Sunday',
            'is_recurring' => false,
            'month_day' => null,
            'source' => 'prediction'
        ];
        
        // Good Friday (2 days before Easter)
        $goodFriday = date('Y-m-d', strtotime($easterDate . ' -2 days'));
        $holidays[] = [
            'name' => 'Good Friday',
            'date' => $goodFriday,
            'type' => 'regular',
            'description' => 'Friday before Easter Sunday',
            'is_recurring' => false,
            'month_day' => null,
            'source' => 'prediction'
        ];
        
        // Black Saturday (1 day before Easter)
        $blackSaturday = date('Y-m-d', strtotime($easterDate . ' -1 day'));
        $holidays[] = [
            'name' => 'Black Saturday',
            'date' => $blackSaturday,
            'type' => 'special_non_working',
            'description' => 'Saturday before Easter Sunday',
            'is_recurring' => false,
            'month_day' => null,
            'source' => 'prediction'
        ];
        
        return $holidays;
    }
    
    /**
     * Calculate National Heroes Day (last Monday of August)
     */
    private function calculateNationalHeroesDay($year) {
        $lastDayOfAugust = mktime(0, 0, 0, 8, 31, $year);
        $dayOfWeek = date('w', $lastDayOfAugust);
        
        // Calculate days to subtract to get to Monday (1)
        $daysToSubtract = ($dayOfWeek + 6) % 7;
        $heroesDay = date('Y-m-d', $lastDayOfAugust - ($daysToSubtract * 24 * 60 * 60));
        
        return [
            'name' => 'National Heroes Day',
            'date' => $heroesDay,
            'type' => 'regular',
            'description' => 'Last Monday of August',
            'is_recurring' => false,
            'month_day' => null,
            'source' => 'prediction'
        ];
    }
    
    /**
     * Sync holidays to database
     */
    public function syncHolidays($holidays, $year) {
        $synced = 0;
        $errors = [];
        
        foreach ($holidays as $holiday) {
            try {
                // Check if holiday already exists
                $stmt = $this->pdo->prepare("SELECT id FROM philippine_holidays WHERE date = ? AND name = ?");
                $stmt->execute([$holiday['date'], $holiday['name']]);
                
                if (!$stmt->fetch()) {
                    // Insert new holiday
                    $stmt = $this->pdo->prepare("
                        INSERT INTO philippine_holidays (name, date, type, description, is_recurring, month_day, source) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $holiday['name'],
                        $holiday['date'],
                        $holiday['type'],
                        $holiday['description'],
                        $holiday['is_recurring'] ? 1 : 0,
                        $holiday['month_day'],
                        $holiday['source'] ?? 'api'
                    ]);
                    $synced++;
                }
            } catch (Exception $e) {
                $errors[] = "Error syncing {$holiday['name']}: " . $e->getMessage();
            }
        }
        
        return [
            'synced' => $synced,
            'errors' => $errors,
            'total' => count($holidays)
        ];
    }
    
    /**
     * Auto-sync holidays for multiple years
     */
    public function autoSyncHolidays($startYear, $endYear) {
        $results = [];
        
        for ($year = $startYear; $year <= $endYear; $year++) {
            $yearResults = [
                'year' => $year,
                'api_holidays' => 0,
                'predicted_holidays' => 0,
                'total_synced' => 0,
                'errors' => []
            ];
            
            try {
                // Try to fetch from HolidayAPI.com first (primary source)
                $apiHolidays = $this->fetchFromHolidayAPI($year);
                $apiResult = $this->syncHolidays($apiHolidays, $year);
                $yearResults['api_holidays'] = $apiResult['synced'];
                $yearResults['errors'] = array_merge($yearResults['errors'], $apiResult['errors']);
            } catch (Exception $e) {
                $yearResults['errors'][] = "HolidayAPI.com fetch failed: " . $e->getMessage();

                // Fallback to Nager API if HolidayAPI fails
                try {
                    $apiHolidays = $this->fetchFromNager($year);
                    $apiResult = $this->syncHolidays($apiHolidays, $year);
                    $yearResults['api_holidays'] = $apiResult['synced'];
                    $yearResults['errors'] = array_merge($yearResults['errors'], $apiResult['errors']);
                    $yearResults['errors'][] = "Used Nager API as fallback";
                } catch (Exception $e2) {
                    $yearResults['errors'][] = "Nager API fallback also failed: " . $e2->getMessage();
                }
            }
            
            // Always add predicted holidays as fallback
            $predictedHolidays = $this->predictRecurringHolidays($year);
            $predictedResult = $this->syncHolidays($predictedHolidays, $year);
            $yearResults['predicted_holidays'] = $predictedResult['synced'];
            $yearResults['errors'] = array_merge($yearResults['errors'], $predictedResult['errors']);
            
            $yearResults['total_synced'] = $yearResults['api_holidays'] + $yearResults['predicted_holidays'];
            $results[] = $yearResults;
        }
        
        return $results;
    }
}
?>
