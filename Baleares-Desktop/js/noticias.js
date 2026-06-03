class Noticias {
    constructor(selectorContenedor, busqueda = 'Baleares') {
        this.$contenedor = $(selectorContenedor);
        this.busqueda = busqueda;
        this.url = 'https://api.thenewsapi.com/v1/news/top';
        this.token = '3mwVVLj7ZRIFZzsBlbXu9WjO69FPRRtWYGMXCUji';

        this.paramsBase = {
            api_token: this.token,
            search: this.busqueda,
            search_fields: 'title,description,main_text',
            language: 'es,en',
            sort: 'published_at',
            limit: 10
        };
    }

    async buscar() {
        try {
            const json = await $.ajax({
                url: this.url,
                method: 'GET',
                dataType: 'json',
                data: this.paramsBase
            });

            const items = this.procesarInformacion(json);
            this.pintar(items);
        } catch (err) {
            console.error('Error en la consulta de noticias:', err);
            this.mostrarError();
        }
    }

    procesarInformacion(json) {
        const items = Array.isArray(json?.data) ? json.data : [];

        return items.map(a => ({
            titulo: a.title,
            entradilla: a.description || a.snippet || '',
            enlace: a.url,
            fuente: a.source,
            publicado: a.published_at
        }));
    }

    pintar(noticias) {
        this.$contenedor.empty();

        if (!noticias.length) {
            this.$contenedor.append(
                $('<article>').append(
                    $('<h3>').text('No hay noticias disponibles ahora mismo.')
                )
            );
            return;
        }

        noticias.forEach(noticia => this.pintarNoticia(noticia));
    }

    pintarNoticia(noticia) {
        const $articulo = $('<article>');

        $articulo.append(
            $('<h3>').text(noticia.titulo || 'Titular no disponible')
        );

        if (noticia.entradilla) {
            $articulo.append(
                $('<p>').text(noticia.entradilla)
            );
        }

        const $meta = $('<p>');

        if (noticia.enlace) {
            $meta.append(
                $('<a>', {
                    href: noticia.enlace,
                    target: '_blank',
                    rel: 'noopener noreferrer',
                    text: 'Leer en la fuente'
                })
            );
        }

        $meta.append(` — ${noticia.fuente || 'Fuente desconocida'}`);

        if (noticia.publicado) {
            const fecha = new Date(noticia.publicado);
            $meta.append(` — ${fecha.toLocaleString('es-ES')}`);
        }

        $articulo.append($meta);
        this.$contenedor.append($articulo);
    }

    mostrarError() {
        this.$contenedor.empty().append(
            $('<p>').text('No se pudieron cargar las noticias. Inténtalo más tarde.')
        );
    }
}