<?php
session_start();
include "../config/database.php";

// USER ACCESS PROTECTION
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
    header("Location: ../index.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

// CSRF TOKEN
if (empty($_SESSION["archive_token"])) {
    $_SESSION["archive_token"] = bin2hex(random_bytes(32));
}

// DIRECTORIES
$uploadDirectory = __DIR__ . "/../user_archive_uploads/";
$profileDirectory = __DIR__ . "/../profile_uploads/";

if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0755, true);
}
if (!is_dir($profileDirectory)) {
    mkdir($profileDirectory, 0755, true);
}

// HANDLE NEW PROFILE PICTURE UPLOAD
if (isset($_POST["upload_new_avatar"])) {
    if (!isset($_POST["archive_token"]) || !hash_equals($_SESSION["archive_token"], $_POST["archive_token"])) {
        die("Invalid request.");
    }

    if (isset($_FILES["new_avatar_file"]) && $_FILES["new_avatar_file"]["error"] === UPLOAD_ERR_OK) {
        $file = $_FILES["new_avatar_file"];
        $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $allowedExt = ["jpg", "jpeg", "png", "webp"];
        $maxSize = 3 * 1024 * 1024; // 3MB

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file["tmp_name"]);
        $allowedMime = ["image/jpeg", "image/png", "image/webp"];

        if (in_array($extension, $allowedExt, true) && in_array($mimeType, $allowedMime, true) && $file["size"] <= $maxSize) {
            $newAvatarName = "avatar_" . $user_id . "_" . bin2hex(random_bytes(8)) . "." . $extension;
            $destination = $profileDirectory . $newAvatarName;

            if (move_uploaded_file($file["tmp_name"], $destination)) {
                $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                $stmt->bind_param("si", $newAvatarName, $user_id);
                $stmt->execute();
                $stmt->close();

                header("Location: first.php?avatar=updated");
                exit;
            }
        }
    }
}

