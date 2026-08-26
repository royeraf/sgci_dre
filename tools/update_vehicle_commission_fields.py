#!/usr/bin/env python3
"""Fill the departure/return fields on an already signed vehicle commission.

This tool never re-renders the commission and never edits already-signed
bytes. It only fills empty form fields that were reserved before the first
signature (see prepare_vehicle_commission_fields.py), as a PDF incremental
update. Mirrors update_papeleta_qr_fields.py.
"""

import argparse

from pyhanko.pdf_utils import form_tools, layout
from pyhanko.pdf_utils.font.basic import SimpleFontEngineFactory
from pyhanko.pdf_utils.incremental_writer import IncrementalPdfFileWriter

# Helvetica: the standard PDF font closest to the Arial/sans-serif the rest
# of pdf.vehicle_exit_authorization uses. Only the autorizador's name/DNI
# need it, to match how the solicitante's own name renders (plain HTML
# text). The departure/return data (hours, km, fuel, plate) keeps pyHanko's
# built-in default (Courier) — the monospaced, "typed-in value" look this
# form had before, which the labels around it never had.
_SANS_SERIF_FONT = SimpleFontEngineFactory('Helvetica', 0.5)


def _empty_field(writer, field_name):
    try:
        return form_tools.find_existing_empty_field(writer, field_name, field_type='/Tx')
    except form_tools.FormFillingError:
        return None


def fill_text(writer, field_name: str, value: str, font_size: int = 9, font=None):
    if not value:
        return
    # A later revision may be rebuilt from an already-filled earlier one; a
    # completed field must be left untouched rather than overwritten.
    if _empty_field(writer, field_name) is None:
        return
    box_layout_rule = layout.SimpleBoxLayoutRule(
        layout.AxisAlignment.ALIGN_MID,
        layout.AxisAlignment.ALIGN_MID,
    )
    style_kwargs = dict(
        font_size=font_size,
        leading=max(font_size + 1, 9),
        box_layout_rule=box_layout_rule,
    )
    if font is not None:
        style_kwargs['font'] = font
    form_tools.populate_static_text_field(
        writer,
        field_name,
        form_tools.TextBoxStyle(**style_kwargs),
        value,
    )


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument('--input', required=True)
    parser.add_argument('--output', required=True)
    parser.add_argument('--hora-salida', default='')
    parser.add_argument('--hora-retorno', default='')
    parser.add_argument('--km-salida', default='')
    parser.add_argument('--km-retorno', default='')
    parser.add_argument('--combustible', default='')
    parser.add_argument('--total-km', default='')
    parser.add_argument('--autorizador-nombre', default='')
    parser.add_argument('--autorizador-dni', default='')
    args = parser.parse_args()

    with open(args.input, 'rb') as source, open(args.output, 'wb') as target:
        writer = IncrementalPdfFileWriter(source, strict=False)
        fill_text(writer, 'VEH_HORA_SALIDA', args.hora_salida, 9)
        fill_text(writer, 'VEH_HORA_RETORNO', args.hora_retorno, 9)
        fill_text(writer, 'VEH_KM_SALIDA', args.km_salida, 9)
        fill_text(writer, 'VEH_KM_RETORNO', args.km_retorno, 9)
        fill_text(writer, 'VEH_COMBUSTIBLE', args.combustible, 8)
        fill_text(writer, 'VEH_TOTAL_KM', args.total_km, 9)
        # Font size 6: a full name can run 30+ characters and this box
        # cannot grow (its Rect was fixed before the first signature). At
        # size 8 a long name overflows the box's one line and the layout
        # engine pushes the extra line upward into the label above it.
        fill_text(writer, 'VEH_AUTORIZADOR_NOMBRE', args.autorizador_nombre, 6, font=_SANS_SERIF_FONT)
        fill_text(writer, 'VEH_AUTORIZADOR_DNI', args.autorizador_dni, 6, font=_SANS_SERIF_FONT)
        writer.write(target)
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
