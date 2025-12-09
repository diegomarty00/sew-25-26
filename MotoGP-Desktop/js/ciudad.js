class ciudad {

    constructor(nombre, pais, gentilicio) {
        this.nombre = nombre;
        this.pais = pais;
        this.gentilicio = gentilicio;
    }

    setPoblacion(poblacion) {
        this.poblacion = poblacion;
    }

    getPoblacion() {
        return this.poblacion;
    }

    setCoordenadas(longitud, latitud) {
        this.longitud = longitud;
        this.latitud = latitud;
    }

    getCoordenadas() {
        return this.latitud + ", " + this.longitud;
    }

    getNombre() {
        return this.nombre;
    }

    getPais() {
        return this.pais;
    }

    getGentilicio() {
        return this.gentilicio;
    }

    parrafoNombre() {
        const mensaje = document.createElement("p");
        mensaje.textContent = "Ciudad: " + this.getNombre();
        document.querySelector("main").appendChild(mensaje);
    }

    parrafoPais() {
        const mensaje = document.createElement("p");
        mensaje.textContent = "Pais: " + this.getPais();
        document.querySelector("main").appendChild(mensaje);
    }

    parrafoSecundario() {
        const mensaje = document.createElement("p");
        mensaje.textContent = "Gentilicio" + this.getGentilicio() + " - Población: " + this.getPoblacion();
        document.querySelector("main").appendChild(mensaje);
    }

    parrafoCoordenadas() {
        // document.write("<p>Coordenadas [" + this.getCoordenadas() + "] </p>");
        // Evitamos el deprecado document.write y usamos createElement y appendChild
        const mensaje = document.createElement("p");
        mensaje.textContent = "Coordenadas: " + this.getCoordenadas();
        document.querySelector("main").appendChild(mensaje);
        
    }
}
