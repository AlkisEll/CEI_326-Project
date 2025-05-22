<?php
session_start();
require_once "database.php";
require_once "get_config.php";

if (!isset($_SESSION["admin"])) {
    header("Location: admin_login.php");
    exit();
}

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

$success = "";
$logo_saved = false;

function getConfig($key, $conn) {
    $stmt = $conn->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $stmt->bind_result($value);
    $stmt->fetch();
    $stmt->close();
    return $value;
}

function saveConfig($key, $value, $conn) {
    $stmt = $conn->prepare("INSERT INTO system_config (config_key, config_value)
                            VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
    $stmt->bind_param("ss", $key, $value);
    $stmt->execute();
    $stmt->close();
}

$site_title = getConfig("site_title", $conn);
$moodle_url = getConfig("moodle_url", $conn);
$logo_path = getConfig("logo_path", $conn);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $site_title = trim($_POST["site_title"]);
    $moodle_url = trim($_POST["moodle_url"]);

    saveConfig("site_title", $site_title, $conn);
    saveConfig("moodle_url", $moodle_url, $conn);

    if (isset($_FILES["logo"]) && $_FILES["logo"]["error"] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = "logo_" . time() . "_" . basename($_FILES["logo"]["name"]);
        $uploadPath = $uploadDir . $filename;
        $relativePath = "uploads/" . $filename;

        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $uploadPath)) {
            $oldLogo = getConfig("logo_path", $conn);
            if ($oldLogo && file_exists(__DIR__ . "/" . $oldLogo)) {
                unlink(__DIR__ . "/" . $oldLogo);
            }
            saveConfig("logo_path", $relativePath, $conn);
            $logo_path = $relativePath;
            $logo_saved = true;
        } else {
            $success = "<span style='color:red;'>Upload failed. Check folder permissions.</span>";
        }
    }

    $success = "Configuration saved successfully.";
}
$showBack = true;
$backLink = "admin_dashboard.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Configure System - CUT</title>
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
  <h2 class="mb-4"><i class="bi bi-gear-fill me-2"></i>Configure System</h2>
  <div class="my-apps-card">
    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label"><strong>System Title</strong></label>
        <input type="text" name="site_title" class="form-control" value="<?= htmlspecialchars($site_title); ?>" required>
      </div>

<div class="mb-3">
  <label class="form-label"><strong>Upload Logo</strong></label>
  <input type="file" name="logo" class="form-control" accept="image/*">

  <?php if (!empty($logo_path) && file_exists($logo_path)): ?>
    <div class="mt-2">
      <img src="<?= $logo_path ?>" alt="Current Logo" style="height: 60px;">
    </div>

    <!-- New: remove-logo checkbox -->
    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="remove_logo" id="removeLogoCheckbox" value="1">
      <label class="form-check-label" for="removeLogoCheckbox">
        Remove current logo
      </label>
    </div>
  <?php endif; ?>
</div>


      <div class="mb-3">
        <label class="form-label"><strong>Moodle URL</strong></label>
        <input type="url" name="moodle_url" class="form-control" value="<?= htmlspecialchars($moodle_url); ?>" required>
      </div>

      <button type="submit" class="btn btn-primary w-100">Save Configuration</button>
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
