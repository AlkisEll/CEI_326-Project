<?php
session_start();
require_once "database.php";
require_once "get_config.php";

if (!isset($_SESSION["user"]) || !in_array($_SESSION["user"]["role"], ['admin', 'owner'])) {
    header("Location: index.php");
    exit();
}

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

$error = "";
$success = "";

if (isset($_POST["create"])) {
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $role = $_POST["role"];

    // Check if email already exists
    $checkQuery = "SELECT id FROM users WHERE email = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $error = "A user with this email already exists.";
    } else {
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, is_verified) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param("ssss", $full_name, $email, $password, $role);
        if ($stmt->execute()) {
            $success = "User created successfully!";
            header("Location: manage_users.php");
            exit();
        } else {
            $error = "Failed to create user. Please try again.";
        }
    }

    $checkStmt->close();
}
$showBack = true;
$backLink = "manage_users.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add New User - CUT</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="indexstyle.css">
  <link rel="stylesheet" href="darkmode.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>

<!-- Navbar -->
<?php include "navbar.php"; ?>

<!-- Main Content -->
<div class="container py-5">
  <h2 class="mb-4"><i class="bi bi-person-plus-fill me-2"></i>Add New User</h2>
  <div class="my-apps-card">

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label class="form-label"><strong>Full Name</strong></label>
        <input type="text" name="full_name" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label"><strong>Email Address</strong></label>
        <input type="email" name="email" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label"><strong>Password</strong></label>
        <input type="password" name="password" class="form-control" required minlength="8">
      </div>

      <div class="mb-3">
        <label class="form-label"><strong>Role</strong></label>
        <select name="role" class="form-select" required>
          <option value="user">Candidate (User)</option>
          <option value="hr">HR</option>
          <option value="evaluator">Evaluator</option>
          <option value="scientist">Scientist</option>
          <option value="admin">Admin</option>
        </select>
      </div>

      <button type="submit" name="create" class="btn btn-primary w-100">Create User</button>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  if (localStorage.getItem('dark-mode') === 'true') {
    document.body.classList.add('dark-mode');
  }
</script>
</body>
</html>
