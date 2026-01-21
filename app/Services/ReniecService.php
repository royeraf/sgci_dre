<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReniecService
{
    protected string $apiUrl;
    protected string $token;

    public function __construct()
    {
        $this->apiUrl = config('services.reniec.url');
        $this->token = config('services.reniec.token');
    }

    /**
     * Consultar datos de una persona por DNI
     *
     * @param string $dni
     * @return array
     */
    public function consultarDni(string $dni): array
    {
        try {
            // Validar que el DNI tenga 8 dígitos
            if (!preg_match('/^\d{8}$/', $dni)) {
                return [
                    'success' => false,
                    'message' => 'El DNI debe tener exactamente 8 dígitos numéricos.',
                    'data' => null
                ];
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->timeout(10)
            ->get($this->apiUrl, [
                'numero' => $dni
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Verificar si hay error en la respuesta
                if (isset($data['error']) && $data['error']) {
                    return [
                        'success' => false,
                        'message' => $data['message'] ?? 'Error al consultar el DNI.',
                        'data' => null
                    ];
                }

                // La API de decolecta.com devuelve:
                // first_name, first_last_name, second_last_name, full_name, document_number
                if (empty($data['first_name']) && empty($data['full_name'])) {
                    return [
                        'success' => false,
                        'message' => 'No se encontraron datos para este DNI.',
                        'data' => null
                    ];
                }

                // Formatear respuesta exitosa
                return [
                    'success' => true,
                    'message' => 'Consulta exitosa',
                    'data' => [
                        'dni' => $data['document_number'] ?? $dni,
                        'nombres' => $data['first_name'] ?? '',
                        'apellido_paterno' => $data['first_last_name'] ?? '',
                        'apellido_materno' => $data['second_last_name'] ?? '',
                        'nombre_completo' => $this->formatNombreCompleto($data),
                    ]
                ];
            }

            // Error de la API
            Log::warning('RENIEC API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'No se pudo obtener información del DNI. Verifique que el número sea correcto.',
                'data' => null
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('RENIEC API Connection Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error de conexión con el servicio RENIEC. Intente nuevamente.',
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error('RENIEC API Exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error inesperado al consultar el DNI.',
                'data' => null
            ];
        }
    }

    /**
     * Formatear nombre completo a partir de los datos de la API
     * El formato preferido es: NOMBRES APELLIDO_PATERNO APELLIDO_MATERNO
     */
    private function formatNombreCompleto(array $data): string
    {
        // Si ya viene el nombre completo, usarlo pero reordenarlo
        // La API devuelve "APELLIDOS NOMBRES", queremos "NOMBRES APELLIDOS"
        $firstName = $data['first_name'] ?? '';
        $firstLastName = $data['first_last_name'] ?? '';
        $secondLastName = $data['second_last_name'] ?? '';
        
        $partes = array_filter([
            trim($firstName),
            trim($firstLastName),
            trim($secondLastName)
        ]);

        return strtoupper(implode(' ', $partes));
    }
}