// HANDLE SELECTING EXISTING PICTURE FROM MY DRIVE
if (isset($_POST["select_drive_avatar"])) {
    if (!isset($_POST["archive_token"]) || !hash_equals($_SESSION["archive_token"], $_POST["archive_token"])) {
        die("Invalid request.");
    }

    $selectedFileId = (int) ($_POST["drive_file_id"] ?? 0);

    $stmt = $conn->prepare("SELECT stored_name FROM user_files WHERE id = ? AND owner_user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $selectedFileId, $user_id);
    $stmt->execute();
    $driveFile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($driveFile && file_exists($uploadDirectory . $driveFile["stored_name"])) {
        $ext = strtolower(pathinfo($driveFile["stored_name"], PATHINFO_EXTENSION));
        if (in_array($ext, ["jpg", "jpeg", "png"], true)) {
            $newAvatarName = "avatar_" . $user_id . "_" . bin2hex(random_bytes(8)) . "." . $ext;
            if (copy($uploadDirectory . $driveFile["stored_name"], $profileDirectory . $newAvatarName)) {
                $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                $stmt->bind_param("si", $newAvatarName, $user_id);
                $stmt->execute();
                $stmt->close();

                header("Location: first.php?avatar=updated");
                exit;
            }
        }
    }
}

// RETRIEVE LOGGED-IN USER PROFILE
$sql = "SELECT fullname, username, email, profile_pic
        FROM users
        WHERE id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

// HANDLE PERSONAL ARCHIVE FILE UPLOAD
if (isset($_POST["upload_file"])) {
    if (!isset($_POST["archive_token"]) || !hash_equals($_SESSION["archive_token"], $_POST["archive_token"])) {
        die("Invalid request.");
    }

    if (isset($_FILES["archive_file"]) && $_FILES["archive_file"]["error"] === UPLOAD_ERR_OK) {
        $file = $_FILES["archive_file"];
        $originalName = basename($file["name"]);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $allowedExtensions = [
            "pdf", "doc", "docx",
            "xls", "xlsx",
            "ppt", "pptx",
            "txt", "jpg", "jpeg", "png"
        ];

        $maxSize = 5 * 1024 * 1024;
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file["tmp_name"]);

        $allowedMimeTypes = [
            "application/pdf",
            "application/msword",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "application/vnd.ms-excel",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "application/vnd.ms-powerpoint",
            "application/vnd.openxmlformats-officedocument.presentationml.presentation",
            "application/zip",
            "text/plain",
            "image/jpeg",
            "image/png"
        ];

        if (!in_array($extension, $allowedExtensions, true)) {
            $archiveMessage = "File type is not supported.";
        } elseif (!in_array($mimeType, $allowedMimeTypes, true)) {
            $archiveMessage = "Invalid file format.";
        } elseif ($file["size"] > $maxSize) {
            $archiveMessage = "File must not exceed 5 MB.";
        } else {
            $storedName = bin2hex(random_bytes(24)) . "." . $extension;
            $destination = $uploadDirectory . $storedName;

            if (move_uploaded_file($file["tmp_name"], $destination)) {
                $sql = "INSERT INTO user_files
                        (owner_user_id, original_name, stored_name, mime_type, file_size)
                        VALUES (?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssi", $user_id, $originalName, $storedName, $mimeType, $file["size"]);

                if ($stmt->execute()) {
                    header("Location: first.php?archive=uploaded");
                    exit;
                } else {
                    unlink($destination);
                    $archiveMessage = "Unable to save file information.";
                }
                $stmt->close();
            } else {
                $archiveMessage = "Unable to upload file.";
            }
        }
    } elseif (isset($_FILES["archive_file"])) {
        $archiveMessage = "Upload failed. Please check your file.";
    }
}

// RETRIEVE ONLY THE LOGGED-IN USER'S FILES
$sql = "SELECT * FROM user_files
        WHERE owner_user_id = ?
        ORDER BY uploaded_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userFiles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Filter image files from userFiles for the "Change display picture" modal
$driveImages = array_filter($userFiles, function ($f) {
    $ext = strtolower(pathinfo($f['original_name'], PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png'], true);
});

// SEARCH ROOMS
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $sql = "SELECT * FROM rooms WHERE room_name LIKE ? ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("s", $searchTerm);
} else {
    $sql = "SELECT * FROM rooms ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Researva | Available Rooms</title>

<!-- Bootstrap 5.3.3 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --purple: #625fc8;
    --purple-hover: #514eb5;
    --blue: #0f7ad1;
    --teal: #2ca8a8;
    --text: #17232c;
    --muted: #667781;
    --line: #dce5e8;
    --panel: rgba(255,255,255,.97);
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    font-family: "Poppins", Arial, sans-serif;
    color: var(--text);
    background:
        linear-gradient(135deg, rgba(8,42,71,.92), rgba(44,168,168,.88)),
        url("https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=2000&q=85")
        center / cover fixed no-repeat;
}

/* HEADER */
.site-header {
    min-height: 80px;
    width: min(100% - 8.5%, 1400px);
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    color: white;
}

.brand {
    color: white;
    text-decoration: none;
    font-size: 23px;
    font-weight: 700;
    letter-spacing: -.7px;
}

.site-nav {
    display: flex;
    align-items: center;
    gap: clamp(20px, 3vw, 44px);
}

.site-nav a {
    color: rgba(255,255,255,.92);
    text-decoration: none;
    font-size: 14px;
    font-weight: 300;
    padding: 8px 0;
}

.site-nav a:hover {
    border-bottom: 2px solid white;
}

.pill {
    border: 0;
    border-radius: 999px;
    background: var(--purple);
    color: white;
    padding: 13px 23px;
    font: 600 11px "Poppins", sans-serif;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform .15s ease, background .15s ease;
}

.pill:hover {
    background: var(--purple-hover);
    color: white;
    transform: translateY(-1px);
}

.header-cta {
    padding: 13px 22px;
}

/* THREE-COLUMN DASHBOARD */
.page-layout {
    width: fit-content;
    max-width: calc(100% - 40px);
    margin: 30px auto 60px;
    display: grid;
    grid-template-columns:
        230px
        minmax(400px, 680px)
        260px;
    gap: 22px;
    align-items: start;
    justify-content: center;
}

.page-content {
    min-width: 0;
    width: 100%;
}

.welcome {
    color: white;
    margin: 0 0 25px;
}

.welcome h1 {
    font-size: clamp(24px, 3vw, 32px);
    line-height: 1.2;
    margin: 0 0 7px;
    font-weight: 600;
    letter-spacing: -.6px;
}

.welcome p {
    margin: 0;
    font-size: 14px;
    line-height: 1.7;
    font-weight: 300;
    color: rgba(255,255,255,.86);
}

/* SEARCH SECTION */
.search-container {
    background: var(--panel);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 25px;
    box-shadow: 0 12px 34px rgba(3,28,43,.16);
}

.search-container label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 10px;
    color: var(--text);
}

.search-box {
    display: flex;
    gap: 8px;
}

.search-input {
    min-width: 0;
    flex: 1;
    border: 0;
    outline: 0;
    background: #eef4f6;
    border-radius: 25px;
    padding: 12px 16px;
    font: 400 13px "Poppins", sans-serif;
    color: var(--text);
}

.search-input:focus {
    box-shadow: inset 0 0 0 1px rgba(15,122,209,.45);
}

.search-btn {
    border: 0;
    border-radius: 25px;
    background: var(--blue);
    color: white;
    padding: 10px 22px;
    font: 500 12px "Poppins", sans-serif;
    cursor: pointer;
}

.search-btn:hover {
    background: #0968b5;
}

.clear-search {
    display: inline-block;
    margin-top: 10px;
    color: var(--blue);
    font-size: 11px;
    text-decoration: none;
}

/* ROOMS SECTION */
.rooms-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 25px 3px 12px;
}

