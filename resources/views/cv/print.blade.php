@php
    $compiledCss = \Illuminate\Support\Facades\Vite::content('resources/css/app.css');
    $compiledCss = preg_replace('/@font-face\s*\{[^}]*\}/i', '', $compiledCss) ?? $compiledCss;

    $fontData = [];

    foreach ([400, 500, 600] as $weight) {
        $fontPath = resource_path("fonts/instrument-sans/instrument-sans-{$weight}.woff2");

        if (! is_file($fontPath)) {
            throw new RuntimeException("Missing print font: {$fontPath}");
        }

        $fontData[$weight] = base64_encode((string) file_get_contents($fontPath));
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CV</title>
    <style>{!! $compiledCss !!}</style>
    <style>
        @font-face {
            font-family: "Instrument Sans";
            font-style: normal;
            font-weight: 400;
            font-display: block;
            src: url(data:font/woff2;base64,{{ $fontData[400] }}) format("woff2");
        }

        @font-face {
            font-family: "Instrument Sans";
            font-style: normal;
            font-weight: 500;
            font-display: block;
            src: url(data:font/woff2;base64,{{ $fontData[500] }}) format("woff2");
        }

        @font-face {
            font-family: "Instrument Sans";
            font-style: normal;
            font-weight: 600;
            font-display: block;
            src: url(data:font/woff2;base64,{{ $fontData[600] }}) format("woff2");
        }

        @page {
            size: A4;
            margin: 0;
        }

        :root {
            color-scheme: light;
            --background: #ffffff;
            --foreground: #111111;
            --card: #ffffff;
            --card-foreground: #111111;
            --muted: #f4f4f5;
            --muted-foreground: #52525b;
            --border: #d4d4d8;
            --primary: #18181b;
            --primary-foreground: #fafafa;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        body {
            color: #111111;
            font-family: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
            font-size: 16px;
            line-height: 1.5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .cv-preview-frame {
            width: 100% !important;
            overflow: visible !important;
        }

        article {
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            overflow: visible !important;
        }

        article a[href] {
            color: var(--cv-navy) !important;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        article h2,
        article h3,
        article h4,
        article .cv-section-title,
        article .cv-side-title,
        article .cv-entry-role,
        article .cv-project-title {
            break-after: avoid;
            page-break-after: avoid;
        }

        article p,
        article li {
            orphans: 2;
            widows: 2;
        }

        article p,
        article li,
        article h2,
        article h3,
        article h4 {
            overflow-wrap: anywhere;
        }

        article section,
        article .cv-layout,
        article .cv-main,
        article .cv-sidebar {
            break-inside: auto;
        }

        article .cv-entry {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        article .cv-entry--long {
            break-inside: auto;
            page-break-inside: auto;
        }

        article .cv-project-intro {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        article .cv-project--long {
            break-inside: auto;
            page-break-inside: auto;
        }

        article .cv-edu,
        article .cv-chip,
        article .cv-cert-row {
            break-inside: avoid;
            page-break-inside: avoid;
        }
    </style>
</head>
<body class="font-sans antialiased">
    <x-inertia::app />
</body>
</html>
