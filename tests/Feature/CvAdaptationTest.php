<?php

use App\Models\Certification;
use App\Models\CvAdaptation;
use App\Models\CvProfile;
use App\Models\EducationEntry;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\WorkExperience;
use App\Support\CvAdaptation\CvAdaptationAnalyzer;
use App\Support\CvAdaptation\CvAdaptationCodex;
use App\Support\CvAdaptation\CvAdaptationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class FakeCvAdaptationAnalyzer implements CvAdaptationAnalyzer
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $calls = [];

    public function __construct(private Closure $callback) {}

    public function setCallback(Closure $callback): void
    {
        $this->callback = $callback;
    }

    public function analyze(array $catalog, string $offer, ?string $strategy = null): array
    {
        $this->calls[] = [
            'catalog' => $catalog,
            'offer' => $offer,
            'strategy' => $strategy,
        ];

        return ($this->callback)($catalog, $offer, $strategy);
    }
}

function bindCvAdaptationAnalyzer(Closure $callback): FakeCvAdaptationAnalyzer
{
    $fake = new FakeCvAdaptationAnalyzer($callback);

    app()->bind(CvAdaptationAnalyzer::class, fn (): FakeCvAdaptationAnalyzer => $fake);

    return $fake;
}

/**
 * @return array<string, mixed>
 */
function cvAdaptationMasterOnly(): array
{
    $master = Profile::current();
    $master->update([
        'full_name' => 'Persona Secreta',
        'professional_title' => 'Arquitecta de Soluciones',
        'phone' => '+54 11 9999 8888',
        'email' => 'privado@example.com',
        'location' => 'Ciudad Privada',
        'linkedin_url' => 'https://privado.example.com/linkedin',
        'github_url' => 'https://privado.example.com/github',
        'website_url' => 'https://privado.example.com',
        'summary' => 'Resumen profesional del Maestro basado en datos registrados.',
    ]);

    $experience = WorkExperience::factory()->create([
        'company' => 'Acme',
        'position' => 'Arquitecta de Software',
        'description' => 'Diseño de servicios y APIs.',
        'responsibilities' => 'Relevamiento y arquitectura.',
        'achievements' => 'Reducción de tiempos de entrega.',
    ]);
    $education = EducationEntry::factory()->create([
        'institution' => 'Universidad Nacional',
        'program' => 'Ingeniería en Sistemas',
        'description' => 'Formación en sistemas de información.',
    ]);
    $certification = Certification::factory()->create([
        'name' => 'Curso de Laravel',
        'institution' => 'Academy',
        'duration' => '40 horas',
        'category' => 'Programación',
    ]);
    $skill = Skill::factory()->create([
        'name' => 'Laravel',
        'category' => 'tecnologia',
    ]);
    $project = Project::factory()->create([
        'name' => 'Portal interno',
        'role' => 'Arquitecta',
        'description' => 'Migración del portal corporativo.',
        'results' => 'Reducción del tiempo de carga.',
    ]);

    return compact('master', 'experience', 'education', 'certification', 'skill', 'project');
}

/**
 * @return array<string, mixed>
 */
function cvAdaptationFixture(): array
{
    $data = cvAdaptationMasterOnly();

    $profile = CvProfile::factory()->create([
        'name' => 'Analista',
        'professional_title' => 'Analista de Sistemas',
        'summary' => 'Resumen del perfil de origen.',
    ]);
    $profile->workExperiences()->attach($data['experience'], ['relevance' => 'alta']);
    $profile->educationEntries()->attach($data['education'], ['relevance' => 'media']);
    $profile->certifications()->attach($data['certification'], ['relevance' => 'media']);
    $profile->skills()->attach($data['skill'], ['relevance' => 'alta']);
    $profile->projects()->attach($data['project'], ['relevance' => 'baja']);

    return [...$data, 'profile' => $profile];
}

/**
 * @param  array<string, mixed>  $catalog
 * @return array<string, mixed>
 */
function cvAdaptationProposal(array $catalog): array
{
    return [
        'schema_version' => 1,
        'operation' => 'duplicate',
        'source_profile_ref' => $catalog['profiles'][0]['ref'] ?? null,
        'job_title' => 'Analista de Sistemas',
        'company' => 'Acme',
        'rationale' => [
            'La oferta pide Laravel y el perfil registra experiencia relacionada.',
        ],
        'profile_details' => [
            'name' => 'Analista adaptado',
            'professional_title' => 'Analista de Sistemas',
            'summary' => 'Resumen basado en los datos registrados.',
        ],
        'requirements' => [
            [
                'ref' => 'req_01',
                'text' => 'Experiencia con Laravel',
                'importance' => 'required',
                'coverage' => 'supported',
                'exclusive' => false,
                'exclusive_support' => null,
            ],
            [
                'ref' => 'req_02',
                'text' => 'Experiencia con AWS',
                'importance' => 'required',
                'coverage' => 'gap',
                'exclusive' => true,
                'exclusive_support' => 'La oferta indica que AWS es excluyente.',
            ],
        ],
        'gaps' => [
            [
                'requirement_ref' => 'req_02',
                'reason' => 'No hay evidencia de AWS registrada.',
                'evidence_state' => 'not_recorded',
            ],
        ],
        'selected_content' => [
            'work_experiences' => [
                ['ref' => $catalog['work_experiences'][0]['ref'], 'relevance' => 'alta'],
            ],
            'education_entries' => [
                ['ref' => $catalog['education_entries'][0]['ref'], 'relevance' => 'media'],
            ],
            'certifications' => [
                ['ref' => $catalog['certifications'][0]['ref'], 'relevance' => 'media'],
            ],
            'skills' => [
                ['ref' => $catalog['skills'][0]['ref'], 'relevance' => 'alta'],
            ],
            'projects' => [
                ['ref' => $catalog['projects'][0]['ref'], 'relevance' => 'baja'],
            ],
        ],
        'development_recommendations' => [
            [
                'kind' => 'skill',
                'label' => 'AWS',
                'reason' => 'La oferta lo pide y no está registrado.',
                'evidence_state' => 'not_recorded',
            ],
        ],
        'clarifying_questions' => [
            [
                'requirement_ref' => 'req_02',
                'question' => '¿Tenés experiencia con AWS?',
            ],
        ],
    ];
}

