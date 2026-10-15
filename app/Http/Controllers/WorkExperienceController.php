<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkExperienceRequest;
use App\Http\Requests\UpdateWorkExperienceRequest;
use App\Models\Profile;
use App\Models\WorkExperience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class WorkExperienceController extends Controller
{
    public function index(): Response
    {
        $records = Profile::current()
            ->workExperiences()
            ->with('skills:id,name,category')
            ->chronological()
            ->get()
            ->map(fn (WorkExperience $experience): array => $this->serialize($experience));

        return Inertia::render('Experiences/Index', [
            'records' => $records,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Experiences/Create', [
            'skills' => $this->skillOptions(),
        ]);
    }

    public function store(StoreWorkExperienceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $skillIds = $data['skill_ids'] ?? [];
        unset($data['skill_ids']);

        $experience = Profile::current()
            ->workExperiences()
            ->create($this->applyCurrentState($data));

        $experience->skills()->sync($skillIds);

        return $this->redirectToIndex('Experiencia guardada.');
    }

    public function edit(WorkExperience $experience): Response
    {
        $experience->load('skills:id,name,category');

        return Inertia::render('Experiences/Edit', [
            'record' => [
                ...$this->serialize($experience),
                'skill_ids' => $experience->skills->pluck('id')->all(),
            ],
            'skills' => $this->skillOptions(),
        ]);
    }

    public function update(UpdateWorkExperienceRequest $request, WorkExperience $experience): RedirectResponse
    {
        $data = $request->validated();
        $skillIds = $data['skill_ids'] ?? [];
        unset($data['skill_ids']);

        $experience->update($this->applyCurrentState($data, $experience));
        $experience->skills()->sync($skillIds);

        return $this->redirectToIndex('Experiencia actualizada.');
    }

    public function destroy(WorkExperience $experience): RedirectResponse
    {
        $experience->delete();

        return $this->redirectToIndex('Experiencia eliminada.');
    }

    /**
     * FR-008 and the spec edge case: an end date clears "current";
     * removing a previously set end date makes it current again.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyCurrentState(array $data, ?WorkExperience $existing = null): array
    {
        foreach (['start_year', 'start_month', 'end_year', 'end_month'] as $field) {
            $data[$field] ??= null;
        }

        if (filled($data['end_year'] ?? null)) {
            $data['is_current'] = false;
        } elseif ($existing !== null && $existing->end_year !== null) {
            $data['is_current'] = true;
        } else {
            $data['is_current'] = (bool) ($data['is_current'] ?? false);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(WorkExperience $experience): array
    {
        return [
            'id' => $experience->id,
            'company' => $experience->company,
            'position' => $experience->position,
            'start_year' => $experience->start_year,
            'start_month' => $experience->start_month,
            'end_year' => $experience->end_year,
            'end_month' => $experience->end_month,
            'is_current' => $experience->is_current,
            'description' => $experience->description,
            'responsibilities' => $experience->responsibilities,
            'achievements' => $experience->achievements,
            'skills' => $experience->skills
                ->map(fn ($skill): array => [
                    'id' => $skill->id,
                    'name' => $skill->name,
                    'category' => $skill->category,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return Collection<int, array{id: int, name: string, category: string}>
     */
    private function skillOptions()
    {
        return Profile::current()
            ->skills()
            ->orderBy('name')
            ->get(['id', 'name', 'category']);
    }

    private function redirectToIndex(string $message): RedirectResponse
    {
        return redirect()
            ->route('experiences.index')
            ->with('toast', ['type' => 'success', 'message' => $message]);
    }
}
