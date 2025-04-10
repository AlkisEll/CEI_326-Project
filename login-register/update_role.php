<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user"]) || !in_array($_SESSION["user"]["role"], ['admin', 'owner'])) {

    header("Location: index.php");
    exit();
}

if (isset($_GET["id"]) && isset($_GET["role"])) {
    $userId = (int)$_GET["id"];
    $newRole = ($_GET["role"] === 'admin') ? 'admin' : 'user';

    // Prevent editing owner's role
    $check = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $check->bind_param("i", $userId);
    $check->execute();
    $res = $check->get_result();
    $target = $res->fetch_assoc();

    if ($target && $target["role"] === 'owner') {
        header("Location: manage_users.php?error=cannot_change_owner");
        exit();
    }

    if ($userId !== $_SESSION["user"]["id"]) {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $newRole, $userId);
        $stmt->execute();
    }
}

header("Location: manage_users.php");
exit();
