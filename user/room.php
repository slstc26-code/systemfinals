
<?php
session_start();
include "../config/database.php";

// USER ACCESS CHECK
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
    header("Location: first.php");
    exit;
}

// GET ROOM ID
$room_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$room_id || $room_id < 1) {
    die("Invalid room ID.");
}

$user_id = $_SESSION['user_id'];
$message = "";
$message_type = "success";

// CSRF TOKEN
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// FETCH ROOM
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();

$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    die("Room not found.");
}

// HANDLE FILE UPLOAD
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['upload_file'])) {

    if (!hash_equals(
        $_SESSION['csrf_token'],
        $_POST['csrf_token'] ?? ''
    )) {
        $message = "Invalid request. Please refresh the page.";
        $message_type = "danger";

    } elseif (
        !isset($_FILES['room_file']) ||
        $_FILES['room_file']['error'] !== UPLOAD_ERR_OK
    ) {
        $message = "Please select a file to upload.";
        $message_type = "danger";

    } else {

        $file = $_FILES['room_file'];

        // MAXIMUM FILE SIZE: 10MB
        $max_size = 10 * 1024 * 1024;

        // ALLOWED FILE TYPES
        $allowed_types = [
            'pdf'  => ['application/pdf'],
            'doc'  => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls'  => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ppt'  => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'txt'  => ['text/plain'],
            'png'  => ['image/png'],
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg']
        ];

        $original_name = basename($file['name']);
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        if ($file['size'] > $max_size) {

            $message = "File is too large. Maximum size is 10MB.";
            $message_type = "danger";

        } elseif (!isset($allowed_types[$extension])) {

            $message = "File type not allowed.";
            $message_type = "danger";

        } else {

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($file['tmp_name']);

            if (!in_array($mime_type, $allowed_types[$extension], true)) {

                $message = "Invalid file format.";
                $message_type = "danger";

            } else {

                // CREATE UPLOAD DIRECTORY
                $upload_dir = __DIR__ . "/uploads/rooms/";

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // GENERATE UNIQUE FILE NAME
                $stored_name = bin2hex(random_bytes(16)) . "." . $extension;

                $destination = $upload_dir . $stored_name;

                if (move_uploaded_file($file['tmp_name'], $destination)) {

                    $file_path = "uploads/rooms/" . $stored_name;

                    // SAVE FILE INFORMATION TO DATABASE
                    $sql = "INSERT INTO room_files
                            (room_id, uploaded_by, original_name,
                             stored_name, file_path, file_size)
                            VALUES (?, ?, ?, ?, ?, ?)";

                    $stmt = $conn->prepare($sql);

                    $stmt->bind_param(
                        "iisssi",
                        $room_id,
                        $user_id,
                        $original_name,
                        $stored_name,
                        $file_path,
                        $file['size']
                    );

                    try {
                        $stmt->execute();

                        $message = "File uploaded successfully!";
                        $message_type = "success";

                    } catch (mysqli_sql_exception $e) {

                        unlink($destination);

                        $message = "Unable to save file information.";
                        $message_type = "danger";
                    }

                } else {

                    $message = "Unable to upload file. Check folder permissions.";
                    $message_type = "danger";
                }
            }
        }
    }
}

// FETCH UPLOADED FILES
$sql = "SELECT room_files.*, users.fullname
        FROM room_files
        LEFT JOIN users ON room_files.uploaded_by = users.id
        WHERE room_files.room_id = ?
        ORDER BY room_files.uploaded_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();

$files = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Researva | <?php echo htmlspecialchars($room['room_name']); ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    font-family: "Poppins", sans-serif;
    color: #17232c;

    background:
        linear-gradient(
            135deg,
            rgba(8,42,71,.94),
            rgba(44,168,168,.90)
        ),
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
    gap: 24px;
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
    background: #625fc8;
    color: white;
    text-decoration: none;
    padding: 13px 23px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
}

.pill:hover {
    background: #514eb5;
    color: white;
}

/* MAIN */

.page {
    max-width: 850px;
    margin: 45px auto 70px;
}

.welcome {
    color: white;
    margin-bottom: 28px;
}

