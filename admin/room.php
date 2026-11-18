<?php
session_start();
include "../config/database.php";

// Admin access protection
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: first.php");
    exit;
}

// Get room ID from URL
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$admin_id = $_SESSION['user_id'];

// Retrieve room using MySQLi
$sql = "SELECT * FROM rooms
        WHERE id = ? AND created_by = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $room_id, $admin_id);
$stmt->execute();

$result = $stmt->get_result();
$room = $result->fetch_assoc();

$stmt->close();

// Check if room exists
if (!$room) {
    header("Location: first.php");
    exit;
}
?>
<?php
// FETCH FILES UPLOADED IN THIS ROOM
$sql = "SELECT room_files.*, users.fullname
        FROM room_files
        LEFT JOIN users
        ON room_files.uploaded_by = users.id
        WHERE room_files.room_id = ?
        ORDER BY room_files.uploaded_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();

$uploaded_files = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
    <?php echo htmlspecialchars($room['room_name']); ?> | Researva
</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

:root {
    --purple: #625fc8;
    --purple-hover: #514eb5;
    --teal: #0d9488;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    font-family: "Poppins", sans-serif;
    color: white;

    background:
        linear-gradient(135deg, rgba(8,42,71,.92), rgba(44,168,168,.88)),
        url("https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=2000&q=85")
        center / cover fixed no-repeat;
}

/* HEADER */

.site-header {
    min-height: 80px;
    width: min(100% - 8.5%, 1400px);
    margin: auto;

    display: flex;
    align-items: center;
    justify-content: space-between;
}

.brand {
    color: white;
    text-decoration: none;
    font-size: 23px;
    font-weight: 700;
}

.site-nav {
    display: flex;
    gap: 40px;
}

.site-nav a {
    color: white;
    text-decoration: none;
    font-size: 14px;
    font-weight: 300;
}

.pill {
    border: 0;
    border-radius: 999px;
    background: var(--purple);
    color: white;
    padding: 13px 23px;
    font: 600 11px "Poppins", sans-serif;
    text-decoration: none;
}

.pill:hover {
    background: var(--purple-hover);
    color: white;
}

/* MAIN CONTENT */

.page {
    max-width: 680px;
    margin: 45px auto 60px;
}

.back-btn {
    display: inline-block;
    color: white;
    text-decoration: none;
    font-size: 13px;
    margin-bottom: 22px;
}

.back-btn:hover {
    color: #b9f5eb;
}

.room-heading {
    margin-bottom: 25px;
}

.room-heading h1 {
    font-size: clamp(25px, 3vw, 34px);
    font-weight: 600;
    margin-bottom: 8px;
    overflow-wrap: anywhere;
}

.room-heading p {
    font-size: 14px;
    font-weight: 300;
    color: rgba(255,255,255,.85);
}

/* ROOM CONTENT */

.room-panel {
    background: rgba(255,255,255,.97);
    color: #17232c;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 12px 34px rgba(3,28,43,.20);
}

.room-panel h3 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 12px;
}

.room-description {
    color: #667781;
    font-size: 13px;
    line-height: 1.8;
    overflow-wrap: anywhere;
}

.room-info {
    background: #eef4f6;
    border-radius: 9px;
    padding: 15px;
    margin-top: 20px;
}

.room-info p {
    color: #536773;
    font-size: 12px;
    margin: 0;
}

.room-info strong {
    color: #17232c;
}

/* ROOM ACTIONS */

.room-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.action-btn {
    border: none;
    background: var(--teal);
    color: white;
    padding: 11px 20px;
    border-radius: 8px;
    font-family: "Poppins", sans-serif;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
}

.action-btn:hover {
    background: #0f766e;
    color: white;
}

@media(max-width: 760px) {

    .site-header {
        width: calc(100% - 36px);
        flex-wrap: wrap;
        padding: 15px 0;
        gap: 15px;
    }

    .site-nav {
        gap: 18px;
    }

    .page {
        width: calc(100% - 28px);
        margin-top: 25px;
    }
}
.admin-file-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 16px;
    margin-top: 12px;

    background: #ffffff;
    border: 1px solid #e2eaed;
    border-radius: 10px;

    transition: .2s;
}

.admin-file-item:hover {
    border-color: #2ca8a8;
    box-shadow: 0 4px 12px rgba(0,0,0,.06);
}

.admin-file-info {
    display: flex;
    align-items: center;
    gap: 13px;
    min-width: 0;
}

.admin-file-icon {
    font-size: 30px;
}

.admin-file-details {
    min-width: 0;
}

.admin-file-name {
    font-size: 13px;
    font-weight: 600;
    color: #17232c;
    overflow-wrap: anywhere;
}

.admin-file-meta {
    font-size: 10px;
    color: #819099;
    margin-top: 5px;
    line-height: 1.8;
}

