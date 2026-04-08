#!/usr/bin/env python3
"""
Simple Stream Tester
Tests camera RTSP streams and reports status
"""

import sys
import subprocess
import json
import re

def test_stream(rtsp_url, timeout=5):
    """Test if an RTSP stream is accessible"""
    cmd = [
        'ffprobe',
        '-v', 'quiet',
        '-print_format', 'json',
        '-show_streams',
        '-rtsp_transport', 'tcp',
        rtsp_url
    ]
    
    try:
        result = subprocess.run(
            cmd,
            capture_output=True,
            text=True,
            timeout=timeout
        )
        
        if result.returncode == 0:
            # Parse output
            data = json.loads(result.stdout)
            streams = data.get('streams', [])
            
            video_stream = None
            for stream in streams:
                if stream.get('codec_type') == 'video':
                    video_stream = stream
                    break
            
            if video_stream:
                return {
                    'success': True,
                    'online': True,
                    'codec': video_stream.get('codec_name'),
                    'width': video_stream.get('width'),
                    'height': video_stream.get('height'),
                    'fps': video_stream.get('r_frame_rate'),
                }
        
        return {
            'success': False,
            'online': False,
            'error': result.stderr or 'Unknown error'
        }
        
    except subprocess.TimeoutExpired:
        return {
            'success': False,
            'online': False,
            'error': 'Connection timeout'
        }
    except FileNotFoundError:
        return {
            'success': False,
            'online': False,
            'error': 'ffprobe not found. Install ffmpeg.'
        }
    except Exception as e:
        return {
            'success': False,
            'online': False,
            'error': str(e)
        }

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print("Usage: python3 test_stream.py <rtsp_url>")
        sys.exit(1)
    
    rtsp_url = sys.argv[1]
    result = test_stream(rtsp_url)
    print(json.dumps(result, indent=2))