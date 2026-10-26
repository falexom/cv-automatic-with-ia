<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>CV</title>
    <style>
        @page {
            size: A4;
            margin: 18mm;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            color: #1f2933;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10.5pt;
            line-height: 1.4;
            word-wrap: break-word;
        }

        p {
            margin: 0 0 3pt;
        }

        a {
            color: #1e3a5f;
            text-decoration: underline;
        }

        .header {
            border-bottom: 1.5pt solid #334155;
            margin-bottom: 10pt;
            padding-bottom: 8pt;
        }

        h1 {
            font-size: 22pt;
            font-weight: bold;
            line-height: 1.15;
            margin: 0 0 4pt;
        }

        .contact {
            color: #475569;
            font-size: 9.5pt;
            margin: 0 0 5pt;
        }

        .contact-item {
            margin-right: 10pt;
        }

        .professional-title {
            font-size: 12pt;
            font-weight: bold;
            margin: 4pt 0 3pt;
        }

        .section {
            margin-top: 10pt;
        }

        h2 {
            border-bottom: 0.75pt solid #94a3b8;
            font-size: 12.5pt;
            font-weight: bold;
            margin: 0 0 5pt;
            padding-bottom: 2pt;
            page-break-after: avoid;
        }

        .entry {
            margin: 0 0 7pt;
        }

        .entry-title {
            font-weight: bold;
            margin: 0 0 1pt;
            page-break-after: avoid;
        }

        .meta {
            color: #475569;
            font-size: 9.5pt;
            margin: 0 0 2pt;
        }

        .wrap {
            word-wrap: break-word;
        }

        ul {
            list-style-type: disc;
            margin: 0;
            padding-left: 14pt;
        }

        li {
            margin: 0 0 2pt;
        }

        .skill-name {
            font-weight: bold;
        }

        .skill-category {
            color: #475569;
        }
    </style>
