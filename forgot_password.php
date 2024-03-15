<?php
include("connections.php");
include("functions.php");

session_start();

// Function to generate OTP and timestamp
function generateOTPWithTimestamp($length = 6) {
    $characters = '0123456789';
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= $characters[rand(0, strlen($characters) - 1)];
    }
    // Return OTP and current timestamp
    return array('otp' => $otp, 'timestamp' => time());
}

// Function to check if OTP has expired

// Database connection parameters
$host = 'localhost';
$dbname = 'intruder_detection';
$username = 'root';
$password = '';

try {
    // Establish database connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    $error_message = "Connection failed: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (isset($_POST['email'])) {
        $email = $_POST['email'];

        // Check if email exists in the database
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Email exists, proceed with generating OTP
            $otpData = generateOTPWithTimestamp();
            $otp = $otpData['otp'];
            $timestamp = $otpData['timestamp'];

            // Store OTP and timestamp in session
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_timestamp'] = $timestamp;
            $_SESSION['email'] = $email;

            // Send OTP to user's email using Gmail SMTP
            $smtpAddress = "smtp.gmail.com";
            $smtpPort = 587;
            $senderEmail = "nakulraja33@gmail.com"; // Enter your Gmail email address
            $senderPassword = " "; // Enter your Gmail password generated from 2 step verification
            $senderName = "Your Name";

            $subject = 'Password Reset OTP';
            $message = "Your OTP to reset the password is: $otp";
            $headers = "From: $senderName <$senderEmail>\r\n";
            $headers .= "Reply-To: $senderEmail\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            // SMTP authentication
            $mailConfig = array(
                'smtp_auth' => true,
                'username' => $senderEmail,
                'password' => $senderPassword,
                'smtp_secure' => 'tls',
                'port' => $smtpPort
            );

            if (mail($email, $subject, $message, $headers, "-f $senderEmail")) {
                // Redirect to OTP verification page
                header("Location: otp_verification.php");
                exit();
            } else {
                // Handle email sending failure
                $error_message = "Failed to send OTP. Please try again.";
            }
        } else {
            // Handle email not found in database
            $error_message = "Email not found. Please enter a valid email address.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <style>
        * {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.page1-container {
  width: 100%;
  display: flex;
  overflow: auto;
  min-height: 100vh;
  align-items: center;
  flex-direction: column;
  justify-content: center;
  background-image: linear-gradient(225deg, rgb(9, 8, 27) 34.00%,rgb(56, 100, 163) 100.00%);
}
.page1-container1 {
  width: 700px;
  height: 320px;
  display: flex;
  position: relative;
  box-shadow: 5px 5px 10px 0px #d4d4d4;
  align-items: flex-start;
  border-radius: 15px;
  background-size: cover;
  background-image: url("bg3.png");
  z-index: 1;
  background-position: top left;
  background-color: rgba(255, 255, 255, 0.2); /* 0.5 sets the opacity */
}

.page1-container2 {
  flex: 0 0 auto;
  width: 0px;
  height: 150%;
  display: flex;
  position: relative;
  align-items: center;
  border-radius: 15px;
  justify-content: center;
  background-color: #000000;
}
.page1-text {
  top: -87px;
  color: #f5eeee;
  right: -500px;
  position: absolute;
  font-size: 30px;
  align-self: flex-start;
  font-style: normal;
  font-weight: 800;
  letter-spacing: 5px;
  font-family: 'Inter';
}
.page1-text1 {
  top: 60px;
  color: rgb(245, 238, 238);
  right: -190px;
  position: absolute;
  font-size: 20px;
  align-self: flex-start;
  font-style: normal;
  font-family: 'Inter';
}

.page1-text2 {
  top: 80px;
  color: rgb(245, 238, 238);
  right: -320px;
  position: absolute;
  font-size: 20px;
  align-self: flex-start;
  font-style: normal;
  font-family:'Inter';

}
.page1-text3 {
  top: 235px;
  color: rgb(245, 238, 238);
  right: -200px;
  position: absolute;
  font-size: 20px;
  align-self: flex-start;
  font-style: normal;
  text-align: center;
  font-weight: 500;
  letter-spacing: 1px;
  font-family: 'Inter';
}
.page1-text4 {
  right: -288px;
  top: 325px;
  color: rgb(245, 238, 238);
  position: absolute;
  font-size: 20px;
  align-self: flex-start;
  font-style: normal;
  text-align: center;
  font-weight: 500;
  letter-spacing: 1px;
  font-family: 'Inter';
}

.error-message {
  right: -700px;
  top: 250px;
  color: #fff;
  position: absolute;
  font-size: 25px;
  align-self: flex-start;
  font-style: normal;
  text-align: center;
  font-weight: 500;
  letter-spacing: 1px;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  animation: slide-in 0.5s ease forwards;
}

@keyframes slide-in {
  from {
    right: -900px;
    opacity: 0;
  }
  to {
    right: -300;
    opacity: 1;
  }
}


.page1-textinput,
.page1-textinput1,
.page1-textinput2,
.page1-textinput3{
  width: 300px;
  height: 35px;
  position: absolute;
  border: 1px solid #09081b; /* Adding a solid border */
  border-radius: 0px; /* Slightly rounded corners */
  padding: 10px; /* Adding some padding inside the textboxes */
  font-size: 16px; /* Adjusting font size for better readability */
  outline: none; /* Removing default outline */
}

.page1-textinput {
  top: 90px;
  left: 95px;
}

.page1-textinput1 {
  top: 125px;
  left: 97px;
}
.page1-textinput2 {
  top: 265px;
  left: 97px;
}
.page1-textinput3 {
  top: 350px;
  left: 97px;
}

/* Hover effect for textboxes */
.page1-textinput:hover,
.page1-textinput1:hover,
.page1-textinput2:hover,
.page1-textinput3:hover{
  border-color: #6182ad; /* Change border color on hover */
}

/* Focus effect for textboxes */
.page1-textinput:focus,
.page1-textinput1:focus,
.page1-textinput2:focus,
.page1-textinput3:focus{
  border-color: #6182ad; /* Change border color on focus */
}



.page1-button2{
    right: -330px;
  top: 190px;
  position: absolute;
  color:#fff;
  font-size: 17px; 
  font-weight: 600; 
  border: 2px solid #6182ad; 
  border-radius: 8px; 
  background-color: #09081b; 
  padding: 12px 24px; 
  cursor: pointer; 
  transition: all 0.3s ease; 
}
.page1-button2:hover {
  background-color: #6182ad; 
  color: #09081b; 
  font-size: 17px; 
  font-weight: 600; 
}

@media(max-width: 767px) {
  .page1-container1 {
    width: 444px;
  }
  .page1-text {
    top: -57px;
    right: -282px;
  }
}
@media(max-width: 479px) {
  .page1-text {
    top: -59px;
    right: -300px;
  }
  .page1-textinput {
    width: 244px;
  }
  .page1-textinput1 {
    width: 247px;
  }
}
 <    </style>
</head>
<body>
    <div class="background-container">
        <div class="page1-container">
            <div class="page1-container1">
                <div class="page1-container2">
                    <span class="page1-text">RESET PASSWORD</span>
                    <form id="login-Form" method="POST" action="login.php">
                        <!-- Form fields -->
                    </form>
                    <!-- Add this section for password reset -->
                    <div class="password-reset-container">
                        <form id="forgot-password-form" method="POST" action="forgot_password.php">
                            <span class="page1-text2">Enter your email</span>
                            <input type="email" id="email" name="email" class="page1-textinput1 input" required>
                            <button type="submit" class="page1-button2 button">Reset Password</button>
                            <?php if (isset($error_message)) { ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php } ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