/**
 * @param  array<string, mixed>  $catalog
 * @return array<string, mixed>
 */
function cvAdaptationReuseProposal(array $catalog): array
{
    return [
        'schema_version' => 1,
        'operation' => 'reuse',
        'source_profile_ref' => $catalog['profiles'][0]['ref'],
        'job_title' => 'Analista de Sistemas',
        'company' => 'Acme',
        'rationale' => ['El perfil activo ya cubre la oferta sin cambios.'],
        'profile_details' => null,
        'requirements' => [
            [
                'ref' => 'req_01',
                'text' => 'Experiencia con Laravel',
                'importance' => 'required',
                'coverage' => 'supported',
                'exclusive' => false,
                'exclusive_support' => null,
            ],
        ],
        'gaps' => [],
        'selected_content' => null,
        'development_recommendations' => [],
        'clarifying_questions' => [],
    ];
}

/**
 * @param  array<string, mixed>  $catalog
 * @return array<string, mixed>
 */
function cvAdaptationCreateProposal(array $catalog): array
{
    $proposal = cvAdaptationProposal($catalog);
    $proposal['operation'] = 'create';
    $proposal['source_profile_ref'] = null;

    return $proposal;
}

/**
 * @param  array<string, mixed>  $catalog
 * @return array<string, mixed>
 */
function cvAdaptationReuseWithFeedback(array $catalog): array
{
    $proposal = cvAdaptationProposal($catalog);
    $proposal['operation'] = 'reuse';
    $proposal['source_profile_ref'] = $catalog['profiles'][0]['ref'];
    $proposal['profile_details'] = null;
    $proposal['selected_content'] = null;

    return $proposal;
}

/**
 * @param  array<string, mixed>  $catalog
 * @return array<string, mixed>
 */
function cvAdaptationRichProposal(array $catalog): array
{
    $proposal = cvAdaptationProposal($catalog);
    $proposal['job_title'] = 'Backend Engineer';
    $proposal['company'] = null;
    $proposal['requirements'] = [
        [
            'ref' => 'req_01',
            'text' => 'Experiencia con Laravel',
            'importance' => 'required',
            'coverage' => 'supported',
            'exclusive' => false,
            'exclusive_support' => null,
        ],
        [
            'ref' => 'req_02',
            'text' => 'Experiencia con APIs',
            'importance' => 'preferred',
            'coverage' => 'partial',
            'exclusive' => false,
            'exclusive_support' => null,
        ],
        [
            'ref' => 'req_03',
            'text' => 'Experiencia con AWS',
            'importance' => 'required',
            'coverage' => 'gap',
            'exclusive' => true,
            'exclusive_support' => 'La oferta exige AWS de forma excluyente.',
        ],
        [
            'ref' => 'req_04',
            'text' => 'Certificación vigente',
            'importance' => 'required',
            'coverage' => 'needs_confirmation',
            'exclusive' => false,
            'exclusive_support' => null,
        ],
        [
            'ref' => 'req_05',
            'text' => 'Trabajo remoto',
            'importance' => 'required',
            'coverage' => 'supported',
            'exclusive' => false,
            'exclusive_support' => null,
        ],
    ];
    $proposal['gaps'] = [
        [
            'requirement_ref' => 'req_03',
            'reason' => 'No hay evidencia de AWS registrada.',
            'evidence_state' => 'not_recorded',
        ],
        [
            'requirement_ref' => 'req_04',
            'reason' => 'Falta confirmar la certificación.',
            'evidence_state' => 'needs_confirmation',
        ],
    ];
    $proposal['development_recommendations'] = [
        [
            'kind' => 'skill',
            'label' => 'AWS',
            'reason' => 'La oferta lo pide y no está registrado.',
            'evidence_state' => 'not_recorded',
        ],
        [
            'kind' => 'course',
            'label' => 'Curso de AWS',
            'reason' => 'Permitiría cubrir la brecha.',
            'evidence_state' => 'needs_confirmation',
        ],
    ];
    $proposal['clarifying_questions'] = [
        [
            'requirement_ref' => 'req_03',
            'question' => '¿Tenés experiencia con AWS?',
        ],
        [
            'requirement_ref' => 'req_04',
            'question' => '¿Tenés la certificación vigente?',
        ],
        [
            'requirement_ref' => 'req_05',
            'question' => '¿Podés trabajar remoto?',
        ],
    ];

    return $proposal;
}

/**
 * @return array<string, mixed>
 */
function analyzeCvAdaptation(
    TestCase $test,
    Closure $callback,
    string $strategy = 'auto',
    ?FakeCvAdaptationAnalyzer $fake = null,
): array {
    $fake ??= bindCvAdaptationAnalyzer($callback);
    $fake->setCallback($callback);

    $response = $test->postJson('/cv/adapt/analyze', [
        'offer' => 'Buscamos una persona con experiencia en Laravel, APIs y AWS.',
        'consent' => true,
        'strategy' => $strategy,
    ]);

    $response->assertOk();

    return $response->json();
}

/**
 * @param  array<string, mixed>  $analysis
 * @param  array<string, mixed>  $overrides
 */
