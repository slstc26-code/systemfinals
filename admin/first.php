
<?php
session_start();
include "../config/database.php";

// Admin access protection
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: admin/first.php");
    exit;
}

$admin_id = $_SESSION['user_id'];

// CSRF SECURITY TOKEN
if (empty($_SESSION['room_token'])) {
    $_SESSION['room_token'] = bin2hex(random_bytes(32));
}

$roomToken = $_SESSION['room_token'];


// EDIT ROOM
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_room'])) {

    if (!hash_equals($roomToken, $_POST['room_token'] ?? '')) {
        die("Invalid request.");
    }

    $room_id = (int) ($_POST['room_id'] ?? 0);
    $room_name = trim($_POST['room_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($room_id > 0 && $room_name !== '') {

        $sql = "UPDATE rooms
                SET room_name = ?, description = ?
                WHERE id = ? AND created_by = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssii",
            $room_name,
            $description,
            $room_id,
            $admin_id
        );

        $stmt->execute();

        if ($stmt->affected_rows >= 0) {
            $_SESSION['room_message'] = "Room successfully updated!";
        }

        $stmt->close();

    } else {
        $_SESSION['room_message'] = "Please enter a valid room name.";
    }

    header("Location: first.php");
    exit;
}


// DELETE ROOM
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_room'])) {

    if (!hash_equals($roomToken, $_POST['room_token'] ?? '')) {
        die("Invalid request.");
    }

    $room_id = (int) ($_POST['room_id'] ?? 0);

    if ($room_id > 0) {

        $sql = "DELETE FROM rooms
                WHERE id = ? AND created_by = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("ii", $room_id, $admin_id);

        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $_SESSION['room_message'] = "Room successfully deleted!";
        } else {
            $_SESSION['room_message'] = "Room not found or you cannot delete this room.";
        }

        $stmt->close();
    }

    header("Location: first.php");
    exit;
}

