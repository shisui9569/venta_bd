    // Inicializar eventos cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        // Botón aplicar filtros
        const aplicarFiltrosBtn = document.getElementById('aplicarFiltros');
        if (aplicarFiltrosBtn) {
            aplicarFiltrosBtn.addEventListener('click', aplicarFiltros);
        }
        
        // Botón limpiar filtros
        const limpiarFiltrosBtn = document.getElementById('limpiarFiltros');
        if (limpiarFiltrosBtn) {
            limpiarFiltrosBtn.addEventListener('click', limpiarFiltros);
        }
        
        // Búsqueda desde el header
        const searchButton = document.getElementById('searchButton');
        const searchInput = document.getElementById('searchInput');
        
        if (searchButton) {
            searchButton.addEventListener('click', buscarDesdeHeader);
        }
        
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    buscarDesdeHeader();
                }
            });
        }
        
        // Prevenir que los selects envíen automáticamente
        const categoriaSelect = document.getElementById('categoriaSelect');
        const ordenSelect = document.getElementById('ordenSelect');
        const envioSelect = document.getElementById('envioSelect');
        
        if (categoriaSelect) {
            categoriaSelect.addEventListener('change', function(e) {
                e.preventDefault();
                // No hacer nada - solo el botón aplica los filtros
            });
        }
        
        if (ordenSelect) {
            ordenSelect.addEventListener('change', function(e) {
                e.preventDefault();
                // No hacer nada - solo el botón aplica los filtros
            });
        }
        
        if (envioSelect) {
            envioSelect.addEventListener('change', function(e) {
                e.preventDefault();
                // No hacer nada - solo el botón aplica los filtros
            });
        }
    });