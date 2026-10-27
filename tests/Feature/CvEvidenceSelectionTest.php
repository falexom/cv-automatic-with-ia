<?php

use App\Models\Certification;
use App\Models\CvProfile;
use App\Models\Document;
use App\Models\EducationEntry;
use App\Models\WorkExperience;
use App\Support\CvEvidenceSelection;
use App\Support\CvPreviewContent;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

it('shows one entry per eligible document with included associations, availability and verification date', function () {
    $includedExperience = WorkExperience::factory()->create([
        'company' => 'Acme',
        'position' => 'Analista funcional',
    ]);
    $excludedExperience = WorkExperience::factory()->create([
        'company' => 'Otra',
        'position' => 'Excluida',
    ]);
    $includedEducation = EducationEntry::factory()->create([
        'institution' => 'Universidad',
        'program' => 'Sistemas',
    ]);
    $includedCertification = Certification::factory()->create([
        'name' => 'Curso de Laravel',
    ]);

    $profile = CvProfile::factory()->create();
    $profile->workExperiences()->attach($includedExperience, ['relevance' => 'alta']);
    $profile->educationEntries()->attach($includedEducation, ['relevance' => 'media']);
    $profile->certifications()->attach($includedCertification, ['relevance' => 'baja']);

    $verifiedAt = now()->subDay()->startOfSecond();

    $sharedDocument = Document::factory()->create([
        'title' => 'Título visible',
        'is_available' => true,
        'last_verified_at' => $verifiedAt,
    ]);
    $sharedDocument->workExperiences()->attach([
        $includedExperience->id,
        $excludedExperience->id,
    ]);
    $sharedDocument->educationEntries()->attach($includedEducation);

    $unavailableDocument = Document::factory()->unavailable()->create([
        'title' => null,
        'original_filename' => 'certificado.pdf',
    ]);
    $unavailableDocument->certifications()->attach($includedCertification);

    $excludedDocument = Document::factory()->create();
    $excludedDocument->workExperiences()->attach($excludedExperience);

    $counts = [
        'documents' => Document::count(),
        'experience_links' => DB::table('document_work_experience')->count(),
        'education_links' => DB::table('document_education_entry')->count(),
        'certification_links' => DB::table('document_certification')->count(),
    ];

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cv/Index')
            ->where('status', 'ready')
            ->has('evidences', 2)
            ->where('evidences.0.id', $unavailableDocument->id)
            ->where('evidences.0.display_name', 'certificado.pdf')
            ->where('evidences.0.is_available', false)
            ->where('evidences.0.last_verified_at', null)
            ->where('evidences.0.associations', [
                [
                    'type' => 'certification',
                    'id' => $includedCertification->id,
                    'label' => 'Curso de Laravel',
                ],
            ])
            ->where('evidences.1.id', $sharedDocument->id)
            ->where('evidences.1.display_name', 'Título visible')
            ->where('evidences.1.is_available', true)
            ->where('evidences.1.last_verified_at', $verifiedAt->toIso8601String())
            ->where('evidences.1.associations', [
                [
                    'type' => 'education',
                    'id' => $includedEducation->id,
                    'label' => 'Universidad — Sistemas',
                ],
                [
                    'type' => 'experience',
                    'id' => $includedExperience->id,
                    'label' => 'Acme — Analista funcional',
                ],
            ])
            ->missing('evidences.2'));

    expect(Document::count())->toBe($counts['documents'])
        ->and(DB::table('document_work_experience')->count())->toBe($counts['experience_links'])
        ->and(DB::table('document_education_entry')->count())->toBe($counts['education_links'])
        ->and(DB::table('document_certification')->count())->toBe($counts['certification_links'])
        ->and($sharedDocument->fresh()->last_verified_at->equalTo($verifiedAt))->toBeTrue();
});

it('recomputes eligible evidence for each profile without persisting the selection', function () {
    $experienceA = WorkExperience::factory()->create();
    $experienceB = WorkExperience::factory()->create();

    $documentA = Document::factory()->create(['title' => 'Documento A']);
    $documentA->workExperiences()->attach($experienceA);

    $documentB = Document::factory()->create(['title' => 'Documento B']);
    $documentB->workExperiences()->attach($experienceB);

    $profileA = CvProfile::factory()->create(['name' => 'Perfil A']);
    $profileB = CvProfile::factory()->create(['name' => 'Perfil B']);
    $profileA->workExperiences()->attach($experienceA);
    $profileB->workExperiences()->attach($experienceB);

    $counts = [
        'documents' => Document::count(),
        'links' => DB::table('document_work_experience')->count(),
        'profile_selections' => DB::table('cv_profile_work_experience')->count(),
    ];

    $this->get("/cv?profile={$profileA->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'ready')
            ->has('evidences', 1)
            ->where('evidences.0.id', $documentA->id)
            ->missing('evidences.1'));

    $this->get("/cv?profile={$profileB->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'ready')
            ->has('evidences', 1)
            ->where('evidences.0.id', $documentB->id)
            ->missing('evidences.1'));

    $this->get("/cv?profile={$profileA->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'ready')
            ->has('evidences', 1)
            ->where('evidences.0.id', $documentA->id));

    expect(Document::count())->toBe($counts['documents'])
        ->and(DB::table('document_work_experience')->count())->toBe($counts['links'])
        ->and(DB::table('cv_profile_work_experience')->count())->toBe($counts['profile_selections']);
});

it('returns an empty evidence list when included records have no documents', function () {
    $experience = WorkExperience::factory()->create();
    $profile = CvProfile::factory()->create();
    $profile->workExperiences()->attach($experience);

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'ready')
            ->where('evidences', []));
});

it('keeps the generator states without evidence when no profile is usable', function () {
    CvProfile::factory()->inactive()->create();

    $this->get('/cv')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'no_active_profiles')
            ->where('evidences', []));

    $active = CvProfile::factory()->create();

    $this->get('/cv')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'selection_required')
            ->where('evidences', []));

    $this->get('/cv?profile=999999')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'unavailable')
            ->where('evidences', []));

    $this->get("/cv?profile={$active->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'ready')
            ->where('evidences', []));
});

it('reports a controlled error without evidence when the evidence query fails', function () {
    $profile = CvProfile::factory()->create();

    $this->app->bind(CvEvidenceSelection::class, fn () => new class extends CvEvidenceSelection
    {
        public function eligibleFor(CvProfile $profile): array
        {
            throw new RuntimeException('Fallo de evidencias');
        }
    });

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'error')
            ->where('selected_profile_id', $profile->id)
            ->where('preview', null)
            ->where('evidences', []));
});

it('keeps the preview failure state without evidence', function () {
    $profile = CvProfile::factory()->create();

    $this->app->bind(CvPreviewContent::class, fn () => new class extends CvPreviewContent
    {
        public function build(CvProfile $profile): array
        {
            throw new RuntimeException('Fallo de composición');
        }
    });

    $this->get("/cv?profile={$profile->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'error')
            ->where('preview', null)
            ->where('evidences', []));
});
