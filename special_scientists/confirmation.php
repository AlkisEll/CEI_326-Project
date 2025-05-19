<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Application Submitted - CUT</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="indexstyle.css">
  <style>
    .redirect-note {
      font-size: 0.95rem;
      color: #555;
    }
  </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container py-5">
  <div class="alert alert-success shadow text-center">
    <h4 class="mb-3">🎉 Application Submitted!</h4>
    <p>Your form has been submitted successfully.</p>
    <p class="redirect-note">
      You will be automatically redirected to the <strong>My Applications</strong> section in 
      <strong><span id="countdown">3</span> second<span id="plural">s</span></strong>.
    </p>
    <p class="redirect-note">If you are not redirected soon, please click the button below.</p>
    <a href="my_applications.php" class="btn btn-primary rounded-pill mt-2">View My Applications</a>
  </div>
</div>

<script>
  let seconds = 3;
  const countdownSpan = document.getElementById("countdown");
  const pluralSpan = document.getElementById("plural");

  const interval = setInterval(() => {
    seconds--;
    countdownSpan.textContent = seconds;
    pluralSpan.textContent = seconds === 1 ? "" : "s";

    if (seconds <= 0) {
      clearInterval(interval);
      window.location.href = "my_applications.php";
    }
  }, 1000);
</script>
<script>
  // Prevent form resubmission on back button
  if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
  }
</script>
</body>
</html>
