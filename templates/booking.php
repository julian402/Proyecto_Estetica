  <!-- ========== AGENDAMIENTO ========== -->
  <section class="booking" id="agendar">
    <div class="container">
      <p class="section__label">AGENDAMIENTO</p>
      <h2 class="section__title">Reserva tu cita en 3 pasos</h2>
      <p class="section__description">
        Vive la experiencia K-Beauty. Solo necesitas tus datos de contacto para asegurar tu espacio de cuidado facial.
      </p>

      <!-- Stepper -->
      <div class="booking__card">
        <div class="stepper" role="navigation" aria-label="Progreso de agendamiento">
          <div class="stepper__step stepper__step--active" data-step="1">
            <span class="stepper__number">1</span>
            <span class="stepper__text">Tratamiento y Especialista</span>
          </div>
          <div class="stepper__line"></div>
          <div class="stepper__step" data-step="2">
            <span class="stepper__number">2</span>
            <span class="stepper__text">Fecha y Hora</span>
          </div>
          <div class="stepper__line"></div>
          <div class="stepper__step" data-step="3">
            <span class="stepper__number">3</span>
            <span class="stepper__text">Tus datos y Confirmación</span>
          </div>
        </div>

        <div class="booking__layout">
          <!-- Form -->
          <form class="booking__form" id="bookingForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <!-- PASO 1: Tratamiento y Especialista -->
            <div class="booking__step-group" id="stepGroup1">
              <div class="form-group">
                <label class="form-label" for="tratamiento">
                  Tratamiento <span class="required-asterisk" aria-hidden="true">*</span>
                </label>
                <select class="form-select" id="tratamiento" name="servicio_id" required aria-required="true">
                  <option value="" disabled <?php echo empty($treatments) ? 'selected' : ''; ?>>Selecciona un tratamiento</option>
                  <?php foreach ($treatments as $idx => $t): ?>
                  <option value="<?php echo (int)$t['id_servicio']; ?>"
                          data-duracion="<?php echo (int)$t['duracion_minutos']; ?>"
                          data-precio="<?php echo number_format($t['precio'], 0, '', '.'); ?>"
                          <?php if ($idx === 0): ?>selected<?php endif; ?>>
                    <?php echo sanitize($t['nombre_servicio']); ?> — <?php echo (int)$t['duracion_minutos']; ?> min
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Tarea 20: Especialista INMEDIATAMENTE DEBAJO de Tratamiento y ANTES de Fecha y Hora -->
              <div class="form-group">
                <label class="form-label" id="especialistaLabel">
                  Especialista <span class="required-asterisk" aria-hidden="true">*</span>
                </label>
                <div class="specialist-options" role="radiogroup" aria-labelledby="especialistaLabel" aria-required="true">
                  <label class="specialist-pill specialist-pill--active">
                    <input type="radio" name="esteticista_id" value="0" checked required aria-required="true">
                    <span class="specialist-pill__dot"></span>
                    Aleatorio (disponible)
                  </label>
                  <?php foreach ($esteticistas as $est): ?>
                  <label class="specialist-pill">
                    <input type="radio" name="esteticista_id" value="<?php echo (int)$est['id_usuario']; ?>" required aria-required="true">
                    <span class="specialist-pill__dot"></span>
                    <?php echo sanitize($est['nombre']); ?>
                  </label>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- PASO 2: Fecha y Hora -->
            <div class="booking__step-group" id="stepGroup2">
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label" for="fecha">
                    Fecha <span class="required-asterisk" aria-hidden="true">*</span>
                  </label>
                  <input type="date" class="form-input" id="fecha" name="date" placeholder="dd/mm/aaaa" required aria-required="true">
                </div>
                <div class="form-group">
                  <label class="form-label" for="hora">
                    Hora <span class="required-asterisk" aria-hidden="true">*</span>
                  </label>
                  <select class="form-select" id="hora" name="time" required aria-required="true">
                    <option value="">Selecciona fecha primero</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- PASO 3: Tus datos -->
            <div class="booking__step-group" id="stepGroup3">
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label" for="bookNombre">
                    Nombre completo <span class="required-asterisk" aria-hidden="true">*</span>
                  </label>
                  <input type="text" class="form-input" id="bookNombre" name="nombre" required aria-required="true"
                         placeholder="Tu nombre completo"
                         <?php if ($currentUser): ?>value="<?php echo sanitize($currentUser['nombre']); ?>"<?php endif; ?>>
                </div>
                <!-- Tarea 19: Teléfono obligatorio en HU2 -->
                <div class="form-group">
                  <label class="form-label" for="bookTelefono">
                    Teléfono <span class="required-asterisk" aria-hidden="true">*</span>
                  </label>
                  <input type="tel" class="form-input" id="bookTelefono" name="telefono" required aria-required="true"
                         placeholder="Ej: 300 123 4567"
                         pattern="[0-9+\s-]{7,15}"
                         title="Ingresa un número de teléfono de al menos 7 dígitos"
                         <?php if ($currentUser && !empty($currentUser['telefono'])): ?>value="<?php echo sanitize($currentUser['telefono']); ?>"<?php endif; ?>>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label" for="bookCorreo">
                  Correo electrónico <span class="required-asterisk" aria-hidden="true">*</span>
                </label>
                <input type="email" class="form-input" id="bookCorreo" name="correo" required aria-required="true"
                       placeholder="tu@correo.com"
                       <?php if ($currentUser): ?>value="<?php echo sanitize($currentUser['correo']); ?>"<?php endif; ?>>
              </div>
            </div>
          </form>

          <!-- Summary -->
          <div class="booking__summary">
            <h3 class="booking__summary-title">Resumen de tu cita</h3>
            <div class="booking__summary-row">
              <span>Tratamiento</span>
              <strong id="summaryTreatment"><?php echo isset($treatments[0]) ? sanitize($treatments[0]['nombre_servicio']) : ''; ?></strong>
            </div>
            <div class="booking__summary-row">
              <span>Duración</span>
              <strong id="summaryDuration"><?php echo isset($treatments[0]) ? (int)$treatments[0]['duracion_minutos'] . ' min' : ''; ?></strong>
            </div>
            <div class="booking__summary-row">
              <span>Especialista</span>
              <strong id="summarySpecialist">Aleatorio (disponible)</strong>
            </div>
            <div class="booking__summary-row">
              <span>Fecha y hora</span>
              <strong id="summaryFechaHora">Por seleccionar</strong>
            </div>
            <div class="booking__summary-row">
              <span>Valor</span>
              <strong id="summaryPrice"><?php echo isset($treatments[0]) ? '$' . number_format($treatments[0]['precio'], 0, '', '.') : ''; ?></strong>
            </div>
            <button class="btn btn--primary btn--full" id="confirmBookingBtn" type="button">Confirmar reserva</button>
            <p class="booking__summary-note" id="bookingMessage">
              Recibirás tu confirmación por correo y podrás completar tu cuenta para gestionar tu cita.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