.welcome h1 {
    font-size: clamp(26px, 4vw, 36px);
    font-weight: 600;
    margin-bottom: 8px;
}

.welcome p {
    font-size: 14px;
    font-weight: 300;
    color: rgba(255,255,255,.85);
}

/* CARDS */

.room-card {
    background: rgba(255,255,255,.97);
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 22px;
    box-shadow: 0 12px 34px rgba(3,28,43,.18);
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #17232c;
}

.description {
    color: #667781;
    font-size: 13px;
    line-height: 1.7;
}

/* UPLOAD AREA */

.upload-area {
    border: 2px dashed #a9c6ce;
    border-radius: 12px;
    padding: 35px 20px;
    text-align: center;
    background: #f5fafb;
    transition: .2s;
}

.upload-area:hover {
    border-color: #2ca8a8;
    background: #eef8f8;
}

.upload-icon {
    font-size: 40px;
    margin-bottom: 10px;
}

.upload-area h5 {
    font-size: 15px;
    font-weight: 600;
}

.upload-area p {
    font-size: 12px;
    color: #71828a;
}

.file-input {
    display: none;
}

.choose-btn {
    display: inline-block;
    background: #625fc8;
    color: white;
    padding: 11px 22px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: .2s;
}

.choose-btn:hover {
    background: #514eb5;
}

.selected-file {
    margin-top: 15px;
    color: #16877e;
    font-size: 12px;
    font-weight: 500;
    overflow-wrap: anywhere;
}

.upload-btn {
    width: 100%;
    margin-top: 15px;
    border: none;
    background: #159b91;
    color: white;
    padding: 13px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    transition: .2s;
}

.upload-btn:hover {
    background: #10786f;
}

.upload-btn:disabled {
    opacity: .5;
    cursor: not-allowed;
}

/* FILE LIST */

.file-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;

    padding: 15px;
    border: 1px solid #e2eaed;
    border-radius: 10px;
    margin-top: 12px;
    background: #fff;
}

.file-info {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.file-icon {
    font-size: 28px;
}

.file-details {
    min-width: 0;
}

.file-name {
    font-size: 13px;
    font-weight: 600;
    overflow-wrap: anywhere;
}

.file-meta {
    font-size: 10px;
    color: #819099;
    margin-top: 4px;
}

.download-btn {
    background: #159b91;
    color: white;
    text-decoration: none;
    padding: 9px 15px;
    border-radius: 7px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

.download-btn:hover {
    background: #10786f;
    color: white;
}

.empty {
    text-align: center;
    padding: 30px 10px;
    color: #84939a;
    font-size: 13px;
}

/* ALERT */

.alert {
    font-size: 13px;
    border-radius: 9px;
}

/* RESPONSIVE */

@media(max-width: 760px) {

    .site-header {
        width: calc(100% - 36px);
        flex-wrap: wrap;
        padding: 15px 0;
    }

    .site-nav {
        order: 3;
        width: 100%;
        justify-content: center;
        gap: 30px;
    }

    .page {
        width: calc(100% - 28px);
        margin-top: 25px;
    }

    .room-card {
        padding: 17px;
    }

    .file-item {
        align-items: flex-start;
    }

    .download-btn {
        padding: 8px 10px;
    }
}
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,.15);
    color: white;
    border: 1px solid rgba(255,255,255,.5);
    padding: 10px 20px;
    border-radius: 999px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    transition: .2s;
    margin-bottom: 20px;
}

.back-btn:hover {
    background: white;
    color: #17465C;
    transform: translateX(-3px);
}
</style>
</head>

<body>

<!-- HEADER -->

<header class="site-header">

    <a href="first.php" class="brand">
        Researva
    </a>

    <nav class="site-nav">
        <a href="first.php">Home</a>
        <a href="inquire.php">Inquire</a>
    </nav>

    <a href="../logout.php" class="pill">
        Log out
    </a>

</header>


<!-- MAIN CONTENT -->

<main class="container page">
    <!-- BACK BUTTON -->
<a href="first.php" class="back-btn">
    ← Back to Rooms
