<?php

namespace App\Support;

class CvMarkdownRenderer
{
    private const MONTHS = [
        1 => 'ene',
        2 => 'feb',
        3 => 'mar',
        4 => 'abr',
        5 => 'may',
        6 => 'jun',
        7 => 'jul',
        8 => 'ago',
        9 => 'sep',
        10 => 'oct',
        11 => 'nov',
        12 => 'dic',
    ];

    private const SKILL_CATEGORY_LABELS = [
        'tecnica' => 'Técnicas',
        'funcional' => 'Funcionales',
        'metodologica' => 'Metodológicas',
        'herramienta' => 'Herramientas',
        'tecnologia' => 'Tecnologías',
    ];

    /**
     * Render the CV preview payload as a UTF-8 Markdown document.
     *
     * @param  array<string, mixed>  $preview
     */
    public function render(array $preview): string
    {
        $personal = is_array($preview['personal_data'] ?? null)
            ? $preview['personal_data']
            : [];
        $content = is_array($preview['content'] ?? null)
            ? $preview['content']
            : [];

        $blocks = [];

        if (($name = $this->text($personal['full_name'] ?? null)) !== null) {
            $blocks[] = '# '.$this->inline($name);
        }

        if (($title = $this->text($preview['professional_title'] ?? null)) !== null) {
            $blocks[] = '**'.$this->inline($title).'**';
        }

        if (($contact = $this->contact($personal)) !== []) {
            $blocks[] = "## Contacto\n\n".implode("\n", $contact);
        }

        if (($summary = $this->text($preview['summary'] ?? null)) !== null) {
            $blocks[] = "## Resumen\n\n".$this->paragraphs($summary);
        }

        $sections = [
            'Experiencia laboral' => $this->workExperiences($content['work_experiences'] ?? []),
            'Educación' => $this->education($content['education_entries'] ?? []),
            'Cursos y certificaciones' => $this->certifications($content['certifications'] ?? []),
            'Habilidades y tecnologías' => $this->skills($content['skills'] ?? []),
            'Proyectos' => $this->projects($content['projects'] ?? []),
        ];

        foreach ($sections as $heading => $body) {
            if ($body !== []) {
                $blocks[] = "## {$heading}\n\n".implode("\n\n", $body);
            }
        }

        return rtrim(implode("\n\n", $blocks))."\n";
    }

    /**
     * @param  array<string, mixed>  $personal
     * @return array<int, string>
     */
    private function contact(array $personal): array
    {
        $items = [];

        if (($location = $this->text($personal['location'] ?? null)) !== null) {
            $items[] = '- Ubicación: '.$this->inline($location);
        }

        if (($email = $this->text($personal['email'] ?? null)) !== null) {
            $items[] = filter_var($email, FILTER_VALIDATE_EMAIL)
                ? '- Correo: '.$this->link($email, 'mailto:'.$email)
                : '- Correo: '.$this->inline($email);
        }

        if (($phone = $this->text($personal['phone'] ?? null)) !== null) {
            $items[] = '- Teléfono: '.$this->inline($phone);
        }

        $profileLinks = [
            'LinkedIn' => $personal['linkedin_url'] ?? null,
            'GitHub' => $personal['github_url'] ?? null,
            'Sitio web' => $personal['website_url'] ?? null,
        ];

        foreach ($profileLinks as $label => $url) {
            if (($link = $this->link($label, $url)) !== null) {
                $items[] = '- '.$link;
            }
        }

        foreach (is_array($personal['links'] ?? null) ? $personal['links'] : [] as $link) {
            if (! is_array($link) || ($label = $this->text($link['label'] ?? null)) === null) {
                continue;
            }

            $rendered = $this->link($label, $link['url'] ?? null);

            if ($rendered !== null) {
                $items[] = '- '.$rendered;
            }
        }

        return $items;
    }

