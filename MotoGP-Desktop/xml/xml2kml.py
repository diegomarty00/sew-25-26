import xml.etree.ElementTree as ET

def xml2kml(xml):
    try:
        arbol = ET.parse(xml+".xml")
    except IOError:
        print('No se encuentra el archivo', xml)
        exit()
    except ET.ParseError:
        print("Error procesando el archivo XML =", xml)
        exit()

    raiz = arbol.getroot()
    kmlName = xml+".kml"

    kml = open(kmlName,"w")
    prologo(kml, kmlName)

    setCoordenadas(raiz, kml)
    epilogo(kml)
    kml.close()

    print(f'Se ha convertido el circuito a KML en {kmlName}')

def prologo(kml, name):
    kml.write('<?xml version="1.0" encoding="UTF-8"?>\n')
    kml.write('<kml xmlns="http://www.opengis.net/kml/2.2">\n')
    kml.write('<Document>\n')
    kml.write('<Placemark>\n')
    kml.write('<name>' + name + '</name>\n')
    kml.write('<LineString>\n')
    kml.write('<extrude>1</extrude>\n')
    kml.write('<tessellate>1</tessellate>\n')
    kml.write('<coordinates>\n')

def setCoordenadas(raiz, kml):
    # helper mínimo para lidiar con namespace por defecto
    def localname(tag):
        return tag.split('}', 1)[1] if tag.startswith('{') else tag

    def val(contenedor, nombre):
        # busca el hijo por nombre local y devuelve su texto normalizado
        for h in contenedor:
            if localname(h.tag) == nombre:
                return (h.text or '').strip().replace(',', '.')
        return ''

    # 1) Punto inicial: datos / coordenadasCircuito
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
            lat = val(cc, 'longitud')
            lon = val(cc, 'latitud')
            alt = val(cc, 'altitud') or '0'
            if lon and lat:
                # SIEMPRE en orden KML: longitud,latitud,altitud
                kml.write(f'{lon},{lat},{alt}\n')

    # 2) Resto de puntos: tramos / tramo / coordenadasTramoFin
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
                lat = val(fin, 'longitud')
                lon = val(fin, 'latitud')
                alt = val(fin, 'altitud') or '0'
                if lon and lat:
                    # SIEMPRE en orden KML: longitud,latitud,altitud
                    kml.write(f'{lon},{lat},{alt}\n')




def epilogo(kml):
    kml.write('</coordinates>\n')
    kml.write('<altitudeMode>clampToGround</altitudeMode>\n')
    kml.write('</LineString>\n')
    kml.write('<Style> id="lineaRoja">\n')
    kml.write('<LineStyle>\n')
    kml.write('<color>#ff0000ff</color>\n')
    kml.write('<width>5</width>\n')
    kml.write('</LineStyle>\n')
    kml.write('</Style>\n')
    kml.write('</Placemark>\n')

    kml.write('</Document>\n')
    kml.write('</kml>')

def main():
    xml = input('Introduce el archivo XML de entrada: ')
    xml2kml(xml)

if __name__ == "__main__":
    main()