function applyCvAdaptation(
    TestCase $test,
    array $analysis,
    array $overrides = [],
): TestResponse {
    $proposal = $analysis['proposal'];
    $questions = collect($proposal['clarifying_questions'])
        ->map(fn (array $question, int $index): array => [
            'index' => $index,
            'status' => 'unanswered',
            'answer' => null,
            'user_confirmed_absent' => false,
        ])
        ->values()
        ->all();

    return $test->postJson('/cv/adapt/apply', [
        'operation' => $proposal['operation'],
        'ref_nonce' => $analysis['ref_nonce'],
        'confirmation_token' => $analysis['confirmation_token'],
        'retention_accepted' => true,
        'job_title' => $proposal['job_title'],
        'company' => $proposal['company'],
        'questions' => $questions,
        'source_profile_ref' => $proposal['source_profile_ref'],
        'profile_details' => $proposal['profile_details'],
        'selected_content' => $proposal['selected_content'],
        ...$overrides,
    ]);
}

/**
 * @param  array<string, mixed>  $value
 * @return list<string>
 */
function arrayKeysRecursive(array $value): array
{
    $keys = [];

    foreach ($value as $key => $item) {
        $keys[] = (string) $key;

        if (is_array($item)) {
            $keys = [...$keys, ...arrayKeysRecursive($item)];
        }
    }

    return array_values(array_unique($keys));
}

it('renders the adaptation page with the consent notice and master summary', function () {
    cvAdaptationFixture();
    $fake = bindCvAdaptationAnalyzer(fn (array $catalog): array => cvAdaptationProposal($catalog));

    $this->get('/cv/adapt')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Cv/Adapt')
            ->where('data_notice.destination', 'Codex/OpenAI')
            ->where('data_notice.model', 'gpt-6-luna')
            ->where('data_notice.model_label', 'Luna 6')
            ->where('data_notice.reasoning_effort', 'xhigh')
            ->where('data_notice.reasoning_effort_label', 'Extra High')
            ->has('data_notice.sent', 8)
            ->has('data_notice.excluded', 5)
            ->has('data_notice.retention.stored', 7)
            ->has('data_notice.retention.excluded', 3)
            ->missing('data_notice.binary')
            ->missing('data_notice.node_binary')
            ->missing('data_notice.timeout')
            ->where('summary.work_experiences', 1)
            ->where('summary.education_entries', 1)
            ->where('summary.certifications', 1)
            ->where('summary.skills', 1)
            ->where('summary.projects', 1)
            ->where('summary.profiles', 1)
            ->where('summary.active_profiles', 1));

    expect($fake->calls)->toBeEmpty();
});

it('shows configured model and effort differences without hiding them', function () {
    config([
        'services.codex.model' => 'custom-model',
        'services.codex.reasoning_effort' => 'high',
    ]);

    $this->get('/cv/adapt')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('data_notice.model', 'custom-model')
            ->where('data_notice.model_label', 'custom-model')
            ->where('data_notice.reasoning_effort', 'high')
            ->where('data_notice.reasoning_effort_label', 'High'));
});

it('resolves the real Codex analyzer from the effective configuration', function () {
    expect(app(CvAdaptationAnalyzer::class))
        ->toBeInstanceOf(CvAdaptationCodex::class);
});

