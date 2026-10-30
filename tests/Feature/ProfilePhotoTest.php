<?php

use App\Models\CvProfile;
use App\Models\Profile;
use App\Support\CvPreviewContent;
use App\Support\ProfilePhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
});

function profilePhotoUpload(string $name = 'foto.jpg', int $width = 400, int $height = 300): UploadedFile
{
    return UploadedFile::fake()->image($name, $width, $height);
}

/**
 * @return array{photo_focus_x: int, photo_focus_y: int, photo_zoom: int}
 */
function profilePhotoCrop(int $x = 50, int $y = 50, int $zoom = 100): array
{
    return [
        'photo_focus_x' => $x,
        'photo_focus_y' => $y,
        'photo_zoom' => $zoom,
    ];
}

it('stores a valid photo privately and persists its crop', function () {
    $this->post('/profile/photo', [
        'photo' => profilePhotoUpload(),
        ...profilePhotoCrop(35, 65, 150),
    ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('toast.type', 'success');

    $profile = Profile::current()->fresh();

    expect($profile->photo_path)
        ->toBeString()
        ->toStartWith('profile-photos/')
        ->and($profile->photo_mime)->toBe('image/jpeg')
        ->and($profile->photo_focus_x)->toBe(35)
        ->and($profile->photo_focus_y)->toBe(65)
        ->and($profile->photo_zoom)->toBe(150);

    Storage::disk('local')->assertExists($profile->photo_path);

    expect(Storage::disk('local')->allFiles('profile-photos'))->toHaveCount(1);
});

it('accepts png photos and keeps their data uri type', function () {
    $this->post('/profile/photo', [
        'photo' => profilePhotoUpload('foto.png', 320, 240),
        ...profilePhotoCrop(),
    ])->assertRedirect(route('profile.edit'));

    $profile = Profile::current()->fresh();
    $payload = ProfilePhoto::payload($profile);

    expect($profile->photo_mime)->toBe('image/png')
        ->and($payload)->not->toBeNull()
        ->and($payload['data_uri'])->toStartWith('data:image/png;base64,');
});

it('exposes the photo as a data uri without internal paths', function () {
    $this->post('/profile/photo', [
        'photo' => profilePhotoUpload(),
        ...profilePhotoCrop(40, 60, 140),
    ])->assertRedirect(route('profile.edit'));

    $profile = Profile::current()->fresh();

    $this->get('/profile')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Edit')
            ->has('profile.photo')
            ->where('profile.photo.focus_x', 40)
            ->where('profile.photo.focus_y', 60)
            ->where('profile.photo.zoom', 140)
            ->where(
                'profile.photo.data_uri',
                fn ($value): bool => is_string($value)
                    && str_starts_with($value, 'data:image/jpeg;base64,'),
            ));

    $payload = ProfilePhoto::payload($profile);
    $response = $this->get('/profile');

    expect($payload)->not->toBeNull()
        ->and(json_encode($payload))->not->toContain($profile->photo_path)
        ->and($response->getContent())
        ->not->toContain($profile->photo_path)
        ->not->toContain('profile-photos/');
});

it('updates the crop without replacing the stored file', function () {
    $this->post('/profile/photo', [
        'photo' => profilePhotoUpload(),
        ...profilePhotoCrop(50, 50, 100),
    ])->assertRedirect(route('profile.edit'));

    $path = Profile::current()->fresh()->photo_path;

    $this->post('/profile/photo', profilePhotoCrop(10, 90, 125))
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasNoErrors();

    $profile = Profile::current()->fresh();

    expect($profile->photo_path)->toBe($path)
        ->and($profile->photo_focus_x)->toBe(10)
        ->and($profile->photo_focus_y)->toBe(90)
        ->and($profile->photo_zoom)->toBe(125);

    Storage::disk('local')->assertExists($path);
});

it('replaces the photo and removes only the previous file', function () {
    $this->post('/profile/photo', [
        'photo' => profilePhotoUpload('primera.jpg'),
        ...profilePhotoCrop(),
    ])->assertRedirect(route('profile.edit'));

    $firstPath = Profile::current()->fresh()->photo_path;

    $this->post('/profile/photo', [
        'photo' => profilePhotoUpload('segunda.jpg', 500, 400),
        ...profilePhotoCrop(20, 30, 160),
    ])->assertRedirect(route('profile.edit'));

    $profile = Profile::current()->fresh();

    expect($profile->photo_path)
        ->not->toBe($firstPath)
        ->and($profile->photo_focus_x)->toBe(20)
        ->and($profile->photo_focus_y)->toBe(30)
        ->and($profile->photo_zoom)->toBe(160);

    Storage::disk('local')->assertMissing($firstPath);
    Storage::disk('local')->assertExists($profile->photo_path);
    expect(Storage::disk('local')->allFiles('profile-photos'))->toHaveCount(1);
});

it('deletes the photo, its file and resets the crop', function () {
    $this->post('/profile/photo', [
        'photo' => profilePhotoUpload(),
        ...profilePhotoCrop(25, 75, 180),
    ])->assertRedirect(route('profile.edit'));

    $path = Profile::current()->fresh()->photo_path;

    $this->delete('/profile/photo')
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('toast.type', 'success');

    $profile = Profile::current()->fresh();

    expect($profile->photo_path)->toBeNull()
        ->and($profile->photo_mime)->toBeNull()
        ->and($profile->photo_focus_x)->toBe(ProfilePhoto::DEFAULT_FOCUS)
        ->and($profile->photo_focus_y)->toBe(ProfilePhoto::DEFAULT_FOCUS)
        ->and($profile->photo_zoom)->toBe(ProfilePhoto::MIN_ZOOM);

    Storage::disk('local')->assertMissing($path);
    expect(Storage::disk('local')->allFiles('profile-photos'))->toBe([]);
});

it('rejects unsupported formats, fake content, oversized files and invalid crops without storing', function () {
    $invalidPhotos = [
        ['photo' => UploadedFile::fake()->image('foto.gif', 100, 100)],
        ['photo' => UploadedFile::fake()->createWithContent('foto.jpg', 'esto no es una imagen')],
        ['photo' => UploadedFile::fake()->create('foto.jpg', 5121, 'image/jpeg')],
    ];

    foreach ($invalidPhotos as $payload) {
        $this->post('/profile/photo', [
            ...$payload,
            ...profilePhotoCrop(),
        ])->assertSessionHasErrors('photo');
    }

    $this->post('/profile/photo', [
        'photo' => profilePhotoUpload(),
        'photo_focus_x' => 101,
        'photo_focus_y' => -1,
        'photo_zoom' => 201,
    ])->assertSessionHasErrors([
        'photo_focus_x',
        'photo_focus_y',
        'photo_zoom',
    ]);

    expect(Profile::current()->fresh()->photo_path)->toBeNull()
        ->and(Storage::disk('local')->allFiles('profile-photos'))->toBe([]);
});

it('includes the current photo in the derived cv payload and omits it after deletion', function () {
    $master = Profile::current();
    $master->update(['full_name' => 'Ada Lovelace']);

    $cvProfile = CvProfile::factory()->create([
        'name' => 'Perfil con foto',
        'professional_title' => 'Analista',
        'summary' => 'Resumen.',
    ]);

    $this->post('/profile/photo', [
        'photo' => profilePhotoUpload(),
        ...profilePhotoCrop(30, 70, 130),
    ])->assertRedirect(route('profile.edit'));

    $path = Profile::current()->fresh()->photo_path;
    $preview = app(CvPreviewContent::class)->build($cvProfile);

    expect($preview['personal_data']['photo'])
        ->toMatchArray([
            'focus_x' => 30,
            'focus_y' => 70,
            'zoom' => 130,
        ])
        ->and($preview['personal_data']['photo']['data_uri'])
        ->toStartWith('data:image/jpeg;base64,')
        ->and(json_encode($preview))->not->toContain($path);

    $this->delete('/profile/photo')->assertRedirect(route('profile.edit'));

    $preview = app(CvPreviewContent::class)->build($cvProfile->fresh());

    expect($preview['personal_data']['photo'])->toBeNull()
        ->and($preview['has_data'])->toBeTrue();
});

it('keeps cv generation working without a photo', function () {
    $master = Profile::current();
    $master->update(['full_name' => 'Ada Lovelace']);

    $cvProfile = CvProfile::factory()->create([
        'name' => 'Perfil sin foto',
        'professional_title' => 'Analista',
        'summary' => 'Resumen.',
    ]);

    $preview = app(CvPreviewContent::class)->build($cvProfile);

    expect($preview['personal_data']['photo'])->toBeNull()
        ->and($preview['has_data'])->toBeTrue();

    $this->get("/cv?profile={$cvProfile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cv/Index')
            ->where('status', 'ready')
            ->where('preview.personal_data.photo', null));
});
