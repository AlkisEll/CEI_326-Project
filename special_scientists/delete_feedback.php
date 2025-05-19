<?php
session_start();
require_once "database.php";

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'owner'])) {
    http_response_code(403);
    exit("Unauthorized");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
    $id = intval($_POST["id"]);
    $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        http_response_code(200);
    } else {
        http_response_code(500);
        echo "Delete failed";
    }
    exit();
}

http_response_code(400);
echo "Invalid request";
