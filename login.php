<?php
// Start session securely
session_start();
include('includes/dbconnection.php');

// Secure LOGIN Logic using Prepared Statements
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $login_success = false;
    $error_message = "";

    // Function to check login against a specific table
    function checkLogin($conn, $table, $email, $password) {
        $query = "SELECT * FROM `$table` WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                // To support both plaintext (seed data) and hashed passwords (new regs)
                if ($password === $row['password'] || password_verify($password, $row['password'])) {
                    return $row;
                }
            }
            mysqli_stmt_close($stmt);
        }
        return false;
    }

    // 1. Check Customer Table
    if ($user = checkLogin($conn, 'Customer', $email, $password)) {
        $_SESSION['user_id'] = $user['customerID'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = 'Customer';
        $_SESSION['name'] = $user['fullName'];
        header("Location: index.php");
        exit();
    }
    // 2. Check Staff Table (අලුත් Routing Logic එක මෙතනට එකතු කර ඇත)
    elseif ($user = checkLogin($conn, 'Staff', $email, $password)) {
        $_SESSION['user_id'] = $user['staffID'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['staff_type']; 
        $_SESSION['name'] = $user['fullName'];
        
        // රෝල් එක අනුව අදාළ ෆයිල් එකට Redirect කිරීම
        $dashboard_mapping = [
            'Manager'              => 'staff/manager_dashboard.php',
            'Stock Keeper'         => 'staff/stock_keeper_dashboard.php',
            'Sales Representative' => 'staff/sales_dashboard.php',
            'Inquiry Manager'      => 'staff/inquiry_dashboard.php',
            'Repair Technician'    => 'staff/repair_dashboard.php'
        ];
        
        $redirect_page = isset($dashboard_mapping[$user['staff_type']]) ? $dashboard_mapping[$user['staff_type']] : 'login.php';
        header("Location: " . $redirect_page);
        exit();
    }
    // 3. Check Admin Table (කෙලින්ම admin_dashboard.php වෙත හරවා යැවීම)
    elseif ($user = checkLogin($conn, 'Admin', $email, $password)) {
        $_SESSION['user_id'] = $user['adminID'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = 'Administrator';
        $_SESSION['name'] = $user['fullName'];
        header("Location: staff/admin_dashboard.php");
        exit();
    } else {
        $error_message = "Invalid email or password.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Shark Login</title>
    <link rel="icon" type="image/png" href="assets/logo.png"/>
    <link rel="stylesheet" href="includes/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <a href="index.php" title="Go to Home Page" style="display:inline-block; transition:transform 0.2s ease;">
                <img src="assets/logo.png" alt="Tech Shark Logo" class="logo" style="cursor:pointer;" onmouseover="this.parentElement.style.transform='scale(1.05)'" onmouseout="this.parentElement.style.transform='scale(1)'">
            </a>
            <h1>Welcome to Tech Shark</h1>
            <p>Your one-stop computer shop</p>
        </div>
        
        <div class="login-form">
            <?php if (!empty($error_message)): ?>
                <div class="error-message" style="color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <form action="login.php" method="post">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email </label>
                    <input type="email" id="email" placeholder="Example@mail.com" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password </label>
                    <div class="password-wrapper">   
                        <i class="fas fa-eye-slash eye-icon" id="show-password"></i>
                        <input type="password" id="password" placeholder="Enter your password" name="password" required>
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
                
                <div class="form-options">
                    <label>
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>
                
                <input type="submit" class="login-btn" value="Login" name="login">
                
                <div class="register-link">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </form>
            
            <div class="social-login">
                <p>Or login with:</p>
                <div class="social-icons">
                    <a href="#" class="social-icon"><i class="fab fa-google"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </div>
        
        <div class="need-help">
            <button class="need-help-btn"><i class="fas fa-handshake"></i> &nbsp; Need help? Contact Us</button>
        </div>
    </div>
    <!-- after click the out of the div -> goto the home page -->
    
    <script src="includes/js/login.js"></script>
</body>
</html>