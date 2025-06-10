from flask import Flask, request, jsonify
import cv2
import dlib
import numpy as np
import base64

app = Flask(__name__)

# Load dlib's face detector and shape predictor.
detector = dlib.get_frontal_face_detector()
predictor = dlib.shape_predictor("shape_predictor_68_face_landmarks.dat")

# Track warnings per session
warnings = {}
MAX_WARNINGS = 3

@app.after_request
def add_cors_headers(response):
    response.headers['Access-Control-Allow-Origin'] = '*'
    response.headers['Access-Control-Allow-Methods'] = 'POST, GET, OPTIONS'
    response.headers['Access-Control-Allow-Headers'] = 'Content-Type'
    return response

@app.route('/', methods=['GET'])
def index():
    return "Flask server is running. Use the /track_eye_movement endpoint with a POST request."

# Updated detection function using face width ratio
def detect_eye_direction(shape):
    # Use landmarks 36 and 45 for left and right eyes, 30 for nose tip.
    left_eye = np.array([shape.part(36).x, shape.part(36).y])
    right_eye = np.array([shape.part(45).x, shape.part(45).y])
    nose_tip = np.array([shape.part(30).x, shape.part(30).y])
    
    # Compute the average eye center
    avg_eye = (left_eye + right_eye) / 2.0

    # Compute face width using jawline landmarks (0 and 16)
    face_width = abs(shape.part(16).x - shape.part(0).x)
    if face_width == 0:
        face_width = 1  # Prevent division by zero

    # Compute horizontal displacement ratio
    horizontal_displacement = abs(avg_eye[0] - nose_tip[0]) / face_width

    # If displacement ratio exceeds threshold (e.g., 0.15), consider it as looking away.
    if horizontal_displacement > 0.15:
        return True
    return False

# Compute the average eye center from both eyes.
def compute_eye_center(shape):
    # Left eye: landmarks 36-41
    left_eye_x = np.mean([shape.part(i).x for i in range(36, 42)])
    left_eye_y = np.mean([shape.part(i).y for i in range(36, 42)])
    # Right eye: landmarks 42-47
    right_eye_x = np.mean([shape.part(i).x for i in range(42, 48)])
    right_eye_y = np.mean([shape.part(i).y for i in range(42, 48)])
    center_x = int((left_eye_x + right_eye_x) / 2)
    center_y = int((left_eye_y + right_eye_y) / 2)
    return {"x": center_x, "y": center_y}

# Endpoint to track eye movement and give warnings
@app.route('/track_eye_movement', methods=['POST'])
def track_eye_movement():
    data = request.get_json()
    session_id = data.get('session_id')
    
    if not session_id:
        return jsonify({"error": "Session ID is required"}), 400
    
    # If maximum warnings already reached, notify auto submission
    if session_id in warnings and warnings[session_id] >= MAX_WARNINGS:
        return jsonify({"status": "exam_submitted", "message": "Exam auto-submitted due to multiple warnings"})
    
    if 'image' not in data:
        return jsonify({"error": "No image provided"}), 400

    img_data = data['image']
    if ',' in img_data:
        img_data = img_data.split(',')[1]

    try:
        img_bytes = base64.b64decode(img_data)
        nparr = np.frombuffer(img_bytes, np.uint8)
        img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
        if img is None:
            raise ValueError("Image decoding failed")
    except Exception as e:
        return jsonify({"error": str(e)}), 400

    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    faces = detector(gray)
    face_count = len(faces)
    response = {"face_count": face_count}

    if face_count == 1:
        shape = predictor(gray, faces[0])
        # Compute the eye center for front-end drawing
        eye_center = compute_eye_center(shape)
        response["eye_center"] = eye_center
        
        # Check if eyes are looking away using the updated detection function
        if detect_eye_direction(shape):
            warnings[session_id] = warnings.get(session_id, 0) + 1
            if warnings[session_id] >= MAX_WARNINGS:
                return jsonify({"status": "exam_submitted", "message": "Exam auto-submitted due to multiple warnings"})
            return jsonify({
                "warning": warnings[session_id],
                "message": "Warning! Please focus on the screen.",
                "eye_center": eye_center
            })
        
    return jsonify(response)

if __name__ == '__main__':
    app.run(port=5000)