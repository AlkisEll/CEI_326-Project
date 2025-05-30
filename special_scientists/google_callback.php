<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "database.php";

$clientID = '298135741411-6gbhvfmubpk1vjgbeervmma5mntarggk.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'https://cei326-omada7.cut.ac.cy/special_scientists/google_callback.php';

if (!isset($_GET['code'])) {
    header('Location: registration.php');
    exit();
}

$code = $_GET['code'];

// Exchange code for access token
$tokenRequest = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($tokenRequest, CURLOPT_POST, true);
curl_setopt($tokenRequest, CURLOPT_RETURNTRANSFER, true);
curl_setopt($tokenRequest, CURLOPT_POSTFIELDS, http_build_query([
    'code' => $code,
    'client_id' => $clientID,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri,
    'grant_type' => 'authorization_code'
]));
$response = curl_exec($tokenRequest);
curl_close($tokenRequest);
$tokenData = json_decode($response, true);

if (!isset($tokenData['access_token'])) {
    echo "Failed to get access token.";
    exit();
}

// Fetch user info
$userRequest = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt($userRequest, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $tokenData['access_token']
]);
curl_setopt($userRequest, CURLOPT_RETURNTRANSFER, true);
$userInfo = json_decode(curl_exec($userRequest), true);
curl_close($userRequest);

$fullName = $userInfo['name'] ?? '';
$email = $userInfo['email'] ?? '';

if (!$email) {
    echo "Google account did not return an email.";
    exit();
}

// Check if user already exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    $dummyPassword = password_hash(uniqid(), PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, is_verified) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $fullName, $email, $dummyPassword);
    $stmt->execute();

    $user = [
        'id' => $stmt->insert_id,
        'full_name' => $fullName,
        'email' => $email,
        'country' => null,
        'city' => null,
        'address' => null,
        'postcode' => null,
        'dob' => null,
        'phone' => null,
        'role' => 'user'
    ];
}

// ✅ Fetch previous last_login before updating
$prevLogin = null;
$getLogin = $conn->prepare("SELECT last_login FROM users WHERE id = ?");
$getLogin->bind_param("i", $user['id']);
$getLogin->execute();
$loginResult = $getLogin->get_result();
if ($row = $loginResult->fetch_assoc()) {
    $prevLogin = $row['last_login'];
}

// ✅ Update last_login to now
$updateLogin = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
$updateLogin->bind_param("i", $user['id']);
$updateLogin->execute();

// Check profile completeness
$is_complete = !(
    empty($user['country']) ||
    empty($user['city']) ||
    empty($user['address']) ||
    empty($user['postcode']) ||
    empty($user['dob']) ||
    empty($user['phone'])
);

// ✅ Store in session
$_SESSION['user'] = [
    'id' => $user['id'],
    'full_name' => $user['full_name'],
    'email' => $user['email'],
    'role' => $user['role'] ?? 'user',
    'profile_complete' => $is_complete,
    'last_login' => $prevLogin
];

// Redirect
header("Location: " . ($is_complete ? "index.php" : "complete_profile.php"));
exit();
?>
