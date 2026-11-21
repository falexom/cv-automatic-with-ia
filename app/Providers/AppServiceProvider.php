<?php

namespace App\Providers;

use App\Support\CertificationImport\CertificationImportAnalyzer;
use App\Support\CertificationImport\CertificationImportCodex;
use App\Support\CertificationImport\LocalOcrExtractor;
use App\Support\CertificationImport\PdfTextExtractor;
use App\Support\CertificationImport\PdftotextExtractor;
use App\Support\CertificationImport\TesseractOcrExtractor;
use App\Support\CvAdaptation\CvAdaptationAnalyzer;
use App\Support\CvAdaptation\CvAdaptationCodex;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CvAdaptationAnalyzer::class, CvAdaptationCodex::class);
        $this->app->bind(PdfTextExtractor::class, PdftotextExtractor::class);
        $this->app->bind(LocalOcrExtractor::class, TesseractOcrExtractor::class);
        $this->app->bind(CertificationImportAnalyzer::class, CertificationImportCodex::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
