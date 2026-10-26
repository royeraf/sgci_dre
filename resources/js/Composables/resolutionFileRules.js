export function preferredRevisionKey(path) {
    return path.replace(/\s*\[R\](?=\.pdf$)/iu, '').toLocaleLowerCase('es');
}
export function isPreferredRevision(path) { return /\[R\]\.pdf$/iu.test(path); }

export function isAuxiliaryResolutionFile(filename) {
    const stem = filename.replace(/(?:\.\s*pdf)+$/iu,'').trim().normalize('NFD').replace(/[\u0300-\u036f]/g,'').toUpperCase();
    return /^(?:(?:\d{1,6}[\s._-]+)?(?:ACTUAD|ACUTAD)[\p{L}]*\b|NOTIFICACION\b|CEDULA\s+DE\s+NOTIFICACION\b|OFICIO\b|MEMORAND(?:O|UM)\b|INFORME\b|CARTA\b|~\$|CX?$)/u.test(stem);
}
