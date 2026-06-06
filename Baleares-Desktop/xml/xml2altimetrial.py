import math
import xml.etree.ElementTree as ET


def texto(nodo, defecto=""):
    return (nodo.text or defecto).strip() if nodo is not None else defecto


def numero(nodo, defecto=0.0):
    try:
        return float(texto(nodo).replace(",", "."))
    except ValueError:
        return defecto


def obtener_hitos(ruta):
    hitos = []
    distancia_acumulada = 0.0

    for hito in ruta.findall("hitos/hito"):
        nombre = texto(hito.find("nombre"))
        distancia = numero(hito.find("distancia"), 0.0)
        distancia_acumulada += distancia

        coordenadas = hito.find("coordenadasHito")
        altitud = numero(coordenadas.find("altitud"), 0.0) if coordenadas is not None else 0.0

        hitos.append({
            "nombre": nombre,
            "distancia": distancia_acumulada,
            "altitud": altitud
        })

    return hitos


def escalar(valor, minimo, maximo, origen, destino):
    if maximo == minimo:
        return (origen + destino) / 2
    return origen + (valor - minimo) * (destino - origen) / (maximo - minimo)


def escribir_svg(nombre_archivo, nombre_ruta, hitos):
    ancho = 900
    alto = 420
    margen_izquierdo = 70
    margen_derecho = 40
    margen_superior = 45
    margen_inferior = 80

    max_distancia = max(h["distancia"] for h in hitos) if hitos else 1
    min_altitud = min(h["altitud"] for h in hitos) if hitos else 0
    max_altitud = max(h["altitud"] for h in hitos) if hitos else 1

    if min_altitud == max_altitud:
        max_altitud += 10

    puntos = []
    for hito in hitos:
        x = escalar(hito["distancia"], 0, max_distancia, margen_izquierdo, ancho - margen_derecho)
        y = escalar(hito["altitud"], min_altitud, max_altitud, alto - margen_inferior, margen_superior)
        puntos.append((x, y, hito))

    base_y = alto - margen_inferior
    polilinea = " ".join(f"{x:.2f},{y:.2f}" for x, y, _ in puntos)
    poligono = f"{margen_izquierdo},{base_y} {polilinea} {ancho - margen_derecho},{base_y}"

    with open(nombre_archivo, "w", encoding="utf-8") as svg:
        svg.write('<?xml version="1.0" encoding="UTF-8"?>\n')
        svg.write(f'<svg xmlns="http://www.w3.org/2000/svg" width="{ancho}" height="{alto}" viewBox="0 0 {ancho} {alto}">\n')
        svg.write(f'  <title>Altimetría de {nombre_ruta}</title>\n')
        svg.write(f'  <desc>Perfil de altitud de la ruta turística {nombre_ruta} con distancias y altitudes en metros.</desc>\n')
        svg.write('  <rect width="100%" height="100%" fill="#FFFFFF"/>\n')
        svg.write('  <line x1="70" y1="340" x2="860" y2="340" stroke="#003B5C" stroke-width="2"/>\n')
        svg.write('  <line x1="70" y1="45" x2="70" y2="340" stroke="#003B5C" stroke-width="2"/>\n')
        svg.write(f'  <text x="450" y="25" text-anchor="middle" font-family="Verdana" font-size="18" fill="#003B5C">{nombre_ruta}</text>\n')
        svg.write('  <text x="450" y="395" text-anchor="middle" font-family="Verdana" font-size="13" fill="#003B5C">Distancia horizontal acumulada en metros</text>\n')
        svg.write('  <text x="20" y="200" text-anchor="middle" font-family="Verdana" font-size="13" fill="#003B5C" transform="rotate(-90 20,200)">Altitud en metros</text>\n')

        for i in range(0, 6):
            y = escalar(i, 0, 5, base_y, margen_superior)
            alt = min_altitud + (max_altitud - min_altitud) * i / 5
            svg.write(f'  <line x1="70" y1="{y:.2f}" x2="860" y2="{y:.2f}" stroke="#D8F3DC" stroke-width="1"/>\n')
            svg.write(f'  <text x="62" y="{y + 4:.2f}" text-anchor="end" font-family="Verdana" font-size="11" fill="#003B5C">{alt:.0f}</text>\n')

        svg.write(f'  <polygon points="{poligono}" fill="#D8F3DC" stroke="#8F3A2F" stroke-width="2"/>\n')
        svg.write(f'  <polyline points="{polilinea}" fill="none" stroke="#8F3A2F" stroke-width="3"/>\n')

        for x, y, hito in puntos:
            svg.write(f'  <circle cx="{x:.2f}" cy="{y:.2f}" r="5" fill="#003B5C"/>\n')
            svg.write(f'  <text x="{x:.2f}" y="{y - 10:.2f}" text-anchor="middle" font-family="Verdana" font-size="11" fill="#003B5C">{hito["nombre"]}</text>\n')
            svg.write(f'  <text x="{x:.2f}" y="{base_y + 18:.2f}" text-anchor="middle" font-family="Verdana" font-size="10" fill="#003B5C">{hito["distancia"]:.0f}</text>\n')

        svg.write('</svg>\n')


def generar_altimetrias(xml="rutas.xml"):
    arbol = ET.parse(xml)
    raiz = arbol.getroot()

    for ruta in raiz.findall("ruta"):
        nombre_ruta = ruta.get("nombre", "ruta")
        nombre_archivo = ruta.findtext("altimetria") or "altimetria.svg"
        hitos = obtener_hitos(ruta)
        escribir_svg(nombre_archivo, nombre_ruta, hitos)
        print(f"SVG generado: {nombre_archivo}")


def main():
    archivo = input("Introduce el nombre del XML [rutas.xml]: ").strip() or "rutas.xml"
    generar_altimetrias(archivo)


if __name__ == "__main__":
    main()
