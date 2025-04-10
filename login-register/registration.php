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

    if (empty($fullName) || empty($email) || empty($password) || empty($passwordRepeat) || empty($country) || empty($city) || empty($address) || empty($postcode) || empty($dob) || empty($phone)) {
        array_push($errors, "All fields are required!");
    }

    if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        array_push($errors, "Phone number is not valid!");
    }

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $rowCount = mysqli_stmt_num_rows($stmt);
        if ($rowCount > 0) {
            $_SESSION["registration_error"] = "An account with this email already exists!";
            header("Location: registration.php");
            exit();     
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
        $sql = "INSERT INTO users (full_name, email, password, country, city, address, postcode, dob, phone, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
        $stmt = mysqli_stmt_init($conn);
        if (mysqli_stmt_prepare($stmt, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssssss", $fullName, $email, $passwordHash, $country, $city, $address, $postcode, $dob, $phone);
            mysqli_stmt_execute($stmt);

            $_SESSION["user_id"] = mysqli_insert_id($conn);
            $_SESSION["email"] = $email;
            $_SESSION["full_name"] = $fullName;

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
    <title>Register an Account</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.1.0/css/countrySelect.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="registration_page">
<div class="container">

    <?php if (isset($_SESSION["registration_error"])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION["registration_error"]; unset($_SESSION["registration_error"]); ?>
        </div>
    <?php endif; ?>

    <a href="https://www.cut.ac.cy" class="logo-link" target="_blank" title="Go to CUT website"></a>

    <h2>Special Scientist Registration</h2>

 <!-- Social Sign-In -->
    <div class="mb-4 text-center">
        <a href="https://accounts.google.com/o/oauth2/v2/auth?client_id=298135741411-6gbhvfmubpk1vjgbeervmma5mntarggk.apps.googleusercontent.com&redirect_uri=http://localhost/login-register/google_callback.php&response_type=code&scope=email%20profile&access_type=online" 
            class="btn btn-light border d-flex align-items-center justify-content-center gap-2"
            style="max-width: 300px; margin: auto;">
            <img src="https://developers.google.com/identity/images/g-logo.png" style="height: 20px;">
            Continue with Google
        </a>

    <a href="https://www.facebook.com/v17.0/4012641692398081/dialog/oauth?client_id=4012641692398081&redirect_uri=https://cei326-omada7.cut.ac.cy/login-register/facebook_callback.php&scope=email,public_profile" class="btn btn-primary">
       class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2">
        <img src="https://upload.wikimedia.org/wikipedia/commons/5/51/Facebook_f_logo_%282019%29.svg"
             alt="Facebook" style="width: 20px; height: 20px;">
        Continue with Facebook
    </a>
</div>



    <form method="post" action="registration.php" id="registration-form">
        <div class="form-group">
            <label for="fullname">Full Name</label>
            <input type="text" class="form-control" name="fullname" required>
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" class="form-control" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="password" id="password" required>
                <span class="input-group-text toggle-password" data-target="#password"><i class="bi bi-eye"></i></span>
            </div>
            <div class="password-strength-meter">
                <div class="strength-bar" id="strength-bar"></div>
            </div>
            <div class="password-strength-text" id="strength-text"></div>
        </div>
        <div class="form-group">
            <label for="repeat_password">Confirm Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="repeat_password" id="confirm_password" required>
                <span class="input-group-text toggle-password" data-target="#confirm_password"><i class="bi bi-eye"></i></span>
            </div>
        </div>
        <div class="form-group country_row">
            <label for="country_input">Country</label>
        </div>
        <div class="form-group">
            <input type="text" class="form-control country_input" id="country" name="country" required>
        </div>
        <div class="form-group">
            <label for="city">City</label>
            <input type="text" class="form-control" name="city" required>
        </div>
        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" class="form-control" name="address" required>
        </div>
        <div class="form-group">
            <label for="postcode">Postal Code</label>
            <input type="text" class="form-control" name="postcode" required>
        </div>
        <div class="form-group">
            <label for="dob">Date of Birth</label>
            <input type="date" class="form-control" name="dob" id="dob" max="" required>
        </div>
        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone Number" required>
        </div>
        <div class="form-btn">
            <input type="submit" class="btn btn-primary" value="Register" name="submit">
        </div>
    </form>
    <div class="form-footer">
        Already have an account? <a href="login.php">Login</a>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.1.0/js/countrySelect.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    $(document).ready(function () {
        $("#country").countrySelect({ defaultCountry: "cy", responsiveDropdown: true });

        const phoneInput = document.querySelector("#phone");
        const iti = window.intlTelInput(phoneInput, {
            separateDialCode: true,
            initialCountry: "cy",
            preferredCountries: ['cy', 'gr', 'us'],
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
        });

        $('#country').on('change', function () {
            const selectedCountry = $(this).val().toLowerCase();
            try { iti.setCountry(selectedCountry); } catch (e) {}
        });

        // Toggle password visibility
        $(".toggle-password").on("click", function () {
            const input = $($(this).data("target"));
            const icon = $(this).find("i");
            const type = input.attr("type") === "password" ? "text" : "password";
            input.attr("type", type);
            icon.toggleClass("bi-eye bi-eye-slash");
        });

        $("#password").on("input", function () {
            const password = $(this).val();
            const result = zxcvbn(password);
            let strengthClass = "", strengthLabel = "";

            switch (result.score) {
                case 0: strengthClass = "weak"; strengthLabel = "Weak"; break;
                case 1: strengthClass = "fair"; strengthLabel = "Fair"; break;
                case 2: strengthClass = "good"; strengthLabel = "Good"; break;
                case 3: strengthClass = "very-good"; strengthLabel = "Very Good"; break;
                case 4: strengthClass = "strong"; strengthLabel = "Strong"; break;
            }

            $("#strength-bar").removeClass().addClass("strength-bar " + strengthClass).css("width", (result.score + 1) * 25 + "%");
            $("#strength-text").removeClass().addClass("password-strength-text " + strengthClass).text("Password Strength: " + strengthLabel);
        });

        $("#registration-form").submit(function () {
            $('#phone').val(iti.getNumber());
            const password = $("#password").val();
            if (zxcvbn(password).score < 1) {
                toastr.error('Your password is too weak. Please choose a stronger password.');
                return false;
            }
        });
    });

    // Limit future date of birth
    document.getElementById("dob").setAttribute("max", new Date().toISOString().split('T')[0]);

    // Google callback handler
    function handleCredentialResponse(response) {
        const jwt = JSON.parse(atob(response.credential.split('.')[1]));
        document.querySelector('input[name="email"]').value = jwt.email || "";
        document.querySelector('input[name="fullname"]').value = jwt.name || "";
    }
</script>
</body>
</html>
