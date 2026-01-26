<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Person;
use App\Models\Employee;
use App\Models\HRPosition;
use App\Models\HRContractType;
use Illuminate\Support\Str;

class ImportPersonalExcel extends Command
{
    protected $signature = 'import:personal {file? : Ruta al archivo Excel} {--fresh : Eliminar datos existentes antes de importar}';
    protected $description = 'Importa personal desde archivo Excel (hojas CAS y 276)';

    private $tempDniCounter = 1;
    private $positionsCache = [];
    private $contractTypesCache = [];
    private $importedCount = 0;
    private $skippedCount = 0;
    private $errorsCount = 0;

    public function handle()
    {
        // Si se usa --fresh, limpiar datos existentes
        if ($this->option('fresh')) {
            $this->warn("⚠️  Modo FRESH activado: eliminando datos existentes...");

            if (!$this->confirm('¿Estás seguro de eliminar todos los empleados y personas internas?')) {
                $this->info('Operación cancelada.');
                return 0;
            }

            $employeesCount = Employee::count();
            $personsCount = Person::where('tipo', 'INTERNO')->count();

            Employee::truncate();
            Person::where('tipo', 'INTERNO')->delete();

            $this->info("✓ Eliminados {$employeesCount} empleados y {$personsCount} personas internas");
            $this->newLine();
        }

        $filePath = $this->argument('file') ?? base_path('presonal.xlsx');

        if (!file_exists($filePath)) {
            $this->error("Archivo no encontrado: {$filePath}");
            return 1;
        }

        $this->info("📂 Cargando archivo: {$filePath}");
        
        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Exception $e) {
            $this->error("Error al cargar el archivo: " . $e->getMessage());
            return 1;
        }

        $this->info("📊 Hojas encontradas: " . implode(', ', $spreadsheet->getSheetNames()));
        $this->newLine();

        // Procesar CAS
        if ($spreadsheet->sheetNameExists('CAS')) {
            $this->info("🔄 Procesando hoja CAS...");
            $this->processSheetCAS($spreadsheet->getSheetByName('CAS'));
        }

        // Procesar 276
        if ($spreadsheet->sheetNameExists('276')) {
            $this->info("🔄 Procesando hoja 276...");
            $this->processSheet276($spreadsheet->getSheetByName('276'));
        }

