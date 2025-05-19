<?php
session_start();

require_once "get_config.php";
require_once "database.php";

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

if (!isset($_SESSION["user"]) || !in_array($_SESSION["user"]["role"], ['admin', 'owner'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: manage_users.php");
    exit();
}

$id = (int)$_GET["id"];

if (isset($_POST["save"])) {
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $role = $_POST["role"];

    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, role=? WHERE id=?");
    $stmt->bind_param("sssi", $full_name, $email, $role, $id);
    $stmt->execute();

    header("Location: manage_users.php");
    exit();
}

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) {
    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit User - CUT</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="indexstyle.css">
  <link rel="stylesheet" href="darkmode.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>

<!-- Navbar -->
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
        <a class="nav-link text-white" href="manage_users.php"><i class="bi bi-arrow-left-circle me-1"></i>Back</a>
        <li class="nav-item"><a class="nav-link text-white" href="index.php">Home</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Main Content -->
<div class="container py-5">
  <h2 class="mb-4"><i class="bi bi-pencil-square me-2"></i>Edit User</h2>
  <div class="my-apps-card">
    <form method="post">
      <div class="mb-3">
        <label class="form-label"><strong>Full Name:</strong></label>
        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user["full_name"]) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label"><strong>Email:</strong></label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user["email"]) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label"><strong>Role:</strong></label>
        <select name="role" class="form-select" required>
          <option value="user" <?= $user["role"] === "user" ? "selected" : "" ?>>Candidate (User)</option>
          <option value="hr" <?= $user["role"] === "hr" ? "selected" : "" ?>>HR</option>
          <option value="evaluator" <?= $user["role"] === "evaluator" ? "selected" : "" ?>>Evaluator</option>
          <option value="scientist" <?= $user["role"] === "scientist" ? "selected" : "" ?>>Scientist</option>
          <option value="admin" <?= $user["role"] === "admin" ? "selected" : "" ?>>Admin</option>
        </select>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" name="save" class="btn btn-success w-100">Save Changes</button>
        <a href="manage_users.php" class="btn btn-secondary w-100">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const saved = localStorage.getItem('dark-mode');
  if (saved === 'true') document.body.classList.add('dark-mode');
</script>
</body>
</html>