// Create Room

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['create_room'])) {

    $room_name = trim($_POST['room_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($room_name !== "") {

        $sql = "INSERT INTO rooms
                (room_name, description, created_by)
                VALUES (?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssi",
            $room_name,
            $description,
            $_SESSION['user_id']
        );

        $stmt->execute();
        $stmt->close();

        $_SESSION['room_message'] = "Room successfully created!";

    } else {

        $_SESSION['room_message'] = "Please enter a room name.";

    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


// Get notification
$message = $_SESSION['room_message'] ?? '';
unset($_SESSION['room_message']);

// Retrieve rooms created by this admin
$sql = "SELECT * FROM rooms
        WHERE created_by = ?
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $admin_id);

$stmt->execute();

$result = $stmt->get_result();

$rooms = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Researva</title>

  <!-- Bootstrap 5.3.3 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    .create-room-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            background: #0d9488; /* teal-600 */
            color: white;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 9999px; /* fully rounded */
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.35);
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            user-select: none;
        }

        .create-room-btn:hover {
            background: #0f766e; /* teal-700 */
            transform: scale(1.08);
            box-shadow: 0 8px 25px rgba(13, 148, 136, 0.45);
        }

        .create-room-btn:active {
            transform: scale(0.97);
        }

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
    * { box-sizing: border-box; }
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
    .brand { color: white; text-decoration: none; font-size: 23px; font-weight: 700; letter-spacing: -.7px; }
    .site-nav { display: flex; align-items: center; gap: clamp(20px, 3vw, 44px); }
    .site-nav a { color: rgba(255,255,255,.92); text-decoration: none; font-size: 14px; font-weight: 300; padding: 8px 0; }
    .site-nav a.active { border-bottom: 2px solid white; }
    .pill {
      border: 0; border-radius: 999px; background: var(--purple); color: white;
      padding: 13px 23px; font: 600 11px "Poppins", sans-serif; cursor: pointer;
      text-decoration: none; display: inline-flex; align-items: center; justify-content: center;
      transition: transform .15s ease, background .15s ease;
    }
    .pill:hover { background: var(--purple-hover); color: white; transform: translateY(-1px); }
    .header-cta { padding: 13px 22px; }
    .page { max-width: 680px; margin: 30px auto 60px; }
    .welcome { color: white; margin: 0 0 18px; }
    .welcome h1 { font-size: clamp(24px, 3vw, 32px); line-height: 1.2; margin: 0 0 7px; font-weight: 600; letter-spacing: -.6px; }
    .welcome p { margin: 0; font-size: 14px; line-height: 1.7; font-weight: 300; color: rgba(255,255,255,.86); }
    .lineup-card { background: var(--panel); border: 1px solid rgba(255,255,255,.5); border-radius: 12px; box-shadow: 0 12px 34px rgba(3,28,43,.16); margin-bottom: 18px; overflow: hidden; }
    .composer { padding: 16px; }
    .composer-top, .post-head { display: flex; align-items: center; gap: 11px; }
    .avatar { width: 42px; height: 42px; border-radius: 50%; flex: 0 0 42px; background: #dce7ed; display: grid; place-items: center; overflow: hidden; }
    .avatar svg { width: 35px; height: 35px; margin-top: 6px; }
    .composer-input {
      min-width: 0; flex: 1; border: 0; outline: 0; background: #eef4f6;
      border-radius: 25px; padding: 13px 16px; font: 400 14px "Poppins", sans-serif; color: var(--text);
    }
    .composer-input:focus { box-shadow: inset 0 0 0 1px rgba(15,122,209,.45); }
    .rule { height: 1px; background: var(--line); margin: 15px 0 10px; }
    .composer-actions { display: flex; gap: 7px; }
    .action {
      border: 0; cursor: pointer; border-radius: 8px; font: 500 12px "Poppins", sans-serif;
      padding: 10px 9px; display: inline-flex; align-items: center; justify-content: center; gap: 7px;
      flex: 1; background: transparent; color: #536773;
    }
    .action:hover { background: #edf4f6; color: var(--blue); }
    .action .emoji { font-size: 18px; }
    .upload-label { cursor: pointer; }
    #imageInput { display: none; }
    .preview-wrap { display: none; position: relative; margin-top: 14px; border: 1px solid var(--line); border-radius: 9px; overflow: hidden; }
    .preview-wrap img { width: 100%; max-height: 460px; object-fit: contain; display: block; background: #f7f9fa; }
    .remove-preview { position: absolute; top: 10px; right: 10px; width: 34px; height: 34px; border: 0; border-radius: 50%; background: white; box-shadow: 0 1px 5px #0002; font-size: 20px; cursor: pointer; }
    .post-button { width: 100%; background: var(--blue); color: white; margin-top: 13px; padding: 12px; font: 600 12px "Poppins", sans-serif; border: 0; border-radius: 8px; }
    .post-button:hover { background: #0968b5; }
    .feed-title { color: rgba(255,255,255,.9); font-size: 12px; font-weight: 600; letter-spacing: 1.6px; margin: 24px 3px 10px; }
    .post { padding: 16px 16px 8px; }
    .post-author { font-weight: 600; font-size: 14px; }
    .post-time { color: var(--muted); font-size: 11px; margin-top: 3px; }
    .post-text { font-size: 14px; line-height: 1.7; white-space: pre-wrap; overflow-wrap: anywhere; margin: 14px 0; }
    .post-image { display: block; width: calc(100% + 32px); max-height: 600px; margin: 0 -16px; object-fit: contain; background: #f7f9fa; }
    .post-stats { display: flex; justify-content: space-between; color: var(--muted); font-size: 12px; padding: 12px 0; }
    .post-actions { display: flex; border-top: 1px solid var(--line); padding: 4px 0; gap: 4px; }
    .post-actions button { flex: 1; background: transparent; color: #5b6d77; border: 0; padding: 10px 5px; border-radius: 7px; cursor: pointer; font: 500 12px "Poppins", sans-serif; }
    .post-actions button:hover { background: #edf4f6; }
    .post-actions button.liked { color: var(--blue); }
    .comments { padding: 0 16px 14px; }
    .comment-list { display: grid; gap: 8px; margin-bottom: 10px; }
    .comment-bubble { background: #eef4f6; border-radius: 15px; padding: 9px 12px; font-size: 13px; overflow-wrap: anywhere; }
    .comment-bubble strong { display: block; font-size: 11px; margin-bottom: 3px; }
    .comment-form { display: flex; gap: 8px; }
    .comment-form input { flex: 1; min-width: 0; border: 0; outline: 0; border-radius: 20px; background: #eef4f6; padding: 10px 13px; font: 400 12px "Poppins", sans-serif; }
    .comment-form button { border: 0; background: var(--blue); color: white; border-radius: 20px; padding: 0 16px; cursor: pointer; font: 500 12px "Poppins", sans-serif; }
    .empty { text-align: center; color: var(--muted); font-size: 13px; padding: 25px 14px; }
    @media (max-width: 760px) {
      .site-header { width: min(100% - 36px, 600px); flex-wrap: wrap; padding: 16px 0; }
      .site-nav { order: 3; width: 100%; justify-content: space-between; gap: 12px; }
      .site-nav a { font-size: 12px; }
      .header-cta { padding: 11px 15px; }
      .page { width: calc(100% - 28px); }
    }
    @media (max-width: 480px) {
      .page { width: calc(100% - 16px); margin-top: 20px; }
      .composer { padding: 12px; }
      .composer-actions { gap: 2px; }
      .action { padding: 9px 3px; font-size: 10px; }
      .action .emoji { font-size: 16px; }
      .welcome { padding: 0 4px; }
    }
    
      /* CREATE ROOM SECTION */
      .create-room-btn {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          padding: 14px 28px;
          background: #0d9488;
          color: white;
          font-size: 16px;
          font-weight: 600;
          border: 2px solid white;
          outline: 2px solid black;
          border-radius: 9999px;
          cursor: pointer;
          transition: all 0.25s ease;
      }

      .create-room-btn:hover {
          background: #0f766e;
          transform: scale(1.05);
      }

      /* ROOM FORM */
      .room-form {
          display: none;
          margin-top: 22px;
          padding: 22px;
          background: rgba(255,255,255,.97);
          border-radius: 12px;
          box-shadow: 0 12px 34px rgba(3,28,43,.20);
          animation: slideDown .3s ease;
      }

      .room-form.show {
          display: block;
      }

      .room-form h3 {
          color: #17232c;
          font-size: 19px;
          font-weight: 600;
          margin-bottom: 17px;
      }

      .room-form label {
          color: #536773;
          font-size: 13px;
          font-weight: 500;
          margin-bottom: 6px;
      }

      .room-form input,
      .room-form textarea {
          width: 100%;
          padding: 12px 14px;
          border: 1px solid #dce5e8;
          border-radius: 8px;
          outline: none;
          font-family: "Poppins", sans-serif;
          font-size: 13px;
          margin-bottom: 14px;
      }

      .room-form input:focus,
      .room-form textarea:focus {
          border-color: #0d9488;
          box-shadow: 0 0 0 3px rgba(13,148,136,.12);
      }

      .room-submit {
          background: #0d9488;
          color: white;
          border: none;
          border-radius: 8px;
          padding: 11px 22px;
          font-family: "Poppins", sans-serif;
          font-size: 13px;
          font-weight: 600;
          cursor: pointer;
      }

      .room-submit:hover {
          background: #0f766e;
      }

      .room-cancel {
          background: #e9eff1;
          color: #536773;
          border: none;
          border-radius: 8px;
          padding: 11px 22px;
          font-family: "Poppins", sans-serif;
          font-size: 13px;
          cursor: pointer;
          margin-left: 5px;
      }

      /* SUCCESS MESSAGE */
      .room-message {
          background: #d1fae5;
          color: #065f46;
          border-radius: 8px;
          padding: 12px 16px;
          margin-top: 20px;
          font-size: 13px;
      }

      /* ROOMS GRID */
      .rooms-section {
          margin-top: 35px;
      }

      .rooms-heading {
          color: white;
          font-size: 16px;
          font-weight: 600;
          margin-bottom: 17px;
      }

      .rooms-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
          gap: 18px;
      }

      /* ROOM SQUARE CARD */
      .room-card {
          background: rgba(255,255,255,.96);
          border-radius: 12px;
          padding: 22px;
          min-height: 180px;
          box-shadow: 0 12px 30px rgba(3,28,43,.15);
          transition: .25s ease;
          display: flex;
          flex-direction: column;
          justify-content: space-between;
      }

      .room-card:hover {
          transform: translateY(-5px);
          box-shadow: 0 17px 35px rgba(3,28,43,.25);
      }

      .room-icon {
          width: 43px;
          height: 43px;
          background: #d9f3ef;
          color: #0d9488;
          border-radius: 10px;
          display: grid;
          place-items: center;
          font-size: 21px;
          margin-bottom: 13px;
      }

      .room-card h3 {
          color: #17232c;
          font-size: 16px;
          font-weight: 600;
          margin-bottom: 6px;
          overflow-wrap: anywhere;
      }

      .room-card p {
          color: #667781;
          font-size: 12px;
          margin-bottom: 15px;
          overflow-wrap: anywhere;
      }

      .enter-room {
          background: #0d9488;
          color: white;
          text-decoration: none;
          text-align: center;
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
      /* ROOM ACTION BUTTONS */

.room-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 12px;
}

.room-actions form {
    width: 100%;
}

.room-actions .enter-room {
    width: 100%;
    display: block;
}

.edit-room-btn,
.delete-room-btn {
    width: 100%;
    border: none;
    border-radius: 8px;
    padding: 9px;
    font-family: "Poppins", sans-serif;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: .2s ease;
}

.edit-room-btn {
    background: #e0f2fe;
    color: #0369a1;
}

.edit-room-btn:hover {
    background: #bae6fd;
}

.delete-room-btn {
    background: #fee2e2;
    color: #dc2626;
}

.delete-room-btn:hover {
    background: #fecaca;
}

/* EDIT FORM */

.edit-room-form {
    display: none;
    margin-top: 15px;
    padding: 15px;
    background: #eef4f6;
    border: 1px solid #dce5e8;
    border-radius: 10px;
}

.edit-room-form.show {
    display: block;
    animation: slideDown .25s ease;
}

.edit-room-form h4 {
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 13px;
    color: #17232c;
}

.edit-room-form label {
    display: block;
    font-size: 11px;
    font-weight: 500;
    margin-bottom: 5px;
    color: #536773;
}

.edit-room-form input,
.edit-room-form textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 12px;
    border: 1px solid #dce5e8;
    border-radius: 7px;
    font-family: "Poppins", sans-serif;
    font-size: 12px;
    outline: none;
}

.edit-room-form input:focus,
.edit-room-form textarea:focus {
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13,148,136,.12);
}

.edit-form-actions {
    display: flex;
    gap: 7px;
}

.save-room-btn,
.cancel-edit-btn {
    flex: 1;
    border: none;
    border-radius: 7px;
    padding: 10px 5px;
    font-family: "Poppins", sans-serif;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

.save-room-btn {
    background: #0d9488;
    color: white;
}

.save-room-btn:hover {
    background: #0f766e;
}

.cancel-edit-btn {
    background: #dce5e8;
    color: #536773;
}

.cancel-edit-btn:hover {
    background: #cbd5d9;
}

      .no-rooms {
          background: rgba(255,255,255,.12);
          color: rgba(255,255,255,.85);
          padding: 25px;
          border-radius: 12px;
          text-align: center;
          font-size: 13px;
      }

      @keyframes slideDown {
          from {
              opacity: 0;
              transform: translateY(-10px);
          }
          to {
              opacity: 1;
              transform: translateY(0);
          }
      }

  </style>
</head>
<body>
  <header class="site-header">
    <a class="brand" href="#" aria-label="Researva">Researva</a>
    <nav class="site-nav" aria-label="Main navigation">
      <a href="first.php">Home</a>
    </nav>
    <a class="pill header-cta" href="../logout.php">Log out</a>
  </header>

  <main class="container page" id="home">
    <section class="welcome">
      <h1>Welcome, <?php 
            echo htmlspecialchars($_SESSION["fullname"]);
            ?>!</h1>
      <p>View tasks.</p>
    </section>
    
<!-- CREATE ROOM BUTTON -->
<button type="button" class="create-room-btn" id="createRoomBtn">
    + Create a Room
</button>

<!-- NOTIFICATION -->
<?php if ($message): ?>
    <div class="room-message">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- CREATE ROOM FORM -->
<div class="room-form" id="roomForm">

    <h3>Create a New Room</h3>

    <form method="POST">

        <label>Room Name</label>
        <input
            type="text"
            name="room_name"
            placeholder="Enter your room name..."
            maxlength="100"
            required
        >

        <label>Description</label>
        <textarea
            name="description"
            rows="3"
            placeholder="What is this room about?"
        ></textarea>

        <button type="submit" name="create_room" class="room-submit">
            Create Room
        </button>

        <button type="button" class="room-cancel" id="cancelRoomBtn">
            Cancel
        </button>

    </form>
</div>

<!-- CREATED ROOMS -->
<section class="rooms-section">

    <h2 class="rooms-heading">YOUR ROOMS</h2>

    <?php if (count($rooms) > 0): ?>

        <div class="rooms-grid">

            <?php foreach ($rooms as $room): ?>

                <div class="room-card">

                    <div>
                        <div class="room-icon">₪</div>

                        <h3>
                            <?php echo htmlspecialchars($room['room_name']); ?>
                        </h3>

                        <p>
                            <?php
                            echo htmlspecialchars(
                                $room['description'] ?: 'No description available.'
                            );
                            ?>
                        </p>
                    </div>

                    <!-- ROOM ACTION BUTTONS -->

<div class="room-actions">

    <!-- ENTER ROOM -->
    <a href="room.php?id=<?php echo urlencode($room['id']); ?>"
       class="enter-room">
        Enter Room →
    </a>

    <!-- EDIT ROOM -->
    <button
        type="button"
        class="edit-room-btn"
        onclick="toggleEditForm(<?php echo (int)$room['id']; ?>)">
        ✎ Edit
    </button>

    <!-- DELETE ROOM -->
    <form method="POST"
          onsubmit="return confirm('Are you sure you want to delete this room?');">

        <input type="hidden" name="room_id"
               value="<?php echo (int)$room['id']; ?>">

        <input type="hidden" name="room_token"
               value="<?php echo htmlspecialchars($roomToken); ?>">

        <button type="submit"
                name="delete_room"
                class="delete-room-btn">
            ✕ Delete
        </button>

    </form>

</div>
<!-- EDIT ROOM FORM -->

<div class="edit-room-form"
     id="editForm<?php echo (int)$room['id']; ?>">

    <h4>Edit Room</h4>

    <form method="POST">

        <input type="hidden"
               name="room_id"
               value="<?php echo (int)$room['id']; ?>">

        <input type="hidden"
               name="room_token"
               value="<?php echo htmlspecialchars($roomToken); ?>">

        <label>Room Name</label>

        <input
            type="text"
            name="room_name"
            value="<?php echo htmlspecialchars($room['room_name']); ?>"
            maxlength="100"
            required
        >

        <label>Description</label>

        <textarea
            name="description"
            rows="3"
        ><?php echo htmlspecialchars($room['description'] ?? ''); ?></textarea>

        <div class="edit-form-actions">

            <button type="submit"
                    name="update_room"
                    class="save-room-btn">
                Save Changes
            </button>

            <button type="button"
                    class="cancel-edit-btn"
                    onclick="toggleEditForm(<?php echo (int)$room['id']; ?>)">
                Cancel
            </button>

        </div>

    </form>

</div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="no-rooms">
            No rooms created yet. Click "+ Create a Room" to get started.
        </div>

    <?php endif; ?>

</section>

</main>

<!-- CREATE ROOM INTERACTION -->
<script>
const createRoomBtn = document.getElementById('createRoomBtn');
const roomForm = document.getElementById('roomForm');
const cancelRoomBtn = document.getElementById('cancelRoomBtn');

createRoomBtn.addEventListener('click', function() {

    roomForm.classList.toggle('show');

    if (roomForm.classList.contains('show')) {
        roomForm.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    }

});

cancelRoomBtn.addEventListener('click', function() {
    roomForm.classList.remove('show');
});
// EDIT ROOM FORM INTERACTION

function toggleEditForm(roomId) {

    const form = document.getElementById('editForm' + roomId);

    form.classList.toggle('show');

    if (form.classList.contains('show')) {

        form.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });

    }

}
</script>

</body>
</html>

