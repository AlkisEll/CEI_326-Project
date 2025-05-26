<?php
session_start();
require_once "database.php";
require_once "get_config.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$user      = $_SESSION["user"];
$fullName  = $user["full_name"];
$email     = $user["email"];
$id        = $user["id"];
$role      = $user["role"] ?? '';

// … your existing SELECT/DB‐fetch logic …
$stmt = $conn->prepare("SELECT username, full_name, email, …, phone, dob, country, city, address FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result     = $stmt->get_result();
$userData   = $result->fetch_assoc();
$username   = $userData["username"];

// … any other logic …
$result = mysqli_stmt_get_result($stmt);
$info   = mysqli_fetch_assoc($result);

$phone   = $info["phone"];
$dob     = $info["dob"];
$country = $info["country"];
$city    = $info["city"];
$address = $info["address"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_profile"])) {
    $newUsername = trim($_POST["username"]);
    $newFullName = trim($_POST["full_name"]);
    $newCity     = trim($_POST["city"]);
    $newCountry  = trim($_POST["country"]);
    $newDob      = trim($_POST["dob"]);
    $newAddress  = trim($_POST["address"]);

    // … your existing validation & UPDATE logic for save_profile …

    $_SESSION["profile_success"] = "Profile updated.";
    header("Location: my_profile.php");
    exit();
}

?>
<?php include "navbar.php"; ?>

<div class="container py-5">
  <h2 class="mb-4">My Profile</h2>
  <p class="text-muted text-end">
    Last login: <?= $lastLogin ? date("F j, Y, g:i a", strtotime($lastLogin)) : "— Never" ?>
  </p>
  <?php
    $updatedAt = $conn
      ->query("SELECT updated_at FROM users WHERE id = $id")
      ->fetch_assoc()['updated_at'];
    echo "<p class='text-muted text-end'>Last updated on: "
         . date("F j, Y", strtotime($updatedAt))
         . "</p>";
  ?>

  <?php if (isset($_SESSION["profile_success"])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <?= $_SESSION["profile_success"]; unset($_SESSION["profile_success"]); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="card p-4 shadow">
    <form action="my_profile.php" method="post">
      <h5 class="mb-3 text-primary border-bottom pb-2">Account Info</h5>
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Full Name</label>
          <input
            type="text"
            name="full_name"
            class="form-control"
            value="<?= htmlspecialchars($fullName); ?>"
            oninput="this.value = this.value.replace(/[^\p{L}\s]/gu, '')"
            required
          >
        </div>
        <div class="col-md-6">
          <label class="form-label">Username</label>
          <input
            type="text"
            name="username"
            class="form-control"
            minlength="3"
            maxlength="20"
            value="<?= htmlspecialchars($username); ?>"
            required
          >
          <div class="mt-2">
            <span class="badge bg-primary">Role: <?= ucfirst($role) ?></span>
          </div>
        </div>
      </div>

      <h5 class="mt-4 mb-3 text-primary border-bottom pb-2">Contact Info</h5>
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Email Address</label>
          <!-- now belongs to its own form via form="email-form" -->
          <input
            type="email"
            name="new_email"
            form="email-form"
            class="form-control"
            value="<?= htmlspecialchars($email); ?>"
            required
          >
        </div>
        <div class="col-md-6">
          <label class="form-label">Phone Number</label>
          <input
            type="tel"
            name="new_phone"
            form="phone-form"
            class="form-control"
            value="<?= htmlspecialchars($phone); ?>"
            required
          >
          <small class="text-muted">
            To change your phone number, submit and check your email for verification.
          </small>
        </div>
      </div>

      <h5 class="mt-4 mb-3 text-primary border-bottom pb-2">Personal Details</h5>
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Date of Birth</label>
          <input
            type="date"
            name="dob"
            class="form-control"
            value="<?= htmlspecialchars($dob); ?>"
            required
          >
        </div>
        <div class="col-md-6">
          <label class="form-label">Country</label>
          <input
            type="text"
            name="country"
            id="country"
            class="form-control country_input"
            value="<?= htmlspecialchars($country); ?>"
            required
          >
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">City</label>
          <input
            type="text"
            name="city"
            class="form-control"
            value="<?= htmlspecialchars($city); ?>"
            required
          >
        </div>
        <div class="col-md-6">
          <label class="form-label">Address</label>
          <input
            type="text"
            name="address"
            class="form-control"
            value="<?= htmlspecialchars($address); ?>"
            required
          >
        </div>
      </div>

      <div class="d-flex flex-wrap justify-content-between gap-2 mt-4">
        <div>
          <a href="change_password.php" class="btn btn-outline-primary">Change Password</a>
          <?php if (in_array($role, ['admin', 'owner'])): ?>
            <a href="admin_dashboard.php" class="btn btn-warning">Admin Mode</a>
          <?php endif; ?>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <button type="submit" name="save_profile" class="btn btn-primary">
            Save Changes
          </button>
          <button type="submit" form="phone-form" class="btn btn-success">
            Request Phone Change
          </button>
          <button type="submit" form="email-form" class="btn btn-warning">
            Request Email Address Change
          </button>
        </div>
      </div>
    </form>

    <!-- Empty forms for the phone/email change handlers -->
    <form action="request_phone_change.php" method="post" id="phone-form"></form>
    <form action="request_email_change.php" method="post" id="email-form"></form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const savedMode = localStorage.getItem('dark-mode');
  if (savedMode === 'true') document.body.classList.add('dark-mode');

  setTimeout(() => {
    const alertBox = document.querySelector('.alert');
    if (alertBox) alertBox.classList.remove('show');
  }, 4000);
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.1.0/js/countrySelect.min.js"></script>
<script>
  $("#country").countrySelect({ defaultCountry: "cy" });
</script>
</body>
</html>
