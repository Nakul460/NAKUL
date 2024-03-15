<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Upload and View</title>
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

        /* Style for the logout button */
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            border: none;
            background-color: #dc3545; /* Red color for logout button */
            color: #fff;
            font-size: 16px;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #c82333; /* Darker shade of red on hover */
        }
    </style>
</head>
<body>
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

// Check if the form was submitted and the file was uploaded
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
        // Check file size
        if ($img_size > 12500000000) {
            $em = "Sorry, your file is too large.";
            header("Location: dashboard.php?error=$em");
            exit();
        } else {
            // Get file extension and convert to lowercase
            $img_ex = pathinfo($img_name, PATHINFO_EXTENSION);
            $img_ex_lc = strtolower($img_ex);

            // Allowed file extensions
            $allowed_exs = array("jpg", "jpeg", "png");

            // Check if the uploaded file has an allowed extension
            if (in_array($img_ex_lc, $allowed_exs)) {
                // Generate a unique file name
                $new_img_name = uniqid("IMG-", true) . '.' . $img_ex_lc;
                $img_upload_path = 'uploads/' . $new_img_name;

                // Move the uploaded file to the uploads directory
                move_uploaded_file($tmp_name, $img_upload_path);

                // Insert the file details into the database
                $sql = "INSERT INTO faces (user_id, image_url, face_name, phone_number) 
        VALUES ('$user_id', '$new_img_name', '$face_name', '$phone_number')";
mysqli_query($con, $sql);

                // Redirect to the current page after successful upload
                header("Location: dashboard.php?upload=success");
                exit();
            } else {
                $em = "You can't upload files of this type";
                header("Location: dashboard.php?error=$em");
                exit();
            }
        }
    } else {
        $em = "Select the image to be uploaded";
        header("Location: dashboard.php?error=$em");
        exit();
    }
}

// Delete selected images from the database
if (isset($_POST['delete_selected'])) {
    if (!empty($_POST['delete_ids'])) {
        $delete_ids = implode(",", $_POST['delete_ids']);
        $sql = "DELETE FROM faces WHERE face_id IN ($delete_ids) AND user_id = '$user_id'";
        mysqli_query($con, $sql);
        header("Location: dashboard.php?delete=success");
        exit();
    } else {
        // If no images are selected for deletion, display an error message
        $em = "Please select at least one image to delete.";
        header("Location: dashboard.php?error=$em");
        exit();
    }
}

// Query the database to get the images
$sql = "SELECT * FROM faces WHERE user_id = '$user_id' ORDER BY face_id DESC";
$res = mysqli_query($con, $sql);
?>

   <div class="container">
        <!-- Image upload form -->
        <?php if (isset($_GET['error'])): ?>
            <p class="error"><?php echo $_GET['error']; ?></p>
        <?php endif ?>
        <?php if (isset($_GET['upload']) && $_GET['upload'] === 'success'): ?>
            <p class="success">Image uploaded successfully!</p>
        <?php endif ?>
        <h2>Image Upload</h2>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
            <input type="file" name="my_image"><hr>
            <input type="text" name="face_name" placeholder="Enter face name" required><hr>
            <input type="text" name="phone_number" placeholder="Enter phone number along with country code" pattern="\d{12}" title="Please enter the phone number in the format XXXXXXXXXXX." required>
            <input type="submit" name="submit" value="Upload">
        </form>
    </div>
    <div class="container">
        <!-- Display images from the database -->
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <div class="album-container">
                <?php 
                if ($res && mysqli_num_rows($res) > 0) {
                    while ($images = mysqli_fetch_assoc($res)) { 
                        ?>
                        <div class="album" style="background-color: #fff;">
                            <input type="checkbox" name="delete_ids[]" value="<?=$images['face_id']?>" class="album-checkbox">
                            <img src="uploads/<?=$images['image_url']?>" alt="Image">
                        </div>
                    <?php 
                    }
                } else {
                    echo "<p>No images found.</p>";
                }
                ?>
            </div>
            <input type="submit" name="delete_selected" value="Delete Selected">
        </form>
    </div>
    <div class="container">
        <!-- Start Detection  -->
        <button class="redirect-btn" onclick="start()">Start Detection</button>
<script>
function start() {
    window.location.href = 'http://localhost:5000';
}
</script>
    </div>
    <!-- Logout button -->
    <button class="logout-btn" onclick="logout()">Logout</button>

    <!-- JavaScript code for handling actions -->
    <script>
        function logout() {
            // Redirect the user to the logout.php page
            window.location.href = 'logout.php';
        }
    </script>
</body>
</html>

