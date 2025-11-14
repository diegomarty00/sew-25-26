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
        this.volteadas = [];
    }

    flipCard(cardElement) {
        if (!this.juegoIniciado) {
            this.juegoIniciado = true;
            this.cronometro.arrancar();
        }
        const estado = cardElement.getAttribute('data-estado');
        const nombre = cardElement.querySelector('img').alt.replace('Logo-', '');

        if (estado === 'volteada') {
            cardElement.setAttribute('data-estado', 'normal');
            this.volteada = this.volteadas.filter(c => c !== cardElement);
            return;
        }
        if (estado === 'pareada')
            return;
        if (estado === 'normal') {
            cardElement.setAttribute('data-estado', 'volteada');
            this.volteadas.push(cardElement);
            if (this.volteadas.length === 2) {
                this.checkMatch();
            }
        }
    }

    checkMatch() {
        const [carta1, carta2] = this.volteadas;
        const nombre1 = this.getCardName(carta1);
        const nombre2 = this.getCardName(carta2);

        if (nombre1 === nombre2) {
            carta1.setAttribute('data-estado', 'pareada');
            carta2.setAttribute('data-estado', 'pareada');
            this.volteadas = [];
            if (document.querySelectorAll('main article[data-estado="pareada"]').length === this.cards.length) {
                this.cronometro.parar();
                setTimeout(() => {
                    alert('¡Felicidades! ¡Has completado el juego de memoria! Tu tiempo: ' + document.querySelector('main p').textContent);
                }, 500);
            }
        } else {
            setTimeout(() => {
                carta1.setAttribute('data-estado', 'normal');
                carta2.setAttribute('data-estado', 'normal');
                this.volteadas = [];
            }, 500);
        }
    }

    getCardName(cardElement) {
        return cardElement.querySelector('img').alt.replace('Logo-', '');
    }


    shuffle(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
    }


    renderCards() {
        const container = document.querySelector('main');

        this.shuffle(this.cards);

        this.cards.forEach(card => {
            const article = document.createElement('article');
            article.setAttribute('data-estado', 'normal');
            article.onclick = () => this.flipCard(article);

            const h3 = document.createElement('h3');
            h3.textContent = 'Memory Card';

            const img = document.createElement('img');
            img.src = card.source;
            img.alt = `Logo-${card.element.split('_')[0]}`;

            article.appendChild(h3);
            article.appendChild(img);
            container.appendChild(article);
        });
    }

}