#!/usr/bin/env python3
"""Fill QR-controlled fields on an already signed papeleta incrementally.

This tool never re-renders a papeleta and it never edits the signed bytes.  It
only fills empty form fields that were reserved before certification.  The
output is a new incremental PDF revision, suitable to become the document that
the application serves as the final one-page papeleta.
"""

import argparse
import os
from io import BytesIO
from math import atan, asinh, pi, tan
from urllib.request import Request, urlopen

from PIL import Image, ImageDraw, ImageFont
from pyhanko.pdf_utils import form_tools, generic, layout
from pyhanko.pdf_utils.incremental_writer import IncrementalPdfFileWriter
from pyhanko.pdf_utils.images import PdfImage
from pyhanko.stamp import StaticStampStyle


def _empty_field(writer, field_name):
    """Return an empty field, or None when it was already completed."""
    try:
        return form_tools.find_existing_empty_field(writer, field_name, field_type='/Tx')
    except form_tools.FormFillingError:
        return None


def fill_text(writer, field_name: str, value: str, font_size: int):
    if not value:
        return
    # The application may rebuild a later QR revision using the already-filled
    # previous revision.  A completed field must be left untouched.
    if _empty_field(writer, field_name) is None:
        return
    # QR hours are short values in bordered cells.  The default layout anchors
    # text at the bottom, which makes the digits look cut off in PDF viewers.
    # Center only these two values horizontally and vertically.
    is_hour = field_name in {'QR_SALIDA_REAL', 'QR_RETORNO_REAL'}
    box_layout_rule = (
        layout.SimpleBoxLayoutRule(
            layout.AxisAlignment.ALIGN_MID,
            layout.AxisAlignment.ALIGN_MID,
        )
        if is_hour else None
    )
    form_tools.populate_static_text_field(
        writer,
        field_name,
        form_tools.TextBoxStyle(
            font_size=font_size,
            leading=max(font_size + 1, 9),
            box_layout_rule=box_layout_rule,
        ),
        value,
    )


def fill_signature(writer, field_name: str, image_path: str, value: str):
    if not image_path or not os.path.isfile(image_path):
        return
    field_ref = _empty_field(writer, field_name)
    if field_ref is None:
        return

    field = field_ref.get_object()
    annotation = form_tools.get_single_field_annot(field)
    width, height = form_tools.annot_width_height(annotation)

    # The touchscreen stroke is the normal appearance of the pre-existing
    # field, not an annotation pasted over the signed page.
    with Image.open(image_path).convert('RGBA') as signature:
        stamp = StaticStampStyle(
            border_width=0,
            background=PdfImage(signature.copy()),
        ).create_stamp(writer, layout.BoxConstraints(width=width, height=height), {})
        stamp.apply_appearance(annotation)

    field['/V'] = generic.TextStringObject(value or 'Firma tactil registrada')
    field['/Ff'] = generic.NumberObject(int(field.get('/Ff', 0)) | 1)
    field.pop('/DA', None)
    writer.update_container(annotation)
    writer.mark_update(field_ref)


def _tile_number(latitude: float, longitude: float, zoom: int):
    """Return the fractional OpenStreetMap tile position for a coordinate."""
    scale = 2 ** zoom
    x = (longitude + 180.0) / 360.0 * scale
    lat_rad = latitude * pi / 180.0
    y = (1.0 - asinh(tan(lat_rad)) / pi) / 2.0 * scale
    return x, y


