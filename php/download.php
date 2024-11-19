<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use YoutubeDl\YoutubeDl;
use YoutubeDl\Options;
use YoutubeDl\Exception\CopyrightException;
use YoutubeDl\Exception\NotFoundException;
use YoutubeDl\Exception\PrivateVideoException;
use Symfony\Component\Process\ExecutableFinder;

header('Content-Type: application/json');

// Enable output buffering for progress updates
ob_start();

// Ensure directories exist
if (!file_exists(DOWNLOAD_PATH)) {
    mkdir(DOWNLOAD_PATH, 0755, true);
}
if (!file_exists(TEMP_PATH)) {
    mkdir(TEMP_PATH, 0755, true);
}

// Clean old files
cleanOldFiles();

function cleanOldFiles() {
    $files = glob(DOWNLOAD_PATH . '/*');
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= FILE_LIFETIME) {
                unlink($file);
            }
        }
    }
}

function sanitizeFilename($filename) {
    // Remove any character that isn't a letter, number, dot, hyphen or underscore
    $filename = preg_replace('/[^\w\-\.]/', '_', $filename);
    // Remove multiple underscores
    $filename = preg_replace('/_+/', '_', $filename);
    return $filename;
}

function sendProgress($status, $percentage) {
    echo "data: " . json_encode([
        'status' => $status,
        'percentage' => $percentage
    ]) . "\n\n";
    ob_flush();
    flush();
}

function downloadVideo() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['url']) || !isset($data['format'])) {
            throw new Exception('Missing required parameters');
        }

        if (!array_key_exists($data['format'], ALLOWED_FORMATS)) {
            throw new Exception('Invalid format selected');
        }

        $format = ALLOWED_FORMATS[$data['format']];
        
        // Initialize youtube-dl
        $dl = new YoutubeDl();
        
        // Create Options object using the factory method
        $options = Options::create()
            ->downloadOptions()
                ->enableContinue()
                ->disablePlaylist()
                ->output(TEMP_PATH . '/%(title)s.%(ext)s')
            ->end();

        // Set format-specific options
        if ($format['format'] === 'mp4') {
            $options->downloadOptions()
                ->format('bestvideo[height<=' . str_replace('p', '', $format['quality']) . ']+bestaudio/best[height<=' . str_replace('p', '', $format['quality']) . ']')
                ->end();
        } else {
            $options->downloadOptions()
                ->format('bestaudio/best')
                ->extractAudio()
                ->audioFormat('mp3')
                ->audioQuality($format['quality'])
                ->end();
        }

        // Configure progress callback
        $dl->onProgress(function ($progress) {
            $status = 'downloading';
            $percentage = 0;
            
            // Extract progress information
            if (isset($progress['total_bytes']) && isset($progress['downloaded_bytes'])) {
                $percentage = round(($progress['downloaded_bytes'] / $progress['total_bytes']) * 100);
                
                if ($percentage < 30) {
                    $status = 'Downloading video...';
                } else if ($percentage < 60) {
                    $status = 'Converting format...';
                } else if ($percentage < 90) {
                    $status = 'Preparing file...';
                } else {
                    $status = 'Almost done...';
                }
            } else if (isset($progress['downloading']) && $progress['downloading']) {
                $status = 'Starting download...';
            } else if (isset($progress['converting']) && $progress['converting']) {
                $status = 'Converting...';
                $percentage = isset($progress['converting_progress']) ? $progress['converting_progress'] : 50;
            }
            
            sendProgress($status, $percentage);
        });

        // Download the video
        $collection = $dl->download(
            $options,
            [$data['url']]
        );

        if ($collection->count() === 0) {
            throw new Exception('Failed to download video');
        }

        $video = $collection->first();
        $originalFile = $video->getFile();

        if (!$originalFile || !file_exists($originalFile)) {
            throw new Exception('Failed to get downloaded file');
        }

        $filename = sanitizeFilename(pathinfo($originalFile, PATHINFO_FILENAME));
        $extension = $format['format'];
        
        $finalFilename = $filename . '.' . $extension;
        $finalPath = DOWNLOAD_PATH . '/' . $finalFilename;

        // Move file to downloads directory
        if (!rename($originalFile, $finalPath)) {
            throw new Exception('Failed to move downloaded file');
        }

        // Send completion progress
        sendProgress('Complete', 100);

        return [
            'success' => true,
            'file' => 'downloads/' . $finalFilename,
            'filename' => $finalFilename
        ];

    } catch (NotFoundException $e) {
        return ['success' => false, 'message' => 'Video not found'];
    } catch (PrivateVideoException $e) {
        return ['success' => false, 'message' => 'This video is private'];
    } catch (CopyrightException $e) {
        return ['success' => false, 'message' => 'This video is copyright protected'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

echo json_encode(downloadVideo());