it('refuses to analyze without explicit consent and does not call Codex', function () {
    cvAdaptationFixture();
    $fake = bindCvAdaptationAnalyzer(fn (array $catalog): array => cvAdaptationProposal($catalog));

    $this->postJson('/cv/adapt/analyze', [
        'offer' => 'Buscamos Laravel.',
        'consent' => false,
        'strategy' => 'auto',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('consent');

    expect($fake->calls)->toBeEmpty()
        ->and(CvProfile::count())->toBe(1)
        ->and(CvAdaptation::count())->toBe(0);
});

it('analyzes with consent and sends only approved professional data', function () {
    cvAdaptationFixture();
    $fake = bindCvAdaptationAnalyzer(fn (array $catalog): array => cvAdaptationProposal($catalog));

    $response = $this->postJson('/cv/adapt/analyze', [
        'offer' => 'Buscamos Laravel, APIs y AWS.',
        'consent' => true,
        'strategy' => 'auto',
    ])->assertOk();

    $data = $response->json();

    expect($data['proposal']['operation'])->toBe('duplicate')
        ->and($data['proposal']['job_title'])->toBe('Analista de Sistemas')
        ->and($data['proposal']['company'])->toBe('Acme')
        ->and($data['ref_nonce'])->toMatch('/^[a-f0-9]{32}$/')
        ->and($data['confirmation_token'])->toBeString()->not->toBeEmpty()
        ->and($fake->calls)->toHaveCount(1)
        ->and($fake->calls[0]['offer'])->toContain('Laravel')
        ->and($fake->calls[0]['strategy'])->toBe('auto')
        ->and(CvProfile::count())->toBe(1)
        ->and(WorkExperience::count())->toBe(1)
        ->and(EducationEntry::count())->toBe(1)
        ->and(Certification::count())->toBe(1)
        ->and(Skill::count())->toBe(1)
        ->and(Project::count())->toBe(1)
        ->and(CvAdaptation::count())->toBe(0);

    $catalog = $fake->calls[0]['catalog'];
    $keys = arrayKeysRecursive($catalog);
    $encoded = json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    expect($keys)->not->toContain('id')
        ->and($keys)->not->toContain('profile_id')
        ->and($keys)->not->toContain('full_name')
        ->and($keys)->not->toContain('phone')
        ->and($keys)->not->toContain('email')
        ->and($keys)->not->toContain('location')
        ->and($keys)->not->toContain('linkedin_url')
        ->and($keys)->not->toContain('github_url')
        ->and($keys)->not->toContain('website_url')
        ->and($keys)->not->toContain('photo_path')
        ->and($keys)->not->toContain('photo_mime')
        ->and($keys)->not->toContain('code')
        ->and($keys)->not->toContain('validation_url')
        ->and($keys)->not->toContain('url')
        ->and($encoded)->not->toContain('Persona Secreta')
        ->and($encoded)->not->toContain('privado@example.com')
        ->and($encoded)->not->toContain('Ciudad Privada')
        ->and($encoded)->not->toContain('https://privado.example.com');
});

it('returns a controlled error when Codex fails without writing', function () {
    cvAdaptationFixture();
    $fake = bindCvAdaptationAnalyzer(function (): array {
        throw new CvAdaptationException(
            'codex_auth',
            'Codex no tiene una sesión válida.',
            502,
        );
    });

    $this->postJson('/cv/adapt/analyze', [
        'offer' => 'Buscamos Laravel.',
        'consent' => true,
        'strategy' => 'auto',
    ])
        ->assertStatus(502)
        ->assertJson([
            'code' => 'codex_auth',
            'message' => 'Codex no tiene una sesión válida.',
        ]);

    expect($fake->calls)->toHaveCount(1)
        ->and(CvProfile::count())->toBe(1)
        ->and(CvAdaptation::count())->toBe(0);
});

it('rejects a proposal that does not match the requested strategy', function () {
    cvAdaptationFixture();
    bindCvAdaptationAnalyzer(fn (array $catalog): array => cvAdaptationProposal($catalog));

    $this->postJson('/cv/adapt/analyze', [
        'offer' => 'Buscamos Laravel.',
        'consent' => true,
        'strategy' => 'create',
    ])
        ->assertStatus(502)
        ->assertJsonPath('code', 'strategy_mismatch');

    expect(CvProfile::count())->toBe(1)
        ->and(CvAdaptation::count())->toBe(0);
});

it('rejects an invalid structured proposal', function () {
    cvAdaptationFixture();
    bindCvAdaptationAnalyzer(fn (): array => ['schema_version' => 1]);

    $this->postJson('/cv/adapt/analyze', [
        'offer' => 'Buscamos Laravel.',
        'consent' => true,
        'strategy' => 'auto',
    ])
        ->assertStatus(502)
        ->assertJsonPath('code', 'schema_invalid');

    expect(CvProfile::count())->toBe(1)
        ->and(CvAdaptation::count())->toBe(0);
});

it('reuses an active profile and saves an evaluation without modifying the profile', function () {
    $fixture = cvAdaptationFixture();
    $profile = $fixture['profile'];
    $updatedAt = $profile->updated_at;
    $counts = [
        'profiles' => CvProfile::count(),
        'master' => Profile::count(),
        'experiences' => WorkExperience::count(),
        'pivot' => DB::table('cv_profile_work_experience')->count(),
    ];

    $analysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationReuseProposal($catalog),
        'reuse',
    );

    applyCvAdaptation($this, $analysis)
        ->assertOk()
        ->assertJsonPath('redirect', route('cv.index', ['profile' => $profile->id]));

    $adaptation = CvAdaptation::query()->firstOrFail();

    expect(CvProfile::count())->toBe($counts['profiles'])
        ->and(Profile::count())->toBe($counts['master'])
        ->and(WorkExperience::count())->toBe($counts['experiences'])
        ->and(DB::table('cv_profile_work_experience')->count())->toBe($counts['pivot'])
        ->and($profile->fresh()->updated_at->equalTo($updatedAt))->toBeTrue()
        ->and($adaptation->cv_profile_id)->toBe($profile->id)
        ->and($adaptation->operation)->toBe('reuse')
        ->and($adaptation->job_title)->toBe('Analista de Sistemas')
        ->and($adaptation->company)->toBe('Acme')
        ->and($adaptation->analyzed_at)->not->toBeNull()
        ->and($adaptation->confirmed_at)->not->toBeNull()
        ->and($adaptation->context['requirements'])->toHaveCount(1)
        ->and($adaptation->observations()->count())->toBe(0);
});

it('rejects reuse when the profile was deactivated before confirmation', function () {
    $fixture = cvAdaptationFixture();
    $profile = $fixture['profile'];

    $analysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationReuseProposal($catalog),
        'reuse',
    );

    $profile->update(['is_active' => false]);

    applyCvAdaptation($this, $analysis)
        ->assertStatus(422)
        ->assertJsonPath('code', 'stale_reference');

    expect(CvProfile::count())->toBe(1)
        ->and(CvAdaptation::count())->toBe(0);
});

