  <!-- ========== AGENDAMIENTO ========== -->
  <section class="booking" id="agendar">
    <div class="container">
      <p class="section__label">AGENDAMIENTO</p>
      <h2 class="section__title">Reserva tu cita en 3 pasos</h2>
      <p class="section__description">
        Solo necesitas tu nombre y correo. Podras completar tu cuenta despues de confirmar.
      </p>

      <!-- Stepper -->
      <div class="booking__card">
        <div class="stepper">
          <div class="stepper__step stepper__step--active">
            <span class="stepper__number">1</span>
            <span class="stepper__text">Tratamiento</span>
          </div>
          <div class="stepper__line"></div>
          <div class="stepper__step">
            <span class="stepper__number">2</span>
            <span class="stepper__text">Fecha y especialista</span>
          </div>
          <div class="stepper__line"></div>
          <div class="stepper__step">
            <span class="stepper__number">3</span>
            <span class="stepper__text">Confirmar</span>
          </div>
        </div>

        <div class="booking__layout">
          <!-- Form -->
          <form class="booking__form" id="bookingForm">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="bookNombre">Nombre completo</label>
                <input type="text" class="form-input" id="bookNombre" name="nombre" required
                       placeholder="Tu nombre"
                       <?php if ($currentUser): ?>value="<?php echo sanitize($currentUser['nombre']); ?>"<?php endif; ?>>
              </div>
              <div class="form-group">
                <label class="form-label" for="bookTelefono">Telefono</label>
                <input type="tel" class="form-input" id="bookTelefono" name="telefono"
                       placeholder="300 123 4567"
                       <?php if ($currentUser && !empty($currentUser['telefono'])): ?>value="<?php echo sanitize($currentUser['telefono']); ?>"<?php endif; ?>>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="bookCorreo">Correo electronico</label>
              <input type="email" class="form-input" id="bookCorreo" name="correo" required
                     placeholder="tu@correo.com"
                     <?php if ($currentUser): ?>value="<?php echo sanitize($currentUser['correo']); ?>"<?php endif; ?>>
            </div>

            <div class="form-group">
              <label class="form-label" for="tratamiento">Tratamiento</label>
              <select class="form-select" id="tratamiento" name="servicio_id">
                <?php foreach ($treatments as $t): ?>
                <option value="<?php echo (int)$t['id_servicio']; ?>"
                        data-duracion="<?php echo (int)$t['duracion_minutos']; ?>"
                        data-precio="<?php echo number_format($t['precio'], 0, '', '.'); ?>">
                  <?php echo sanitize($t['nombre_servicio']); ?> — <?php echo (int)$t['duracion_minutos']; ?> min
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="fecha">Fecha</label>
                <input type="date" class="form-input" id="fecha" name="date" placeholder="dd/mm/aaaa">
              </div>
              <div class="form-group">
                <label class="form-label" for="hora">Hora</label>
                <select class="form-select" id="hora" name="time">
                  <option value="">Selecciona fecha primero</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Especialista</label>
              <div class="specialist-options">
                <label class="specialist-pill">
                  <input type="radio" name="esteticista_id" value="0" checked>
                  <span class="specialist-pill__dot"></span>
                  Aleatorio
                </label>
                <?php foreach ($esteticistas as $est): ?>
                <label class="specialist-pill">
                  <input type="radio" name="esteticista_id" value="<?php echo (int)$est['id_usuario']; ?>">
                  <span class="specialist-pill__dot"></span>
                  <?php echo sanitize($est['nombre']); ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
          </form>

          <!-- Summary -->
          <div class="booking__summary">
            <h3 class="booking__summary-title">Resumen de tu cita</h3>
            <div class="booking__summary-row">
              <span>Tratamiento</span>
              <strong><?php echo isset($treatments[0]) ? sanitize($treatments[0]['nombre_servicio']) : ''; ?></strong>
            </div>
            <div class="booking__summary-row">
              <span>Duracion</span>
              <strong><?php echo isset($treatments[0]) ? (int)$treatments[0]['duracion_minutos'] . ' min' : ''; ?></strong>
            </div>
            <div class="booking__summary-row">
              <span>Especialista</span>
              <strong>Aleatorio (disponible)</strong>
            </div>
            <div class="booking__summary-row">
              <span>Valor</span>
              <strong><?php echo isset($treatments[0]) ? '$' . number_format($treatments[0]['precio'], 0, '', '.') : ''; ?></strong>
            </div>
            <button class="btn btn--primary btn--full" id="confirmBookingBtn">Confirmar reserva</button>
            <p class="booking__summary-note" id="bookingMessage">
              Recibiras tu confirmacion por correo, con invitacion a crear tu contrasena.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
