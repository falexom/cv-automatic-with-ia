<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\Profile;
use App\Support\ProfilePhoto;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        $profile = Profile::current();

        return Inertia::render('Profile/Edit', [
            'profile' => [
                'id' => $profile->id,
                'full_name' => $profile->full_name,
                'professional_title' => $profile->professional_title,
                'phone' => $profile->phone,
                'email' => $profile->email,
                'location' => $profile->location,
                'linkedin_url' => $profile->linkedin_url,
                'github_url' => $profile->github_url,
                'website_url' => $profile->website_url,
                'summary' => $profile->summary,
                'photo' => ProfilePhoto::payload($profile),
            ],
            'links' => $profile->links()
                ->orderBy('id')
                ->get(['id', 'label', 'url']),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $profile = Profile::current();
        $data = $request->validated();
        $links = $data['links'] ?? [];
        unset($data['links']);

        $profile->update($data);

        $profile->links()->delete();

        if ($links !== []) {
            $profile->links()->createMany($links);
        }

        return redirect()
            ->route('profile.edit')
            ->with('toast', ['type' => 'success', 'message' => 'Perfil guardado.']);
    }
}