</a>

    <!-- ROOM INFORMATION -->

    <section class="welcome">

        <h1>
            <?php echo htmlspecialchars($room['room_name']); ?>
        </h1>

        <p>
            <?php echo htmlspecialchars($room['description'] ?? 'Share your progress.'); ?>
        </p>

    </section>


    <!-- NOTIFICATION -->

    <?php if ($message): ?>

        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <!-- UPLOAD SECTION -->

    <div class="room-card">

        <h5 class="section-title">
            Upload Your File
        </h5>

        <p class="description">
            Share your documents, activities, and other files with this room.
        </p>

        <form method="POST" enctype="multipart/form-data">

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
            >

            <div class="upload-area">

                <div class="upload-icon">
                    📁
                </div>

                <h5>Select a file to upload</h5>

                <p>
                    PDF, Word, Excel, PowerPoint, TXT, and Images
                    <br>
                    Maximum file size: 10MB
                </p>

                <label for="room_file" class="choose-btn">
                    + Choose File
                </label>

                <input
                    type="file"
                    name="room_file"
                    id="room_file"
                    class="file-input"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.png,.jpg,.jpeg"
                    required
                >

                <div class="selected-file" id="selectedFile">
                    No file selected
                </div>

            </div>

            <button
                type="submit"
                name="upload_file"
                class="upload-btn"
                id="uploadButton"
                disabled
            >
                Upload File
            </button>

        </form>

    </div>


    <!-- UPLOADED FILES -->

    <div class="room-card">

        <h5 class="section-title">
            Shared Files
        </h5>

        <p class="description">
            Files uploaded by members of this room.
        </p>

        <?php if ($files->num_rows > 0): ?>

            <?php while ($file = $files->fetch_assoc()): ?>

                <?php
                    $extension = strtolower(
                        pathinfo($file['original_name'], PATHINFO_EXTENSION)
                    );

                    $icon = '📄';

                    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                        $icon = '🖼️';
                    } elseif ($extension === 'pdf') {
                        $icon = '📕';
                    } elseif (in_array($extension, ['doc', 'docx'])) {
                        $icon = '📝';
                    } elseif (in_array($extension, ['xls', 'xlsx'])) {
                        $icon = '📊';
                    } elseif (in_array($extension, ['ppt', 'pptx'])) {
                        $icon = '📽️';
                    }

                    $file_size = $file['file_size'] >= 1048576
                        ? number_format($file['file_size'] / 1048576, 2) . ' MB'
                        : number_format($file['file_size'] / 1024, 2) . ' KB';
                ?>

                <div class="file-item">

                    <div class="file-info">

                        <div class="file-icon">
                            <?php echo $icon; ?>
                        </div>

                        <div class="file-details">

                            <div class="file-name">
                                <?php echo htmlspecialchars($file['original_name']); ?>
                            </div>

                            <div class="file-meta">

                                Uploaded by:
                                <?php echo htmlspecialchars($file['fullname'] ?? 'User'); ?>

                                • <?php echo $file_size; ?>

                                • <?php echo date(
                                    'M d, Y',
                                    strtotime($file['uploaded_at'])
                                ); ?>

                            </div>

                        </div>

                    </div>

                    <a
                        href="<?php echo htmlspecialchars($file['file_path']); ?>"
                        class="download-btn"
                        download="<?php echo htmlspecialchars($file['original_name']); ?>"
                    >
                        Download
                    </a>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                <div style="font-size:35px;">📂</div>
                <p>No files uploaded yet.</p>
                <small>Be the first to share a file in this room!</small>
            </div>

        <?php endif; ?>

    </div>

</main>


<!-- FILE SELECTION SCRIPT -->

<script>

const fileInput = document.getElementById('room_file');
const selectedFile = document.getElementById('selectedFile');
const uploadButton = document.getElementById('uploadButton');

fileInput.addEventListener('change', function() {

    if (this.files.length > 0) {

        const file = this.files[0];

        selectedFile.textContent =
            "Selected: " + file.name;

        uploadButton.disabled = false;

    } else {

        selectedFile.textContent = "No file selected";

        uploadButton.disabled = true;

    }

});

</script>

</body>
</html>
