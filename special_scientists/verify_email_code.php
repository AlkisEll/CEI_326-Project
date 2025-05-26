<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user"]) || !isset($_SESSION["pending_email"])) {
    header("Location: my_profile.php");
    exit();
}

$userId       = $_SESSION["user"]["id"];
$pendingEmail = $_SESSION["pending_email"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $inputCode = trim($_POST["verification_code"] ?? '');

    $stmt = $conn->prepare("SELECT id FROM email_verifications WHERE user_id = ? AND email = ? AND code = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("iss", $userId, $pendingEmail, $inputCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Update email
        $update = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $update->bind_param("si", $pendingEmail, $userId);
        $update->execute();

        // Cleanup
        $conn->prepare("DELETE FROM email_verifications WHERE user_id = ?")->bind_param("i", $userId)->execute();

        $_SESSION["user"]["email"] = $pendingEmail;
        unset($_SESSION["pending_email"]);
        $_SESSION["profile_success"] = "Email Address has been changed successfully!";
        header("Location: my_profile.php");
        exit();
    } else {
        $error = "Invalid or expired code.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify Email Address</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="card p-4 mx-auto shadow" style="max-width: 500px;">
      <h4 class="mb-3">Verify Your Email Address</h4>
      <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="mb-3">
          <label for="verification_code" class="form-label">Verification Code</label>
          <input type="text" class="form-control" name="verification_code" pattern="\d{6}" required maxlength="6">
        </div>
        <button type="submit" class="btn btn-primary w-100">Verify</button>
      </form>
    </div>
  </div>
</body>
</html>