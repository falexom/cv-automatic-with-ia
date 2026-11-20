<?php

namespace App\Support\CertificationImport;

final class CertificationImportSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'title' => 'Propuesta de importación de un curso o certificación',
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'schema_version',
                'sufficiency',
                'sufficiency_note',
                'fields',
                'skills',
                'ambiguities',
            ],
            'properties' => [
                'schema_version' => [
                    'type' => 'integer',
                    'const' => 1,
                ],
                'sufficiency' => [
                    'type' => 'string',
                    'enum' => ['sufficient', 'insufficient_ocr', 'irrelevant'],
                ],
                'sufficiency_note' => [
                    'type' => ['string', 'null'],
                    'maxLength' => 500,
                ],
                'fields' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => [
                        'name',
                        'institution',
                        'date',
                        'duration',
                        'code',
                        'validation_url',
                        'category',
                    ],
                    'properties' => [
                        'name' => self::field(['found', 'absent'], 255),
                        'institution' => self::field(['found', 'absent'], 255),
                        'date' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['state', 'year', 'month', 'support', 'page', 'reason'],
                            'properties' => [
                                'state' => [
                                    'type' => 'string',
                                    'enum' => ['found', 'absent'],
                                ],
                                'year' => [
                                    'type' => ['integer', 'null'],
                                    'minimum' => 1900,
                                    'maximum' => 9999,
                                ],
                                'month' => [
                                    'type' => ['integer', 'null'],
                                    'minimum' => 1,
                                    'maximum' => 12,
                                ],
                                'support' => [
                                    'type' => ['string', 'null'],
                                    'maxLength' => 1000,
                                ],
                                'page' => [
                                    'type' => ['integer', 'null'],
                                    'minimum' => 1,
                                ],
                                'reason' => [
                                    'type' => ['string', 'null'],
                                    'maxLength' => 500,
                                ],
                            ],
                        ],
                        'duration' => self::field(['found', 'absent'], 100),
                        'code' => self::field(['found', 'absent'], 255),
                        'validation_url' => self::field(['found', 'absent'], 2048),
                        'category' => self::field(['found', 'suggested', 'absent'], 100),
                    ],
                ],
                'skills' => [
                    'type' => 'array',
                    'maxItems' => 30,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['ref', 'state', 'support', 'page', 'reason'],
                        'properties' => [
                            'ref' => [
                                'type' => 'string',
                                'pattern' => '^skl_[a-f0-9]{12}$',
                            ],
                            'state' => [
                                'type' => 'string',
                                'enum' => ['found', 'suggested'],
                            ],
                            'support' => [
                                'type' => ['string', 'null'],
                                'maxLength' => 1000,
                            ],
                            'page' => [
                                'type' => ['integer', 'null'],
                                'minimum' => 1,
                            ],
                            'reason' => [
                                'type' => ['string', 'null'],
                                'maxLength' => 500,
                            ],
                        ],
                    ],
                ],
                'ambiguities' => [
                    'type' => 'array',
                    'maxItems' => 10,
                    'items' => [
                        'type' => 'string',
                        'maxLength' => 500,
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
     * @param  array<int, string>  $states
     * @return array<string, mixed>
     */
    private static function field(array $states, int $maxLength): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['state', 'value', 'support', 'page', 'reason'],
            'properties' => [
                'state' => [
                    'type' => 'string',
                    'enum' => $states,
                ],
                'value' => [
                    'type' => ['string', 'null'],
                    'maxLength' => $maxLength,
                ],
                'support' => [
                    'type' => ['string', 'null'],
                    'maxLength' => 1000,
                ],
                'page' => [
                    'type' => ['integer', 'null'],
                    'minimum' => 1,
                ],
                'reason' => [
                    'type' => ['string', 'null'],
                    'maxLength' => 500,
                ],
            ],
        ];
    }
}
