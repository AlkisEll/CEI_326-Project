<?php
session_start();
require_once "database.php";

require_once "get_config.php";
$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");


if (!isset($_SESSION["admin"])) {
    header("Location: admin_login.php");
    exit();
}

$success = "";
$logo_saved = false;

// Fetch current configuration from DB
function getConfig($key, $conn) {
    $stmt = $conn->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $stmt->bind_result($value);
    $stmt->fetch();
    $stmt->close();
    return $value;
}

// Update or insert config
function saveConfig($key, $value, $conn) {
    $stmt = $conn->prepare("INSERT INTO system_config (config_key, config_value)
                            VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
    $stmt->bind_param("ss", $key, $value);
    $stmt->execute();
    $stmt->close();
}

// Load current config values
$site_title = getConfig("site_title", $conn);
$moodle_url = getConfig("moodle_url", $conn);
$logo_path = getConfig("logo_path", $conn);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $site_title = trim($_POST["site_title"]);
    $moodle_url = trim($_POST["moodle_url"]);

    // Save title & Moodle URL
    saveConfig("site_title", $site_title, $conn);
    saveConfig("moodle_url", $moodle_url, $conn);

 // Handle logo upload
if (isset($_FILES["logo"]) && $_FILES["logo"]["error"] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . "/uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = "logo_" . time() . "_" . basename($_FILES["logo"]["name"]);
    $uploadPath = $uploadDir . $filename;
    $relativePath = "uploads/" . $filename; // For saving in DB and displaying

    if (move_uploaded_file($_FILES["logo"]["tmp_name"], $uploadPath)) {
        // Optional: Delete the previous logo file if one exists
        $oldLogo = getConfig("logo_path", $conn);
        if ($oldLogo && file_exists(__DIR__ . "/" . $oldLogo)) {
            unlink(__DIR__ . "/" . $oldLogo);
        }

        // Save new path in DB
        saveConfig("logo_path", $relativePath, $conn);
        $logo_path = $relativePath;
        $logo_saved = true;
    } else {
        $success = "<span style='color:red;'>Upload failed. Check folder permissions.</span>";
    }
}



    $success = "Configuration saved successfully.";
}
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
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="https://www.cut.ac.cy" target="_blank" title="Go to Cyprus University of Technology">
            <?php if (!empty($logo_path) && file_exists($logo_path)): ?>
            <img src="<?= $logo_path ?>" alt="Logo" style="height: 40px; margin-right: 10px;">
            <?php endif; ?>
            <span><?= htmlspecialchars($system_title) ?></span>
        </a>

        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-center">
                <a href="javascript:history.back()" class="btn btn-secondary">← Back</a>
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h2 class="mb-4">Configure System</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">System Title</label>
            <input type="text" name="site_title" class="form-control" value="<?= htmlspecialchars($site_title); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Upload Logo</label>
            <input type="file" name="logo" class="form-control" accept="image/*">
            <?php if (!empty($logo_path) && file_exists($logo_path)): ?>
                <div class="mt-2">
                    <img src="<?= $logo_path ?>" alt="Current Logo" style="height: 60px;">
                </div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label">Moodle URL</label>
            <input type="url" name="moodle_url" class="form-control" value="<?= htmlspecialchars($moodle_url); ?>" required>
        </div>

        <button type="submit" class="btn btn-primary">Save Configuration</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const saved = localStorage.getItem('dark-mode');
    if (saved === 'true') document.body.classList.add('dark-mode');
</script>
</body>
</html>