        $this->newLine();
        $this->info("✅ Importación completada:");
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Importados', $this->importedCount],
                ['Omitidos (ya existen)', $this->skippedCount],
                ['Errores', $this->errorsCount],
                ['Cargos creados', count($this->positionsCache)],
                ['Tipos de contrato creados', count($this->contractTypesCache)],
            ]
        );

        if (count($this->positionsCache) > 0) {
            $this->newLine();
            $this->info("📋 Cargos creados:");
            foreach ($this->positionsCache as $nombre => $id) {
                $this->line("  - {$nombre}");
            }
        }

        if (count($this->contractTypesCache) > 0) {
            $this->newLine();
            $this->info("📝 Tipos de contrato creados:");
            foreach ($this->contractTypesCache as $nombre => $id) {
                $this->line("  - {$nombre}");
            }
        }

        return 0;
    }

    /**
     * Procesa hoja CAS: N.°, Apellidos y Nombres, DNI, Cargo
     */
    private function processSheetCAS($sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $bar = $this->output->createProgressBar($highestRow - 1);
        $bar->start();

        for ($row = 2; $row <= $highestRow; $row++) {
            try {
                $num = $sheet->getCell("A{$row}")->getValue();
                $nombreApellido = $sheet->getCell("B{$row}")->getValue();
                $dni = $sheet->getCell("C{$row}")->getValue();
                $cargo = $sheet->getCell("D{$row}")->getValue();

                // Validar que sea un registro válido (no encabezado repetido)
                if (!$num || !is_numeric($num) || !$nombreApellido || !$dni) {
                    $bar->advance();
                    continue;
                }

                $this->importRecord(
                    dni: trim($dni),
                    nombreApellido: $nombreApellido,
                    cargo: $cargo,
                    tipoContrato: 'CAS',
                    email: null,
                    fechaNacimiento: null,
                    formatoNombresApellidos: false // CAS: APELLIDOS, NOMBRES
                );

                $bar->advance();
            } catch (\Exception $e) {
                $bar->advance();
                $this->errorsCount++;
                if ($this->option('verbose')) {
                    $this->newLine();
                    $this->error("Error en fila {$row}: " . $e->getMessage());
                }
            }
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Procesa hoja 276: N°, NOMBRES Y APELLIDOS, CARGO O FUNCIÓN, ÁREA O UNIDAD, REG. LAB., E-MAIL, FECHA NACIMIENTO
     */
    private function processSheet276($sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $bar = $this->output->createProgressBar($highestRow - 1);
        $bar->start();

        for ($row = 2; $row <= $highestRow; $row++) {
            try {
                $num = $sheet->getCell("A{$row}")->getValue();
                $nombreApellido = $sheet->getCell("B{$row}")->getValue();
                $cargo = $sheet->getCell("C{$row}")->getValue();
                // $area = $sheet->getCell("D{$row}")->getValue();
                $email = $sheet->getCell("F{$row}")->getValue();
                $fechaNacimiento = $sheet->getCell("G{$row}")->getValue();

                // Validar que sea un registro válido
                if (!$num || !is_numeric($num) || !$nombreApellido) {
                    $bar->advance();
                    continue;
                }

                // Generar DNI temporal
                $dniTemporal = $this->generateTempDni();

                $this->importRecord(
                    dni: $dniTemporal,
                    nombreApellido: $nombreApellido,
                    cargo: $cargo,
                    tipoContrato: '276',
                    email: $email !== 'NO PROPORCIONÓ' ? $email : null,
                    fechaNacimiento: $fechaNacimiento,
                    formatoNombresApellidos: false // 276: APELLIDOS, NOMBRES
                );

                $bar->advance();
            } catch (\Exception $e) {
                $bar->advance();
                $this->errorsCount++;
                if ($this->option('verbose')) {
                    $this->newLine();
                    $this->error("Error en fila {$row}: " . $e->getMessage());
                }
            }
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Importa un registro individual
     */
    private function importRecord(
        string $dni,
        string $nombreApellido,
        ?string $cargo,
        string $tipoContrato,
        ?string $email,
        $fechaNacimiento,
        bool $formatoNombresApellidos = false
    ) {
        try {
            if ($this->option('verbose')) {
                $this->line("Procesando: {$dni} - {$nombreApellido}");
            }

            // Separar apellidos y nombres según el formato
            $parts = array_map('trim', explode(',', $nombreApellido, 2));

            if ($formatoNombresApellidos) {
                // Formato: "NOMBRES, APELLIDOS"
                $nombres = mb_strtoupper(trim($parts[0] ?? ''), 'UTF-8');
                $apellidos = mb_strtoupper(trim($parts[1] ?? ''), 'UTF-8');
            } else {
                // Formato: "APELLIDOS, NOMBRES"
                $apellidos = mb_strtoupper(trim($parts[0] ?? ''), 'UTF-8');
                $nombres = mb_strtoupper(trim($parts[1] ?? ''), 'UTF-8');
            }

            // Si no hay coma, intentar otro formato
            if (count($parts) < 2 || empty($nombres)) {
                // Asumir que todo es nombre+apellido junto
                $apellidos = mb_strtoupper($nombreApellido, 'UTF-8');
                $nombres = '';
            }

            // Verificar si ya existe por DNI
            $existingPerson = Person::where('dni', $dni)->first();
            if ($existingPerson) {
                $this->skippedCount++;
                if ($this->option('verbose')) {
                    $this->warn("  -> Ya existe (omitido)");
                }
                return;
            }

            // Obtener o crear el cargo
            $positionId = null;
            if ($cargo) {
                $positionId = $this->getOrCreatePosition($cargo);
            }

            // Obtener o crear el tipo de contrato
            $contractTypeId = $this->getOrCreateContractType($tipoContrato);

            // Crear persona
            $person = Person::create([
                'dni' => $dni,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'email' => $email,
                'fecha_nacimiento' => $this->parseDate($fechaNacimiento),
                'tipo' => 'INTERNO',
                'is_active' => true,
            ]);

            // Crear empleado
            Employee::create([
                'person_id' => $person->id,
                'position_id' => $positionId,
                'contract_type_id' => $contractTypeId,
                'fecha_ingreso' => now(),
                'estado' => 'ACTIVO',
            ]);

            $this->importedCount++;

            if ($this->option('verbose')) {
                $this->info("  -> Importado exitosamente");
            }

        } catch (\Exception $e) {
            $this->errorsCount++;
            if ($this->option('verbose')) {
                $this->newLine();
                $this->error("Error en registro {$dni} ({$nombreApellido}): " . $e->getMessage());
            }
            // No hacer throw para que continúe con los siguientes registros
        }
    }

    /**
     * Genera un DNI temporal incremental
     */
    private function generateTempDni(): string
    {
        $temp = 'T' . str_pad($this->tempDniCounter, 7, '0', STR_PAD_LEFT);
        $this->tempDniCounter++;
        return $temp;
    }

    /**
     * Obtiene o crea un cargo en la tabla hr_positions
     */
    private function getOrCreatePosition(string $cargo): string
    {
        $cargoNormalizado = mb_strtoupper(trim($cargo), 'UTF-8');

        if (isset($this->positionsCache[$cargoNormalizado])) {
            return $this->positionsCache[$cargoNormalizado];
        }

        // Buscar existente (case insensitive)
        $existing = HRPosition::whereRaw('UPPER(nombre) = ?', [$cargoNormalizado])->first();

        if ($existing) {
            $this->positionsCache[$cargoNormalizado] = $existing->id;
            return $existing->id;
        }

        // Crear nuevo
        $position = HRPosition::create([
            'nombre' => $cargoNormalizado,
            'descripcion' => null,
            'activo' => true,
        ]);

        $this->positionsCache[$cargoNormalizado] = $position->id;
        return $position->id;
    }

    /**
     * Obtiene o crea un tipo de contrato en la tabla hr_contract_types
     */
    private function getOrCreateContractType(string $tipoContrato): string
    {
        $tipoNormalizado = mb_strtoupper(trim($tipoContrato), 'UTF-8');

        if (isset($this->contractTypesCache[$tipoNormalizado])) {
            return $this->contractTypesCache[$tipoNormalizado];
        }

        // Buscar existente (case insensitive)
        $existing = HRContractType::whereRaw('UPPER(nombre) = ?', [$tipoNormalizado])->first();

        if ($existing) {
            $this->contractTypesCache[$tipoNormalizado] = $existing->id;
            return $existing->id;
        }

        // Crear nuevo
        $contractType = HRContractType::create([
            'nombre' => $tipoNormalizado,
            'descripcion' => 'Generado automáticamente durante importación',
            'activo' => true,
        ]);

        $this->contractTypesCache[$tipoNormalizado] = $contractType->id;
        return $contractType->id;
    }

    /**
     * Parsea fecha desde Excel
     */
    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Si es un número (fecha Excel)
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            }

            // Si ya es una fecha o string
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            // Intentar parsear como string
            return date('Y-m-d', strtotime($value));
        } catch (\Exception $e) {
            return null;
        }
    }
}
