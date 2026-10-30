<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfilePhotoRequest;
use App\Models\Profile;
use App\Support\ProfilePhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Throwable;

class ProfilePhotoController extends Controller
{
    public function __construct(
        private readonly ProfilePhoto $photos,
    ) {}

    public function store(UpdateProfilePhotoRequest $request): RedirectResponse
    {
        $profile = Profile::current();
        $data = $request->validated();
        $file = $request->file('photo');
        $previousPath = $profile->photo_path;
        $stored = null;

        try {
            if ($file instanceof UploadedFile) {
                $stored = $this->photos->store($file);
            }

            $profile->update([
                'photo_path' => $stored['path'] ?? $profile->photo_path,
                'photo_mime' => $stored['mime'] ?? $profile->photo_mime,
                'photo_focus_x' => (int) $data['photo_focus_x'],
                'photo_focus_y' => (int) $data['photo_focus_y'],
                'photo_zoom' => (int) $data['photo_zoom'],
            ]);

            if ($stored !== null
                && is_string($previousPath)
                && $previousPath !== ''
                && $previousPath !== $stored['path']) {
                $this->photos->delete($previousPath);
            }
        } catch (Throwable $exception) {
            if ($stored !== null) {
                $this->photos->delete($stored['path']);
            }

            report($exception);

            return back()->withErrors([
                'photo' => 'No se pudo guardar la fotografía. Volvé a intentar.',
            ]);
        }

        return redirect()
            ->route('profile.edit')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Fotografía guardada.',
            ]);
    }

    public function destroy(): RedirectResponse
    {
        $profile = Profile::current();
        $path = $profile->photo_path;

        $profile->update([
            'photo_path' => null,
            'photo_mime' => null,
            'photo_focus_x' => ProfilePhoto::DEFAULT_FOCUS,
            'photo_focus_y' => ProfilePhoto::DEFAULT_FOCUS,
            'photo_zoom' => ProfilePhoto::MIN_ZOOM,
        ]);

        if (is_string($path) && $path !== '') {
            $this->photos->delete($path);
        }

        return redirect()
            ->route('profile.edit')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Fotografía eliminada.',
            ]);
    }
}