.rooms-header h2 {
    color: rgba(255,255,255,.95);
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 1.5px;
    margin: 0;
}

.room-count {
    color: rgba(255,255,255,.85);
    font-size: 11px;
}

.rooms-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.room-card {
    background: var(--panel);
    border: 1px solid rgba(255,255,255,.5);
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 12px 34px rgba(3,28,43,.16);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 195px;
    transition: .25s ease;
}

.room-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 17px 35px rgba(3,28,43,.25);
}

.room-icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    background: #d9f3ef;
    color: #0d9488;
    border-radius: 10px;
    font-size: 21px;
    margin-bottom: 13px;
}

.room-card h3 {
    color: var(--text);
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 7px;
    overflow-wrap: anywhere;
}

.room-card p {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.6;
    margin-bottom: 15px;
    overflow-wrap: anywhere;
}

.enter-room {
    display: block;
    width: 100%;
    background: #0d9488;
    color: white;
    text-align: center;
    text-decoration: none;
    padding: 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    transition: .2s;
}

.enter-room:hover {
    background: #0f766e;
    color: white;
}

.empty {
    background: rgba(255,255,255,.96);
    border-radius: 12px;
    text-align: center;
    color: var(--muted);
    font-size: 13px;
    padding: 35px 15px;
    grid-column: 1 / -1;
}

.empty-icon {
    font-size: 35px;
    margin-bottom: 10px;
}

/* =================================
   PROFILE SIDEBAR & AVATAR PLUS BUTTON
================================= */
.profile-sidebar {
    position: relative;
    margin-top: 85px;
}

.profile-panel {
    background: rgba(255,255,255,.96);
    border-radius: 16px;
    padding: 22px 18px;
    box-shadow: 0 12px 34px rgba(3,28,43,.18);
    text-align: center;
    transition: .25s ease;
    position: relative;
}

/* Wrapper to anchor the + button and options menu */
.avatar-wrapper {
    position: relative;
    width: 78px;
    height: 78px;
    margin: 0 auto 13px;
}

.profile-avatar {
    width: 100%;
    height: 100%;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: linear-gradient(135deg, #0d9488, #38b2ac);
    color: white;
    font-size: 28px;
    font-weight: 600;
    border: 4px solid #d9f3ef;
    overflow: hidden;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* PLUS (+) BUTTON */
.avatar-plus-btn {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #0f7ad1;
    color: white;
    border: 2px solid #ffffff;
    font-size: 16px;
    font-weight: 600;
    line-height: 1;
    display: grid;
    place-items: center;
    cursor: pointer;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.25);
    transition: transform .15s ease, background .15s ease;
    padding: 0;
}

.avatar-plus-btn:hover {
    background: #0968b5;
    transform: scale(1.08);
}

/* AVATAR OPTIONS POPOVER */
.avatar-options-popover {
    display: none;
    position: absolute;
    top: 48px;
    left: 68px;
    width: 195px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 10px 28px rgba(3, 28, 43, 0.24);
    border: 1px solid #e4ecee;
    padding: 6px;
    z-index: 250;
    text-align: left;
    animation: profileAppear .18s ease;
}

.avatar-options-popover.show {
    display: block;
}

.avatar-option-item {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    background: transparent;
    border: none;
    padding: 9px 11px;
    border-radius: 7px;
    font-family: "Poppins", sans-serif;
    font-size: 12px;
    font-weight: 500;
    color: #0f7ad1;
    text-decoration: underline;
    cursor: pointer;
    text-align: left;
    transition: background .15s ease;
}

.avatar-option-item:hover {
    background: #f0f7fc;
    color: #0968b5;
}

.profile-panel h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 5px;
    color: #17232c;
    overflow-wrap: anywhere;
}

