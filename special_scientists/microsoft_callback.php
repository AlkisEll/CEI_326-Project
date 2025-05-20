<?php
session_start();

$client_id = '56612818-b196-4abd-944f-198689fee50c';
$client_secret = 'IpX8Q~Q5ohhAejsYGRgIUadGGBFjIql21ptJab7f';
$redirect_uri = 'http://cei326-omada7.cut.ac.cy/special-scientists/microsoft_callback.php';

if (!isset($_GET['code'])) {
    echo 'Microsoft login failed: ' . htmlspecialchars($_GET['error_description'] ?? 'No code returned.');
    exit;
}

$code = $_GET['code'];

// Exchange authorization code for access token
$token_url = "https://login.microsoftonline.com/common/oauth2/v2.0/token";
$token_data = [
    'client_id' => $client_id,
    'scope' => 'https://graph.microsoft.com/user.read',
    'code' => $code,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code',
    'client_secret' => $client_secret
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
$response = curl_exec($ch);
curl_close($ch);

$token_response = json_decode($response, true);

if (!isset($token_response['access_token'])) {
    echo 'Failed to get access token:<br>';
    var_dump($token_response);
    exit;
}

$access_token = $token_response['access_token'];

// Fetch user info
$ch = curl_init("https://graph.microsoft.com/v1.0/me");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$user_info = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($user_info['mail']) && !isset($user_info['userPrincipalName'])) {
    echo "Microsoft account did not return an email.";
    exit;
}

$email = $user_info['mail'] ?? $user_info['userPrincipalName'];
$name = $user_info['displayName'] ?? 'Microsoft User';

require_once "database.php";

// Check if user exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // ✅ Fetch previous login before updating
    $prevLogin = null;
    $getLogin = $conn->prepare("SELECT last_login FROM users WHERE id = ?");
    $getLogin->bind_param("i", $user['id']);
    $getLogin->execute();
    $loginResult = $getLogin->get_result();
    if ($row = $loginResult->fetch_assoc()) {
        $prevLogin = $row['last_login'];
    }

    // ✅ Update login timestamp
    $updateLogin = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateLogin->bind_param("i", $user['id']);
    $updateLogin->execute();

    // ✅ Store in session
    $_SESSION['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'role' => $user['role'],
        'profile_complete' => $user['profile_complete'],
        'last_login' => $prevLogin
    ];

    header("Location: index.php");
    exit();
} else {
    // New user, send to complete_profile
    $_SESSION["manual_email"] = $email;
    $_SESSION["manual_name"] = $name;
    header("Location: complete_profile.php");
    exit();
}
?>
