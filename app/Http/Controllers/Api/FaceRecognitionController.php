<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaceRecognitionController extends Controller
{
    protected $pythonServiceUrl;
    
    public function __construct()
    {
        $this->pythonServiceUrl = config('services.face_recognition.url', 'http://localhost:5000');
    }
    
    /**
     * Detect and recognize face from uploaded image
     */
    public function detect(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif'
        ]);
        
        try {
            $image = file_get_contents($request->file('image')->getRealPath());
            $base64Image = base64_encode($image);
            
            $response = Http::timeout(30)->post("{$this->pythonServiceUrl}/api/detect", [
                'image' => $base64Image
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // If face recognized, save to detections
                if (isset($data['recognized']) && $data['recognized'] && isset($data['person'])) {
                    $personId = $data['person']['id'];
                    $cameraId = $request->input('camera_id', 1);
                    $confidence = $data['confidence'];
                    
                    // Save detection
                    $detection = \App\Models\Detection::create([
                        'person_id' => $personId,
                        'camera_id' => $cameraId,
                        'confidence' => $confidence,
                        'detected_at' => now()
                    ]);
                    
                    $data['detection_id'] = $detection->id;
                }
                
                return response()->json($data);
            }
            
            return response()->json([
                'error' => 'Face recognition service error',
                'message' => $response->body()
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Face detection error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Detection failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Encode a face from image (for registering new person)
     */
    public function encode(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif'
        ]);
        
        try {
            $image = file_get_contents($request->file('image')->getRealPath());
            $base64Image = base64_encode($image);
            
            $response = Http::timeout(30)->post("{$this->pythonServiceUrl}/api/encode", [
                'image' => $base64Image
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return response()->json([
                'error' => 'Encoding failed',
                'message' => $response->body()
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Face encoding error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Encoding failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Recognize a face from existing encoding
     */
    public function recognize(Request $request)
    {
        $request->validate([
            'encoding' => 'required|array'
        ]);
        
        try {
            $response = Http::timeout(30)->post("{$this->pythonServiceUrl}/api/recognize", [
                'encoding' => $request->input('encoding')
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return response()->json([
                'error' => 'Recognition failed',
                'message' => $response->body()
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Face recognition error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Recognition failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Search by uploading an image
     */
    public function search(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif'
        ]);
        
        try {
            $image = file_get_contents($request->file('image')->getRealPath());
            $base64Image = base64_encode($image);
            
            $response = Http::timeout(30)->post("{$this->pythonServiceUrl}/api/search", [
                'image' => $base64Image
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return response()->json([
                'error' => 'Search failed',
                'message' => $response->body()
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Face search error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Search failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Health check for face recognition service
     */
    public function health()
    {
        try {
            $response = Http::timeout(5)->get("{$this->pythonServiceUrl}/health");
            
            if ($response->successful()) {
                return response()->json(array_merge(
                    $response->json(),
                    ['laravel_connected' => true]
                ));
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Face service unreachable'
            ], 503);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 503);
        }
    }
}