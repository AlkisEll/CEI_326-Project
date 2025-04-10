<?php
session_start();
require_once "database.php";

$appId = '4012641692398081';
$appSecret = '5b7208c90d3c93406c0a703de1bf5f67';
$redirectUri = 'http://localhost/login-register/facebook_callback.php';

if (!isset($_GET['code'])) {
    die('Facebook login failed.');
}

$code = $_GET['code'];

// Exchange code for access token
$tokenUrl = "https://graph.facebook.com/v17.0/oauth/access_token?" . http_build_query([
    'client_id' => $appId,
    'redirect_uri' => $redirectUri,
    'client_secret' => $appSecret,
    'code' => $code
]);

$response = file_get_contents($tokenUrl);
$data = json_decode($response, true);
$accessToken = $data['access_token'] ?? null;

if (!$accessToken) {
    die('Failed to get access token.');
}

// Fetch user data
$userData = file_get_contents("https://graph.facebook.com/me?fields=id,name,email&access_token=$accessToken");
$user = json_decode($userData, true);

$fullName = $user['name'] ?? '';
$email = $user['email'] ?? '';

if (!$email) {
    die("Facebook did not return an email address.");
}

// Check if user exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    // Insert new user with only name/email
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, is_verified) VALUES (?, ?, 1)");
    $stmt->bind_param("ss", $fullName, $email);
    $stmt->execute();

    $user = [
        'id' => $stmt->insert_id,
        'full_name' => $fullName,
        'email' => $email,
        'role' => 'user'
    ];
}

// Check if their profile is complete
$is_complete = !(
    empty($user['country']) ||
    empty($user['city']) ||
    empty($user['address']) ||
    empty($user['postcode']) ||
    empty($user['dob']) ||
    empty($user['phone'])
);

// Save user session
$_SESSION['user'] = [
    'id' => $user['id'],
    'full_name' => $user['full_name'],
    'email' => $user['email'],
    'role' => $user['role'] ?? 'user',
    'profile_complete' => $is_complete
];

// Redirect appropriately
if ($is_complete) {
    header("Location: index.php");
} else {
    header("Location: complete_profile.php");
}
exit();
?>