.profile-label {
    font-size: 11px;
    color: #819099;
    margin-bottom: 17px;
}

.profile-button {
    width: 100%;
    border: none;
    border-radius: 9px;
    background: #0d9488;
    color: white;
    padding: 11px;
    font-family: "Poppins", sans-serif;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: .2s;
}

.profile-button:hover {
    background: #0f766e;
}

/* PROFILE INFO POPOVER */
.profile-popover {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    margin-top: 12px;
    padding: 19px;
    background: white;
    border-radius: 13px;
    box-shadow: 0 12px 35px rgba(0,0,0,.2);
    z-index: 100;
    animation: profileAppear .2s ease;
}

.profile-popover.show {
    display: block;
}

@keyframes profileAppear {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.profile-popover h4 {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 17px;
    color: #17232c;
}

.profile-info {
    text-align: left;
    margin-bottom: 15px;
}

.profile-info label {
    display: block;
    font-size: 10px;
    font-weight: 600;
    color: #8b9ba3;
    text-transform: uppercase;
    letter-spacing: .7px;
    margin-bottom: 4px;
}

.profile-info p {
    font-size: 12px;
    color: #263943;
    font-weight: 500;
    margin: 0;
    overflow-wrap: anywhere;
}

.profile-divider {
    border: 0;
    border-top: 1px solid #e8eeee;
    margin: 14px 0;
}

.profile-status {
    font-size: 10px;
    color: #0d9488;
    font-weight: 500;
}

/* PERSONAL ARCHIVE */
.archive-sidebar {
    margin-top: 85px;
    min-width: 0;
}

.archive-panel {
    background: rgba(255,255,255,.97);
    border-radius: 15px;
    padding: 18px;
    box-shadow: 0 12px 34px rgba(3,28,43,.18);
}

.archive-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.archive-heading h3 {
    font-size: 15px;
    font-weight: 600;
    margin: 0;
    color: #17232c;
}

.archive-heading span {
    background: #d9f3ef;
    color: #0d9488;
    font-size: 10px;
    font-weight: 600;
    padding: 5px 8px;
    border-radius: 20px;
}

.archive-upload {
    border: 2px dashed #b9dcd7;
    background: #f3faf9;
    border-radius: 11px;
    padding: 18px 10px;
    text-align: center;
    margin-bottom: 18px;
}

.archive-upload .upload-icon {
    font-size: 28px;
    color: #0d9488;
    margin-bottom: 5px;
}

.archive-upload p {
    font-size: 11px;
    color: #52656d;
    margin-bottom: 12px;
}

.archive-upload input[type="file"] {
    display: none;
}

.archive-select {
    display: inline-block;
    background: #0d9488;
    color: white;
    padding: 9px 15px;
    border-radius: 7px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

.archive-select:hover {
    background: #0f766e;
}

.archive-submit {
    width: 100%;
    border: none;
    background: #625fc8;
    color: white;
    border-radius: 7px;
    padding: 10px;
    font-family: "Poppins", sans-serif;
    font-size: 11px;
    font-weight: 600;
    margin-top: 10px;
    cursor: pointer;
}

.archive-submit:hover {
    background: #514eb5;
}

.archive-list-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.archive-list-title h4 {
    font-size: 12px;
    font-weight: 600;
    margin: 0;
    color: #263943;
}

.archive-list-title span {
    font-size: 10px;
    color: #819099;
}

.archive-file {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 10px 0;
    border-bottom: 1px solid #edf1f2;
}

.archive-file:last-child {
    border-bottom: none;
}

.file-icon {
    flex-shrink: 0;
    width: 35px;
    height: 35px;
    background: #e3f4f1;
    color: #0d9488;
    display: grid;
    place-items: center;
    border-radius: 8px;
    font-size: 16px;
}

.file-details {
    flex: 1;
    min-width: 0;
}

.file-details h5 {
    font-size: 10px;
    font-weight: 600;
    margin: 0 0 3px;
    overflow-wrap: anywhere;
    color: #263943;
}

.file-details p {
    font-size: 9px;
    color: #8999a2;
    margin: 0;
}

.file-actions {
    display: flex;
    gap: 5px;
}

.file-actions a,
.file-actions button {
    width: 27px;
    height: 27px;
    border: none;
    border-radius: 6px;
    background: #f0f4f5;
    color: #52656d;
    display: grid;
    place-items: center;
    text-decoration: none;
    font-size: 12px;
    cursor: pointer;
}

.file-actions a:hover {
    background: #d9f3ef;
    color: #0d9488;
}

.file-actions button:hover {
    background: #ffe8e8;
    color: #dc2626;
}

.archive-empty {
    text-align: center;
    padding: 20px 5px;
    color: #91a0a7;
}

.archive-empty div {
    font-size: 28px;
    margin-bottom: 6px;
}

.archive-empty p {
    font-size: 10px;
    margin: 0;
}

.archive-alert {
    font-size: 10px;
    padding: 9px;
    border-radius: 7px;
    margin-bottom: 12px;
    overflow-wrap: anywhere;
}

/* DRIVE IMAGE PICKER GRID IN MODAL */
.drive-picker-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-top: 10px;
}

.drive-picker-item {
    border: 2px solid #dce5e8;
    border-radius: 10px;
    padding: 6px;
    cursor: pointer;
    text-align: center;
    transition: .15s ease;
    background: #f8fbfc;
}

.drive-picker-item:hover {
    border-color: #0d9488;
    background: #eef9f7;
}

.drive-picker-item img {
    width: 100%;
    height: 75px;
    object-fit: cover;
    border-radius: 6px;
    margin-bottom: 5px;
}

.drive-picker-item span {
    display: block;
    font-size: 10px;
    color: #263943;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* RESPONSIVE */
@media (max-width: 1100px) {
    .page-layout {
        width: min(100% - 40px, 950px);
        max-width: none;
        grid-template-columns: 200px minmax(0, 680px);
        justify-content: center;
    }

    .archive-sidebar {
        grid-column: 1 / -1;
        margin-top: 0;
    }
}

@media (max-width: 760px) {
    .site-header {
        width: min(100% - 36px, 600px);
        flex-wrap: wrap;
        padding: 16px 0;
    }

    .site-nav {
        order: 3;
        width: 100%;
        justify-content: space-between;
        gap: 12px;
    }

    .site-nav a {
        font-size: 12px;
    }

    .header-cta {
        padding: 11px 15px;
    }

    .page-layout {
        width: calc(100% - 28px);
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .profile-sidebar,
    .archive-sidebar {
        margin-top: 0;
    }

    .archive-sidebar {
        grid-column: auto;
    }

    .profile-panel {
        display: grid;
        grid-template-columns: 60px 1fr;
        align-items: center;
        text-align: left;
        gap: 0 12px;
        padding: 15px;
    }

    .avatar-wrapper {
        width: 56px;
        height: 56px;
        grid-row: span 2;
        margin: 0;
    }

    .profile-avatar {
        font-size: 21px;
    }

    .avatar-plus-btn {
        width: 22px;
        height: 22px;
        font-size: 14px;
    }

    .avatar-options-popover {
        left: 0;
        top: 62px;
    }

    .profile-panel h3 {
        margin: 0;
        align-self: end;
    }

    .profile-label {
        margin: 0;
        align-self: start;
    }

    .profile-button {
        grid-column: 1 / -1;
        margin-top: 13px;
    }

    .profile-popover {
        position: relative;
        top: auto;
        margin-top: 10px;
    }

    .page-content {
        margin: 0 auto 40px;
    }
}

@media (max-width: 480px) {
    .rooms-grid {
        grid-template-columns: 1fr;
    }

    .search-box {
        flex-wrap: wrap;
    }
}
</style>
</head>
<body>

<!-- NAVIGATION -->
<header class="site-header">
    <a class="brand" href="first.php" aria-label="Researva">Researva</a>

    <nav class="site-nav" aria-label="Main navigation">
        <a href="first.php">Home</a>
        <a href="inquire.php">FAQs</a>
    </nav>

    <a class="pill header-cta" href="../logout.php">Log out</a>
</header>

<!-- MAIN CONTENT -->
<main class="page-layout" id="home">

    <!-- LEFT PROFILE SIDEBAR -->
    <aside class="profile-sidebar">
        <div class="profile-panel">

            <!-- AVATAR + PLUS BUTTON -->
            <div class="avatar-wrapper">
                <div class="profile-avatar">
                    <?php if (!empty($profile['profile_pic']) && file_exists($profileDirectory . $profile['profile_pic'])): ?>
                        <img src="../profile_uploads/<?php echo htmlspecialchars($profile['profile_pic']); ?>" alt="Profile Picture">
                    <?php else: ?>
                        <?php echo strtoupper(substr($profile['fullname'] ?? 'U', 0, 1)); ?>
                    <?php endif; ?>
                </div>

                <button
                    type="button"
                    class="avatar-plus-btn"
                    id="avatarPlusBtn"
                    title="Update profile picture"
                    aria-label="Update profile picture"
                    aria-expanded="false"
                >+</button>

                <!-- OPTIONS POPOVER -->
                <div class="avatar-options-popover" id="avatarOptionsPopover">
                    <button type="button" class="avatar-option-item" id="openDrivePickerBtn">
                        Change display picture
                    </button>
                    <button type="button" class="avatar-option-item" id="triggerUploadNewBtn">
                        Upload new picture
                    </button>
                </div>
            </div>

            <!-- HIDDEN FORM FOR DIRECT NEW PICTURE UPLOAD -->
            <form method="POST" enctype="multipart/form-data" id="newAvatarForm" style="display:none;">
                <input type="hidden" name="archive_token" value="<?php echo htmlspecialchars($_SESSION['archive_token']); ?>">
                <input type="hidden" name="upload_new_avatar" value="1">
                <input type="file" name="new_avatar_file" id="newAvatarInput" accept="image/jpeg,image/png,image/webp">
            </form>

            <!-- USER NAME -->
            <h3><?php echo htmlspecialchars($profile['fullname'] ?? 'User'); ?></h3>
            <p class="profile-label">Researva Member</p>

            <!-- POPOVER TRIGGER -->
            <button type="button" class="profile-button" id="profileToggle" aria-expanded="false">
                <span>My Profile</span>
                <span> ↓</span>
            </button>

        </div>

        <!-- PROFILE INFO POPOVER -->
        <div class="profile-popover" id="profilePopover">
            <h4>Account Information</h4>

            <div class="profile-info">
                <label>Full Name</label>
                <p><?php echo htmlspecialchars($profile['fullname'] ?? ''); ?></p>
            </div>

            <div class="profile-info">
                <label>Username</label>
                <p>@<?php echo htmlspecialchars($profile['username'] ?? ''); ?></p>
            </div>

            <div class="profile-info">
                <label>Email Address</label>
                <p><?php echo htmlspecialchars($profile['email'] ?? ''); ?></p>
            </div>

            <hr class="profile-divider">
            <div class="profile-status">● Account Active</div>
        </div>
    </aside>

    <!-- CENTER MAIN CONTENT -->
    <div class="page-content">
        <section class="welcome">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION["fullname"]); ?>!</h1>
            <p>Share your progress.</p>
        </section>

        <!-- SEARCH ROOMS -->
        <section class="search-container">
            <form method="GET" action="first.php">
                <label for="search">Find a Room</label>
                <div class="search-box">
                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="search-input"
                        placeholder="Search room name..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                    <button type="submit" class="search-btn">Search</button>
                </div>

                <?php if ($search !== ''): ?>
                    <a href="first.php" class="clear-search">✕ Clear Search</a>
                <?php endif; ?>
            </form>
        </section>

        <!-- AVAILABLE ROOMS -->
        <section class="rooms-section">
            <div class="rooms-header">
                <h2>AVAILABLE ROOMS</h2>
                <span class="room-count"><?php echo count($rooms); ?> room(s) found</span>
            </div>

            <div class="rooms-grid">
                <?php if (count($rooms) > 0): ?>
                    <?php foreach ($rooms as $room): ?>
                        <div class="room-card">
                            <div>
                                <div class="room-icon">₪</div>
                                <h3><?php echo htmlspecialchars($room['room_name']); ?></h3>
                                <p>
                                    <?php
                                    if (!empty($room['description'])) {
                                        echo nl2br(htmlspecialchars($room['description']));
                                    } else {
                                        echo "No description available.";
                                    }
                                    ?>
                                </p>
                            </div>
                            <a href="room.php?id=<?php echo urlencode($room['id']); ?>" class="enter-room">
                                Enter Room →
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">
                        <div class="empty-icon">🔍</div>
                        <?php if ($search !== ''): ?>
                            <h5>No Rooms Found</h5>
                            <p>No room matches "<?php echo htmlspecialchars($search); ?>". Try searching for another room name.</p>
                        <?php else: ?>
                            <h5>No Available Rooms Yet</h5>
                            <p>There are currently no rooms available. Please check again later.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- RIGHT PERSONAL ARCHIVE -->
    <aside class="archive-sidebar">
        <div class="archive-panel">
            <div class="archive-heading">
                <h3>My Drive</h3>
                <span>PRIVATE</span>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="archive_token" value="<?php echo htmlspecialchars($_SESSION['archive_token']); ?>">
                <div class="archive-upload">
                    <div class="upload-icon">☁</div>
                    <p>Store your personal files here</p>
                    <label for="archive_file" class="archive-select">+ Choose File</label>
                    <input
                        type="file"
                        name="archive_file"
                        id="archive_file"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png"
                        required
                    >
                    <div id="selectedFile" style="font-size:10px; color:#0d9488; margin-top:9px;"></div>
                    <button type="submit" name="upload_file" class="archive-submit">Upload to My Drive</button>
                </div>
            </form>

            <?php if (!empty($archiveMessage)): ?>
                <div class="alert alert-danger archive-alert"><?php echo htmlspecialchars($archiveMessage); ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['archive']) && $_GET['archive'] === 'uploaded'): ?>
                <div class="alert alert-success archive-alert">File uploaded successfully!</div>
            <?php endif; ?>

            <?php if (isset($_GET['archive']) && $_GET['archive'] === 'deleted'): ?>
                <div class="alert alert-success archive-alert">File deleted successfully!</div>
            <?php endif; ?>

            <div class="archive-list-title">
                <h4>My Files</h4>
                <span><?php echo count($userFiles); ?> file(s)</span>
            </div>

            <?php if (count($userFiles) > 0): ?>
                <?php foreach ($userFiles as $file): ?>
                    <?php
                        $extension = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
                        $fileIcon = "📄";
                        if ($extension === "pdf") $fileIcon = "📕";
                        elseif (in_array($extension, ["jpg", "jpeg", "png"])) $fileIcon = "🖼️";
                        elseif (in_array($extension, ["doc", "docx"])) $fileIcon = "📝";
                        elseif (in_array($extension, ["xls", "xlsx"])) $fileIcon = "📊";
                        elseif (in_array($extension, ["ppt", "pptx"])) $fileIcon = "📽️";

                        $fileSize = $file['file_size'] >= 1048576
                            ? number_format($file['file_size'] / 1048576, 2) . " MB"
                            : number_format($file['file_size'] / 1024, 1) . " KB";
                    ?>
                    <div class="archive-file">
                        <div class="file-icon"><?php echo $fileIcon; ?></div>
                        <div class="file-details">
                            <h5 title="<?php echo htmlspecialchars($file['original_name']); ?>">
                                <?php echo htmlspecialchars($file['original_name']); ?>
                            </h5>
                            <p><?php echo $fileSize; ?></p>
                        </div>
                        <div class="file-actions">
                            <a href="download.php?id=<?php echo (int) $file['id']; ?>" title="Download file">↓</a>
                            <form method="POST" action="delete_file.php" onsubmit="return confirm('Are you sure you want to delete this file?');">
                                <input type="hidden" name="file_id" value="<?php echo (int) $file['id']; ?>">
                                <input type="hidden" name="archive_token" value="<?php echo htmlspecialchars($_SESSION['archive_token']); ?>">
                                <button type="submit" title="Delete file">×</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="archive-empty">
                    <div>📂</div>
                    <p>Your drive is empty.</p>
                    <p>Upload your first file!</p>
                </div>
            <?php endif; ?>
        </div>
    </aside>
