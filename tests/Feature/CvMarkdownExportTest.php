<?php

use App\Models\Certification;
use App\Models\CvProfile;
use App\Models\EducationEntry;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\WorkExperience;
use App\Support\CvMarkdownRenderer;
use Illuminate\Support\Facades\Route;

function cvMarkdownMaster(array $attributes = []): Profile
{
    $master = Profile::current();
    $master->update($attributes);

    return $master;
}

it('downloads the selected profile as a UTF-8 markdown attachment', function () {
    cvMarkdownMaster([
        'full_name' => 'Ada Lovelace',
        'phone' => '+54 11 5555 1234',
        'email' => 'ada@example.com',
        'location' => 'Buenos Aires, Argentina',
        'linkedin_url' => 'https://www.linkedin.com/in/ada',
    ]);

    $profile = CvProfile::factory()->create([
        'name' => 'Programador / Desarrollador',
        'professional_title' => 'Desarrolladora',
        'summary' => 'Resumen con ñandú y estadística.',
    ]);

    expect(Route::has('cv.markdown'))->toBeTrue();

    $response = $this->get("/cv/markdown?profile={$profile->id}");

    $response->assertOk();

    $markdown = $response->getContent();

    expect($markdown)->toStartWith("# Ada Lovelace\n")
        ->and($markdown)->toContain('**Desarrolladora**')
        ->and($markdown)->toContain('## Contacto')
        ->and($markdown)->toContain('Buenos Aires, Argentina')
        ->and($markdown)->toContain('ada@example.com')
        ->and($markdown)->toContain('+54 11 5555 1234')
        ->and($markdown)->toContain('https://www.linkedin.com/in/ada')
        ->and($markdown)->toContain('## Resumen')
        ->and($markdown)->toContain('Resumen con ñandú y estadística.')
        ->and(mb_check_encoding($markdown, 'UTF-8'))->toBeTrue()
        ->and($response->headers->get('Content-Type'))->toBe('text/markdown; charset=UTF-8')
        ->and($response->headers->get('Content-Disposition'))
        ->toBe('attachment; filename="cv-programador-desarrollador.md"')
        ->and($response->headers->get('Content-Length'))->toBe((string) strlen($markdown))
        ->and($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->headers->get('Cache-Control'))->toContain('private')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

it('exports only the selected records in relevance order with available fields', function () {
    cvMarkdownMaster([
        'full_name' => 'Ada Lovelace',
        'professional_title' => 'Título del Maestro',
        'summary' => 'Resumen del Maestro',
    ]);

    $high = WorkExperience::factory()->create([
        'position' => 'Experiencia prioritaria',
        'company' => 'Empresa prioritaria',
        'start_year' => 2018,
        'start_month' => 1,
        'end_year' => 2020,
        'end_month' => 12,
        'is_current' => false,
        'description' => 'Descripción prioritaria.',
        'responsibilities' => "Responsabilidad A.\nResponsabilidad B.",
        'achievements' => 'Logro prioritario.',
    ]);
    $low = WorkExperience::factory()->create([
        'position' => 'Experiencia posterior',
        'company' => 'Empresa posterior',
        'start_year' => 2024,
        'start_month' => 5,
        'end_year' => null,
        'end_month' => null,
        'is_current' => true,
    ]);
    $excluded = WorkExperience::factory()->create([
        'position' => 'Experiencia excluida',
    ]);

    $education = EducationEntry::factory()->create([
        'institution' => 'Universidad Incluida',
        'program' => 'Sistemas Incluidos',
        'degree_level' => 'Licenciatura Incluida',
        'start_year' => 2015,
        'start_month' => 2,
        'end_year' => 2020,
        'end_month' => 12,
        'description' => 'Descripción de educación incluida.',
    ]);
    $excludedEducation = EducationEntry::factory()->create([
        'program' => 'Educación excluida',
    ]);

    $certification = Certification::factory()->create([
        'name' => 'Certificación incluida',
        'institution' => 'Academia incluida',
        'duration' => '40 horas',
        'category' => 'Categoría incluida',
        'code' => 'CODIGO-INCLUIDO',
        'validation_url' => 'https://academy.example.com/validar/incluida',
    ]);
    $excludedCertification = Certification::factory()->create([
        'name' => 'Certificación excluida',
    ]);

    $skill = Skill::factory()->create([
        'name' => 'Habilidad incluida',
        'category' => 'tecnologia',
    ]);
    $excludedSkill = Skill::factory()->create([
        'name' => 'Habilidad excluida',
    ]);

    $project = Project::factory()->create([
        'name' => 'Proyecto incluido',
        'role' => 'Rol incluido',
        'description' => 'Descripción del proyecto incluido.',
        'results' => 'Resultado incluido.',
        'url' => 'https://example.com/proyecto-incluido',
    ]);
    $excludedProject = Project::factory()->create([
        'name' => 'Proyecto excluido',
    ]);

    $profile = CvProfile::factory()->create([
        'name' => 'Perfil seleccionado',
        'professional_title' => 'Título propio',
        'summary' => 'Resumen propio.',
    ]);

    $profile->workExperiences()->attach($high, ['relevance' => 'alta']);
    $profile->workExperiences()->attach($low, ['relevance' => 'baja']);
    $profile->educationEntries()->attach($education, ['relevance' => 'alta']);
    $profile->certifications()->attach($certification, ['relevance' => 'alta']);
    $profile->skills()->attach($skill, ['relevance' => 'alta']);
    $profile->projects()->attach($project, ['relevance' => 'alta']);

    $other = CvProfile::factory()->create([
        'name' => 'Otro perfil',
        'professional_title' => 'Título del otro perfil',
        'summary' => 'Resumen del otro perfil.',
    ]);
    $otherExperience = WorkExperience::factory()->create([
        'position' => 'Contenido del otro perfil',
    ]);
    $other->workExperiences()->attach($otherExperience, ['relevance' => 'alta']);

    $markdown = $this->get("/cv/markdown?profile={$profile->id}")
        ->assertOk()
        ->getContent();

    expect($markdown)->toContain('## Experiencia laboral')
        ->and($markdown)->toContain('### Experiencia prioritaria — Empresa prioritaria')
        ->and($markdown)->toContain('*ene 2018 – dic 2020*')
        ->and($markdown)->toContain('Descripción prioritaria.')
        ->and($markdown)->toContain("**Responsabilidades**\n\n- Responsabilidad A.\n- Responsabilidad B.")
        ->and($markdown)->toContain("**Logros**\n\nLogro prioritario.")
        ->and($markdown)->toContain('### Experiencia posterior — Empresa posterior')
        ->and($markdown)->toContain('*may 2024 – actual*')
        ->and($markdown)->toContain('## Educación')
        ->and($markdown)->toContain('### Sistemas Incluidos')
        ->and($markdown)->toContain('Universidad Incluida')
        ->and($markdown)->toContain('*Licenciatura Incluida · feb 2015 – dic 2020*')
        ->and($markdown)->toContain('Descripción de educación incluida.')
        ->and($markdown)->toContain('## Cursos y certificaciones')
        ->and($markdown)->toContain('### Certificación incluida')
        ->and($markdown)->toContain('Academia incluida · 40 horas · Categoría incluida')
        ->and($markdown)->toContain('*jun 2023*')
        ->and($markdown)->toContain('Código: CODIGO-INCLUIDO')
        ->and($markdown)->toContain('[Validar certificación](https://academy.example.com/validar/incluida)')
        ->and($markdown)->toContain('## Habilidades y tecnologías')
        ->and($markdown)->toContain("### Tecnologías\n\n- Habilidad incluida")
        ->and($markdown)->toContain('## Proyectos')
        ->and($markdown)->toContain('### Proyecto incluido')
        ->and($markdown)->toContain('*Rol incluido · ene 2021 – sep 2021*')
        ->and($markdown)->toContain('Descripción del proyecto incluido.')
        ->and($markdown)->toContain("**Resultados**\n\nResultado incluido.")
        ->and($markdown)->toContain('[Ver proyecto](https://example.com/proyecto-incluido)')
        ->and($markdown)->not->toContain('Experiencia excluida')
        ->and($markdown)->not->toContain('Educación excluida')
        ->and($markdown)->not->toContain('Certificación excluida')
        ->and($markdown)->not->toContain('Habilidad excluida')
        ->and($markdown)->not->toContain('Proyecto excluido')
        ->and($markdown)->not->toContain('Título del Maestro')
        ->and($markdown)->not->toContain('Resumen del Maestro')
        ->and($markdown)->not->toContain('Título del otro perfil')
        ->and($markdown)->not->toContain('Contenido del otro perfil')
        ->and($markdown)->not->toContain('/profiles/')
        ->and($markdown)->not->toContain('relevance');

    $priority = strpos($markdown, 'Experiencia prioritaria');
    $later = strpos($markdown, 'Experiencia posterior');

    expect($priority)->not->toBeFalse()
        ->and($later)->not->toBeFalse()
        ->and($priority)->toBeLessThan($later);
});

it('keeps text and urls faithful while escaping unsafe markdown', function () {
    $master = cvMarkdownMaster([
        'full_name' => 'Ada *Lovelace* <Admin>',
        'email' => 'ada@example.com',
        'linkedin_url' => 'https://www.linkedin.com/in/ada_(dev)',
        'github_url' => 'javascript:alert(1)',
        'website_url' => 'data:text/html,<script>alert(2)</script>',
    ]);

    $master->links()->create([
        'label' => 'Blog [personal]',
        'url' => 'https://blog.example.com/nota_(uno)',
    ]);
    $master->links()->create([
        'label' => 'Enlace inseguro',
        'url' => 'javascript:alert(3)',
    ]);

    $certification = Certification::factory()->create([
        'name' => 'Certificación <segura>',
        'validation_url' => 'file:///etc/passwd',
    ]);
    $project = Project::factory()->create([
        'name' => 'Proyecto *destacado*',
        'url' => 'javascript:alert(4)',
    ]);

    $profile = CvProfile::factory()->create([
        'name' => 'Perfil con caracteres',
        'professional_title' => 'Título con # y >',
        'summary' => 'Resumen con *énfasis*, [enlace](https://evil.example), <script>alert(5)</script> y ñandú.',
    ]);

    $profile->certifications()->attach($certification, ['relevance' => 'alta']);
    $profile->projects()->attach($project, ['relevance' => 'alta']);

    $markdown = $this->get("/cv/markdown?profile={$profile->id}")
        ->assertOk()
        ->getContent();

    expect($markdown)->toContain('# Ada \*Lovelace\* \<Admin\>')
        ->and($markdown)->toContain('**Título con \# y \>**')
        ->and($markdown)->toContain('Resumen con \*énfasis\*, \[enlace\](https://evil.example), \<script\>alert(5)\</script\> y ñandú.')
        ->and($markdown)->toContain('[LinkedIn](https://www.linkedin.com/in/ada_%28dev%29)')
        ->and($markdown)->toContain('[Blog \[personal\]](https://blog.example.com/nota_%28uno%29)')
        ->and($markdown)->toContain('### Certificación \<segura\>')
        ->and($markdown)->toContain('### Proyecto \*destacado\*')
        ->and($markdown)->not->toContain('<script>alert(5)</script>')
        ->and($markdown)->not->toContain('javascript:')
        ->and($markdown)->not->toContain('data:text/html')
        ->and($markdown)->not->toContain('file:///etc/passwd')
        ->and($markdown)->not->toContain('Enlace inseguro')
        ->and($markdown)->not->toContain('[Ver proyecto]')
        ->and($markdown)->not->toContain('[Validar certificación]');
});

it('redirects with clear errors and never returns a partial markdown document', function () {
    CvProfile::factory()->inactive()->create(['name' => 'Inactivo']);

    $this->get('/cv/markdown?profile=1')
        ->assertRedirect('/cv')
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas('toast.message', 'No hay perfiles activos para exportar.');

    $profile = CvProfile::factory()->create(['name' => 'Activo']);

    foreach (['/cv/markdown', '/cv/markdown?profile='] as $url) {
        $this->get($url)
            ->assertRedirect('/cv')
            ->assertSessionHas('toast.type', 'error')
            ->assertSessionHas('toast.message', 'Seleccioná un perfil para exportar el Markdown.');
    }

    foreach (['?profile=abc', '?profile=0', '?profile=-1', '?profile[]=1'] as $query) {
        $this->get('/cv/markdown'.$query)
            ->assertRedirect('/cv')
            ->assertSessionHas('toast.type', 'error')
            ->assertSessionHas('toast.message', 'El perfil seleccionado no está disponible. Elegí un perfil activo.');
    }

    $empty = CvProfile::factory()->create([
        'name' => 'Sin datos',
        'professional_title' => null,
        'summary' => null,
    ]);

    $this->get("/cv/markdown?profile={$empty->id}")
        ->assertRedirect("/cv?profile={$empty->id}")
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas('toast.message', 'Este perfil no tiene datos para exportar.');

    expect($profile->id)->toBeGreaterThan(0);
});

it('handles renderer failures without exposing details or modifying data', function () {
    cvMarkdownMaster(['full_name' => 'Ada Lovelace']);

    $profile = CvProfile::factory()->create([
        'name' => 'Perfil válido',
        'professional_title' => 'Título válido',
        'summary' => 'Resumen válido.',
    ]);

    $updatedAt = $profile->updated_at;

    $this->app->bind(CvMarkdownRenderer::class, fn () => new class extends CvMarkdownRenderer
    {
        public function render(array $preview): string
        {
            throw new RuntimeException('Fallo interno del renderizador.');
        }
    });

    $response = $this->get("/cv/markdown?profile={$profile->id}");

    $response->assertRedirect("/cv?profile={$profile->id}")
        ->assertSessionHas('toast.type', 'error')
        ->assertSessionHas('toast.message', 'No se pudo generar el Markdown. Volvé a intentar.');

    expect($response->getContent())
        ->not->toContain('# Ada Lovelace')
        ->not->toContain('Fallo interno del renderizador.')
        ->and($profile->fresh()->updated_at->equalTo($updatedAt))->toBeTrue()
        ->and(glob(base_path('cv-*.md')))->toBe([]);
});

it('only accepts GET on the markdown route', function () {
    foreach (['post', 'put', 'patch', 'delete'] as $method) {
        $this->{$method}('/cv/markdown')->assertStatus(405);
    }
});
