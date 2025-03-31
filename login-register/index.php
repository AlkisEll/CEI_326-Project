<?php
session_start();
if (!isset($_SESSION["user"])) {
   header("Location: login.php");
   exit();
}
$name = $_SESSION["user"]["full_name"] ?? 'User';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-btn {
            display: block;
            width: 100%;
            padding: 12px 20px;
            margin-bottom: 15px;
            border: 2px solid #006778;
            border-radius: 6px;
            background-color: white;
            color: #006778;
            font-weight: bold;
            font-size: 16px;
            text-align: left;
            transition: all 0.2s ease-in-out;
        }

        .dashboard-btn:hover {
            background-color: #006778;
            color: white;
        }

        .logout-btn {
            background-color: #dc3545;
            border: none;
            color: white;
            font-weight: bold;
        }

        .logout-btn:hover {
            background-color: #b02a37;
        }

        .dashboard-card {
            width: 100%;
            max-width: 400px;
            padding: 40px 30px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .dashboard-card h1 {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .dashboard-card p {
            margin-bottom: 30px;
            color: #333;
        }
    </style>
</head>
<body>
    

    <div class="dashboard-card">
        <a href="https://www.cut.ac.cy" class="logo-link" target="_blank" title="Go to CUT website"></a>
        <h1>Welcome, <?php echo htmlspecialchars($name); ?>!</h1>
        <p>Select a section to continue:</p>

        <a href="my_profile.php" class="dashboard-btn">👤 My Profile</a>
        <a href="my_applications.php" class="dashboard-btn">📄 My Applications</a>
        <a href="application_status.php" class="dashboard-btn">📌 Application Status</a>

        <form action="logout.php" method="post" class="mt-3">
            <button type="submit" class="btn logout-btn w-100">🚪 Logout</button>
        </form>
    </div>
</body>
</html>
