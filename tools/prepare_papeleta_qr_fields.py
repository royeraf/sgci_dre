#!/usr/bin/env python3
"""Reserve AcroForm fields in a new papeleta before any PAdES signature.

These fields are intentionally added *before* the server, boss and RR.HH.
signatures.  They are later populated by ``update_papeleta_qr_fields.py`` as a
PDF incremental update.  This is why the QR checkpoints can be displayed in
the same one-page document without regenerating its signed content.
"""

import argparse

from pyhanko.pdf_utils import generic
from pyhanko.pdf_utils.incremental_writer import IncrementalPdfFileWriter
from pyhanko.sign.fields import SigFieldSpec, append_signature_field


# A5 portrait coordinates (origin is the lower-left corner).  The positions
# match the empty cells and destination box rendered by papeleta_request.blade.
FIELDS = {
    # A5 coordinates for the official DRE layout in papeleta_request.blade.
    # The narrow cells are in the centre of the duration table; their values
    # are added after QR scanning through a permitted incremental form update.
    "QR_SALIDA_REAL": (135, 370, 165, 396, 0),
    "QR_RETORNO_REAL": (260, 370, 290, 396, 0),
    # The commission confirmation is below the three institutional signatures,
    # as defined by the DRE paper form. It remains a reserved form field.
    # The signature and identity remain inside the official constancia box.
    "QR_DESTINO_FIRMA": (50, 212, 140, 239, 0),
    # Keep every audit datum in its own one-line field. This avoids a long
    # personal name overflowing into the signature area in PDF viewers.
    "QR_DESTINO_NOMBRE": (145, 229, 370, 239, 0),
    "QR_DESTINO_DNI": (145, 218, 370, 228, 0),
    "QR_DESTINO_COORDENADAS": (145, 204, 370, 218, 0),
    # The GPS map intentionally uses the clear lower portion of the page,
    # not the signature/seal rectangle above it.
    "QR_DESTINO_MAP": (50, 55, 370, 145, 0),
}

# These approval fields must also be present before the certification signature.
# Adding a new signature widget after certification is a structural change and
# is correctly rejected by strict PDF validators, even when form filling itself
# is permitted.
SIGNATURE_FIELDS = {
    # The cards sit immediately above the dotted signature lines in the
    # official layout.  Their labels remain unobstructed below each card.
    'Papeleta_SERVIDOR': (42, 277, 136, 335),
    'Papeleta_JEFE_INMEDIATO': (160, 277, 255, 335),
    'Papeleta_RRHH': (282, 277, 380, 335),
}


def _name(value: str):
    return generic.pdf_name(value)


def add_static_text_field(writer, page_ref, page, acroform, field_name, spec):
    """Add one combined field/widget annotation to the first page.

    A combined field/widget is deliberate: pyHanko's form helper can find it
    reliably, render a static appearance and set it read-only after QR data is
    recorded.  Nothing in this function is called after a digital signature.
    """
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
            _name('/F'): generic.NumberObject(4),  # print the appearance
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
        # ``find_page_for_modification`` returns a page reference and its
        # resources, not the page dictionary itself.
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

        for field_name, box in SIGNATURE_FIELDS.items():
            append_signature_field(
                writer,
                SigFieldSpec(sig_field_name=field_name, on_page=0, box=box),
            )

        writer.update_container(acroform)
        # ``page`` was retrieved through an indirect page reference. Marking
        # that reference explicitly is what persists the /Annots array, which
        # lets every viewer paint the field appearances (not just read /V).
        writer.mark_update(page_ref)
        writer.write(target)
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
