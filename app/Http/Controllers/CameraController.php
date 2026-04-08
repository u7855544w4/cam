<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use Illuminate\Http\Request;

class CameraController extends Controller
{
    public function index()
    {
        $cameras = Camera::with('nvr')->latest()->paginate(10);
        
        // Add rtsp_url to each camera
        $cameras->getCollection()->transform(function ($camera) {
            $camera->rtsp_url = $camera->rtsp_url;
            return $camera;
        });
        
        return response()->json($cameras);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nvr_id' => 'nullable|exists:nvrs,id',
            'name' => 'required|string|max:255',
            'ip' => 'nullable|string|max:255',
            'channel' => 'nullable|integer|min:1',
            'rtsp_url' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
        ]);

        $camera = Camera::create($validated);

        return response()->json([
            'message' => 'Camera created successfully',
            'camera' => $camera,
        ], 201);
    }

    public function show(Camera $camera)
    {
        return response()->json($camera->load(['detections', 'nvr']));
    }

    public function update(Request $request, Camera $camera)
    {
        $validated = $request->validate([
            'nvr_id' => 'nullable|exists:nvrs,id',
            'name' => 'sometimes|string|max:255',
            'ip' => 'nullable|string|max:255',
            'channel' => 'nullable|integer|min:1',
            'rtsp_url' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $camera->update($validated);

        return response()->json([
            'message' => 'Camera updated successfully',
            'camera' => $camera,
        ]);
    }

    public function destroy(Camera $camera)
    {
        $camera->delete();

        return response()->json(['message' => 'Camera deleted successfully']);
    }

    public function stream(Camera $camera)
    {
        // Return RTSP URL or HLS stream URL for the frontend
        return response()->json([
            'camera' => $camera,
            'stream_url' => $camera->rtsp_url,
            'rtsp_url' => $camera->rtsp_url,
            'nvr' => $camera->nvr,
        ]);
    }

    public function testConnection(Camera $camera)
    {
        $rtspUrl = $camera->rtsp_url;
        
        if (empty($rtspUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد رابط RTSP',
                'camera' => $camera,
            ], 400);
        }

        // Use Python script to test stream
        $command = sprintf(
            'cd /workspace/project/cam/python-service && python3 test_stream.py "%s" 2>&1',
            escapeshellarg($rtspUrl)
        );
        
        $output = shell_exec($command);
        
        // Parse JSON output
        $result = json_decode($output, true);
        
        if ($result && $result['success']) {
            return response()->json([
                'success' => true,
                'online' => true,
                'message' => "الكاميرا متصلة! {$result['codec']} ({$result['width']}x{$result['height']})",
                'codec' => $result['codec'] ?? 'unknown',
                'resolution' => "{$result['width']}x{$result['height']}",
                'rtsp_url' => $rtspUrl,
                'camera' => $camera,
            ]);
        }
        
        $errorMsg = $result['error'] ?? 'لا يمكن الاتصال بالكاميرا';
        return response()->json([
            'success' => false,
            'online' => false,
            'message' => $errorMsg,
            'rtsp_url' => $rtspUrl,
            'camera' => $camera,
        ]);
    }
}