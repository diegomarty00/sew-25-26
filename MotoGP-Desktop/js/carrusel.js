
// js/carrusel.js
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

        this.$article = $('<article class="carrusel"></article>');
        this.$h2 = $(`<h2>Imágenes del circuito de ${this.busqueda}</h2>`);
        this.$img = $('<img>', {
            src: this.fotos[0].url,
            alt: this.fotos[0].title,
            loading: 'lazy'
        });

        this.$article.append(this.$h2, this.$img);
        $('main').append(this.$article);

        this.actual = 0;

        // Cambia la fotografía cada 3 segundos
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