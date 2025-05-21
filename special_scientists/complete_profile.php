<?php
session_start();
require_once "database.php";

if (isset($_SESSION["user"])) {
    $userId = $_SESSION["user"]["id"];
    $email = $_SESSION["user"]["email"];
    $isSocialLogin = true;
} elseif (isset($_SESSION["manual_email"])) {
    $userId = null;
    $email = $_SESSION["manual_email"];
    $isSocialLogin = false;
} else {
    header("Location: registration.php");
    exit();
}

$errors = [];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullName = trim($_POST["fullname"]);
    $nameParts = preg_split('/\s+/', $fullName);
    if (count($nameParts) < 2 || count($nameParts) > 3) {
    $errors[] = "Full name must be 2 or 3 words (e.g., First Last or First Middle Last).";
}
    $firstName = $nameParts[0];
    $lastName = $nameParts[count($nameParts) - 1];
    $middleName = count($nameParts) > 2 ? implode(" ", array_slice($nameParts, 1, -1)) : null;
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $repeat_password = $_POST["repeat_password"];
    $country = $_POST["country"];
    $city = $_POST["city"];
    $address = $_POST["address"];
    $postcode = $_POST["postcode"];
    $dob = $_POST["dob"];
    $phone = $_POST["phone"];

    if (empty($fullName) || empty($username) || empty($password) || empty($repeat_password)
        || empty($country) || empty($city) || empty($address) || empty($postcode) || empty($dob) || empty($phone)) {
        $errors[] = "All fields are required!";
    }

    if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $errors[] = "Phone number is not valid!";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email is not valid!";
    }

    if (
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[\W_]/', $password)
    ) {
        $errors[] = "Password must be at least 8 characters and include an uppercase letter, a number, and a symbol.";
    }    

    if ($password !== $repeat_password) {
        $errors[] = "Passwords do not match!";
    }

        // Check for duplicate username
    if ($isSocialLogin) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $userId);
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
    }

    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $errors[] = "This username already exists, please choose a different one.";
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($isSocialLogin) {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, first_name = ?, middle_name = ?, last_name = ?, username = ?, password = ?, country = ?, city = ?, address = ?, postcode = ?, dob = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("ssssssssssssi", $fullName, $firstName, $middleName, $lastName, $username, $passwordHash, $country, $city, $address, $postcode, $dob, $phone, $userId);
            $stmt->execute();
            $_SESSION["user"]["profile_complete"] = true;
        } else {
            $stmt = $conn->prepare("INSERT INTO users (full_name, first_name, middle_name, last_name, username, email, password, country, city, address, postcode, dob, phone, role, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'user', 0)");
            $stmt->bind_param("sssssssssssss", $fullName, $firstName, $middleName, $lastName, $username, $email, $passwordHash, $country, $city, $address, $postcode, $dob, $phone);
            $stmt->execute();
            $newUserId = $stmt->insert_id;

            $_SESSION["user"] = [
                "id" => $newUserId,
                "full_name" => $fullName,
                "first_name" => $firstName,
                "middle_name" => $middleName,
                "last_name" => $lastName,
                "email" => $email,
                "role" => "user",
                "profile_complete" => true,
                "is_verified" => 0
            ];
            $_SESSION["user_id"] = $newUserId;
            $_SESSION["email"] = $email;
            $_SESSION["full_name"] = $fullName;

            unset($_SESSION["manual_email"]);
        }

        header("Location: select_verification_method.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complete Your Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.1.0/css/countrySelect.min.css" />
</head>
<body class="registration_page">

<div class="wizard-box">
    <div class="wizard-header">
        <img src="Tepak-logo.png" alt="CUT Logo" style="height: 60px;">
        <h3 class="mt-2">Complete Your Profile</h3>
        <div class="step-indicator position-relative">
  <div class="step-indicator-fill" id="step-progress" style="width: 33%;"></div>
  <div id="progress-percentage" class="progress-text">33%</div>
</div>
    </div>

    <form method="post" id="complete-profile-form">
        <div class="wizard-content">
            <!-- Step 1 -->
            <div class="form-step active" id="step1">
                <?php if (!empty($errors) && isset($_POST["fullname"])): ?>
                    <?php foreach ($errors as $error): ?>
                        <?php if (
                            in_array($error, ["All fields are required!", "Phone number is not valid!", "Email is not valid!"])
                        ): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="form-group"><label>Full Name</label>
                    <input type="text" name="fullname" class="form-control" placeholder="e.g. John Doe" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" required>
                    <?php
    if (!empty($errors)) {
        foreach ($errors as $error) {
            if (str_contains($error, "Full name must be")) {
                echo "<div class='text-danger mt-1'>" . htmlspecialchars($error) . "</div>";
            }
        }
    }
?>
                </div>
                <div class="form-group"><label>Username</label>
    <input type="text" name="username" id="username" class="form-control" placeholder="Choose a username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
    <small id="username-status" class="text-danger"></small>
    <?php if (!empty($errors)) {
        foreach ($errors as $error) {
            if (str_contains($error, "username")) {
                echo "<div class='text-danger mt-1'>" . htmlspecialchars($error) . "</div>";
            }
        }
    } ?>
</div>
                <div class="form-group"><label>Email Address</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($email) ?>" readonly>
                </div>
                <div class="form-group"><label>Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                </div>
                <div class="form-group"><label>Date of Birth</label>
                    <input type="date" name="dob" class="form-control" id="dob" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>" required>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="form-step" id="step2">
                <?php if (!empty($errors) && isset($_POST["password"])): ?>
                    <?php foreach ($errors as $error): ?>
                        <?php if (
                            in_array($error, ["All fields are required!", "Password must be at least 8 characters!", "Passwords do not match!"])
                        ): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="form-group"><label>Password</label>
                    <div class="input-group">
                        <input type="password" name="password" class="form-control" id="password" required>
                        <span class="input-group-text toggle-password" data-target="#password"><i class="bi bi-eye"></i></span>
                    </div>
                    <ul class="password-checklist mt-2" style="list-style: none; padding-left: 0; font-size: 14px;">
                    <li id="check-length"><span class="text-danger">✖</span> At least 8 characters</li>
                    <li id="check-uppercase"><span class="text-danger">✖</span> At least 1 uppercase letter</li>
                    <li id="check-number"><span class="text-danger">✖</span> At least 1 number</li>
                    <li id="check-symbol"><span class="text-danger">✖</span> At least 1 symbol</li>
                </ul>
                </div>
                <div class="form-group"><label>Confirm Password</label>
                    <div class="input-group">
                        <input type="password" name="repeat_password" class="form-control" id="confirm_password" required>
                        <span class="input-group-text toggle-password" data-target="#confirm_password"><i class="bi bi-eye"></i></span>
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="form-step" id="step3">
                <?php if (!empty($errors) && isset($_POST["country"])): ?>
                    <?php foreach ($errors as $error): ?>
                        <?php if ($error === "All fields are required!") : ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="form-group"><label>Country</label>
                    <input type="text" name="country" class="form-control country_input" id="country" value="<?= htmlspecialchars($_POST['country'] ?? '') ?>" required>
                </div>
                <div class="form-group"><label>City</label>
                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" required>
                </div>
                <div class="form-group"><label>Address</label>
                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" required>
                </div>
                <div class="form-group"><label>Postal Code</label>
                    <input type="text" name="postcode" class="form-control" value="<?= htmlspecialchars($_POST['postcode'] ?? '') ?>" required>
                </div>

            </div>

            <!-- Navigation -->
            <div class="wizard-actions">
                <button type="button" class="btn btn-secondary" id="prevBtn" style="display: none;">Back</button>
                <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
                <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">Finish</button>
            </div>
        </div>
    </form>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.1.0/js/countrySelect.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
<script>
let usernameValid = false;

$(document).ready(function () {
    $("input[name='fullname']").on("input", function () {
    const nameParts = $(this).val().trim().split(/\s+/);
    if (nameParts.length >= 2 && nameParts.length <= 3) {
        $(this).removeClass("is-invalid");
        $("#fullname-error").remove();
    }
});

    $("#country").countrySelect({ defaultCountry: "cy" });

    const iti = window.intlTelInput(document.querySelector("#phone"), {
        separateDialCode: true,
        initialCountry: "cy",
        preferredCountries: ['cy', 'gr', 'us'],
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
    });

    let currentStep = 1;
    const totalSteps = 3;

    function showStep(step) {
        $(".form-step").removeClass("active");
        $("#step" + step).addClass("active");

        $("#prevBtn").toggle(step > 1);
        $("#nextBtn").toggle(step < totalSteps);
        $("#submitBtn").toggle(step === totalSteps);

        $("#step-progress").css("width", (step / totalSteps) * 100 + "%");
        $("#progress-percentage").text(Math.round((step / totalSteps) * 100) + "%");
        $(`#step${step} input:not([type=hidden]):first`).focus();
    }

    $("#nextBtn").click(function () {
        if (currentStep === 1) {
            $("#username").trigger("blur");
        }

        const currentFields = $(`#step${currentStep} input:required`);
        let valid = true;

        currentFields.each(function () {
            if (!$(this).val().trim()) {
                $(this).addClass("is-invalid");
                valid = false;
            } else {
                $(this).removeClass("is-invalid");
            }
        });

        if (currentStep === 1 && !usernameValid) {
            $("#username").addClass("is-invalid");
            $("#username-status").text("This username already exists, please choose a different one.");
            valid = false;
        }

        if (currentStep === 1) {
    const fullName = $("input[name='fullname']").val().trim();
    const nameParts = fullName.split(/\s+/);
    if (nameParts.length < 2 || nameParts.length > 3) {
        $("input[name='fullname']").addClass("is-invalid");
        if ($("#fullname-error").length === 0) {
            $("<div id='fullname-error' class='text-danger mt-1'>Full name must be 2 or 3 words (e.g., First Last or First Middle Last).</div>").insertAfter("input[name='fullname']");
        }
        valid = false;
    } else {
        $("input[name='fullname']").removeClass("is-invalid");
        $("#fullname-error").remove();
    }
}

if (valid && currentStep < totalSteps) {
    currentStep++;
    showStep(currentStep);
}

    });

    $("#prevBtn").click(function () {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });

    $(".toggle-password").click(function () {
        const input = $($(this).data("target"));
        const type = input.attr("type") === "password" ? "text" : "password";
        input.attr("type", type);
        $(this).find("i").toggleClass("bi-eye bi-eye-slash");
    });

    $("#password").on("input", function () {
        const val = $(this).val();

        const checks = {
            length: val.length >= 8,
            uppercase: /[A-Z]/.test(val),
            number: /[0-9]/.test(val),
            symbol: /[\W_]/.test(val)
        };

        $("#check-length").html((checks.length ? '✅' : '<span class="text-danger">✖</span>') + ' At least 8 characters');
        $("#check-uppercase").html((checks.uppercase ? '✅' : '<span class="text-danger">✖</span>') + ' At least 1 uppercase letter');
        $("#check-number").html((checks.number ? '✅' : '<span class="text-danger">✖</span>') + ' At least 1 number');
        $("#check-symbol").html((checks.symbol ? '✅' : '<span class="text-danger">✖</span>') + ' At least 1 symbol');
    });

    $("#complete-profile-form").on("submit", function (e) {
        const fullPhone = iti.getNumber();

        if (!iti.isValidNumber()) {
            e.preventDefault();
            $("#phone").addClass("is-invalid");
            alert("Please enter a valid phone number.");
            return false;
        }

        $('#phone').val(fullPhone); // you can replace this with a hidden input if needed
    });

    $("#username").on("blur", function () {
        const username = $(this).val().trim();
        if (username.length === 0) return;

        $.post("check_username.php", { username }, function (response) {
            if (response === "taken") {
                $("#username-status").text("This username already exists, please choose a different one.");
                usernameValid = false;
            } else {
                $("#username-status").text("");
                usernameValid = true;
            }
        });
    });

    showStep(currentStep); // initialize first step
});
</script>
</body>
</html>
