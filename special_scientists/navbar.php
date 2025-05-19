<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "translate_init.php";
require_once "get_config.php";

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

$fullName = $_SESSION["user"]["full_name"] ?? "Guest";
$role = $_SESSION["user"]["role"] ?? "Null";
?>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="https://www.cut.ac.cy" target="_blank">
      <?php if (!empty($logo_path) && file_exists($logo_path)): ?>
        <img src="<?= $logo_path ?>" alt="Logo" style="height: 40px; margin-right: 10px;">
      <?php endif; ?>
      <?= htmlspecialchars($system_title) ?>
    </a>

    <button class="btn btn-outline-light d-lg-none" id="burgerToggle" style="border: none;">
      <i class="bi bi-list" style="font-size: 1.5rem;"></i>
    </button>

    <div class="d-none d-lg-flex justify-content-end" id="navbarContent">
      <ul class="navbar-nav align-items-center gap-2">
        <?php if ($role === "Null"): ?>
          <li class="nav-item"><a class="nav-link text-white" href="login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link text-white" href="registration.php">Register</a></li>
        <?php else: ?>
            <li class="nav-item"><span class="nav-link text-white">Welcome, <strong><?= htmlspecialchars($fullName); ?></strong></span></li>

            <?php if (!empty($showBack)): ?>
                <li class="nav-item">
                    <a class="nav-link text-white" href="<?= htmlspecialchars($backLink ?? 'index.php') ?>">
                        <i class="bi bi-arrow-left-circle me-1"></i>Back
                    </a>
                </li>
            <?php endif; ?>

          <li class="nav-item"><a class="nav-link text-white" href="my_profile.php">My Profile</a></li>

          <?php if ($role === 'user' || $role === 'scientist'): ?>
            <li class="nav-item"><a class="nav-link text-white" href="my_applications.php">My Applications</a></li>
          <?php endif; ?>

          <?php if (in_array($role, ['hr', 'evaluator'])): ?>
            <li class="nav-item"><a class="nav-link text-white" href="view_applications.php">View Applications</a></li>
          <?php endif; ?>

          <?php if (in_array($role, ['admin', 'owner', 'hr', 'scientist'])): ?>
            <li class="nav-item"><a class="nav-link text-white" href="enrollment_dashboard.php">Enrollment Dashboard</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link text-white" href="index.php">Home</a></li>
          <li class="nav-item">
            <button class="btn btn-outline-light d-flex align-items-center gap-1 px-3 py-1 rounded-pill"
                    data-bs-toggle="modal" data-bs-target="#logoutModal" style="font-size: 0.9rem;">
              <i class="bi bi-box-arrow-left" style="font-size: 1rem;"></i>
              Logout
            </button>
          </li>
        <?php endif; ?>

        <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
  <form method="post" action="switch_lang.php">
    <input type="hidden" name="lang" value="<?= $_SESSION['lang'] === 'en' ? 'el' : 'en' ?>">
    <button type="submit" class="btn btn-outline-light">
      <?= $_SESSION['lang'] === 'en' ? 'Ελληνικά' : 'English' ?>
    </button>
  </form>
</li>
        <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
          <div class="form-check form-switch text-white">
            <input class="form-check-input" type="checkbox" id="darkModeToggle">
            <label class="form-check-label" for="darkModeToggle">Dark Mode</label>
          </div>
        </li>


      </ul>
    </div>
  </div>
</nav>

<div class="floating-menu" id="floatingMenu" style="display: none;">
  <?php if ($role === "Null"): ?>
    <p class="mb-2">Welcome, <strong>Guest</strong></p>
    <a href="index.php">Home</a>
    <a href="login.php">Login</a>
    <a href="registration.php">Register</a>
  <?php else: ?>
    <p class="mb-2">Welcome, <strong><?= htmlspecialchars($fullName); ?></strong></p>
    <a href="index.php">Home</a>
    <?php if (!empty($showBack)): ?>
      <a href="<?= htmlspecialchars($backLink ?? 'index.php') ?>"><i class="bi bi-arrow-left-circle me-1"></i>Back</a>
    <?php endif; ?>
    <a href="my_profile.php">My Profile</a>
    <?php if ($role === 'user' || $role === 'scientist'): ?>
      <a href="my_applications.php">My Applications</a>
    <?php endif; ?>
    <?php if (in_array($role, ['hr', 'evaluator'])): ?>
      <a href="view_applications.php">View Applications</a>
    <?php endif; ?>
    <?php if (in_array($role, ['admin', 'owner', 'hr', 'scientist'])): ?>
      <a href="enrollment_dashboard.php">Enrollment Dashboard</a>
    <?php endif; ?>

   <form method="post" action="switch_lang.php" class="mb-2">
  <?php if ($_SESSION['lang'] === 'en'): ?>
    <button type="submit" name="lang" value="el" class="btn w-100 text-white" style="background-color: #4da3ff;">Ελληνικά</button>
  <?php else: ?>
    <button type="submit" name="lang" value="en" class="btn w-100 text-white" style="background-color: #4da3ff;">English</button>
  <?php endif; ?>
</form>

    <button data-bs-toggle="modal" data-bs-target="#logoutModal">
      <i class="bi bi-box-arrow-left me-1"></i> Logout
    </button>
  <?php endif; ?>
  
  <div class="form-check form-switch mt-2">
    <input class="form-check-input" type="checkbox" id="darkModeToggleMobile">
    <label class="form-check-label" for="darkModeToggleMobile">Dark Mode</label>
  </div>
</div>

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


<script>
// burger menu toggle
const toggleBtn = document.getElementById('burgerToggle');
const floatingMenu = document.getElementById('floatingMenu');
let isMenuOpen = false;

toggleBtn?.addEventListener('click', () => {
  if (isMenuOpen) {
    floatingMenu.classList.remove('fade-in');
    floatingMenu.classList.add('fade-out');
    setTimeout(() => {
      floatingMenu.style.display = 'none';
      isMenuOpen = false;
    }, 200);
  } else {
    floatingMenu.style.display = 'block';
    floatingMenu.classList.remove('fade-out');
    void floatingMenu.offsetWidth;
    floatingMenu.classList.add('fade-in');
    isMenuOpen = true;
  }
});

document.addEventListener('click', (e) => {
  if (
    isMenuOpen &&
    !floatingMenu.contains(e.target) &&
    !toggleBtn.contains(e.target)
  ) {
    floatingMenu.classList.remove('fade-in');
    floatingMenu.classList.add('fade-out');
    setTimeout(() => {
      floatingMenu.style.display = 'none';
      isMenuOpen = false;
    }, 200);
  }
});

// dark mode sync
const darkToggle = document.getElementById('darkModeToggle');
const darkMobile = document.getElementById('darkModeToggleMobile');
const body = document.body;

if (localStorage.getItem('dark-mode') === 'true') {
  body.classList.add('dark-mode');
  darkToggle.checked = true;
  if (darkMobile) darkMobile.checked = true;
}

darkToggle?.addEventListener('change', () => {
  body.classList.toggle('dark-mode');
  localStorage.setItem('dark-mode', body.classList.contains('dark-mode'));
  if (darkMobile) darkMobile.checked = darkToggle.checked;
});

darkMobile?.addEventListener('change', () => {
  darkToggle.checked = darkMobile.checked;
  darkToggle.dispatchEvent(new Event('change'));
});
</script>

