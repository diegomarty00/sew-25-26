class Cronometro {
    constructor() {
        this.tiempo = 0;
    }

    arrancar() {
        if (this.corriendo) return; // Evita múltiples llamadas a arrancar
        // Crea el atributo inicio y lo inicializa según la disponibilidad de Temporal
        if (typeof Temporal !== "undefined" && Temporal.Now) {
            this.inicio = Temporal.Now.instant(); // Usar Temporal si está disponible
        } else {
            this.inicio = new Date(); // Usar Date si Temporal no está disponible
        }

        // Llama al método actualizar cada décima de segundo y guarda el ID del intervalo
        this.corriendo = setInterval(this.actualizar.bind(this), 100); // Usa bind para mantener el contexto de this
    }

    actualizar() {
        let ahora; 
        if (typeof Temporal !== "undefined" && Temporal.Now) {
            ahora = Temporal.Now.instant(); // Usar Temporal si está disponible
            this.tiempo = ahora.epochMilliseconds - this.inicio.epochMilliseconds;
        } else {
            ahora = new Date(); // Usar Date si Temporal no está disponible
            this.tiempo = ahora - this.inicio;
        }
       
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
        if (!this.corriendo) return;
        this.parar();
        this.tiempo = 0;
        this.mostrar();
    }
}