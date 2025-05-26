<?php
// File: auth_check.php
session_start();
require_once "database.php";

// If user is not logged in, allow them to access login/registration/etc.
if (!isset($_SESSION['user'])) {
    return;
}

// Check verification status in DB (avoids trusting outdated session data)
$userId = $_SESSION['user']['id'];

$stmt = $conn->prepare("SELECT is_verified FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row || $row['is_verified'] !== '1') {
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.createElement('div');
            modal.innerHTML = `
                <div class='modal fade show' style='display:block; background-color: rgba(0,0,0,0.5); z-index:1050;'>
                    <div class='modal-dialog modal-dialog-centered'>
                        <div class='modal-content'>
                            <div class='modal-header'>
                                <h5 class='modal-title'>Verification Required</h5>
                            </div>
                            <div class='modal-body'>
                                <p>You need to verify your email or phone number before accessing the website.</p>
                            </div>
                            <div class='modal-footer'>
                                <a href='select_verification_method.php' class='btn btn-primary'>Verify Here</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='modal-backdrop fade show'></div>
            `;
            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden';
        });
    </script>";
    exit();
}
?>
