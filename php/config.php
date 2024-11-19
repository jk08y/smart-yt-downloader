<?php
// php/config.php

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set maximum execution time to 5 minutes
set_time_limit(300);

// Set memory limit
ini_set('memory_limit', '512M');

// Directory configurations
define('BASE_PATH', dirname(__DIR__));
define('DOWNLOAD_PATH', BASE_PATH . '/downloads');
define('TEMP_PATH', BASE_PATH . '/downloads/temp');

// Clean up files older than 1 hour
define('FILE_LIFETIME', 3600); // 1 hour in seconds

// Allowed formats
define('ALLOWED_FORMATS', [
    'mp4_720' => ['format' => 'mp4', 'quality' => '720p'],
    'mp4_1080' => ['format' => 'mp4', 'quality' => '1080p'],
    'mp3_320' => ['format' => 'mp3', 'quality' => '320'],
    'mp3_192' => ['format' => 'mp3', 'quality' => '192']
]);
