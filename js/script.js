document.addEventListener("DOMContentLoaded", function () {
  const ctaButton = document.querySelector('.btn-gradient');
  if (ctaButton) {
    ctaButton.addEventListener('click', function (e) {
      e.preventDefault();
      alert("Redirecting to registration...");
    });
  }

  // Particle animation for hero section
  const canvas = document.getElementById('hero-particles');
  if (canvas) {
    const ctx = canvas.getContext('2d');
    let particles = [];
    let width = 0, height = 0;
    const PARTICLE_COUNT = 32;
    const COLORS = ['#c3dafe', '#fcb6e2', '#e0c3fc', '#a5b4fc', '#b3d8fd'];

    function resizeCanvas() {
      const parent = canvas.parentElement;
      width = parent.offsetWidth;
      height = parent.offsetHeight;
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
        vx: randomBetween(-0.3, 0.3),
        vy: randomBetween(-0.3, 0.3),
        r,
        color: COLORS[Math.floor(Math.random() * COLORS.length)],
        alpha: randomBetween(0.3, 0.7),
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
        // Move
        p.x += p.vx;
        p.y += p.vy;
        // Bounce
        if (p.x < p.r || p.x > width - p.r) p.vx *= -1;
        if (p.y < p.r || p.y > height - p.r) p.vy *= -1;
        // Alpha fade in/out
        p.alpha += 0.005 * p.alphaDir;
        if (p.alpha > 0.7) p.alphaDir = -1;
        if (p.alpha < 0.3) p.alphaDir = 1;
        // Draw
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

    function onResize() {
      resizeCanvas();
      initParticles();
    }

    window.addEventListener('resize', onResize);
    resizeCanvas();
    initParticles();
    animateParticles();
  }

  // Modal AJAX logic for login/signup
  const loginForm = document.getElementById('loginForm');
  const signupForm = document.getElementById('signupForm');
  const loginError = document.getElementById('loginError');
  const signupError = document.getElementById('signupError');
  const signupSuccess = document.getElementById('signupSuccess');

  if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
      e.preventDefault();
      loginError.style.display = 'none';
      const formData = new FormData(loginForm);
      fetch('auth_login.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          loginError.style.display = 'none';
          loginForm.reset();
          // Optionally close modal and reload or redirect
          const modal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
          if (modal) modal.hide();
          // Reload page or redirect to dashboard
          window.location.reload();
        } else {
          loginError.textContent = data.message;
          loginError.style.display = 'block';
        }
      })
      .catch(() => {
        loginError.textContent = 'An error occurred. Please try again.';
        loginError.style.display = 'block';
      });
    });
  }

  if (signupForm) {
    signupForm.addEventListener('submit', function(e) {
      e.preventDefault();
      signupError.style.display = 'none';
      signupSuccess.style.display = 'none';
      const formData = new FormData(signupForm);
      fetch('auth_register.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          signupError.style.display = 'none';
          signupSuccess.textContent = data.message;
          signupSuccess.style.display = 'block';
          signupForm.reset();
        } else {
          signupError.textContent = data.message;
          signupError.style.display = 'block';
        }
      })
      .catch(() => {
        signupError.textContent = 'An error occurred. Please try again.';
        signupError.style.display = 'block';
      });
    });
  }

  // Clear messages on modal open/close
  const loginModal = document.getElementById('loginModal');
  if (loginModal) {
    loginModal.addEventListener('show.bs.modal', function() {
      if (loginError) loginError.style.display = 'none';
      if (loginForm) loginForm.reset();
    });
  }
  const signupModal = document.getElementById('signupModal');
  if (signupModal) {
    signupModal.addEventListener('show.bs.modal', function() {
      if (signupError) signupError.style.display = 'none';
      if (signupSuccess) signupSuccess.style.display = 'none';
      if (signupForm) signupForm.reset();
    });
  }

  const resetRequestForm = document.getElementById('resetRequestForm');
  const resetRequestError = document.getElementById('resetRequestError');
  const resetRequestSuccess = document.getElementById('resetRequestSuccess');

  if (resetRequestForm) {
    resetRequestForm.addEventListener('submit', function(e) {
      e.preventDefault();
      resetRequestError.style.display = 'none';
      resetRequestSuccess.style.display = 'none';
      const formData = new FormData(resetRequestForm);
      fetch('request_reset.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          resetRequestError.style.display = 'none';
          resetRequestSuccess.textContent = data.message;
          resetRequestSuccess.style.display = 'block';
          resetRequestForm.reset();
        } else {
          resetRequestError.textContent = data.message;
          resetRequestError.style.display = 'block';
        }
      })
      .catch(() => {
        resetRequestError.textContent = 'An error occurred. Please try again.';
        resetRequestError.style.display = 'block';
      });
    });
  }

  const resetRequestModal = document.getElementById('resetRequestModal');
  if (resetRequestModal) {
    resetRequestModal.addEventListener('show.bs.modal', function() {
      if (resetRequestError) resetRequestError.style.display = 'none';
      if (resetRequestSuccess) resetRequestSuccess.style.display = 'none';
      if (resetRequestForm) resetRequestForm.reset();
    });
  }
}); 