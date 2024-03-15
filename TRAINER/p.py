import os
import cv2 as cv
import numpy as np
from flask import Flask, render_template, Response, request

from yunet import YuNet

app = Flask(__name__)

# Initialize YuNet model
model = YuNet(modelPath='face_detection_yunet_2023mar.onnx',
              inputSize=[320, 320],
              confThreshold=0.9,
              nmsThreshold=0.3,
              topK=5000,
              backendId=cv.dnn.DNN_BACKEND_CUDA,
              targetId=cv.dnn.DNN_TARGET_CUDA)

# Set up directory for storing face images
image_dir = 'face_img'
if not os.path.isdir(image_dir):
    os.mkdir(image_dir)

# Function to draw bounding boxes
def draw_bounding_box(image, box, color=(0, 255, 0), thickness=2):
    x, y, w, h = map(int, box[:4])
    cv.rectangle(image, (x, y), (x + w, y + h), color, thickness)

# Generator function to get frames from webcam
def webcam_feed(name):
    user_dir = os.path.join(image_dir, name)
    if not os.path.exists(user_dir):
        os.mkdir(user_dir)

    cap = cv.VideoCapture(0)
    w = int(cap.get(cv.CAP_PROP_FRAME_WIDTH))
    h = int(cap.get(cv.CAP_PROP_FRAME_HEIGHT))
    model.setInputSize([w, h])

    count_max = 40
    count = 0

    while count < count_max:
        rval, frame = cap.read()

        if not rval:
            print("Failed to open webcam. Exiting...")
            break

        # Face detection
        results = model.infer(frame)

        # Save the first detected face
        if len(results) > 0:
            det = results[0]
            x, y, w, h = map(int, det[:4])  # Convert coordinates to integers
            face_img = frame[y:y+h, x:x+w]
            cv.imwrite(os.path.join(user_dir, f"face_{count}.jpg"), face_img)

            count += 1
            print(f"Saved sample {count}/{count_max}")

        # Draw bounding boxes around detected faces
        for det in results:
            draw_bounding_box(frame, det, color=(255, 0, 0), thickness=2)

        # Encode frame as JPEG
        _, jpeg = cv.imencode('.jpg', frame)
        frame_bytes = jpeg.tobytes()
        yield (b'--frame\r\n'
               b'Content-Type: image/jpeg\r\n\r\n' + frame_bytes + b'\r\n')

    cap.release()

@app.route('/')
def index():
    return render_template('train1.html')

@app.route('/video_feed', methods=['POST'])
def video_feed():
    name = request.form['name']
    return Response(webcam_feed(name), mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/exit')
def exit():
    # You can add any cleanup operations here before exiting
    cv.destroyAllWindows()  # Close OpenCV windows
    return 'Exiting...'

if __name__ == '__main__':
    app.run(debug=True)
