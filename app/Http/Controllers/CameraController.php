<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use Illuminate\Http\Request;

class CameraController extends Controller
{
    public function index()
    {
        $cameras = Camera::with('nvr')->latest()->paginate(10);
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
}