</head>
<body>
    @php
        $personal = $preview['personal_data'] ?? [];
        $content = $preview['content'] ?? [];
        $fullName = trim((string) ($personal['full_name'] ?? ''));
        $phone = $personal['phone'] ?? null;
        $email = $personal['email'] ?? null;
        $location = $personal['location'] ?? null;
        $linkedin = $personal['linkedin_url'] ?? null;
        $github = $personal['github_url'] ?? null;
        $website = $personal['website_url'] ?? null;
        $links = $personal['links'] ?? [];
        $phoneHref = \App\Support\CvPdfLink::phoneHref($phone);
        $emailHref = \App\Support\CvPdfLink::emailHref($email);
        $linkedinHref = \App\Support\CvPdfLink::href($linkedin);
        $githubHref = \App\Support\CvPdfLink::href($github);
        $websiteHref = \App\Support\CvPdfLink::href($website);
        $hasContact = filled($phone)
            || filled($email)
            || filled($location)
            || filled($linkedin)
            || filled($github)
            || filled($website)
            || count($links) > 0;
    @endphp

    <header class="header">
        @if ($fullName !== '')
            <h1 class="wrap">{{ $fullName }}</h1>
        @endif

        @if ($hasContact)
            <p class="contact">
                @if (filled($phone))
                    <span class="contact-item">
                        @if ($phoneHref !== null)
                            <a href="{{ $phoneHref }}">{{ $phone }}</a>
                        @else
                            {{ $phone }}
                        @endif
                    </span>
                @endif

                @if (filled($email))
                    <span class="contact-item">
                        @if ($emailHref !== null)
                            <a href="{{ $emailHref }}">{{ $email }}</a>
                        @else
                            {{ $email }}
                        @endif
                    </span>
                @endif

                @if (filled($location))
                    <span class="contact-item">{{ $location }}</span>
                @endif

                @if (filled($linkedin))
                    <span class="contact-item">
                        @if ($linkedinHref !== null)
                            <a href="{{ $linkedinHref }}">LinkedIn</a>
                        @else
                            LinkedIn
                        @endif
                    </span>
                @endif

                @if (filled($github))
                    <span class="contact-item">
                        @if ($githubHref !== null)
                            <a href="{{ $githubHref }}">GitHub</a>
                        @else
                            GitHub
                        @endif
                    </span>
                @endif

                @if (filled($website))
                    <span class="contact-item">
                        @if ($websiteHref !== null)
                            <a href="{{ $websiteHref }}">Sitio web</a>
                        @else
                            Sitio web
                        @endif
                    </span>
                @endif

                @foreach ($links as $link)
                    @php
                        $linkHref = \App\Support\CvPdfLink::href($link['url'] ?? null);
                    @endphp
                    <span class="contact-item">
                        @if ($linkHref !== null)
                            <a href="{{ $linkHref }}">{{ $link['label'] ?? '' }}</a>
                        @else
                            {{ $link['label'] ?? '' }}
                        @endif
                    </span>
                @endforeach
            </p>
        @endif

        @if (filled($preview['professional_title'] ?? null))
            <p class="professional-title wrap">{{ $preview['professional_title'] }}</p>
        @endif

        @if (filled($preview['summary'] ?? null))
            <p class="wrap">{!! nl2br(e($preview['summary'])) !!}</p>
        @endif
    </header>

    @if (count($content['work_experiences'] ?? []) > 0)
        <section class="section">
            <h2>Experiencia laboral</h2>

            @foreach ($content['work_experiences'] as $item)
                @php
                    $position = trim((string) ($item['position'] ?? ''));
                    $company = trim((string) ($item['company'] ?? ''));
                    $heading = implode(' — ', array_filter([$position, $company], fn ($value) => $value !== ''));
                    $range = \App\Support\CvPdfFormatter::formatRange(
                        $item['start_year'] ?? null,
                        $item['start_month'] ?? null,
                        $item['end_year'] ?? null,
                        $item['end_month'] ?? null,
                        (bool) ($item['is_current'] ?? false),
                    );
                @endphp

                <div class="entry">
                    @if ($heading !== '')
                        <p class="entry-title wrap">{{ $heading }}</p>
                    @endif

                    @if ($range !== '')
                        <p class="meta">{{ $range }}</p>
                    @endif

                    @if (filled($item['description'] ?? null))
                        <p class="wrap">{!! nl2br(e($item['description'])) !!}</p>
                    @endif

                    @if (filled($item['responsibilities'] ?? null))
                        <p class="wrap">{!! nl2br(e($item['responsibilities'])) !!}</p>
                    @endif

                    @if (filled($item['achievements'] ?? null))
                        <p class="wrap">{!! nl2br(e($item['achievements'])) !!}</p>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    @if (count($content['education_entries'] ?? []) > 0)
        <section class="section">
            <h2>Educación</h2>

            @foreach ($content['education_entries'] as $item)
                @php
                    $program = trim((string) ($item['program'] ?? ''));
                    $institution = trim((string) ($item['institution'] ?? ''));
                    $heading = implode(' — ', array_filter([$program, $institution], fn ($value) => $value !== ''));
                    $range = \App\Support\CvPdfFormatter::formatRange(
                        $item['start_year'] ?? null,
                        $item['start_month'] ?? null,
                        $item['end_year'] ?? null,
                        $item['end_month'] ?? null,
                        (bool) ($item['is_in_progress'] ?? false),
                        'en curso',
                    );
                @endphp

                <div class="entry">
                    @if ($heading !== '')
                        <p class="entry-title wrap">{{ $heading }}</p>
                    @endif

                    @if ($range !== '')
                        <p class="meta">{{ $range }}</p>
                    @endif

                    @if (filled($item['degree_level'] ?? null))
                        <p>{{ $item['degree_level'] }}</p>
                    @endif

                    @if (filled($item['description'] ?? null))
                        <p class="wrap">{!! nl2br(e($item['description'])) !!}</p>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    @if (count($content['certifications'] ?? []) > 0)
        <section class="section">
            <h2>Cursos y certificaciones</h2>

            @foreach ($content['certifications'] as $item)
                @php
                    $name = trim((string) ($item['name'] ?? ''));
                    $institution = trim((string) ($item['institution'] ?? ''));
                    $heading = implode(' — ', array_filter([$name, $institution], fn ($value) => $value !== ''));
                    $date = \App\Support\CvPdfFormatter::formatYearMonth(
                        $item['date_year'] ?? null,
                        $item['date_month'] ?? null,
                    );
                @endphp

                <div class="entry">
                    @if ($heading !== '')
                        <p class="entry-title wrap">{{ $heading }}</p>
                    @endif

                    @if ($date !== null)
                        <p class="meta">{{ $date }}</p>
                    @endif

                    @if (filled($item['duration'] ?? null))
                        <p>Duración: {{ $item['duration'] }}</p>
                    @endif

                    @if (filled($item['category'] ?? null))
                        <p>Categoría: {{ $item['category'] }}</p>
                    @endif

                    @if (filled($item['code'] ?? null))
                        <p>Código: {{ $item['code'] }}</p>
                    @endif

                    @if (filled($item['validation_url'] ?? null))
                        @php
                            $validationHref = \App\Support\CvPdfLink::href($item['validation_url']);
                        @endphp
                        <p>
                            @if ($validationHref !== null)
                                <a href="{{ $validationHref }}">Validar certificación</a>
                            @else
                                Validar certificación
                            @endif
                        </p>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    @if (count($content['skills'] ?? []) > 0)
        <section class="section">
            <h2>Habilidades</h2>

            <ul>
                @foreach ($content['skills'] as $item)
                    <li class="wrap">
                        <span class="skill-name">{{ $item['name'] ?? '' }}</span>
                        @if (filled($item['category'] ?? null))
                            <span class="skill-category">{{ \App\Support\CvPdfFormatter::skillCategoryLabel($item['category']) }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if (count($content['projects'] ?? []) > 0)
        <section class="section">
            <h2>Proyectos</h2>

            @foreach ($content['projects'] as $item)
                @php
                    $name = trim((string) ($item['name'] ?? ''));
                    $role = trim((string) ($item['role'] ?? ''));
                    $heading = implode(' — ', array_filter([$name, $role], fn ($value) => $value !== ''));
                    $range = \App\Support\CvPdfFormatter::formatRange(
                        $item['start_year'] ?? null,
                        $item['start_month'] ?? null,
                        $item['end_year'] ?? null,
                        $item['end_month'] ?? null,
                    );
                @endphp

                <div class="entry">
                    @if ($heading !== '')
                        <p class="entry-title wrap">{{ $heading }}</p>
                    @endif

                    @if ($range !== '')
                        <p class="meta">{{ $range }}</p>
                    @endif

                    @if (filled($item['description'] ?? null))
                        <p class="wrap">{!! nl2br(e($item['description'])) !!}</p>
                    @endif

                    @if (filled($item['results'] ?? null))
                        <p class="wrap">{!! nl2br(e($item['results'])) !!}</p>
                    @endif

                    @if (filled($item['url'] ?? null))
                        @php
                            $projectHref = \App\Support\CvPdfLink::href($item['url']);
                        @endphp
                        <p>
                            @if ($projectHref !== null)
                                <a href="{{ $projectHref }}">Ver proyecto</a>
                            @else
                                Ver proyecto
                            @endif
                        </p>
                    @endif
                </div>
            @endforeach
        </section>
    @endif
</body>
</html>
