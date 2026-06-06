class Meteorologia {
    constructor(selectorActual, selectorPrevision) {
        this.$actual = $(selectorActual);
        this.$prevision = $(selectorPrevision);

        this.ciudad = "Palma";
        this.provincia = "Islas Baleares";

        this.latitud = 39.5696;
        this.longitud = 2.6502;

        this.url = "https://api.open-meteo.com/v1/forecast";

        this.parametros = {
            latitude: this.latitud,
            longitude: this.longitud,
            current_weather: true,
            daily: "temperature_2m_max,temperature_2m_min,precipitation_probability_max,windspeed_10m_max,weathercode",
            timezone: "Europe/Madrid",
            forecast_days: 7
        };
    }

    consultar() {
        if (!this.$actual.length || !this.$prevision.length) {
            console.error("No se encontraron los contenedores de meteorología.");
            return;
        }

        $.ajax({
            url: this.url,
            method: "GET",
            dataType: "json",
            data: this.parametros,
            success: (respuesta) => this.procesarRespuesta(respuesta),
            error: () => this.mostrarError()
        });
    }

    procesarRespuesta(respuesta) {
        this.$actual.find("article").remove();
        this.$prevision.find("article").remove();

        this.mostrarTiempoActual(respuesta.current_weather);
        this.mostrarPrevision(respuesta.daily);
    }

    mostrarTiempoActual(datos) {
        const $articulo = $("<article>");

        $articulo.append(
            $("<h3>").text(`Tiempo actual en ${this.ciudad}`)
        );

        if (!datos) {
            $articulo.append(
                $("<p>").text("No hay datos meteorológicos actuales disponibles.")
            );

            this.$actual.append($articulo);
            return;
        }

        const $lista = $("<dl>");

        this.agregarDato($lista, "Lugar", `${this.ciudad}, ${this.provincia}`);
        this.agregarDato($lista, "Temperatura actual", `${datos.temperature} ºC`);
        this.agregarDato($lista, "Velocidad del viento", `${datos.windspeed} km/h`);
        this.agregarDato($lista, "Dirección del viento", `${datos.winddirection} grados`);
        this.agregarDato($lista, "Fecha y hora de la medición", this.formatearFechaHora(datos.time));

        $articulo.append($lista);
        this.$actual.append($articulo);
    }

    mostrarPrevision(datos) {
        const $articulo = $("<article>");

        $articulo.append(
            $("<h3>").text(`Previsión meteorológica en ${this.ciudad}`)
        );

        if (!datos || !datos.time || datos.time.length === 0) {
            $articulo.append(
                $("<p>").text("No hay datos de previsión disponibles.")
            );

            this.$prevision.append($articulo);
            return;
        }

        const $lista = $("<ul>");

        for (let i = 0; i < datos.time.length; i++) {
            const fecha = this.formatearFecha(datos.time[i]);
            const maxima = datos.temperature_2m_max[i];
            const minima = datos.temperature_2m_min[i];
            const lluvia = datos.precipitation_probability_max[i];
            const viento = datos.windspeed_10m_max[i];
            const descripcion = this.obtenerDescripcionTiempo(datos.weathercode[i]);

            const $elemento = $("<li>");

            const $titulo = $("<h4>").text(fecha);

            const $datosDia = $("<dl>");

            this.agregarDato($datosDia, "Estado del cielo", descripcion);
            this.agregarDato($datosDia, "Temperatura máxima", `${maxima} ºC`);
            this.agregarDato($datosDia, "Temperatura mínima", `${minima} ºC`);
            this.agregarDato($datosDia, "Probabilidad máxima de precipitación", `${lluvia} %`);
            this.agregarDato($datosDia, "Viento máximo", `${viento} km/h`);

            $elemento.append($titulo, $datosDia);
            $lista.append($elemento);
        }

        $articulo.append($lista);
        this.$prevision.append($articulo);
    }

    agregarDato($lista, termino, descripcion) {
        const $termino = $("<dt>").text(termino);
        const $descripcion = $("<dd>").text(descripcion);

        $lista.append($termino, $descripcion);
    }

    obtenerDescripcionTiempo(codigo) {
        const codigos = {
            0: "Cielo despejado",
            1: "Principalmente despejado",
            2: "Parcialmente nuboso",
            3: "Cubierto",
            45: "Niebla",
            48: "Niebla con escarcha",
            51: "Llovizna ligera",
            53: "Llovizna moderada",
            55: "Llovizna intensa",
            56: "Llovizna helada ligera",
            57: "Llovizna helada intensa",
            61: "Lluvia ligera",
            63: "Lluvia moderada",
            65: "Lluvia intensa",
            66: "Lluvia helada ligera",
            67: "Lluvia helada intensa",
            71: "Nevada ligera",
            73: "Nevada moderada",
            75: "Nevada intensa",
            77: "Granizo",
            80: "Chubascos ligeros",
            81: "Chubascos moderados",
            82: "Chubascos violentos",
            85: "Chubascos de nieve ligeros",
            86: "Chubascos de nieve intensos",
            95: "Tormenta",
            96: "Tormenta con granizo ligero",
            99: "Tormenta con granizo intenso"
        };

        return codigos[codigo] || "Estado meteorológico no disponible";
    }

    formatearFecha(fechaISO) {
        const fecha = new Date(fechaISO);

        return new Intl.DateTimeFormat("es-ES", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
        }).format(fecha);
    }

    formatearFechaHora(fechaISO) {
        const fecha = new Date(fechaISO);

        return new Intl.DateTimeFormat("es-ES", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        }).format(fecha);
    }

    mostrarError() {
        this.$actual.append(
            $("<article>").append(
                $("<h3>").text("Error al obtener la meteorología actual"),
                $("<p>").text("No se pudo obtener la información meteorológica actual. Inténtalo de nuevo más tarde.")
            )
        );

        this.$prevision.append(
            $("<article>").append(
                $("<h3>").text("Error al obtener la previsión meteorológica"),
                $("<p>").text("No se pudo obtener la previsión meteorológica. Inténtalo de nuevo más tarde.")
            )
        );
    }
}

class InicioMeteorologia {
    constructor() {
        this.selectorActual = 'section[aria-label="Información meteorológica actual de Palma"]';
        this.selectorPrevision = 'section[aria-label="Previsión meteorológica de Palma para los próximos 7 días"]';

        this.meteorologia = new Meteorologia(this.selectorActual, this.selectorPrevision);
    }

    iniciar() {
        this.meteorologia.consultar();
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const inicioMeteorologia = new InicioMeteorologia();
    inicioMeteorologia.iniciar();
});