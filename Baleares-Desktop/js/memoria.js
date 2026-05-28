class Memoria {
    constructor() {
        this.cards = [
            { element: "Aprolia_1", source: "multimedia/logos/aprilia.svg" },
            { element: "Ducati_1", source: "multimedia/logos/ducati.svg" },
            { element: "Honda_1", source: "multimedia/logos/honda.svg" },
            { element: "Ktm_1", source: "multimedia/logos/ktm.svg" },
            { element: "MotoGP_1", source: "multimedia/logos/motogp.svg" },
            { element: "Yamaha_1", source: "multimedia/logos/yamaha.svg" },
            { element: "Aprolia_2", source: "multimedia/logos/aprilia.svg" },
            { element: "Ducati_2", source: "multimedia/logos/ducati.svg" },
            { element: "Honda_2", source: "multimedia/logos/honda.svg" },
            { element: "Ktm_2", source: "multimedia/logos/ktm.svg" },
            { element: "MotoGP_2", source: "multimedia/logos/motogp.svg" },
            { element: "Yamaha_2", source: "multimedia/logos/yamaha.svg" }
        ];
        this.juegoIniciado = false;
        this.cronometro = new Cronometro();

        this.primera_carta = null;
        this.segunda_carta = null;

        this.tablero_bloqueado = true;  // bloqueado mientras se monta
        this.renderizarCartas();
        this.tablero_bloqueado = false; // desbloquear tablero al terminar montaje
    }

    reiniciarAtributos(){
        this.tablero_bloqueado = false;
        this.primera_carta = null;
        this.segunda_carta = null;
    }

    voltearCarta(cardElement) {
        if (this.tablero_bloqueado) return;

        let estado = cardElement.getAttribute('data-estado');

        if (estado === 'revelada' || estado === 'volteada') return;

        if (!this.juegoIniciado) {
            this.juegoIniciado = true;
            this.cronometro.arrancar();
        }
        
         // Voltear la carta actual
        cardElement.setAttribute('data-estado', 'volteada');

        if (!this.primera_carta) {  // Primera carta volteada
            this.primera_carta = cardElement;
        }else {                     // Segunda carta volteada
            this.segunda_carta = cardElement;
            this.tablero_bloqueado = true;
            this.comprobarPareja();
        }
    }

     comprobarPareja() {
        let iguales = this.getCardName(this.primera_carta) === this.getCardName(this.segunda_carta);
        iguales ? this.deshabilitarCartas() : this.cubrirCartas();
    }

    cubrirCartas(retardo = 500) {
        this.tablero_bloqueado = true;

        let c1 = this.primera_carta;
        let c2 = this.segunda_carta;

        window.setTimeout(() => {
            if (c1) c1.removeAttribute('data-estado');
            if (c2) c2.removeAttribute('data-estado');

            this.reiniciarAtributos();
        }, retardo);
    }

    deshabilitarCartas() {
        this.primera_carta.setAttribute('data-estado', 'revelada');
        this.segunda_carta.setAttribute('data-estado', 'revelada');

        // Limpia la jugada y desbloquea tablero
        this.reiniciarAtributos();

        // Comprobar fin de juego
        this.comprobarJuego();
    }

    comprobarJuego() {
        let reveladas = document.querySelectorAll('main article[data-estado="revelada"]').length;
        if (reveladas === this.cards.length) {
            this.cronometro.parar();
            setTimeout(() => {
                let tiempo = document.querySelector('main p')?.textContent ?? '';
                alert('¡Felicidades! ¡Has completado el juego de memoria! Tu tiempo: ' + tiempo);
            }, 500);
        }
    }

    getCardName(cardElement) {
        let img = null;
        if (cardElement && cardElement.children && cardElement.children.length > 0) {
            // Mejor buscar la primera IMG entre los hijos
            for (let child of cardElement.children) {
                if (child.tagName === 'IMG') { img = child; break; }
            }
        }
        if (!img) img = cardElement.querySelector('img');

        let alt = img ? img.getAttribute('alt') : '';
        return alt ? alt.replace('Logo-', '') : '';
    }


    barajarCartas(array) {
        for (let i = array.length - 1; i > 0; i--) {
            let j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
    }


    renderizarCartas() {
        let container = document.querySelector('main');
        if (!container) return;

        container.querySelectorAll('article').forEach(a => a.remove());

        this.barajarCartas(this.cards);

        this.cards.forEach(card => {
            let article = document.createElement('article');
            article.setAttribute('data-estado', 'normal');
            article.onclick = () => this.voltearCarta(article);

            let h3 = document.createElement('h3');
            h3.textContent = 'Memory Card';

            let img = document.createElement('img');
            img.src = card.source;
            img.alt = `Logo-${card.element.split('_')[0]}`;

            article.appendChild(h3);
            article.appendChild(img);
            container.appendChild(article);
        });
    }

}