</main>

<!-- MODAL: CHANGE DISPLAY PICTURE (FROM MY DRIVE) -->
<div class="modal fade" id="driveAvatarModal" tabindex="-1" aria-labelledby="driveAvatarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #edf1f2;">
                <h5 class="modal-title" id="driveAvatarModalLabel" style="font-size: 15px; font-weight: 600;">
                    Choose from My Drive
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (count($driveImages) > 0): ?>
                    <p style="font-size: 12px; color: #667781; margin-bottom: 8px;">
                        Click an image from your drive to set it as your display picture:
                    </p>
                    <div class="drive-picker-grid">
                        <?php foreach ($driveImages as $img): ?>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="archive_token" value="<?php echo htmlspecialchars($_SESSION['archive_token']); ?>">
                                <input type="hidden" name="select_drive_avatar" value="1">
                                <input type="hidden" name="drive_file_id" value="<?php echo (int) $img['id']; ?>">
                                <button type="submit" class="drive-picker-item" style="width: 100%;">
                                    <img src="../user_archive_uploads/<?php echo htmlspecialchars($img['stored_name']); ?>" alt="Drive image">
                                    <span><?php echo htmlspecialchars($img['original_name']); ?></span>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px 10px; color: #819099; font-size: 12px;">
                        <div style="font-size: 28px; margin-bottom: 6px;">🖼️</div>
                        No image files found in your drive.<br>
                        Use <strong>Upload new picture</strong> instead!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle (Required for Modal) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Archive File Selection Label
