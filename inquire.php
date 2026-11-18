<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Researva — Inquiry Flowchart</title>

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

    .hero-main {
      width: min(100% - 10%, 1138px);
      min-height: calc(100vh - 80px);
      margin: 0 auto;
      padding: 40px 0 90px;
    }

    .page-title {
      font-size: clamp(28px, 3vw, 36px);
      font-weight: 600;
      letter-spacing: -.8px;
      margin: 0 0 12px;
      text-align: center;
    }
    .page-sub {
      color: rgba(255,255,255,.88);
      font-size: 16px;
      font-weight: 300;
      text-align: center;
      margin: 0 0 48px;
    }
/* ===== Image + Info Holder Layout ===== */
.image-holder {
  position: relative;
  border-radius: 16px;
  padding: 36px 40px;
  margin-top: 80px;
  
}

.image-holder img {
  display: block;
  transition: transform .35s ease;
}

.image-holder:hover img {
  transform: scale(1.03);
}

.image-caption {
  font-weight: 300;
  letter-spacing: 0.3px;
}

.info-holder {
  background: rgba(255, 255, 255, 0.97);
  color: #0a2540;
  border-radius: 16px;
  padding: 36px 40px;
  height: 100%;
}

.info-title {
  font-size: clamp(24px, 2.5vw, 30px);
  font-weight: 600;
  letter-spacing: -0.6px;
  margin: 0 0 12px;
  color: #0a2540;
}

.info-subtitle {
  font-size: 15.5px;
  font-weight: 400;
  color: #3a4a5c;
  line-height: 1.55;
  margin-bottom: 28px;
}

.info-block {
  margin-bottom: 26px;
}

.info-heading {
  font-size: 15px;
  font-weight: 600;
  color: #625fc8;
  margin: 0 0 10px;
  letter-spacing: -0.2px;
}

.info-list {
  margin: 0;
  padding-left: 18px;
  color: #2c3a4a;
  font-size: 14.5px;
  line-height: 1.65;
}

.info-list li {
  margin-bottom: 6px;
}

.info-text {
  font-size: 14.5px;
  line-height: 1.65;
  color: #2c3a4a;
  margin: 0;
}

/* Responsive tweaks */
@media (max-width: 991.98px) {
  .info-holder {
    padding: 28px 24px;
  }
  .image-holder {
    max-width: 480px;
    margin: 0 auto;
  }
}
  </style>
</head>
<body>
  <header class="site-header navbar navbar-expand">
    <a class="brand navbar-brand m-0 p-0" href="index.php" aria-label="Researva">Researva</a>

    <nav class="site-nav navbar-nav flex-row" aria-label="Main navigation">
      <a class="nav-link" href="index.php">Getting Started</a>
      <a class="nav-link" href="inquire.php">FAQs</a>
    </nav>

    <a class="pill header-cta btn" href="index.php">Sign In</a>
  </header>
  <!-- ========== MAIN CONTENT: Image (left) + Editable Info (right) ========== -->
  <main class="hero-main">
    <div class="container-fluid px-0">
      <div class="row g-4 g-lg-5 align-items-start justify-content-center">

        <!-- LEFT: Image Holder -->
        <div class="col-12 col-lg-5">
          <div class="image-holder">
            <!-- Replace the src below with your own image -->
            <img 
              src="images/faqs1.png" 
              alt="Researva visual"
              class="img-fluid"
              style="width:100%; height:auto; object-fit:cover; max-height:520px;"
            >
            <!-- Optional caption under the image (easy to edit) -->
            <p class="image-caption mt-3 mb-0 text-center text-white-50 small">
              <!-- EDIT THIS CAPTION -->
             
            </p>
          </div>
        </div>

        <!-- RIGHT: Editable Information Holder -->
        <div class="col-12 col-lg-6">
          <div class="info-holder">
            
            <!-- ===== EDITABLE TITLE ===== -->
            <h2 class="info-title">Hi! I am Filo.</h2>

            <!-- ===== EDITABLE SUBTITLE / INTRO ===== -->
            <p class="info-subtitle">
              Your trusted partner for research guidance, academic support, and inquiry assistance.
            </p>

            <!-- ===== EDITABLE CONTENT BLOCKS ===== -->
            <div class="info-block">
              <h5 class="info-heading">How can I log in?</h5>
              <ul class="info-list">
                <li>Click the "Create an account" on the log In page</li>
                <li>HEADS UP! make sure to use an active email account</li>
                <li>Fill out the needed info</li>
                <li>Verify your registration through email and dont forget to click the link</li>
                <li>Get back, Log in then get FILO with me!</li>
              </ul>
            </div>

            <div class="info-block">
              <h5 class="info-heading">Can i use same email for multiple account?</h5>
              <p class="info-text">
                DONT YOU DAREEEEEEEE (╯°□°）╯︵ ┻━┻.
              </p>
            </div>

            <!-- Optional call-to-action button (matches your .pill style) -->
            <div class="mt-4">
              <a href="register.php" class="pill">Try creating one</a>
            </div>

          </div>
        </div>

      </div>
    </div>
  </main>
  

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>