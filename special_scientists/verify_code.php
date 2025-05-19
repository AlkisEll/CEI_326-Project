<?php
session_start();
require_once "database.php";

// Get data from the AJAX request
$phone = $_POST['phone'];
$verification_code = $_POST['verification_code'];

// Check if the verification code exists in the database
$sql = "SELECT * FROM users WHERE phone = ? AND verification_code = ?";
$stmt = mysqli_stmt_init($conn);
if (mysqli_stmt_prepare($stmt, $sql)) {
    mysqli_stmt_bind_param($stmt, "ss", $phone, $verification_code);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $rowCount = mysqli_stmt_num_rows($stmt);

    if ($rowCount > 0) {
        // Verification code is valid, update is_verified to 1
        $sql = "UPDATE users SET is_verified = 1 WHERE phone = ?";
        $stmt = mysqli_stmt_init($conn);
        if (mysqli_stmt_prepare($stmt, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $phone);
            mysqli_stmt_execute($stmt);

            // Return success response
            echo json_encode(['success' => true]);
        } else {
            die("Something went wrong. Please try again later.");
        }
    } else {
        // Verification code is invalid
        echo json_encode(['success' => false]);
    }
} else {
    die("Something went wrong. Please try again later.");
}
?>
