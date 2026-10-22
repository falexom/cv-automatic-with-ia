<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSkillRequest;
use App\Http\Requests\UpdateSkillRequest;
use App\Models\Profile;
use App\Models\Skill;
use App\Support\NameNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SkillController extends Controller
{
    public function index(): Response
    {
        $records = Profile::current()
            ->skills()
            ->withCount(['workExperiences', 'certifications', 'projects'])
            ->orderByRaw("CASE category
                WHEN 'tecnica' THEN 1
                WHEN 'funcional' THEN 2
                WHEN 'metodologica' THEN 3
                WHEN 'herramienta' THEN 4
                WHEN 'tecnologia' THEN 5
                ELSE 6 END")
            ->orderBy('name')
            ->get()
            ->map(fn (Skill $skill): array => [
                ...$this->serialize($skill),
                'usage_count' => $skill->work_experiences_count
                    + $skill->certifications_count
                    + $skill->projects_count,
            ]);

        return Inertia::render('Skills/Index', [
            'records' => $records,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Skills/Create');
    }

    public function store(StoreSkillRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->ensureUniqueName($data['name']);

        Profile::current()->skills()->create($data);

        return $this->redirectToIndex('Habilidad creada.');
    }

    public function edit(Skill $skill): Response
    {
        return Inertia::render('Skills/Edit', [
            'record' => $this->serialize($skill),
            'usage' => [
                'experiences' => $skill->workExperiences()
                    ->get(['work_experiences.id', 'company', 'position'])
                    ->map(fn ($experience): array => [
                        'id' => $experience->id,
                        'label' => "{$experience->position} — {$experience->company}",
                    ])
                    ->values()
                    ->all(),
                'certifications' => $skill->certifications()
                    ->get(['certifications.id', 'name'])
                    ->map(fn ($certification): array => [
                        'id' => $certification->id,
                        'label' => $certification->name,
                    ])
                    ->values()
                    ->all(),
                'projects' => $skill->projects()
                    ->get(['projects.id', 'name'])
                    ->map(fn ($project): array => [
                        'id' => $project->id,
                        'label' => $project->name,
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function update(UpdateSkillRequest $request, Skill $skill): RedirectResponse
    {
        $data = $request->validated();

        $this->ensureUniqueName($data['name'], $skill);

        $skill->update($data);

        return $this->redirectToIndex('Habilidad actualizada.');
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $skill->delete();

        return $this->redirectToIndex('Habilidad eliminada.');
    }

    /**
     * FR-020: duplicates are detected on the normalized name.
     */
    private function ensureUniqueName(string $name, ?Skill $ignore = null): void
    {
        $normalized = NameNormalizer::normalize($name);

        $exists = Profile::current()
            ->skills()
            ->where('normalized_name', $normalized)
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'Ya existe una habilidad con ese nombre. Buscala en el catálogo para reutilizarla en lugar de duplicarla.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Skill $skill): array
    {
        return [
            'id' => $skill->id,
            'name' => $skill->name,
            'category' => $skill->category,
        ];
    }

    private function redirectToIndex(string $message): RedirectResponse
    {
        return redirect()
            ->route('skills.index')
            ->with('toast', ['type' => 'success', 'message' => $message]);
    }
}
