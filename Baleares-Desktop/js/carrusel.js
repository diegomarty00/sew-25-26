"use strict";

class Carrusel extends GaleriaImagenes {
    constructor(selectorContenedor) {
        const imagenes = [
            {
                ruta: "multimedia/imagenes/mapa.jpg",
                textoAlternativo: "Mapa de situación de las Islas Baleares en el mar Mediterráneo",
                titulo: "Mapa de situación de las Islas Baleares"
            },
            {
                ruta: "multimedia/imagenes/vista_mallorca.jpg",
                textoAlternativo: "Vista turística de Mallorca con costa mediterránea",
                titulo: "Mallorca"
            },
            {
                ruta: "multimedia/imagenes/vista_menorca.jpg",
                textoAlternativo: "Paisaje natural de Menorca con una cala de aguas claras",
                titulo: "Menorca"
            },
            {
                ruta: "multimedia/imagenes/vista_ibiza.jpg",
                textoAlternativo: "Paisaje turístico de Ibiza junto al mar",
                titulo: "Ibiza"
            },
            {
                ruta: "multimedia/imagenes/vista_formentera.jpg",
                textoAlternativo: "Playa de Formentera con arena clara y agua turquesa",
                titulo: "Formentera"
            },
            {
                ruta: "multimedia/imagenes/vista_palma.jpg",
                textoAlternativo: "Vista de Palma, capital de las Islas Baleares",
                titulo: "Palma"
            }
        ];

        super(selectorContenedor, imagenes, {
            titulo: "Descubre las Islas Baleares",
            mostrarTexto: true,
            texto: "Las Islas Baleares son un destino turístico del Mediterráneo formado por Mallorca, Menorca, Ibiza y Formentera. Destacan por sus playas, paisajes naturales, patrimonio histórico, gastronomía y rutas turísticas.",
            automatico: true,
            tiempo: 5000,
            conAmpliacion: true
        });
    }
}