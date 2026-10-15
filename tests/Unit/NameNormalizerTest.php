<?php

use App\Support\NameNormalizer;

it('lowercases and trims names', function () {
    expect(NameNormalizer::normalize('  PHP  '))->toBe('php');
    expect(NameNormalizer::normalize('React'))->toBe('react');
});

it('collapses inner whitespace', function () {
    expect(NameNormalizer::normalize("Análisis   de\tProcesos"))->toBe('análisis de procesos');
});

it('preserves accents', function () {
    expect(NameNormalizer::normalize('Programación'))->toBe('programación');
    expect(NameNormalizer::normalize('Estadística'))->toBe('estadística');
});

it('treats case and extra spaces as the same name', function () {
    expect(NameNormalizer::normalize('  rEACT '))
        ->toBe(NameNormalizer::normalize('React'));
});

it('handles empty and null values', function () {
    expect(NameNormalizer::normalize(''))->toBe('');
    expect(NameNormalizer::normalize(null))->toBe('');
    expect(NameNormalizer::normalize('   '))->toBe('');
});
