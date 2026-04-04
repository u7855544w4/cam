#!/usr/bin/env python3
"""
Face Recognition Service for Laravel System
Real-time face detection and recognition using face_recognition library
"""

import os
import sys
import json
import base64
import numpy as np
from datetime import datetime
import sqlite3
from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS
import face_recognition
import cv2
from PIL import Image
import io

app = Flask(__name__)
CORS(app)

# Database path
DB_PATH = '/workspace/cam/database/database.sqlite'
IMAGES_DIR = '/workspace/cam/storage/app/public/people'

def get_db_connection():
    """Get database connection"""
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn

def load_known_faces():
    """Load all known faces from database"""
    conn = get_db_connection()
    people = conn.execute('SELECT * FROM people WHERE face_encoding IS NOT NULL').fetchall()
    conn.close()

    known_encodings = []
    known_ids = []
    known_names = []
    known_types = []
    known_images = []

    for person in people:
        try:
            if person['face_encoding']:
                encoding = json.loads(person['face_encoding'])
                known_encodings.append(np.array(encoding))
                known_ids.append(person['id'])
                known_names.append(person['name'])
                known_types.append(person['type'])
                known_images.append(person['image'])
        except:
            continue

    return known_encodings, known_ids, known_names, known_types, known_images

def encode_face(image_path):
    """Encode a face from image path"""
    try:
        image = face_recognition.load_image_file(image_path)
        encodings = face_recognition.face_encodings(image)
        
        if len(encodings) > 0:
            return encodings[0].tolist()
    except Exception as e:
        print(f"Error encoding face: {e}")
    
    return None

def recognize_face(face_encoding, known_encodings, known_ids, known_names, known_types, known_images):
    """Compare face with known faces"""
    if len(known_encodings) == 0:
        return None, 0

    matches = face_recognition.compare_faces(known_encodings, face_encoding, tolerance=0.5)
    face_distances = face_recognition.face_distance(known_encodings, face_encoding)

    if True in matches:
        best_match_index = np.argmin(face_distances)
        confidence = (1 - face_distances[best_match_index]) * 100
        
        return {
            'id': known_ids[best_match_index],
            'name': known_names[best_match_index],
            'type': known_types[best_match_index],
            'image': known_images[best_match_index],
            'confidence': round(confidence, 2)
        }, confidence

    return None, 0

@app.route('/health', methods=['GET'])
def health():
    """Health check endpoint"""
    return jsonify({'status': 'ok', 'service': 'face-recognition'})

@app.route('/api/encode', methods=['POST'])
def encode_face_api():
    """Encode a face from uploaded image"""
    if 'image' not in request.files:
        return jsonify({'error': 'No image provided'}), 400

    image_file = request.files['image']
    
    try:
        # Read image
        image = face_recognition.load_image_file(image_file)
        encodings = face_recognition.face_encodings(image)

        if len(encodings) == 0:
            return jsonify({'error': 'No face detected in image'}), 400

        return jsonify({
            'encoding': encodings[0].tolist(),
            'face_count': len(encodings)
        })

    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/search', methods=['POST'])
def search_by_image():
    """Search for a person by uploading their image"""
    if 'image' not in request.files:
        return jsonify({'error': 'No image provided'}), 400

    image_file = request.files['image']

    try:
        # Load and encode the uploaded image
        image = face_recognition.load_image_file(image_file)
        face_encodings = face_recognition.face_encodings(image)

        if len(face_encodings) == 0:
            return jsonify({
                'found': False,
                'message': 'لم يتم اكتشاف وجه في الصورة'
            })

        # Load known faces
        known_encodings, known_ids, known_names, known_types, known_images = load_known_faces()

        # Try to recognize
        result, confidence = recognize_face(
            face_encodings[0],
            known_encodings, known_ids, known_names, known_types, known_images
        )

        if result:
            return jsonify({
                'found': True,
                'person': result,
                'confidence': confidence
            })
        else:
            return jsonify({
                'found': False,
                'message': 'الشخص غير موجود في قاعدة البيانات'
            })

    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/detect', methods=['POST'])
def detect_face():
    """Detect faces in uploaded image (for adding new person)"""
    if 'image' not in request.files:
        return jsonify({'error': 'No image provided'}), 400

    image_file = request.files['image']

    try:
        # Load and detect faces
        image = face_recognition.load_image_file(image_file)
        face_locations = face_recognition.face_locations(image)
        face_encodings = face_recognition.face_encodings(image, face_locations)

        return jsonify({
            'faces_found': len(face_encodings),
            'encodings': [enc.tolist() for enc in face_encodings],
            'locations': face_locations
        })

    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/process-frame', methods=['POST'])
def process_frame():
    """Process a video frame for face detection"""
    data = request.json
    
    if 'image' not in data:
        return jsonify({'error': 'No image data provided'}), 400

    try:
        # Decode base64 image
        image_data = base64.b64decode(data['image'])
        image = face_recognition.load_image_file(io.BytesIO(image_data))
        
        # Detect faces
        face_locations = face_recognition.face_locations(image)
        face_encodings = face_recognition.face_encodings(image, face_locations)

        # Load known faces
        known_encodings, known_ids, known_names, known_types, known_images = load_known_faces()

        results = []
        
        for i, face_encoding in enumerate(face_encodings):
            result, confidence = recognize_face(
                face_encoding,
                known_encodings, known_ids, known_names, known_types, known_images
            )
            
            top, right, bottom, left = face_locations[i]
            
            if result:
                results.append({
                    'recognized': True,
                    'person': result,
                    'confidence': confidence,
                    'location': {'top': top, 'right': right, 'bottom': bottom, 'left': left}
                })
            else:
                results.append({
                    'recognized': False,
                    'location': {'top': top, 'right': right, 'bottom': bottom, 'left': left}
                })

        return jsonify({
            'faces_detected': len(results),
            'results': results
        })

    except Exception as e:
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    print("Starting Face Recognition Service...")
    print("Loading known faces...")
    load_known_faces()
    print("Service ready!")
    app.run(host='0.0.0.0', port=5000, debug=True)