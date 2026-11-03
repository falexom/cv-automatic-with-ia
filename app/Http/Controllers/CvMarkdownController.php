<?php

namespace App\Http\Controllers;

use App\Support\CvMarkdownRenderer;
use App\Support\CvPdfFilename;
use App\Support\CvPreviewContent;
use App\Support\CvProfileResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;
use Throwable;

class CvMarkdownController extends Controller
{
    private const ERROR_MESSAGE = 'No se pudo generar el Markdown. Volvé a intentar.';

    public function __construct(
        private readonly CvProfileResolver $profiles,
        private readonly CvPreviewContent $previewContent,
        private readonly CvMarkdownRenderer $renderer,
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

            $markdown = $this->renderer->render($preview);

            if (trim($markdown) === '') {
                throw new RuntimeException('The generated Markdown is empty.');
            }

            $filename = CvPdfFilename::markdownFromProfileName($profile->name);

            return new Response($markdown, 200, [
                'Content-Type' => 'text/markdown; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Content-Length' => (string) strlen($markdown),
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
            return $this->failure('/cv', 'Seleccioná un perfil para exportar el Markdown.');
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
