<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleCommission extends Model
{
    use HasUuids;

    protected $fillable = [
        'numero',
        'anio',
        'solicitante_employee_id',
        'dia',
        'hora',
        'lugar',
        'ambito_destino',
        'referencia',
        'motivo',
        'usuarios',
        'vehicle_id',
        'conductor_employee_id',
        'autorizado_por',
        'fecha_autorizacion',
        'comentario_autorizacion',
        'fecha_confirmacion_conductor',
        'fecha_salida',
        'hora_salida',
        'fecha_retorno',
        'hora_regreso',
        'km_salida',
        'km_retorno',
        'combustible',
        'pnro',
        'estado',
        'executed_document_path',
        'executed_document_revision',
    ];

    protected $casts = [
        'dia' => 'date',
        'fecha_autorizacion' => 'datetime',
        'fecha_confirmacion_conductor' => 'datetime',
        'fecha_salida' => 'date',
        'fecha_retorno' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function solicitanteEmployee()
    {
        return $this->belongsTo(Employee::class, 'solicitante_employee_id');
    }

    public function getSolicitanteNombreAttribute(): ?string
    {
        return $this->solicitanteEmployee?->person?->nombre_full;
    }

    public function conductorEmployee()
    {
        return $this->belongsTo(Employee::class, 'conductor_employee_id');
    }

    public function passengers(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'vehicle_commission_passengers', 'vehicle_commission_id', 'employee_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(VehicleCommissionSignature::class, 'vehicle_commission_id');
    }

    public function getConductorNombreAttribute(): ?string
    {
        return $this->conductorEmployee?->person?->nombre_full;
    }

    public function getConductorLicenciaAttribute(): ?string
    {
        $lic = $this->conductorEmployee?->driverLicense;

        return $lic ? "{$lic->numero} ({$lic->categoria})" : null;
    }

    public function autorizadorEmployee()
    {
        return $this->belongsTo(Employee::class, 'autorizado_por');
    }

    public function getAutorizadoPorNombreAttribute(): ?string
    {
        return $this->autorizadorEmployee?->person?->nombre_full;
    }

    /**
     * Estado en el que se acepta la solicitud pendiente de autorización.
     */
    public function necesitaAutorizacion(): bool
    {
        return $this->estado === 'PENDIENTE';
    }

    /**
     * Estado en el que el conductor asignado debe confirmar la salida.
     */
    public function necesitaConfirmacionConductor(): bool
    {
        return $this->estado === 'AUTORIZADA';
    }

    public function estaAutorizada(): bool
    {
        return !is_null($this->autorizado_por);
    }

    /**
     * Get the next correlative number for a given year (resets each year).
     */
    public static function nextNumero(int $anio): int
    {
        $last = self::where('anio', $anio)->max('numero');

        return ($last ?? 0) + 1;
    }

    public function getTotalKmRecorridoAttribute(): ?int
    {
        if (is_numeric($this->km_salida) && is_numeric($this->km_retorno)) {
            return max(0, (int) $this->km_retorno - (int) $this->km_salida);
        }

        return null;
    }

    /**
     * The most recent PDF revision, whichever came last: a raw PAdES
     * signature or an "executed" revision with departure/return data filled
     * in. These two update streams can interleave in any order (e.g.
     * "Gestionar" can register execution data between two signature steps),
     * so recency — not a fixed priority between the two — decides which one
     * the next signature or the next execution update must build on top of.
     *
     * Signing and refreshing are routinely two steps of the very same
     * request, so they can legitimately land in the same wall-clock second;
     * comparing by timestamp made that tie pick the wrong "latest" revision
     * often enough to matter. `document_revision` is a single counter
     * incremented atomically by both streams precisely to make this
     * comparison exact, never approximate.
     */
    public function latestDocumentPath(): ?string
    {
        $this->loadMissing('signatures');
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        $best = null;
        $bestRevision = -1;

        $latestSignature = $this->signatures
            ->filter(fn ($signature) => $signature->signed_document_path !== 'pending')
            ->sortByDesc('document_revision')
            ->first();
        if ($latestSignature && $disk->exists($latestSignature->signed_document_path)) {
            $best = $latestSignature->signed_document_path;
            $bestRevision = $latestSignature->document_revision;
        }

        if (
            $this->executed_document_path
            && $this->executed_document_revision !== null
            && $this->executed_document_revision > $bestRevision
            && $disk->exists($this->executed_document_path)
        ) {
            $best = $this->executed_document_path;
        }

        return $best;
    }

    /**
     * Atomically bump the shared document-revision counter and return the
     * new value, so the caller can stamp the artifact it is about to create
     * (a signature or an executed revision) with an unambiguous position in
     * the single combined timeline. Must be called right before creating
     * that artifact, not before the potentially slow work that produces it,
     * so two concurrent bumps never get attributed to the wrong artifact.
     */
    public function nextDocumentRevision(): int
    {
        $this->increment('document_revision');

        return $this->document_revision;
    }

    /**
     * Get the status display class
     */
    public function getStatusClassAttribute(): string
    {
        return match($this->estado) {
            'AUTORIZADA' => 'bg-indigo-100 text-indigo-800',
            'CONFIRMADA' => 'bg-cyan-100 text-cyan-800',
            'EN_COMISION' => 'bg-blue-100 text-blue-800',
            'COMPLETADA' => 'bg-green-100 text-green-800',
            'RECHAZADA' => 'bg-red-100 text-red-800',
            'CANCELADA' => 'bg-gray-100 text-gray-800',
            default => 'bg-yellow-100 text-yellow-800',
        };
    }
}
