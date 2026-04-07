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

        // Try to connect to the camera stream
        // This is a basic check - in production you'd use FFprobe or similar
        $command = sprintf(
            'timeout 5 ffprobe -v error -show_entries stream=codec_name -of default=noprint_wrappers=1:nokey=1 "%s" 2>&1',
            escapeshellarg($rtspUrl)
        );
        
        $output = shell_exec($command);
        $isOnline = !empty($output) && strpos($output, 'h264') !== false;
        
        return response()->json([
            'success' => $isOnline,
            'online' => $isOnline,
            'message' => $isOnline ? 'الكاميرا متصلة وتعمل' : 'لا يمكن الاتصال بالكاميرا',
            'rtsp_url' => $rtspUrl,
            'codec' => trim($output),
            'camera' => $camera,
        ]);
    }
}