<?php

namespace App\Support;

use App\Models\CvProfile;
use Illuminate\Support\Collection;

class CvProfileResolver
{
    /**
     * Resolve the active profile selection from the full query array.
     *
     * The complete query is used instead of a single scalar so an array
     * (`?profile[]=1`) is treated as an invalid value rather than throwing.
     *
     * @param  array<int|string, mixed>  $query
     * @return array{
     *     status: 'no_active_profiles'|'selection_required'|'unavailable'|'ready',
     *     profiles: Collection<int, CvProfile>,
     *     profile: CvProfile|null
     * }
     */
    public function resolve(array $query): array
    {
        $activeProfiles = CvProfile::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($activeProfiles->isEmpty()) {
            return [
                'status' => 'no_active_profiles',
                'profiles' => $activeProfiles,
                'profile' => null,
            ];
        }

        $profileQuery = $query['profile'] ?? null;

        if ($profileQuery === null || $profileQuery === '') {
            return [
                'status' => 'selection_required',
                'profiles' => $activeProfiles,
                'profile' => null,
            ];
        }

        if (! is_string($profileQuery)
            || ! ctype_digit($profileQuery)
            || (int) $profileQuery < 1) {
            return [
                'status' => 'unavailable',
                'profiles' => $activeProfiles,
                'profile' => null,
            ];
        }

        $profile = $activeProfiles->firstWhere('id', (int) $profileQuery);

        return [
            'status' => $profile === null ? 'unavailable' : 'ready',
            'profiles' => $activeProfiles,
            'profile' => $profile,
        ];
    }
}
