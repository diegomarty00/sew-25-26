
// js/circuito.js
class Circuito {
    constructor() {
        if (!this.comprobarApiFile()) return;

        // Selectores robustos (sin entidades HTML)
        this.inputXML = document.querySelector('main > section:nth-of-type(1) input');

        this.article = document.querySelector('main > section:nth-of-type(1) article');

        if (!this.inputXML || !this.article) {
            console.error('No se encontró el input o el article de destino.');
            return;
        }

        // Conectar el input
        this.inputXML.addEventListener('change', (evento) => this.leerArchivoHTML(evento));
    }

    comprobarApiFile() {
        const ok = !!(window.File && window.FileReader && window.FileList && window.Blob);
        if (!ok) {
            const p = document.createElement('p');
            p.textContent = 'Error: su navegador no soporta el API File.';
            document.querySelector('main')?.prepend(p);
        }
        return ok;
    }

    leerArchivoHTML(evento) {
        const archivo = evento.target.files?.[0];
        if (!archivo) return;

        const lector = new FileReader();
        lector.onload = (e) => {
            const contenido = String(e.target.result || '');
            // Pega el contenido tal cual en <article>
            this.article.innerHTML = contenido;
        };
        lector.readAsText(archivo, 'utf-8');
    }
}


class CargadorSVG {
    constructor() {
        // Selecciona el input en la segunda sección de <main>
        const inputSVG = document.querySelector('main > section:nth-of-type(2) input');
        if (inputSVG) {
            inputSVG.addEventListener('change', this.leerArchivoSVG.bind(this));
        }
    }

    leerArchivoSVG(evento) {
        const archivo = evento.target.files[0];
        if (!archivo) return;

        const lector = new FileReader();

        lector.onload = () => {
            const contenido = lector.result;

            // Borrar cualquier SVG previo en la segunda sección
            document.querySelectorAll('main > section:nth-of-type(2) svg').forEach(svg => svg.remove());

            // Insertar el nuevo SVG debajo del <h3> si existe
            const seccion = document.querySelector('main > section:nth-of-type(2)');
            const h3 = seccion.querySelector('h3');

            // Parsear el contenido como SVG para mantener el namespace correcto
            const parser = new DOMParser();
            const docSVG = parser.parseFromString(contenido, 'image/svg+xml');
            const svg = docSVG.documentElement;

            if (h3) {
                h3.insertAdjacentElement('afterend', svg);
            } else {
                seccion.appendChild(svg);
            }
        };

        lector.readAsText(archivo);
    }
}


class CargadorKML {
    constructor() {
        this.mapa = null;

        // Referencias dentro de la 3ª sección de <main>
        this.contenedorMapa = document.querySelector('main > section:nth-of-type(3) > div');
        this.inputKML = document.querySelector('main > section:nth-of-type(3) input');

        // Array para guardar las referencias a las líneas y marcadores para poder borrarlos
        this.overlays = [];

        if (this.inputKML) {
            this.inputKML.addEventListener('change', this.leerArchivoKML.bind(this));
        } else {
            console.error('No se encontró el input KML en la 3ª sección.');
        }
    }

    inicializarMapa() {
        // Limpiamos el contenedor por si quedara algo del DOM
        while (this.contenedorMapa && this.contenedorMapa.firstChild) {
            this.contenedorMapa.removeChild(this.contenedorMapa.firstChild);
        }

        // Creamos el mapa de Google Maps
        this.mapa = new google.maps.Map(this.contenedorMapa, {
            zoom: 15,
            center: { lat: 43.354, lng: -5.8534 },
            mapTypeId: 'terrain'
        });
    }

    leerArchivoKML(files) {
        const archivo = files.target.files && files.target.files[0];
        if (!archivo) return;

        // Inicializamos el mapa si no existe (primera vez)
        if (!this.mapa) {
            this.inicializarMapa();
        }

        const lector = new FileReader();
        lector.onload = () => {
            const parser = new DOMParser();
            // Usa 'application/xml' para KML
            const xmlDoc = parser.parseFromString(String(lector.result || ''), 'application/xml');

            // Comprobación básica de parseo
            if (xmlDoc.querySelector('parsererror')) {
                this.#mensaje('El KML está mal formado.', 'error');
                return;
            }

            this.insertarCapaKML(xmlDoc);
        };
        lector.readAsText(archivo, 'utf-8');
    }

