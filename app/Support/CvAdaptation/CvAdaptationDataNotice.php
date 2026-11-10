<?php

namespace App\Support\CvAdaptation;

final class CvAdaptationDataNotice
{
    public function __construct(
        private readonly CvAdaptationCodexConfig $config,
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
                'El texto completo de la oferta que pegues.',
                'El título profesional y el resumen del Perfil Maestro.',
                'Experiencias: cargo, empleador, fechas, descripción, responsabilidades, logros y tecnologías registradas.',
                'Estudios: programa, institución, nivel, fechas y descripción.',
                'Cursos y certificaciones: nombre, entidad, fechas, categoría y duración.',
                'Habilidades: nombre y categoría.',
                'Proyectos: nombre, rol, fechas, descripción y resultados.',
                'Perfiles de CV: estado, nombre, título, resumen y selecciones con sus relevancias.',
            ],
            'excluded' => [
                'Nombre personal, teléfono, correo, ubicación y enlaces personales.',
                'Fotografía, archivos, rutas y documentos adjuntos.',
                'Códigos o URL de validación de certificaciones.',
                'URL de proyectos y enlaces que no correspondan a los campos autorizados.',
                'Identificadores de base de datos.',
            ],
        ];
    }
}
