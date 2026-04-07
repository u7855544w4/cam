#!/usr/bin/env python3
"""
Video Streaming Service
Converts RTSP camera streams to HLS for web playback
"""

import os
import subprocess
import threading
import time
import json
from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS
import shutil

app = Flask(__name__)
CORS(app)

# Configuration
STREAM_DIR = '/workspace/cam/storage/stream'
FFMPEG_PATH = '/usr/bin/ffmpeg'

# Ensure stream directory exists
os.makedirs(STREAM_DIR, exist_ok=True)

# Active streams
active_streams = {}

def convert_rtsp_to_hls(camera_id, rtsp_url, name, username=None, password=None):
    """Convert RTSP stream to HLS"""
    output_dir = os.path.join(STREAM_DIR, str(camera_id))
    os.makedirs(output_dir, exist_ok=True)
    
    playlist = os.path.join(output_dir, 'playlist.m3u8')
    segment = os.path.join(output_dir, 'segment%03d.ts')
    
    # Build FFmpeg command
    cmd = [
        FFMPEG_PATH,
        '-rtsp_transport', 'tcp',
        '-i', rtsp_url,
        '-c:v', 'libx264',
        '-preset', 'ultrafast',
        '-tune', 'zerolatency',
        '-b:v', '2000k',
        '-b:a', '64k',
        '-hls_time', '2',
        '-hls_list_size', '3',
        '-hls_flags', 'delete_segments',
        '-start_number', '1',
        '-f', 'hls',
        '-y',
        playlist
    ]
    
    # Add re-connection options for stability
    cmd.extend(['-reconnect', '1', '-reconnect_streamed', '1', '-reconnect_delay_max', '5'])
    
    try:
        process = subprocess.Popen(
            cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE
        )
        
        # Wait a moment to check if FFmpeg starts successfully
        time.sleep(2)
        
        # Check if process is still running
        if process.poll() is not None:
            stdout, stderr = process.communicate()
            print(f"FFmpeg error: {stderr.decode() if stderr else 'Unknown error'}")
            return False
        
        active_streams[camera_id] = {
            'process': process,
            'rtsp_url': rtsp_url,
            'name': name,
            'started_at': time.time()
        }
        
        return True
    except Exception as e:
        print(f"Error starting stream: {e}")
        return False

def stop_stream(camera_id):
    """Stop a stream"""
    if camera_id in active_streams:
        active_streams[camera_id]['process'].terminate()
        del active_streams[camera_id]
        return True
    return False

@app.route('/health', methods=['GET'])
def health():
    """Health check"""
    return jsonify({
        'status': 'ok',
        'service': 'video-streaming',
        'active_streams': len(active_streams)
    })

@app.route('/api/start', methods=['POST'])
def start_stream():
    """Start streaming a camera"""
    data = request.json
    
    camera_id = data.get('camera_id')
    rtsp_url = data.get('rtsp_url')
    name = data.get('name', f'Camera {camera_id}')
    username = data.get('username')
    password = data.get('password')
    
    if not camera_id or not rtsp_url:
        return jsonify({'error': 'camera_id and rtsp_url required'}), 400
    
    # Check if already streaming
    if camera_id in active_streams:
        return jsonify({
            'success': True,
            'message': 'Already streaming',
            'stream_url': f'/stream/{camera_id}/playlist.m3u8'
        })
    
    # Start stream
    success = convert_rtsp_to_hls(camera_id, rtsp_url, name, username, password)
    
    if success:
        return jsonify({
            'success': True,
            'message': 'Stream started',
            'stream_url': f'/stream/{camera_id}/playlist.m3u8'
        })
    
    return jsonify({'error': 'Failed to start stream. Check if camera is online and RTSP URL is correct.'}), 500

@app.route('/api/stop', methods=['POST'])
def stop():
    """Stop streaming"""
    data = request.json
    camera_id = data.get('camera_id')
    
    if not camera_id:
        return jsonify({'error': 'camera_id required'}), 400
    
    if stop_stream(camera_id):
        return jsonify({'success': True, 'message': 'Stream stopped'})
    
    return jsonify({'error': 'Stream not found'}), 404

@app.route('/api/status', methods=['GET'])
def status():
    """Get all streams status"""
    streams = []
    for camera_id, info in active_streams.items():
        streams.append({
            'camera_id': camera_id,
            'name': info['name'],
            'uptime': int(time.time() - info['started_at']),
            'stream_url': f'/stream/{camera_id}/playlist.m3u8'
        })
    
    return jsonify({'streams': streams})

@app.route('/stream/<path:filename>', methods=['GET'])
def serve_stream(filename):
    """Serve HLS files"""
    # Extract camera_id from path
    parts = filename.split('/')
    if parts:
        camera_id = parts[0]
        return send_from_directory(os.path.join(STREAM_DIR, camera_id), '/'.join(parts[1:]))
    
    return send_from_directory(STREAM_DIR, filename)

if __name__ == '__main__':
    print("Starting Video Streaming Service on port 5001...")
    app.run(host='0.0.0.0', port=5001, debug=False)