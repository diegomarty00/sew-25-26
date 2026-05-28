
import xml.etree.ElementTree as ET

class Html:
    def __init__(self, outFile):
        self.out = outFile

    def prologo(self):
        self.out.write('<!DOCTYPE html>\n')
        self.out.write('<html lang="es">\n')
        self.out.write('<head>\n')
        self.out.write('    <meta charset="UTF-8">\n')
        self.out.write('    <meta name="viewport" content="width=device-width, initial-scale=1.0">\n')
        self.out.write('    <title>Información del Circuito</title>\n')
        self.out.write('    <link rel="stylesheet" type="text/css" href="estilo/estilo.css">\n')
        self.out.write('</head>\n')
        self.out.write('<body>\n')
        self.out.write('    <header>\n')
        self.out.write('        <h1>MotoGP Desktop</h1>\n')
        self.out.write('    </header>\n')
        self.out.write('    <main>\n')

    def epilogo(self):
        self.out.write('    </main>\n')
        self.out.write('</body>\n')
        self.out.write('</html>\n')


    def formatear_tiempo(self, tiempo):
        if tiempo is not None and tiempo.text:
            import re
            m = re.fullmatch(r'PT(?:(\d+)M)?(?:(\d+(?:\.\d+)?)S)?', tiempo.text.strip())
            if m:
                mins = int(m.group(1) or 0)
                secs = float(m.group(2) or 0.0)
                return f'{mins}:{secs:06.3f}'  # mm:ss.mmm
            return tiempo.text.strip()
        return ''


    def escribir_circuito(self, root, ns):
        # Nodo principal
        nombre = root.get("nombreCircuito")

        pais = root.find('.//ns:pais', namespaces=ns)
        localidad = root.find('.//ns:localidad', namespaces=ns)
        vueltas = root.find('.//ns:vueltas', namespaces=ns)

        longitud = root.find('.//ns:longitudCircuito', namespaces=ns)
        anchura = root.find('.//ns:anchura', namespaces=ns)
        fecha = root.find('.//ns:fecha', namespaces=ns)
        hora = root.find('.//ns:horaCarreraEsp', namespaces=ns)
        patrocinador = root.find('.//ns:patrocinador', namespaces=ns)

        ganador = root.find('.//ns:resultados/ns:ganador', namespaces=ns)
        tiempo = self.formatear_tiempo(root.find('.//ns:resultados/ns:tiempoGP', namespaces=ns))
        clasificacion = root.findall('.//ns:resultados/ns:clasificacion/ns:piloto', namespaces=ns)

        referencias = root.findall('.//ns:referencias/ns:referencia', namespaces=ns)
        fotos = root.findall('.//ns:multimedia/ns:foto', namespaces=ns)
        videos = root.findall('.//ns:multimedia/ns:video', namespaces=ns)

        latitud = root.find('.//ns:coordenadasCircuito/ns:latitud', namespaces=ns)
        longitud_coord = root.find('.//ns:coordenadasCircuito/ns:longitud', namespaces=ns)
        altitud = root.find('.//ns:coordenadasCircuito/ns:altitud', namespaces=ns)


        # Escritura
        self.out.write(f'        <h2>Circuito: {nombre}</h2>\n')
        self.out.write(f'        <p>Localidad: {localidad.text} ({pais.text})</p>\n')
        self.out.write(f'        <p>Vueltas: {vueltas.text}</p>\n')

        if longitud is not None:
            self.out.write(f'        <p>Longitud del circuito: {longitud.text} {longitud.get("unidad")}</p>\n')
        if anchura is not None:
            self.out.write(f'        <p>Anchura media: {anchura.text} {anchura.get("unidad")}</p>\n')
        if latitud is not None:
            self.out.write(f'        <p>Coordenadas: {latitud.text}, {longitud_coord.text}, {altitud.text}</p>\n')
        if fecha is not None:
            self.out.write(f'        <p>Fecha de la carrera: {fecha.text}</p>\n')
        if hora is not None:
            self.out.write(f'        <p>Hora en España: {hora.text}</p>\n')
        if patrocinador is not None:
            self.out.write(f'        <p>Patrocinador: {patrocinador.text}</p>\n')
        if ganador is not None and tiempo is not None:
            self.out.write(f'        <p>Ganador: {ganador.text} ({tiempo})</p>\n')

        # Clasificación
        if clasificacion:
            self.out.write('        <h3>Clasificación Mundial</h3>\n')
            self.out.write('        <ol>\n')
            for piloto in clasificacion:
                self.out.write(f'            <li>{piloto.text}</li>\n')
            self.out.write('        </ol>\n')

        # Referencias
        if referencias:
            self.out.write('        <h3>Referencias</h3>\n')
            self.out.write('        <ul>\n')
            for ref in referencias:
                url = ref.text.strip() if (ref is not None and ref.text) else ''
                if url:
                    # enlace completo y bien cerrado
                    self.out.write(
                        f'            <li><a href="{url}">{url}</a></li>\n'
                    )
            self.out.write('        </ul>\n')


        # Galería
        if fotos or videos:
            self.out.write('        <h3>Galería</h3>\n')

        for foto in fotos:
            src = foto.text.strip() if (foto is not None and foto.text) else ''
            if src:
                self.out.write(
                    f'            <figure><img src="{foto.text}" alt="Imagen del circuito"></figure>\n'
                )

        for video in videos:
            src = video.text.strip() if (video is not None and video.text) else ''
            if src:
                self.out.write(
                    f'             <video controls><source src="{video.text}" type="video/mp4"></video>\n'
                )


def main():
    try:
        tree = ET.parse('circuitoEsquema.xml')
    except Exception as e:
        print("No se puede abrir 'circuitoEsquema.xml':", e)
        return

    ns = {'ns': 'http://www.uniovi.es'}  # Ajusta si tu XML usa otro namespace
    root = tree.getroot()

    html_filename = '../InfoCircuito.html'

    with open(html_filename, 'w', encoding='utf-8') as outFile:
        html = Html(outFile)
        html.prologo()
        html.escribir_circuito(root, ns)
        html.epilogo()

    print(f'HTML generado correctamente: {html_filename}')


if __name__ == '__main__':
    main()
