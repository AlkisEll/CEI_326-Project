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

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $errors = array();

    // Validate form fields
    if (empty($fullName) || empty($email) || empty($password) || empty($passwordRepeat) || empty($country) || empty($city) || empty($address) || empty($postcode) || empty($dob)) {
        array_push($errors, "Απαιτούνται όλα τα πεδία!");
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
            array_push($errors, "Το Email υπάρχει ήδη!");
        } else {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                array_push($errors, "Το Email δεν είναι έγκυρο!");
            }
            if (strlen($password) < 8) {
                array_push($errors, "Ο κωδικός πρέπει να είναι τουλάχιστον 8 χαρακτήρες!");
            }
            if ($password !== $passwordRepeat) {
                array_push($errors, "Οι κωδικοί δεν αντιστοιχούν!");
            }
        }
    } else {
        die("Κάτι πήγε στραβά. Δοκιμάστε ξανά αργότερα.");
    }

    if (count($errors) > 0) {
        foreach ($errors as $error) {
            echo "<div class='alert alert-danger'>$error</div>";
        }
    } else {
        // Insert user data into the database (with new fields)
        $sql = "INSERT INTO users (full_name, email, password, country, city, address, postcode, dob, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";
        $stmt = mysqli_stmt_init($conn);
        if (mysqli_stmt_prepare($stmt, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssssss", $fullName, $email, $passwordHash, $country, $city, $address, $postcode, $dob);
            mysqli_stmt_execute($stmt);

            // Store user data in session for verification method selection
            $_SESSION["user_id"] = mysqli_insert_id($conn); // Get the last inserted user ID
            $_SESSION["email"] = $email;
            $_SESSION["full_name"] = $fullName;

            // Redirect to verification method selection page
            header("Location: select_verification_method.php");
            exit();
        } else {
            die("Κάτι πήγε στραβά. Δοκιμάστε ξανά αργότερα.");
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
        <a href="https://www.cut.ac.cy" class="logo-link" target="_blank" title="Μετάβαση στην ιστοσελίδα του ΤΕΠΑΚ"></a>

        <h2>Εγγραφή Ειδικού Επιστήμονα</h2>
        <div id="password-alert" style="display: none;"></div>
        <form method="post" action="registration.php" id="registration-form">
            <div class="form-group">
                <label for="fullname">Ονοματεπώνυμο</label>
                <input type="text" class="form-control" name="fullname" placeholder="π.χ. Νίκος Νικολάου" required>
            </div>
            <div class="form-group">
                <label for="email">Ηλεκτρονική Διεύθυνση</label>
                <input type="email" class="form-control" name="email" placeholder="π.χ. user@cut.ac.cy" required>
            </div>
            <div class="form-group">
                <label for="password">Κωδικός Πρόσβασης</label>
                <input type="password" class="form-control" name="password" id="password" placeholder="Δημιουργήστε έναν κωδικό" required>
                <div class="password-strength-meter">
                    <div class="strength-bar" id="strength-bar"></div>
                </div>
                <div class="password-strength-text" id="strength-text"></div>
            </div>
            <div class="form-group">
                <label for="repeat_password">Επαλήθευση Κωδικού</label>
                <input type="password" class="form-control" name="repeat_password" placeholder="Επαναλάβετε τον κωδικό" required>
            </div>
             <div class="form-group country_row">
                <label for="country_input">Χώρα</label>
                
            </div>
            <div class="form-group">
                
                <input type="text" class="form-control country_input" id="country" name="country" placeholder="Επιλέξτε την χώρα σας" required>
            </div>
            <div class="form-group">
                <label for="city">Πόλη</label>
                <input type="text" class="form-control" name="city" placeholder="Εισάγετε την πόλη σας" required>
            </div>
            <div class="form-group">
                <label for="address">Διεύθυνση</label>
                <input type="text" class="form-control" name="address" placeholder="Εισάγετε την διεύθυνση σας" required>
            </div>
            <div class="form-group">
                <label for="postcode">Ταχυδρομικός Κώδικας</label>
                <input type="text" class="form-control" name="postcode" placeholder="Εισάγετε τον ταχυδρομικό σας κώδικα" required>
            </div>
            <div class="form-group">
                <label for="dob">Ημερομηνία Γέννησης</label>
                <input type="date" class="form-control" name="dob" placeholder="Εισάγετε την ημερομηνία γέννησης σας" required>
            </div>
            <div class="form-btn">
                <input type="submit" class="btn btn-primary" value="Εγγραφή" name="submit">
            </div>
        </form>
        <div class="form-footer">
            Έχετε ήδη λογαριασμό; <a href="login.php">Σύνδεση</a>
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
                        strengthLabel = "Αδύναμος";
                        break;
                    case 1:
                        strengthClass = "fair";
                        strengthLabel = "Μέτριος";
                        break;
                    case 2:
                        strengthClass = "good";
                        strengthLabel = "Καλός";
                        break;
                    case 3:
                        strengthClass = "very-good";
                        strengthLabel = "Πολύ Καλός";
                        break;
                    case 4:
                        strengthClass = "strong";
                        strengthLabel = "Δυνατός";
                        break;
                }

                // Update strength bar
                strengthBar.removeClass().addClass("strength-bar " + strengthClass);
                strengthBar.css("width", (strength + 1) * 25 + "%");

                // Update strength text and color
                strengthText.removeClass().addClass("password-strength-text " + strengthClass);
                strengthText.text("Ισχύς Κωδικού: " + strengthLabel);
            });

            // Form submission validation
            $("#registration-form").submit(function (e) {
                const password = $("#password").val();
                const result = zxcvbn(password);
                const strength = result.score;

                if (strength < 1) { // Block submission if password is "Weak"
                    e.preventDefault();
                    toastr.error('Ο κωδικός σας είναι πολύ αδύναμος. Παρακαλώ επιλέξτε έναν ισχυρότερο κωδικό');
                }
            });
        });
    </script>
</body>
</html>