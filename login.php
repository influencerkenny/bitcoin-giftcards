<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login | Giftcard & Bitcoin Trading</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <style>
    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #0a174e 0%, #19376d 100%);
      position: relative;
      overflow-x: hidden;
    }
    #login-particles {
      position: fixed;
      inset: 0;
      width: 100vw;
      height: 100vh;
      z-index: 0;
      pointer-events: none;
      display: block;
    }
    .modal-login .modal-content {
      background: #fff;
      border-radius: 1.5rem;
      box-shadow: 0 8px 40px rgba(10,23,78,0.18);
      max-width: 410px;
      margin: 0 auto;
      z-index: 1;
      position: relative;
      padding: 1.5rem 1.2rem 1.2rem 1.2rem;
    }
    .modal-login .btn-gradient {
      background: linear-gradient(90deg, #19376d 0%, #0a174e 100%);
      color: #fff;
      font-weight: 600;
      border: none;
    }
    .modal-login .btn-gradient:hover {
      background: linear-gradient(90deg, #0a174e 0%, #19376d 100%);
      color: #fff;
    }
    .modal-login .form-label {
      color: #19376d;
      font-weight: 500;
    }
    .modal-login .form-control:focus {
      border-color: #19376d;
      box-shadow: 0 0 0 0.2rem rgba(25,55,109,0.08);
    }
    .modal-login .input-group .btn {
      border-radius: 0 0.5rem 0.5rem 0;
    }
    .modal-login .form-text, .modal-login .invalid-feedback {
      color: #19376d !important;
    }
    .modal-login .text-primary, .modal-login a.text-primary {
      color: #19376d !important;
    }
    .modal-login .modal-header {
      border-bottom: none;
      padding-bottom: 0;
    }
    .modal-login .modal-title {
      color: #19376d;
      font-weight: 700;
    }
    @media (max-width: 600px) {
      .modal-login .modal-content { padding: 0.7rem 0.2rem; border-radius: 1rem; }
    }
  </style>
</head>
<body>
  <canvas id="login-particles"></canvas>
  <div class="modal fade show" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-modal="true" role="dialog" style="display:block;background:rgba(10,23,78,0.18);">
    <div class="modal-dialog modal-dialog-centered modal-login">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="loginModalLabel">Sign In</h5>
          <a href="index.html" class="btn-close" aria-label="Close"></a>
        </div>
        <div class="modal-body">
          <form method="post" action="login.php" id="loginForm">
            <div class="text-center mb-3">
              <img src="images/logo.png" alt="Logo" style="height:40px;">
              <p class="text-muted mb-0">Access your account</p>
            </div>
            <div class="mb-2">
              <label for="email" class="form-label">Email address</label>
              <input type="email" class="form-control" id="email" name="email" required />
            </div>
            <div class="mb-2">
              <label for="password" class="form-label">Password</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" required />
                <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1"><span class="bi bi-eye"></span></button>
              </div>
            </div>
            <button type="submit" class="btn btn-gradient w-100 mb-2">Login</button>
          </form>
          <div class="text-center mt-2">
            <small>Don't have an account? <a href="signup.php" class="text-primary">Sign up</a></small>
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
          <p class="mb-2">Login successful.</p>
          <div class="progress mb-2" style="height: 8px;">
            <div id="congratsProgress" class="progress-bar bg-success" role="progressbar" style="width: 0%;transition:width 0.2s;"></div>
          </div>
          <div id="congratsProgressText" class="mb-2">Redirecting to your dashboard...</div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Particle animation for login background
    const canvas = document.getElementById('login-particles');
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

    // Show congratulation modal and progressive bar, then redirect after login
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
      loginForm.addEventListener('submit', function(e) {
        if (!loginForm.classList.contains('submitted')) {
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
              setTimeout(() => { window.location.href = 'dashboard.php'; }, 200);
            }
          }, 50); // 2.5s total
          loginForm.classList.add('submitted');
        }
      });
    }
  </script>
</body>
</html> 