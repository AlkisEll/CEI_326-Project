<?php
session_start();
if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

// Database connection
require_once "database.php";

if (isset($_POST["submit"])) {
    $fullName = $_POST["fullname"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $passwordRepeat = $_POST["repeat_password"];
    $country = $_POST["country"];
    $city = $_POST["city"];
    $address = $_POST["address"];
    $postcode = $_POST["postcode"];
    $dob = $_POST["dob"];
    $phone = $_POST["phone"];

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $errors = array();

    // Validate form fields
    if (empty($fullName) || empty($email) || empty($password) || empty($passwordRepeat) || empty($country) || empty($city) || empty($address) || empty($postcode) || empty($dob) || empty($phone)) {
        array_push($errors, "All fields are required!");
    }

    // Check if email already exists
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $rowCount = mysqli_stmt_num_rows($stmt);
        if ($rowCount > 0) {
            array_push($errors, "Email already exists!");
        } else {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                array_push($errors, "Email is not valid!");
            }
            if (strlen($password) < 8) {
                array_push($errors, "Password must be at least 8 characters!");
            }
            if ($password !== $passwordRepeat) {
                array_push($errors, "Passwords do not match!");
            }
        }
    } else {
        die("Something went wrong. Please try again later.");
    }

    if (count($errors) > 0) {
        foreach ($errors as $error) {
            echo "<div class='alert alert-danger'>$error</div>";
        }
    } else {
        // Insert user data into the database (with new fields)
        $sql = "INSERT INTO users (full_name, email, password, country, city, address, postcode, dob, phone, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
        $stmt = mysqli_stmt_init($conn);
        if (mysqli_stmt_prepare($stmt, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssssss", $fullName, $email, $passwordHash, $country, $city, $address, $postcode, $dob, $phone);
            mysqli_stmt_execute($stmt);

            // Store user data in session for verification method selection
            $_SESSION["user_id"] = mysqli_insert_id($conn); // Get the last inserted user ID
            $_SESSION["email"] = $email;
            $_SESSION["full_name"] = $fullName;

            // Redirect to verification method selection page
            header("Location: select_verification_method.php");
            exit();
        } else {
            die("Something went wrong. Please try again later.");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register an Account</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <!-- country-select-js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.1.0/css/countrySelect.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <!-- Password Strength Meter CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body class="registration_page">
    <div class="container">
        <a href="https://www.cut.ac.cy" class="logo-link" target="_blank" title="Go to CUT website"></a>

        <h2>Special Scientist Registration</h2>
        <div id="password-alert" style="display: none;"></div>
        <form method="post" action="registration.php" id="registration-form">
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" class="form-control" name="fullname" placeholder="e.g. Nikos Nikolaou" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" class="form-control" name="email" placeholder="e.g. user@cut.ac.cy" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" class="form-control" name="phone" placeholder="e.g. +35799112233" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" name="password" id="password" placeholder="Create a password" required>
                <div class="password-strength-meter">
                    <div class="strength-bar" id="strength-bar"></div>
                </div>
                <div class="password-strength-text" id="strength-text"></div>
            </div>
            <div class="form-group">
                <label for="repeat_password">Confirm Password</label>
                <input type="password" class="form-control" name="repeat_password" placeholder="Repeat your password" required>
            </div>
             <div class="form-group country_row">
                <label for="country_input">Country</label>
                
            </div>
            <div class="form-group">
                
                <input type="text" class="form-control country_input" id="country" name="country" placeholder="Select your country" required>
            </div>
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" class="form-control" name="city" placeholder="Enter your city" required>
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" class="form-control" name="address" placeholder="Enter your address" required>
            </div>
            <div class="form-group">
                <label for="postcode">Postal Code</label>
                <input type="text" class="form-control" name="postcode" placeholder="Enter your postal code" required>
            </div>
            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" class="form-control" name="dob" placeholder="Enter your date of birth" required>
            </div>
            <div class="form-btn">
                <input type="submit" class="btn btn-primary" value="Register" name="submit">
            </div>
        </form>
        <div class="form-footer">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- country-select-js JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.1.0/js/countrySelect.min.js"></script>
    <!-- zxcvbn for password strength -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        // Initialize country-select-js
        $(document).ready(function () {
            $("#country").countrySelect({
                defaultCountry: "us", // Default selected country (e.g., United States)
                responsiveDropdown: true, // Make the dropdown responsive
            });

            // Password strength meter
            $("#password").on("input", function () {
                const password = $(this).val();
                const result = zxcvbn(password);
                const strength = result.score; // 0 (Weak) to 4 (Strong)

                // Update strength bar and text
                const strengthBar = $("#strength-bar");
                const strengthText = $("#strength-text");

                let strengthClass = "";
                let strengthLabel = "";

                switch (strength) {
                    case 0:
                        strengthClass = "weak";
                        strengthLabel = "Weak";
                        break;
                    case 1:
                        strengthClass = "fair";
                        strengthLabel = "Fair";
                        break;
                    case 2:
                        strengthClass = "good";
                        strengthLabel = "Good";
                        break;
                    case 3:
                        strengthClass = "very-good";
                        strengthLabel = "Very Good";
                        break;
                    case 4:
                        strengthClass = "strong";
                        strengthLabel = "Strong";
                        break;
                }

                // Update strength bar
                strengthBar.removeClass().addClass("strength-bar " + strengthClass);
                strengthBar.css("width", (strength + 1) * 25 + "%");

                // Update strength text and color
                strengthText.removeClass().addClass("password-strength-text " + strengthClass);
                strengthText.text("Password Strength: " + strengthLabel);
            });

            // Form submission validation
            $("#registration-form").submit(function (e) {
                const password = $("#password").val();
                const result = zxcvbn(password);
                const strength = result.score;

                if (strength < 1) { // Block submission if password is "Weak"
                    e.preventDefault();
                    toastr.error('Your password is too weak. Please choose a stronger password');
                }
            });
        });
    </script>
</body>
</html>
