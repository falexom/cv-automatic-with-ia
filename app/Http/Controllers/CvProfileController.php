<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCvProfileRequest;
use App\Http\Requests\UpdateCvProfileRequest;
use App\Models\CvProfile;
use App\Support\CvProfileContent;
use App\Support\NameNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CvProfileController extends Controller
{
    public function index(): Response
    {
        $profiles = CvProfile::query()
            ->orderBy('name')
            ->get()
            ->map(fn (CvProfile $profile): array => [
                'id' => $profile->id,
                'name' => $profile->name,
                'professional_title' => $profile->professional_title,
                'is_active' => $profile->is_active,
            ])
            ->values()
            ->all();

        return Inertia::render('Profiles/Index', [
            'profiles' => $profiles,
            'can_create_initial_profiles' => CvProfile::query()->doesntExist(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Profiles/Create', [
            'options' => CvProfileContent::options(),
        ]);
    }

    public function store(StoreCvProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->ensureUniqueName($data['name']);

        $profile = CvProfileContent::create($data);

        return redirect()
            ->route('profiles.show', $profile)
            ->with('toast', ['type' => 'success', 'message' => 'Perfil creado.']);
    }

    public function storeInitial(): RedirectResponse
    {
        $created = DB::transaction(function (): bool {
            if (CvProfile::query()->exists()) {
                return false;
            }

            CvProfile::create([
                'name' => 'Analista',
                'professional_title' => 'Analista de Sistemas',
                'summary' => null,
                'is_active' => true,
            ]);

            CvProfile::create([
                'name' => 'Programador',
                'professional_title' => 'Programador / Desarrollador',
                'summary' => null,
                'is_active' => true,
            ]);

            return true;
        });

        return redirect()
            ->route('profiles.index')
            ->with('toast', $created
                ? ['type' => 'success', 'message' => 'Perfiles iniciales creados.']
                : ['type' => 'info', 'message' => 'Los perfiles iniciales solo se crean cuando no existe ningún perfil.']);
    }

    public function show(CvProfile $cvProfile): Response
    {
        return Inertia::render('Profiles/Show', [
            'profile' => $this->ownData($cvProfile),
            'personal_data' => CvProfileContent::personalData(),
            'content' => CvProfileContent::content($cvProfile),
        ]);
    }

    public function edit(CvProfile $cvProfile): Response
    {
        return Inertia::render('Profiles/Edit', [
            'profile' => $this->ownData($cvProfile),
            'options' => CvProfileContent::options(),
            'selections' => CvProfileContent::selections($cvProfile),
        ]);
    }

    public function update(UpdateCvProfileRequest $request, CvProfile $cvProfile): RedirectResponse
    {
        $data = $request->validated();

        $this->ensureUniqueName($data['name'], $cvProfile);

        CvProfileContent::update($cvProfile, $data);

        return redirect()
            ->route('profiles.show', $cvProfile)
            ->with('toast', ['type' => 'success', 'message' => 'Perfil actualizado.']);
    }

    public function activate(CvProfile $cvProfile): RedirectResponse
    {
        $cvProfile->update(['is_active' => true]);

        return redirect()
            ->route('profiles.index')
            ->with('toast', ['type' => 'success', 'message' => 'Perfil activado.']);
    }

    public function deactivate(CvProfile $cvProfile): RedirectResponse
    {
        $cvProfile->update(['is_active' => false]);

        return redirect()
            ->route('profiles.index')
            ->with('toast', ['type' => 'success', 'message' => 'Perfil desactivado.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function ownData(CvProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'professional_title' => $profile->professional_title,
            'summary' => $profile->summary,
            'is_active' => $profile->is_active,
        ];
    }

    private function ensureUniqueName(string $name, ?CvProfile $ignore = null): void
    {
        $exists = CvProfile::query()
            ->where('normalized_name', NameNormalizer::normalize($name))
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'Ya existe un perfil con ese nombre.',
            ]);
        }
    }
}
