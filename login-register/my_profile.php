<?php
session_start();
require_once "database.php";
require_once "get_config.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION["user"];
$fullName = $user["full_name"];
$email = $user["email"];
$id = $user["id"];
$role = $user["role"] ?? '';

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

$sql = "SELECT phone, dob, country, city, address FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$info = mysqli_fetch_assoc($result);

$phone = $info["phone"];
$dob = $info["dob"];
$country = $info["country"];
$city = $info["city"];
$address = $info["address"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_profile"])) {
    $newFullName = trim($_POST["full_name"]);
    $newAddress = trim($_POST["address"]);

    $updateSql = "UPDATE users SET full_name = ?, address = ? WHERE id = ?";
    $updateStmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($updateStmt, "ssi", $newFullName, $newAddress, $id);
    mysqli_stmt_execute($updateStmt);

    $_SESSION["user"]["full_name"] = $newFullName;
    header("Location: my_profile.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - CUT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="indexstyle.css">
    <link rel="stylesheet" href="darkmode.css">

</head>

<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="https://www.cut.ac.cy" target="_blank">
            <?php if (!empty($logo_path) && file_exists($logo_path)): ?>
                <img src="<?= $logo_path ?>" alt="Logo" style="height: 40px; margin-right: 10px;">
            <?php endif; ?>
            <?= htmlspecialchars($system_title) ?>
        </a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link text-white" href="index.php">Home</a></li>
                <li class="nav-item"><span class="nav-link text-white">Welcome, <strong><?= htmlspecialchars($fullName) ?></strong></span></li>
                <li class="nav-item">
                    <button class="nav-link btn btn-link text-white" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Logout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">Are you sure you want to log out?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
        <form action="logout.php" method="post">
            <button type="submit" class="btn btn-danger">Yes, Logout</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="container py-5">
    <h2 class="mb-4">My Profile</h2>
    <div class="card p-4">
        <form action="my_profile.php" method="post">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($fullName); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($email); ?>" disabled>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" name="new_phone" value="<?= htmlspecialchars($phone); ?>" required form="phone-form">
                    <small class="text-muted">To change your phone number, submit and check your email for verification.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date of Birth</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($dob); ?>" disabled>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Country</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($country); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">City</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($city); ?>" disabled>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($address); ?>" required>
            </div>

            <div class="d-flex justify-content-between flex-wrap gap-2">
                <a href="change_password.php" class="btn btn-outline-primary">Change Password</a>
                <?php if (in_array($role, ['admin', 'owner'])): ?>
                    <a href="admin_dashboard.php" class="btn btn-warning">Admin Mode</a>
                <?php endif; ?>
                <button type="submit" name="save_profile" class="btn btn-primary">Save Changes</button>
            </div>
        </form>

        <form action="request_phone_change.php" method="post" id="phone-form" class="mt-3 text-end">
            <button type="submit" class="btn btn-success">Request Phone Change</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const savedMode = localStorage.getItem('dark-mode');
    if (savedMode === 'true') {
        document.body.classList.add('dark-mode');
    }
</script>
</body>
</html>
