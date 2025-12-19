
import xml.etree.ElementTree as ET
import math

# ----------------------------
# Utilidades
# ----------------------------
def localname(tag):
    return tag.split('}', 1)[1] if tag.startswith('{') else tag

def get_child_text(node, name, default=''):
    for h in node:
        if localname(h.tag) == name:
            return (h.text or '').strip().replace(',', '.')
    return default

def haversine(lon1, lat1, lon2, lat2):
    """Distancia en metros entre dos coordenadas WGS84."""
    R = 6371000.0
    phi1, phi2 = math.radians(lat1), math.radians(lat2)
    dphi = math.radians(lat2 - lat1)
    dlmb = math.radians(lon2 - lon1)
    a = math.sin(dphi/2)**2 + math.cos(phi1)*math.cos(phi2)*math.sin(dlmb/2)**2
    return R * 2 * math.atan2(math.sqrt(a), math.sqrt(1-a))

# ----------------------------
# Flujo principal
# ----------------------------
def xml2svg(xml):
    # abrir y parsear
    try:
        arbol = ET.parse(xml + ".xml")
    except IOError:
        print('No se encuentra el archivo', xml)
        exit(1)
    except ET.ParseError:
        print("Error procesando el archivo XML =", xml)
        exit(1)

    raiz = arbol.getroot()
    svg_name = "altimetria.svg"

    # extraer puntos (lat, lon, alt) como en tu xml2kml
    puntos = []

    # punto inicial: datos/coordenadasCircuito
    datos = None
    for hijo in raiz:
        if localname(hijo.tag) == 'datos':
            datos = hijo
            break
    if datos is not None:
        cc = None
        for h in datos:
            if localname(h.tag) == 'coordenadasCircuito':
                cc = h
                break
        if cc is not None:
            lon = float(get_child_text(cc, 'longitud', '0'))
            lat = float(get_child_text(cc, 'latitud', '0'))
            alt = float(get_child_text(cc, 'altitud', '0'))
            puntos.append((lat, lon, alt))

    # resto: tramos/tramo/coordenadasTramoFin
    tramos = None
    for hijo in raiz:
        if localname(hijo.tag) == 'tramos':
            tramos = hijo
            break
    if tramos is not None:
        for tramo in tramos:
            if localname(tramo.tag) != 'tramo':
                continue
            fin = None
            for h in tramo:
                if localname(h.tag) == 'coordenadasTramoFin':
                    fin = h
                    break
            if fin is not None:
                lon = float(get_child_text(fin, 'longitud', '0'))
                lat = float(get_child_text(fin, 'latitud', '0'))
                alt = float(get_child_text(fin, 'altitud', '0'))
                puntos.append((lat, lon, alt))

    if len(puntos) < 2:
        print("No hay suficientes puntos para generar la altimetría.")
        exit(1)

    # calcular distancia acumulada (usamos lat/lon; alt para perfil)
    dists = [0.0]
    total = 0.0
    for i in range(1, len(puntos)):
        lat1, lon1, _ = puntos[i-1]
        lat2, lon2, _ = puntos[i]
        total += haversine(lon1, lat1, lon2, lat2)
        dists.append(total)

    alts = [p[2] for p in puntos]

    # construir SVG
    svg = open(svg_name, "w", encoding="utf-8")
    prologo(svg)
    setPerfil(svg, dists, alts)
    epilogo(svg)
    svg.close()

    print(f'Se ha generado la altimetría en {svg_name}')

def prologo(svg):
    # tamaño básico
    svg.write('<svg xmlns="http://www.w3.org/2000/svg" width="900" height="380" viewBox="0 0 900 380">\n')
    svg.write('<style>')
    svg.write('text{font-family:Segoe UI,Arial,sans-serif;fill:#222}')
    svg.write('.axis{stroke:#333;stroke-width:1}')
    svg.write('.grid{stroke:#bbb;stroke-width:0.6;stroke-dasharray:3 3}')
    svg.write('.profile{fill:rgba(220,0,0,0.18);stroke:#d00;stroke-width:2}')
    svg.write('</style>\n')

def setPerfil(svg, dists, alts):
    W, H, M = 900, 380, 48
    x0, y0 = M, H - M

    max_d = max(dists)
    min_a, max_a = min(alts), max(alts)
    # respiración vertical
    pad = max(5.0, 0.05 * (max_a - min_a or 1))
    min_a -= pad
    max_a += pad

    # escalas
    sx = (W - 2*M) / (max_d if max_d > 0 else 1)
    sy = (H - 2*M) / ((max_a - min_a) if (max_a - min_a) > 0 else 1)

    # ejes
    svg.write(f'<line class="axis" x1="{x0}" y1="{y0}" x2="{W-M}" y2="{y0}"/>\n')  # X
    svg.write(f'<line class="axis" x1="{M}" y1="{H-M}" x2="{M}" y2="{M}"/>\n')      # Y

    # generar puntos polilínea
    pts = []
    for d, a in zip(dists, alts):
        x = x0 + d * sx
        y = y0 - (a - min_a) * sy
        pts.append(f"{x:.2f},{y:.2f}")

    # cerrar al suelo para efecto relleno
    pts_closed = [f"{x0:.2f},{y0:.2f}"] + pts + [f"{x0 + max_d*sx:.2f},{y0:.2f}"]

    # título y etiquetas mínimas
    svg.write(f'<text x="{W/2:.0f}" y="{M-18}" font-size="16" text-anchor="middle">Altimetría del circuito</text>\n')
    svg.write(f'<text x="{W/2:.0f}" y="{H-12}" font-size="12" text-anchor="middle">Distancia (m)</text>\n')
    svg.write(f'<text x="18" y="{H/2:.0f}" font-size="12" text-anchor="middle" transform="rotate(-90,18,{H/2:.0f})">Altitud (m)</text>\n')

    # polilínea
    svg.write(f'<polyline class="profile" points="{" ".join(pts_closed)}"/>\n')

def epilogo(svg):
    svg.write('</svg>')

def main():
    xml = input('Introduce el nombre base del XML (sin .xml): ')
    xml2svg(xml)

if __name__ == "__main__":
    main()
