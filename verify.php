
<?php

session_start();
include "config/database.php";

$message = "";
$status = "danger";

if (isset($_GET['token'])) {

    $token = $_GET['token'];

    // Hash the received token
    $hashedToken = hash('sha256', $token);

    // Find matching account
    $sql = "SELECT id FROM users
            WHERE verification_token = ?
            AND email_verified = 0
            AND token_expires >= NOW()";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $hashedToken
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {

            // Activate account
            $update = "UPDATE users
                       SET email_verified = 1,
                           verification_token = NULL,
                           token_expires = NULL
                       WHERE id = ?";

            $updateStmt = mysqli_prepare($conn, $update);

            mysqli_stmt_bind_param(
                $updateStmt,
                "i",
                $user['id']
            );

            if (mysqli_stmt_execute($updateStmt)) {

                $message =
                    "Your email has been successfully verified! You can now log in.";

                $status = "success";

            } else {

                $message =
                    "Unable to activate your account. Please try again.";

            }

            mysqli_stmt_close($updateStmt);

        } else {

            $message =
                "Invalid or expired verification link.";

        }

        mysqli_stmt_close($stmt);

    } else {

        $message = "Database error. Please try again.";

    }

} else {

    $message = "No verification token was provided.";

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Researva - Email Verification</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center
                align-items-center min-vh-100">

        <div class="card shadow-sm text-center"
             style="max-width:450px;width:100%;">

            <div class="card-body p-5">

                <h2 class="mb-3">Researva</h2>

                <div class="alert alert-<?php echo $status; ?>">

                    <?php echo htmlspecialchars($message); ?>

                </div>

                <a href="index.php"
                   class="btn btn-primary w-100">

                    GO TO LOGIN

                </a>

            </div>

        </div>

    </div>

</body>
</html>
