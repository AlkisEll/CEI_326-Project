<?php
session_start();
require_once "database.php";
require_once "get_config.php";

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

if (!isset($_SESSION["user"]) || ($_SESSION["user"]["role"] !== 'admin' && $_SESSION["user"]["role"] !== 'owner')) {
    header("Location: index.php");
    exit();
}

$sql = "SELECT id, full_name, email, role, is_verified, last_login FROM users";
$result = mysqli_query($conn, $sql);
$users = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}
$showBack = true;
$backLink = "admin_dashboard.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users - Admin</title>
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
  <h2 class="mb-4"><i class="bi bi-people-fill me-2"></i>Manage Users</h2>

  <div class="my-apps-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <div class="d-flex flex-wrap gap-3">
        <div class="d-flex flex-column" style="min-width: 180px;">
          <label for="nameFilter" class="form-label mb-1">Filter by Name:</label>
          <input type="text" id="nameFilter" class="form-control" placeholder="Type full name...">
        </div>
        <div class="d-flex flex-column" style="min-width: 160px;">
          <label for="roleFilter" class="form-label mb-1">Filter by Role:</label>
          <select id="roleFilter" class="form-select">
            <option value="all">All</option>
            <option value="user">User</option>
            <option value="admin">Admin</option>
            <option value="owner">Owner</option>
          </select>
        </div>
        <div class="d-flex flex-column" style="min-width: 160px;">
          <label for="verifyFilter" class="form-label mb-1">Filter by Verification:</label>
          <select id="verifyFilter" class="form-select">
            <option value="all">All</option>
            <option value="1">✔ Yes</option>
            <option value="0">✖ No</option>
          </select>
        </div>
      </div>

      <a href="add_user.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Add New User</a>
    </div>

    <?php if (!empty($users)): ?>
      <div class="table-responsive">
        <table class="table table-bordered table-hover text-center align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Verified</th>
              <th>Last Login</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
              <tr 
                class="role-<?= $user['role'] ?> verify-<?= $user['is_verified'] ?>" 
                data-name="<?= strtolower($user['full_name']) ?>"
              >
                <td><?= $user["id"] ?></td>
                <td><?= htmlspecialchars($user["full_name"]) ?></td>
                <td><?= htmlspecialchars($user["email"]) ?></td>
                <td>
                  <?= $user["role"] === 'owner' ? '<span class="badge bg-danger">Owner</span>' : htmlspecialchars($user["role"]) ?>
                </td>
                <td><?= $user["is_verified"] ? "✔ Yes" : "✖ No" ?></td>
                <td>
                  <?= $user["last_login"] ? date("M j, Y, g:i a", strtotime($user["last_login"])) : "<span class='text-muted'>Never</span>" ?>
                </td>
                <td>
                  <?php
  $roleHierarchy = ['user', 'scientist', 'evaluator', 'hr', 'admin'];
  $currentRole = $user["role"];
  $currentIndex = array_search($currentRole, $roleHierarchy);

  $promoteTo = $currentIndex !== false && $currentIndex < count($roleHierarchy) - 1
      ? $roleHierarchy[$currentIndex + 1]
      : null;

  $demoteTo = $currentIndex !== false && $currentIndex > 0
      ? $roleHierarchy[$currentIndex - 1]
      : null;

  function formatRoleName($roleCode) {
      return match ($roleCode) {
          'user' => 'Candidate (User)',
          'scientist' => 'Scientist',
          'evaluator' => 'Evaluator',
          'hr' => 'HR',
          'admin' => 'Admin',
          default => ucfirst($roleCode)
      };
}
?>
<?php if ($user["role"] === 'owner'): ?>
  <em>Protected</em>
<?php elseif ($user["id"] != $_SESSION["user"]["id"]): ?>
  <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-info">Edit</a>

  <?php if ($promoteTo): ?>
    <a href="update_role.php?id=<?= $user['id'] ?>&role=<?= $promoteTo ?>" class="btn btn-sm btn-success">
      Promote to <?= formatRoleName($promoteTo) ?>
    </a>
  <?php endif; ?>

  <?php if ($demoteTo): ?>
    <a href="update_role.php?id=<?= $user['id'] ?>&role=<?= $demoteTo ?>" class="btn btn-sm btn-warning">
      Demote to <?= formatRoleName($demoteTo) ?>
    </a>
  <?php endif; ?>

  <a href="delete_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
<?php else: ?>
  <em>Current Admin</em>
<?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="text-center text-muted">No users found.</div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  if (localStorage.getItem('dark-mode') === 'true') document.body.classList.add('dark-mode');

  const nameFilter = document.getElementById("nameFilter");
  const roleFilter = document.getElementById("roleFilter");
  const verifyFilter = document.getElementById("verifyFilter");

  function filterUsers() {
    const nameValue = nameFilter.value.toLowerCase();
    const roleValue = roleFilter.value;
    const verifyValue = verifyFilter.value;

    document.querySelectorAll("tbody tr").forEach(row => {
      const nameMatch = row.dataset.name.includes(nameValue);
      const roleMatch = roleValue === 'all' || row.classList.contains("role-" + roleValue);
      const verifyMatch = verifyValue === 'all' || row.classList.contains("verify-" + verifyValue);

      row.style.display = nameMatch && roleMatch && verifyMatch ? "" : "none";
    });
  }

  nameFilter.addEventListener("input", filterUsers);
  roleFilter.addEventListener("change", filterUsers);
  verifyFilter.addEventListener("change", filterUsers);
</script>
</body>
</html>
