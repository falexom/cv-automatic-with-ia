<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Document;
use App\Models\Profile;
use App\Support\DocumentEntities;
use App\Support\PdfFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->resolveFilters($request);

        $query = Profile::current()
            ->documents()
            ->with([
                'certifications:id,name',
                'educationEntries:id,institution,program',
                'workExperiences:id,company,position',
            ]);

        if ($filters['q'] !== '') {
            $term = '%'.$filters['q'].'%';

            $query->where(function (Builder $query) use ($term): void {
                $query->where('title', 'like', $term)
                    ->orWhere('original_filename', 'like', $term);
            });
        }

        if ($filters['status'] === 'linked') {
            $query->where(function (Builder $query): void {
                $query->has('certifications')
                    ->orHas('educationEntries')
                    ->orHas('workExperiences');
            });
        } elseif ($filters['status'] === 'unlinked') {
            $query->whereDoesntHave('certifications')
                ->whereDoesntHave('educationEntries')
                ->whereDoesntHave('workExperiences');
        }

        if ($filters['entity_type'] !== '' && $filters['entity_id'] !== '') {
            $entityId = (int) $filters['entity_id'];

            $query->whereHas(
                DocumentEntities::relationName($filters['entity_type']),
                fn (Builder $query) => $query->whereKey($entityId),
            );
        }

        $documents = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Document $document): array => $document->toIndexPayload());

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'filters' => $filters,
            'associationOptions' => DocumentEntities::options(),
            'filterOptions' => DocumentEntities::options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Documents/Create', [
            'associationOptions' => DocumentEntities::options(),
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $file = $request->file('file');

        if ($file === null) {
            return back()->withErrors(['file' => 'Seleccioná un archivo PDF.']);
        }

        $contents = (string) $file->get();
        $checksum = hash('sha256', $contents);

        $document = Profile::current()
            ->documents()
            ->where('checksum', $checksum)
            ->first();

        $reused = $document !== null;

        if ($document === null) {
            $document = $this->storeOriginal($contents, $checksum, [
                'original_filename' => $file->getClientOriginalName(),
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'size_bytes' => strlen($contents),
            ]);
        }

        if (isset($data['linkable_type'], $data['linkable_id'])) {
            $this->attachLink($document, (string) $data['linkable_type'], (int) $data['linkable_id']);

            return redirect()
                ->route(DocumentEntities::editRoute((string) $data['linkable_type']), $data['linkable_id'])
                ->with('toast', ['type' => 'success', 'message' => $this->storeMessage($reused)]);
        }

        return redirect()
            ->route('documents.index')
            ->with('toast', ['type' => 'success', 'message' => $this->storeMessage($reused)]);
    }

    /**
     * FR-023: delete the document, its links (cascade) and its file.
     * Entities are never affected.
     */
    public function destroy(Document $document): RedirectResponse
    {
        $document->delete();

        return redirect()
            ->route('documents.index')
            ->with('toast', ['type' => 'success', 'message' => 'Documento eliminado.']);
    }

    /**
     * FR-022: metadata only.
     */
    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        $document->update($request->validated());

        return redirect()
            ->route('documents.index')
            ->with('toast', ['type' => 'success', 'message' => 'Documento actualizado.']);
    }

    /**
     * FR-004: full on-demand integrity verification with a report.
     */
    public function verify(): RedirectResponse
    {
        $documents = Profile::current()->documents()->get();
        $disk = Storage::disk(Document::DISK);
        $problems = [];
        $ok = 0;

        foreach ($documents as $document) {
            if ($this->verifyIntegrity($document)) {
                $ok++;

                continue;
            }

            $problems[] = [
                'id' => $document->id,
                'display_name' => $document->displayName(),
                'reason' => $disk->exists($document->path()) ? 'altered' : 'missing',
            ];
        }

        return back()->with('verification', [
            'checked' => $documents->count(),
            'ok' => $ok,
            'unavailable' => count($problems),
            'problems' => $problems,
        ]);
    }

    /**
     * FR-004: integrity is verified when the viewer is opened; the
     * availability flag and verification timestamp are persisted.
     */
    public function viewer(Request $request, Document $document): Response
    {
        $available = $this->verifyIntegrity($document);

        return Inertia::render('Documents/Viewer', [
            'document' => $document->toEntityPayload(),
            'available' => $available,
            'fileUrl' => $available ? route('documents.file', $document) : null,
            'backUrl' => $this->resolveBackUrl($request),
        ]);
    }

    /**
     * FR-017: streams the untouched original for the browser's native
     * PDF viewer.
     */
    public function file(Document $document): StreamedResponse
    {
        $disk = Storage::disk(Document::DISK);

        abort_unless($disk->exists($document->path()), 404);

        return $disk->response(
            $document->path(),
            PdfFile::sanitizeFilename($document->original_filename),
            ['Content-Type' => 'application/pdf'],
            'inline',
        );
    }

    private function storeMessage(bool $reused): string
    {
        return $reused
            ? 'El archivo ya estaba en el repositorio; se reutilizó el documento existente.'
            : 'Documento subido.';
    }

    /**
     * Writes the file first and then the row: a failure in either step
     * leaves no partial document behind (FR-006).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function storeOriginal(string $contents, string $checksum, array $attributes): Document
    {
        $disk = Storage::disk(Document::DISK);
        $path = Document::pathFor($checksum);

        $disk->put($path, $contents);

        try {
            return DB::transaction(fn (): Document => Profile::current()
                ->documents()
                ->create([...$attributes, 'checksum' => $checksum]));
        } catch (\Throwable $exception) {
            $disk->delete($path);

            throw $exception;
        }
    }

    private function attachLink(Document $document, string $type, int $id): void
    {
        $relation = $document->relationFor($type);

        if (! $relation->whereKey($id)->exists()) {
            $relation->attach($id);
        }
    }

    private function verifyIntegrity(Document $document): bool
    {
        $disk = Storage::disk(Document::DISK);
        $available = false;

        if ($disk->exists($document->path())) {
            $path = $disk->path($document->path());
            $available = hash_file('sha256', $path) === $document->checksum;
        }

        $document->forceFill([
            'is_available' => $available,
            'last_verified_at' => now(),
        ])->save();

        return $available;
    }

    /**
     * Only internal paths are accepted as the viewer back link.
     */
    private function resolveBackUrl(Request $request): string
    {
        $back = $request->query('back');

        if (is_string($back) && str_starts_with($back, '/') && ! str_starts_with($back, '//')) {
            return $back;
        }

        return route('documents.index');
    }

    /**
     * FR-021: validated filters for the repository listing.
     *
     * @return array{q: string, status: string, entity_type: string, entity_id: string}
     */
    private function resolveFilters(Request $request): array
    {
        $status = (string) $request->query('status', 'all');
        $entityType = (string) $request->query('entity_type', '');
        $entityId = (string) $request->query('entity_id', '');

        if (! in_array($status, ['all', 'linked', 'unlinked'], true)) {
            $status = 'all';
        }

        if (! DocumentEntities::isValidType($entityType) || ! ctype_digit($entityId)) {
            $entityType = '';
            $entityId = '';
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => $status,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ];
    }
}
