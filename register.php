<?php
session_start();
include('includes/dbconnection.php');

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_btn'])) {
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $uName = trim($_POST['uName']);
    $password = $_POST['password'];
    $address = trim($_POST['address']);
    $contactNo = trim($_POST['contactNo']);
    $city = trim($_POST['city']);
    $gender = $_POST['gender'];
    $dofBirth = $_POST['dofBirth'];

    // Basic validation
    if (empty($fullName) || empty($email) || empty($uName) || empty($password)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Check if email or username already exists
        $check_query = "SELECT customerID FROM Customer WHERE email = ? OR userName = ?";
        if ($stmt = mysqli_prepare($conn, $check_query)) {
            mysqli_stmt_bind_param($stmt, "ss", $email, $uName);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error_message = "Email or Username already exists.";
            } else {
                // Secure password hash
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $insert_query = "INSERT INTO Customer (fullName, email, userName, password, contactNo, address, city, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                if ($insert_stmt = mysqli_prepare($conn, $insert_query)) {
                    mysqli_stmt_bind_param($insert_stmt, "sssssssss", $fullName, $email, $uName, $hashed_password, $contactNo, $address, $city, $gender, $dofBirth);
                    
                    if (mysqli_stmt_execute($insert_stmt)) {
                        $success_message = "Registration successful! You can now login.";
                        // Optionally redirect to login page
                        // header("Location: login.php");
                        // exit;
                    } else {
                        $error_message = "Something went wrong. Please try again.";
                    }
                    mysqli_stmt_close($insert_stmt);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Shark Registration</title>
    <link rel="icon" type="image/png" href="assets/logo.png"/>
    <link rel="stylesheet" href="includes/css/register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="register-container">
        <div class="register-header">
            <img src="assets/logo.png" alt="Tech Shark Logo" class="logo">
            <h1>Register Form</h1>
            <p>Tech Shark</p>
        </div>
        
        <div class="register-form">
            <?php if (!empty($error_message)): ?>
                <div class="error-message" style="color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="success-message" style="color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center;">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            <form method="post" action="register.php" id="loginForm">
                <div class="form-group">
                    <label for="fname"><i class="fas fa-user"></i> Full Name </label>
                    <input type="text" id="fullName" placeholder="Full Name" name="fullName" required>
                </div>
                                
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email </label>
                    <input type="email" id="email" placeholder="Example@mail.com" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="uname"><i class="fas fa-user"></i> User Name </label>
                    <input type="text" id="uName" placeholder="User name " name="uName" required>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password </label>
                    <div class="password-wrapper"> 
                        <i class="fas fa-eye-slash eye-icon" id="show-password"></i>
                        <input type="password" id="password" placeholder="Password" name="password" required>
                    </div>
                </div>

                <script>
                    // show and hide password
                    const showPassword = document.querySelector("#show-password");
                    const passwordField = document.querySelector("#password");

                    showPassword.addEventListener("click", function () {

                    // toggle password visibility
                    const type = passwordField.getAttribute("type") === "password"
                        ? "text"
                        : "password";
                    passwordField.setAttribute("type", type);

                    // toggle eye icon
                    this.classList.toggle("fa-eye");
                    this.classList.toggle("fa-eye-slash");
                    });
                </script>

                <div class="form-group">
                    <label for="address"><i class="fas fa-home"></i> Address </label>
                    <input type="text" id="address" placeholder="Address" name="address">
                </div>

                <div class="form-group">
                    <label for="contactno"><i class="fas fa-phone"></i> Contact No. </label>
                    <input type="text" id="contactNo" placeholder="Contact No." name="contactNo">
                    <!-- Change Input type  -->
                </div>

                <div class="form-group">
                    <label for="city"><i class="fas fa-map-marker-alt"></i> District </label>
                    <select id="city" name="city" class="form-select" required>
                        <option value="" disabled selected>Select District</option>
                        <option value="Ampara">Ampara</option>
                        <option value="Anuradhapura">Anuradhapura</option>
                        <option value="Badulla">Badulla</option>
                        <option value="Batticaloa">Batticaloa</option>
                        <option value="Colombo">Colombo</option>
                        <option value="Galle">Galle</option>
                        <option value="Gampaha">Gampaha</option>
                        <option value="Hambantota">Hambantota</option>
                        <option value="Jaffna">Jaffna</option>
                        <option value="Kalutara">Kalutara</option>
                        <option value="Kandy">Kandy</option>
                        <option value="Kegalle">Kegalle</option>
                        <option value="Kilinochchi">Kilinochchi</option>
                        <option value="Kurunegala">Kurunegala</option>
                        <option value="Mannar">Mannar</option>
                        <option value="Matale">Matale</option>
                        <option value="Matara">Matara</option>
                        <option value="Monaragala">Monaragala</option>
                        <option value="Mullaitivu">Mullaitivu</option>
                        <option value="Nuwara Eliya">Nuwara Eliya</option>
                        <option value="Polonnaruwa">Polonnaruwa</option>
                        <option value="Puttalam">Puttalam</option>
                        <option value="Ratnapura">Ratnapura</option>
                        <option value="Trincomalee">Trincomalee</option>
                        <option value="Vavuniya">Vavuniya</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="gender"><i class="fas fa-venus-mars"></i> Gender </label>
                    <select id="gender" name="gender" class="form-select" required>
                        <option value="" disabled selected>Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>                   
                </div>

                <div class="form-group">
                    <label for="dofbirth"><i class="fas fa-calendar"></i> Date of Birth</label>
                    <input type="date" id="dofBirth" name="dofBirth">
                    <!-- change format -->
                </div>
                                
                <button type="submit" name="register_btn" class="register-btn" id="register">Register</button>
                
                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>


</body>
</html>