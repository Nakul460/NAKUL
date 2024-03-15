import logging as log
from time import perf_counter
import cv2
from openvino.runtime import Core, get_version
from landmarks_detector import LandmarksDetector
from face_detector import FaceDetector
from faces_database import FacesDatabase
from face_identifier import FaceIdentifier
from flask import Flask, render_template
from flask_socketio import SocketIO
import base64

app = Flask(__name__)
socketio = SocketIO(app)

class FrameProcessor:
    QUEUE_SIZE = 16

    def __init__(self):
        self.setup_logging()
        self.setup_openvino()
        self.setup_database()

    def setup_logging(self):
        log.basicConfig(level=log.INFO)

    def setup_openvino(self):
        try:
            log.info('OpenVINO Runtime')
            log.info('\tbuild: {}'.format(get_version()))
            self.core = Core()
            self.face_detector = FaceDetector(self.core, faceDETECT, input_size=(300, 300), confidence_threshold=0.6)
            self.landmarks_detector = LandmarksDetector(self.core, faceLANDMARK)
            self.face_identifier = FaceIdentifier(self.core, FaceIDENTIFY, match_threshold=0.7, match_algo='HUNGARIAN')
            self.device = 'CPU'  # or set this from an external configuration
            self.face_detector.deploy(self.device)
            self.landmarks_detector.deploy(self.device, self.QUEUE_SIZE)
            self.face_identifier.deploy(self.device, self.QUEUE_SIZE)
        except Exception as e:
            log.error(f"Error setting up OpenVINO: {e}")
            raise

    def setup_database(self):
        try:
            self.faces_database = FacesDatabase(r'E:\xaamp\htdocs\myProject\flask_app\face_recogition_CPU\face_img', self.face_identifier, self.landmarks_detector)
            self.face_identifier.set_faces_database(self.faces_database)
            log.info('Database is built, registered {} identities'.format(len(self.faces_database)))
        except Exception as e:
            log.error(f"Error setting up database: {e}")
            raise

    def face_process(self, frame):
        try:
            rois = self.face_detector.infer((frame,))
            if self.QUEUE_SIZE > len(rois):
                rois = rois[:self.QUEUE_SIZE]
            landmarks = self.landmarks_detector.infer((frame, rois))
            face_identities, unknowns = self.face_identifier.infer((frame, rois, landmarks))
            return [rois, landmarks, face_identities]
        except Exception as e:
            log.error(f"Error processing faces: {e}")
            return []

def draw_face_detection(frame, frame_processor, detections):
    try:
        size = frame.shape[:2]
        for roi, landmarks, identity in zip(*detections):
            text = frame_processor.face_identifier.get_identity_label(identity.id)
            xmin = max(int(roi.position[0]), 0)
            ymin = max(int(roi.position[1]), 0)
            xmax = min(int(roi.position[0] + roi.size[0]), size[1])
            ymax = min(int(roi.position[1] + roi.size[1]), size[0])
            cv2.rectangle(frame, (xmin, ymin), (xmax, ymax), (0, 220, 0), 2)
            face_point = xmin, ymin
            for point in landmarks:
                x = int(xmin + roi.size[0] * point[0])
                y = int(ymin + roi.size[1] * point[1])
                cv2.circle(frame, (x, y), 1, (0, 255, 255), 2)
            frame = image_recognizer(frame, text, identity, face_point, 0.75)
        return frame
    except Exception as e:
        log.error(f"Error drawing face detection: {e}")
        return frame

def image_recognizer(frame, text, identity, face_point, threshold):
    try:
        xmin, ymin = face_point
        if identity.id != FaceIdentifier.UNKNOWN_ID:
            if (1 - identity.distance) > threshold:
                text_size = cv2.getTextSize(text, cv2.FONT_HERSHEY_SIMPLEX, 0.7, 1)[0]
                cv2.rectangle(frame, (xmin, ymin), (xmin + text_size[0], ymin - text_size[1]), (255, 255, 255), cv2.FILLED)
                cv2.putText(frame, text, (xmin, ymin), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 0, 255), 1)
            else:
                text_size = cv2.getTextSize("Unknown", cv2.FONT_HERSHEY_SIMPLEX, 0.7, 1)[0]
                cv2.rectangle(frame, (xmin, ymin), (xmin + text_size[0], ymin - text_size[1]), (255, 255, 255), cv2.FILLED)
                cv2.putText(frame, "Unknown", (xmin, ymin), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 0, 255), 1)
        return frame
    except Exception as e:
        log.error(f"Error recognizing image: {e}")
        return frame

# Main code
source = 0  # Webcam index (0 for the first webcam)
faceDETECT = r"E:\xaamp\htdocs\myProject\flask_app\face_recogition_CPU\model_2022_3\face-detection-retail-0005.xml"
faceLANDMARK = r"E:\xaamp\htdocs\myProject\flask_app\face_recogition_CPU\model_2022_3\landmarks-regression-retail-0009.xml"
FaceIDENTIFY = r"E:\xaamp\htdocs\myProject\flask_app\face_recogition_CPU\model_2022_3\face-reidentification-retail-0095.xml"

cap = cv2.VideoCapture(source)
frame_processor = FrameProcessor()

@socketio.on('connect')
def handle_connect():
    print('Client connected')

@socketio.on('disconnect')
def handle_disconnect():
    print('Client disconnected')

def process_frame(frame_processor):
    try:
        ret, frame = cap.read()
        if not ret:
            log.error("Failed to capture frame from the source.")
            return

        detections = frame_processor.face_process(frame)
        frame = draw_face_detection(frame, frame_processor, detections)

        # Encode frame to JPEG
        _, buffer = cv2.imencode('.jpg', frame)

        # Convert JPEG buffer to base64 string
        jpg_as_text = base64.b64encode(buffer).decode('utf-8')

        socketio.emit('frame', {'data': jpg_as_text})
    except Exception as e:
        log.error(f"Error processing frame: {e}")

@socketio.on('process_frame')
def handle_process_frame():
    process_frame(frame_processor)

@app.route('/')
def index():
    return render_template('index.html')

if __name__ == '__main__':
    socketio.run(app)
