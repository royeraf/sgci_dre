#!/usr/bin/env python3
"""Reserve AcroForm fields in a new vehicle commission PDF before any signature.

The departure/return data (real hours, km, fuel, P/N) is only known *after*
the driver has already confirmed the authorization, i.e. after all three
signatures. These fields are reserved here, empty, so that
``update_vehicle_commission_fields.py`` can fill them later as a PDF
incremental update, without ever re-rendering or altering the already
signed page content. Mirrors prepare_papeleta_qr_fields.py.
"""

import argparse

from pyhanko.pdf_utils import generic
from pyhanko.pdf_utils.incremental_writer import IncrementalPdfFileWriter


# A5 landscape points (bottom-left origin). Measured with
# `pdftotext -bbox` against a real render of pdf.vehicle_exit_authorization
# and converted from its top-left word coordinates.
FIELDS = {
    'VEH_HORA_SALIDA': (80, 237, 190, 248, 0),
    'VEH_HORA_RETORNO': (395, 237, 505, 248, 0),
    'VEH_KM_SALIDA': (74, 222, 184, 233, 0),
    'VEH_KM_RETORNO': (390, 222, 500, 233, 0),
    'VEH_COMBUSTIBLE': (73, 207, 183, 217, 0),
    'VEH_TOTAL_KM': (99, 192, 264, 202, 0),
    # Below "Funcionario que autoriza": unknown at first render (that
    # signer hasn't been chosen yet), filled in as soon as they sign.
    'VEH_AUTORIZADOR_NOMBRE': (213, 89, 378, 98, 0),
    'VEH_AUTORIZADOR_DNI': (213, 79, 378, 88, 0),
}


def _name(value: str):
    return generic.pdf_name(value)


def add_static_text_field(writer, page_ref, page, acroform, field_name, spec):
    x1, y1, x2, y2, flags = spec
    field = generic.DictionaryObject(
        {
            _name('/FT'): _name('/Tx'),
            _name('/T'): generic.TextStringObject(field_name),
            _name('/Type'): _name('/Annot'),
            _name('/Subtype'): _name('/Widget'),
            _name('/Rect'): generic.ArrayObject(
                [
                    generic.FloatObject(x1), generic.FloatObject(y1),
                    generic.FloatObject(x2), generic.FloatObject(y2),
                ]
            ),
            _name('/P'): page_ref,
            _name('/F'): generic.NumberObject(4),
            _name('/Ff'): generic.NumberObject(flags),
        }
    )
    field_ref = writer.add_object(field)
    acroform['/Fields'].append(field_ref)

    try:
        annotations = page['/Annots']
    except KeyError:
        annotations = generic.ArrayObject()
        page['/Annots'] = annotations
    annotations.append(field_ref)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument('--input', required=True)
    parser.add_argument('--output', required=True)
    args = parser.parse_args()

    with open(args.input, 'rb') as source, open(args.output, 'wb') as target:
        writer = IncrementalPdfFileWriter(source, strict=False)
        page_ref, _ = writer.find_page_for_modification(0)
        page = page_ref.get_object()

        try:
            acroform = writer.root['/AcroForm']
        except KeyError:
            acroform = generic.DictionaryObject(
                {
                    _name('/Fields'): generic.ArrayObject(),
                    _name('/NeedAppearances'): generic.BooleanObject(False),
                }
            )
            writer.root['/AcroForm'] = writer.add_object(acroform)
            writer.update_root()

        if '/Fields' not in acroform:
            acroform['/Fields'] = generic.ArrayObject()

        for field_name, spec in FIELDS.items():
            add_static_text_field(writer, page_ref, page, acroform, field_name, spec)

        writer.update_container(acroform)
        writer.mark_update(page_ref)
        writer.write(target)
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
