<?php

session_start();
include "../config/database.php";

// USER ACCESS PROTECTION
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
    header("Location: ../index.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

// VERIFY REQUEST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: first.php");
    exit;
}

if (
    !isset($_POST["archive_token"]) ||
    !hash_equals(
        $_SESSION["archive_token"] ?? "",
        $_POST["archive_token"]
    )
) {
    die("Invalid request.");
}

$file_id = (int) ($_POST["file_id"] ?? 0);

// FIND ONLY THE USER'S OWN FILE
$sql = "SELECT stored_name FROM user_files
        WHERE id = ? AND owner_user_id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $file_id, $user_id);
$stmt->execute();

$file = $stmt->get_result()->fetch_assoc();

$stmt->close();

if ($file) {

    $uploadDirectory = realpath(
        __DIR__ . "/../user_archive_uploads"
    );

    $filePath = realpath(
        __DIR__ . "/../user_archive_uploads/" . $file["stored_name"]
    );

    if (
        $uploadDirectory &&
        $filePath &&
        dirname($filePath) === $uploadDirectory &&
        is_file($filePath)
    ) {

        if (!unlink($filePath)) {
            die("Unable to delete the file.");
        }

    }

    // DELETE DATABASE RECORD
    $sql = "DELETE FROM user_files
            WHERE id = ? AND owner_user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: first.php?archive=deleted");
exit;

?>