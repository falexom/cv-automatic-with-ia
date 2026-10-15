<?php

use App\Support\YearMonth;

it('treats a year-only end date as december and a year-only start as january', function () {
    expect(YearMonth::endIsBeforeStart(2022, null, 2022, null))->toBeFalse();
    expect(YearMonth::endIsBeforeStart(2022, 6, 2022, null))->toBeFalse();
    expect(YearMonth::endIsBeforeStart(2022, null, 2022, 1))->toBeFalse();
    expect(YearMonth::endIsBeforeStart(2022, 3, 2022, 2))->toBeTrue();
    expect(YearMonth::endIsBeforeStart(2022, 3, 2022, 4))->toBeFalse();
});

it('compares across years', function () {
    expect(YearMonth::endIsBeforeStart(2023, null, 2022, 12))->toBeTrue();
    expect(YearMonth::endIsBeforeStart(2022, 12, 2023, null))->toBeFalse();
    expect(YearMonth::endIsBeforeStart(2022, null, 2021, 5))->toBeTrue();
});

it('cannot compare when a year is missing', function () {
    expect(YearMonth::endIsBeforeStart(null, 3, 2022, 1))->toBeFalse();
    expect(YearMonth::endIsBeforeStart(2022, 3, null, 1))->toBeFalse();
    expect(YearMonth::endIsBeforeStart(null, null, null, null))->toBeFalse();
});

it('builds sort keys with null month as january and null year last', function () {
    expect(YearMonth::sortKey(2022, null))->toBe(['year' => 2022, 'month' => 1]);
    expect(YearMonth::sortKey(2022, 7))->toBe(['year' => 2022, 'month' => 7]);

    $withYear = YearMonth::sortKey(2000, 1);
    $withoutYear = YearMonth::sortKey(null, 12);

    expect($withYear['year'])->toBeGreaterThan($withoutYear['year']);
});
