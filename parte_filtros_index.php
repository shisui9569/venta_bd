  <!-- Filter Section -->
  <section class="filter-section">
    <div class="filter-container" id="filterContainer">
      <div class="filter-group">
        <label class="filter-label">Buscar Producto</label>
        <input type="text" id="buscarInput" class="filter-input" 
               placeholder="Nombre del producto..." 
               value="<?= htmlspecialchars($busqueda) ?>">
      </div>
      
      <div class="filter-group">
        <label class="filter-label">Categoría</label>
        <select id="categoriaSelect" class="filter-select">
          <option value="">Todas las categorías</option>
          <option value="SoporteInmunologico" <?= $categoria=="SoporteInmunologico"?"selected":"" ?>>Soporte Inmunológico</option>
          <option value="BienestarDiario" <?= $categoria=="BienestarDiario"?"selected":"" ?>>Bienestar Diario</option>
          <option value="EnvejecimientoSaludable" <?= $categoria=="EnvejecimientoSaludable"?"selected":"" ?>>Envejecimiento Saludable</option>
          <option value="RendimientoDeportivo" <?= $categoria=="RendimientoDeportivo"?"selected":"" ?>>Rendimiento Deportivo</option>
          <option value="EstresEstadoAnimo" <?= $categoria=="EstresEstadoAnimo"?"selected":"" ?>>Estrés y Estado de Ánimo</option>
          <option value="SaludCerebral" <?= $categoria=="SaludCerebral"?"selected":"" ?>>Salud Cerebral</option>
          <option value="Promociones" <?= $categoria=="Promociones"?"selected":"" ?>>Promociones</option>
          <option value="PaquetesSalud" <?= $categoria=="PaquetesSalud"?"selected":"" ?>>Paquetes de Salud</option>
          <option value="Accesorios" <?= $categoria=="Accesorios"?"selected":"" ?>>Accesorios</option>
          <option value="Vitaminas" <?= $categoria=="Vitaminas"?"selected":"" ?>>Vitaminas</option>
          <option value="Minerales" <?= $categoria=="Minerales"?"selected":"" ?>>Minerales</option>
        </select>
      </div>
      
      <div class="filter-group">
        <label class="filter-label">Ordenar Por</label>
        <select id="ordenSelect" class="filter-select">
          <option value="">Por defecto</option>
          <option value="popular" <?= $orden=="popular"?"selected":"" ?>>Más Popular</option>
          <option value="precio_menor" <?= $orden=="precio_menor"?"selected":"" ?>>Precio ↑</option>
          <option value="precio_mayor" <?= $orden=="precio_mayor"?"selected":"" ?>>Precio ↓</option>
          <option value="nuevo" <?= $orden=="nuevo"?"selected":"" ?>>Más Reciente</option>
        </select>
      </div>
      
      <div class="filter-group">
        <label class="filter-label">Envío</label>
        <select id="envioSelect" class="filter-select">
          <option value="">Todos</option>
          <option value="1" <?= $envio_gratis=="1"?"selected":"" ?>>Con Envío Gratis</option>
          <option value="0" <?= $envio_gratis=="0"?"selected":"" ?>>Con Costo de Envío</option>
        </select>
      </div>
      
      <div class="filter-actions">
        <button type="button" id="aplicarFiltros" class="btn-neon btn-primary filter-btn">
          <i class="fas fa-filter"></i> Aplicar
        </button>
        <button type="button" id="limpiarFiltros" class="btn-neon btn-secondary filter-btn">
          <i class="fas fa-eraser"></i> Limpiar
        </button>
      </div>
    </div>
  </section>