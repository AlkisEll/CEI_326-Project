<?php
session_start();
require_once "database.php";

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'owner'])) {
    http_response_code(403);
    exit("Unauthorized");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($conn->query("DELETE FROM feedback")) {
        http_response_code(200);
    } else {
        http_response_code(500);
        echo "Clear failed";
    }
    exit();
}

http_response_code(400);
echo "Invalid request";
