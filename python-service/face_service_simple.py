#!/usr/bin/env python3
"""
Face Recognition Service for Laravel System
Using OpenCV for face detection and recognition
"""

import os
import sys
import json
import base64
import numpy as np
from datetime import datetime
import sqlite3
from flask import Flask, request, jsonify
from flask_cors import CORS
import cv2
import io

app = Flask(__name__)
CORS(app)

# Database path
DB_PATH = '/workspace/cam/database/database.sqlite'
IMAGES_DIR = '/workspace/cam/storage/app/public/people'
LARAVEL_API = 'http://localhost:8000/api'

def get_db_connection():
    """Get database connection"""
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn

def encode_face_from_image(image_data):
    """Encode face from image data using OpenCV"""
    try:
        # Convert base64 to image
        img_bytes = base64.b64decode(image_data)
        nparr = np.frombuffer(img_bytes, np.uint8)
        img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
        
        if img is None:
            return None
            
        # Convert to RGB
        rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
        
        # Use Haar Cascade for detection
        face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
        faces = face_cascade.detectMultiScale(rgb, 1.1, 4)
        
        if len(faces) == 0:
            return None
            
        # Get the largest face
        x, y, w, h = max(faces, key=lambda f: f[2] * f[3])
        face = rgb[y:y+h, x:x+w]
        
        # Resize to standard size
        face = cv2.resize(face, (128, 128))
        
        # Create encoding (flatten the face)
        encoding = face.flatten().tolist()
        
        return encoding
    except Exception as e:
        print(f"Error encoding face: {e}")
        return None

def compare_faces(encoding1, encoding2, threshold=0.7):
    """Compare two face encodings"""
    try:
        arr1 = np.array(encoding1)
        arr2 = np.array(encoding2)
        
        # Normalize
        arr1 = arr1 / np.linalg.norm(arr1)
        arr2 = arr2 / np.linalg.norm(arr2)
        
        # Calculate similarity
        similarity = np.dot(arr1, arr2)
        
        return similarity >= threshold, similarity * 100
    except:
        return False, 0

def load_known_faces():
    """Load all known faces from database"""
    conn = get_db_connection()
    people = conn.execute('SELECT * FROM people WHERE face_encoding IS NOT NULL').fetchall()
    conn.close()
    
    known = []
    for person in people:
        try:
            if person['face_encoding']:
                encoding = json.loads(person['face_encoding'])
                known.append({
                    'id': person['id'],
                    'name': person['name'],
                    'type': person['type'],
                    'image': person['image'],
                    'encoding': encoding
                })
        except:
            continue
    
    return known

def recognize_face(face_encoding):
    """Compare face with known faces"""
    known_faces = load_known_faces()
    
    if len(known_faces) == 0:
        return None, 0
    
    best_match = None
    best_confidence = 0
    
    for person in known_faces:
        is_match, confidence = compare_faces(face_encoding, person['encoding'])
        if is_match and confidence > best_confidence:
            best_match = person
            best_confidence = confidence
    
    if best_match:
        return best_match, best_confidence
    
    return None, 0

@app.route('/health', methods=['GET'])
def health():
    """Health check endpoint"""
    return jsonify({'status': 'ok', 'service': 'face-recognition'})

@app.route('/api/detect', methods=['POST'])
def detect_face():
    """Detect and recognize faces from image"""
    if 'image' not in request.files:
        # Check if base64
        if request.is_json and 'image' in request.json:
            image_data = request.json['image']
        else:
            return jsonify({'error': 'No image provided'}), 400
    else:
        file = request.files['image']
        image_data = base64.b64encode(file.read()).decode('utf-8')
    
    # Encode the face
    encoding = encode_face_from_image(image_data)
    
    if encoding is None:
        return jsonify({'faces_found': 0, 'message': 'No face detected'})
    
    # Try to recognize
    person, confidence = recognize_face(encoding)
    
    return jsonify({
        'faces_found': 1,
        'encodings': [encoding],
        'recognized': person is not None,
        'person': person,
        'confidence': confidence
    })

@app.route('/api/encode', methods=['POST'])
def encode_image():
    """Encode a single face from image"""
    if 'image' not in request.files:
        if request.is_json and 'image' in request.json:
            image_data = request.json['image']
        else:
            return jsonify({'error': 'No image provided'}), 400
    else:
        file = request.files['image']
        image_data = base64.b64encode(file.read()).decode('utf-8')
    
    encoding = encode_face_from_image(image_data)
    
    if encoding is None:
        return jsonify({'success': False, 'message': 'No face detected'})
    
    return jsonify({'success': True, 'encoding': encoding})

@app.route('/api/recognize', methods=['POST'])
def recognize():
    """Recognize a face from encoding"""
    data = request.json
    if not data or 'encoding' not in data:
        return jsonify({'error': 'No encoding provided'}), 400
    
    person, confidence = recognize_face(data['encoding'])
    
    if person:
        return jsonify({
            'recognized': True,
            'person': person,
            'confidence': confidence
        })
    
    return jsonify({
        'recognized': False,
        'message': 'No matching face found'
    })

@app.route('/api/search', methods=['POST'])
def search_by_image():
    """Search for a person by uploading an image"""
    if 'image' not in request.files:
        if request.is_json and 'image' in request.json:
            image_data = request.json['image']
        else:
            return jsonify({'error': 'No image provided'}), 400
    else:
        file = request.files['image']
        image_data = base64.b64encode(file.read()).decode('utf-8')
    
    # Encode the face
    encoding = encode_face_from_image(image_data)
    
    if encoding is None:
        return jsonify({'success': False, 'message': 'No face detected in image'})
    
    # Load all detections
    conn = get_db_connection()
    detections = conn.execute('''
        SELECT d.*, p.name as person_name, p.type as person_type, p.image as person_image, c.name as camera_name
        FROM detections d
        JOIN people p ON d.person_id = p.id
        JOIN cameras c ON d.camera_id = c.id
        ORDER BY d.detected_at DESC
        LIMIT 100
    ''').fetchall()
    conn.close()
    
    # For now, return recent detections without face matching
    # In production, you'd compare the encoding with stored encodings
    results = []
    for d in detections:
        results.append({
            'id': d['id'],
            'person_id': d['person_id'],
            'person_name': d['person_name'],
            'person_type': d['person_type'],
            'camera_name': d['camera_name'],
            'detected_at': d['detected_at'],
            'confidence': d['confidence']
        })
    
    return jsonify({
        'success': True,
        'encoding_provided': True,
        'results': results[:10]
    })

if __name__ == '__main__':
    print("Starting Face Recognition Service on port 5000...")
    print("OpenCV version:", cv2.__version__)
    app.run(host='0.0.0.0', port=5000, debug=True)