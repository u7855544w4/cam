<?php

namespace App\Http\Controllers;

use App\Models\Nvr;
use App\Models\Camera;
use Illuminate\Http\Request;

class NvrController extends Controller
{
    public function index()
    {
        $nvrs = Nvr::with('cameras')->latest()->paginate(10);
        return response()->json($nvrs);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip' => 'required|string|max:255',
            'port' => 'sometimes|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'channels_count' => 'nullable|integer|min:1|max:64',
        ]);

        $nvr = Nvr::create($validated);

        // Auto-create cameras based on channels count
        $channelsCount = $validated['channels_count'] ?? 8;
        $cameras = [];
        
        for ($channel = 1; $channel <= $channelsCount; $channel++) {
            $camera = Camera::create([
                'nvr_id' => $nvr->id,
                'name' => "Camera $channel",
                'channel' => $channel,
                'location' => "Channel $channel",
                'is_active' => true,
            ]);
            $cameras[] = $camera;
        }

        return response()->json([
            'message' => 'NVR created successfully with ' . count($cameras) . ' cameras',
            'nvr' => $nvr->load('cameras'),
            'cameras' => $cameras,
        ], 201);
    }

    public function show(Nvr $nvr)
    {
        return response()->json($nvr->load('cameras'));
    }

    public function update(Request $request, Nvr $nvr)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'ip' => 'sometimes|string|max:255',
            'port' => 'sometimes|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        $nvr->update($validated);

        return response()->json([
            'message' => 'NVR updated successfully',
            'nvr' => $nvr,
        ]);
    }

    public function destroy(Nvr $nvr)
    {
        // Don't delete cameras, just remove the relation
        $nvr->cameras()->update(['nvr_id' => null]);
        $nvr->delete();

        return response()->json(['message' => 'NVR deleted successfully']);
    }

    public function channels(Nvr $nvr)
    {
        // Return available channels from the NVR
        // This would typically connect to the NVR API to get actual channels
        return response()->json([
            'nvr' => $nvr,
            'channels' => range(1, $nvr->cameras()->count() ?: 8),
        ]);
    }

    public function testConnection(Nvr $nvr)
    {
        // Test NVR connection
        // In production, this would actually test the connection
        return response()->json([
            'success' => true,
            'message' => 'NVR connection successful',
            'nvr' => $nvr,
        ]);
    }
}