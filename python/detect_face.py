import dlib

# Path to the landmark predictor file (make sure it's correct)
PREDICTOR_PATH = "shape_predictor_68_face_landmarks.dat"

# Load the model
detector = dlib.get_frontal_face_detector()
predictor = dlib.shape_predictor(PREDICTOR_PATH)

print("Dlib model loaded successfully!")
