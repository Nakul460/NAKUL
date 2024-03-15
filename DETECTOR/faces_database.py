import logging as log
import os
import os.path as osp

import cv2
import numpy as np
from scipy.optimize import linear_sum_assignment
from scipy.spatial.distance import cosine

from face_detector import FaceDetector


class FacesDatabase:
    IMAGE_EXTENSIONS = ['jpg', 'png']

    class Identity:
        def __init__(self, label, descriptors):
            self.label = label
            self.descriptors = descriptors

        @staticmethod
        def cosine_dist(x, y):
            return cosine(x, y) * 0.5

    def __init__(self, path, face_identifier, landmarks_detector, face_detector=None, no_show=False):
        path = osp.abspath(path)
        self.fg_path = path
        self.no_show = no_show
        subdirectories = [f for f in os.listdir(path) if osp.isdir(osp.join(path, f))]
        
        if len(subdirectories) == 0:
            log.error("The 'face_img' folder has no subdirectories.")
        
        self.database = []
        for subdirectory in subdirectories:
            subdirectory_path = osp.join(path, subdirectory)
            images = [f for f in os.listdir(subdirectory_path) if f.split('.')[-1] in self.IMAGE_EXTENSIONS]
            
            if len(images) == 0:
                log.warning("No images found in '{}'".format(subdirectory_path))
                continue
            
            for image_filename in images:
                label = subdirectory
                image_path = osp.join(subdirectory_path, image_filename)
                image = cv2.imread(image_path, flags=cv2.IMREAD_COLOR)
                orig_image = image.copy()

                if face_detector:
                    rois = face_detector.infer((image,))
                    if len(rois) < 1:
                        log.warning("No faces found in the image '{}'".format(image_path))
                else:
                    w, h = image.shape[1], image.shape[0]
                    rois = [FaceDetector.Result([0, 0, 0, 0, 0, w, h])]

                for roi in rois:
                    r = [roi]
                    landmarks = landmarks_detector.infer((image, r))

                    face_identifier.start_async(image, r, landmarks)
                    descriptor = face_identifier.get_descriptors()[0]

                    if face_detector:
                        mm = self.check_if_face_exist(descriptor, face_identifier.get_threshold())
                        if mm < 0:
                            crop = orig_image[int(roi.position[1]):int(roi.position[1]+roi.size[1]),
                                   int(roi.position[0]):int(roi.position[0]+roi.size[0])]
                            # name = self.ask_to_save(crop)
                            # self.dump_faces(crop, descriptor, name)
                    else:
                        log.debug("Adding label {} to the gallery".format(label))
                        self.add_item(descriptor, label)

    def match_faces(self, descriptors, match_algo='HUNGARIAN'):
        database = self.database
        distances = np.empty((len(descriptors), len(database)))
        for i, desc in enumerate(descriptors):
            for j, identity in enumerate(database):
                dist = []
                for id_desc in identity.descriptors:
                    dist.append(FacesDatabase.Identity.cosine_dist(desc, id_desc))
                distances[i][j] = dist[np.argmin(dist)]

        matches = []
        # if user specify MIN_DIST for face matching, face with minium cosine distance will be selected.
        if match_algo == 'MIN_DIST':
            for i in range(len(descriptors)):
                id = np.argmin(distances[i])
                min_dist = distances[i][id]
                matches.append((id, min_dist))
        else:
            # Find best assignments, prevent repeats, assuming faces can not repeat
            _, assignments = linear_sum_assignment(distances)
            for i in range(len(descriptors)):
                if len(assignments) <= i: # assignment failure, too many faces
                    matches.append((0, 1.0))
                    continue

                id = assignments[i]
                distance = distances[i, id]
                matches.append((id, distance))

        return matches

    def create_new_label(self, path, id):
        while osp.exists(osp.join(path, "face{}.jpg".format(id))):
            id += 1
        return "face{}".format(id)

    def check_if_face_exist(self, desc, threshold):
        match = -1
        for j, identity in enumerate(self.database):
            dist = []
            for id_desc in identity.descriptors:
                dist.append(FacesDatabase.Identity.cosine_dist(desc, id_desc))
            if dist[np.argmin(dist)] < threshold:
                match = j
                break
        return match

    def check_if_label_exists(self, label):
        match = -1
        import re
        name = re.split(r'-\d+$', label)
        if not len(name):
            return -1, label
        label = name[0].lower()

        for j, identity in enumerate(self.database):
            if identity.label == label:
                match = j
                break
        return match, label

    def add_item(self, desc, label):
        match = -1
        if not label:
            label = self.create_new_label(self.fg_path, len(self.database))
            log.warning("Trying to store an item without a label. Assigned label {}.".format(label))
        else:
            match, label = self.check_if_label_exists(label)

        if match < 0:
            self.database.append(FacesDatabase.Identity(label, [desc]))
        else:
            self.database[match].descriptors.append(desc)
            log.debug("Appending new descriptor for label {}.".format(label))

        return match, label

    def __getitem__(self, idx):
        return self.database[idx]

    def __len__(self):
        return len(self.database)
