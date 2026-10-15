<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCertificationRequest;
use App\Http\Requests\UpdateCertificationRequest;
use App\Models\Certification;
use App\Models\Profile;
use App\Support\NameNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CertificationController extends Controller
{
    public function index(): Response
    {
        $records = Profile::current()
            ->certifications()
            ->with('skills:id,name,category')
            ->chronological()
            ->get()
            ->map(fn (Certification $certification): array => $this->serialize($certification));

        return Inertia::render('Certifications/Index', [
            'records' => $records,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Certifications/Create', [
            'skills' => $this->skillOptions(),
        ]);
    }

    public function store(StoreCertificationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $confirmDuplicate = (bool) ($data['confirm_duplicate'] ?? false);
        $skillIds = $data['skill_ids'] ?? [];
        unset($data['confirm_duplicate'], $data['skill_ids']);
        $data = $this->normalizeDate($data);

        $duplicate = $this->findDuplicate($data);

        if ($duplicate !== null && ! $confirmDuplicate) {
            return back()->with('duplicate_warning', $this->warningPayload($duplicate));
        }

        $certification = Profile::current()->certifications()->create($data);
        $certification->skills()->sync($skillIds);

        return $this->redirectToIndex('Certificación guardada.');
    }

    public function edit(Certification $certification): Response
    {
        $certification->load('skills:id,name,category');

        return Inertia::render('Certifications/Edit', [
            'record' => [
                ...$this->serialize($certification),
                'skill_ids' => $certification->skills->pluck('id')->all(),
            ],
            'skills' => $this->skillOptions(),
        ]);
    }

    public function update(UpdateCertificationRequest $request, Certification $certification): RedirectResponse
    {
        $data = $request->validated();
        $confirmDuplicate = (bool) ($data['confirm_duplicate'] ?? false);
        $skillIds = $data['skill_ids'] ?? [];
        unset($data['confirm_duplicate'], $data['skill_ids']);
        $data = $this->normalizeDate($data);

        $duplicate = $this->findDuplicate($data, $certification);

        if ($duplicate !== null && ! $confirmDuplicate) {
            return back()->with('duplicate_warning', $this->warningPayload($duplicate));
        }

        $certification->update($data);
        $certification->skills()->sync($skillIds);

        return $this->redirectToIndex('Certificación actualizada.');
    }

    public function destroy(Certification $certification): RedirectResponse
    {
        $certification->delete();

        return $this->redirectToIndex('Certificación eliminada.');
    }

    /**
     * FR-018: a certification with the same normalized name and
     * institution requires explicit confirmation.
     *
     * @param  array<string, mixed>  $data
     */
    private function findDuplicate(array $data, ?Certification $ignore = null): ?Certification
    {
        $name = NameNormalizer::normalize($data['name'] ?? null);
        $institution = NameNormalizer::normalize($data['institution'] ?? null);

        return Profile::current()
            ->certifications()
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->get()
            ->first(fn (Certification $certification): bool => NameNormalizer::normalize($certification->name) === $name
                && NameNormalizer::normalize($certification->institution) === $institution);
    }

    /**
     * @return array{existing_id: int, name: string, institution: string|null}
     */
    private function warningPayload(Certification $certification): array
    {
        return [
            'existing_id' => $certification->id,
            'name' => $certification->name,
            'institution' => $certification->institution,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeDate(array $data): array
    {
        $data['date_year'] ??= null;
        $data['date_month'] ??= null;

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Certification $certification): array
    {
        return [
            'id' => $certification->id,
            'name' => $certification->name,
            'institution' => $certification->institution,
            'date_year' => $certification->date_year,
            'date_month' => $certification->date_month,
            'duration' => $certification->duration,
            'code' => $certification->code,
            'validation_url' => $certification->validation_url,
            'category' => $certification->category,
            'skills' => $certification->skills
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
            ->route('certifications.index')
            ->with('toast', ['type' => 'success', 'message' => $message]);
    }
}
