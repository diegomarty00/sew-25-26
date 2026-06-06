class Juego {
    constructor(selectorContenedor) {
        this.contenedor = document.querySelector(selectorContenedor);
        this.preguntas = this.obtenerPreguntas();
        this.formulario = null;
        this.resultado = null;
    }

    iniciar() {
        if (this.contenedor === null) {
            console.error("No se encontró el contenedor del juego.");
            return;
        }

        this.crearFormulario();
    }

    obtenerPreguntas() {
        return [
            {
                texto: "¿Cuál es la capital de Baleares utilizada en la página de Meteorología?",
                opciones: ["Ibiza", "Palma", "Maó", "Ciutadella", "Manacor"],
                correcta: 1
            },
            {
                texto: "¿Cuántos días de previsión se muestran en la página de Meteorología?",
                opciones: ["3 días", "5 días", "7 días", "10 días", "14 días"],
                correcta: 2
            },
            {
                texto: "¿Qué queso es típico de las Islas Baleares?",
                opciones: ["Cabrales", "Mahón", "Picón", "Gamoneu", "Idiazabal"],
                correcta: 1
            },
            {
                texto: "¿Cuál de estos productos aparece en la tabla de productos gastronómicos?",
                opciones: [
                    "Frito mallorquín",
                    "Pomada menorquina",
                    "Arròs Brut",
                    "Pulpo a feira",
                    "Calçots"
                ],
                correcta: 1
            },
            {
                texto: "¿Qué expresión gastronómica se usa para desear buen provecho?",
                opciones: [
                    "Bon profit",
                    "Camí de Cavalls",
                    "Dalt Vila",
                    "Sa Mesquida",
                    "Ses Taules"
                ],
                correcta: 0
            },
            {
                texto: "En la Ruta monumental por Palma, ¿cuál es el hito con mayor altitud?",
                opciones: [
                    "Catedral de Mallorca",
                    "Palacio de la Almudaina",
                    "Parc de la Mar",
                    "Plaza Mayor de Palma",
                    "Lonja de Palma"
                ],
                correcta: 3
            },
            {
                texto: "En la ruta histórica por Dalt Vila, ¿cuál es el segundo hito que se visita?",
                opciones: [
                    "Baluarte de Santa Llúcia", 
                    "Portal de Ses Taules", 
                    "Baluarte de Sant Jaume", 
                    "Catedral de Santa María", 
                    "Plaza de Vila"],
                correcta: 4
            },
            {
                texto: "¿Cuál es la duración de la Ruta histórica por Dalt Vila?",
                opciones: ["1 hora", "2 horas", "3 horas", "4 horas", "5 horas"],
                correcta: 1
            },
            {
                texto: "¿Qué ruta tiene una recomendación de 10 sobre 10?",
                opciones: [
                    "Ruta monumental por Palma",
                    "Ruta histórica por Dalt Vila",
                    "Ruta natural por el Camí de Cavalls",
                    "Ruta gastronómica por Binissalem",
                    "Ruta marítima por Formentera"
                ],
                correcta: 2
            },
            {
                texto: "¿Cuantas fotos hay en el carrusel de imágenes de las Islas Baleares?",
                opciones: ["1", "2", "4", "6", "infinitas"],
                correcta: 3
            }
        ];
    }

    crearFormulario() {
        this.formulario = document.createElement("form");

        this.preguntas.forEach((pregunta, indicePregunta) => {
            const fieldset = this.crearPregunta(pregunta, indicePregunta);
            this.formulario.appendChild(fieldset);
        });

        const boton = document.createElement("button");
        boton.type = "button";
        boton.textContent = "Finalizar juego";
        boton.addEventListener("click", () => this.finalizar());

        this.resultado = document.createElement("p");
        this.resultado.setAttribute("role", "status");
        this.resultado.setAttribute("aria-live", "polite");

        this.formulario.appendChild(boton);
        this.formulario.appendChild(this.resultado);

        this.contenedor.appendChild(this.formulario);
    }

    crearPregunta(pregunta, indicePregunta) {
        const fieldset = document.createElement("fieldset");

        const legend = document.createElement("legend");
        legend.textContent = `${indicePregunta + 1}. ${pregunta.texto}`;
        fieldset.appendChild(legend);

        pregunta.opciones.forEach((opcion, indiceOpcion) => {
            const parrafo = this.crearOpcion(indicePregunta, indiceOpcion, opcion);
            fieldset.appendChild(parrafo);
        });

        return fieldset;
    }

    crearOpcion(indicePregunta, indiceOpcion, textoOpcion) {
        const parrafo = document.createElement("p");

        const identificador = `pregunta-${indicePregunta}-opcion-${indiceOpcion}`;

        const input = document.createElement("input");
        input.type = "radio";
        input.id = identificador;
        input.name = `pregunta-${indicePregunta}`;
        input.value = indiceOpcion;

        const label = document.createElement("label");
        label.setAttribute("for", identificador);
        label.textContent = textoOpcion;

        parrafo.appendChild(input);
        parrafo.appendChild(label);

        return parrafo;
    }

    finalizar() {
        if (!this.estanTodasRespondidas()) {
            this.resultado.textContent = "Debes responder todas las preguntas antes de finalizar el juego.";
            return;
        }

        const puntuacion = this.calcularPuntuacion();
        this.resultado.textContent = `Has obtenido una puntuación de ${puntuacion} sobre 10.`;
    }

    estanTodasRespondidas() {
        for (let i = 0; i < this.preguntas.length; i++) {
            const respuesta = document.querySelector(`input[name="pregunta-${i}"]:checked`);

            if (respuesta === null) {
                return false;
            }
        }

        return true;
    }

    calcularPuntuacion() {
        let puntuacion = 0;

        this.preguntas.forEach((pregunta, indicePregunta) => {
            const respuesta = document.querySelector(`input[name="pregunta-${indicePregunta}"]:checked`);

            if (Number(respuesta.value) === pregunta.correcta) {
                puntuacion++;
            }
        });

        return puntuacion;
    }
}

class InicioJuego {
    constructor() {
        this.selectorJuego = 'section[aria-label="Juego de preguntas sobre el sitio web de Baleares"]';
        this.juego = new Juego(this.selectorJuego);
    }

    iniciar() {
        this.juego.iniciar();
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const inicioJuego = new InicioJuego();
    inicioJuego.iniciar();
});