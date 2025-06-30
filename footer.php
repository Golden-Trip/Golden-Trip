<footer class="footer-custom text-white mt-5 pt-5 pb-3 position-relative">
  <div class="container">
    <div class="row gy-4">

      <div class="col-md-4">
        <h5 class="mb-3">Sobre AgenciaViajes</h5>
        <p>
          AgenciaViajes es tu mejor opción para descubrir destinos increíbles alrededor del mundo. 
          Con años de experiencia, te ofrecemos paquetes personalizados y atención exclusiva para que vivas experiencias inolvidables.
        </p>

        <h6 class="mt-4">Síguenos</h6>
        <div class="social-icons">
          <a href="https://www.facebook.com/tu_pagina" class="text-white me-3" target="_blank" rel="noopener noreferrer">
            <i class="bi bi-facebook fs-4"></i>
          </a>
          <a href="https://www.instagram.com/tu_cuenta" class="text-white me-3" target="_blank" rel="noopener noreferrer">
            <i class="bi bi-instagram fs-4"></i>
          </a>
          <a href="https://www.twitter.com/tu_cuenta" class="text-white me-3" target="_blank" rel="noopener noreferrer">
            <i class="bi bi-twitter fs-4"></i>
          </a>
          <a href="https://www.youtube.com/tu_canal" class="text-white" target="_blank" rel="noopener noreferrer">
            <i class="bi bi-youtube fs-4"></i>
          </a>
        </div>
      </div>

      <div class="col-md-3">
        <h5 class="mb-3">Enlaces Rápidos</h5>
        <ul class="list-unstyled footer-links">
          <li><a href="productos.php" class="text-white">Paquetes</a></li>
          <li><a href="contacto.php" class="text-white">Contacto</a></li>
          <li><a href="perfil.php" class="text-white">Mi Perfil</a></li>
          <li><a href="historial.php" class="text-white">Historial</a></li>
          <li><a href="opiniones.php" class="text-white">Opiniones</a></li>
        </ul>
      </div>

      <div class="col-md-3">
        <h5 class="mb-3">Contáctanos</h5>
        <ul class="list-unstyled contact-info">
          <li><i class="bi bi-geo-alt-fill me-2"></i> Av. Turismo 123, Ciudad, País</li>
          <li><i class="bi bi-telephone-fill me-2"></i> +54 11 1234 5678</li>
          <li><i class="bi bi-envelope-fill me-2"></i> contacto@agenciaviajes.com</li>
          <li><i class="bi bi-clock-fill me-2"></i> Lun - Vie: 9am - 6pm</li>
        </ul>
      </div>

      <div class="col-md-2">
        <h5 class="mb-3">Newsletter</h5>
        <p>Suscríbete para recibir ofertas exclusivas y novedades.</p>

        <form id="newsletterForm" class="d-flex flex-column gap-2">
          <input type="email" id="emailInput" name="email" placeholder="Tu correo electrónico" class="form-control form-control-sm" required>
          <button type="submit" class="btn btn-outline-light btn-sm">Suscribirse</button>
          <small id="emailFeedback" class="text-warning" style="display:none;"></small>
        </form>

        <script>
          document.getElementById('newsletterForm').addEventListener('submit', async function (e) {
            e.preventDefault(); // Evita el envío tradicional del formulario

            const emailInput = document.getElementById('emailInput');
            const feedback = document.getElementById('emailFeedback');
            const submitButton = this.querySelector('button[type="submit"]'); // Referencia al botón de envío

            const email = emailInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            // Limpia el feedback anterior y lo oculta
            feedback.style.display = 'none';
            feedback.textContent = ''; 

            if (!emailRegex.test(email)) {
              feedback.textContent = 'Por favor, ingresa un correo válido.';
              feedback.style.color = '#ffc107'; // Amarillo para advertencia
              feedback.style.display = 'block';
              return; // Detiene la ejecución si el email es inválido
            }

            // Deshabilita el botón para evitar envíos múltiples
            submitButton.disabled = true; 
            feedback.textContent = 'Enviando...'; // Mensaje de carga
            feedback.style.color = '#ffffff'; // Color blanco para el mensaje de carga
            feedback.style.display = 'block';

            const formData = new FormData();
            formData.append('email', email);

            try {
              const response = await fetch('newsletter.php', {
                method: 'POST',
                body: formData
              });

              const result = await response.json();

              if (result.success) {
                feedback.textContent = '¡Suscripción exitosa! Revisa tu bandeja de entrada 📬';
                feedback.style.color = '#4CAF50'; // Verde para éxito
                emailInput.value = ''; // Limpia el input solo si es exitoso
              } else {
                // Muestra el error que viene del servidor (newsletter.php)
                feedback.textContent = result.error || 'Error desconocido al procesar la solicitud.';
                feedback.style.color = '#dc3545'; // Rojo para error
              }
            } catch (error) {
              console.error('Error de red o del servidor:', error); // Para depuración en consola
              feedback.textContent = 'Hubo un problema de conexión. Inténtalo de nuevo más tarde.';
              feedback.style.color = '#dc3545'; // Rojo para error de red
            } finally {
              submitButton.disabled = false; // Siempre habilita el botón al finalizar
              feedback.style.display = 'block'; // Asegura que el feedback final se muestre
              setTimeout(() => feedback.style.display = 'none', 5000); // Oculta después de 5 segundos
            }
          });
        </script>
      </div>

    </div>
  </div>

  <div class="text-center mt-4">
    <button id="scrollTopBtn" title="Volver arriba">
      <i class="bi bi-arrow-up-circle-fill me-2"></i> Volver al comienzo
    </button>
  </div>

  <hr class="my-4 border-light">

  <div class="d-flex justify-content-between flex-column flex-md-row align-items-center small">
    <div>
      &copy; <?php echo date('Y'); ?> AgenciaViajes. Todos los derechos reservados.
    </div>
    <div>
      <a href="/terminos-y-condiciones.php" class="text-white me-3 footer-link">Términos y condiciones</a>
      <a href="/politica-de-privacidad.php" class="text-white footer-link">Política de privacidad</a>
    </div>
  </div>
