<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Inertia\Ssr\HttpGateway;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Limit server-side rendering to the PDF export request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        app(HttpGateway::class)->disable(fn (): bool => ! $request->is('cv/pdf'));

        return parent::handle($request, $next);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'flash' => [
                'duplicate_warning' => fn () => $request->session()->get('duplicate_warning'),
                'toast' => fn () => $request->session()->get('toast'),
                'verification' => fn () => $request->session()->get('verification'),
            ],
        ];
    }
}
