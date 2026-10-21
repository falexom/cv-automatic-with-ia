<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        $records = Profile::current()
            ->projects()
            ->with('skills:id,name,category')
            ->chronological()
            ->get()
            ->map(fn (Project $project): array => $this->serialize($project));

        return Inertia::render('Projects/Index', [
            'records' => $records,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Projects/Create', [
            'skills' => $this->skillOptions(),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $data = $this->normalizeDates($request->validated());
        $skillIds = $data['skill_ids'] ?? [];
        unset($data['skill_ids']);

        $project = Profile::current()->projects()->create($data);
        $project->skills()->sync($skillIds);

        return $this->redirectToIndex('Proyecto guardado.');
    }

    public function edit(Project $project): Response
    {
        $project->load('skills:id,name,category');

        return Inertia::render('Projects/Edit', [
            'record' => [
                ...$this->serialize($project),
                'skill_ids' => $project->skills->pluck('id')->all(),
            ],
            'skills' => $this->skillOptions(),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $data = $this->normalizeDates($request->validated());
        $skillIds = $data['skill_ids'] ?? [];
        unset($data['skill_ids']);

        $project->update($data);
        $project->skills()->sync($skillIds);

        return $this->redirectToIndex('Proyecto actualizado.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return $this->redirectToIndex('Proyecto eliminado.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeDates(array $data): array
    {
        foreach (['start_year', 'start_month', 'end_year', 'end_month'] as $field) {
            $data[$field] ??= null;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'role' => $project->role,
            'results' => $project->results,
            'url' => $project->url,
            'start_year' => $project->start_year,
            'start_month' => $project->start_month,
            'end_year' => $project->end_year,
            'end_month' => $project->end_month,
            'skills' => $project->skills
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
     * @return Collection<int, Skill>
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
            ->route('projects.index')
            ->with('toast', ['type' => 'success', 'message' => $message]);
    }
}
