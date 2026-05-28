class Ciudad {

    constructor(nombre, pais, gentilicio) {
        this.nombre = nombre;
        this.pais = pais;
        this.gentilicio = gentilicio;

        this.poblacion = null;
        this.latitud = null;
        this.longitud = null;
        this.fechaCarrera = null;

        this.base = 'https://archive-api.open-meteo.com/v1/archive';
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

    setFechaCarrera(fecha) {
        this.fechaCarrera = fecha;
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
    // =========================
    //   Open‑Meteo: helpers
    // =========================

    _url({ start, end, hourlyList = [], dailyList = [] }) {
        const query = new URLSearchParams({
            latitude: this.latitud,
            longitude: this.longitud,
            start_date: start,
            end_date: end,
            timezone: 'auto',                 // hora local del lugar
            hourly: hourlyList.join(','),
            daily: dailyList.join(',')
        }).toString();
        const full = `${this.base}?${query}`;
        // (Opcional) ver en consola la URL llamada
        console.log('[Open‑Meteo] GET', full);
        return full;
    }

    _diaISO(date) {
        const d = (date instanceof Date) ? date : new Date(date);
        const pad = n => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    }

    // =========================
    //   Día de la carrera
    // =========================

    /**
     * Obtiene JSON horario del día de carrera + diario (sunrise/sunset)
     * Variables horarias mínimas exigidas por el enunciado.
     */
    getMeteorologiaCarrera() {
        if (!this.latitud || !this.longitud || !this.fechaCarrera) {
            return $.Deferred().reject(new Error('Faltan coordenadas o fecha de carrera')).promise();
        }

        const hourly = [
            'temperature_2m',
            'apparent_temperature',
            'precipitation',
            'relative_humidity_2m',
            'windspeed_10m',
            'winddirection_10m'
        ];
        const daily = ['sunrise', 'sunset'];

        const url = this._url({
            start: this.fechaCarrera,
            end: this.fechaCarrera,
            hourlyList: hourly,
            dailyList: daily
        });

        // Open‑Meteo devuelve JSON con CORS habilitado
        return $.getJSON(url);
    }

    /**
     * Procesa el JSON del día de carrera
     */
    procesarJSONCarrera(json) {
        const hourly = json.hourly || {};
        const daily = json.daily || {};

        return {
            nombre: this.nombre,
            fecha: this.fechaCarrera,
            horas: hourly.time || [],
            temperatura: hourly.temperature_2m || [],
            sensacion: hourly.apparent_temperature || [],
            lluvia: hourly.precipitation || [],
            humedad: hourly.relative_humidity_2m || [],
            vientoVel: hourly.windspeed_10m || [],
            vientoDir: hourly.winddirection_10m || [],
            salidaSol: (daily.sunrise && daily.sunrise[0]) || null,
            puestaSol: (daily.sunset && daily.sunset[0]) || null,
            units: {
                temperatura: json.hourly_units?.temperature_2m || '°C',
                sensacion: json.hourly_units?.apparent_temperature || '°C',
                lluvia: json.hourly_units?.precipitation || 'mm',
                humedad: json.hourly_units?.relative_humidity_2m || '%',
                vientoVel: json.hourly_units?.windspeed_10m || 'km/h',
                vientoDir: json.hourly_units?.winddirection_10m || '°',
                salidaSol: json.daily_units?.sunrise || 'ISO',
                puestaSol: json.daily_units?.sunset || 'ISO'
            }
        };
    }


    mostrarCarrera(d) {
        const main = document.querySelector('main');

        // Contenedor principal del bloque de carrera
        const art = document.createElement('article');
        art.className = 'meteo-carrera';

        // Título del bloque
        const h2 = document.createElement('h2');
        h2.textContent = `Meteorología (día de carrera) — ${d.nombre} — ${d.fecha}`;

        // === ARTICLE meteo-sol (por encima de las horas y fuera de meteo-cards) ===
        const artSol = document.createElement('article');
        artSol.className = 'meteo-sol';
        artSol.setAttribute('aria-label', 'Resumen solar');

        // Helper para crear tarjeta "sol" (misma clase que las otras para coherencia visual)
        const crearCardSol = (titulo, valorISO) => {
            const card = document.createElement('article');
            card.className = 'meteo-card meteo-card--sol'; // reutiliza meteo-card


            // Par dt/dd dentro de un <dl>
            const dl = document.createElement('dl');
            dl.className = 'resumen-sol';

            const dt = document.createElement('dt');
            dt.textContent = titulo;

            const dd = document.createElement('dd');
            if (valorISO) {
                const timeEl = document.createElement('time');
                timeEl.dateTime = valorISO;
                timeEl.textContent = typeof fmtFechaHoraLegible === 'function'
                    ? fmtFechaHoraLegible(valorISO)
                    : (typeof fmtHora === 'function' ? fmtHora(valorISO) : valorISO);
                dd.appendChild(timeEl);
            } else {
                dd.textContent = '—';
            }

            dl.append(dt, dd);
            card.append(dl);
            return card;
        };

        // Añadir las dos tarjetas dentro de meteo-sol
        artSol.appendChild(crearCardSol('Salida del sol', d.salidaSol));
        artSol.appendChild(crearCardSol('Puesta del sol', d.puestaSol));

        // === SECCIÓN TARJETAS POR HORA ===
        const sectionCards = document.createElement('section');
        sectionCards.className = 'meteo-cards';
        sectionCards.setAttribute('aria-label', 'Meteorología por horas');

        // Helper para añadir pares <dt><dd> en las tarjetas por hora
        const addPar = (dl, titulo, valor, unidad) => {
            const dt = document.createElement('dt'); dt.textContent = titulo;
            const dd = document.createElement('dd');
            dd.textContent = (valor === null || valor === undefined)
                ? '—'
                : `${round2(valor)}${unidad ? ' ' + unidad : ''}`;
            dl.append(dt, dd);
        };

        // Crear una tarjeta <article> por cada hora
        for (let i = 0; i < d.horas.length; i++) {
            const card = document.createElement('article');
            card.className = 'meteo-card';

            // Cabecera de la tarjeta: h4 con <time>
            const h4 = document.createElement('h4');
            const timeEl = document.createElement('time');
            timeEl.dateTime = d.horas[i];
            timeEl.textContent = fmtHora(d.horas[i]); // "HH:MM"
            h4.appendChild(timeEl);

            // Valores de esa hora como <dl>
            const dlVals = document.createElement('dl');
            dlVals.className = 'meteo-hora-valores';

            addPar(dlVals, 'Temperatura', d.temperatura[i], d.units.temperatura);
            addPar(dlVals, 'Sensación', d.sensacion[i], d.units.sensacion);
            addPar(dlVals, 'Lluvia', d.lluvia[i], d.units.lluvia);
            addPar(dlVals, 'Humedad', d.humedad[i], d.units.humedad);
            addPar(dlVals, 'Viento', d.vientoVel[i], d.units.vientoVel);
            addPar(dlVals, 'Dirección', d.vientoDir[i], d.units.vientoDir);

            card.append(h4, dlVals);
            sectionCards.appendChild(card);
        }

        // Ensamblado final: título, meteo-sol arriba y meteo-cards debajo
        art.append(h2, artSol, sectionCards);
        main.appendChild(art);
    }



    // =========================
    //   Entrenamientos (3 días previos)
    // =========================

    /**
     * Obtiene JSON horario de los 3 días previos a la carrera
     * Variables: temperatura 2m, lluvia, viento 10m (velocidad), humedad 2m
     */
    getMeteorologiaEntrenos() {
        if (!this.latitud || !this.longitud || !this.fechaCarrera) {
            return $.Deferred().reject(new Error('Faltan coordenadas o fecha de carrera')).promise();
        }

        const fCarrera = new Date(this.fechaCarrera);
        const inicio = new Date(fCarrera); inicio.setDate(inicio.getDate() - 3);
        const fin = new Date(fCarrera); fin.setDate(fin.getDate() - 1);

        const hourly = [
            'temperature_2m',
            'precipitation',
            'windspeed_10m',
            'relative_humidity_2m'
        ];

        const url = this._url({
            start: this._diaISO(inicio),
            end: this._diaISO(fin),
            hourlyList: hourly
        });

        return $.getJSON(url);
    }

    /**
     * Procesa JSON de entrenos y calcula medias por día (2 decimales)
     */
    procesarJSONEntrenos(json) {
        const H = json.hourly || {};
        const horas = H.time || [];
        const T = H.temperature_2m || [];
        const P = H.precipitation || [];
        const W = H.windspeed_10m || [];
        const RH = H.relative_humidity_2m || [];

        // Agrupar por fecha YYYY-MM-DD
        const grupos = {};
        for (let i = 0; i < horas.length; i++) {
            const fecha = (horas[i] || '').slice(0, 10);
            if (!grupos[fecha]) grupos[fecha] = { T: [], P: [], W: [], RH: [] };
            grupos[fecha].T.push(T[i]);
            grupos[fecha].P.push(P[i]);
            grupos[fecha].W.push(W[i]);
            grupos[fecha].RH.push(RH[i]);
        }

        const avg = arr => (arr.length ? arr.reduce((a, b) => a + (b || 0), 0) / arr.length : 0);
        const resumen = Object.keys(grupos).sort().map(fecha => {
            const g = grupos[fecha];
            return {
                fecha,
                temperatura_media: round2(avg(g.T)),
                lluvia_media: round2(avg(g.P)),
                viento_media: round2(avg(g.W)),
                humedad_media: round2(avg(g.RH))
            };
        });

        return {
            nombre: this.nombre,
            resumen,
            units: {
                temperatura: json.hourly_units?.temperature_2m || '°C',
                lluvia: json.hourly_units?.precipitation || 'mm',
                viento: json.hourly_units?.windspeed_10m || 'km/h',
                humedad: json.hourly_units?.relative_humidity_2m || '%'
            }
        };
    }



    mostrarEntrenos(r) {
        const main = document.querySelector('main');

        // Contenedor principal del bloque de entrenos
        const art = document.createElement('article');
        art.className = 'meteo-entrenos';

        // Título del bloque
        const h2 = document.createElement('h2');
        h2.textContent = `Entrenamientos (medias por día) — ${r.nombre}`;

        // Sección contenedora de las tarjetas por día
        const sectionCards = document.createElement('section');
        sectionCards.className = 'entrenos-cards';
        sectionCards.setAttribute('aria-label', 'Medias meteorológicas por día');

        // Helper para añadir pares <dt><dd>
        const addPar = (dl, titulo, valor, unidad) => {
            const dt = document.createElement('dt'); dt.textContent = titulo;
            const dd = document.createElement('dd');
            dd.textContent = (valor === null || valor === undefined)
                ? '—'
                : `${round2(valor)}${unidad ? ' ' + unidad : ''}`;
            dl.append(dt, dd);
        };

        // Crear una tarjeta <article> por cada día del resumen
        r.resumen.forEach(row => {
            const card = document.createElement('article');
            card.className = 'entrenos-card';

            // Cabecera de la tarjeta: h4 con <time>
            const h4 = document.createElement('h4');
            const timeEl = document.createElement('time');
            timeEl.dateTime = row.fecha;          // "YYYY-MM-DD"
            timeEl.textContent = row.fecha; // "dd/mm/aaaa"
            h4.appendChild(timeEl);

            // Valores de ese día (medias) en <dl>
            const dlVals = document.createElement('dl');
            dlVals.className = 'entrenos-valores';

            addPar(dlVals, 'Temp media', row.temperatura_media, r.units.temperatura);
            addPar(dlVals, 'Lluvia media', row.lluvia_media, r.units.lluvia);
            addPar(dlVals, 'Viento medio', row.viento_media, r.units.viento);
            addPar(dlVals, 'Humedad media', row.humedad_media, r.units.humedad);

            card.append(h4, dlVals);
            sectionCards.appendChild(card);
        });

        art.append(h2, sectionCards);
        main.appendChild(art);
    }


    // ---- manejo de errores (DOM nativo) ----
    mostrarError(err) {
        console.error(err);
        const main = document.querySelector('main');

        const art = document.createElement('article');
        art.className = 'meteo-error';
        const h2 = document.createElement('h2'); h2.textContent = 'Error al obtener datos de Open‑Meteo';
        const p = document.createElement('p'); p.textContent = 'Inténtalo de nuevo más tarde.';

        art.appendChild(h2); art.appendChild(p);
        main.appendChild(art);
    }
}

// ---- utilidades numéricas ----
function round2(x) {
    const v = Number.isFinite(x) ? x : 0;
    return Math.round(v * 100) / 100;
}

function fmt(x) {
    return (x === null || x === undefined) ? '—' : round2(x);
}


// Parsea "YYYY-MM-DDTHH:MM" como fecha/hora local (sin depender de 'Z')
function parseLocalISO(iso) {
    if (!iso || typeof iso !== 'string') return null;
    const [datePart, timePart] = iso.split('T');
    const [Y, M, D] = datePart.split('-').map(Number);
    const [h = 0, m = 0] = (timePart || '').split(':').map(Number);
    return new Date(Y, M - 1, D, h, m); // construye fecha local
}

// Formatea a "HH:MM" en español (puedes cambiar 'es-ES' si prefieres otra cultura)
function fmtHora(iso) {
    const d = parseLocalISO(iso);
    if (!d) return '—';
    return new Intl.DateTimeFormat('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
    }).format(d);
}

// Formatea fecha+hora para <time datetime="..."> (opcional)
function fmtFechaHoraLegible(iso) {
    const d = parseLocalISO(iso);
    if (!d) return '—';
    return new Intl.DateTimeFormat('es-ES', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit'
    }).format(d);
}
