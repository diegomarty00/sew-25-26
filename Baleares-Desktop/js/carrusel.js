class Carrusel {
    constructor(selectorContenedor) {
        this.$contenedor = $(selectorContenedor);
        this.indiceActual = 0;
        this.intervalo = null;
        this.tiempo = 5000;
        this.enMovimiento = true;

        this.imagenes = [
            {
                ruta: "multimedia/imagenes/Fondo.png",
                textoAlternativo: "Mapa de situación de las Islas Baleares en el mar Mediterráneo",
                titulo: "Mapa de situación de las Islas Baleares"
            },
            {
                ruta: "multimedia/imagenes/Fondo.jpg",
                textoAlternativo: "Vista turística de Mallorca con costa mediterránea",
                titulo: "Mallorca"
            },
            {
                ruta: "multimedia/imagenes/Fondo.jpg",
                textoAlternativo: "Paisaje natural de Menorca con una cala de aguas claras",
                titulo: "Menorca"
            },
            {
                ruta: "multimedia/imagenes/Fondo.jpg",
                textoAlternativo: "Paisaje turístico de Ibiza junto al mar",
                titulo: "Ibiza"
            },
            {
                ruta: "multimedia/imagenes/Fondo.jpg",
                textoAlternativo: "Playa de Formentera con arena clara y agua turquesa",
                titulo: "Formentera"
            },
            {
                ruta: "multimedia/imagenes/Fondo.jpg",
                textoAlternativo: "Vista de Palma, capital de las Islas Baleares",
                titulo: "Palma"
            }
        ];
    }

    iniciar() {
        this.crearEstructura();
        this.mostrarImagenActual();
        this.iniciarMovimientoAutomatico();
    }

    crearEstructura() {
        this.$titulo = $("<h2>").text("Recursos turísticos de las Islas Baleares");

        this.$figura = $("<figure>");
        this.$imagen = $("<img>", {
            src: this.imagenes[0].ruta,
            alt: this.imagenes[0].textoAlternativo
        });

        this.$pie = $("<figcaption>", {
            "aria-live": "polite"
        });

        this.$botonAnterior = $("<button>", {
            type: "button",
            text: "Anterior",
            "aria-label": "Mostrar imagen anterior del carrusel"
        });

        this.$botonSiguiente = $("<button>", {
            type: "button",
            text: "Siguiente",
            "aria-label": "Mostrar imagen siguiente del carrusel"
        });

        this.$botonMovimiento = $("<button>", {
            type: "button",
            text: "Pausar",
            "aria-label": "Pausar el movimiento automático del carrusel"
        });

        this.$botonAnterior.on("click", () => this.mostrarAnterior());
        this.$botonSiguiente.on("click", () => this.mostrarSiguiente());
        this.$botonMovimiento.on("click", () => this.alternarMovimiento());

        this.$figura.append(this.$imagen, this.$pie);
        this.$contenedor.empty();
        this.$contenedor.append(
            this.$titulo,
            this.$figura,
            this.$botonAnterior,
            this.$botonSiguiente,
            this.$botonMovimiento
        );
    }

    mostrarImagenActual() {
        const imagen = this.imagenes[this.indiceActual];

        this.$imagen.attr("src", imagen.ruta);
        this.$imagen.attr("alt", imagen.textoAlternativo);
        this.$pie.text(`${imagen.titulo}. Imagen ${this.indiceActual + 1} de ${this.imagenes.length}.`);
    }

    mostrarSiguiente() {
        this.indiceActual = (this.indiceActual + 1) % this.imagenes.length;
        this.mostrarImagenActual();
    }

    mostrarAnterior() {
        this.indiceActual = this.indiceActual - 1;

        if (this.indiceActual < 0) {
            this.indiceActual = this.imagenes.length - 1;
        }

        this.mostrarImagenActual();
    }

    iniciarMovimientoAutomatico() {
        this.intervalo = window.setInterval(() => this.mostrarSiguiente(), this.tiempo);
    }

    detenerMovimientoAutomatico() {
        window.clearInterval(this.intervalo);
        this.intervalo = null;
    }

    alternarMovimiento() {
        if (this.enMovimiento) {
            this.detenerMovimientoAutomatico();
            this.enMovimiento = false;
            this.$botonMovimiento.text("Reanudar");
            this.$botonMovimiento.attr("aria-label", "Reanudar el movimiento automático del carrusel");
        } else {
            this.iniciarMovimientoAutomatico();
            this.enMovimiento = true;
            this.$botonMovimiento.text("Pausar");
            this.$botonMovimiento.attr("aria-label", "Pausar el movimiento automático del carrusel");
        }
    }
}