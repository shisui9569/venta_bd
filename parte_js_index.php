    // Función para aplicar filtros
    function aplicarFiltros() {
        const buscar = document.getElementById('buscarInput').value.trim();
        const categoria = document.getElementById('categoriaSelect').value;
        const orden = document.getElementById('ordenSelect').value;
        const envio = document.getElementById('envioSelect').value;
        
        const params = new URLSearchParams();
        
        if (buscar) params.set('buscar', buscar);
        if (categoria) params.set('categoria', categoria);
        if (orden) params.set('orden', orden);
        if (envio) params.set('envio_gratis', envio);
        
        // Redirigir con los nuevos parámetros
        const newUrl = 'index.php?' + params.toString();
        window.location.href = newUrl;
    }

    // Función para limpiar filtros
    function limpiarFiltros() {
        // Redirigir a la página sin parámetros
        window.location.href = 'index.php';
    }

    // Función para buscar desde el header
    function buscarDesdeHeader() {
        const searchInput = document.getElementById('searchInput');
        const buscar = searchInput.value.trim();
        
        if (buscar) {
            const params = new URLSearchParams();
            params.set('buscar', buscar);
            window.location.href = 'index.php?' + params.toString();
        } else {
            // Si está vacío, limpiar búsqueda
            limpiarFiltros();
        }
    }