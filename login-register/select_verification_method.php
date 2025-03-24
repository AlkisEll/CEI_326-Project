<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: registration.php");
    exit();
}

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Database connection
require_once "database.php";

// Twilio credentials
$accountSid = 'ACed50809afda0163369b2505abc4354f7'; // Your Twilio Account SID
$authToken = 'e2da96226efadb15d09d2dee1b81836b'; // Your Twilio Auth Token
$twilioPhoneNumber = '+12182616825'; // Your Twilio phone number

if (isset($_POST["select_method"])) {
    $verificationMethod = $_POST["verification_method"];
    $phone = $_POST["phone"] ?? ''; // Phone number (if phone verification is selected)

    $verification_code = rand(100000, 999999); // Generate a random 6-digit verification code

    if ($verificationMethod === 'email') {
        // Update user data with verification code
        $sql = "UPDATE users SET verification_code = ? WHERE id = ?";
        $stmt = mysqli_stmt_init($conn);
        if (mysqli_stmt_prepare($stmt, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $verification_code, $_SESSION["user_id"]);
            mysqli_stmt_execute($stmt);

            // Send verification email
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->SMTPDebug = 0; // Disable verbose debug output
                $mail->isSMTP();
                $mail->Host = 'premium245.web-hosting.com'; // Set your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'admin@festival-web.com'; // SMTP username
                $mail->Password = '!g3$~8tYju*D'; // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587; // Use TLS port

                // Recipients
                $mail->setFrom('admin@festival-web.com', 'Festival-web Admin');
                $mail->addAddress($_SESSION["email"], $_SESSION["full_name"]);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Verify your email';
                $mail->Body = "<p>Ο κωδικός επαλήθευσης είναι: <b>$verification_code</b></p>"; // Email body with verification code
                $mail->send();
                echo "<div class='alert alert-success'>Ο κωδικός επαλήθευσης έχει σταλεί στο Email σας. <a href='verify.php'>Πατήστε εδώ για να εισάγετε των κωδικό επαλήθευσης.</a></div>";
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>Αποτυχία αποστολής κωδικού επαλήθευσης. Δοκιμάστε ξανά αργότερα.</div>";
            }
        } else {
            die("Κάτι πήγε στραβά. Δοκιμάστε ξανά αργότερα.");
        }
    } elseif ($verificationMethod === 'phone') {
        if (empty($phone)) {
            echo "<div class='alert alert-danger'>Ένα νούμερο τηλεφώνου απαιτείται για επαλήθευση τηλεφώνου!</div>";
        } else {
            // Update user data with phone and verification code
            $sql = "UPDATE users SET phone = ?, verification_code = ? WHERE id = ?";
            $stmt = mysqli_stmt_init($conn);
            if (mysqli_stmt_prepare($stmt, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssi", $phone, $verification_code, $_SESSION["user_id"]);
                mysqli_stmt_execute($stmt);

                // Store phone and verification code in session
                $_SESSION["phone"] = $phone;
                $_SESSION["verification_code"] = $verification_code;

                // Prepare the SMS message
                $body = "Ο κωδικός επαλήθευσης είναι: $verification_code";

                // Prepare the POST data for Twilio
                $postData = http_build_query([
                    'From' => $twilioPhoneNumber,
                    'To' => $phone,
                    'Body' => $body,
                ]);

                // Initialize cURL
                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://api.twilio.com/2010-04-01/Accounts/$accountSid/Messages.json",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $postData,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/x-www-form-urlencoded',
                        'Authorization: Basic ' . base64_encode("$accountSid:$authToken"),
                    ],
                    CURLOPT_SSL_VERIFYPEER => false, // Disable SSL verification (not recommended)
                    CURLOPT_SSL_VERIFYHOST => false, // Disable SSL host verification (not recommended)
                ]);

                // Execute the request
                $response = curl_exec($curl);

                // Check for errors
                if (curl_errno($curl)) {
                    echo "<div class='alert alert-danger'>Failed to send SMS: " . curl_error($curl) . "</div>";
                } else {
                    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    if ($httpCode === 201) {
                        // Redirect to verify_phone.php after successful SMS sending
                        header("Location: verify_phone.php");
                        exit();
                    } else {
                        header("Location: verify_phone.php");
                        $responseData = json_decode($response, true);
                        $errorMessage = $responseData['message'] ?? 'Αποτυχία αποστολής SMS.';
                        //echo "<div class='alert alert-danger'>$errorMessage</div>";
                    }
                }

                // Close cURL
                curl_close($curl);
            } else {
                die("Κάτι πήγε στραβά. Δοκιμάστε ξανά αργότερα.");
            }
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
    <title>Select Verification Method</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <!-- intl-tel-input CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="https://www.cut.ac.cy" class="logo-link" target="_blank" title="Μετάβαση στην ιστοσελίδα του ΤΕΠΑΚ"></a>
        <h2>Επιλέξτε μέθοδο επαλήθευσης</h2>
        <form method="post" action="select_verification_method.php" id="verification-form">
            <!-- Hidden input fields for session data -->
            <input type="hidden" id="fullname" name="fullname" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>">
            <input type="hidden" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>">

            <div class="form-group">
                <label for="verification_method">Μέθοδος Επαλήθευσης</label>
                <select class="form-control" name="verification_method" id="verification_method" required>
                    <option value="email">Επαλήθευση με Email</option>
                    <option value="phone">Επαλήθευση με Τηλέφωνο</option>
                </select>
            </div>
            <div class="form-group phone_row" id="phone-field" style="display: none;">
                <input style="width: 441px;" type="tel" class="form-control" id="phone" name="phone" placeholder="Εισαγωγή αρ. τηλεφώνου">
                <div id="phone-error" class="error-message alert alert-danger" style="display: none;">Λανθασμένο νούμερο τηλεφώνου για την συγκεκριμένη χώρα.</div>
            </div>
            <div class="form-btn">
                <input type="submit" class="btn btn-primary" value="Συνέχεια" name="select_method">
            </div>
        </form>
    </div>

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- intl-tel-input JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
    $(document).ready(function () {
        let iti; // Global variable to store the intlTelInput instance

        // Show/hide phone field based on verification method
        $('#verification_method').change(function () {
            if ($(this).val() === 'phone') {
                $('#phone-field').show();
                initializePhoneInput();
            } else {
                $('#phone-field').hide();
            }
        });

        // Initialize intl-tel-input
        function initializePhoneInput() {
            const phoneInput = document.querySelector("#phone");
            iti = window.intlTelInput(phoneInput, {
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
                separateDialCode: true,
                preferredCountries: ['us', 'gb', 'gr'], // Default preferred countries
                initialCountry: "auto", // Auto-detect user's country
            });
        }

        // Form submission validation
        $('#verification-form').submit(function (e) {
            const verificationMethod = $('#verification_method').val();

            if (verificationMethod === 'phone') {
                const phoneInput = $('#phone');
                const phoneError = $('#phone-error');

                // Validate phone number
                if (!iti.isValidNumber()) {
                    e.preventDefault(); // Prevent form submission if number is invalid
                    toastr.error('Λανθασμένο νούμερο τηλεφώνου για την συγκεκριμένη χώρα.'); // Show toastr error
                    return; // Stop execution
                } else {
                    phoneError.hide(); // Hide error message if number is valid
                }

                // Get full phone number with country code
                const fullPhoneNumber = iti.getNumber();

                // Add the full phone number to a hidden input field
                $('<input>').attr({
                    type: 'hidden',
                    name: 'phone',
                    value: fullPhoneNumber
                }).appendTo('#verification-form');
            }
        });
    });
    </script>
    <?php if (isset($_SESSION['error'])): ?>
        <script>
            toastr.error("<?php echo $_SESSION['error']; ?>");
        </script>
        <?php unset($_SESSION['error']); // Clear the error message after displaying ?>
    <?php endif; ?>
</body>
</html>