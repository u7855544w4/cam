<?php
/**
 * Camera Stream Test Endpoint
 * Tests camera connection and returns detailed status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get camera ID from request
$cameraId = $_GET['camera_id'] ?? null;

if (!$cameraId) {
    echo json_encode([
        'success' => false,
        'message' => 'camera_id is required'
    ]);
    exit;
}

$camera = \App\Models\Camera::find($cameraId);

if (!$camera) {
    echo json_encode([
        'success' => false,
        'message' => 'Camera not found'
    ]);
    exit;
}

$rtspUrl = $camera->rtsp_url;

if (empty($rtspUrl)) {
    echo json_encode([
        'success' => false,
        'message' => 'No RTSP URL configured',
        'camera' => $camera
    ]);
    exit;
}

// Test with Python script
$command = sprintf(
    'cd /workspace/project/cam/python-service && python3 test_stream.py "%s" 2>&1',
    escapeshellarg($rtspUrl)
);

$output = shell_exec($command);
$result = json_decode($output, true);

if ($result && $result['success']) {
    echo json_encode([
        'success' => true,
        'online' => true,
        'message' => "Connected! {$result['codec']} ({$result['width']}x{$result['height']})",
        'codec' => $result['codec'] ?? 'unknown',
        'resolution' => "{$result['width']}x{$result['height']}",
        'rtsp_url' => $rtspUrl,
        'camera' => $camera
    ]);
} else {
    echo json_encode([
        'success' => false,
        'online' => false,
        'message' => $result['error'] ?? 'Cannot connect to camera',
        'rtsp_url' => $rtspUrl,
        'camera' => $camera
    ]);
}