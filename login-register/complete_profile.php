<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user"])) {
    header("Location: registration.php");
    exit();
}

$user = $_SESSION["user"];
$userId = $user["id"];
$fullName = htmlspecialchars($user["full_name"]);
$email = htmlspecialchars($user["email"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $country = $_POST["country"];
    $city = $_POST["city"];
    $address = $_POST["address"];
    $postcode = $_POST["postcode"];
    $dob = $_POST["dob"];
    $phone = $_POST["phone"];
    $password = $_POST["password"];
    $repeatPassword = $_POST["repeat_password"];

    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    if ($password !== $repeatPassword) {
        $errors[] = "Passwords do not match.";
    }

    if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $errors[] = "Phone number is not valid.";
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password=?, country=?, city=?, address=?, postcode=?, dob=?, phone=?, profile_complete=1 WHERE id=?");
        $stmt->bind_param("sssssssi", $passwordHash, $country, $city, $address, $postcode, $dob, $phone, $userId);
        $stmt->execute();

        $_SESSION["user"]["profile_complete"] = true;

        header("Location: index.php");
        exit();
    } else {
        foreach ($errors as $error) {
            echo "<div class='alert alert-danger'>$error</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complete Your Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.1.0/css/countrySelect.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
</head>
<body class="registration_page">
<div class="container">
    <a href="https://www.cut.ac.cy" class="logo-link" target="_blank" title="Go to CUT website"></a>
    <h2>Complete Your Profile</h2>

    <form method="post" action="complete_profile.php" id="complete-form">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" class="form-control" value="<?= $fullName ?>" readonly>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" class="form-control" value="<?= $email ?>" readonly>
        </div>
        <div class="form-group">
            <label>Create Password</label>
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
            <label>Confirm Password</label>
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
            <label>City</label>
            <input type="text" class="form-control" name="city" required>
        </div>
        <div class="form-group">
            <label>Address</label>
            <input type="text" class="form-control" name="address" required>
        </div>
        <div class="form-group">
            <label>Postal Code</label>
            <input type="text" class="form-control" name="postcode" required>
        </div>
        <div class="form-group">
            <label>Date of Birth</label>
            <input type="date" class="form-control" name="dob" id="dob" max="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" class="form-control" id="phone" name="phone" required>
        </div>
        <div class="form-btn">
            <button type="submit" class="btn btn-primary">Save and Continue</button>
        </div>
    </form>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.1.0/js/countrySelect.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
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
            const bar = $("#strength-bar");
            const text = $("#strength-text");

            let strengthClass = "", label = "";
            switch (result.score) {
                case 0: strengthClass = "weak"; label = "Weak"; break;
                case 1: strengthClass = "fair"; label = "Fair"; break;
                case 2: strengthClass = "good"; label = "Good"; break;
                case 3: strengthClass = "very-good"; label = "Very Good"; break;
                case 4: strengthClass = "strong"; label = "Strong"; break;
            }

            bar.removeClass().addClass("strength-bar " + strengthClass).css("width", (result.score + 1) * 25 + "%");
            text.removeClass().addClass("password-strength-text " + strengthClass).text("Password Strength: " + label);
        });

        $("#complete-form").submit(function () {
            $('#phone').val(iti.getNumber());
        });

        // Limit future DOB
        document.getElementById("dob").setAttribute("max", new Date().toISOString().split('T')[0]);
    });
</script>
</body>
</html>
