"use strict";

/*
 * rutas.xml del proyecto Baleares. * Diego Martínez Menéndez - UO270457
 *   Permite visualizar la información XML como HTML, cargar altimetría SVG
 *   y dibujar planimetría KML sobre Google Maps.
 */

class Rutas {
    constructor() {
        this.rutaXMLPorDefecto = "xml/rutas.xml";
        this.inputXML = $("main > section:nth-of-type(1) input").first();
        this.contenedor = $("main > section:nth-of-type(1) article").first();

        if (!this.comprobarApiFile()) {
            return;
        }

        if (this.inputXML.length === 0 || this.contenedor.length === 0) {
            console.error("No se encontró el input XML o el article de destino.");
            return;
        }

        this.inputXML.on("change", (evento) => this.leerArchivoXML(evento));

        this.cargarXMLPorDefecto();
    }

    comprobarApiFile() {
        const soportado = !!(window.File && window.FileReader && window.FileList && window.Blob);

        if (!soportado) {
            $("main").prepend(
                $("<p></p>").text("Error: su navegador no soporta el API File.")
            );
        }

        return soportado;
    }

    cargarXMLPorDefecto() {
        $.ajax({
            url: this.rutaXMLPorDefecto,
            method: "GET",
            dataType: "xml",
            success: (xml) => this.procesarXML(xml),
            error: () => {
                this.contenedor.empty();
                this.contenedor.append($("<h3></h3>").text("Datos procesados:"));
                this.contenedor.append(
                    $("<p></p>").text(
                        "No se pudo cargar automáticamente xml/rutas.xml. Puede seleccionarlo manualmente."
                    )
                );
            }
        });
    }

    leerArchivoXML(evento) {
        const archivo = evento.target.files[0];

        if (!archivo) {
            return;
        }

        const lector = new FileReader();

        lector.onload = (e) => {
            const contenido = String(e.target.result || "");
            const parser = new DOMParser();
            const xml = parser.parseFromString(contenido, "application/xml");

            if (xml.querySelector("parsererror")) {
                this.mostrarErrorXML("El archivo XML no está bien formado.");
                return;
            }

            this.procesarXML(xml);
        };

        lector.readAsText(archivo, "utf-8");
    }

    procesarXML(xml) {
        const rutas = $(xml).find("ruta");

        this.contenedor.empty();
        this.contenedor.append($("<h3></h3>").text("Datos procesados:"));

        if (rutas.length === 0) {
            this.contenedor.append(
                $("<p></p>").text("No se encontraron rutas en el XML.")
            );
            return;
        }

        rutas.each((indice, ruta) => {
            this.escribirRuta($(ruta));
        });
    }

    escribirRuta(ruta) {
        const articleRuta = $("<article></article>");

        articleRuta.append($("<h4></h4>").text(ruta.attr("nombre")));
        articleRuta.append($("<p></p>").text(this.obtenerTexto(ruta, "descripcion")));

        articleRuta.append(this.crearDatosGenerales(ruta));
        articleRuta.append(this.crearInicioRuta(ruta));
        articleRuta.append(this.crearHitos(ruta));
        articleRuta.append(this.crearReferencias(ruta));
        articleRuta.append(this.crearRecursosGenerados(ruta));

        this.contenedor.append(articleRuta);
    }

    crearDatosGenerales(ruta) {
        const lista = $("<ul></ul>");
        const duracion = ruta.find("duracion").first();

        lista.append($("<li></li>").text("Tipo: " + this.obtenerTexto(ruta, "tipo")));
        lista.append($("<li></li>").text("Medio de transporte: " + this.obtenerTexto(ruta, "transporte")));
        lista.append($("<li></li>").text("Fecha de inicio: " + this.obtenerTexto(ruta, "fechaInicio")));
        lista.append($("<li></li>").text("Hora de inicio: " + this.obtenerTexto(ruta, "horaInicio")));
        lista.append(
            $("<li></li>").text(
                "Duración: " + duracion.text().trim() + " " + duracion.attr("unidad")
            )
        );
        lista.append($("<li></li>").text("Agencia: " + this.obtenerTexto(ruta, "agencia")));
        lista.append($("<li></li>").text("Personas adecuadas: " + this.obtenerTexto(ruta, "personasAdecuadas")));
        lista.append($("<li></li>").text("Recomendación: " + this.obtenerTexto(ruta, "recomendacion") + "/10"));

        return lista;
    }

