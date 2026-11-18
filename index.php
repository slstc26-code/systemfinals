<?php
session_start();
include "config/database.php";

// Redirect users who are already logged in
if (isset($_SESSION["role"])) {

    if ($_SESSION["role"] === "admin") {
        header("Location: admin/first.php");
    } else {
        header("Location: user/first.php");
    }

    exit;
}

$error = "";
$redirect_url = ""; // Track successful destination for animation

if (isset($_POST["login"])) {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    // Search account using username
    $sql = "SELECT * FROM users
            WHERE username = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $username
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {

            // Verify password
            if (password_verify($password, $user["password"])) {

                // ===================================
                // CHECK EMAIL VERIFICATION
                // ===================================

                if (
                    $user["role"] === "user" &&
                    (int)$user["email_verified"] !== 1
                ) {

                    $error =
                        "Please verify your email address before logging in. Check your inbox for the confirmation link.";

                } else {

                    // ===================================
                    // LOGIN SUCCESS
                    // ===================================

                    session_regenerate_id(true);

                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["fullname"] = $user["fullname"];
                    $_SESSION["role"] = $user["role"];

                    // Determine redirection target for the animation trigger
                    if ($user["role"] === "admin") {
                        $redirect_url = "admin/first.php";
                    } else {
                        $redirect_url = "user/first.php";
                    }

                }

            } else {

                $error = "Invalid username or password.";

            }

        } else {

            $error = "Invalid username or password.";

        }

        mysqli_stmt_close($stmt);

    } else {

        $error = "Unable to process login. Please try again.";

    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Group 1</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Original Google Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root { --purple: #625fc8; --ink: #0f7ad1; }
    * { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: "Poppins", Arial, sans-serif;
      color: white;
      background:
        linear-gradient(rgba(8, 42, 71, 0.88), rgba(44, 168, 168, 0.88)),
        url("https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=2000&q=85")
        center / cover no-repeat fixed;
    }

    /* Bootstrap navbar with styling retained from your original design */
    .site-header {
      width: min(100% - 8.5%, 1400px);
      margin: 0 auto;
      min-height: 80px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
    }

    .brand {
      color: #fff;
      text-decoration: none;
      font-size: 20px;
      font-weight: 700;
      letter-spacing: -.5px;
    }

    .site-nav {
      display: flex;
      align-items: center;
      gap: clamp(22px, 3vw, 48px);
    }

    .site-nav .nav-link {
      color: #f5f5f5;
      text-decoration: none;
      font-size: 15px;
      font-weight: 300;
      padding: 8px 0;
    }

    .site-nav .nav-link.active { border-bottom: 2px solid #fff; }

    .pill {
      border: 0;
      border-radius: 999px;
      background: var(--purple);
      color: white;
      padding: 15px 30px;
      font: 600 11px "Poppins", sans-serif;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: transform .15s ease, background .15s ease;
    }

    .pill:hover { background: #514eb5; color: white; transform: translateY(-1px); }
    .header-cta { padding: 14px 22px; }

    /* Bootstrap grid replaces the original CSS grid */
    .hero-main {
      width: min(100% - 10%, 1138px);
      min-height: calc(100vh - 80px);
      margin: 0 auto;
      padding: 50px 0 90px;
    }

    .intro { padding-top: 10px; }

    h1 {
      font-size: clamp(30px, 3vw, 39px);
      line-height: 1.25;
      margin: 0 0 24px;
      font-weight: 600;
      letter-spacing: -.8px;
    }

    .intro p {
      color: rgba(255,255,255,.88);
      font-size: 16px;
      line-height: 1.8;
      font-weight: 300;
      margin: 0 0 26px;
      max-width: 560px;
    }

    .intro .pill { min-width: 206px; min-height: 52px; }

    /* Bootstrap card and form controls */
    .signup-card {
      background: #fff;
      color: #080808;
      border-radius: 6px;
      padding: 37px 40px 55px;
      width: 100%;
      max-width: 445px;
      box-shadow: 0 12px 40px rgba(0,0,0,.08);
      border: 0;
    }

    h2 { font-size: 23px; font-weight: 300; margin: 0 0 22px; }

    .signup-card .form-control {
      display: block;
      width: 100%;
      height: 49px;
      margin: 0 0 16px;
      border: 1px solid #d7dce2;
      border-radius: 6px;
      background: #fff;
      padding: 0 13px;
      font: 300 14px "Poppins", sans-serif;
      color: #3f4650;
      outline: none;
      box-shadow: none;
      transition: border-color .15s, box-shadow .15s;
    }

    .signup-card .form-control::placeholder { color: #657080; opacity: 1; }

    .signup-card .form-control:focus {
      border-color: var(--purple);
      box-shadow: 0 0 0 3px rgba(98,95,200,.12);
    }

    .signup-card .pill { min-width: 112px; height: 48px; margin-top: 8px; }
    .form-message { color: #315d42; font-size: 13px; margin: 14px 0 0; min-height: 18px; }

    @media (max-width: 760px) {
      .site-header {
        width: min(100% - 36px, 600px);
        flex-wrap: wrap;
        padding: 18px 0;
      }

      .site-nav {
        order: 3;
        width: 100%;
        justify-content: space-between;
        gap: 12px;
      }

      .site-nav .nav-link { font-size: 13px; }
      .header-cta { padding: 12px 16px; }

      .hero-main {
        width: min(100% - 36px, 540px);
        padding: 55px 0 60px;
      }

      .intro { padding-top: 0; }
      .signup-card { max-width: none; padding: 30px 25px 35px; }
    }

    @media (max-width: 390px) {
      .brand { font-size: 18px; }
      .site-nav { gap: 8px; }
      .site-nav .nav-link { font-size: 12px; }
      h1 { font-size: 29px; }
    }

/* MORE INFORMATION SECTION */
.more-info {
    width: 100%;
    padding: 65px 8.5% 35px;
    background: rgba(12, 42, 65, 0.88);
    backdrop-filter: blur(10px);
    color: white;
    margin-top: 30px;
}

.info-heading {
    font-size: 27px;
    font-weight: 600;
    margin-bottom: 15px;
}

.info-description {
    font-size: 14px;
    font-weight: 300;
    line-height: 1.9;
    color: rgba(255,255,255,.78);
}

.info-title {
    font-size: 17px;
    font-weight: 500;
    margin-bottom: 15px;
}

.info-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.info-links li {
    margin-bottom: 10px;
}

.info-links a {
    color: rgba(255,255,255,.75);
    text-decoration: none;
    font-size: 13px;
    transition: .2s;
}

.info-links a:hover {
    color: white;
    padding-left: 5px;
}

.footer-bottom {
    border-top: 1px solid rgba(255,255,255,.18);
    margin-top: 45px;
    padding-top: 22px;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.footer-bottom p {
    font-size: 12px;
    color: rgba(255,255,255,.65);
    margin: 0;
}

@media (max-width: 768px) {
    .more-info {
        padding: 45px 25px 25px;
    }

    .info-column {
        margin-bottom: 30px;
    }

    .footer-bottom {
        flex-direction: column;
        text-align: center;
    }
}
/* Floating transparent scroll-to-bottom button */
.floating-bottom-btn {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: rgba(98, 95, 200, 0.45);   /* transparent purple */
  border: 1px solid rgba(255, 255, 255, 0.25);
  color: white;
  font-size: 22px;
  cursor: pointer;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
  transition: all 0.25s ease;
}

.floating-bottom-btn:hover {
  background: rgba(98, 95, 200, 0.75);
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.floating-bottom-btn:active {
  transform: translateY(0) scale(0.98);
}

@media (max-width: 480px) {
  .floating-bottom-btn {
    transition: all 0.35s ease;
    opacity: 1;
  }
}

    /* ==========================================================
       3D ENVELOPE OPENING PAGE TRANSITION STYLES (FIXED)
       ========================================================== */
    .envelope-transition-overlay {
      position: fixed;
      inset: 0;
      background: rgba(5, 20, 35, 0.95);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      z-index: 999999;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
    }

    .envelope-transition-overlay.active {
      opacity: 1;
      pointer-events: auto;
    }

    .envelope-wrapper {
      position: relative;
      width: 420px;
      height: 300px;
      perspective: 1200px;
    }

    .envelope-pocket {
      position: absolute;
      inset: 0;
      background: #f4f5f7;
      border-radius: 8px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
      z-index: 3;
      overflow: hidden;
      border: 1px solid #d7dce2;
    }

    /* Envelope Flap */
    .envelope-flap {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 150px;
      background: #e4e7ec;
      clip-path: polygon(0 0, 50% 100%, 100% 0);
      transform-origin: top;
      transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 5;
    }

    .envelope-wrapper.open .envelope-flap {
      transform: rotateX(180deg);
      z-index: 1;
    }

    /* Wax Seal */
    .wax-seal {
      position: absolute;
      top: 125px;
      left: 50%;
      transform: translateX(-50%);
      width: 50px;
      height: 50px;
      background: #625fc8;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.3);
      z-index: 6;
      transition: transform 0.3s ease, opacity 0.3s ease;
      border: 2px solid #514eb5;
    }

    .envelope-wrapper.open .wax-seal {
      transform: translateX(-50%) scale(0) rotate(45deg);
      opacity: 0;
    }

    /* Dashboard letter inside */
    .letter-content {
      position: absolute;
      bottom: 10px;
      left: 20px;
      right: 20px;
      height: 240px;
      background: #ffffff;
      border-radius: 6px;
      padding: 25px;
      color: #080808;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      z-index: 2;
      transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
      transform: translateY(0);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
    }

    .envelope-wrapper.open .letter-content {
      transform: translateY(-130px);
      z-index: 4;
    }

    /* When expanding, make the entire overlay act as the full-screen background page */
    .envelope-transition-overlay.expand {
      background: linear-gradient(rgba(8, 42, 71, 0.95), rgba(44, 168, 168, 0.95)),
                  url("https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=2000&q=85") center / cover no-repeat fixed;
    }

    .envelope-wrapper.expand {
      animation: expandFullScreen 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }

    @keyframes expandFullScreen {
      0% {
        width: 420px;
        height: 300px;
      }
      100% {
        width: 100vw;
        height: 100vh;
        max-width: none;
      }
    }

    .envelope-wrapper.expand .envelope-pocket,
    .envelope-wrapper.expand .envelope-flap,
    .envelope-wrapper.expand .wax-seal {
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
    }

    .envelope-wrapper.expand .letter-content {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      border-radius: 0;
      transform: translateY(0) !important;
      background: transparent;
      color: white;
      box-shadow: none;
    }
    /* ===== CONTACT US HOVER CARD ===== */
.contact-hover-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
}

/* Invisible bridge so the mouse can travel from button → card without losing hover */
.contact-hover-wrapper::after {
  content: "";
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  height: 18px; /* covers the gap between button and card */
}

.contact-hover-card {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  width: 280px;
  background: #ffffff;
  color: #1a1a1a;
  border-radius: 12px;
  padding: 20px 22px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.18), 0 0 0 1px rgba(0, 0, 0, 0.04);
  opacity: 0;
  visibility: hidden;
  transform: translateY(8px) scale(0.97);
  transition: opacity 0.22s ease, transform 0.22s ease, visibility 0.22s;
  z-index: 1000;
  pointer-events: none;
  font-family: "Poppins", Arial, sans-serif;
}

/* Small arrow pointing up to the button */
.contact-hover-card::before {
  content: "";
  position: absolute;
  top: -7px;
  right: 28px;
  width: 14px;
  height: 14px;
  background: #ffffff;
  transform: rotate(45deg);
  box-shadow: -2px -2px 4px rgba(0, 0, 0, 0.04);
}

.contact-hover-wrapper:hover .contact-hover-card,
.contact-hover-wrapper:focus-within .contact-hover-card {
  opacity: 1;
  visibility: visible;
  transform: translateY(0) scale(1);
  pointer-events: auto;
}

.contact-hover-card h4 {
  margin: 0 0 12px;
  font-size: 15px;
  font-weight: 600;
  color: #625fc8;
  letter-spacing: -0.2px;
}

.contact-hover-card .contact-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 12px;
  font-size: 13px;
  line-height: 1.45;
  color: #333;
}

.contact-hover-card .contact-item:last-child {
  margin-bottom: 0;
}

.contact-hover-card .contact-icon {
  flex-shrink: 0;
  width: 18px;
  height: 18px;
  margin-top: 1px;
  color: #625fc8;
}

.contact-hover-card a {
  color: #0f7ad1;
  text-decoration: none;
  font-weight: 500;
}

.contact-hover-card a:hover {
  text-decoration: underline;
}

.contact-hover-card .contact-note {
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px solid #eee;
  font-size: 12px;
  color: #666;
  line-height: 1.5;
}

@media (max-width: 760px) {
  .contact-hover-card {
    right: auto;
    left: 50%;
    transform: translateX(-50%) translateY(8px) scale(0.97);
    width: min(280px, 90vw);
  }
  .contact-hover-card::before {
    right: auto;
    left: 50%;
    margin-left: -7px;
  }
  .contact-hover-wrapper:hover .contact-hover-card,
  .contact-hover-wrapper:focus-within .contact-hover-card {
    transform: translateX(-50%) translateY(0) scale(1);
  }
}
/* ===== END CONTACT US HOVER CARD ===== */
  </style>
</head>
<body>
  <!-- Header/navigation retained; Bootstrap utilities provide layout -->
  <header class="site-header navbar navbar-expand">
    <a class="brand navbar-brand m-0 p-0" href="index.php" aria-label="Researva">Researva</a>

    <nav class="site-nav navbar-nav flex-row" aria-label="Main navigation">
      <a class="nav-link" href="#home">Getting Started</a>
      <a class="nav-link" href="inquire.php">FAQs</a>
    </nav>

    <!-- CONTACT US button + hover card (edit the content inside .contact-hover-card) -->
<div class="contact-hover-wrapper">
  <a class="pill header-cta btn" href="https://www.facebook.com/deanpierre.01" target="_blank" rel="noopener">CONTACT US</a>

  <div class="contact-hover-card" role="tooltip">
    <h4>Get in touch</h4>

    <!-- ===== EDITABLE CONTACT INFO START ===== -->
    <div class="contact-item">
      <svg class="contact-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
        <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104L5.803 8.852 0 4.697ZM6.761 9.77l-6.57 5.037A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.193l-6.57-5.037L8 10.586l-1.239-.816Z"/>
        <path d="M16 4.697l-5.803 4.155L16 11.801V4.697Z"/>
      </svg>
      <div>
        <strong>Email</strong><br>
        <a href="mailto:slstc26@gmail.com">slstc26@gmail.com</a>
      </div>
    </div>

    <div class="contact-item">
      <svg class="contact-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
        <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/>
      </svg>
      <div>
        <strong>Phone</strong><br>
        +63 947 221 7893
      </div>
    </div>

    <div class="contact-item">
      <svg class="contact-icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
        <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.049c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z"/>
      </svg>
      <div>
        <strong>Facebook</strong><br>
        <a href="https://www.facebook.com/deanpierre.01" target="_blank" rel="noopener">@deanpierre.01</a>
      </div>
    </div>

    <div class="contact-note">
      We usually reply within 24 hours on weekdays.
    </div>
    <!-- ===== EDITABLE CONTACT INFO END ===== -->
  </div>
</div>
  
  </header>

  <!-- Bootstrap responsive row/columns -->
  <main class="hero-main row align-items-center g-0" id="home">
    <section class="intro col-md-7 pe-md-4" aria-labelledby="hero-title">
      <h1 id="hero-title">Ease of access with us.</h1>
      <p>Monitor your progress<br class="desktop-break">End it with success.</p>
      <a class="pill btn" href="register.php">Create an account</a>
    </section>

    <section class="col-md-5 d-flex justify-content-md-end mt-4 mt-md-0" id="signup" aria-labelledby="signup-title">
      <div class="signup-card card">
        <h2 id="login">Log in</h2>
<form method="POST" action="" id="loginForm">
    <input
        class="form-control"
        type="text"
        name="username"
        placeholder="Username"
        required
    >

    <div class="position-relative mb-3">
    <input
        class="form-control"
        type="password"
        name="password"
        id="password"
        placeholder="Password"
        required
        style="padding-right: 45px;"
    >

    <!-- Eye Icon Button -->
    <button type="button" 
            id="togglePassword"
            class="btn btn-link position-absolute end-0 top-50 translate-middle-y pe-3"
            style="text-decoration: none; color: #657080; z-index: 5;">
        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <!-- Eye Open (default) -->
            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
            <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
        </svg>
    </button>
</div>

    <button class="pill btn" type="submit" name="login">
        Enter
    </button>

    <?php if (!empty($error)): ?>
        <p class="text-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>
</form>

      </div>
    </section>
  </main>
  
<!-- MORE INFORMATION BANNER / FOOTER -->
<section class="more-info" id="more-information">
    <div class="container-fluid">

        <div class="row g-4">

            <!-- About -->
            <div class="col-lg-4 col-md-6 info-column">
                <h3 class="info-heading">About Researva</h3>

                <p class="info-description">
                    Researva is a platform designed to provide
                    users with an easier way to organize, monitor,
                    and manage their progress. Our goal is to
                    make every process more accessible,
                    convenient, and efficient.
                </p>
            </div>

            <!-- Getting Started -->
            <div class="col-lg-3 col-md-6 info-column">
                <h5 class="info-title">Getting Started</h5>

                <ul class="info-links">
                    <li><a href="register.php">Create an Account</a></li>
                    <li><a href="#login">Log In</a></li>
                    <li><a href="inquire.php">Submit an Inquiry</a></li>
                    <li><a href="#home">Back to Home</a></li>
                </ul>
            </div>

            <!-- More Information -->
<div class="col-lg-2 col-md-6 info-column">
    <h5 class="info-title">More Information</h5>

    <ul class="info-links">
        <li>
            <a href="#" data-bs-toggle="modal" data-bs-target="#purposeModal">Our Purpose</a>
        </li>
        <li>
            <a href="#" data-bs-toggle="modal" data-bs-target="#howItWorksModal">How It Works</a>
        </li>
        <li>
            <a href="#" data-bs-toggle="modal" data-bs-target="#featuresModal">Our Features</a>
        </li>
    </ul>
</div>

            <!-- Contact -->
            <div class="col-lg-3 col-md-6 info-column">
                <h5 class="info-title">Contact Us</h5>

                <p class="info-description">
                    Have questions or need assistance?
                    Feel free to reach out to us.
                </p>

                <ul class="info-links">
                    <li>
                        <a href="https://www.facebook.com/deanpierre.01"
                           target="_blank">
                            Facebook
                        </a>
                    </li>
                    <li>
                        <a href="inquire.php">Send an Inquiry</a>
                    </li>
                </ul>
            </div>

        </div>

        <!-- Bottom Footer -->
        <div class="footer-bottom">
            <p>© 2026 Researva. All Rights Reserved.</p>

            <p>Designed to make progress easier and more accessible.</p>
        </div>

    </div>
</section>
<!-- END MORE INFORMATION -->


  <!-- Bootstrap JavaScript bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Floating Scroll to Bottom Button -->
  <button class="floating-bottom-btn" id="scrollToBottom" title="Go to bottom" aria-label="Scroll to bottom">
    ↓
  </button>

  <script>
  const btn = document.getElementById('scrollToBottom');
  let isScrolling = false; // prevents multiple clicks during animation

  // ========== Dramatic slow scroll ==========
  function dramaticScrollToBottom() {
    if (isScrolling) return; // already scrolling → ignore

    const start = window.scrollY;
    const end = document.documentElement.scrollHeight - window.innerHeight;
    const distance = end - start;

    // Already at (or extremely close to) bottom → do nothing
    if (distance < 30) return;

    isScrolling = true;
    const duration = 2000; // slower = higher number
    const startTime = performance.now();

    function easeInOutCubic(t) {
      return t < 0.5
        ? 4 * t * t * t
        : 1 - Math.pow(-2 * t + 2, 3) / 2;
    }

    function animate(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const eased = easeInOutCubic(progress);

      window.scrollTo(0, start + distance * eased);

      if (progress < 1) {
        requestAnimationFrame(animate);
      } else {
        isScrolling = false; // animation finished
      }
    }

    requestAnimationFrame(animate);
  }

  // ========== Fade button when near bottom ==========
  function updateButtonVisibility() {
    const scrollPosition = window.scrollY + window.innerHeight;
    const pageHeight = document.documentElement.scrollHeight;
    const nearBottom = scrollPosition >= pageHeight - 120;

    if (nearBottom) {
      btn.style.opacity = '0';
      btn.style.pointerEvents = 'none';
      btn.style.transform = 'scale(0.8)';
    } else {
      btn.style.opacity = '1';
      btn.style.pointerEvents = 'auto';
      btn.style.transform = 'scale(1)';
    }
  }

  // Listeners
  btn.addEventListener('click', dramaticScrollToBottom);
  window.addEventListener('scroll', updateButtonVisibility);
  window.addEventListener('resize', updateButtonVisibility);

  // Run once on page load
  updateButtonVisibility();
</script>

<!-- ==================== MODALS ==================== -->

<!-- Our Purpose Modal -->
<div class="modal fade" id="purposeModal" tabindex="-1" aria-labelledby="purposeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header" style="background: linear-gradient(135deg, #625fc8, #0f7ad1); color: white;">
        <h5 class="modal-title fw-semibold" id="purposeModalLabel">Our Purpose</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" style="font-family: 'Poppins', sans-serif; line-height: 1.7; color: #333;">
        <p>
          At <strong>Researva</strong>, our purpose is to simplify the way people organize, track, and complete their goals. 
          We believe that progress should feel clear, motivating, and accessible — not overwhelming.
        </p>
        <p>
          Whether you're a student, researcher, or professional, Researva is designed to help you stay focused and turn your plans into real results.
        </p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- How It Works Modal -->
<div class="modal fade" id="howItWorksModal" tabindex="-1" aria-labelledby="howItWorksModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header" style="background: linear-gradient(135deg, #625fc8, #0f7ad1); color: white;">
        <h5 class="modal-title fw-semibold" id="howItWorksModalLabel">How It Works</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" style="font-family: 'Poppins', sans-serif; line-height: 1.7; color: #333;">
        <ol class="ps-3">
          <li class="mb-3">
            <strong>Create an Account</strong><br>
            Sign up in just a few steps and verify your email.
          </li>
          <li class="mb-3">
            <strong>Set Your Goals</strong><br>
            Add the tasks, research, or projects you want to track.
          </li>
          <li class="mb-3">
            <strong>Monitor Progress</strong><br>
            Use the dashboard to see updates, milestones, and completion status.
          </li>
          <li>
            <strong>Achieve Success</strong><br>
            Stay organized and finish what you start with clear visibility.
          </li>
        </ol>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Our Features Modal -->
<div class="modal fade" id="featuresModal" tabindex="-1" aria-labelledby="featuresModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header" style="background: linear-gradient(135deg, #625fc8, #0f7ad1); color: white;">
        <h5 class="modal-title fw-semibold" id="featuresModalLabel">Our Features</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" style="font-family: 'Poppins', sans-serif; line-height: 1.7; color: #333;">
        <ul class="list-unstyled">
          <li class="mb-3">
            <strong>✔ Progress Tracking</strong><br>
            Easily monitor the status of all your tasks and goals in one place.
          </li>
          <li class="mb-3">
            <strong>✔ Clean & Simple Dashboard</strong><br>
            A user-friendly interface designed for clarity and focus.
          </li>
          <li class="mb-3">
            <strong>✔ Secure Login System</strong><br>
            Your account is protected with modern password security and email verification.
          </li>
          <li class="mb-3">
            <strong>✔ Inquiry Support</strong><br>
            Need help? Submit questions directly through the platform.
          </li>
          <li>
            <strong>✔ Mobile Responsive</strong><br>
            Access Researva smoothly on any device.
          </li>
        </ul>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- 3D Envelope Transition Overlay Structure -->
<div class="envelope-transition-overlay" id="envelopeOverlay">
  <div class="envelope-wrapper" id="envelopeWrapper">
    <div class="envelope-pocket"></div>
    <div class="envelope-flap"></div>
    <div class="wax-seal">Open</div>
    <div class="letter-content">
      <h3 class="fw-bold mb-2">Welcome Back!</h3>
      <p class="small mb-0" style="opacity: 0.85;">Opening your session...</p>
    </div>
  </div>
</div>

<script>
  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');
  const eyeIcon = document.getElementById('eyeIcon');

  togglePassword.addEventListener('click', function () {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);

    if (type === 'text') {
      eyeIcon.innerHTML = `
        <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.94 5.94 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z"/>
        <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.288.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/>
        <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 6.884-12-12 .708-.708 12 12-.708.708z"/>
      `;
    } else {
      eyeIcon.innerHTML = `
        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
      `;
    }
  });

  // Trigger Envelope Animation if PHP signals a valid login
  const serverRedirectUrl = "<?php echo $redirect_url; ?>";
  if (serverRedirectUrl !== "") {
    const overlay = document.getElementById('envelopeOverlay');
    const wrapper = document.getElementById('envelopeWrapper');

    overlay.classList.add('active');
    
    // Choreography sequence
    setTimeout(() => { wrapper.classList.add('open'); }, 300);
    setTimeout(() => { 
      wrapper.classList.add('expand'); 
      overlay.classList.add('expand');
    }, 900);
    setTimeout(() => { window.location.href = serverRedirectUrl; }, 1600);
  }
</script>
</body>
</html>