    // Método auxiliar para limpiar el mapa antes de pintar uno nuevo
    limpiarMapa() {
        if (this.overlays && this.overlays.length) {
            for (let i = 0; i < this.overlays.length; i++) {
                // setMap(null) elimina el objeto del mapa de Google
                if (this.overlays[i] && typeof this.overlays[i].setMap === 'function') {
                    this.overlays[i].setMap(null);
                } else if (this.overlays[i] && 'map' in this.overlays[i]) {
                    // Adaptador por si usas AdvancedMarkerElement (no tiene setMap)
                    this.overlays[i].map = null;
                }
            }
            this.overlays = [];
        }
    }

    insertarCapaKML(xmlDoc) {
        // CORRECCIÓN: Llamamos a limpiar antes de procesar nada nuevo
        this.limpiarMapa();

        // Recogemos TODOS los <coordinates> (varios LineString/Placemark)
        const coordinatesTags = Array.from(xmlDoc.getElementsByTagName('coordinates'));
        if (!coordinatesTags.length) {
            this.#mensaje('No se encontraron etiquetas <coordinates> en el KML.', 'warn');
            return;
        }

        const limites = new google.maps.LatLngBounds();
        let hayRuta = false;

        for (const tag of coordinatesTags) {
            const coordenadasRaw = (tag.textContent || '').trim();
            if (!coordenadasRaw) continue;

            // Cada coordenada puede venir separada por espacios o saltos de línea
            const lineas = coordenadasRaw.split(/\s+/);
            const rutaGoogle = [];

            for (const linea of lineas) {
                // KML: lon,lat[,alt] → primero longitud (lng), luego latitud (lat)
                const [lngStr, latStr] = linea.split(',');
                const lng = parseFloat(lngStr);
                const lat = parseFloat(latStr);
                if (!isNaN(lat) && !isNaN(lng)) {
                    const punto = { lat, lng };
                    rutaGoogle.push(punto);
                    limites.extend(punto);
                }
            }

            if (rutaGoogle.length >= 2) {
                hayRuta = true;

                // Trazado del tramo
                const trazadoCircuito = new google.maps.Polyline({
                    path: rutaGoogle,
                    geodesic: true,
                    strokeColor: '#FF0000',
                    strokeOpacity: 1.0,
                    strokeWeight: 4
                });

                trazadoCircuito.setMap(this.mapa);
                // Guardamos la referencia para poder borrarla luego
                this.overlays.push(trazadoCircuito);

                // Marcador al inicio del tramo (opcional)
                const marcador = new google.maps.Marker({
                    position: rutaGoogle[0],
                    map: this.mapa,
                    title: 'Inicio del tramo'
                });
                this.overlays.push(marcador);
            }
        }

        if (hayRuta) {
            // Ajustamos la vista al total de puntos de todos los tramos
            this.mapa.fitBounds(limites);
            this.#mensaje('KML cargado y dibujado correctamente.', 'ok');
            return;
        }

        // Si no hay rutas, intentamos pintar puntos (Point -> coordinates)
        const pointCoords = Array.from(xmlDoc.getElementsByTagName('Point'))
            .map(p => p.querySelector('coordinates'))
            .filter(Boolean);

        if (pointCoords.length) {
            for (const tag of pointCoords) {
                const [lngStr, latStr] = (tag.textContent || '').trim().split(',');
                const lng = parseFloat(lngStr);
                const lat = parseFloat(latStr);
                if (isNaN(lat) || isNaN(lng)) continue;

                const pos = { lat, lng };
                limites.extend(pos);

                const marcador = new google.maps.Marker({
                    position: pos,
                    map: this.mapa,
                    title: 'Punto KML'
                });
                this.overlays.push(marcador);
            }
            this.mapa.fitBounds(limites);
            this.#mensaje('Puntos KML dibujados.', 'ok');
            return;
        }

        this.#mensaje('El KML no contiene LineString ni Point con coordinates válidos.', 'warn');
    }


    #mensaje(texto, tipo = 'info') {
        // Seguridad: si aún no tenemos referencia al contenedor del mapa, salimos
        if (!this.contenedorMapa) return;

        // Crea el mensaje
        const p = document.createElement('p');
        p.textContent = texto;
        p.className = `msg msg--${tipo}`;

        // Inserta el <p> JUSTO ANTES del <div> del mapa (encima)
        this.contenedorMapa.insertAdjacentElement('beforebegin', p);

        // Auto-ocultar tras unos segundos (opcional)
        setTimeout(() => p.remove(), 4000);
    }

}


function initMap() {
    // Esta función es llamada por la API de Google Maps al cargar el script
    // No hace nada aquí, ya que la inicialización del mapa se hace al cargar el KML
}