    crearInicioRuta(ruta) {
        const inicio = ruta.find("inicio").first();
        const articleInicio = $("<article></article>");
        const lista = $("<ul></ul>");

        articleInicio.append($("<h5></h5>").text("Inicio de la ruta"));

        lista.append($("<li></li>").text("Lugar: " + inicio.find("lugar").first().text().trim()));
        lista.append($("<li></li>").text("Dirección: " + inicio.find("direccion").first().text().trim()));

        const coordenadas = inicio.find("coordenadasInicio").first();

        lista.append($("<li></li>").text("Longitud: " + this.obtenerCoordenada(coordenadas, "longitud") + " grados"));
        lista.append($("<li></li>").text("Latitud: " + this.obtenerCoordenada(coordenadas, "latitud") + " grados"));
        lista.append($("<li></li>").text("Altitud: " + this.obtenerCoordenada(coordenadas, "altitud") + " metros"));

        articleInicio.append(lista);

        return articleInicio;
    }

    crearHitos(ruta) {
        const articleHitos = $("<article></article>");
        const lista = $("<ol></ol>");

        articleHitos.append($("<h5></h5>").text("Hitos de la ruta"));

        ruta.find("hitos > hito").each((indice, hito) => {
            const hitoJQ = $(hito);
            const elemento = $("<li></li>");

            elemento.append($("<strong></strong>").text(hitoJQ.find("nombre").first().text().trim()));
            elemento.append(document.createTextNode(": " + hitoJQ.find("descripcion").first().text().trim()));

            elemento.append(this.crearDatosHito(hitoJQ));
            elemento.append(this.crearGaleriaFotos(hitoJQ));
            elemento.append(this.crearGaleriaVideos(hitoJQ));

            lista.append(elemento);
        });

        articleHitos.append(lista);

        return articleHitos;
    }

    crearDatosHito(hito) {
        const lista = $("<ul></ul>");
        const distancia = hito.find("distancia").first();
        const coordenadas = hito.find("coordenadasHito").first();

        lista.append(
            $("<li></li>").text(
                "Distancia desde el hito anterior: " +
                distancia.text().trim() +
                " " +
                distancia.attr("unidad")
            )
        );

        lista.append($("<li></li>").text("Longitud: " + this.obtenerCoordenada(coordenadas, "longitud") + " grados"));
        lista.append($("<li></li>").text("Latitud: " + this.obtenerCoordenada(coordenadas, "latitud") + " grados"));
        lista.append($("<li></li>").text("Altitud: " + this.obtenerCoordenada(coordenadas, "altitud") + " metros"));

        return lista;
    }

