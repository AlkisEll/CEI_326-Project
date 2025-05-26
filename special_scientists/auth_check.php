<?php
session_start();
require_once "database.php";

if (!isset($_SESSION['user'])) return;

$userId = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT is_verified FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row || $row['is_verified'] !== '1') {
    echo <<<HTML
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .verification-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .verification-box {
            background: white;
            color: #212529;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            text-align: center;
        }
        body.dark-mode .verification-box {
            background: #2c2c2c;
            color: #f8f9fa;
        }
    </style>

    <div class="verification-overlay">
        <div class="verification-box">
            <h4 class="mb-3 text-danger">🔒 Verification Required</h4>
            <p class="mb-3">You need to verify your email or phone number before accessing the website.</p>
            <a href="select_verification_method.php" class="btn btn-primary px-4">Verify Here</a>
        </div>
    </div>
    <script>document.body.style.overflow = 'hidden';</script>
HTML;
    exit();
}
?>
