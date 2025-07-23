<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign Up | Giftcard & Bitcoin Trading</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/css/intlTelInput.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <style>
    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #0a174e 0%, #19376d 100%);
      position: relative;
      overflow-x: hidden;
    }
    #signup-particles {
      position: fixed;
      inset: 0;
      width: 100vw;
      height: 100vh;
      z-index: 0;
      pointer-events: none;
      display: block;
    }
    .modal-signup .modal-content {
      background: #fff;
      border-radius: 1.5rem;
      box-shadow: 0 8px 40px rgba(10,23,78,0.18);
      max-width: 410px;
      margin: 0 auto;
      z-index: 1;
      position: relative;
      padding: 1.5rem 1.2rem 1.2rem 1.2rem;
    }
    .modal-signup .btn-gradient {
      background: linear-gradient(90deg, #19376d 0%, #0a174e 100%);
      color: #fff;
      font-weight: 600;
      border: none;
    }
    .modal-signup .btn-gradient:hover {
      background: linear-gradient(90deg, #0a174e 0%, #19376d 100%);
      color: #fff;
    }
    .modal-signup .form-label {
      color: #19376d;
      font-weight: 500;
    }
    .modal-signup .form-control:focus {
      border-color: #19376d;
      box-shadow: 0 0 0 0.2rem rgba(25,55,109,0.08);
    }
    .modal-signup .progress-bar {
      background: #19376d;
    }
    .modal-signup .input-group .btn {
      border-radius: 0 0.5rem 0.5rem 0;
    }
    .modal-signup .form-text, .modal-signup .invalid-feedback {
      color: #19376d !important;
    }
    .modal-signup .text-primary, .modal-signup a.text-primary {
      color: #19376d !important;
    }
    .modal-signup .form-select {
      color: #19376d;
    }
    .modal-signup .modal-header {
      border-bottom: none;
      padding-bottom: 0;
    }
    .modal-signup .modal-title {
      color: #19376d;
      font-weight: 700;
    }
    @media (max-width: 600px) {
      .modal-signup .modal-content { padding: 0.7rem 0.2rem; border-radius: 1rem; }
    }
  </style>
</head>
<body>
  <canvas id="signup-particles"></canvas>
  <div class="modal fade show" id="signupModal" tabindex="-1" aria-labelledby="signupModalLabel" aria-modal="true" role="dialog" style="display:block;background:rgba(10,23,78,0.18);">
    <div class="modal-dialog modal-dialog-centered modal-signup">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="signupModalLabel">Sign Up</h5>
          <a href="index.html" class="btn-close" aria-label="Close"></a>
        </div>
        <div class="modal-body">
          <form method="post" action="signup.php" id="signupForm">
            <div class="text-center mb-3">
              <img src="images/logo.png" alt="Logo" style="height:40px;">
              <p class="text-muted mb-0">Create your account</p>
            </div>
            <div class="mb-2">
              <label for="name" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="name" name="name" required />
            </div>
            <div class="mb-2">
              <label for="username" class="form-label">Username</label>
              <input type="text" class="form-control" id="username" name="username" required />
              <div class="invalid-feedback" id="usernameFeedback">Username must be at least 3 characters and contain only letters, numbers, and underscores.</div>
            </div>
            <div class="mb-2">
              <label for="email" class="form-label">Email address</label>
              <input type="email" class="form-control" id="email" name="email" required />
              <div class="invalid-feedback" id="emailFeedback">This email is already registered.</div>
            </div>
            <div class="mb-2">
              <label for="phone" class="form-label">Phone Number</label>
              <input type="tel" class="form-control" id="phone" name="phone" required />
              <div class="invalid-feedback" id="phoneFeedback">This phone number is already registered or invalid.</div>
            </div>
            <div class="mb-2">
              <label for="country" class="form-label">Country</label>
              <select class="form-control" id="country" name="country" required>
                <option value="">Select Country</option>
                <option value="United States">United States</option>
                <option value="Nigeria">Nigeria</option>
                <option value="United Kingdom">United Kingdom</option>
                <option value="Canada">Canada</option>
                <option value="Ghana">Ghana</option>
                <option value="South Africa">South Africa</option>
                <option value="India">India</option>
                <option value="Kenya">Kenya</option>
                <option value="Germany">Germany</option>
                <option value="France">France</option>
                <option value="Australia">Australia</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="mb-2">
              <label for="password" class="form-label">Password</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" required />
                <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1"><span class="bi bi-eye"></span></button>
              </div>
              <div class="progress mt-2" style="height: 6px;">
                <div id="passwordStrength" class="progress-bar" role="progressbar" style="width: 0%;"></div>
              </div>
              <div class="form-text" id="passwordStrengthText"></div>
            </div>
            <div class="mb-2">
              <label for="confirm_password" class="form-label">Confirm Password</label>
              <div class="input-group">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required />
                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword" tabindex="-1"><span class="bi bi-eye"></span></button>
              </div>
            </div>
            <button type="submit" class="btn btn-gradient w-100 mb-2">Sign Up</button>
          </form>
          <div class="text-center mt-2">
            <small>Already have an account? <a href="login.php" class="text-primary">Login</a></small>
            <br>
            <a href="reset_password.php" class="text-primary small">Forgot Password?</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Congratulation Modal -->
  <div class="modal fade" id="congratsModal" tabindex="-1" aria-labelledby="congratsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border-radius:1.5rem;">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-bold" id="congratsModalLabel">Congratulations!</h5>
        </div>
        <div class="modal-body text-center">
          <div class="mb-3">
            <span class="bi bi-emoji-smile" style="font-size:2.5rem;color:#19376d;"></span>
          </div>
          <p class="mb-2">Your account has been created successfully.</p>
          <div class="progress mb-2" style="height: 8px;">
            <div id="congratsProgress" class="progress-bar bg-success" role="progressbar" style="width: 0%;transition:width 0.2s;"></div>
          </div>
          <div id="congratsProgressText" class="mb-2">Redirecting to login page...</div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/js/intlTelInput.min.js"></script>
  <script>
    // Particle animation for signup background
    const canvas = document.getElementById('signup-particles');
    if (canvas) {
      const ctx = canvas.getContext('2d');
      let width = window.innerWidth;
      let height = window.innerHeight;
      canvas.width = width;
      canvas.height = height;
      let particles = [];
      const PARTICLE_COUNT = 36;
      const COLORS = ['#19376d', '#0a174e', '#fff', '#3e497a'];
      function resizeCanvas() {
        width = window.innerWidth;
        height = window.innerHeight;
        canvas.width = width;
        canvas.height = height;
      }
      function randomBetween(a, b) {
        return a + Math.random() * (b - a);
      }
      function createParticle() {
        const r = randomBetween(8, 18);
        return {
          x: randomBetween(r, width - r),
          y: randomBetween(r, height - r),
          vx: randomBetween(-0.25, 0.25),
          vy: randomBetween(-0.25, 0.25),
          r,
          color: COLORS[Math.floor(Math.random() * COLORS.length)],
          alpha: randomBetween(0.18, 0.5),
          alphaDir: Math.random() > 0.5 ? 1 : -1
        };
      }
      function initParticles() {
        particles = [];
        for (let i = 0; i < PARTICLE_COUNT; i++) {
          particles.push(createParticle());
        }
      }
      function animateParticles() {
        ctx.clearRect(0, 0, width, height);
        for (let p of particles) {
          p.x += p.vx;
          p.y += p.vy;
          if (p.x < p.r || p.x > width - p.r) p.vx *= -1;
          if (p.y < p.r || p.y > height - p.r) p.vy *= -1;
          p.alpha += 0.004 * p.alphaDir;
          if (p.alpha > 0.5) p.alphaDir = -1;
          if (p.alpha < 0.18) p.alphaDir = 1;
          ctx.save();
          ctx.globalAlpha = p.alpha;
          ctx.beginPath();
          ctx.arc(p.x, p.y, p.r, 0, 2 * Math.PI);
          ctx.fillStyle = p.color;
          ctx.shadowColor = p.color;
          ctx.shadowBlur = 16;
          ctx.fill();
          ctx.restore();
        }
        requestAnimationFrame(animateParticles);
      }
      window.addEventListener('resize', () => { resizeCanvas(); initParticles(); });
      resizeCanvas();
      initParticles();
      animateParticles();
    }
    // Country code picker for phone
    const phoneInput = document.querySelector('#phone');
    if (phoneInput) {
      window.intlTelInput(phoneInput, {
        initialCountry: 'auto',
        geoIpLookup: function(callback) {
          fetch('https://ipapi.co/json').then(res => res.json()).then(data => callback(data.country_code)).catch(() => callback('US'));
        },
        utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/js/utils.js',
      });
    }
    // Client-side username validation
    const usernameInput = document.getElementById('username');
    if (usernameInput) {
      usernameInput.addEventListener('input', function() {
        const valid = /^[a-zA-Z0-9_]{3,}$/.test(this.value);
        this.classList.toggle('is-invalid', !valid);
      });
    }
    // Client-side password strength validation
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
      passwordInput.addEventListener('input', function() {
        this.classList.toggle('is-invalid', this.value.length < 6);
      });
    }
    // Password strength meter
    const passwordStrength = document.getElementById('passwordStrength');
    const passwordStrengthText = document.getElementById('passwordStrengthText');
    if (passwordInput && passwordStrength && passwordStrengthText) {
      passwordInput.addEventListener('input', function() {
        const val = this.value;
        let score = 0;
        if (val.length >= 6) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;
        let percent = [0, 25, 50, 75, 100][score];
        let color = ['#e0e0e0', '#dc3545', '#ffc107', '#0d6efd', '#198754'][score];
        let text = ['Too weak', 'Weak', 'Fair', 'Good', 'Strong'][score];
        passwordStrength.style.width = percent + '%';
        passwordStrength.style.backgroundColor = color;
        passwordStrengthText.textContent = text;
        passwordStrengthText.style.color = color;
      });
    }
    // AJAX check for duplicate username
    if (usernameInput) {
      usernameInput.addEventListener('blur', function() {
        const val = this.value;
        if (/^[a-zA-Z0-9_]{3,}$/.test(val)) {
          fetch('validate_field.php?type=username&value=' + encodeURIComponent(val))
            .then(res => res.json())
            .then(data => {
              if (!data.valid) {
                usernameInput.classList.add('is-invalid');
                document.getElementById('usernameFeedback').textContent = 'Username is already taken.';
              } else {
                usernameInput.classList.remove('is-invalid');
                document.getElementById('usernameFeedback').textContent = 'Username must be at least 3 characters and contain only letters, numbers, and underscores.';
              }
            });
        }
      });
    }
    // AJAX check for duplicate phone
    const phoneInputField = document.getElementById('phone');
    if (phoneInputField) {
      phoneInputField.addEventListener('blur', function() {
        const val = this.value.replace(/\D/g, '');
        if (val.length >= 7) {
          fetch('validate_field.php?type=phone&value=' + encodeURIComponent(val))
            .then(res => res.json())
            .then(data => {
              if (!data.valid) {
                phoneInputField.classList.add('is-invalid');
                document.getElementById('phoneFeedback').textContent = 'This phone number is already registered.';
              } else {
                phoneInputField.classList.remove('is-invalid');
                document.getElementById('phoneFeedback').textContent = 'This phone number is already registered or invalid.';
              }
            });
        }
      });
    }
    // Password show/hide toggle
    function setupPasswordToggle(inputId, toggleId) {
      const input = document.getElementById(inputId);
      const toggle = document.getElementById(toggleId);
      if (input && toggle) {
        toggle.addEventListener('click', function() {
          const type = input.type === 'password' ? 'text' : 'password';
          input.type = type;
          toggle.querySelector('span').className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
      }
    }
    setupPasswordToggle('password', 'togglePassword');
    setupPasswordToggle('confirm_password', 'toggleConfirmPassword');

    // AJAX check for duplicate email
    const emailInput = document.getElementById('email');
    if (emailInput) {
      emailInput.addEventListener('blur', function() {
        const val = this.value;
        if (val && /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(val)) {
          fetch('validate_field.php?type=email&value=' + encodeURIComponent(val))
            .then(res => res.json())
            .then(data => {
              if (!data.valid) {
                emailInput.classList.add('is-invalid');
                document.getElementById('emailFeedback').textContent = 'This email is already registered.';
              } else {
                emailInput.classList.remove('is-invalid');
                document.getElementById('emailFeedback').textContent = 'This email is already registered.';
              }
            });
        }
      });
    }
    // Show congratulation modal and progressive bar, then redirect after signup
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
      signupForm.addEventListener('submit', function(e) {
        if (!signupForm.classList.contains('submitted')) {
          e.preventDefault();
          const congratsModal = new bootstrap.Modal(document.getElementById('congratsModal'));
          congratsModal.show();
          let progress = 0;
          const bar = document.getElementById('congratsProgress');
          const text = document.getElementById('congratsProgressText');
          const interval = setInterval(() => {
            progress += 2;
            bar.style.width = progress + '%';
            if (progress >= 100) {
              clearInterval(interval);
              text.textContent = 'Redirecting...';
              setTimeout(() => { window.location.href = 'login.php'; }, 200);
            }
          }, 50); // 2.5s total
          signupForm.classList.add('submitted');
        }
      });
    }
  </script>
</body>
</html> 