it('duplicates a profile without altering the origin or the master', function () {
    $fixture = cvAdaptationFixture();
    $source = $fixture['profile'];
    $sourceUpdatedAt = $source->updated_at;
    $masterUpdatedAt = $fixture['master']->updated_at;
    $masterCounts = [
        'experiences' => WorkExperience::count(),
        'education' => EducationEntry::count(),
        'certifications' => Certification::count(),
        'skills' => Skill::count(),
        'projects' => Project::count(),
    ];

    $analysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationProposal($catalog),
        'duplicate',
    );

    $response = applyCvAdaptation($this, $analysis)->assertOk();

    $created = CvProfile::query()->where('name', 'Analista adaptado')->firstOrFail();

    $response->assertJsonPath('redirect', route('cv.index', ['profile' => $created->id]));

    $adaptation = CvAdaptation::query()->firstOrFail();

    expect(CvProfile::count())->toBe(2)
        ->and($created->is_active)->toBeTrue()
        ->and($created->professional_title)->toBe('Analista de Sistemas')
        ->and($created->summary)->toBe('Resumen basado en los datos registrados.')
        ->and($created->workExperiences()->count())->toBe(1)
        ->and($created->educationEntries()->count())->toBe(1)
        ->and($created->certifications()->count())->toBe(1)
        ->and($created->skills()->count())->toBe(1)
        ->and($created->projects()->count())->toBe(1)
        ->and($created->workExperiences()->first()->pivot->relevance)->toBe('alta')
        ->and($created->skills()->first()->pivot->relevance)->toBe('alta')
        ->and($created->skills()->where('name', 'AWS')->doesntExist())->toBeTrue()
        ->and($adaptation->cv_profile_id)->toBe($created->id)
        ->and($adaptation->operation)->toBe('duplicate')
        ->and($adaptation->job_title)->toBe('Analista de Sistemas')
        ->and($adaptation->company)->toBe('Acme')
        ->and($adaptation->context['requirements'][1]['exclusive'])->toBeTrue()
        ->and($adaptation->context['requirements'][1]['exclusive_support'])->toBe('La oferta indica que AWS es excluyente.')
        ->and($adaptation->observations()->count())->toBe(3)
        ->and($source->adaptations()->count())->toBe(0)
        ->and($created->adaptations()->count())->toBe(1)
        ->and($source->fresh()->name)->toBe('Analista')
        ->and($source->fresh()->professional_title)->toBe('Analista de Sistemas')
        ->and($source->fresh()->summary)->toBe('Resumen del perfil de origen.')
        ->and($source->fresh()->workExperiences()->count())->toBe(1)
        ->and($source->fresh()->updated_at->equalTo($sourceUpdatedAt))->toBeTrue()
        ->and($fixture['master']->fresh()->updated_at->equalTo($masterUpdatedAt))->toBeTrue()
        ->and(WorkExperience::count())->toBe($masterCounts['experiences'])
        ->and(EducationEntry::count())->toBe($masterCounts['education'])
        ->and(Certification::count())->toBe($masterCounts['certifications'])
        ->and(Skill::count())->toBe($masterCounts['skills'])
        ->and(Project::count())->toBe($masterCounts['projects']);
});

it('duplicates an inactive source profile without modifying it', function () {
    $fixture = cvAdaptationFixture();
    $source = $fixture['profile'];
    $source->update(['is_active' => false]);
    $sourceUpdatedAt = $source->fresh()->updated_at;

    $analysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationProposal($catalog),
        'duplicate',
    );

    $response = applyCvAdaptation($this, $analysis)->assertOk();
    $created = CvProfile::query()->where('name', 'Analista adaptado')->firstOrFail();

    $response->assertJsonPath('redirect', route('cv.index', ['profile' => $created->id]));

    expect(CvProfile::count())->toBe(2)
        ->and($created->is_active)->toBeTrue()
        ->and($source->fresh()->is_active)->toBeFalse()
        ->and($source->fresh()->updated_at->equalTo($sourceUpdatedAt))->toBeTrue()
        ->and(CvAdaptation::count())->toBe(1)
        ->and(CvAdaptation::query()->firstOrFail()->cv_profile_id)->toBe($created->id)
        ->and($source->adaptations()->count())->toBe(0);
});

it('rejects duplicate when the source profile disappeared before confirmation', function () {
    $fixture = cvAdaptationFixture();

    $analysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationProposal($catalog),
        'duplicate',
    );

    $fixture['profile']->delete();

    applyCvAdaptation($this, $analysis)
        ->assertStatus(422)
        ->assertJsonPath('code', 'stale_reference');

    expect(CvProfile::count())->toBe(0)
        ->and(CvAdaptation::count())->toBe(0);
});

it('creates a profile even when no profile exists and respects reviewed content', function () {
    $master = cvAdaptationMasterOnly();

    $analysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationCreateProposal($catalog),
        'create',
    );

    $response = applyCvAdaptation($this, $analysis)->assertOk();
    $created = CvProfile::query()->firstOrFail();

    $response->assertJsonPath('redirect', route('cv.index', ['profile' => $created->id]));

    expect(CvProfile::count())->toBe(1)
        ->and($created->name)->toBe('Analista adaptado')
        ->and($created->is_active)->toBeTrue()
        ->and($created->workExperiences()->count())->toBe(1)
        ->and($created->skills()->count())->toBe(1)
        ->and(WorkExperience::count())->toBe(1)
        ->and(Profile::count())->toBe(1)
        ->and(CvAdaptation::count())->toBe(1)
        ->and(CvAdaptation::query()->firstOrFail()->cv_profile_id)->toBe($created->id);
});

it('rejects stale selected references without partial persistence', function () {
    $fixture = cvAdaptationFixture();

    $analysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationProposal($catalog),
        'duplicate',
    );

    $fixture['experience']->delete();

    applyCvAdaptation($this, $analysis)
        ->assertStatus(422)
        ->assertJsonPath('code', 'stale_reference');

    expect(CvProfile::count())->toBe(1)
        ->and(CvProfile::query()->where('name', 'Analista adaptado')->exists())->toBeFalse()
        ->and(CvAdaptation::count())->toBe(0);
});

it('rejects a duplicate profile name before writing', function () {
    cvAdaptationFixture();
    CvProfile::factory()->create(['name' => 'Analista adaptado']);

    $analysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationProposal($catalog),
        'duplicate',
    );

    applyCvAdaptation($this, $analysis)
        ->assertStatus(422)
        ->assertJsonPath('code', 'duplicate_name');

    expect(CvProfile::count())->toBe(2)
        ->and(CvAdaptation::count())->toBe(0);
});

