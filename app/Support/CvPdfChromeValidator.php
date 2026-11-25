<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Throwable;

class CvPdfChromeValidator
{
    public function check(string $chromePath): CvPdfChromeCheck
    {
        $directory = storage_path('app/private/cv-pdf-chrome-check/'.Str::uuid()->toString());

        if (! File::makeDirectory($directory, 0700, true) && ! File::isDirectory($directory)) {
            return new CvPdfChromeCheck(
                ok: false,
                error: 'No se pudo crear el directorio temporal para la prueba de Chrome.',
            );
        }

        try {
            $pdf = Browsershot::html($this->html())
                ->newHeadless()
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->disableJavascript()
                ->blockUrls(['http://', 'https://'])
                ->addChromiumArguments(['use-mock-keychain'])
                ->userDataDir($directory.'/chrome-profile')
                ->timeout((int) config('services.browsershot.timeout', 30))
                ->setNodeModulePath(base_path('node_modules'))
                ->setCustomTempPath($directory)
                ->setChromePath($chromePath)
                ->pdf();

            if (! (new CvPdfValidator)->isValid($pdf)) {
                return new CvPdfChromeCheck(
                    ok: false,
                    error: 'El ejecutable inició, pero el PDF de prueba no es estructuralmente válido.',
                );
            }

            return new CvPdfChromeCheck(ok: true, bytes: strlen($pdf));
        } catch (Throwable $exception) {
            return new CvPdfChromeCheck(
                ok: false,
                error: Str::limit($exception->getMessage(), 1200),
            );
        } finally {
            File::deleteDirectory($directory);
        }
    }

    private function html(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4; margin: 10mm; }
        body { font-family: Arial, sans-serif; color: #1d2939; }
        h1 { font-size: 18pt; }
        p { font-size: 10pt; }
    </style>
</head>
<body>
    <h1>CV Automático</h1>
    <p>Prueba local de exportación PDF. Este documento no contiene datos del CV.</p>
</body>
</html>
HTML;
    }
}
