<button type="button" class="about-trigger" data-about-open aria-controls="aboutPanel" aria-expanded="false">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
    <circle cx="12" cy="12" r="9"/>
    <path d="M12 11v6M12 7h.01" stroke-linecap="round"/>
  </svg>
  Acerca de
</button>

<section id="aboutPanel" class="about-card animate-fade-up" data-about-panel data-about-interval="20000" aria-label="Acerca de este proyecto" hidden>
  <div class="about-topbar">
    <button type="button" class="about-back" data-about-close>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path d="m14 7-5 5 5 5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Volver al inicio
    </button>
    <span class="about-badge"><span class="about-badge-dot" aria-hidden="true"></span>Hecho por aprendices <span aria-hidden="true">🙏🏻</span></span>
  </div>

  <div class="about-carousel" role="region" aria-roledescription="carrusel" aria-label="Desarrolladores y proyecto">
    <div class="about-slides" aria-live="off">
      <article class="about-slide is-active" data-about-slide role="group" aria-roledescription="diapositiva" aria-label="1 de 2" aria-hidden="false">
        <div class="about-heading">
          <p class="about-eyebrow">El equipo detrás del proyecto</p>
          <h2 class="about-title">Contacto de los desarrolladores</h2>
        </div>

        <ul class="about-contacts">
          <li class="about-contact">
            <span class="about-avatar" aria-hidden="true">KS</span>
            <div class="about-contact-info">
              <h3>Kevin Santiago Saenz Reyes</h3>
              <div class="about-email-actions">
                <button type="button" class="about-email" data-about-email aria-controls="about-copy-kevin" aria-expanded="false">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  <span data-about-email-value>kssaenz0<wbr>@gmail.com</span>
                </button>
                <button type="button" id="about-copy-kevin" class="about-copy" data-about-copy aria-label="Copiar correo de Kevin Santiago Saenz Reyes" hidden>
                  <svg class="about-copy-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg>
                  <svg class="about-copied-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  <span data-about-copy-label>Copiar</span>
                </button>
              </div>
            </div>
          </li>
          <li class="about-contact">
            <span class="about-avatar" aria-hidden="true">NR</span>
            <div class="about-contact-info">
              <h3>Nicol Caterine Rodriguez Benitez</h3>
              <div class="about-email-actions">
                <button type="button" class="about-email" data-about-email aria-controls="about-copy-nicol" aria-expanded="false">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  <span data-about-email-value>rnicolcaterine042007<wbr>@gmail.com</span>
                </button>
                <button type="button" id="about-copy-nicol" class="about-copy" data-about-copy aria-label="Copiar correo de Nicol Caterine Rodriguez Benitez" hidden>
                  <svg class="about-copy-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg>
                  <svg class="about-copied-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  <span data-about-copy-label>Copiar</span>
                </button>
              </div>
            </div>
          </li>
          <li class="about-contact">
            <span class="about-avatar" aria-hidden="true">JB</span>
            <div class="about-contact-info">
              <h3>Johan Stiven Bocanegra Sanchez</h3>
              <div class="about-email-actions">
                <button type="button" class="about-email" data-about-email aria-controls="about-copy-johan" aria-expanded="false">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  <span data-about-email-value>johanstivenbocanegrasanchez<wbr>@gmail.com</span>
                </button>
                <button type="button" id="about-copy-johan" class="about-copy" data-about-copy aria-label="Copiar correo de Johan Stiven Bocanegra Sanchez" hidden>
                  <svg class="about-copy-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg>
                  <svg class="about-copied-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  <span data-about-copy-label>Copiar</span>
                </button>
              </div>
            </div>
          </li>
        </ul>
      </article>

      <article class="about-slide about-story" data-about-slide role="group" aria-roledescription="diapositiva" aria-label="2 de 2" aria-hidden="true" inert>
        <img src="<?= e(app_asset('assets/img/capibaras-equipo.webp')) ?>" class="about-team-image" alt="Tres capibaras del equipo de desarrolladores" width="144" height="120" draggable="false">
        <h2 class="about-title">Sena Premium</h2>
        <p>Este proyecto fue desarrollado por nosotros, <strong>practicantes del SENA</strong>, con dedicación, creatividad y bitacoras🙏🏻.</p>
        <p>¿Necesitas desarrollar un aplicativo? Contáctanos</p>
        <button type="button" class="about-contact-link" data-about-go="0">Ver contactos <span aria-hidden="true">&rarr;</span></button>
      </article>
    </div>

    <div class="about-controls">
      <button type="button" class="about-arrow" data-about-prev aria-label="Mensaje anterior">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
          <path d="m14 6-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
      <div class="about-dots" aria-label="Elegir mensaje">
        <button type="button" class="about-dot is-active" data-about-go="0" aria-label="Ver contactos de los desarrolladores" aria-current="true"></button>
        <button type="button" class="about-dot" data-about-go="1" aria-label="Ver información del proyecto"></button>
      </div>
      <button type="button" class="about-arrow" data-about-next aria-label="Mensaje siguiente">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
          <path d="m10 6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>
    <div class="about-playback">
      <span>Cambio automático cada 20 segundos</span>
      <button type="button" class="about-pause" data-about-pause aria-pressed="false">Pausar</button>
    </div>
    <p class="about-sr-only" data-about-status role="status" aria-live="polite"></p>
  </div>
</section>

<script src="<?= e(app_asset('assets/js/acerca-de.js')) ?>" defer></script>
