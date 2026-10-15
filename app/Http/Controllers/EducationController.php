<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEducationRequest;
use App\Http\Requests\UpdateEducationRequest;
use App\Models\EducationEntry;
use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EducationController extends Controller
{
    public function index(): Response
    {
        $records = Profile::current()
            ->educationEntries()
            ->chronological()
            ->get()
            ->map(fn (EducationEntry $entry): array => $this->serialize($entry));

        return Inertia::render('Education/Index', [
            'records' => $records,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Education/Create');
    }

    public function store(StoreEducationRequest $request): RedirectResponse
    {
        Profile::current()
            ->educationEntries()
            ->create($this->applyProgressState($request->validated()));

        return $this->redirectToIndex('Formación guardada.');
    }

    public function edit(EducationEntry $education): Response
    {
        return Inertia::render('Education/Edit', [
            'record' => $this->serialize($education),
        ]);
    }

    public function update(UpdateEducationRequest $request, EducationEntry $education): RedirectResponse
    {
        $education->update($this->applyProgressState($request->validated(), $education));

        return $this->redirectToIndex('Formación actualizada.');
    }

    public function destroy(EducationEntry $education): RedirectResponse
    {
        $education->delete();

        return $this->redirectToIndex('Formación eliminada.');
    }

    /**
     * FR-013 and the symmetric edge case: an end date clears
     * "in progress"; removing a previously set end date restores it.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyProgressState(array $data, ?EducationEntry $existing = null): array
    {
        foreach (['start_year', 'start_month', 'end_year', 'end_month'] as $field) {
            $data[$field] ??= null;
        }

        if (filled($data['end_year'] ?? null)) {
            $data['is_in_progress'] = false;
        } elseif ($existing !== null && $existing->end_year !== null) {
            $data['is_in_progress'] = true;
        } else {
            $data['is_in_progress'] = (bool) ($data['is_in_progress'] ?? false);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(EducationEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'institution' => $entry->institution,
            'program' => $entry->program,
            'degree_level' => $entry->degree_level,
            'start_year' => $entry->start_year,
            'start_month' => $entry->start_month,
            'end_year' => $entry->end_year,
            'end_month' => $entry->end_month,
            'is_in_progress' => $entry->is_in_progress,
            'description' => $entry->description,
        ];
    }

    private function redirectToIndex(string $message): RedirectResponse
    {
        return redirect()
            ->route('education.index')
            ->with('toast', ['type' => 'success', 'message' => $message]);
    }
}