    crearGaleriaFotos(hito) {
        const contenedor = $("<section></section>");
        const nombreHito = hito.find("nombre").first().text().trim();
        const fotos = hito.find("galeriaFotos > foto");

        const imagenes = [];

        fotos.each((indice, foto) => {
            const rutaFoto = $(foto).text().trim();

            if (rutaFoto.length > 0) {
                imagenes.push({
                    ruta: rutaFoto,
                    textoAlternativo: "Fotografía del hito " + nombreHito,
                    titulo: nombreHito
                });
            }
        });

        contenedor.attr("aria-label", "Fotografías del hito " + nombreHito);
        contenedor.append($("<h6></h6>").text("Fotografías"));

        if (imagenes.length === 0) {
            contenedor.append($("<p></p>").text("No hay fotografías disponibles para este hito."));
            return contenedor;
        }

        let indiceActual = 0;

        const figure = $("<figure></figure>");

        const imagen = $("<img>", {
            src: imagenes[indiceActual].ruta,
            alt: imagenes[indiceActual].textoAlternativo,
            loading: "lazy",
            decoding: "async"
        });

        const pie = $("<figcaption></figcaption>", {
            "aria-live": "polite"
        });

        const actualizarImagen = () => {
            const imagenActual = imagenes[indiceActual];

            imagen.attr("src", imagenActual.ruta);
            imagen.attr("alt", imagenActual.textoAlternativo);
            pie.text(
                imagenActual.titulo +
                ". Imagen " +
                (indiceActual + 1) +
                " de " +
                imagenes.length +
                "."
            );
        };

        const botonAnterior = $("<button></button>", {
            type: "button",
            text: "Anterior",
            "aria-label": "Mostrar fotografía anterior del hito " + nombreHito
        });

        const botonSiguiente = $("<button></button>", {
            type: "button",
            text: "Siguiente",
            "aria-label": "Mostrar fotografía siguiente del hito " + nombreHito
        });

        botonAnterior.on("click", () => {
            indiceActual--;

            if (indiceActual < 0) {
                indiceActual = imagenes.length - 1;
            }

            actualizarImagen();
        });

        botonSiguiente.on("click", () => {
            indiceActual = (indiceActual + 1) % imagenes.length;
            actualizarImagen();
        });

        figure.append(imagen);
        figure.append(pie);

        contenedor.append(figure);

        if (imagenes.length > 1) {
            contenedor.append(botonAnterior);
            contenedor.append(botonSiguiente);
        }

        actualizarImagen();

        return contenedor;
    }

    crearGaleriaVideos(hito) {
        const contenedor = $("<section></section>");
        const videos = hito.find("galeriaVideos > video");

        if (videos.length === 0) {
            return contenedor;
        }

        contenedor.append($("<h6></h6>").text("Vídeos"));

        videos.each((indice, video) => {
            const rutaVideo = $(video).text().trim();

            if (rutaVideo.length === 0) {
                return;
            }

            const reproductor = $("<video></video>");
            const source = $("<source>");

            reproductor.attr("controls", "controls");
            reproductor.attr("preload", "metadata");

            source.attr("src", rutaVideo);
            source.attr("type", this.obtenerTipoVideo(rutaVideo));

            reproductor.append(source);
            reproductor.append("El navegador no puede reproducir el vídeo del hito.");

            contenedor.append(reproductor);
        });

        return contenedor;
    }

    crearReferencias(ruta) {
        const articleReferencias = $("<article></article>");
        const lista = $("<ul></ul>");

        articleReferencias.append($("<h5></h5>").text("Referencias"));

        ruta.find("referencias > referencia").each((indice, referencia) => {
            const url = $(referencia).text().trim();
            const enlace = $("<a></a>");

            enlace.attr("href", url);
            enlace.text(url);

            lista.append($("<li></li>").append(enlace));
        });

        articleReferencias.append(lista);

        return articleReferencias;
    }

    crearRecursosGenerados(ruta) {
        const articleRecursos = $("<article></article>");
        const lista = $("<ul></ul>");

        const planimetria = ruta.find("planimetria").first().text().trim();
        const altimetria = ruta.find("altimetria").first().text().trim();

        const rutaKML = "xml/" + planimetria;
        const rutaSVG = "xml/" + altimetria;

        articleRecursos.append($("<h5></h5>").text("Planimetría y altimetría"));

        lista.append(
            $("<li></li>").append(
                $("<a></a>")
                    .attr("href", rutaKML)
                    .text("Archivo KML: " + planimetria)
            )
        );

        lista.append(
            $("<li></li>").append(
                $("<a></a>")
                    .attr("href", rutaSVG)
                    .text("Archivo SVG: " + altimetria)
            )
        );

        articleRecursos.append(lista);

        const botonKML = $("<button></button>").text("Mostrar KML en el mapa");
        botonKML.on("click", () => {
            if (window.cargadorKML) {
                window.cargadorKML.cargarKMLDesdeRuta(rutaKML);
            }
        });

        const botonSVG = $("<button></button>").text("Mostrar altimetría SVG");
        botonSVG.on("click", () => {
            if (window.cargadorSVG) {
                window.cargadorSVG.cargarSVGDesdeRuta(rutaSVG);
            }
        });

        articleRecursos.append(botonKML);
        articleRecursos.append(botonSVG);

        return articleRecursos;
    }

