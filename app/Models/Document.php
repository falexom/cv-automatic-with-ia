<?php

namespace App\Models;

use App\Support\DocumentEntities;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    /**
     * Private local disk; files live at documents/{checksum}.pdf.
     */
    public const DISK = 'local';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'last_verified_at' => 'datetime',
            'size_bytes' => 'integer',
        ];
    }

    /**
     * FR-023: deleting a document removes its original file. The
     * checksum is unique, so no other document references it.
     */
    protected static function booted(): void
    {
        static::deleting(function (Document $document): void {
            $disk = Storage::disk(self::DISK);

            if ($disk->exists($document->path())) {
                $disk->delete($document->path());
            }
        });
    }

    public static function pathFor(string $checksum): string
    {
        return 'documents/'.$checksum.'.pdf';
    }

    public function path(): string
    {
        return self::pathFor($this->checksum);
    }

    /**
     * Visible name: the editable title, falling back to the original
     * filename (FR-005).
     */
    public function displayName(): string
    {
        return $this->title ?: $this->original_filename;
    }

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * @return BelongsToMany<Certification, $this>
     */
    public function certifications(): BelongsToMany
    {
        return $this->belongsToMany(Certification::class, 'document_certification');
    }

    /**
     * @return BelongsToMany<EducationEntry, $this>
     */
    public function educationEntries(): BelongsToMany
    {
        return $this->belongsToMany(EducationEntry::class, 'document_education_entry');
    }

    /**
     * @return BelongsToMany<WorkExperience, $this>
     */
    public function workExperiences(): BelongsToMany
    {
        return $this->belongsToMany(WorkExperience::class, 'document_work_experience');
    }

    /**
     * Relation for an allow-listed entity type (FR-015).
     *
     * @return BelongsToMany<Certification, $this>|BelongsToMany<EducationEntry, $this>|BelongsToMany<WorkExperience, $this>
     */
    public function relationFor(string $type): BelongsToMany
    {
        return match ($type) {
            'certification' => $this->certifications(),
            'education' => $this->educationEntries(),
            'experience' => $this->workExperiences(),
            default => throw new InvalidArgumentException("Unknown document entity type [{$type}]."),
        };
    }

    /**
     * Repository serialization (Documents/Index).
     *
     * @return array<string, mixed>
     */
    public function toIndexPayload(): array
    {
        return [
            'id' => $this->id,
            'display_name' => $this->displayName(),
            'original_filename' => $this->original_filename,
            'title' => $this->title,
            'description' => $this->description,
            'size_bytes' => $this->size_bytes,
            'uploaded_at' => $this->created_at?->toIso8601String(),
            'is_available' => $this->is_available,
            'last_verified_at' => $this->last_verified_at?->toIso8601String(),
            'associations' => $this->associationsPayload(),
        ];
    }

    /**
     * Short serialization for entity edit pages.
     *
     * @return array<string, mixed>
     */
    public function toEntityPayload(): array
    {
        return [
            'id' => $this->id,
            'display_name' => $this->displayName(),
            'size_bytes' => $this->size_bytes,
            'is_available' => $this->is_available,
            'uploaded_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * FR-011: associations are visible from the document side.
     *
     * @return array<int, array{type: string, id: int, label: string}>
     */
    public function associationsPayload(): array
    {
        $associations = [];

        foreach ($this->certifications as $entity) {
            $associations[] = [
                'type' => 'certification',
                'id' => $entity->id,
                'label' => DocumentEntities::label($entity),
            ];
        }

        foreach ($this->educationEntries as $entity) {
            $associations[] = [
                'type' => 'education',
                'id' => $entity->id,
                'label' => DocumentEntities::label($entity),
            ];
        }

        foreach ($this->workExperiences as $entity) {
            $associations[] = [
                'type' => 'experience',
                'id' => $entity->id,
                'label' => DocumentEntities::label($entity),
            ];
        }

        return $associations;
    }
}