</footer>

<style>
  .footer-custom {
    background-color: #003366;
    color: #fff;
    padding-top: 3rem;
    padding-bottom: 1.5rem;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  .footer-links li {
    margin-bottom: 0.6rem;
  }

  .footer-links a {
    color: #ddd;
    text-decoration: none;
    transition: color 0.3s ease;
  }

  .footer-links a:hover {
    color: #00aaff;
    text-decoration: underline;
  }

  .contact-info li {
    margin-bottom: 0.6rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .contact-info i {
    color: #00aaff;
  }

  .social-icons a {
    text-decoration: none !important;
    color: #fff;
    transition: transform 0.3s ease, color 0.3s ease;
  }

  .social-icons a:hover {
    color: #00aaff;
    transform: scale(1.2) rotate(10deg);
  }

  .social-icons i {
    vertical-align: middle;
  }

  #scrollTopBtn {
    background: #00aaff;
    border: none;
    border-radius: 30px;
    color: white;
    cursor: pointer;
    padding: 10px 24px;
    font-size: 1rem;
    font-weight: bold;
    transition: background-color 0.3s ease, transform 0.2s ease;
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
  }

  #scrollTopBtn:hover {
    background-color: #0088cc;
    transform: translateY(-2px);
  }

  .footer-link {
    transition: color 0.3s ease;
    cursor: pointer;
  }

  .footer-link:hover {
    color: #00aaff;
    text-decoration: underline;
  }

  @media (max-width: 768px) {
    .footer-custom .row > div {
      margin-bottom: 1.5rem;
    }

    #scrollTopBtn {
      width: 100%;
      border-radius: 0;
    }
  }
</style>

<script>
  const scrollBtn = document.getElementById('scrollTopBtn');
  // Muestra/oculta el botón basado en el scroll
  window.addEventListener('scroll', () => {
    scrollBtn.style.display = window.scrollY > 300 ? 'inline-block' : 'none';
  });

  // Asegura que el botón esté oculto al cargar la página si no hay suficiente scroll
  window.addEventListener('load', () => {
    scrollBtn.style.display = window.scrollY > 300 ? 'inline-block' : 'none';
  });

  // Desplazamiento suave al hacer clic en el botón
  scrollBtn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
</script>