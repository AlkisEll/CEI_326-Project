<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user"]) || !in_array($_SESSION["user"]["role"], ['admin', 'owner'])) {

    header("Location: index.php");
    exit();
}

if (isset($_GET["id"])) {
    $userId = (int)$_GET["id"];

    // Prevent deleting owner
    $check = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $check->bind_param("i", $userId);
    $check->execute();
    $res = $check->get_result();
    $target = $res->fetch_assoc();

    if ($target && $target["role"] === 'owner') {
        header("Location: manage_users.php?error=cannot_delete_owner");
        exit();
    }

    if ($userId !== $_SESSION["user"]["id"]) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
}

header("Location: manage_users.php");
exit();
