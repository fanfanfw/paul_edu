<?php

namespace App\Http\Controllers;

use App\Models\CourseMaterial;
use App\Services\MaterialAccessService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CourseMaterialViewerController extends Controller
{
    public function show(CourseMaterial $material, MaterialAccessService $accessService): Response
    {
        $material->load('course');

        abort_unless($accessService->canAccessMaterial(request()->user(), $material), 403);

        $disk = Storage::disk('course_materials');

        if (! $disk->exists($material->file_path)) {
            Log::warning('Course material private file is missing.', [
                'material_id' => $material->id,
                'course_id' => $material->course_id,
            ]);

            abort(404, 'Materi tidak ditemukan.');
        }

        return response($disk->get($material->file_path), 200, [
            'Content-Type' => $material->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($material->original_filename ?: 'material').'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
