<?php

use App\Support\CvPdfValidator;

function cvPdfValidatorSample(): string
{
    return <<<'PDF'
%PDF-1.4
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R >>
endobj
xref
0 4
trailer
<< /Size 4 /Root 1 0 R >>
startxref
123
%%EOF
PDF;
}

it('accepts a complete pdf with pages and a trailer index', function () {
    expect((new CvPdfValidator)->isValid(cvPdfValidatorSample()))->toBeTrue();
});

it('rejects empty, truncated or incomplete pdfs', function (string $pdf) {
    expect((new CvPdfValidator)->isValid($pdf))->toBeFalse();
})->with([
    'empty' => '',
    'truncated' => substr(cvPdfValidatorSample(), 0, -10),
    'without eof' => str_replace('%%EOF', '', cvPdfValidatorSample()),
    'without startxref' => str_replace('startxref', '', cvPdfValidatorSample()),
    'without pages tree' => str_replace('/Type /Pages', '', cvPdfValidatorSample()),
    'without page' => str_replace('/Type /Page ', '', cvPdfValidatorSample()),
    'startxref outside tail' => str_replace(
        'startxref',
        "startxref\n123\n".str_repeat(' ', 2000),
        cvPdfValidatorSample(),
    ),
]);

it('does not confuse one page with the pages tree', function () {
    $onlyPages = <<<'PDF'
%PDF-1.4
1 0 obj
<< /Type /Pages /Kids [] /Count 0 >>
endobj
startxref
1
%%EOF
PDF;

    expect((new CvPdfValidator)->isValid($onlyPages))->toBeFalse();
});
