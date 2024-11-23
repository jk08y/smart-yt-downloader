<?php
declare(strict_types=1);

if (php_sapi_name() === 'cli-server') {
    die('Access denied');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use YoutubeDl\Options;
use YoutubeDl\YoutubeDl;
use YoutubeDl\Exception\CopyrightException;
use YoutubeDl\Exception\NotFoundException;
use YoutubeDl\Exception\PrivateVideoException;

class DownloadLogger {
    private static string $logFile;

    public static function init(string $logPath = null): void {
        self::$logFile = $logPath ?? __DIR__ . '/../logs/download_errors.log';
        
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    public static function error(string $message, ?Throwable $exception = null): void {
        $timestamp = date('Y-m-d H:i:s');
        $errorLog = "[{$timestamp}] " . $message;
        
        if ($exception) {
            $errorLog .= " | Exception: " . $exception->getMessage();
            $errorLog .= " | Stack: " . $exception->getTraceAsString();
        }

        error_log($errorLog . PHP_EOL, 3, self::$logFile);
    }
}

class VideoDownloader {
    private YoutubeDl $yt;
    private string $cookiesPath;
    private string $downloadPath;

    public function __construct() {
        $this->yt = new YoutubeDl();
        $this->cookiesPath = __DIR__ . '/../cookies.txt';
        $this->downloadPath = DOWNLOAD_PATH;
        
        // Ensure download directory exists and is writable
        if (!is_dir($this->downloadPath)) {
            if (!mkdir($this->downloadPath, 0755, true)) {
                throw new \RuntimeException('Failed to create download directory');
            }
        }
        
        if (!is_writable($this->downloadPath)) {
            throw new \RuntimeException('Download directory is not writable');
        }
    }

    private function validateInput(array $input): void {
        if (empty($input['url'])) {
            throw new \InvalidArgumentException('Video URL is required');
        }

        if (!filter_var($input['url'], FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid URL format');
        }

        if (empty($input['format']) || !isset(ALLOWED_FORMATS[$input['format']])) {
            throw new \InvalidArgumentException('Invalid download format');
        }
    }

    private function sanitizeFilename(string $filename): string {
        // Remove non-ASCII characters and replace spaces with underscores
        $filename = preg_replace('/[^\x20-\x7E]/', '', $filename);
        $filename = str_replace(' ', '_', $filename);
        $filename = preg_replace('/[^\w\-\.]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        return substr(trim($filename), 0, 255);
    }

    private function prepareDownloadOptions(array $input): Options {
        $format = ALLOWED_FORMATS[$input['format']];
        
        if (!file_exists($this->cookiesPath)) {
            throw new \RuntimeException('Cookies file not found');
        }

        $options = Options::create()
            ->downloadPath($this->downloadPath)
            ->output('%(title)s.%(ext)s')
            ->cookies($this->cookiesPath)
            ->url($input['url']);

        if ($format['format'] === 'mp4') {
            $options->format('bestvideo[height<=' . str_replace('p', '', $format['quality']) . ']+bestaudio/best[height<=' . str_replace('p', '', $format['quality']) . ']');
        } else {
            $options->extractAudio(true)
                   ->audioFormat('mp3')
                   ->audioQuality($format['quality']);
        }

        return $options;
    }

    public function download(array $input): void {
        try {
            $this->validateInput($input);
            $options = $this->prepareDownloadOptions($input);
            
            // Download video
            $collection = $this->yt->download($options);
            
            if ($collection->count() === 0) {
                throw new \RuntimeException('Failed to download video');
            }

            $video = $collection->getVideos()[0];
            
            if ($video->getError() !== null) {
                throw new \RuntimeException($video->getError());
            }

            $downloadedFile = $video->getFile();
            if (!$downloadedFile || !$downloadedFile->isFile()) {
                throw new \RuntimeException('Failed to retrieve downloaded file');
            }

            // Process the downloaded file
            $originalPath = $downloadedFile->getRealPath();
            $sanitizedFilename = $this->sanitizeFilename($downloadedFile->getFilename());
            $finalPath = $this->downloadPath . '/' . $sanitizedFilename;

            // Move file if names differ
            if ($originalPath !== $finalPath) {
                if (!rename($originalPath, $finalPath)) {
                    throw new \RuntimeException('Failed to move downloaded file');
                }
            }

            // Set proper file permissions
            chmod($finalPath, 0644);

            // Verify file exists and is readable
            if (!is_readable($finalPath)) {
                throw new \RuntimeException('Downloaded file is not readable');
            }

            // Stream file to client
            $this->streamFile($finalPath, $sanitizedFilename);

        } catch (NotFoundException $e) {
            DownloadLogger::error('Video not found', $e);
            $this->sendJsonError('Video not found');
        } catch (PrivateVideoException $e) {
            DownloadLogger::error('Private video', $e);
            $this->sendJsonError('This video is private');
        } catch (CopyrightException $e) {
            DownloadLogger::error('Copyright protected', $e);
            $this->sendJsonError('This video is copyright protected');
        } catch (\Throwable $e) {
            DownloadLogger::error('Download error', $e);
            $this->sendJsonError($e->getMessage());
        }
    }

    private function streamFile(string $filepath, string $filename): void {
        if (!file_exists($filepath)) {
            throw new \RuntimeException('File not found: ' . $filepath);
        }

        $size = filesize($filepath);
        $mime = mime_content_type($filepath) ?: 'application/octet-stream';

        // Send headers
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . $size);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        // Stream file
        $handle = fopen($filepath, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Could not open file for reading');
        }

        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        
        fclose($handle);

        // Delete file after streaming
        unlink($filepath);
    }

    private function sendJsonError(string $message): void {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }

    public function cleanOldFiles(int $lifetime = FILE_LIFETIME): void {
        $files = glob($this->downloadPath . '/*');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file) >= $lifetime)) {
                @unlink($file);
            }
        }
    }
}

// Initialize error logging
DownloadLogger::init();

// Set up headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \InvalidArgumentException('Invalid JSON input');
    }

    $downloader = new VideoDownloader();
    $downloader->cleanOldFiles();
    $downloader->download($input);

} catch (\Throwable $e) {
    DownloadLogger::error('Unexpected error', $e);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred: ' . $e->getMessage()
    ]);
}
