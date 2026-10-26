<?php

namespace App\Http\Controllers;

use App\Support\CvPdfFilename;
use App\Support\CvPdfRenderer;
use App\Support\CvPdfValidator;
use App\Support\CvPreviewContent;
use App\Support\CvProfileResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;
use Throwable;

class CvPdfController extends Controller
{
    private const ERROR_MESSAGE = 'No se pudo generar el PDF. Volvé a intentar.';

    public function __construct(
        private readonly CvProfileResolver $profiles,
        private readonly CvPreviewContent $previewContent,
        private readonly CvPdfRenderer $renderer,
        private readonly CvPdfValidator $validator,
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

        try {
            $preview = $this->previewContent->build($profile);

            if (! ($preview['has_data'] ?? false)) {
                return $this->failure(
                    "/cv?profile={$profile->id}",
                    'Este perfil no tiene datos para exportar.',
                );
            }

            $pdf = $this->renderer->render($preview);

            if (! $this->validator->isValid($pdf)) {
                throw new RuntimeException('The generated PDF is structurally invalid.');
            }

            $filename = CvPdfFilename::fromProfileName($profile->name);

            return new Response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Content-Length' => (string) strlen($pdf),
                'Cache-Control' => 'no-store, private',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure("/cv?profile={$profile->id}", self::ERROR_MESSAGE);
        }
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
            return $this->failure('/cv', 'Seleccioná un perfil para exportar el PDF.');
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
