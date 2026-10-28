<?php

namespace App\Http\Controllers;

use App\Support\CvDocumentedPdf;
use App\Support\CvDocumentedPdfException;
use App\Support\CvPdfFilename;
use App\Support\CvProfileResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class CvDocumentedPdfController extends Controller
{
    private const ERROR_MESSAGE = 'No se pudo generar el CV documentado. Volvé a intentar.';

    public function __construct(
        private readonly CvDocumentedPdf $documentedPdf,
        private readonly CvProfileResolver $profiles,
    ) {}

    public function __invoke(Request $request): Response|RedirectResponse
    {
        $query = [];
        parse_str($request->getQueryString() ?? '', $query);

        try {
            $resolved = $this->profiles->resolve($query);
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('/cv', self::ERROR_MESSAGE);
        }

        if ($resolved['status'] !== 'ready' || $resolved['profile'] === null) {
            return $this->unavailable($resolved['status'], $query);
        }

        $profile = $resolved['profile'];
        $rawDocuments = $query['documents'] ?? null;

        if ($rawDocuments === null || $rawDocuments === []) {
            return $this->failure(
                "/cv?profile={$profile->id}",
                'Seleccioná al menos una evidencia para generar el CV documentado.',
            );
        }

        $documentIds = $this->documentIds($rawDocuments);

        if ($documentIds === null) {
            return $this->failure(
                "/cv?profile={$profile->id}",
                'La selección de evidencias no es válida. Volvé a elegir las evidencias del perfil.',
            );
        }

        try {
            $pdf = $this->documentedPdf->generate($profile, $documentIds);
            $filename = CvPdfFilename::documentedFromProfileName($profile->name);

            return new Response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Content-Length' => (string) strlen($pdf),
                'Cache-Control' => 'no-store, private',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (CvDocumentedPdfException $exception) {
            report($exception);

            return $this->failure("/cv?profile={$profile->id}", $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure("/cv?profile={$profile->id}", self::ERROR_MESSAGE);
        }
    }

    /**
     * @return array<int, int>|null
     */
    private function documentIds(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $ids = [];

        foreach ($value as $id) {
            if (! is_string($id) || ! ctype_digit($id) || (int) $id < 1) {
                return null;
            }

            $ids[] = (int) $id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<int|string, mixed>  $query
     */
    private function unavailable(string $status, array $query): RedirectResponse
    {
        if ($status === 'no_active_profiles') {
            return $this->failure('/cv', 'No hay perfiles activos para exportar.');
        }

        if ($status === 'selection_required') {
            return $this->failure('/cv', 'Seleccioná un perfil para exportar el CV documentado.');
        }

        $profile = $query['profile'] ?? null;
        $target = is_string($profile)
            && ctype_digit($profile)
            && (int) $profile >= 1
                ? '/cv?profile='.(int) $profile
                : '/cv';

        return $this->failure(
            $target,
            'El perfil seleccionado no está disponible. Elegí un perfil activo.',
        );
    }

    private function failure(string $target, string $message): RedirectResponse
    {
        return redirect($target)->with('toast', [
            'type' => 'error',
            'message' => $message,
        ]);
    }
}
