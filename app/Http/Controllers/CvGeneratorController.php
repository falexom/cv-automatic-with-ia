<?php

namespace App\Http\Controllers;

use App\Models\CvProfile;
use App\Support\CvPreviewContent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CvGeneratorController extends Controller
{
    public function __construct(
        private readonly CvPreviewContent $previewContent,
    ) {}

    public function index(Request $request): Response
    {
        $profiles = [];
        $status = 'error';
        $selectedProfileId = null;
        $preview = null;

        try {
            $activeProfiles = CvProfile::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $profiles = $activeProfiles
                ->map(fn (CvProfile $profile): array => [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'professional_title' => $profile->professional_title,
                ])
                ->values()
                ->all();

            if ($activeProfiles->isEmpty()) {
                $status = 'no_active_profiles';
            } else {
                $query = [];
                parse_str($request->getQueryString() ?? '', $query);

                $profileQuery = $query['profile'] ?? null;

                if ($profileQuery === null || $profileQuery === '') {
                    $status = 'selection_required';
                } elseif (! is_string($profileQuery)
                    || ! ctype_digit($profileQuery)
                    || (int) $profileQuery < 1) {
                    $status = 'unavailable';
                } else {
                    $profile = $activeProfiles->firstWhere(
                        'id',
                        (int) $profileQuery,
                    );

                    if ($profile === null) {
                        $status = 'unavailable';
                    } else {
                        $selectedProfileId = $profile->id;
                        $preview = $this->previewContent->build($profile);
                        $status = 'ready';
                    }
                }
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
