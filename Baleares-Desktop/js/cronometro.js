class Cronometro {
    constructor() {
        this.tiempo = 0;
        this.corriendo = null;
        this.inicio = null;
    }

    instanciaTiempo() {
        try {
            // Si Temporal no existe o falla, ReferenceError/TypeError serán capturados
            return Temporal.Now.instant().epochMilliseconds;
        } catch (e) {
            return Date.now();
        }
    }

    arrancar() {
        if (this.corriendo) return; // Evita múltiples llamadas a arrancar
        this.inicio = this.instanciaTiempo();

        // Llama al método actualizar cada décima de segundo y guarda el ID del intervalo
        this.corriendo = setInterval(this.actualizar.bind(this), 100); // Usa bind para mantener el contexto de this
    }

    actualizar() {

        let ahora = this.instanciaTiempo(); // Usar Temporal si está disponible
        this.tiempo = ahora - this.inicio;

        this.tiempo = Math.floor(this.tiempo / 100);

        this.mostrar();
    }

    mostrar() {
        // Calcula minutos, segundos y décimas de segundo
        const minutos = String(Math.floor(this.tiempo / 600)).padStart(2, '0'); // 600 décimas = 1 minuto
        const segundos = String(Math.floor((this.tiempo % 600) / 10)).padStart(2, '0'); // 10 décimas = 1 segundo
        const decimas = String(this.tiempo % 10); // Resto en décimas

        // Crea la cadena en formato mm:ss.s
        const tiempoFormateado = `${minutos}:${segundos}.${decimas}`;

        // Busca el primer párrafo dentro del elemento main y actualiza su contenido
        const main = document.querySelector('main');
        if (main) {
            let parrafo = main.querySelector('p');
            if (!parrafo) {
                // Si no existe un párrafo, lo crea
                parrafo = document.createElement('p');
                main.appendChild(parrafo);
            }
            parrafo.textContent = tiempoFormateado;
        }
    }

    parar() {
        if (!this.corriendo) return;
        clearInterval(this.corriendo);

    }

    reiniciar() {
        this.parar();
        this.tiempo = 0;
        this.inicio = null;
        this.corriendo = null;
        this.mostrar();
    }
}