class Carrusel {
    constructor(busqueda) {
        this.busqueda = busqueda;   // término para API de Flickr
        this.actual = 0;            // índice de foto actual
        this.maximo = 4;            // nº de fotos del carrusel (ver nota abajo)
        this.apiKey = 'a09054d9591e63f243358ca426c788d1';
    }

    getFotografias() {
        if (this.apiKey) {
            return $.getJSON('https://api.flickr.com/services/rest/', {
                method: 'flickr.photos.search',
                api_key: this.apiKey,
                text: this.busqueda,
                safe_search: 1,
                content_type: 1,
                media: 'photos',
                sort: 'relevance',
                per_page: this.maximo,
                page: 1,
                extras: 'url_z,title',        // URL directa 640px + título
                format: 'json',
                nojsoncallback: 1
            })
                .then(json => this.procesarJSONFotografias(json))
                .then(() => this.mostrarFotografias())
                .catch(err => console.error('Error Flickr API:', err));
        }
    }

    procesarJSONFotografias(json) {
        const photos = (json.photos && json.photos.photo)
            ? json.photos.photo.slice(0, this.maximo)
            : [];

        // Preferimos url_z (640px); si no existe, construimos con _z
        this.fotos = photos.map(p => ({
            url: p.url_z
                ? p.url_z
                : `https://live.staticflickr.com/${p.server}/${p.id}_${p.secret}_z.jpg`,
            title: p.title || this.busqueda
        }));
    }


    mostrarFotografias() {
        if (!this.fotos.length) return;

        // 1) Intenta usar el contenedor ya existente en el HTML
        this.$section = $('section.carrusel');

        // 2) Si no existe (por si algún HTML no lo tiene), créalo y colócalo arriba
        if (!this.$section.length) {
            this.$section = $('<section class="carrusel" aria-label="Carrusel de imágenes de MotoGP"></section>');
            const $noticias = $('#noticias');
            if ($noticias.length) {
                $noticias.before(this.$section);  // lo pone por encima de noticias
            } else {
                $('main').prepend(this.$section); // o al principio de <main>
            }
        }

        // 3) Construye el contenido del carrusel
        this.$h2 = $(`<h2>Imágenes del circuito de ${this.busqueda}</h2>`);
        this.$img = $('<img/>', {
            src: this.fotos[0].url,
            alt: this.fotos[0].title,
            loading: 'lazy'
        });

        // 4) Rellena el contenedor (sin volver a añadirlo al final del main)
        this.$section.empty().append(this.$h2, this.$img);

        this.actual = 0;
        this.intervalId = setInterval(this.cambiarFotografia.bind(this), 3000);
    }

    cambiarFotografia() {
        if (!this.fotos.length) return;
        this.actual = (this.actual + 1) % this.fotos.length;

        const foto = this.fotos[this.actual];
        this.$img.attr('src', foto.url);
        this.$img.attr('alt', foto.title);
    }
}