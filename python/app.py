from flask import Flask, request, jsonify, make_response
import cv2
import dlib
import numpy as np
import base64

app = Flask(__name__)

# Load dlib's face detector and shape predictor.
detector = dlib.get_frontal_face_detector()
predictor = dlib.shape_predictor("shape_predictor_68_face_landmarks.dat")

@app.after_request
def add_cors_headers(response):
    response.headers['Access-Control-Allow-Origin'] = '*'
    response.headers['Access-Control-Allow-Methods'] = 'POST, GET, OPTIONS'
    response.headers['Access-Control-Allow-Headers'] = 'Content-Type'
    return response

@app.route('/', methods=['GET'])
def index():
    return "Flask server is running. Use the /track_eye_movement endpoint with a POST request."

@app.route('/track_eye_movement', methods=['POST', 'OPTIONS'])
def track_eye_movement():
    if request.method == 'OPTIONS':
        return make_response('', 200)
    
    data = request.get_json()
    session_id = data.get('session_id')
    if not session_id:
        return jsonify({"error": "Session ID is required"}), 400
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

    # Convert to grayscale.
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    
    # Try initial face detection.
    faces = detector(gray)
    
    # If no faces found, try histogram equalization.
    if len(faces) == 0:
        eq_gray = cv2.equalizeHist(gray)
        faces = detector(eq_gray)
    
    if (len(faces) == 0):
        faces = detector(gray, 1)
    if (len(faces) == 0):
        faces = detector(gray, 2)
    
    
    if len(faces) == 0:
        violation = "no_face_detected"
    elif len(faces) > 1:
        violation = "multiple_faces_detected"
    else:
        violation = "eye_movement"


    eye_center = None
    if len(faces) == 1:
        shape = predictor(gray, faces[0])
        # Compute the average eye center
        left_eye_x = np.mean([shape.part(i).x for i in range(36, 42)])
        left_eye_y = np.mean([shape.part(i).y for i in range(36, 42)])
        right_eye_x = np.mean([shape.part(i).x for i in range(42, 48)])
        right_eye_y = np.mean([shape.part(i).y for i in range(42, 48)])
        eye_center = {"x": int((left_eye_x + right_eye_x) / 2),
                      "y": int((left_eye_y + right_eye_y) / 2)}

    response = {
        "violation": violation,
        "face_count": len(faces),
        "eye_center": eye_center
    }
    return jsonify(response)

if __name__ == '__main__':
    app.run(port=5000, debug=True)