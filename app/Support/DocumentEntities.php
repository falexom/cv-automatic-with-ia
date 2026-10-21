<?php

namespace App\Support;

use App\Models\Certification;
use App\Models\EducationEntry;
use App\Models\Profile;
use App\Models\WorkExperience;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Allow-list for the entities a document can be associated with
 * (FR-015): certifications, education entries and work experiences.
 * Projects are intentionally excluded from the MVP.
 */
class DocumentEntities
{
    /** @var array<int, string> */
    public const TYPES = ['certification', 'education', 'experience'];

    /**
     * @return class-string<Model>
     */
    public static function modelClass(string $type): string
    {
        return match ($type) {
            'certification' => Certification::class,
            'education' => EducationEntry::class,
            'experience' => WorkExperience::class,
            default => throw new InvalidArgumentException("Unknown document entity type [{$type}]."),
        };
    }

    public static function table(string $type): string
    {
        return match ($type) {
            'certification' => 'certifications',
            'education' => 'education_entries',
            'experience' => 'work_experiences',
            default => throw new InvalidArgumentException("Unknown document entity type [{$type}]."),
        };
    }

    public static function editRoute(string $type): string
    {
        return match ($type) {
            'certification' => 'certifications.edit',
            'education' => 'education.edit',
            'experience' => 'experiences.edit',
            default => throw new InvalidArgumentException("Unknown document entity type [{$type}]."),
        };
    }

    /**
     * Relation name on the Document model (used by filters).
     */
    public static function relationName(string $type): string
    {
        return match ($type) {
            'certification' => 'certifications',
            'education' => 'educationEntries',
            'experience' => 'workExperiences',
            default => throw new InvalidArgumentException("Unknown document entity type [{$type}]."),
        };
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    public static function resolve(string $type, int $id): ?Model
    {
        return self::modelClass($type)::query()->find($id);
    }

    public static function label(Model $entity): string
    {
        return match (true) {
            $entity instanceof Certification => $entity->name,
            $entity instanceof EducationEntry => $entity->institution.' — '.$entity->program,
            $entity instanceof WorkExperience => $entity->company.' — '.$entity->position,
            default => '#'.$entity->getKey(),
        };
    }

    /**
     * Grouped options for the association selects and repository filters.
     *
     * @return array{
     *     certifications: array<int, array{id: int, label: string}>,
     *     education: array<int, array{id: int, label: string}>,
     *     experiences: array<int, array{id: int, label: string}>
     * }
     */
    public static function options(): array
    {
        $profile = Profile::current();

        return [
            'certifications' => $profile->certifications()
                ->orderBy('name')
                ->get()
                ->map(fn (Certification $entity): array => self::option($entity))
                ->values()
                ->all(),
            'education' => $profile->educationEntries()
                ->orderBy('institution')
                ->get()
                ->map(fn (EducationEntry $entity): array => self::option($entity))
                ->values()
                ->all(),
            'experiences' => $profile->workExperiences()
                ->orderBy('company')
                ->get()
                ->map(fn (WorkExperience $entity): array => self::option($entity))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{id: int, label: string}
     */
    private static function option(Model $entity): array
    {
        return [
            'id' => (int) $entity->getKey(),
            'label' => self::label($entity),
        ];
    }
}