    obtenerTexto(ruta, etiqueta) {
        const nodo = ruta.find(etiqueta).first();

        if (nodo.length === 0) {
            return "";
        }

        return nodo.text().trim();
    }

    obtenerCoordenada(coordenadas, etiqueta) {
        const nodo = coordenadas.find(etiqueta).first();

        if (nodo.length === 0) {
            return "";
        }

        return nodo.text().trim();
    }

    obtenerTipoVideo(rutaVideo) {
        const rutaMinusculas = rutaVideo.toLowerCase();

        if (rutaMinusculas.endsWith(".mp4")) {
            return "video/mp4";
        }

        if (rutaMinusculas.endsWith(".webm")) {
            return "video/webm";
        }

        if (rutaMinusculas.endsWith(".mov")) {
            return "video/quicktime";
        }

        if (rutaMinusculas.endsWith(".ogv")) {
            return "video/ogg";
        }

        return "video/mp4";
    }

    mostrarErrorXML(mensaje) {
        this.contenedor.empty();
        this.contenedor.append($("<h3></h3>").text("Datos procesados:"));
        this.contenedor.append($("<p></p>").text(mensaje));
    }
}

class CargadorSVG {
    constructor() {
        this.seccionSVG = $("main > section:nth-of-type(2)").first();
        this.inputSVG = $("main > section:nth-of-type(2) input").first();

        if (this.inputSVG.length > 0) {
            this.inputSVG.on("change", (evento) => this.leerArchivoSVG(evento));
        }
    }

    leerArchivoSVG(evento) {
        const archivo = evento.target.files[0];

        if (!archivo) {
            return;
        }

        const lector = new FileReader();

        lector.onload = () => {
            const contenido = String(lector.result || "");
            this.insertarSVGDesdeTexto(contenido);
        };

        lector.readAsText(archivo, "utf-8");
    }

    cargarSVGDesdeRuta(rutaSVG) {
        $.ajax({
            url: rutaSVG,
            method: "GET",
            dataType: "text",
            success: (contenido) => this.insertarSVGDesdeTexto(contenido),
            error: () => this.mostrarMensaje("No se pudo cargar el archivo SVG: " + rutaSVG)
        });
    }

    insertarSVGDesdeTexto(contenido) {
        this.limpiarSVG();

        const parser = new DOMParser();
        const documentoSVG = parser.parseFromString(contenido, "image/svg+xml");

        if (documentoSVG.querySelector("parsererror")) {
            this.mostrarMensaje("El archivo SVG no está bien formado.");
            return;
        }

        const svg = documentoSVG.documentElement;
        this.seccionSVG.append(svg);
    }

    limpiarSVG() {
        this.seccionSVG.find("svg").remove();
        this.seccionSVG.find("object").remove();
        this.seccionSVG.find("p[data-mensaje='svg']").remove();
    }

    mostrarMensaje(texto) {
        this.seccionSVG.append(
            $("<p></p>")
                .attr("data-mensaje", "svg")
                .text(texto)
        );
    }
}

class CargadorKML {
    constructor() {
        this.mapa = null;
        this.overlays = [];

        this.seccionKML = $("main > section:nth-of-type(3)").first();
        this.contenedorMapa = $("main > section:nth-of-type(3) > div").get(0);
        this.inputKML = $("main > section:nth-of-type(3) input").first();

        if (this.inputKML.length > 0) {
            this.inputKML.on("change", (evento) => this.leerArchivoKML(evento));
        }
    }

    inicializarMapa() {
        if (!this.contenedorMapa) {
            console.error("No se encontró el contenedor del mapa.");
            return;
        }

        if (typeof google === "undefined" || typeof google.maps === "undefined") {
            this.mostrarMensaje("No se ha cargado Google Maps.");
            return;
        }

        this.mapa = new google.maps.Map(this.contenedorMapa, {
            zoom: 8,
            center: { lat: 39.6, lng: 2.9 },
            mapTypeId: "terrain"
        });
    }

