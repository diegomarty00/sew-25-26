"use strict";

class VisorImagenes {
    constructor() {
        this.$dialogo = $("<dialog>", {
            "aria-label": "Visor de imágenes"
        });

        this.$figura = $("<figure>");
        this.$imagen = $("<img>", {
            src: "",
            alt: ""
        });

        this.$pie = $("<figcaption>");

        this.$botonCerrar = $("<button>", {
            type: "button",
            text: "Cerrar imagen",
            "aria-label": "Cerrar imagen ampliada"
        });

        this.$botonCerrar.on("click", () => this.cerrar());

        this.$dialogo.on("click", (evento) => {
            if (evento.target === this.$dialogo.get(0)) {
                this.cerrar();
            }
        });

        this.$figura.append(this.$imagen);
        this.$figura.append(this.$pie);

        this.$dialogo.append(this.$figura);
        this.$dialogo.append(this.$botonCerrar);

        $("body").append(this.$dialogo);
    }

    abrir(imagen) {
        this.$imagen.attr("src", imagen.ruta);
        this.$imagen.attr("alt", imagen.textoAlternativo);
        this.$pie.text(imagen.titulo);

        const dialogo = this.$dialogo.get(0);

        if (typeof dialogo.showModal === "function") {
            dialogo.showModal();
        } else {
            this.$dialogo.attr("open", "open");
        }

        this.$botonCerrar.trigger("focus");
    }

    cerrar() {
        const dialogo = this.$dialogo.get(0);

        if (typeof dialogo.close === "function") {
            dialogo.close();
        } else {
            this.$dialogo.removeAttr("open");
        }
    }
}

class GaleriaImagenes {
    constructor(contenedor, imagenes, opciones = {}) {
        this.$contenedor = $(contenedor);
        this.imagenes = imagenes;
        this.indiceActual = 0;

        this.titulo = opciones.titulo || "Fotografías";
        this.texto = opciones.texto || "";
        this.mostrarTexto = opciones.mostrarTexto || false;

        this.automatico = opciones.automatico || false;
        this.tiempo = opciones.tiempo || 5000;
        this.intervalo = null;
        this.enMovimiento = this.automatico;

        this.conAmpliacion = opciones.conAmpliacion || false;

        this.$titulo = null;
        this.$texto = null;
        this.$figura = null;
        this.$imagen = null;
        this.$pie = null;
        this.$botonAnterior = null;
        this.$botonSiguiente = null;
        this.$botonMovimiento = null;
    }

    iniciar() {
        if (!this.$contenedor.length) {
            console.error("No se encontró el contenedor de la galería.");
            return;
        }

        this.$contenedor.empty();

        if (!Array.isArray(this.imagenes) || this.imagenes.length === 0) {
            this.$contenedor.append($("<p>").text("No hay fotografías disponibles."));
            return;
        }

        this.crearEstructura();
        this.mostrarImagenActual();

        if (this.automatico && this.imagenes.length > 1) {
            this.iniciarMovimientoAutomatico();
        }
    }

    crearEstructura() {
        this.$titulo = $("<h6>").text(this.titulo);

        this.$figura = $("<figure>");

        this.$imagen = $("<img>", {
            src: this.imagenes[0].ruta,
            alt: this.imagenes[0].textoAlternativo,
            loading: "lazy",
            decoding: "async"
        });

        this.$pie = $("<figcaption>", {
            "aria-live": "polite"
        });

        if (this.conAmpliacion) {
            this.$imagen.attr("tabindex", "0");
            this.$imagen.attr("role", "button");
            this.$imagen.attr("aria-label", "Ampliar fotografía");

            this.$imagen.on("click", () => this.abrirImagenActual());

            this.$imagen.on("keydown", (evento) => {
                if (evento.key === "Enter" || evento.key === " ") {
                    evento.preventDefault();
                    this.abrirImagenActual();
                }
            });
        }

        this.$figura.append(this.$imagen);
        this.$figura.append(this.$pie);

        this.$contenedor.append(this.$titulo);

        if (this.mostrarTexto && this.texto.length > 0) {
            this.$texto = $("<p>").text(this.texto);
            this.$contenedor.append(this.$texto);
        }

        this.$contenedor.append(this.$figura);

        if (this.imagenes.length > 1) {
            this.crearBotonesNavegacion();
        }

        if (this.automatico && this.imagenes.length > 1) {
            this.crearBotonMovimiento();
        }
    }

    crearBotonesNavegacion() {
        this.$botonAnterior = $("<button>", {
            type: "button",
            text: "Anterior",
            "aria-label": "Mostrar fotografía anterior"
        });

        this.$botonSiguiente = $("<button>", {
            type: "button",
            text: "Siguiente",
            "aria-label": "Mostrar fotografía siguiente"
        });

        this.$botonAnterior.on("click", () => this.mostrarAnterior());
        this.$botonSiguiente.on("click", () => this.mostrarSiguiente());

        this.$contenedor.append(this.$botonAnterior);
        this.$contenedor.append(this.$botonSiguiente);
    }

    crearBotonMovimiento() {
        this.$botonMovimiento = $("<button>", {
            type: "button",
            text: "Pausar",
            "aria-label": "Pausar el movimiento automático de la galería"
        });

        this.$botonMovimiento.on("click", () => this.alternarMovimiento());

        this.$contenedor.append(this.$botonMovimiento);
    }

    mostrarImagenActual() {
        const imagen = this.imagenes[this.indiceActual];

        this.$imagen.attr("src", imagen.ruta);
        this.$imagen.attr("alt", imagen.textoAlternativo);

        if (this.conAmpliacion) {
            this.$imagen.attr("aria-label", "Ampliar fotografía: " + imagen.titulo);
        }

        this.$pie.text(
            imagen.titulo +
            ". Imagen " +
            (this.indiceActual + 1) +
            " de " +
            this.imagenes.length +
            "."
        );
    }

    mostrarSiguiente() {
        this.indiceActual = (this.indiceActual + 1) % this.imagenes.length;
        this.mostrarImagenActual();
    }

    mostrarAnterior() {
        this.indiceActual--;

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
            this.$botonMovimiento.attr("aria-label", "Reanudar el movimiento automático de la galería");
        } else {
            this.iniciarMovimientoAutomatico();
            this.enMovimiento = true;
            this.$botonMovimiento.text("Pausar");
            this.$botonMovimiento.attr("aria-label", "Pausar el movimiento automático de la galería");
        }
    }

    abrirImagenActual() {
        if (!window.visorImagenes) {
            window.visorImagenes = new VisorImagenes();
        }

        window.visorImagenes.abrir(this.imagenes[this.indiceActual]);
    }
}