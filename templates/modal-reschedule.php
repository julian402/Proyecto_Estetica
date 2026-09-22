
<div class="modal" id="rescheduleModal">
    <div class="modal__content">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      <div class="modal__header-kbeauty">
        <h2 class="modal__title">Reprogramar cita</h2>
        <p class="modal__subtitle">Elige una nueva fecha y horario para tu tratamiento facial.</p>
      </div>
<div class="reschedule-summary" id="rescheduleSummary">
        <div class="reschedule-summary__row">
          <span>Tratamiento:</span>
          <strong id="rescheduleServicioNombre">-</strong>
        </div>
        <div class="reschedule-summary__row">
          <span>Especialista:</span>
          <strong id="rescheduleEspecialistaNombre">-</strong>
        </div>
        <div class="reschedule-summary__row">
          <span>Cita actual:</span>
          <strong id="rescheduleActualFechaHora">-</strong>
        </div>
      </div>

      <form class="modal__form" id="rescheduleForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" id="rescheduleReservaId" name="reserva_id" value="">
        <input type="hidden" id="rescheduleServicioId" name="servicio_id" value="">
        <input type="hidden" id="rescheduleEsteticistaId" name="esteticista_id" value="">

        <div class="form-group">
          <label class="form-label" for="rescheduleFecha">
            Nueva fecha <span class="required-asterisk" aria-hidden="true">*</span>
          </label>
          <input type="date" class="form-input" id="rescheduleFecha" name="date" required aria-required="true">
        </div>

        <div class="form-group">
          <label class="form-label" for="rescheduleHora">
            Nueva hora disponible <span class="required-asterisk" aria-hidden="true">*</span>
          </label>
          <select class="form-select" id="rescheduleHora" name="time" required aria-required="true">
            <option value="">Selecciona fecha primero</option>
          </select>
        </div>

        <p class="modal__error" id="rescheduleError" style="color: #e74c3c; font-size: 0.875rem; display: none;"></p>
        <div class="modal__button-group">
          <button type="button" class="btn btn--outline" data-close-modal>Cancelar</button>
          <button type="submit" class="btn btn--primary" id="confirmRescheduleBtn">Confirmar cambio</button>
        </div>
      </form>
    </div>
  </div>