.admin-download-btn {
    background: #159b91;
    color: white;
    text-decoration: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
    transition: .2s;
}

.admin-download-btn:hover {
    background: #10786f;
    color: white;
}

.empty-files {
    text-align: center;
    padding: 30px 10px;
    color: #819099;
    font-size: 13px;
}

.empty-files p {
    margin: 10px 0 4px;
    font-weight: 600;
}

@media(max-width: 480px) {
    .admin-file-item {
        align-items: flex-start;
        flex-direction: column;
    }

    .admin-download-btn {
        width: 100%;
        text-align: center;
    }
}

</style>

</head>

<body>

<!-- NAVIGATION -->
<header class="site-header">

    <a class="brand" href="first.php">
        Researva
    </a>

    <nav class="site-nav">
        <a href="first.php">Home</a>
        <a href="inquire.php">Inquire?</a>
    </nav>

    <a class="pill" href="../logout.php">
        Log out
    </a>

</header>


<!-- ROOM PAGE -->
<main class="container page">

    <a href="first.php" class="back-btn">
        ← Back to Dashboard
    </a>

    <!-- ROOM TITLE -->
    <section class="room-heading">

        <h1>
            <?php echo htmlspecialchars($room['room_name']); ?>
        </h1>

        <p>
            Welcome to your room. Share your progress!
        </p>

    </section>


    <!-- ROOM DETAILS -->
    <section class="room-panel">

        <h3>Room Information</h3>

        <p class="room-description">
            <?php
            echo !empty($room['description'])
                ? nl2br(htmlspecialchars($room['description']))
                : "No description available for this room.";
            ?>
        </p>

        <div class="room-info">

            <p>
                <strong>Room ID:</strong>
                <?php echo htmlspecialchars($room['id']); ?>
            </p>

            <p class="mt-2">
                <strong>Room Name:</strong>
                <?php echo htmlspecialchars($room['room_name']); ?>
            </p>

            <p class="mt-2">
                <strong>Created:</strong>
                <?php echo htmlspecialchars($room['created_at'] ?? 'Not available'); ?>
            </p>

        </div>

        <div class="room-actions">

            <a href="first.php" class="action-btn">
                ← Back to Rooms
            </a>

        </div>

    </section>
    <!-- USER UPLOADED FILES -->

<div class="room-card">

    <h5 class="section-title">
        Uploaded Files
    </h5>

    <p class="description">
        View and download files submitted by room members.
    </p>

    <?php if ($uploaded_files->num_rows > 0): ?>

        <?php while ($file = $uploaded_files->fetch_assoc()): ?>

            <?php
                $extension = strtolower(
                    pathinfo($file['original_name'], PATHINFO_EXTENSION)
                );

                $icon = "📄";

                if ($extension == "pdf") {
                    $icon = "📕";
                } elseif (in_array($extension, ["doc", "docx"])) {
                    $icon = "📝";
                } elseif (in_array($extension, ["xls", "xlsx"])) {
                    $icon = "📊";
                } elseif (in_array($extension, ["ppt", "pptx"])) {
                    $icon = "📽️";
                } elseif (in_array($extension, ["jpg", "jpeg", "png"])) {
                    $icon = "🖼️";
                }

                $file_size = $file['file_size'] >= 1048576
                    ? number_format($file['file_size'] / 1048576, 2) . " MB"
                    : number_format($file['file_size'] / 1024, 2) . " KB";

                // Files are stored inside user/uploads/rooms/
                $download_path = "../user/" . ltrim($file['file_path'], "/");
            ?>

            <div class="admin-file-item">

                <div class="admin-file-info">

                    <div class="admin-file-icon">
                        <?php echo $icon; ?>
                    </div>

                    <div class="admin-file-details">

                        <div class="admin-file-name">
                            <?php echo htmlspecialchars($file['original_name']); ?>
                        </div>

                        <div class="admin-file-meta">

                            Uploaded by:
                            <?php echo htmlspecialchars($file['fullname'] ?? 'User'); ?>

                            <br>

                            <?php echo $file_size; ?>

                            •
                            <?php echo date(
                                "M d, Y h:i A",
                                strtotime($file['uploaded_at'])
                            ); ?>

                        </div>

                    </div>

                </div>

                <a
                    href="<?php echo htmlspecialchars($download_path); ?>"
                    class="admin-download-btn"
                    download="<?php echo htmlspecialchars($file['original_name']); ?>"
                >
                    ⬇ Download
                </a>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty-files">
            <div style="font-size:40px;">📂</div>

            <p>No uploaded files yet.</p>

            <small>
                Files uploaded by users will appear here.
            </small>
        </div>

    <?php endif; ?>

</div>

</main>

</body>
</html>
