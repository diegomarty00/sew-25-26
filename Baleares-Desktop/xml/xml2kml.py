import os
import re
import xml.etree.ElementTree as ET


def texto(nodo, defecto=""):
    return (nodo.text or defecto).strip() if nodo is not None else defecto


def slug(texto_ruta):
    texto_ruta = texto_ruta.lower()
    reemplazos = {
        "á": "a", "é": "e", "í": "i", "ó": "o", "ú": "u",
        "à": "a", "è": "e", "ï": "i", "ò": "o", "ü": "u",
        "ñ": "n", "ç": "c"
    }
    for origen, destino in reemplazos.items():
        texto_ruta = texto_ruta.replace(origen, destino)
    texto_ruta = re.sub(r"[^a-z0-9]+", "-", texto_ruta).strip("-")
    return texto_ruta


def valor_coordenada(contenedor, nombre):
    nodo = contenedor.find(nombre)
    return texto(nodo).replace(",", ".")


def coordenadas_desde(contenedor):
    longitud = valor_coordenada(contenedor, "longitud")
    latitud = valor_coordenada(contenedor, "latitud")
    altitud = valor_coordenada(contenedor, "altitud") or "0"
    return longitud, latitud, altitud


def escribir_prologo(kml, nombre_ruta):
    kml.write('<?xml version="1.0" encoding="UTF-8"?>\n')
    kml.write('<kml xmlns="http://www.opengis.net/kml/2.2">\n')
    kml.write('  <Document>\n')
    kml.write(f'    <name>{nombre_ruta}</name>\n')
    kml.write('    <Style id="lineaRuta">\n')
    kml.write('      <LineStyle>\n')
    kml.write('        <color>ff5c3a8f</color>\n')
    kml.write('        <width>5</width>\n')
    kml.write('      </LineStyle>\n')
    kml.write('    </Style>\n')
    kml.write('    <Placemark>\n')
    kml.write(f'      <name>{nombre_ruta}</name>\n')
    kml.write('      <styleUrl>#lineaRuta</styleUrl>\n')
    kml.write('      <LineString>\n')
    kml.write('        <tessellate>1</tessellate>\n')
    kml.write('        <altitudeMode>clampToGround</altitudeMode>\n')
    kml.write('        <coordinates>\n')


def escribir_epilogo(kml):
    kml.write('        </coordinates>\n')
    kml.write('      </LineString>\n')
    kml.write('    </Placemark>\n')
    kml.write('  </Document>\n')
    kml.write('</kml>\n')


def obtener_puntos(ruta):
    puntos = []

    coordenadas_inicio = ruta.find("inicio/coordenadasInicio")
    if coordenadas_inicio is not None:
        puntos.append(coordenadas_desde(coordenadas_inicio))

    for hito in ruta.findall("hitos/hito"):
        coordenadas_hito = hito.find("coordenadasHito")
        if coordenadas_hito is not None:
            puntos.append(coordenadas_desde(coordenadas_hito))

    return puntos


def generar_kml(xml="rutas.xml"):
    arbol = ET.parse(xml)
    raiz = arbol.getroot()

    for ruta in raiz.findall("ruta"):
        nombre_ruta = ruta.get("nombre", "ruta")
        nombre_archivo = ruta.findtext("planimetria") or f"planimetria-{slug(nombre_ruta)}.kml"
        puntos = obtener_puntos(ruta)

        with open(nombre_archivo, "w", encoding="utf-8") as kml:
            escribir_prologo(kml, nombre_ruta)
            for longitud, latitud, altitud in puntos:
                if longitud and latitud:
                    kml.write(f'          {longitud},{latitud},{altitud}\n')
            escribir_epilogo(kml)

        print(f"KML generado: {nombre_archivo}")


def main():
    archivo = input("Introduce el nombre del XML [rutas.xml]: ").strip() or "rutas.xml"
    generar_kml(archivo)


if __name__ == "__main__":
    main()
