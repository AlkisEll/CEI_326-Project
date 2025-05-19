<?php
session_start();
require_once "database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["feedback"])) {
    $userId = $_SESSION['user']['id'] ?? null;
    $message = trim($_POST["feedback"]);

    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, message, submitted_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("is", $userId, $message);
        $stmt->execute();
    }
}

header("Location: index.php");
exit();
?>
