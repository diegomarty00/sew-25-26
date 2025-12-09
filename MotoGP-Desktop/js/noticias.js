class Noticias {
    constructor(busqueda = 'MotoGP') {
        // Término de búsqueda y URL base (Top Stories) de TheNewsApi
        this.busqueda = busqueda;
        this.url = 'https://api.thenewsapi.com/v1/news/top';
        this.token = '3mwVVLj7ZRIFZzsBlbXu9WjO69FPRRtWYGMXCUji';

        // Parámetros por defecto para la query
        this.paramsBase = {
            api_token: this.token,                 // autenticación (TheNewsApi)
            search: this.busqueda,                 // término de búsqueda
            search_fields: 'title,description,main_text',
            categories: 'sports',                  // filtramos por deportes
            language: 'es,en',                     // español e inglés
            sort: 'published_at',                  // orden por fecha de publicación
            limit: 10                              // número máximo de resultados
        };
    }

    async buscar() {
        // Construimos la query string limpiamente
        const qs = new URLSearchParams(this.paramsBase); // URLSearchParams recomendado
        const endpoint = `${this.url}?${qs.toString()}`;

        try {
            const resp = await fetch(endpoint);
            if (!resp.ok) {
                throw new Error(`HTTP ${resp.status} al consultar TheNewsApi`);
            }
            const json = await resp.json(); // Promesa resuelta con JSON
            return json;
        } catch (err) {
            console.error('Error en la consulta de noticias:', err);
            throw err;
        }
    }

    procesarInformacion(json) {
        const items = Array.isArray(json?.data) ? json.data : [];
        return items.map(a => ({
            titulo: a.title,
            entradilla: a.description || a.snippet || '',
            enlace: a.url,
            fuente: a.source,
            imagen: a.image_url,
            publicado: a.published_at
        }));
    }

    pintar(noticias, contenedorSelector = '#noticias-contenido') {
        const $sec = $(contenedorSelector);

        if (!noticias.length) {
            $sec.append(
                $('<article/>', { class: 'no-news' }).append(
                    $('<h3/>').text('No hay noticias disponibles ahora mismo.')
                )
            );
            return;
        }

        noticias.forEach(n => {
            const $art = $('<article/>', { class: 'noticia' });
            if (n.imagen) {
                $art.append($('<img/>', { src: n.imagen, alt: n.titulo || 'Imagen', loading: 'lazy' }));
            }
            $art.append($('<h3/>').text(n.titulo || 'Titular no disponible'));
            if (n.entradilla) $art.append($('<p/>', { class: 'entradilla' }).text(n.entradilla));

            const $meta = $('<p/>', { class: 'fuente' })
                .append($('<a/>', { href: n.enlace, target: '_blank', rel: 'noopener noreferrer', text: 'Leer en la fuente' }))
                .append(` — ${n.fuente || 'Fuente desconocida'}`);

            if (n.publicado) {
                const fecha = new Date(n.publicado);
                $meta.append(` — ${fecha.toLocaleString()}`);
            }

            $art.append($meta);
            $sec.append($art);
        });
    }
}

// Inicialización al cargar el documento
document.addEventListener('DOMContentLoaded', async () => {
    // Instanciamos con la búsqueda "MotoGP"
    const noticias = new Noticias('MotoGP');

    try {
        const json = await noticias.buscar();
        const items = noticias.procesarInformacion(json);
        noticias.pintar(items, '#noticias');
    } catch (err) {
        // Mensaje de error visible
        const $sec = $('#noticias');
        $sec.append(
            $('<p/>', { class: 'error', text: 'No se pudieron cargar las noticias. Inténtalo más tarde.' })
        );
    }
});