it('rejects a tampered selection reference before writing', function () {
    cvAdaptationFixture();

    $analysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationProposal($catalog),
        'duplicate',
    );

    applyCvAdaptation($this, $analysis, [
        'selected_content' => [
            ...$analysis['proposal']['selected_content'],
            'work_experiences' => [['ref' => 'exp_invalid', 'relevance' => 'alta']],
        ],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('selected_content.work_experiences.0.ref');

    expect(CvProfile::count())->toBe(1)
        ->and(CvAdaptation::count())->toBe(0);
});
it('keeps multiple confirmed reuse evaluations on the same profile and opens each one', function () {
    $fixture = cvAdaptationFixture();
    $profile = $fixture['profile'];
    $profileUpdatedAt = $profile->updated_at;
    $fake = bindCvAdaptationAnalyzer(fn (array $catalog): array => cvAdaptationReuseProposal($catalog));

    $first = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationReuseProposal($catalog),
        'reuse',
        $fake,
    );
    applyCvAdaptation($this, $first)->assertOk();
    $firstAdaptation = CvAdaptation::query()->firstOrFail();
    $firstContext = $firstAdaptation->context;

    $second = analyzeCvAdaptation(
        $this,
        function (array $catalog): array {
            $proposal = cvAdaptationReuseProposal($catalog);
            $proposal['job_title'] = 'Backend Engineer';
            $proposal['company'] = 'Otra Empresa';

            return $proposal;
        },
        'reuse',
        $fake,
    );
    applyCvAdaptation($this, $second)->assertOk();

    $adaptations = CvAdaptation::query()->orderByDesc('id')->get();

    expect($adaptations)->toHaveCount(2)
        ->and($profile->adaptations()->count())->toBe(2)
        ->and($firstAdaptation->fresh()->context)->toBe($firstContext)
        ->and($profile->fresh()->updated_at->equalTo($profileUpdatedAt))->toBeTrue();

    $this->get("/profiles/{$profile->id}/adaptations")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profiles/Adaptations/Index')
            ->where('profile.id', $profile->id)
            ->has('adaptations', 2)
            ->where('adaptations.0.job_title', 'Backend Engineer')
            ->where('adaptations.0.company', 'Otra Empresa')
            ->where('adaptations.1.job_title', 'Analista de Sistemas')
            ->where('adaptations.1.company', 'Acme'));

    foreach ($adaptations as $adaptation) {
        $this->get("/profiles/{$profile->id}/adaptations/{$adaptation->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profiles/Adaptations/Show')
                ->where('profile.id', $profile->id)
                ->where('adaptation.id', $adaptation->id)
                ->where('adaptation.operation', 'reuse')
                ->where('adaptation.context.requirements.0.text', 'Experiencia con Laravel')
                ->has('adaptation.observations', 0));
    }
});

it('stores literal answers and explicit human absence without changing coverage', function () {
    $fixture = cvAdaptationFixture();

    $analysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationProposal($catalog),
        'duplicate',
    );

    applyCvAdaptation($this, $analysis, [
        'questions' => [
            [
                'index' => 0,
                'status' => 'answered',
                'answer' => 'No tengo esa certificación.',
                'user_confirmed_absent' => true,
            ],
        ],
    ])->assertOk();

    $created = CvProfile::query()->where('name', 'Analista adaptado')->firstOrFail();
    $adaptation = CvAdaptation::query()->firstOrFail();
    $question = $adaptation->context['clarifying_questions'][0];

    expect($question['status'])->toBe('answered')
        ->and($question['answer'])->toBe('No tengo esa certificación.')
        ->and($question['answer_source'])->toBe('user_statement')
        ->and($question['user_confirmed_absent'])->toBeTrue()
        ->and($adaptation->context['requirements'][1]['coverage'])->toBe('gap')
        ->and($adaptation->context['gaps'][0]['evidence_state'])->toBe('not_recorded')
        ->and($adaptation->observations()->where('type', 'question')->count())->toBe(1);

    $this->get("/profiles/{$created->id}/adaptations/{$adaptation->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profiles/Adaptations/Show')
            ->where('adaptation.context.clarifying_questions.0.status', 'answered')
            ->where('adaptation.context.clarifying_questions.0.answer', 'No tengo esa certificación.')
            ->where('adaptation.context.clarifying_questions.0.user_confirmed_absent', true)
            ->where('adaptation.counts.supported', 1)
            ->where('adaptation.counts.gap', 1));
});

it('does not infer absence without confirmation and preserves discarded questions', function () {
    $fixture = cvAdaptationFixture();
    $fake = bindCvAdaptationAnalyzer(fn (array $catalog): array => cvAdaptationProposal($catalog));

    $withoutConfirmation = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationProposal($catalog),
        'duplicate',
        $fake,
    );

    applyCvAdaptation($this, $withoutConfirmation, [
        'questions' => [
            [
                'index' => 0,
                'status' => 'answered',
                'answer' => 'No tengo esa certificación.',
                'user_confirmed_absent' => false,
            ],
        ],
    ])->assertOk();

    $created = CvProfile::query()->where('name', 'Analista adaptado')->firstOrFail();
    $answered = $created->adaptations()->firstOrFail();

    expect($answered->context['clarifying_questions'][0]['user_confirmed_absent'])->toBeFalse()
        ->and($answered->context['clarifying_questions'][0]['answer_source'])->toBe('user_statement');

    $discardedAnalysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationReuseWithFeedback($catalog),
        'reuse',
        $fake,
    );

    applyCvAdaptation($this, $discardedAnalysis, [
        'questions' => [
            [
                'index' => 0,
                'status' => 'discarded',
                'answer' => null,
                'user_confirmed_absent' => false,
            ],
        ],
    ])->assertOk();

    $source = $fixture['profile'];
    $discarded = $source->adaptations()->firstOrFail();
    $discardedQuestion = $discarded->context['clarifying_questions'][0];

    expect($discardedQuestion['status'])->toBe('discarded')
        ->and($discardedQuestion['answer'])->toBeNull()
        ->and($discardedQuestion['answer_source'])->toBeNull()
        ->and($discardedQuestion['user_confirmed_absent'])->toBeFalse()
        ->and($discarded->observations()->where('type', 'question')->count())->toBe(0)
        ->and($discarded->observations()->count())->toBe(2);
});

