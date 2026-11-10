<?php

namespace App\Support\CvAdaptation;

final class CvAdaptationSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'title' => 'Propuesta de adaptación de CV a una oferta',
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'schema_version',
                'operation',
                'source_profile_ref',
                'rationale',
                'profile_details',
                'requirements',
                'gaps',
                'selected_content',
                'development_recommendations',
                'clarifying_questions',
            ],
            'properties' => [
                'schema_version' => [
                    'type' => 'integer',
                    'const' => 1,
                    'description' => 'Versión del contrato JSON. Debe ser 1.',
                ],
                'operation' => [
                    'type' => 'string',
                    'enum' => ['reuse', 'duplicate', 'create'],
                    'description' => 'Operación recomendada: reutilizar, duplicar y adaptar, o crear.',
                ],
                'source_profile_ref' => [
                    'type' => ['string', 'null'],
                    'pattern' => '^cv_[a-f0-9]{12}$',
                    'description' => 'Referencia opaca al perfil de CV de origen. Obligatoria para reuse y duplicate; null para create.',
                ],
                'rationale' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'maxItems' => 10,
                    'description' => 'Motivos breves y verificables de la recomendación.',
                    'items' => [
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 2000,
                    ],
                ],
                'profile_details' => [
                    'type' => ['object', 'null'],
                    'additionalProperties' => false,
                    'required' => ['name', 'professional_title', 'summary'],
                    'description' => 'Datos propios del perfil nuevo. null para reuse.',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 100,
                            'description' => 'Nombre único del perfil nuevo.',
                        ],
                        'professional_title' => [
                            'type' => ['string', 'null'],
                            'maxLength' => 255,
                            'description' => 'Título profesional del perfil nuevo, o null.',
                        ],
                        'summary' => [
                            'type' => ['string', 'null'],
                            'description' => 'Resumen del perfil nuevo basado solo en los datos profesionales recibidos, o null.',
                        ],
                    ],
                ],
                'requirements' => [
                    'type' => 'array',
                    'maxItems' => 50,
                    'description' => 'Requisitos identificados en la oferta, con importancia y cobertura.',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['ref', 'text', 'importance', 'coverage'],
                        'properties' => [
                            'ref' => [
                                'type' => 'string',
                                'pattern' => '^req_[a-z0-9_]{1,32}$',
                            ],
                            'text' => [
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 2000,
                            ],
                            'importance' => [
                                'type' => 'string',
                                'enum' => ['required', 'preferred', 'unspecified'],
                            ],
                            'coverage' => [
                                'type' => 'string',
                                'enum' => ['supported', 'partial', 'gap', 'needs_confirmation'],
                            ],
                        ],
                    ],
                ],
                'gaps' => [
                    'type' => 'array',
                    'maxItems' => 50,
                    'description' => 'Requisitos clasificados como brecha o pendientes de confirmación.',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['requirement_ref', 'reason', 'evidence_state'],
                        'properties' => [
                            'requirement_ref' => [
                                'type' => 'string',
                                'pattern' => '^req_[a-z0-9_]{1,32}$',
                            ],
                            'reason' => [
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 2000,
                            ],
                            'evidence_state' => [
                                'type' => 'string',
                                'enum' => ['not_recorded', 'needs_confirmation'],
                            ],
                        ],
                    ],
                ],
                'selected_content' => [
                    'type' => ['object', 'null'],
                    'additionalProperties' => false,
                    'required' => [
                        'work_experiences',
                        'education_entries',
                        'certifications',
                        'skills',
                        'projects',
                    ],
                    'description' => 'Asociaciones seleccionadas para el perfil nuevo. null para reuse.',
                    'properties' => [
                        'work_experiences' => self::selectionList('exp'),
                        'education_entries' => self::selectionList('edu'),
                        'certifications' => self::selectionList('cert'),
                        'skills' => self::selectionList('skill'),
                        'projects' => self::selectionList('proj'),
                    ],
                ],
                'development_recommendations' => [
                    'type' => 'array',
                    'maxItems' => 30,
                    'description' => 'Habilidades, cursos, certificaciones o formación recomendados. No forman parte de selected_content.',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['kind', 'label', 'reason', 'evidence_state'],
                        'properties' => [
                            'kind' => [
                                'type' => 'string',
                                'enum' => ['skill', 'course', 'certification', 'education'],
                            ],
                            'label' => [
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ],
                            'reason' => [
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 2000,
                            ],
                            'evidence_state' => [
                                'type' => 'string',
                                'enum' => ['not_recorded', 'needs_confirmation'],
                            ],
                        ],
                    ],
                ],
                'clarifying_questions' => [
                    'type' => 'array',
                    'maxItems' => 20,
                    'description' => 'Preguntas aclaratorias opcionales sobre requisitos ambiguos o datos por confirmar.',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['requirement_ref', 'question'],
                        'properties' => [
                            'requirement_ref' => [
                                'type' => 'string',
                                'pattern' => '^req_[a-z0-9_]{1,32}$',
                            ],
                            'question' => [
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 1000,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function json(): string
    {
        return json_encode(
            self::definition(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function selectionList(string $prefix): array
    {
        return [
            'type' => 'array',
            'maxItems' => 100,
            'items' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['ref', 'relevance'],
                'properties' => [
                    'ref' => [
                        'type' => 'string',
                        'pattern' => '^'.$prefix.'_[a-f0-9]{12}$',
                    ],
                    'relevance' => [
                        'type' => 'string',
                        'enum' => ['alta', 'media', 'baja'],
                    ],
                ],
            ],
        ];
    }
}
