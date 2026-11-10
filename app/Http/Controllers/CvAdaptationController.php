<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalyzeCvAdaptationRequest;
use App\Http\Requests\ApplyCvAdaptationRequest;
use App\Models\Certification;
use App\Models\CvProfile;
use App\Models\EducationEntry;
use App\Models\Project;
use App\Models\Skill;
use App\Models\WorkExperience;
use App\Support\CvAdaptation\CvAdaptationAnalyzer;
use App\Support\CvAdaptation\CvAdaptationApplier;
use App\Support\CvAdaptation\CvAdaptationCatalog;
use App\Support\CvAdaptation\CvAdaptationDataNotice;
use App\Support\CvAdaptation\CvAdaptationException;
use App\Support\CvAdaptation\CvAdaptationReferences;
use App\Support\CvAdaptation\CvAdaptationValidator;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CvAdaptationController extends Controller
{
    public function __construct(
        private readonly CvAdaptationAnalyzer $analyzer,
        private readonly CvAdaptationApplier $applier,
        private readonly CvAdaptationCatalog $catalog,
        private readonly CvAdaptationDataNotice $dataNotice,
        private readonly CvAdaptationReferences $references,
        private readonly CvAdaptationValidator $validator,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Cv/Adapt', [
            'data_notice' => $this->dataNotice->payload(),
            'summary' => [
                'work_experiences' => WorkExperience::query()->count(),
                'education_entries' => EducationEntry::query()->count(),
                'certifications' => Certification::query()->count(),
                'skills' => Skill::query()->count(),
                'projects' => Project::query()->count(),
                'profiles' => CvProfile::query()->count(),
                'active_profiles' => CvProfile::query()->where('is_active', true)->count(),
            ],
        ]);
    }

    public function analyze(AnalyzeCvAdaptationRequest $request): JsonResponse
    {
        // El análisis con Codex puede superar el límite de ejecución por defecto de PHP.
        @set_time_limit(0);

        $data = $request->validated();

        try {
            $nonce = $this->references->nonce();
            $built = $this->catalog->build($nonce);
            $proposal = $this->analyzer->analyze(
                $built['catalog'],
                $data['offer'],
                $data['strategy'] ?? null,
            );
            $proposal = $this->validator->validateAnalysis(
                $proposal,
                $built,
                $data['strategy'] ?? null,
            );
        } catch (CvAdaptationException $exception) {
            return $this->errorResponse($exception);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Ocurrió un error inesperado durante el análisis. No se guardó ningún cambio.',
                'code' => 'unexpected',
            ], 500);
        }

        return response()->json([
            'proposal' => $proposal,
            'catalog' => $built['catalog'],
            'ref_nonce' => $nonce,
        ]);
    }

    public function apply(ApplyCvAdaptationRequest $request): JsonResponse
    {
        try {
            $result = $this->applier->apply($request->validated());
        } catch (CvAdaptationException $exception) {
            return $this->errorResponse($exception);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Ocurrió un error inesperado al guardar el perfil. No se guardó ningún cambio.',
                'code' => 'unexpected',
            ], 500);
        }

        try {
            $request->session()->flash('toast', [
                'type' => 'success',
                'message' => $result['created']
                    ? 'Perfil creado. Revisá la vista previa y exportá el PDF.'
                    : 'Perfil reutilizado. Se abrió su vista previa.',
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        return response()->json(['redirect' => $result['redirect']]);
    }

    private function errorResponse(CvAdaptationException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'code' => $exception->errorCode(),
        ], $exception->status());
    }
}
