<?php
session_start();
require_once "database.php";

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'owner'])) {
    http_response_code(403);
    exit("Unauthorized");
}

echo "<button id='clearAllBtn' class='btn btn-sm btn-danger mb-3'>Clear All Feedback</button>";

$result = $conn->query("SELECT f.id, f.message, f.submitted_at, u.full_name 
                        FROM feedback f 
                        LEFT JOIN users u ON f.user_id = u.id 
                        ORDER BY f.submitted_at DESC");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $userName = $row['full_name'] ?? 'Guest';
        $date = date("M j, Y, g:i a", strtotime($row['submitted_at']));
        $message = nl2br(htmlspecialchars($row['message']));
        $feedbackId = (int) $row['id'];

        echo "<div class='feedback-item border-bottom pb-2 mb-2'>";
        echo "<strong>" . htmlspecialchars($userName) . "</strong><br>";
        echo "<small class='text-muted'>$date</small><br>";
        echo "<p class='mb-1'>$message</p>";
        echo "<button class='btn btn-sm btn-danger delete-feedback-btn' data-id='$feedbackId'>Delete</button>";
        echo "</div>";
    }
} else {
    echo "<p class='text-muted'>No feedback submitted yet.</p>";
}
?>
