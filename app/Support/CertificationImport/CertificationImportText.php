<?php

namespace App\Support\CertificationImport;

use App\Models\Profile;

/**
 * Local minimization before any external analysis. No profile content is
 * sent: personal values are only used to redact their occurrences.
 */
final class CertificationImportText
{
    /**
     * @param  array<int, array{page: int, text: string}>  $pages
     * @return array{pages: array<int, array{page: int, text: string}>, redactions: int}
     */
    public function minimize(array $pages): array
    {
        $redactions = 0;
        $profile = Profile::current();
        $values = [
            $profile->full_name,
            $profile->email,
            $profile->phone,
            $profile->location,
            $profile->linkedin_url,
            $profile->github_url,
            $profile->website_url,
        ];

        $phoneDigits = preg_replace('/\D+/', '', (string) $profile->phone) ?? '';

        if (mb_strlen($phoneDigits) >= 6) {
            $values[] = $phoneDigits;
        }

        $values = array_values(array_unique(array_filter(
            array_map(
                static fn (mixed $value): string => is_string($value) ? trim($value) : '',
                $values,
            ),
            static fn (string $value): bool => mb_strlen($value) >= 3,
        )));

        usort($values, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        foreach ($pages as $pageIndex => $page) {
            $text = $page['text'];

            foreach ($values as $value) {
                $count = 0;
                $text = (string) preg_replace(
                    '/'.preg_quote($value, '/').'/iu',
                    '[dato personal retirado]',
                    $text,
                    -1,
                    $count,
                );
                $redactions += $count;
            }

            $text = $this->redactPattern(
                '/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/iu',
                '[correo retirado]',
                $text,
                $redactions,
            );

            $text = $this->redactPattern(
                '/^[ \t]*(?:tel(?:[eé]fono)?|phone|celular|m[oó]vil)\s*[:\-].*$/imu',
                '[contacto retirado]',
                $text,
                $redactions,
            );

            $text = $this->redactPattern(
                '/^[ \t]*(?:firma|signature|signed by|autorizado por)\b.*$/imu',
                '[firma retirada]',
                $text,
                $redactions,
            );

            $pages[$pageIndex]['text'] = $text;
        }

        return ['pages' => $pages, 'redactions' => $redactions];
    }

    /**
     * @param  array<int, array{page: int, text: string}>  $pages
     */
    public function totalChars(array $pages): int
    {
        $total = 0;

        foreach ($pages as $page) {
            $total += mb_strlen($page['text']);
        }

        return $total;
    }

    /**
     * @param  array<int, array{page: int, text: string}>  $pages
     * @return 'ok'|'empty'|'illegible'
     */
    public function quality(array $pages): string
    {
        $text = implode("\n", array_map(
            static fn (array $page): string => $page['text'],
            $pages,
        ));
        $compact = preg_replace('/\s+/u', '', $text) ?? '';
        $length = mb_strlen($compact);

        if ($length < 10) {
            return 'empty';
        }

        $alphanumeric = preg_match_all('/[\p{L}\p{N}]/u', $compact);
        $replacementChars = substr_count($text, "\u{FFFD}");

        if (
            $alphanumeric === false
            || ($alphanumeric / max(1, $length)) < 0.35
            || ($replacementChars / max(1, $length)) > 0.02
        ) {
            return 'illegible';
        }

        return 'ok';
    }

    public function supportMatches(string $support, string $text): bool
    {
        $normalizedSupport = $this->normalize($support);
        $normalizedText = $this->normalize($text);

        if ($normalizedSupport === '' || $normalizedText === '') {
            return false;
        }

        return mb_stripos($normalizedText, $normalizedSupport) !== false;
    }

    public function normalize(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * @param  array<int, array{page: int, text: string}>  $pages
     * @return array<int, int> pages whose text contains the support snippet
     */
    public function pagesContaining(string $support, array $pages): array
    {
        $matches = [];

        foreach ($pages as $page) {
            if ($this->supportMatches($support, $page['text'])) {
                $matches[] = $page['page'];
            }
        }

        return $matches;
    }

    private function redactPattern(string $pattern, string $replacement, string $text, int &$redactions): string
    {
        $count = 0;
        $result = (string) preg_replace($pattern, $replacement, $text, -1, $count);
        $redactions += $count;

        return $result;
    }
}