    /**
     * @return array<int, string>
     */
    private function workExperiences(mixed $items): array
    {
        $blocks = [];

        foreach (is_array($items) ? $items : [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $entry = [];
            $heading = $this->heading([
                $item['position'] ?? null,
                $item['company'] ?? null,
            ]);

            if ($heading !== null) {
                $entry[] = '### '.$heading;
            }

            $range = $this->range(
                $item['start_year'] ?? null,
                $item['start_month'] ?? null,
                $item['end_year'] ?? null,
                $item['end_month'] ?? null,
                (bool) ($item['is_current'] ?? false),
                'actual',
            );

            if ($range !== null) {
                $entry[] = '*'.$this->inline($range).'*';
            }

            $description = $this->paragraphs($item['description'] ?? null);

            if ($description !== '') {
                $entry[] = $description;
            }

            $responsibilities = $this->lines($item['responsibilities'] ?? null);

            if ($responsibilities !== []) {
                if ($description !== '') {
                    $entry[] = '**Responsabilidades**';
                }

                $entry[] = '- '.implode("\n- ", array_map(
                    fn (string $line): string => $this->inline($line),
                    $responsibilities,
                ));
            }

            $achievements = $this->paragraphs($item['achievements'] ?? null);

            if ($achievements !== '') {
                $entry[] = "**Logros**\n\n".$achievements;
            }

            if ($entry !== []) {
                $blocks[] = implode("\n\n", $entry);
            }
        }

        return $blocks;
    }

    /**
     * @return array<int, string>
     */
    private function education(mixed $items): array
    {
        $blocks = [];

        foreach (is_array($items) ? $items : [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $program = $this->text($item['program'] ?? null);
            $institution = $this->text($item['institution'] ?? null);
            $entry = [];

            if (($heading = $program ?? $institution) !== null) {
                $entry[] = '### '.$this->inline($heading);
            }

            if ($institution !== null && $institution !== $program) {
                $entry[] = $this->inline($institution);
            }

            $range = $this->range(
                $item['start_year'] ?? null,
                $item['start_month'] ?? null,
                $item['end_year'] ?? null,
                $item['end_month'] ?? null,
                (bool) ($item['is_in_progress'] ?? false),
                'en curso',
            );

            $metadata = array_filter([
                $this->text($item['degree_level'] ?? null),
                $range,
            ]);

            if ($metadata !== []) {
                $entry[] = '*'.implode(' · ', array_map(
                    fn (string $value): string => $this->inline($value),
                    $metadata,
                )).'*';
            }

            $description = $this->paragraphs($item['description'] ?? null);

            if ($description !== '') {
                $entry[] = $description;
            }

            if ($entry !== []) {
                $blocks[] = implode("\n\n", $entry);
            }
        }

        return $blocks;
    }

    /**
     * @return array<int, string>
     */
    private function certifications(mixed $items): array
    {
        $blocks = [];

        foreach (is_array($items) ? $items : [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $entry = [];

            if (($name = $this->text($item['name'] ?? null)) !== null) {
                $entry[] = '### '.$this->inline($name);
            }

            $metadata = array_filter([
                $this->text($item['institution'] ?? null),
                $this->text($item['duration'] ?? null),
                $this->text($item['category'] ?? null),
            ]);

            if ($metadata !== []) {
                $entry[] = implode(' · ', array_map(
                    fn (string $value): string => $this->inline($value),
                    $metadata,
                ));
            }

            $date = $this->yearMonth(
                $item['date_year'] ?? null,
                $item['date_month'] ?? null,
            );

            if ($date !== null) {
                $entry[] = '*'.$this->inline($date).'*';
            }

            if (($code = $this->text($item['code'] ?? null)) !== null) {
                $entry[] = 'Código: '.$this->inline($code);
            }

            if (($link = $this->link('Validar certificación', $item['validation_url'] ?? null)) !== null) {
                $entry[] = $link;
            }

            if ($entry !== []) {
                $blocks[] = implode("\n\n", $entry);
            }
        }

        return $blocks;
    }

    /**
     * @return array<int, string>
     */
    private function skills(mixed $items): array
    {
        $groups = [];

        foreach (is_array($items) ? $items : [] as $item) {
            if (! is_array($item) || ($name = $this->text($item['name'] ?? null)) === null) {
                continue;
            }

            $category = $this->text($item['category'] ?? null) ?? 'otras';
            $label = self::SKILL_CATEGORY_LABELS[$category]
                ?? ucfirst(str_replace('_', ' ', $category));

            $groups[$label][] = $this->inline($name);
        }

        $blocks = [];

        foreach ($groups as $label => $names) {
            $blocks[] = '### '.$this->inline($label)."\n\n".'- '.implode("\n- ", $names);
        }

        return $blocks;
    }

    /**
     * @return array<int, string>
     */
    private function projects(mixed $items): array
    {
        $blocks = [];

        foreach (is_array($items) ? $items : [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $entry = [];

            if (($name = $this->text($item['name'] ?? null)) !== null) {
                $entry[] = '### '.$this->inline($name);
            }

            $range = $this->range(
                $item['start_year'] ?? null,
                $item['start_month'] ?? null,
                $item['end_year'] ?? null,
                $item['end_month'] ?? null,
                false,
                '',
            );

            $metadata = array_filter([
                $this->text($item['role'] ?? null),
                $range,
            ]);

            if ($metadata !== []) {
                $entry[] = '*'.implode(' · ', array_map(
                    fn (string $value): string => $this->inline($value),
                    $metadata,
                )).'*';
            }

            $description = $this->paragraphs($item['description'] ?? null);

            if ($description !== '') {
                $entry[] = $description;
            }

            if (($results = $this->paragraphs($item['results'] ?? null)) !== '') {
                $entry[] = "**Resultados**\n\n".$results;
            }

            if (($link = $this->link('Ver proyecto', $item['url'] ?? null)) !== null) {
                $entry[] = $link;
            }

            if ($entry !== []) {
                $blocks[] = implode("\n\n", $entry);
            }
        }

        return $blocks;
    }

    /**
     * @param  array<int, mixed>  $parts
     */
    private function heading(array $parts): ?string
    {
        $values = [];

        foreach ($parts as $part) {
            if (($value = $this->text($part)) !== null) {
                $values[] = $this->inline($value);
            }
        }

        return $values === [] ? null : implode(' — ', $values);
    }

    private function range(
        mixed $startYear,
        mixed $startMonth,
        mixed $endYear,
        mixed $endMonth,
        bool $isCurrent,
        string $currentLabel,
    ): ?string {
        $start = $this->yearMonth($startYear, $startMonth);

        if ($isCurrent) {
            return $start === null ? $currentLabel : "{$start} – {$currentLabel}";
        }

        $end = $this->yearMonth($endYear, $endMonth);

        if ($start !== null && $end !== null) {
            return "{$start} – {$end}";
        }

        return $start ?? $end;
    }

    private function yearMonth(mixed $year, mixed $month): ?string
    {
        if (! is_int($year) && ! (is_string($year) && ctype_digit($year))) {
            return null;
        }

        $year = (int) $year;

        if ($year < 1) {
            return null;
        }

        if ($month === null) {
            return (string) $year;
        }

        $month = is_int($month) || (is_string($month) && ctype_digit($month))
            ? (int) $month
            : 0;

        if (! isset(self::MONTHS[$month])) {
            return (string) $year;
        }

        return self::MONTHS[$month].' '.$year;
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim(str_replace(["\r\n", "\r"], "\n", $value));

        return $value === '' ? null : $value;
    }

    private function paragraphs(mixed $value): string
    {
        $text = $this->text($value);

        if ($text === null) {
            return '';
        }

        $paragraphs = preg_split('/\n\s*\n/', $text) ?: [];
        $rendered = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim((string) preg_replace('/\s*\n\s*/', ' ', $paragraph));

            if ($paragraph !== '') {
                $rendered[] = $this->inline($paragraph);
            }
        }

        return implode("\n\n", $rendered);
    }

    /**
     * @return array<int, string>
     */
    private function lines(mixed $value): array
    {
        $text = $this->text($value);

        if ($text === null) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/\n/', $text) ?: []),
            fn (string $line): bool => $line !== '',
        ));
    }

    private function inline(string $value): string
    {
        return (string) preg_replace_callback(
            '/[\\\\`*_{}\[\]<>#|~]/u',
            fn (array $match): string => '\\'.$match[0],
            $value,
        );
    }

    private function link(string $label, mixed $url): ?string
    {
        $url = $this->text($url);

        if ($url === null) {
            return null;
        }

        $destination = $this->destination($url);

        return $destination === null
            ? null
            : '['.$this->inline($label).']('.$destination.')';
    }

    private function destination(string $url): ?string
    {
        if (preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return null;
        }

        $parts = parse_url($url);
        $scheme = is_array($parts) && isset($parts['scheme'])
            ? strtolower($parts['scheme'])
            : null;

        if (! in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            return null;
        }

        if (in_array($scheme, ['http', 'https'], true) && empty($parts['host'])) {
            return null;
        }

        if (in_array($scheme, ['mailto', 'tel'], true) && empty($parts['path'])) {
            return null;
        }

        return str_replace(
            ['\\', '(', ')', '<', '>', ' '],
            ['%5C', '%28', '%29', '%3C', '%3E', '%20'],
            $url,
        );
    }
}
