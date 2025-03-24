<?php
session_start();
require_once "database.php";

if (isset($_POST["verify"])) {
    $code = trim($_POST["twofa_code"]);
    $user_id = $_SESSION["temp_user_id"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE id=? AND twofa_code=? AND twofa_expires > NOW()");
    $stmt->bind_param("is", $user_id, $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $_SESSION["user"] = [
            "id" => $user["id"],
            "email" => $user["email"],
            "full_name" => $user["full_name"]
        ];
        unset($_SESSION['temp_user_id']);
        header("Location: index.php");
        exit();
    } else {
        $error = "Μη έγκυρος ή ληγμένος κωδικός.";
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Έλεγχος 2FA</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <form action="twofa_verify.php" method="post">
        <h2>Έλεγχος Ταυτότητας 2 Παραγόντων (Two Factor Authentication - 2FA)</h2>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <div class="form-group">
            <label for="twofa_code">Κωδικός 2FA (Ελέγξετε το Email σας για τον 2FA κωδικό)</label>
            <input type="text" name="twofa_code" id="twofa_code" class="form-control" placeholder="Εισάγετε τον 2FA κωδικό" required maxlength="6">
        </div>
        <input type="submit" name="verify" class="btn btn-primary" value="Επαλήθευση Κωδικού">
    </form>
</div>
</body>
</html>
