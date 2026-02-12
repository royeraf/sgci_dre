<?php
/**
 * Script para parsear el texto extraído del Catálogo SBN y generar CSV
 */

$inputFile = __DIR__ . '/catalogo_sbn.txt';
$outputFile = __DIR__ . '/catalogo_sbn.csv';

$lines = file($inputFile, FILE_IGNORE_NEW_LINES);

$grupoGenerico = '';
$clase = '';
$records = [];

foreach ($lines as $line) {
    $line = trim($line);

    // Detectar GRUPO GENÉRICO
    if (preg_match('/^GRUPO\s+GEN[EÉ]RICO\s+(.+)$/iu', $line, $m)) {
        $grupoGenerico = trim($m[1]);
        continue;
    }

    // Detectar CLASE
    if (preg_match('/^CLASE\s+(.+)$/iu', $line, $m)) {
        $clase = trim($m[1]);
        continue;
    }

    // Detectar línea de datos: N° CÓDIGO(8 dígitos) DENOMINACIÓN Unidad
    if (preg_match('/^\s*(\d+)\s+(\d{8})\s+(.+?)\s+Unidad\s*$/i', $line, $m)) {
        $codigo = trim($m[2]);
        $denominacion = trim($m[3]);

        $records[] = [
            $codigo,
            $denominacion,
            $grupoGenerico,
            $clase,
        ];
    }
}

// Escribir CSV
$fp = fopen($outputFile, 'w');
fputcsv($fp, ['codigo', 'denominacion', 'grupo_generico', 'clase']);

foreach ($records as $record) {
    fputcsv($fp, $record);
}

fclose($fp);

echo "Total registros extraídos: " . count($records) . "\n";
echo "CSV guardado en: {$outputFile}\n";
