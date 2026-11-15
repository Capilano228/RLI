<?php
require_once 'config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    exit;
}
$vote = $_POST['vote'] ?? '';
if (in_array($vote, ['yes', 'no'])) {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("INSERT INTO canteen_votes (student_id, vote, vote_date) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE vote = VALUES(vote)");
    $stmt->execute([$_SESSION['user_id'], $vote, $today]);
}
?>