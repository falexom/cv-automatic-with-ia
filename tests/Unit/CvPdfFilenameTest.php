<?php

use App\Support\CvPdfFilename;

it('builds deterministic safe names from profile names', function (string $name, string $expected) {
    expect(CvPdfFilename::fromProfileName($name))->toBe($expected)
        ->and(CvPdfFilename::fromProfileName($name))->toBe($expected);
})->with([
    ['Analista', 'cv-analista.pdf'],
    ['Programador / Desarrollador', 'cv-programador-desarrollador.pdf'],
    ['Análisis de Sistemas', 'cv-analisis-de-sistemas.pdf'],
    ['Ñandú', 'cv-nandu.pdf'],
    ['Ada 🚀 Lovelace', 'cv-ada-lovelace.pdf'],
    ['../../etc/passwd', 'cv-etc-passwd.pdf'],
    ['a/b\c', 'cv-a-b-c.pdf'],
    ['CV "Ada" .. <>', 'cv-cv-ada.pdf'],
]);

it('falls back to cv.pdf when no alphanumeric base remains', function (?string $name) {
    expect(CvPdfFilename::fromProfileName($name))->toBe('cv.pdf');
})->with([
    'null' => null,
    'empty' => '',
    'spaces' => '   ',
    'emoji' => '🎯🚀',
    'dash' => '-',
    'dots' => '....',
]);

it('removes separators, quotes, dots and control characters', function () {
    $filename = CvPdfFilename::fromProfileName("Ana\x00lítica / \"QA\" .. \\");

    expect($filename)->toMatch('/^cv(-[a-z0-9]+)*\.pdf$/')
        ->and($filename)->not->toContain('..')
        ->and($filename)->not->toContain('/')
        ->and($filename)->not->toContain('\\')
        ->and($filename)->not->toContain('"')
        ->and($filename)->not->toMatch('/[\x00-\x1F\x7F]/');
});

it('truncates the normalized base to eighty characters and trims a trailing dash', function () {
    $long = CvPdfFilename::fromProfileName(str_repeat('a', 100));
    $cutOnDash = CvPdfFilename::fromProfileName(str_repeat('a', 79).'-'.str_repeat('b', 20));

    expect($long)->toBe('cv-'.str_repeat('a', 80).'.pdf')
        ->and($cutOnDash)->toBe('cv-'.str_repeat('a', 79).'.pdf')
        ->and($long)->toMatch('/^cv(-[a-z0-9]+)*\.pdf$/')
        ->and($cutOnDash)->toMatch('/^cv(-[a-z0-9]+)*\.pdf$/');
});
