<?php

namespace App\Support\CertificationImport;

final class CertificationImportDataNotice
{
    public function __construct(
        private readonly CertificationImportConfig $config,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'destination' => 'Codex/OpenAI',
            'model' => $this->config->model(),
            'model_label' => $this->config->modelLabel(),
            'reasoning_effort' => $this->config->reasoningEffort(),
            'reasoning_effort_label' => $this->config->reasoningEffortLabel(),
            'sent' => [
                'El texto nativo y/o el texto obtenido por OCR local del PDF, página por página, después de retirar los datos personales registrados en tu Perfil Maestro.',
                'La indicación de si cada página proviene de texto nativo, OCR local o una combinación de ambos.',
                'Los nombres y límites de estos campos: nombre, institución, fecha, duración, código, URL de validación y categoría.',
                'Las habilidades existentes del catálogo (nombre y categoría), solo como opciones para sugerir asociaciones.',
                'El código o la URL de validación únicamente si aparecen literalmente en el certificado.',
            ],
            'excluded' => [
                'El PDF completo, imágenes o páginas renderizadas. El OCR se ejecuta localmente y ninguna imagen se envía.',
                'Nombre, teléfono, correo, ubicación y enlaces personales del titular; firmas y contactos.',
                'El Perfil Maestro, los perfiles de CV, el CV, las ofertas, los documentos y las rutas locales.',
                'Contraseñas de PDF: no se solicita ni se intenta eludir protección.',
            ],
            'retention' => [
                'stored' => [
                    'Solo tras confirmar: el curso o certificación revisado, las habilidades aceptadas y el vínculo con el PDF original.',
                ],
                'excluded' => [
                    'El texto extraído, las páginas, el prompt y la respuesta completa de la IA.',
                    'Las imágenes, el PDF temporal y el texto OCR usados durante la preparación local.',
                    'Las citas, los borradores y un historial del análisis.',
                ],
                'note' => 'La limpieza local no revierte un envío externo ya autorizado ni garantiza el borrado en el proveedor.',
            ],
        ];
    }
}