it('manages observations independently and rejects foreign observations', function () {
    $fixture = cvAdaptationFixture();
    $fake = bindCvAdaptationAnalyzer(fn (array $catalog): array => cvAdaptationProposal($catalog));

    $analysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationProposal($catalog),
        'duplicate',
        $fake,
    );
    applyCvAdaptation($this, $analysis)->assertOk();

    $created = CvProfile::query()->where('name', 'Analista adaptado')->firstOrFail();
    $adaptation = CvAdaptation::query()->firstOrFail();
    $observation = $adaptation->observations()->where('type', 'gap')->firstOrFail();
    $contextBefore = $adaptation->context;
    $evaluationUpdatedAt = $adaptation->updated_at;

    $this->patch("/profiles/{$created->id}/adaptations/{$adaptation->id}/observations/{$observation->id}", [
        'status' => 'resolved',
        'note' => 'Revisar AWS.',
    ])->assertRedirect(route('profiles.adaptations.show', [$created, $adaptation]));

    expect($observation->fresh()->status)->toBe('resolved')
        ->and($observation->fresh()->note)->toBe('Revisar AWS.')
        ->and($adaptation->fresh()->context)->toBe($contextBefore)
        ->and($adaptation->fresh()->updated_at->equalTo($evaluationUpdatedAt))->toBeTrue();

    $this->patch("/profiles/{$created->id}/adaptations/{$adaptation->id}/observations/{$observation->id}", [
        'status' => 'pending',
        'note' => '',
    ])->assertRedirect();

    expect($observation->fresh()->status)->toBe('pending')
        ->and($observation->fresh()->note)->toBeNull();

    $this->patch("/profiles/{$created->id}/adaptations/{$adaptation->id}/observations/{$observation->id}", [
        'status' => 'discarded',
        'note' => null,
    ])->assertRedirect();

    expect($observation->fresh()->status)->toBe('discarded');

    $this->patchJson("/profiles/{$created->id}/adaptations/{$adaptation->id}/observations/{$observation->id}", [
        'status' => 'invalid',
        'note' => 'No debe aplicarse.',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');

    expect($observation->fresh()->status)->toBe('discarded');

    foreach (['recommendation', 'question'] as $type) {
        $typedObservation = $adaptation->observations()->where('type', $type)->firstOrFail();

        $this->patch("/profiles/{$created->id}/adaptations/{$adaptation->id}/observations/{$typedObservation->id}", [
            'status' => 'resolved',
            'note' => "Seguimiento {$type}.",
        ])->assertRedirect();

        expect($typedObservation->fresh()->status)->toBe('resolved')
            ->and($typedObservation->fresh()->note)->toBe("Seguimiento {$type}.");
    }

    $foreignAnalysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationReuseWithFeedback($catalog),
        'reuse',
        $fake,
    );
    applyCvAdaptation($this, $foreignAnalysis)->assertOk();

    $source = $fixture['profile'];
    $foreignAdaptation = $source->adaptations()->firstOrFail();
    $foreignObservation = $foreignAdaptation->observations()->firstOrFail();

    $this->get("/profiles/{$source->id}/adaptations/{$adaptation->id}")
        ->assertNotFound();

    $this->patch("/profiles/{$created->id}/adaptations/{$adaptation->id}/observations/{$foreignObservation->id}", [
        'status' => 'resolved',
        'note' => 'No debe aplicarse.',
    ])->assertNotFound();

    expect($foreignObservation->fresh()->status)->toBe('pending')
        ->and($foreignObservation->fresh()->note)->toBeNull();
});

it('reports coverage counts and exclusive support without changing them from observations', function () {
    $fixture = cvAdaptationFixture();

    $analysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationRichProposal($catalog),
        'duplicate',
    );
    applyCvAdaptation($this, $analysis)->assertOk();

    $created = CvProfile::query()->where('name', 'Analista adaptado')->firstOrFail();
    $adaptation = CvAdaptation::query()->firstOrFail();

    $this->get("/profiles/{$created->id}/adaptations/{$adaptation->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profiles/Adaptations/Show')
            ->where('adaptation.counts.supported', 2)
            ->where('adaptation.counts.partial', 1)
            ->where('adaptation.counts.gap', 1)
            ->where('adaptation.counts.needs_confirmation', 1)
            ->where('adaptation.counts.pending_coverage', 2)
            ->where('adaptation.counts.total', 5)
            ->where('adaptation.counts.pending_observations', 7)
            ->where('adaptation.context.requirements.0.exclusive', false)
            ->where('adaptation.context.requirements.2.exclusive', true)
            ->where('adaptation.context.requirements.2.exclusive_support', 'La oferta exige AWS de forma excluyente.'));

    $observation = $adaptation->observations()->firstOrFail();

    $this->patch("/profiles/{$created->id}/adaptations/{$adaptation->id}/observations/{$observation->id}", [
        'status' => 'resolved',
        'note' => 'Seguimiento cerrado.',
    ])->assertRedirect();

    $this->get("/profiles/{$created->id}/adaptations/{$adaptation->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('adaptation.counts.supported', 2)
            ->where('adaptation.counts.partial', 1)
            ->where('adaptation.counts.gap', 1)
            ->where('adaptation.counts.needs_confirmation', 1)
            ->where('adaptation.counts.pending_coverage', 2)
            ->where('adaptation.counts.total', 5)
            ->where('adaptation.counts.pending_observations', 6));
});

