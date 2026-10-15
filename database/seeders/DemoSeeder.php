<?php

namespace Database\Seeders;

use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos ficticios y realistas para revisar visualmente todas las
 * secciones implementadas. Es idempotente: antes de cargar, elimina
 * los datos existentes del perfil y sus entidades relacionadas.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->clearExistingData();

            $profile = Profile::current();

            $profile->update([
                'full_name' => 'Alexander Pérez',
                'professional_title' => 'Analista de Sistemas / Desarrollador Full Stack',
                'phone' => '+51 987 654 321',
                'email' => 'alexander.perez@example.com',
                'location' => 'Lima, Perú',
                'linkedin_url' => 'https://www.linkedin.com/in/alexander-perez-dev',
                'github_url' => 'https://github.com/alexander-perez-dev',
                'website_url' => 'https://alexanderperez.example.com',
                'summary' => 'Profesional orientado al análisis de sistemas, automatización de procesos y desarrollo de soluciones web. Experiencia en levantamiento de requerimientos, integración de sistemas, desarrollo backend y frontend, bases de datos, APIs REST y WordPress.',
            ]);

            $profile->links()->createMany([
                ['label' => 'Portafolio', 'url' => 'https://alexanderperez.example.com/portafolio'],
                ['label' => 'Blog técnico', 'url' => 'https://blog.alexanderperez.example.com'],
            ]);

            $skills = $this->createSkills($profile);

            $this->createWorkExperiences($profile, $skills);
            $this->createEducation($profile);
            $this->createCertifications($profile, $skills);
            $this->createProjects($profile, $skills);
        });
    }

    private function clearExistingData(): void
    {
        DB::table('skill_work_experience')->delete();
        DB::table('skill_certification')->delete();
        DB::table('skill_project')->delete();
        DB::table('profile_links')->delete();
        DB::table('work_experiences')->delete();
        DB::table('education_entries')->delete();
        DB::table('certifications')->delete();
        DB::table('skills')->delete();
        DB::table('projects')->delete();
        DB::table('profiles')->delete();
    }

    /**
     * @return array<string, \App\Models\Skill>
     */
    private function createSkills(Profile $profile): array
    {
        $catalog = [
            'PHP' => 'tecnologia',
            'Laravel' => 'tecnologia',
            'React' => 'tecnologia',
            'JavaScript' => 'tecnologia',
            'TypeScript' => 'tecnologia',
            'SQL' => 'tecnologia',
            'REST API' => 'tecnologia',
            'WordPress' => 'tecnologia',
            'Git' => 'tecnologia',
            'Análisis de requerimientos' => 'funcional',
            'Documentación funcional' => 'funcional',
            'Modelado de procesos' => 'metodologica',
        ];

        $skills = [];

        foreach ($catalog as $name => $category) {
            $skills[$name] = $profile->skills()->create([
                'name' => $name,
                'category' => $category,
            ]);
        }

        return $skills;
    }

    /**
     * @param  array<string, \App\Models\Skill>  $skills
     */
    private function createWorkExperiences(Profile $profile, array $skills): void
    {
        $current = $profile->workExperiences()->create([
            'company' => 'Tecnología Andina SAC',
            'position' => 'Analista de Sistemas / Desarrollador Full Stack',
            'start_year' => 2022,
            'start_month' => 3,
            'is_current' => true,
            'description' => 'Análisis, diseño y desarrollo de soluciones web internas para operaciones comerciales y logísticas.',
            'responsibilities' => "Levantamiento y documentación de requerimientos funcionales.\nDesarrollo backend con Laravel y APIs REST.\nImplementación de interfaces con React y TypeScript.\nOptimización de consultas SQL y mantenimiento de bases de datos.",
            'achievements' => "Reducción del 35% en tiempos de generación de reportes operativos.\nIntegración de dos sistemas legados mediante API REST.",
        ]);

        $current->skills()->sync($this->skillIds($skills, [
            'PHP', 'Laravel', 'React', 'TypeScript', 'SQL', 'REST API', 'Git', 'Análisis de requerimientos',
        ]));

        $web = $profile->workExperiences()->create([
            'company' => 'Estudio Digital Pacífico',
            'position' => 'Desarrollador Web',
            'start_year' => 2019,
            'start_month' => null,
            'end_year' => 2022,
            'end_month' => 2,
            'description' => 'Desarrollo de sitios y tiendas web para pequeñas y medianas empresas de distintos rubros.',
            'responsibilities' => "Implementación de sitios con WordPress y plantillas personalizadas.\nDesarrollo de funcionalidades a medida en PHP y JavaScript.\nMantenimiento evolutivo y soporte de sitios en producción.",
            'achievements' => 'Más de 25 sitios web entregados a tiempo y documentados para el equipo de soporte.',
        ]);

        $web->skills()->sync($this->skillIds($skills, [
            'WordPress', 'PHP', 'JavaScript', 'Git', 'Documentación funcional',
        ]));

        $assistant = $profile->workExperiences()->create([
            'company' => 'Grupo Comercial Lima Norte',
            'position' => 'Asistente de Sistemas',
            'start_year' => 2016,
            'start_month' => 5,
            'end_year' => 2019,
            'end_month' => null,
            'description' => 'Soporte a procesos operativos y mejora de los flujos de información internos.',
            'responsibilities' => "Modelado y documentación de procesos de ventas y almacén.\nSoporte funcional a usuarios de sistemas internos.\nAutomatización de reportes con consultas SQL.",
            'achievements' => 'Digitalización del registro de inventario de dos almacenes de la empresa.',
        ]);

        $assistant->skills()->sync($this->skillIds($skills, [
            'SQL', 'Modelado de procesos', 'Análisis de requerimientos', 'Documentación funcional',
        ]));
    }

    private function createEducation(Profile $profile): void
    {
        $profile->educationEntries()->create([
            'institution' => 'Universidad Tecnológica del Pacífico',
            'program' => 'Ingeniería de Sistemas',
            'degree_level' => 'Licenciatura',
            'start_year' => 2013,
            'start_month' => 3,
            'end_year' => 2018,
            'end_month' => 12,
            'description' => 'Formación en análisis de sistemas, bases de datos, arquitectura de software y gestión de proyectos de TI.',
        ]);

        $profile->educationEntries()->create([
            'institution' => 'Escuela de Posgrado del Pacífico',
            'program' => 'Maestría en Gestión de Tecnologías de la Información',
            'degree_level' => 'Maestría',
            'start_year' => 2025,
            'start_month' => 4,
            'is_in_progress' => true,
            'description' => 'Posgrado orientado a la dirección de proyectos tecnológicos y la transformación digital de organizaciones.',
        ]);
    }

    /**
     * @param  array<string, \App\Models\Skill>  $skills
     */
    private function createCertifications(Profile $profile, array $skills): void
    {
        $certifications = [
            [
                'name' => 'Curso de API REST con PHP',
                'institution' => 'Academia Digital Formación',
                'date_year' => 2024,
                'date_month' => 2,
                'duration' => '40 horas',
                'code' => 'ADR-PHP-2024-018',
                'validation_url' => 'https://certificados.example.com/adr/php-api-rest-018',
                'category' => 'Desarrollo Backend',
                'skills' => ['PHP', 'REST API'],
            ],
            [
                'name' => 'Curso Profesional de WordPress',
                'institution' => 'Instituto Web Andino',
                'date_year' => 2021,
                'date_month' => null,
                'duration' => '60 horas',
                'code' => null,
                'validation_url' => 'https://certificados.example.com/iwa/wordpress-profesional',
                'category' => 'Desarrollo Web',
                'skills' => ['WordPress'],
            ],
            [
                'name' => 'Curso de SQL',
                'institution' => 'Academia Digital Formación',
                'date_year' => 2022,
                'date_month' => 7,
                'duration' => '30 horas',
                'code' => 'ADR-SQL-2022-044',
                'validation_url' => 'https://certificados.example.com/adr/sql-044',
                'category' => 'Bases de Datos',
                'skills' => ['SQL'],
            ],
            [
                'name' => 'Curso de JavaScript',
                'institution' => 'Plataforma Código Claro',
                'date_year' => 2020,
                'date_month' => 9,
                'duration' => '45 horas',
                'code' => null,
                'validation_url' => null,
                'category' => 'Desarrollo Frontend',
                'skills' => ['JavaScript'],
            ],
            [
                'name' => 'Curso de React',
                'institution' => 'Plataforma Código Claro',
                'date_year' => 2023,
                'date_month' => 4,
                'duration' => '50 horas',
                'code' => 'CC-REACT-2023-102',
                'validation_url' => 'https://certificados.example.com/cc/react-102',
                'category' => 'Desarrollo Frontend',
                'skills' => ['React', 'TypeScript'],
            ],
        ];

        foreach ($certifications as $data) {
            $skillNames = $data['skills'];
            unset($data['skills']);

            $certification = $profile->certifications()->create($data);
            $certification->skills()->sync($this->skillIds($skills, $skillNames));
        }
    }

    /**
     * @param  array<string, \App\Models\Skill>  $skills
     */
    private function createProjects(Profile $profile, array $skills): void
    {
        $projects = [
            [
                'name' => 'TT Booking',
                'description' => 'Aplicación web de reservas de canchas deportivas con disponibilidad en tiempo real, confirmaciones automáticas y panel de administración.',
                'role' => 'Desarrollador Full Stack',
                'results' => 'Más de 300 reservas gestionadas durante el piloto con comercios locales.',
                'url' => 'https://ttbooking.example.com',
                'start_year' => 2024,
                'start_month' => 1,
                'end_year' => null,
                'end_month' => null,
                'skills' => ['Laravel', 'React', 'TypeScript', 'SQL', 'REST API'],
            ],
            [
                'name' => 'Historia Clínica Digital',
                'description' => 'Sistema de historia clínica con gestión de pacientes, citas y seguimiento de tratamientos para una red de consultorios.',
                'role' => 'Analista y Desarrollador',
                'results' => 'Digitalización del 100% de las historias de tres consultorios y reducción del 50% en el tiempo de búsqueda de fichas.',
                'url' => 'https://hcd.example.com',
                'start_year' => 2022,
                'start_month' => 3,
                'end_year' => 2023,
                'end_month' => 12,
                'skills' => ['Laravel', 'SQL', 'Análisis de requerimientos', 'Documentación funcional'],
            ],
            [
                'name' => 'Portal de Reservas',
                'description' => 'Portal web de reservas para una cadena de alojamientos, con calendario de disponibilidad y confirmaciones por correo.',
                'role' => 'Desarrollador Backend',
                'results' => 'Incremento del 20% en reservas directas durante el primer trimestre.',
                'url' => 'https://reservas.example.com',
                'start_year' => 2019,
                'start_month' => null,
                'end_year' => 2020,
                'end_month' => null,
                'skills' => ['PHP', 'REST API', 'SQL'],
            ],
            [
                'name' => 'Automatización Operativa',
                'description' => 'Automatización de procesos internos de ventas y almacén con generación automática de reportes y alertas.',
                'role' => 'Analista de Sistemas',
                'results' => 'Ahorro estimado de 15 horas semanales en tareas manuales de consolidación de datos.',
                'url' => null,
                'start_year' => 2018,
                'start_month' => 8,
                'end_year' => 2019,
                'end_month' => null,
                'skills' => ['Modelado de procesos', 'Análisis de requerimientos', 'SQL'],
            ],
        ];

        foreach ($projects as $data) {
            $skillNames = $data['skills'];
            unset($data['skills']);

            $project = $profile->projects()->create($data);
            $project->skills()->sync($this->skillIds($skills, $skillNames));
        }
    }

    /**
     * @param  array<string, \App\Models\Skill>  $skills
     * @param  array<int, string>  $names
     * @return array<int, int>
     */
    private function skillIds(array $skills, array $names): array
    {
        return array_map(
            fn (string $name): int => $skills[$name]->id,
            $names,
        );
    }
}
