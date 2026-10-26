<?php

use App\Support\CvPdfLink;

it('keeps valid http, https, mailto and tel destinations', function () {
    expect(CvPdfLink::href('https://example.com/proyecto?x=1'))
        ->toBe('https://example.com/proyecto?x=1')
        ->and(CvPdfLink::href('http://example.com'))
        ->toBe('http://example.com')
        ->and(CvPdfLink::href('mailto:ada@example.com'))
        ->toBe('mailto:ada@example.com')
        ->and(CvPdfLink::href('tel:+54 11 5555-1234'))
        ->toBe('tel:+541155551234');
});

it('rejects unsafe schemes, relative values and credentials', function (string $value) {
    expect(CvPdfLink::href($value))->toBeNull();
})->with([
    'javascript' => 'javascript:alert(1)',
    'data' => 'data:text/html,<script>alert(1)</script>',
    'file' => 'file:///etc/passwd',
    'vbscript' => 'vbscript:msgbox(1)',
    'phar' => 'phar://malicious.phar',
    'relative' => '/profiles/1/edit',
    'protocol relative' => '//example.com',
    'credentials' => 'https://user:pass@example.com',
    'control characters' => "https://example.com/\x00",
    'encoded newline' => 'https://example.com/%0aheader',
    'encoded null' => 'https://example.com/%00',
    'encoded tab' => 'https://example.com/%09',
    'encoded delete' => 'https://example.com/%7F',
    'invalid mailto' => 'mailto:not-an-email',
    'invalid tel' => 'tel:sin-digitos',
]);

it('accepts percent-encoded spaces in otherwise valid urls', function () {
    expect(CvPdfLink::href('https://example.com/ruta%20segura'))
        ->toBe('https://example.com/ruta%20segura');
});

it('builds safe email and phone hrefs', function () {
    expect(CvPdfLink::emailHref('ada@example.com'))->toBe('mailto:ada@example.com')
        ->and(CvPdfLink::emailHref('no-es-correo'))->toBeNull()
        ->and(CvPdfLink::emailHref("ada@example.com\n"))->toBeNull()
        ->and(CvPdfLink::phoneHref('+54 11 5555 1234'))->toBe('tel:+541155551234')
        ->and(CvPdfLink::phoneHref('(011) 5555-1234'))->toBe('tel:01155551234')
        ->and(CvPdfLink::phoneHref('sin-digitos'))->toBeNull()
        ->and(CvPdfLink::phoneHref(null))->toBeNull();
});
