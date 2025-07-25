<?php
// ... existing code ...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Bitcoin & Giftcard Trading Platform</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/fav.png" />
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>
    <style>
      body { background: linear-gradient(252deg, #1a938a 0%, rgba(26, 147, 138, 0) 100.44%); min-height: 100vh; }
      .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 8px;
        position: relative;
      }
      .giftcard-particles {
        position: absolute;
        inset: 0;
        width: 100vw;
        height: 100vh;
        pointer-events: none;
        z-index: 0;
        overflow: hidden;
      }
      .giftcard-particle {
        position: absolute;
        font-size: 2.2rem;
        color: #ffbf3f;
        opacity: 0.18;
        animation: floatGift 8s linear infinite;
      }
      .giftcard-particle.green { color: #1a938a; opacity: 0.13; }
      @keyframes floatGift {
        0% { transform: translateY(0) scale(1) rotate(0deg); opacity: 0.18; }
        50% { opacity: 0.28; }
        100% { transform: translateY(-60px) scale(1.1) rotate(20deg); opacity: 0.18; }
      }
      .glass-card {
        background: linear-gradient(135deg, #ffbf3f 0%, #1a938a 100%);
        box-shadow: 0 8px 32px 0 rgba(26, 147, 138, 0.18);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: 22px;
        border: 1px solid rgba(255,255,255,0.18);
        padding: 48px 38px 36px;
        max-width: 420px;
        width: 100%;
        z-index: 1;
        animation: fadeInUp 0.8s cubic-bezier(0.23, 1, 0.32, 1);
      }
      .logo-container {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
      }
      .logo {
        width: 64px;
        height: 64px;
        object-fit: contain;
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(35,35,76,0.18);
        background: #fff;
      }
      .text {
        font-size: 2rem;
        color: #19376d;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-align: center;
      }
      .subtitle {
        color: #1a938a;
        font-size: 1.08rem;
        margin-bottom: 22px;
        text-align: center;
      }
      form {
        margin-top: 18px;
      }
      .field-group {
        display: flex;
        flex-direction: column;
        gap: 16px;
      }
      .field {
        display: flex;
        align-items: flex-end;
        background: rgba(51,51,51,0.22);
        border-radius: 10px;
        padding: 0 0 0 12px;
        position: relative;
        transition: box-shadow 0.2s;
      }
      .field:focus-within {
        box-shadow: 0 0 0 2px #4f46e5;
      }
      .field .fas {
        color: #868686;
        font-size: 20px;
        min-width: 36px;
        text-align: center;
        margin-bottom: 10px;
      }
      .input-wrapper {
        flex: 1;
        position: relative;
        display: flex;
        flex-direction: column;
      }
      .input-wrapper input {
        background: #111 !important;
        color: #fff !important;
        border: none;
        border-bottom: 2px solid #444;
        font-size: 1rem;
        padding: 18px 10px 8px 10px;
        outline: none;
        border-radius: 0;
        transition: border-color 0.2s;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
      }
      .input-wrapper input::placeholder {
        color: #fff !important;
        opacity: 1;
      }
      .input-wrapper input:-webkit-autofill {
        -webkit-text-fill-color: #fff !important;
        box-shadow: 0 0 0 1000px #111 inset !important;
      }
      .input-wrapper input:-moz-autofill {
        color: #fff !important;
        background: #111 !important;
      }
      .input-wrapper label {
        position: absolute;
        left: 10px;
        top: 18px;
        color: #fff;
        font-size: 1rem;
        pointer-events: none;
        transition: 0.2s cubic-bezier(0.4,0,0.2,1);
      }
      .input-wrapper input:focus + label,
      .input-wrapper input:not(:placeholder-shown) + label {
        top: -8px;
        left: 6px;
        font-size: 0.85rem;
        color: #fff;
        background: rgba(27,27,43,0.92);
        padding: 0 4px;
        border-radius: 4px;
      }
      .input-wrapper input:-webkit-autofill + label {
        top: -8px;
        left: 6px;
        font-size: 0.85rem;
        color: #fff;
        background: rgba(27,27,43,0.92);
        padding: 0 4px;
        border-radius: 4px;
      }
      .error-message {
        color: #ff6b6b;
        font-size: 0.85rem;
        margin-top: 2px;
        min-height: 18px;
      }
      .forgot-link {
        color: #4f46e5;
        text-decoration: underline;
        font-size: 0.95rem;
        float: right;
        margin-bottom: 1.5rem;
      }
      .forgot-link:hover {
        color: #6a11cb;
      }
      .btn-primary {
        width: 100%;
        background: linear-gradient(90deg, #4f46e5 0%, #6a11cb 100%);
        color: #fff;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        padding: 14px 0;
        font-size: 1.1rem;
        margin-top: 8px;
        cursor: pointer;
        transition: background 0.2s, box-shadow 0.2s;
        box-shadow: 0 2px 8px rgba(79,70,229,0.08);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
      }
      .btn-primary:disabled {
        opacity: 0.7;
        cursor: not-allowed;
      }
      .btn-primary .spinner {
        width: 18px;
        height: 18px;
        border: 3px solid #fff;
        border-top: 3px solid #4f46e5;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        display: none;
      }
      .btn-primary.loading .spinner {
        display: inline-block;
      }
      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
      .link {
        text-align: center;
        margin-top: 8px;
        color: #fff;
      }
      .link a {
        color: #4f46e5;
        text-decoration: underline;
        font-weight: 500;
      }
      .link a:hover {
        color: #6a11cb;
      }
      @media (max-width: 600px) {
        .glass-card {
          padding: 32px 8px 24px;
          max-width: 98vw;
        }
        .logo {
          width: 48px;
          height: 48px;
        }
        .text {
          font-size: 1.4rem;
        }
      }
      .modal-bg {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.6);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
      }
      .modal-bg.active { display: flex; }
      .modal-content {
        background: rgba(27, 27, 43, 0.98);
        border-radius: 22px;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        padding: 40px 32px;
        max-width: 350px;
        width: 100%;
        text-align: center;
        color: #fff;
        animation: fadeInUp 0.5s cubic-bezier(0.23, 1, 0.32, 1);
      }
      .modal-content h2 {
        font-size: 2rem;
        margin-bottom: 12px;
        color: #4f46e5;
      }
      .modal-content p {
        font-size: 1.1rem;
        margin-bottom: 0;
      }
    </style>
</head>
<body>
  <div class="login-container">
    <div class="giftcard-particles">
      <i class="fas fa-gift giftcard-particle" style="top:10%;left:12%;animation-delay:0s;"></i>
      <i class="fas fa-gift giftcard-particle green" style="top:30%;left:80%;font-size:2.7rem;animation-delay:2s;"></i>
      <i class="fas fa-gift giftcard-particle" style="top:60%;left:20%;font-size:1.7rem;animation-delay:1.5s;"></i>
      <i class="fas fa-gift giftcard-particle green" style="top:75%;left:60%;font-size:2.3rem;animation-delay:3.5s;"></i>
      <i class="fas fa-gift giftcard-particle" style="top:50%;left:50%;font-size:2.1rem;animation-delay:4.5s;"></i>
    </div>
    <div class="glass-card">
      <div class="logo-container">
        <img src="images/logo.png" alt="Bitcoin Giftcards Logo" class="logo">
      </div>
      <div class="text">Sign in to your account</div>
      <div class="subtitle">Access your dashboard and manage your trades securely.</div>
      <form method="post" action="login.php" autocomplete="on" id="loginForm">
        <div class="field-group">
          <div class="field">
            <div class="fas fa-envelope"></div>
            <div class="input-wrapper">
              <input type="email" id="email" name="email" required placeholder=" ">
              <label for="email">Email Address</label>
              <span class="error-message" id="email-error"></span>
            </div>
          </div>
          <div class="field">
            <div class="fas fa-lock"></div>
            <div class="input-wrapper">
              <input type="password" id="password" name="password" required placeholder=" ">
              <label for="password">Password</label>
              <span class="error-message" id="password-error"></span>
            </div>
          </div>
        </div>
        <a href="reset_password.php" class="forgot-link">Forgot Password?</a>
        <button type="submit" class="btn-primary" id="loginBtn">
          <span>Login</span>
          <span class="spinner"></span>
        </button>
        <div class="link">
          Don't have an account? <a href="signup.php">Sign up</a>
        </div>
      </form>
      <div class="back-home-btn" style="text-align:center; margin-top: 32px;">
        <a href="index.html" class="btn-home" style="display:inline-block;padding:12px 32px;font-size:1.1rem;font-weight:600;border:none;border-radius:8px;background:linear-gradient(90deg,#ffbf3f 0%,#1a938a 100%);color:#19376d;text-decoration:none;box-shadow:0 2px 8px rgba(26,147,138,0.08);transition:background 0.2s;">&larr; Back Home</a>
      </div>
    </div>
  </div>
  <!-- Modal for congratulation -->
  <div class="modal-bg" id="loginCongratsModal">
    <div class="modal-content">
      <h2>Welcome!</h2>
      <p>Login successful.<br>Redirecting to your dashboard...</p>
    </div>
  </div>
  <script>
    // Button loading spinner UX
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const loginCongratsModal = document.getElementById('loginCongratsModal');
    loginForm.addEventListener('submit', function(e) {
      e.preventDefault();
      loginBtn.classList.add('loading');
      loginBtn.disabled = true;
      // Gather form data
      const formData = new FormData(loginForm);
      fetch('auth_login.php', {
        method: 'POST',
        body: formData
      })
      .then(async res => {
        const text = await res.text();
        console.log('Raw response:', text); // Debug log
        try {
          return JSON.parse(text);
        } catch (e) {
          throw new Error('Invalid JSON: ' + text);
        }
      })
      .then(data => {
        loginBtn.classList.remove('loading');
        loginBtn.disabled = false;
        if (data.success) {
          loginCongratsModal.classList.add('active');
          setTimeout(() => {
            window.location.href = 'dashboard.php';
          }, 2000);
        } else {
          // Show error message (reuse error-message span or alert)
          let errorSpan = document.querySelector('.error-message#form-error');
          if (!errorSpan) {
            errorSpan = document.createElement('span');
            errorSpan.className = 'error-message';
            errorSpan.id = 'form-error';
            loginForm.appendChild(errorSpan);
          }
          errorSpan.textContent = data.message;
          errorSpan.style.display = 'block';
        }
      })
      .catch((err) => {
        loginBtn.classList.remove('loading');
        loginBtn.disabled = false;
        let errorSpan = document.querySelector('.error-message#form-error');
        if (!errorSpan) {
          errorSpan = document.createElement('span');
          errorSpan.className = 'error-message';
          errorSpan.id = 'form-error';
          loginForm.appendChild(errorSpan);
        }
        errorSpan.textContent = err.message || 'An error occurred. Please try again.';
        errorSpan.style.display = 'block';
      });
    });
  </script>
</body>
</html> 