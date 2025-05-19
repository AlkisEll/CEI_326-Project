<?php
session_start();
if (isset($_POST['lang'])) {
    $_SESSION['lang'] = $_POST['lang'] === 'el' ? 'el' : 'en';
}
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();