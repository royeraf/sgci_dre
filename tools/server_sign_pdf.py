#!/usr/bin/env python3
import argparse
import os
import sys

from PIL import Image, ImageDraw, ImageFont
from pyhanko.pdf_utils.incremental_writer import IncrementalPdfFileWriter
from pyhanko.pdf_utils.images import PdfImage
from pyhanko.sign import signers
from pyhanko.sign.fields import MDPPerm, SigFieldSpec, SigSeedSubFilter
from pyhanko.stamp import StaticStampStyle


POSITIONS = {
    # A5 portrait coordinates. These boxes sit above each signature line.
    "SERVIDOR": (42, 130, 136, 190),
    "JEFE_INMEDIATO": (160, 130, 255, 190),
    "RRHH": (282, 130, 380, 190),
    "JEFATURA_DESTINO": (145, 210, 275, 245),
}


def _font(size: int, bold: bool = False):
    names = (
        ["/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", "DejaVuSans-Bold.ttf"]
        if bold
        else ["/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", "DejaVuSans.ttf"]
    )
    for name in names:
        try:
            return ImageFont.truetype(name, size)
        except OSError:
            continue
    return ImageFont.load_default()


def _wrap(draw: ImageDraw.ImageDraw, value: str, font, max_width: int) -> list[str]:
    words = (value or "-").split()
    lines: list[str] = []
    current = ""
    for word in words:
        candidate = word if not current else f"{current} {word}"
        if current and draw.textlength(candidate, font=font) > max_width:
            lines.append(current)
            current = word
        else:
            current = candidate
    if current:
        lines.append(current)
    return lines or ["-"]


def signature_card(args) -> Image.Image:
    """Build the visible PAdES appearance that is signed with the PDF."""
    width, height = 1000, 530
    card = Image.new("RGB", (width, height), "white")
    draw = ImageDraw.Draw(card)

    logo_width = 230
    if args.logo and os.path.isfile(args.logo):
        with Image.open(args.logo).convert("RGBA") as logo:
            logo.thumbnail((200, 200), Image.Resampling.LANCZOS)
            card.paste(logo, (24, (height - logo.height) // 2), logo)

    left = logo_width + 18
    font = _font(46)
    title_font = _font(49, bold=True)
    y = 32
    draw.text((left, y), "Firmado digitalmente por:", fill="#111111", font=title_font)
    y += 68

    values = [
        args.signer,
        f"DNI: {args.dni}",
        f"Motivo: {args.reason}",
        f"Fecha: {args.signed_at}",
        f"Cargo: {args.position}",
    ]
    for value in values:
        for line in _wrap(draw, value, font, width - left - 24):
            draw.text((left, y), line, fill="#111111", font=font)
            y += 57
            if y > height - 50:
                return card
    return card


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", required=True)
    parser.add_argument("--output", required=True)
    parser.add_argument("--pfx", required=True)
    parser.add_argument("--field", required=True)
    parser.add_argument("--role", required=True)
    parser.add_argument("--signer", required=True)
    parser.add_argument("--dni", required=True)
    parser.add_argument("--reason", required=True)
    parser.add_argument("--position", required=True)
    parser.add_argument("--signed-at", required=True)
    parser.add_argument("--logo", required=False, default="")
    parser.add_argument(
        "--existing-field",
        action="store_true",
        help="Sign a field reserved before the certification signature.",
    )
    parser.add_argument(
        "--certify",
        action="store_true",
        help="Certify the first signature and permit only AcroForm filling afterwards.",
    )
    args = parser.parse_args()

    password = os.environ.get("DREH_PFX_PASSWORD", "").encode("utf-8")
    signer = signers.SimpleSigner.load_pkcs12(args.pfx, passphrase=password)
    if signer is None:
        raise ValueError("No se pudo abrir el certificado PKCS#12")

    metadata = signers.PdfSignatureMetadata(
        field_name=args.field,
        reason=os.environ.get("DREH_SIGN_REASON", "Firma digital RENIEC"),
        location=os.environ.get("DREH_SIGN_LOCATION", "DRE Huánuco"),
        subfilter=SigSeedSubFilter.PADES,
        md_algorithm="sha256",
        # A new QR-enabled papeleta is certified by the employee.  P=2 allows
        # later form completion (QR times / destination confirmation) and the
        # remaining approval signatures, but not a rewrite of page content.
        certify=args.certify,
        docmdp_permissions=MDPPerm.FILL_FORMS,
    )
    field = None if args.existing_field else SigFieldSpec(
        sig_field_name=args.field,
        on_page=0,
        box=POSITIONS.get(args.role, (42, 130, 136, 190)),
    )
    stamp_style = StaticStampStyle(
        border_width=0,
        background=PdfImage(signature_card(args)),
    )
    with open(args.input, "rb") as source, open(args.output, "wb") as target:
        writer = IncrementalPdfFileWriter(source, strict=False)
        signers.PdfSigner(
            metadata,
            signer=signer,
            stamp_style=stamp_style,
            new_field_spec=field,
        ).sign_pdf(writer, output=target)
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:
        print(str(exc), file=sys.stderr)
        raise