def _gps_map(latitude: float, longitude: float) -> Image.Image:
    """Build a small, self-contained OSM map with a marker.

    Tile retrieval is best effort: a GPS confirmation must not fail merely
    because the map provider is temporarily unreachable.  The fallback still
    preserves an explicit coordinate marker in the PDF.
    """
    width, height, zoom, tile_size = 640, 180, 16, 256
    map_image = Image.new('RGB', (width, height), '#e8eef5')
    x, y = _tile_number(latitude, longitude, zoom)
    center_x, center_y = width / 2, height / 2
    first_x = int(x - center_x / tile_size) - 1
    first_y = int(y - center_y / tile_size) - 1

    for tx in range(first_x, first_x + 5):
        for ty in range(first_y, first_y + 4):
            try:
                request = Request(
                    f'https://tile.openstreetmap.org/{zoom}/{tx}/{ty}.png',
                    headers={'User-Agent': 'SGCI-DRE/1.0 (papeleta QR)'},
                )
                with urlopen(request, timeout=3) as response:
                    tile = Image.open(BytesIO(response.read())).convert('RGB')
                left = round((tx - x) * tile_size + center_x)
                top = round((ty - y) * tile_size + center_y)
                map_image.paste(tile, (left, top))
            except Exception:
                # A neutral background is intentional when offline; the
                # coordinates and red marker remain available for audit.
                pass

    draw = ImageDraw.Draw(map_image)
    draw.ellipse((center_x - 10, center_y - 10, center_x + 10, center_y + 10), fill='#dc2626', outline='white', width=3)
    draw.line((center_x, center_y + 10, center_x, center_y + 21), fill='#991b1b', width=3)
    label = f'GPS: {latitude:.6f}, {longitude:.6f}'
    draw.rectangle((4, height - 20, 250, height - 3), fill='white')
    draw.text((8, height - 18), label, fill='#111827', font=ImageFont.load_default())
    return map_image


def fill_map(writer, field_name: str, latitude: str, longitude: str):
    if not latitude or not longitude:
        return
    try:
        image = _gps_map(float(latitude), float(longitude))
    except (TypeError, ValueError):
        return

    field_ref = _empty_field(writer, field_name)
    if field_ref is None:
        return
    field = field_ref.get_object()
    annotation = form_tools.get_single_field_annot(field)
    width, height = form_tools.annot_width_height(annotation)
    stamp = StaticStampStyle(border_width=0, background=PdfImage(image)).create_stamp(
        writer, layout.BoxConstraints(width=width, height=height), {}
    )
    stamp.apply_appearance(annotation)
    field['/V'] = generic.TextStringObject(f'GPS map: {latitude}, {longitude}')
    field['/Ff'] = generic.NumberObject(int(field.get('/Ff', 0)) | 1)
    field.pop('/DA', None)
    writer.update_container(annotation)
    writer.mark_update(field_ref)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument('--input', required=True)
    parser.add_argument('--output', required=True)
    parser.add_argument('--salida', default='')
    parser.add_argument('--retorno', default='')
    parser.add_argument('--destino-detalle', default='')  # Backward compatibility only.
    parser.add_argument('--destino-firma', default='')
    parser.add_argument('--destino-nombre', default='')
    parser.add_argument('--destino-dni', default='')
    parser.add_argument('--destino-coordenadas', default='')
    parser.add_argument('--latitude', default='')
    parser.add_argument('--longitude', default='')
    args = parser.parse_args()

    with open(args.input, 'rb') as source, open(args.output, 'wb') as target:
        writer = IncrementalPdfFileWriter(source, strict=False)
        fill_text(writer, 'QR_SALIDA_REAL', args.salida, 11)
        fill_text(writer, 'QR_RETORNO_REAL', args.retorno, 11)
        # New papeletas use three short fields so names, DNI and coordinates
        # remain readable alongside the tactile signature.
        fill_text(writer, 'QR_DESTINO_NOMBRE', args.destino_nombre, 6)
        fill_text(writer, 'QR_DESTINO_DNI', args.destino_dni, 6)
        fill_text(writer, 'QR_DESTINO_COORDENADAS', args.destino_coordenadas, 6)
        # This only applies to old, already prepared documents that have the
        # previous combined field; it is never used on the new layout.
        fill_text(writer, 'QR_DESTINO_DETALLE', args.destino_detalle, 7)
        fill_signature(writer, 'QR_DESTINO_FIRMA', args.destino_firma, 'Firma tactil de destino')
        fill_map(writer, 'QR_DESTINO_MAP', args.latitude, args.longitude)
        writer.write(target)
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
