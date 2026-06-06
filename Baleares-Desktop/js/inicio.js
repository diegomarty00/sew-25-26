class Inicio {
    constructor() {
        this.selectorCarrusel = 'section[data-componente="carrusel"]';
        this.selectorNoticias = 'section[aria-label="Listado de noticias sobre las Islas Baleares"]';

        this.carrusel = new Carrusel(this.selectorCarrusel);
        this.noticias = new Noticias(this.selectorNoticias, "Islas Baleares turismo");
    }

    iniciar() {
        this.carrusel.iniciar();
        //this.noticias.buscar();
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const inicio = new Inicio();
    inicio.iniciar();
});