<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Researva</title>

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
  <header class="site-header navbar navbar-expand">
    <a class="brand navbar-brand m-0 p-0" href="first.php" aria-label="Researva">Researva</a>

    <nav class="site-nav navbar-nav flex-row" aria-label="Main navigation">
      <a class="nav-link" href="first.php">Home</a>
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
        <a href="mailto:support@researva.com">slstc26@gmail.com</a>
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

  
  </main>
      <!-- ========== MAIN CONTENT: Image (left) + Editable Info (right) ========== -->
  <main class="hero-main">
    <div class="container-fluid px-0">
      <div class="row g-4 g-lg-5 align-items-start justify-content-center">

        <!-- LEFT: Image Holder -->
        <div class="col-12 col-lg-5">
          <div class="image-holder">
            <!-- Replace the src below with your own image -->
            <img 
              src="../images/faqs1.png" 
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
              <h5 class="info-heading">How to upload?</h5>
              <ul class="info-list">
                <li>There's a button designated for upload</li>
                <li>BUT make sure to choose your desired file first!</li>
                <li>2 steps only for your drive and room</li>
                <li>Be ready, and get FILO with me!</li>
              </ul>
            </div>

            <div class="info-block">
              <h5 class="info-heading">Can i manage my files?</h5>
              <p class="info-text">
                You can only manage your drive but not the files you upload in a room.
              </p>
              <p class="info-text">
                So make sure you double check your files before submitting to your teacher!
              </p>
              <p class="info-text">
                <strong>Just imagine an exam paper. Once submitted you can no longer get it from your teacher.</strong>
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