const archiveInput = document.getElementById("archive_file");
const selectedFile = document.getElementById("selectedFile");

if (archiveInput) {
    archiveInput.addEventListener("change", function() {
        selectedFile.textContent = this.files.length > 0
            ? "Selected: " + this.files[0].name
            : "";
    });
}

// My Profile Info Popover
const profileToggle = document.getElementById("profileToggle");
const profilePopover = document.getElementById("profilePopover");

profileToggle.addEventListener("click", function(event) {
    event.stopPropagation();
    profilePopover.classList.toggle("show");
    const isOpen = profilePopover.classList.contains("show");
    profileToggle.setAttribute("aria-expanded", isOpen);
    profileToggle.innerHTML = isOpen ? "My Profile ↑" : "My Profile ↓";
});

// Avatar Plus (+) Button & Options Popover
const avatarPlusBtn = document.getElementById("avatarPlusBtn");
const avatarOptionsPopover = document.getElementById("avatarOptionsPopover");
const triggerUploadNewBtn = document.getElementById("triggerUploadNewBtn");
const newAvatarInput = document.getElementById("newAvatarInput");
const newAvatarForm = document.getElementById("newAvatarForm");
const openDrivePickerBtn = document.getElementById("openDrivePickerBtn");
const driveAvatarModal = new bootstrap.Modal(document.getElementById("driveAvatarModal"));

