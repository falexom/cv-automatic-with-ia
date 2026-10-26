<?php

use App\Support\CvPdfFormatter;

it('formats year and month like the preview', function () {
    expect(CvPdfFormatter::formatYearMonth(2024, 1))->toBe('ene 2024')
        ->and(CvPdfFormatter::formatYearMonth(2024, 6))->toBe('jun 2024')
        ->and(CvPdfFormatter::formatYearMonth(2024, 12))->toBe('dic 2024')
        ->and(CvPdfFormatter::formatYearMonth(2024, null))->toBe('2024')
        ->and(CvPdfFormatter::formatYearMonth(null, 6))->toBeNull();
});

it('formats ranges and current states like the preview', function () {
    expect(CvPdfFormatter::formatRange(2022, 3, 2023, 6))
        ->toBe('mar 2022 – jun 2023')
        ->and(CvPdfFormatter::formatRange(2022, 3, null, null, true))
        ->toBe('mar 2022 – actual')
        ->and(CvPdfFormatter::formatRange(2022, 3, null, null, true, 'en curso'))
        ->toBe('mar 2022 – en curso')
        ->and(CvPdfFormatter::formatRange(null, null, null, null, true))
        ->toBe('actual')
        ->and(CvPdfFormatter::formatRange(2022, 3, null, null))
        ->toBe('mar 2022')
        ->and(CvPdfFormatter::formatRange(null, null, 2023, 6))
        ->toBe('jun 2023')
        ->and(CvPdfFormatter::formatRange(null, null, null, null))
        ->toBe('');
});

it('labels skill categories with the same fallback as the preview', function () {
    expect(CvPdfFormatter::skillCategoryLabel('tecnica'))->toBe('Técnicas')
        ->and(CvPdfFormatter::skillCategoryLabel('funcional'))->toBe('Funcionales')
        ->and(CvPdfFormatter::skillCategoryLabel('metodologica'))->toBe('Metodológicas')
        ->and(CvPdfFormatter::skillCategoryLabel('herramienta'))->toBe('Herramientas')
        ->and(CvPdfFormatter::skillCategoryLabel('tecnologia'))->toBe('Tecnologías')
        ->and(CvPdfFormatter::skillCategoryLabel('otra'))->toBe('otra');
});
