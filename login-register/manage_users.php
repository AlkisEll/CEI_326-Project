<?php
session_start();
require_once "database.php";

require_once "get_config.php";
$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

// Only admins or higher
if (!isset($_SESSION["user"]) || ($_SESSION["user"]["role"] !== 'admin' && $_SESSION["user"]["role"] !== 'owner')) {
    header("Location: index.php");
    exit();
}

$sql = "SELECT id, full_name, email, role, is_verified FROM users";
$result = mysqli_query($conn, $sql);
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
</head>
<body>

<!-- Navbar -->
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
                <li class="nav-item">
                    <button class="nav-link btn btn-link text-white" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Logout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to log out?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
        <form action="logout.php" method="post">
            <button type="submit" class="btn btn-danger">Yes, Logout</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h2>Manage Users</h2></div>
        <div class="d-flex gap-2">
            <a href="add_user.php" class="btn btn-success">➕ Add New User</a>
        </div>
    </div>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Verified</th>
                <th style="width: 280px;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($user = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $user["id"] ?></td>
                    <td><?= htmlspecialchars($user["full_name"]) ?></td>
                    <td><?= htmlspecialchars($user["email"]) ?></td>
                    <td>
                        <?php if ($user["role"] === 'owner'): ?>
                            <span class="badge bg-danger">Owner</span>
                        <?php else: ?>
                            <?= htmlspecialchars($user["role"]) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= $user["is_verified"] ? "✔ Yes" : "✖ No" ?></td>
                    <td>
                        <?php if ($user["role"] === 'owner'): ?>
                            <em>Protected</em>
                        <?php elseif ($user["id"] != $_SESSION["user"]["id"]): ?>
                            <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                            <?php if ($user["role"] === 'admin'): ?>
                                <a href="update_role.php?id=<?= $user['id'] ?>&role=user" class="btn btn-sm btn-warning">Demote</a>
                            <?php else: ?>
                                <a href="update_role.php?id=<?= $user['id'] ?>&role=admin" class="btn btn-sm btn-success">Promote</a>
                            <?php endif; ?>
                            <a href="delete_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                        <?php else: ?>
                            <em>Current Admin</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No users found.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const saved = localStorage.getItem('dark-mode');
    if (saved === 'true') document.body.classList.add('dark-mode');
</script>
</body>
</html>
