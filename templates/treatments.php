  <!-- ========== TRATAMIENTOS ========== -->
  <section class="treatments" id="tratamientos">
    <div class="container">
      <p class="section__label">MENU DE SERVICIOS</p>
      <h2 class="section__title">Tratamientos faciales y corporales</h2>
      <p class="section__description">
        Cada ritual incluye diagnostico de piel antes de comenzar. Elige el tuyo y
        reservalo en menos de un minuto.
      </p>

      <div class="filter-tabs">
        <button class="filter-tab filter-tab--active" data-filter="todos">Todos</button>
        <button class="filter-tab" data-filter="faciales">Faciales</button>
        <button class="filter-tab" data-filter="corporales">Corporales</button>
        <button class="filter-tab" data-filter="populares">Mas reservados</button>
      </div>

      <div class="treatments__grid">
        <?php foreach ($treatments as $index => $t):
          $catLower = strtolower($t['nombre_categoria']);
          $filterCat = $catLower === 'facial' ? 'faciales' : 'corporales';
        ?>
        <article class="treatment-card" data-category="<?php echo $filterCat; ?>">
          <div class="treatment-card__image">
            <span class="treatment-card__badge treatment-card__badge--<?php echo sanitize($catLower); ?>"><?php echo strtoupper(sanitize($t['nombre_categoria'])); ?></span>
            <div class="treatment-card__img-placeholder treatment-card__img-placeholder--<?php echo $index + 1; ?>"></div>
          </div>
          <div class="treatment-card__body">
            <h3 class="treatment-card__title"><?php echo sanitize($t['nombre_servicio']); ?></h3>
            <p class="treatment-card__description">
              <?php echo sanitize($t['descripcion']); ?>
            </p>
            <p class="treatment-card__meta"><?php echo (int)$t['duracion_minutos']; ?> min · Incluye diagnostico</p>
            <div class="treatment-card__footer">
              <span class="treatment-card__price">$<?php echo number_format($t['precio'], 0, '', '.'); ?></span>
              <a href="#agendar" class="btn btn--primary btn--sm" data-treatment="<?php echo $index; ?>" data-servicio-id="<?php echo (int)$t['id_servicio']; ?>">Reservar</a>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