    leerArchivoKML(evento) {
        const archivo = evento.target.files[0];

        if (!archivo) {
            return;
        }

        const lector = new FileReader();

        lector.onload = () => {
            const contenido = String(lector.result || "");
            this.insertarKMLDesdeTexto(contenido);
        };

        lector.readAsText(archivo, "utf-8");
    }

    cargarKMLDesdeRuta(rutaKML) {
        $.ajax({
            url: rutaKML,
            method: "GET",
            dataType: "text",
            success: (contenido) => this.insertarKMLDesdeTexto(contenido),
            error: () => this.mostrarMensaje("No se pudo cargar el archivo KML: " + rutaKML)
        });
    }

    insertarKMLDesdeTexto(contenido) {
        const parser = new DOMParser();
        const documentoKML = parser.parseFromString(contenido, "application/xml");

        if (documentoKML.querySelector("parsererror")) {
            this.mostrarMensaje("El archivo KML no está bien formado.");
            return;
        }

        this.insertarCapaKML(documentoKML);
    }

    insertarCapaKML(documentoKML) {
        if (!this.mapa) {
            this.inicializarMapa();
        }

        if (!this.mapa) {
            return;
        }

        this.limpiarMapa();

        const coordenadas = this.extraerCoordenadas(documentoKML);

        if (coordenadas.length < 2) {
            this.mostrarMensaje("El KML no contiene suficientes coordenadas para dibujar una ruta.");
            return;
        }

        const limites = new google.maps.LatLngBounds();

        for (let i = 0; i < coordenadas.length; i++) {
            limites.extend(coordenadas[i]);
        }

        const lineaRuta = new google.maps.Polyline({
            path: coordenadas,
            geodesic: true,
            strokeColor: "#8F3A2F",
            strokeOpacity: 1.0,
            strokeWeight: 4
        });

        lineaRuta.setMap(this.mapa);
        this.overlays.push(lineaRuta);

        const marcadorInicio = new google.maps.Marker({
            position: coordenadas[0],
            map: this.mapa,
            title: "Inicio de la ruta"
        });

        const marcadorFin = new google.maps.Marker({
            position: coordenadas[coordenadas.length - 1],
            map: this.mapa,
            title: "Fin de la ruta"
        });

        this.overlays.push(marcadorInicio);
        this.overlays.push(marcadorFin);

        this.mapa.fitBounds(limites);
        this.mostrarMensaje("KML cargado y dibujado correctamente.");
    }

    extraerCoordenadas(documentoKML) {
        const puntos = [];
        const etiquetasCoordenadas = Array.from(
            documentoKML.getElementsByTagName("coordinates")
        );

        for (let i = 0; i < etiquetasCoordenadas.length; i++) {
            const texto = (etiquetasCoordenadas[i].textContent || "").trim();
            const coordenadas = texto.split(/\s+/);

            for (let j = 0; j < coordenadas.length; j++) {
                const partes = coordenadas[j].split(",");
                const longitud = parseFloat(partes[0]);
                const latitud = parseFloat(partes[1]);

                if (!isNaN(latitud) && !isNaN(longitud)) {
                    puntos.push({ lat: latitud, lng: longitud });
                }
            }
        }

        return puntos;
    }

    limpiarMapa() {
        for (let i = 0; i < this.overlays.length; i++) {
            if (this.overlays[i] && typeof this.overlays[i].setMap === "function") {
                this.overlays[i].setMap(null);
            }
        }

        this.overlays = [];
        this.seccionKML.find("p[data-mensaje='kml']").remove();
    }

    mostrarMensaje(texto) {
        this.seccionKML.find("p[data-mensaje='kml']").remove();

        this.seccionKML.append(
            $("<p></p>")
                .attr("data-mensaje", "kml")
                .text(texto)
        );
    }
}

function initMap() {
    if (window.cargadorKML) {
        window.cargadorKML.inicializarMapa();
    }
}

$(document).ready(function () {
    window.rutas = new Rutas();
    window.cargadorSVG = new CargadorSVG();
    window.cargadorKML = new CargadorKML();

    if (typeof google !== "undefined" && typeof google.maps !== "undefined") {
        window.cargadorKML.inicializarMapa();
    }
});