avatarPlusBtn.addEventListener("click", function(event) {
    event.stopPropagation();
    avatarOptionsPopover.classList.toggle("show");
    avatarPlusBtn.setAttribute("aria-expanded", avatarOptionsPopover.classList.contains("show"));
});

// Option 1: Change display picture (opens My Drive image selector)
openDrivePickerBtn.addEventListener("click", function() {
    avatarOptionsPopover.classList.remove("show");
    driveAvatarModal.show();
});

// Option 2: Upload new picture (triggers file browser and auto-submits)
triggerUploadNewBtn.addEventListener("click", function() {
    avatarOptionsPopover.classList.remove("show");
    newAvatarInput.click();
});

newAvatarInput.addEventListener("change", function() {
    if (this.files.length > 0) {
        newAvatarForm.submit();
    }
});

// Close popovers when clicking outside
document.addEventListener("click", function(event) {
    if (!profilePopover.contains(event.target) && !profileToggle.contains(event.target)) {
        profilePopover.classList.remove("show");
        profileToggle.setAttribute("aria-expanded", "false");
        profileToggle.innerHTML = "My Profile ↓";
    }

    if (!avatarOptionsPopover.contains(event.target) && !avatarPlusBtn.contains(event.target)) {
        avatarOptionsPopover.classList.remove("show");
        avatarPlusBtn.setAttribute("aria-expanded", "false");
    }
});
</script>
</body>
</html>