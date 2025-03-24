<?php
session_start();

if (isset($_GET['phone'])) {
    $_SESSION["phone"] = $_GET['phone'];
}

if (!isset($_SESSION["phone"]) || !isset($_SESSION["verification_code"])) {
    header("Location: registration.php");
    exit();
}

require_once "database.php";

if (isset($_POST["verify"])) {
    $phone = $_SESSION["phone"];
    $verification_code = $_POST["verification_code"];

    // Check if the verification code matches
    $sql = "SELECT * FROM users WHERE phone = ? AND verification_code = ?";
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $phone, $verification_code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rowCount = mysqli_num_rows($result);

        if ($rowCount > 0) {
            // Fetch user data
            $row = mysqli_fetch_assoc($result);

            // Update the user's verification status
            $sql = "UPDATE users SET is_verified = 1 WHERE phone = ?";
            $stmt = mysqli_stmt_init($conn);
            if (mysqli_stmt_prepare($stmt, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $phone);
                mysqli_stmt_execute($stmt);

                // Store user data in session
                $_SESSION["user"] = [
                    "id" => $row["id"],
                    "email" => $row["email"],
                    "full_name" => $row["full_name"]
                ];

                // Set a session variable to indicate successful verification
                $_SESSION["verification_success"] = true;

                // Redirect to index.php after successful verification
                header("Location: index.php");
                exit();
            } else {
                die("Κάτι πήγε στραβά. Δοκιμάστε ξανά αργότερα.");
            }
        } else {
            // Store error message in session
            $_SESSION['error'] = 'Λανθασμένος κωδικός επαλήθευσης.';
        }
    } else {
        die("Κάτι πήγε στραβά. Δοκιμάστε ξανά αργότερα.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Phone</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
</head>
<body>
    <div class="container">
        <h2>Επαληθεύστε τον αριθμό τηλεφώνου σας</h2>
        <form method="post" action="verify_phone.php">
            <div class="form-group">
                <label for="verification_code">Κωδικός Επαλήθευσης</label>
                <input type="text" class="form-control" name="verification_code" placeholder="Εισάγετε τον κωδικό επαλήθευσης σας" required>
            </div>
            <div class="form-btn">
                <input type="submit" class="btn btn-primary" value="Επαλήθευση" name="verify">
            </div>
        </form>
    </div>

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        // Toastr configuration
        toastr.options = {
            closeButton: true, // Show close button
            progressBar: true, // Show progress bar
            positionClass: 'toast-top-right', // Position of the toast
            timeOut: 5000, // Time to auto-close the toast
            extendedTimeOut: 1000, // Additional time if the user hovers over the toast
        };

        // Display error message from PHP using Toastr
        <?php if (isset($_SESSION['error'])): ?>
            toastr.error("<?php echo $_SESSION['error']; ?>");
            <?php unset($_SESSION['error']); // Clear the error message after displaying ?>
        <?php endif; ?>
    </script>
</body>
</html>