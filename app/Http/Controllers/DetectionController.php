<?php

namespace App\Http\Controllers;

use App\Models\Detection;
use App\Models\Person;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DetectionController extends Controller
{
    public function index(Request $request)
    {
        $query = Detection::with(['person', 'camera']);

        if ($request->has('person_id')) {
            $query->where('person_id', $request->person_id);
        }

        if ($request->has('camera_id')) {
            $query->where('camera_id', $request->camera_id);
        }

        if ($request->has('date')) {
            $query->whereDate('detected_at', $request->date);
        }

        $detections = $query->latest('detected_at')->paginate(20);

        return response()->json($detections);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'person_id' => 'required|exists:people,id',
            'camera_id' => 'required|exists:cameras,id',
            'snapshot' => 'nullable|string',
            'confidence' => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['detected_at'] = now();

        $detection = Detection::create($validated);

        // Load relationships for response
        $detection->load(['person', 'camera']);

        // Broadcast event for real-time notification
        event(new \App\Events\FaceDetected($detection));

        return response()->json([
            'message' => 'Detection recorded successfully',
            'detection' => $detection,
        ], 201);
    }

    public function show(Detection $detection)
    {
        return response()->json($detection->load(['person', 'camera']));
    }

    public function today()
    {
        $detections = Detection::with(['person', 'camera'])
            ->whereDate('detected_at', Carbon::today())
            ->latest('detected_at')
            ->paginate(20);

        return response()->json($detections);
    }

    public function statistics()
    {
        $today = Carbon::today();
        $weekAgo = Carbon::now()->subWeek();

        return response()->json([
            'today_detections' => Detection::whereDate('detected_at', $today)->count(),
            'week_detections' => Detection::where('detected_at', '>=', $weekAgo)->count(),
            'unique_people_today' => Detection::whereDate('detected_at', $today)
                ->distinct('person_id')
                ->count('person_id'),
            'active_cameras' => \App\Models\Camera::where('is_active', true)->count(),
        ]);
    }
}