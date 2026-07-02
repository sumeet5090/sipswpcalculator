<?php

declare(strict_types=1);

/**
 * Handle blog comment submissions.
 * Stores comments in the SQLite database.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'] ?? '';
    $author_name = trim($_POST['author_name'] ?? '');
    $comment_body = trim($_POST['comment_body'] ?? '');

    if (empty($post_id) || empty($author_name) || empty($comment_body)) {
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/resources.php') . '?error=missing_fields#comments');
        exit;
    }

    $dbPath = __DIR__ . '/../database/database.sqlite';
    try {
        $pdo = new PDO("sqlite:" . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("INSERT INTO comments (post_id, author_name, comment_body) VALUES (:post_id, :author_name, :comment_body)");
        $stmt->execute([
            ':post_id' => $post_id,
            ':author_name' => htmlspecialchars($author_name),
            ':comment_body' => htmlspecialchars($comment_body),
        ]);

        header('Location: ' . $_SERVER['HTTP_REFERER'] . '#comments');
        exit;
    } catch (\Throwable $e) {
        error_log("Comment submission error: " . $e->getMessage());
        header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=db_error#comments');
        exit;
    }
} else {
    header('Location: /resources.php');
    exit;
}
