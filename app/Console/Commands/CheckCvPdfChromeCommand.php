<?php

namespace App\Console\Commands;

use App\Support\CvPdfChrome;
use App\Support\CvPdfChromeValidator;
use Illuminate\Console\Command;

class CheckCvPdfChromeCommand extends Command
{
    protected $signature = 'cv:pdf:chrome
                            {--warn-only : Muestra el problema pero no falla el proceso}';

    protected $description = 'Valida el Chrome que Laravel usa para exportar el CV a PDF';

    public function __construct(
        private readonly CvPdfChrome $chrome,
        private readonly CvPdfChromeValidator $validator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $resolution = $this->chrome->resolve();

        if (! $resolution->isAvailable() || $resolution->path === null) {
            $this->components->error('No se encontró un Chrome completo para exportar el CV a PDF.');
            $this->newLine();
            $this->line($resolution->error ?? 'Revisá la configuración de Chrome para Browsershot.');

            return $this->option('warn-only') ? self::SUCCESS : self::FAILURE;
        }

        $this->components->info('Chrome para exportar el CV a PDF');
        $this->line('  Ejecutable: '.$resolution->path);
        $this->line('  Origen: '.($resolution->source ?? 'desconocido'));
        $this->newLine();

        $check = $this->validator->check($resolution->path);

        if (! $check->ok) {
            $this->components->error('El Chrome encontrado no pudo iniciar o no generó un PDF de prueba válido.');
            $this->newLine();
            $this->line($check->error ?? 'Error desconocido al iniciar Chrome.');
            $this->newLine();
            $this->line('Verificá que sea un Chrome/Chromium completo y ejecutable, o instalá el Chrome de Puppeteer una sola vez:');
            $this->line('  pnpm exec puppeteer browsers install chrome');
            $this->line('También podés apuntar BROWSERSHOT_CHROME_PATH a otro Chrome existente.');

            return $this->option('warn-only') ? self::SUCCESS : self::FAILURE;
        }

        $this->components->info(
            'PDF de prueba generado correctamente ('.number_format($check->bytes ?? 0).' bytes).',
        );

        return self::SUCCESS;
    }
}