it('keeps evaluations historical and out of CV exports', function () {
    $fixture = cvAdaptationFixture();

    $analysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationProposal($catalog),
        'duplicate',
    );
    applyCvAdaptation($this, $analysis)->assertOk();

    $created = CvProfile::query()->where('name', 'Analista adaptado')->firstOrFail();
    $adaptation = CvAdaptation::query()->firstOrFail();
    $originalContext = $adaptation->context;
    $originalAnalyzedAt = $adaptation->analyzed_at;

    $fixture['master']->update(['professional_title' => 'Título actualizado del Maestro']);
    $fixture['experience']->update(['position' => 'Puesto actualizado']);
    $created->update(['professional_title' => 'Título actualizado del perfil']);

    expect($adaptation->fresh()->context)->toBe($originalContext)
        ->and($adaptation->fresh()->analyzed_at->equalTo($originalAnalyzedAt))->toBeTrue();

    $this->get("/profiles/{$created->id}/adaptations/{$adaptation->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profiles/Adaptations/Show')
            ->where('adaptation.context.requirements.0.text', 'Experiencia con Laravel')
            ->where('adaptation.analyzed_at', $originalAnalyzedAt->toIso8601String()));

    $this->get("/cv/markdown?profile={$created->id}")
        ->assertOk()
        ->assertSee('Título actualizado del perfil')
        ->assertSee('Puesto actualizado')
        ->assertDontSee('No hay evidencia de AWS registrada.')
        ->assertDontSee('AWS');
});

it('is idempotent for the same confirmation and rejects altered tokens', function () {
    $fixture = cvAdaptationFixture();

    $analysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationProposal($catalog),
        'duplicate',
    );

    applyCvAdaptation($this, $analysis)->assertOk();

    $created = CvProfile::query()->where('name', 'Analista adaptado')->firstOrFail();
    $profileCount = CvProfile::count();
    $adaptationCount = CvAdaptation::count();

    applyCvAdaptation($this, $analysis)
        ->assertOk()
        ->assertJsonPath('redirect', route('cv.index', ['profile' => $created->id]));

    expect(CvProfile::count())->toBe($profileCount)
        ->and(CvAdaptation::count())->toBe($adaptationCount);

    applyCvAdaptation($this, $analysis, [
        'ref_nonce' => 'ffffffffffffffffffffffffffffffff',
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'invalid_confirmation');

    applyCvAdaptation($this, $analysis, [
        'confirmation_token' => 'not-a-valid-confirmation-token',
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'invalid_confirmation');

    applyCvAdaptation($this, $analysis, [
        'retention_accepted' => false,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('retention_accepted');

    expect(CvProfile::count())->toBe($profileCount)
        ->and(CvAdaptation::count())->toBe($adaptationCount);
});

it('shows zero evaluations and allows consulting an inactive profile', function () {
    $fixture = cvAdaptationFixture();
    $profile = $fixture['profile'];

    $this->get("/profiles/{$profile->id}/adaptations")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profiles/Adaptations/Index')
            ->where('profile.id', $profile->id)
            ->where('adaptations', []));

    $analysis = analyzeCvAdaptation(
        $this,
        fn (array $catalog): array => cvAdaptationReuseProposal($catalog),
        'reuse',
    );
    applyCvAdaptation($this, $analysis)->assertOk();

    $adaptation = CvAdaptation::query()->firstOrFail();
    $profile->update(['is_active' => false]);

    $this->get("/profiles/{$profile->id}/adaptations")
        ->assertRedirect(route('profiles.adaptations.show', [$profile, $adaptation]));

    $this->get("/profiles/{$profile->id}/adaptations/{$adaptation->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profiles/Adaptations/Show')
            ->where('profile.is_active', false)
            ->where('adaptation.id', $adaptation->id));
});

it('stores only the approved evaluation context and reviewed metadata', function () {
    $fixture = cvAdaptationFixture();

    $analysis = analyzeCvAdaptation(
        $this,
        function (array $catalog): array {
            $proposal = cvAdaptationProposal($catalog);
            $proposal['job_title'] = 'Puesto revisado';
            $proposal['company'] = null;
            $proposal['profile_details']['name'] = 'Perfil con nombre revisado';

            return $proposal;
        },
        'duplicate',
    );

    applyCvAdaptation($this, $analysis)->assertOk();

    $created = CvProfile::query()->where('name', 'Perfil con nombre revisado')->firstOrFail();
    $adaptation = CvAdaptation::query()->firstOrFail();
    $context = $adaptation->context;

    expect($adaptation->job_title)->toBe('Puesto revisado')
        ->and($adaptation->company)->toBeNull()
        ->and($created->name)->toBe('Perfil con nombre revisado')
        ->and($context)->toHaveKey('requirements')
        ->and($context)->not->toHaveKey('offer')
        ->and($context)->not->toHaveKey('prompt')
        ->and($context)->not->toHaveKey('catalog')
        ->and($context)->not->toHaveKey('selected_content')
        ->and($context)->not->toHaveKey('profile_details')
        ->and(json_encode($context, JSON_UNESCAPED_UNICODE))
        ->not->toContain('Buscamos una persona con experiencia en Laravel');
});

it('exposes the adaptation and history routes without adding profile deletion', function () {
    expect(Route::has('cv.adapt.create'))->toBeTrue()
        ->and(Route::has('cv.adapt.analyze'))->toBeTrue()
        ->and(Route::has('cv.adapt.apply'))->toBeTrue()
        ->and(Route::has('profiles.adaptations.index'))->toBeTrue()
        ->and(Route::has('profiles.adaptations.show'))->toBeTrue()
        ->and(Route::has('profiles.adaptations.observations.update'))->toBeTrue()
        ->and(Route::has('profiles.destroy'))->toBeFalse();
});
