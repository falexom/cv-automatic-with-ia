<?php

namespace App\Http\Controllers;

use App\Models\CvProfile;
use App\Support\CvPreviewContent;
use App\Support\CvProfileResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CvGeneratorController extends Controller
{
    public function __construct(
        private readonly CvPreviewContent $previewContent,
        private readonly CvProfileResolver $profileResolver,
    ) {}

    public function index(Request $request): Response
    {
        $profiles = [];
        $status = 'error';
        $selectedProfileId = null;
        $preview = null;

        try {
            $query = [];
            parse_str($request->getQueryString() ?? '', $query);

            $resolved = $this->profileResolver->resolve($query);

            $profiles = $resolved['profiles']
                ->map(fn (CvProfile $profile): array => [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'professional_title' => $profile->professional_title,
                ])
                ->values()
                ->all();

            $status = $resolved['status'];

            if ($resolved['profile'] !== null) {
                $selectedProfileId = $resolved['profile']->id;
                $preview = $this->previewContent->build($resolved['profile']);
                $status = 'ready';
            }
        } catch (Throwable $exception) {
            report($exception);

            $status = 'error';
            $preview = null;
        }

        return Inertia::render('Cv/Index', [
            'profiles' => $profiles,
            'status' => $status,
            'selected_profile_id' => $selectedProfileId,
            'preview' => $preview,
        ]);
    }
}
