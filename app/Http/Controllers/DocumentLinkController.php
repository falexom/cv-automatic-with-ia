<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentLinkRequest;
use App\Models\Document;
use App\Support\DocumentEntities;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentLinkController extends Controller
{
    /**
     * FR-009/FR-012: associate a document with an allow-listed entity.
     */
    public function store(StoreDocumentLinkRequest $request, Document $document): RedirectResponse
    {
        $data = $request->validated();

        $document
            ->relationFor((string) $data['linkable_type'])
            ->attach((int) $data['linkable_id']);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Documento asociado.',
        ]);
    }

    /**
     * FR-012: unlink keeps the document and its original file.
     */
    public function destroy(Request $request, Document $document): RedirectResponse
    {
        $data = $request->validate([
            'linkable_type' => ['required', Rule::in(DocumentEntities::TYPES)],
            'linkable_id' => ['required', 'integer'],
        ]);

        $document
            ->relationFor((string) $data['linkable_type'])
            ->detach((int) $data['linkable_id']);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Documento desasociado.',
        ]);
    }
}
