<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PersonController extends Controller
{
    public function index()
    {
        $people = Person::latest()->paginate(10);
        return response()->json($people);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:employee,visitor,citizen',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $imagePath = $request->file('image')->store('people', 'public');

        // Call Python service to encode face
        $fullPath = storage_path('app/public/' . $imagePath);
        $faceEncoding = null;
        
        $pythonHost = env('PYTHON_SERVICE_HOST', '127.0.0.1');
        $pythonPort = env('PYTHON_SERVICE_PORT', 5000);
        
        try {
            $client = new \GuzzleHttp\Client([
                'base_uri' => "http://{$pythonHost}:{$pythonPort}",
                'timeout' => 30,
            ]);

            $response = $client->post('/api/detect', [
                'multipart' => [
                    [
                        'name' => 'image',
                        'contents' => fopen($fullPath, 'r'),
                    ],
                ],
            ]);

            $result = json_decode($response->getBody(), true);
            
            if ($result && $result['faces_found'] > 0 && isset($result['encodings'][0])) {
                $faceEncoding = json_encode($result['encodings'][0]);
            }
        } catch (\Exception $e) {
            // Continue without face encoding if service is not available
            \Log::warning('Face encoding service not available: ' . $e->getMessage());
        }

        $person = Person::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'image' => $imagePath,
            'face_encoding' => $faceEncoding,
        ]);

        return response()->json([
            'message' => 'Person created successfully',
            'person' => $person,
        ], 201);
    }

    public function show(Person $person)
    {
        return response()->json($person->load('detections'));
    }

    public function update(Request $request, Person $person)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:employee,visitor,citizen',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($person->image) {
                Storage::disk('public')->delete($person->image);
            }
            $validated['image'] = $request->file('image')->store('people', 'public');
        }

        $person->update($validated);

        return response()->json([
            'message' => 'Person updated successfully',
            'person' => $person,
        ]);
    }

    public function destroy(Person $person)
    {
        if ($person->image) {
            Storage::disk('public')->delete($person->image);
        }
        $person->delete();

        return response()->json(['message' => 'Person deleted successfully']);
    }

    public function searchByImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Save temp image for Python service to process
        $tempPath = $request->file('image')->store('temp', 'public');

        // Call Python face detection service
        $pythonHost = env('PYTHON_SERVICE_HOST', '127.0.0.1');
        $pythonPort = env('PYTHON_SERVICE_PORT', 5000);

        try {
            $client = new \GuzzleHttp\Client([
                'base_uri' => "http://{$pythonHost}:{$pythonPort}",
                'timeout' => 30,
            ]);

            $response = $client->post('/api/search', [
                'multipart' => [
                    [
                        'name' => 'image',
                        'contents' => fopen(storage_path('app/public/' . $tempPath), 'r'),
                    ],
                ],
            ]);

            $results = json_decode($response->getBody(), true);

            // Clean up temp file
            Storage::disk('public')->delete($tempPath);

            return response()->json($results);
        } catch (\Exception $e) {
            Storage::disk('public')->delete($tempPath);
            return response()->json([
                'error' => 'Face search service is not available',
                'message' => $e->getMessage(),
            ], 503);
        }
    }
}