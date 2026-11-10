<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCvAdaptationObservationRequest;
use App\Models\CvAdaptation;
use App\Models\CvAdaptationObservation;
use App\Models\CvProfile;
use App\Support\CvAdaptation\CvAdaptationDetail;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProfileAdaptationController extends Controller
{
    public function __construct(
        private readonly CvAdaptationDetail $detail,
    ) {}

    public function index(CvProfile $cvProfile): Response|RedirectResponse
    {
        $adaptations = $cvProfile->adaptations()
            ->orderByDesc('analyzed_at')
            ->orderByDesc('id')
            ->get();

        if ($adaptations->count() === 1) {
            return redirect()->route('profiles.adaptations.show', [
                $cvProfile,
                $adaptations->first(),
            ]);
        }

        return Inertia::render('Profiles/Adaptations/Index', [
            'profile' => $this->profileData($cvProfile),
            'adaptations' => $adaptations
                ->map(fn (CvAdaptation $adaptation): array => [
                    'id' => $adaptation->id,
                    'operation' => $adaptation->operation,
                    'job_title' => $adaptation->job_title,
                    'company' => $adaptation->company,
                    'analyzed_at' => $adaptation->analyzed_at->toIso8601String(),
                    'confirmed_at' => $adaptation->confirmed_at->toIso8601String(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function show(CvProfile $cvProfile, CvAdaptation $cvAdaptation): Response
    {
        abort_unless($cvAdaptation->cv_profile_id === $cvProfile->id, 404);

        $cvAdaptation->load('observations');

        return Inertia::render('Profiles/Adaptations/Show', [
            'profile' => $this->profileData($cvProfile),
            'adaptation' => $this->detail->build($cvAdaptation),
        ]);
    }

    public function updateObservation(
        UpdateCvAdaptationObservationRequest $request,
        CvProfile $cvProfile,
        CvAdaptation $cvAdaptation,
        CvAdaptationObservation $cvAdaptationObservation,
    ): RedirectResponse {
        abort_unless($cvAdaptation->cv_profile_id === $cvProfile->id, 404);
        abort_unless($cvAdaptationObservation->cv_adaptation_id === $cvAdaptation->id, 404);

        $data = $request->validated();
        $note = $data['note'] ?? null;

        if (is_string($note) && trim($note) === '') {
            $note = null;
        }

        $cvAdaptationObservation->update([
            'status' => $data['status'],
            'note' => $note,
        ]);

        return redirect()
            ->route('profiles.adaptations.show', [$cvProfile, $cvAdaptation])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Observación actualizada.',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function profileData(CvProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'professional_title' => $profile->professional_title,
            'summary' => $profile->summary,
            'is_active' => $profile->is_active,
        ];
    }
}
