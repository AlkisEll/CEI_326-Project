<?php
require_once "database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["username"])) {
    $username = trim($_POST["username"]);
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    echo $stmt->num_rows > 0 ? "taken" : "available";

    $stmt->close();
    $conn->close();
    exit; // ✅ CRITICAL: prevents extra output
}
?>
