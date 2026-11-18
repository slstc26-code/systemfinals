<?php

session_start();
include "../database.php";

// USER ACCESS PROTECTION
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
    header("Location: ../index.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

$file_id = (int) ($_GET["id"] ?? 0);

// GET FILE OWNED BY THIS USER
$sql = "SELECT * FROM user_files
        WHERE id = ? AND owner_user_id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $file_id, $user_id);
$stmt->execute();

$file = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$file) {
    die("File not found or access denied.");
}

// FILE LOCATION
$filePath = realpath(
    __DIR__ . "/../user_archive_uploads/" . $file["stored_name"]
);

$uploadPath = realpath(__DIR__ . "/../user_archive_uploads");

if (
    !$filePath ||
    !$uploadPath ||
    dirname($filePath) !== $uploadPath ||
    !is_file($filePath)
) {
    die("File is unavailable.");
}

// DOWNLOAD
header("Content-Type: " . $file["mime_type"]);

header(
    "Content-Disposition: attachment; filename*=UTF-8''" .
    rawurlencode($file["original_name"])
);

header("Content-Length: " . filesize($filePath));

header("X-Content-Type-Options: nosniff");

readfile($filePath);
exit;

?>