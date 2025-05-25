<?php
session_start();
require_once "database.php";

if (isset($_SESSION["user"])) {
    $userId        = $_SESSION["user"]["id"];
    $email         = $_SESSION["user"]["email"];
    $isSocialLogin = true;
} elseif (isset($_SESSION["manual_email"])) {
    $userId        = null;
    $email         = $_SESSION["manual_email"];
    $isSocialLogin = false;
} else {
    header("Location: registration.php");
    exit();
}

$errors = [];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullName        = trim($_POST["fullname"]);
    $nameParts       = preg_split('/\s+/', $fullName);
    $firstName       = $nameParts[0] ?? '';
    $lastName        = end($nameParts) ?: '';
    $middleName      = count($nameParts) > 2
                       ? implode(" ", array_slice($nameParts, 1, -1))
                       : null;
    $username        = trim($_POST["username"]);
    $password        = $_POST["password"];
    $repeat_password = $_POST["repeat_password"];
    $country         = $_POST["country"];
    $city            = trim($_POST["city"]);
    $address         = trim($_POST["address"]);
    $postcode        = trim($_POST["postcode"]);
    $dob             = $_POST["dob"];
    $phone           = $_POST["phone"];

    // Full Name must not contain digits
    if (preg_match('/\d/', $fullName)) {
        $errors[] = "Please enter a proper Full Name!";
    }
    // Full Name word count
    if (count($nameParts) < 2 || count($nameParts) > 3) {
        $errors[] = "Full name must be 2 or 3 words (e.g., First Last or First Middle Last).";
    }

    if (empty($fullName) || empty($username) || empty($password) ||
        empty($repeat_password) || empty($country) || empty($city) ||
        empty($address) || empty($postcode) || empty($dob) ||
        empty($phone)) {
        $errors[] = "All fields are required!";
    }

    // Phone validation
    if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $errors[] = "Phone number is not valid!";
    }

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email is not valid!";
    }

    // Password strength
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

    // Date of Birth age check
    if ($dob) {
        $dobDate = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$dobDate) {
            $errors[] = "Date of Birth is invalid!";
        } else {
            $today = new DateTime();
            $age   = $today->diff($dobDate)->y;
            if ($age < 16 || $age > 100) {
                $errors[] = "Date of Birth can not be more than 100 years or less than 16 years old!";
            }
        }
    }

    // **City validation: only letters & spaces**
    if (!preg_match('/^[a-zA-Z\s]+$/', $city)) {
        $errors[] = "City can only contain letters and spaces!";
    }

    // **Postal Code validation: only digits**
    if (!preg_match('/^[0-9]+$/', $postcode)) {
        $errors[] = "Postal Code can only contain numbers!";
    }

    // Check duplicate username
    if ($isSocialLogin) {
        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE username = ? AND id != ?"
        );
        $stmt->bind_param("si", $username, $userId);
    } else {
        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE username = ?"
        );
        $stmt->bind_param("s", $username);
    }
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "This username already exists, please choose a different one.";
    }

    // If no errors, save to DB
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if ($isSocialLogin) {
            $stmt = $conn->prepare("
                UPDATE users
                   SET full_name   = ?,
                       first_name  = ?,
                       middle_name = ?,
                       last_name   = ?,
                       username    = ?,
                       password    = ?,
                       country     = ?,
                       city        = ?,
                       address     = ?,
                       postcode    = ?,
                       dob         = ?,
                       phone       = ?
                 WHERE id = ?
            ");
            $stmt->bind_param(
                "ssssssssssssi",
                $fullName,
                $firstName,
                $middleName,
                $lastName,
                $username,
                $passwordHash,
                $country,
                $city,
                $address,
                $postcode,
                $dob,
                $phone,
                $userId
            );
            $stmt->execute();
            $_SESSION["user"]["profile_complete"] = true;
        } else {
            $stmt = $conn->prepare("
                INSERT INTO users (
                    full_name, first_name, middle_name, last_name,
                    username, email, password, country, city,
                    address, postcode, dob, phone, role, is_verified
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'user', 0)
            ");
            $stmt->bind_param(
                "sssssssssssss",
                $fullName,
                $firstName,
                $middleName,
                $lastName,
                $username,
                $email,
                $passwordHash,
                $country,
                $city,
                $address,
                $postcode,
                $dob,
                $phone
            );
            $stmt->execute();
            $newUserId = $stmt->insert_id;
            $_SESSION["user"] = [
                "id"               => $newUserId,
                "full_name"        => $fullName,
                "first_name"       => $firstName,
                "middle_name"      => $middleName,
                "last_name"        => $lastName,
                "email"            => $email,
                "role"             => "user",
                "profile_complete" => true,
                "is_verified"      => 0
            ];
            $_SESSION["user_id"]   = $newUserId;
            $_SESSION["email"]     = $email;
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
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"
    rel="stylesheet"
  >
  <link rel="stylesheet" href="style.css">
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.min.css"
    rel="stylesheet"
  >
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.1.0/css/countrySelect.min.css"
    rel="stylesheet"
  >
</head>
<body class="registration_page">

<div class="wizard-box">
  <div class="wizard-header">
    <img src="Tepak-logo.png" alt="CUT Logo" style="height:60px">
    <h3 class="mt-2">Complete Your Profile</h3>
    <div class="step-indicator position-relative">
      <div
        class="step-indicator-fill"
        id="step-progress"
        style="width:33%;"
      ></div>
      <div id="progress-percentage" class="progress-text">33%</div>
    </div>
  </div>

  <form method="post" id="complete-profile-form">
    <div class="wizard-content">
      <!-- Step 1 -->
      <div class="form-step active" id="step1">
        <!-- … your existing Step 1 markup … -->
      </div>

      <!-- Step 2 -->
      <div class="form-step" id="step2">
        <!-- … your existing Step 2 markup … -->
      </div>

      <!-- Step 3 -->
      <div class="form-step" id="step3">
        <?php if (!empty($errors) && isset($_POST["country"])): ?>
          <?php foreach ($errors as $error): ?>
            <?php if ($error === "All fields are required!"): ?>
              <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>

        <div class="form-group">
          <label>Country</label>
          <input
            type="text"
            name="country"
            class="form-control country_input"
            id="country"
            value="<?= htmlspecialchars($_POST['country'] ?? '') ?>"
            required
          >
        </div>

        <div class="form-group">
          <label>City</label>
          <input
            type="text"
            name="city"
            class="form-control"
            pattern="[A-Za-z\s]+"
            title="City can only contain letters and spaces"
            value="<?= htmlspecialchars($_POST['city'] ?? '') ?>"
            required
          >
        </div>

        <div class="form-group">
          <label>Address</label>
          <input
            type="text"
            name="address"
            class="form-control"
            value="<?= htmlspecialchars($_POST['address'] ?? '') ?>"
            required
          >
        </div>

        <div class="form-group">
          <label>Postal Code</label>
          <input
            type="text"
            name="postcode"
            class="form-control"
            pattern="\d+"
            title="Postal Code can only contain numbers"
            value="<?= htmlspecialchars($_POST['postcode'] ?? '') ?>"
            required
          >
        </div>
      </div>

      <!-- Navigation -->
      <div class="wizard-actions">
        <button
          type="button"
          class="btn btn-secondary"
          id="prevBtn"
          style="display:none;"
        >Back</button>
        <button
          type="button"
          class="btn btn-primary"
          id="nextBtn"
        >Next</button>
        <button
          type="submit"
          class="btn btn-success"
          id="submitBtn"
          style="display:none;"
        >Finish</button>
      </div>
    </div>
  </form>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script
  src="https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.1.0/js/countrySelect.min.js"
></script>
<script
  src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"
></script>
<script
  src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"
></script>
<script>
$(document).ready(function () {
  // Country selector
  $("#country").countrySelect({ defaultCountry: "cy" });

  // International phone input
  const iti = window.intlTelInput(
    document.querySelector("#phone"),
    {
      separateDialCode: true,
      initialCountry: "cy",
      preferredCountries: ['cy','gr','us'],
      utilsScript:
        "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
    }
  );

  let currentStep = 1, totalSteps = 3;
  function showStep(step) { /* … unchanged … */ }
  $("#nextBtn").click(function () { /* … unchanged … */ });
  $("#prevBtn").click(function () { /* … unchanged … */ });
  $(".toggle-password").click(function () { /* … unchanged … */ });
  $("#password").on("input", function () { /* … unchanged … */ });
  $("#complete-profile-form").on("submit", function (e) {
    // **Phone validation**
    const fullPhone = iti.getNumber();
    if (!iti.isValidNumber()) {
      e.preventDefault();
      $("#phone").addClass("is-invalid");
      alert("Please enter a valid phone number.");
      return false;
    }
    $('#phone').val(fullPhone);

    // **City validation**
    const cityVal = $("input[name='city']").val().trim();
    if (!/^[A-Za-z\s]+$/.test(cityVal)) {
      e.preventDefault();
      $("input[name='city']").addClass("is-invalid");
      if (!$("#city-error").length) {
        $("<div id='city-error' class='text-danger mt-1'>City can only contain letters and spaces.</div>")
          .insertAfter("input[name='city']");
      }
      return false;
    } else {
      $("input[name='city']").removeClass("is-invalid");
      $("#city-error").remove();
    }

    // **Postal Code validation**
    const postcodeVal = $("input[name='postcode']").val().trim();
    if (!/^\d+$/.test(postcodeVal)) {
      e.preventDefault();
      $("input[name='postcode']").addClass("is-invalid");
      if (!$("#postcode-error").length) {
        $("<div id='postcode-error' class='text-danger mt-1'>Postal Code can only contain numbers.</div>")
          .insertAfter("input[name='postcode']");
      }
      return false;
    } else {
      $("input[name='postcode']").removeClass("is-invalid");
      $("#postcode-error").remove();
    }

    // If we get here, all validations passed and the form will submit
  });

  $("#username").on("blur", function () { /* … unchanged … */ });
  showStep(currentStep);
});
</script>
</body>
</html>
