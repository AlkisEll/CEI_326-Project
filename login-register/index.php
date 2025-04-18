<?php
session_start();
require_once "database.php";
require_once "get_config.php";

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");
$moodle_url = getSystemConfig("moodle_url");

// Default guest role
$role = "Null";
$fullName = "Guest";

// If logged in, verify profile completeness
if (isset($_SESSION["user"])) {
    $userId = $_SESSION["user"]["id"];
    $fullName = $_SESSION["user"]["full_name"] ?? 'User';
    $role = $_SESSION["user"]["role"] ?? 'user';

    $stmt = $conn->prepare("SELECT country, city, address, postcode, dob, phone FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $fieldsComplete = $user && $user["country"] && $user["city"] && $user["address"] && $user["postcode"] && $user["dob"] && $user["phone"];

    // Update profile_complete flag
    $_SESSION["user"]["profile_complete"] = $fieldsComplete;

    // Redirect if incomplete
    if (!$fieldsComplete) {
        header("Location: complete_profile.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($system_title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="indexstyle.css">
  <link rel="stylesheet" href="darkmode.css">
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
        <li class="nav-item"><a class="nav-link text-white" href="<?= htmlspecialchars($moodle_url) ?>" target="_blank">Moodle</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="#">About</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="#">Departments</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="#">Contact</a></li>

        <?php if ($role === "Null"): ?>
            <li class="nav-item"><a class="nav-link text-white" href="login.php">Login</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="registration.php">Register</a></li>
        <?php else: ?>
            <li class="nav-item"><a class="nav-link text-white" href="my_profile.php">My Profile</a></li>
            <li class="nav-item">
                <a class="nav-link text-white">Welcome, <strong><?= htmlspecialchars($fullName); ?></strong></a>
            </li>
        <?php endif; ?>

        <li class="nav-item ms-3">
            <div class="form-check form-switch text-white">
              <input class="form-check-input" type="checkbox" id="darkModeToggle">
              <label class="form-check-label" for="darkModeToggle">Dark Mode</label>
            </div>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<div class="hero-section">
  <div class="container">
    <h1>Welcome to the Cyprus University of Technology</h1>
    <p>Excellence in Education and Research</p>
  </div>
</div>

<!-- Main Content -->
<div class="container content-section py-5">
  <div class="row">
    <div class="col-md-4">
      <h3>Undergraduate Programs</h3>
      <p>Explore our diverse undergraduate programs designed to equip students with the skills needed for the modern world.</p>
    </div>
    <div class="col-md-4">
      <h3>Postgraduate Studies</h3>
      <p>Advance your knowledge with our specialized master's and doctoral programs across various disciplines.</p>
    </div>
    <div class="col-md-4">
      <h3>Research Opportunities</h3>
      <p>Engage in cutting-edge research projects that contribute to technological advancements and societal development.</p>
    </div>
  </div>
</div>

<!-- Footer -->
<div class="footer">
  <div class="container">
    <p>&copy; 2025 Cyprus University of Technology. All rights reserved.</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const toggle = document.getElementById('darkModeToggle');
  const body = document.body;

  const savedMode = localStorage.getItem('dark-mode');
  if (savedMode === 'true') {
    body.classList.add('dark-mode');
    toggle.checked = true;
  }

  toggle.addEventListener('change', () => {
    body.classList.toggle('dark-mode');
    localStorage.setItem('dark-mode', body.classList.contains('dark-mode'));
  });

  // Prevent browser back from restoring cached version
  window.addEventListener("pageshow", function (event) {
    if (event.persisted || (window.performance && window.performance.getEntriesByType("navigation")[0].type === "back_forward")) {
      window.location.reload();
    }
  });
</script>
</body>
</html>
