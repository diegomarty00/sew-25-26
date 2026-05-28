import html
import xml.etree.ElementTree as ET


class GeneradorRutasHTML:
    def __init__(self, salida):
        self.salida = salida

    def escribir(self, texto):
        self.salida.write(texto)

    def texto(self, nodo, defecto=""):
        return html.escape((nodo.text or defecto).strip()) if nodo is not None else defecto

    def prologo(self):
        self.escribir('<!DOCTYPE html>\n')
        self.escribir('<html lang="es">\n')
        self.escribir('<head>\n')
        self.escribir('    <meta charset="UTF-8">\n')
        self.escribir('    <meta name="author" content="Diego Martínez Menéndez">\n')
        self.escribir('    <meta name="description" content="Rutas turísticas por las Islas Baleares">\n')
        self.escribir('    <meta name="keywords" content="Baleares, rutas, turismo, Mallorca, Menorca, Ibiza">\n')
        self.escribir('    <meta name="viewport" content="width=device-width, initial-scale=1.0">\n')
        self.escribir('    <title>Baleares - Rutas</title>\n')
        self.escribir('    <link rel="icon" href="multimedia/imagenes/Baleares.ico" type="image/x-icon">\n')
        self.escribir('    <link rel="stylesheet" href="estilo/estilo.css">\n')
        self.escribir('    <link rel="stylesheet" href="estilo/layout.css">\n')
        self.escribir('</head>\n')
        self.escribir('<body>\n')
        self.escribir('    <header>\n')
        self.escribir('        <h1><a href="index.html">Baleares</a></h1>\n')
        self.escribir('        <nav>\n')
        self.escribir('            <a href="index.html" accesskey="i">Inicio</a>\n')
        self.escribir('            <a href="gastronomia.html" accesskey="g">Gastronomía</a>\n')
        self.escribir('            <a href="rutas.html" class="active" accesskey="r">Rutas</a>\n')
        self.escribir('            <a href="meteorologia.html" accesskey="m">Meteorología</a>\n')
        self.escribir('            <a href="juego.html" accesskey="j">Juego</a>\n')
        self.escribir('            <a href="php/reservas.php" accesskey="v">Reservas</a>\n')
        self.escribir('            <a href="ayuda.html" accesskey="a">Ayuda</a>\n')
        self.escribir('        </nav>\n')
        self.escribir('    </header>\n')
        self.escribir('    <p>Estás en: <a href="index.html">Inicio</a> >> Rutas</p>\n')
        self.escribir('    <main>\n')
        self.escribir('        <section>\n')
        self.escribir('            <h2>Rutas turísticas por Baleares</h2>\n')
        self.escribir('            <p>Información generada a partir del archivo XML de rutas del proyecto.</p>\n')
        self.escribir('        </section>\n')

    def epilogo(self):
        self.escribir('    </main>\n')
        self.escribir('    <footer>\n')
        self.escribir('        <p>All Rights Reserved. &copy; 2026 <a href="index.html">Baleares</a> - Design By: <a href="https://github.com/diegomarty00">Diego Martinez Menéndez</a></p>\n')
        self.escribir('    </footer>\n')
        self.escribir('</body>\n')
        self.escribir('</html>\n')

    def escribir_ruta(self, ruta):
        nombre_ruta = html.escape(ruta.get("nombre", "Ruta"))
        self.escribir('        <section>\n')
        self.escribir(f'            <h2>{nombre_ruta}</h2>\n')
        self.escribir(f'            <p><strong>Tipo:</strong> {self.texto(ruta.find("tipo"))}</p>\n')
        self.escribir(f'            <p><strong>Transporte:</strong> {self.texto(ruta.find("transporte"))}</p>\n')
        self.escribir(f'            <p><strong>Duración:</strong> {self.texto(ruta.find("duracion"))} {html.escape(ruta.find("duracion").get("unidad", ""))}</p>\n')
        self.escribir(f'            <p><strong>Agencia:</strong> {self.texto(ruta.find("agencia"))}</p>\n')
        self.escribir(f'            <p><strong>Descripción:</strong> {self.texto(ruta.find("descripcion"))}</p>\n')
        self.escribir(f'            <p><strong>Personas adecuadas:</strong> {self.texto(ruta.find("personasAdecuadas"))}</p>\n')
        self.escribir(f'            <p><strong>Recomendación:</strong> {self.texto(ruta.find("recomendacion"))}/10</p>\n')

        inicio = ruta.find("inicio")
        if inicio is not None:
            self.escribir('            <article>\n')
            self.escribir('                <h3>Inicio de la ruta</h3>\n')
            self.escribir(f'                <p><strong>Lugar:</strong> {self.texto(inicio.find("lugar"))}</p>\n')
            self.escribir(f'                <p><strong>Dirección:</strong> {self.texto(inicio.find("direccion"))}</p>\n')
            self.escribir('            </article>\n')

        self.escribir('            <article>\n')
        self.escribir('                <h3>Hitos de la ruta</h3>\n')
        self.escribir('                <ol>\n')
        for hito in ruta.findall("hitos/hito"):
            self.escribir(f'                    <li>{self.texto(hito.find("nombre"))}: {self.texto(hito.find("descripcion"))}</li>\n')
        self.escribir('                </ol>\n')
        self.escribir('            </article>\n')

        self.escribir('            <article>\n')
        self.escribir('                <h3>Referencias</h3>\n')
        self.escribir('                <ul>\n')
        for referencia in ruta.findall("referencias/referencia"):
            url = self.texto(referencia)
            self.escribir(f'                    <li><a href="{url}">{url}</a></li>\n')
        self.escribir('                </ul>\n')
        self.escribir('            </article>\n')

        planimetria = self.texto(ruta.find("planimetria"))
        altimetria = self.texto(ruta.find("altimetria"))
        self.escribir(f'            <p><strong>Planimetría:</strong> <a href="xml/{planimetria}">{planimetria}</a></p>\n')
        self.escribir(f'            <p><strong>Altimetría:</strong> <a href="xml/{altimetria}">{altimetria}</a></p>\n')
        self.escribir('        </section>\n')


def generar_html(xml="rutas.xml", salida="../rutas.html"):
    arbol = ET.parse(xml)
    raiz = arbol.getroot()

    with open(salida, "w", encoding="utf-8") as archivo_salida:
        generador = GeneradorRutasHTML(archivo_salida)
        generador.prologo()
        for ruta in raiz.findall("ruta"):
            generador.escribir_ruta(ruta)
        generador.epilogo()

    print(f"HTML generado: {salida}")


def main():
    archivo = input("Introduce el nombre del XML [rutas.xml]: ").strip() or "rutas.xml"
    generar_html(archivo)


if __name__ == "__main__":
    main()
