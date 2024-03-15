<?php
session_start();

include("connections.php");
include("functions.php");

// Function to verify OTP
function verifyOTP($otp, $timestamp) {
    // Check if OTP has expired
    if (isOTPExpired($timestamp)) {
        return false; // OTP has expired
    }
    // Check if OTP has already been used
    if ($_SESSION['otp_used'] === true) {
        return false; // OTP has already been used
    }
    // Compare OTP entered by user with OTP stored in session
    if ($otp === $_SESSION['otp']) {
        // Set OTP as used
        $_SESSION['otp_used'] = true;
        return true; // OTP is verified
    }
    return false; // Incorrect OTP
}

// Initialize error message
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (isset($_POST['otp']) && isset($_POST['email'])) {
        $otp = $_POST['otp'];
        $email = $_POST['email'];

        // Verify OTP
        if (verifyOTP($otp, $_SESSION['otp_timestamp'])) {
            // If OTP is verified and not expired, proceed with password reset
            // Redirect to password reset page with email parameter
            header("Location: password_reset.php?email=" . urlencode($email));
            // Add success message
            $_SESSION['success_message'] = "OTP verified successfully.";
            exit();
        } else {
            // If OTP is incorrect, show error message
            $error_message = "Incorrect or expired OTP. Please try again.";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'>
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
    right: -500px;
  top: -100px;
  color: #fff;
  position: absolute;
  font-size: 35px;
  align-self: flex-start;
  font-style: normal;
  text-align: center;
  font-weight: 500;
  letter-spacing: 5px;
  font-family: 'Inter'
}




.page1-text1 {
  top: 90px;
  color: rgb(245, 238, 238);
  right: -450px;
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
  right: -400px;
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
    right: -200;
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

    </style>
</head>

<body>
<div class="background-container">
    <div class="page1-container">
        <div class="page1-container1">
            <div class="page1-container2">
                <span class="page1-text">OTP Verification</span>
                <?php if ($error_message !== "") { ?>
                    <p class="error-message"><?php echo $error_message; ?></p>
                <?php } ?>
                <form id="otp-verification-form" method="POST" action="">
                    <span class="page1-text1">Enter the OTP sent to <?php echo htmlspecialchars($_SESSION['email']); ?>:</span>
                    <input type="text" id="otp" name="otp" class="page1-textinput1 input" required value="<?php echo isset($_POST['otp']) ? htmlspecialchars($_POST['otp']) : ''; ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>">
                    <button type="submit" class="page1-button2 button">Verify OTP</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>

</html>
