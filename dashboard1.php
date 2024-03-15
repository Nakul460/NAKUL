<?php
// Include the database connection script
include "connections.php";
// Start the session
session_start();
// Check if user is logged in
if(isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    // Redirect to login page if user is not logged in
    header("Location: login.php");
    exit();
}

// Function to handle image upload
function uploadImage($con, $user_id) {
    if (isset($_POST['submit']) && isset($_FILES['my_image'])) {
        // Retrieve file details
        $img_name = $_FILES['my_image']['name'];
        $img_size = $_FILES['my_image']['size'];
        $tmp_name = $_FILES['my_image']['tmp_name'];
        $error = $_FILES['my_image']['error'];
        
        // Retrieve face name
        $face_name = $_POST['face_name'];
        $phone_number = $_POST['phone_number'];

        // Check for upload errors
        if ($error === 0) {
            // Read the image data
            $img_data = file_get_contents($tmp_name);

            // Insert the file details into the database
            $stmt = $con->prepare("INSERT INTO faces (user_id, image_data, face_name, phone_number) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $img_data, $face_name, $phone_number);
            if ($stmt->execute()) {
                return "Image uploaded successfully!";
            } else {
                return "Failed to upload image.";
            }
        } else {
            return "Error uploading image. Please try again.";
        }
    }
    return null;
}

// Function to handle image deletion
function deleteImages($con, $user_id) {
    if (isset($_POST['delete_selected'])) {
        if (!empty($_POST['delete_ids'])) {
            $delete_ids = implode(",", $_POST['delete_ids']);
            $sql = "DELETE FROM faces WHERE face_id IN ($delete_ids) AND user_id = '$user_id'";
            mysqli_query($con, $sql);
            return "Images deleted successfully!";
        } else {
            return "Please select at least one image to delete.";
        }
    }
    return null;
}

// Call uploadImage and deleteImages functions
$uploadMessage = uploadImage($con, $user_id);
$deleteMessage = deleteImages($con, $user_id);

// Query the database to get the images
$sql = "SELECT * FROM faces WHERE user_id = '$user_id' ORDER BY face_id DESC";
$res = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Upload and View</title>
    <!-- Your CSS styles -->
    <style>
         body {
            font-family: Arial, sans-serif;
            background-color: #ddd; 
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            position: relative;
        }

        .album-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            grid-gap: 20px;
        }

        .album {
            position: relative;
            padding: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.3s ease;
            overflow: hidden;
            border-radius: 5px;
        }

        .album:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .album img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
        }

        .album-checkbox {
            position: absolute;
            top: 5px;
            left: 5px;
            cursor: pointer;
            z-index: 1;
        }

        .error,
        .success {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .error {
            background-color: #ffcccc;
            color: #ff0000;
        }

        .success {
            background-color: #ccffcc;
            color: #009900;
        }

        form {
            margin-bottom: 20px;
        }

        input[type="file"] {
            margin-bottom: 10px;
        }

        input[type="submit"] {
            padding: 10px 20px;
            border: none;
            background-color: #007bff;
            color: #fff;
            font-size: 16px;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        
        .redirect-btn {
            padding: 10px 20px;
            border: none;
            background-color: #007bff;
            color: #fff;
            font-size: 16px;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: inline-block;
            margin-top: 20px;
        }

        .redirect-btn:hover {
            background-color: #0056b3;
        }

    </style>
</head>
<body>
   <!-- Your HTML content here -->
   <div class="container">
        <!-- Image upload form -->
        <h2>Image Upload</h2>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
            <input type="file" name="my_image"><br>
            <input type="text" name="face_name" placeholder="Enter face name" required><br>
            <input type="text" name="phone_number" placeholder="Enter phone number along with country code" pattern="\d{12}" title="Please enter the phone number in the format XXXXXXXXXXX." required><br>
            <input type="submit" name="submit" value="Upload">
        </form>
        <?php if ($uploadMessage): ?>
            <p><?php echo $uploadMessage; ?></p>
        <?php endif; ?>
    </div>
    <div class="container">
        <!-- Display images from the database -->
        <?php if ($res && mysqli_num_rows($res) > 0): ?>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                <div class="album-container">
                <?php while ($images = mysqli_fetch_assoc($res)): ?>
                    <div class="album" style="background-color: #fff;">
                        <input type="checkbox" name="delete_ids[]" value="<?php echo $images['face_id']; ?>" class="album-checkbox">
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($images['image_data']); ?>" alt="Image">
                    </div>
                <?php endwhile; ?>
                </div>
                <input type="submit" name="delete_selected" value="Delete Selected">
            </form>
        <?php else: ?>
            <p>No images found.</p>
        <?php endif; ?>
        <?php if ($deleteMessage): ?>
            <p><?php echo $deleteMessage; ?></p>
        <?php endif; ?>
    </div>
    <!-- Your HTML content continues here -->
</body>
</html>