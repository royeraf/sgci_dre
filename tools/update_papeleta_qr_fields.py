#!/usr/bin/env python3
"""Fill QR-controlled fields on an already signed papeleta incrementally.

This tool never re-renders a papeleta and it never edits the signed bytes.  It
only fills empty form fields that were reserved before certification.  The
output is a new incremental PDF revision, suitable to become the document that
the application serves as the final one-page papeleta.
"""

import argparse
import os

from PIL import Image
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


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument('--input', required=True)
    parser.add_argument('--output', required=True)
    parser.add_argument('--salida', default='')
    parser.add_argument('--retorno', default='')
    parser.add_argument('--destino-detalle', default='')
    parser.add_argument('--destino-firma', default='')
    args = parser.parse_args()

    with open(args.input, 'rb') as source, open(args.output, 'wb') as target:
        writer = IncrementalPdfFileWriter(source, strict=False)
        fill_text(writer, 'QR_SALIDA_REAL', args.salida, 11)
        fill_text(writer, 'QR_RETORNO_REAL', args.retorno, 11)
        fill_text(writer, 'QR_DESTINO_DETALLE', args.destino_detalle, 7)
        fill_signature(writer, 'QR_DESTINO_FIRMA', args.destino_firma, 'Firma tactil de destino')
        writer.write(target)
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
