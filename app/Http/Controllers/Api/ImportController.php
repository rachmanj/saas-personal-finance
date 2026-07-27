<?php

namespace App\Http\Controllers\Api;

use App\Enums\ImportFileType;
use App\Enums\ImportStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessCsvImport;
use App\Jobs\ProcessOfxImport;
use App\Models\Import;
use App\Services\CsvImportParser;
use App\Services\OfxImportParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $pageSize = (int) $request->input('pageSize', 20);
        $pageSize = min(max($pageSize, 1), 100);

        $paginator = Import::query()
            ->with(['account', 'user'])
            ->orderByDesc('created_at')
            ->paginate($pageSize);

        return response()->json([
            'data' => $paginator->items(),
            'message' => 'Imports retrieved',
            'errors' => null,
            'meta' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function upload(Request $request, CsvImportParser $csvParser, OfxImportParser $ofxParser): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:csv,txt,ofx'],
            'account_id' => ['required', 'exists:accounts,id'],
        ]);

        $extension = strtolower($request->file('file')->getClientOriginalExtension());

        if (! in_array($extension, ['csv', 'ofx'], true)) {
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'errors' => ['file' => ['The file must be a CSV or OFX file.']],
                'meta' => null,
            ], 422);
        }

        $fileType = $extension === 'csv' ? ImportFileType::Csv : ImportFileType::Ofx;

        $path = $request->file('file')->store(
            'imports/'.$request->user()->current_team_id,
            'imports'
        );

        $import = Import::create([
            'user_id' => $request->user()->id,
            'account_id' => $request->input('account_id'),
            'file_path' => $path,
            'file_type' => $fileType,
            'status' => ImportStatus::Pending,
        ]);

        $filePath = Storage::disk('imports')->path($path);
        $preview = $fileType === ImportFileType::Csv
            ? $csvParser->parse($filePath)
            : $ofxParser->parse($filePath);

        $import->update(['total_rows' => $preview['total_rows'] ?? 0]);

        return response()->json([
            'data' => [
                'id' => $import->id,
                'file_type' => $import->file_type->value,
                'status' => $import->status->value,
                'total_rows' => $import->total_rows,
                'preview' => $preview,
            ],
            'message' => 'File uploaded successfully',
            'errors' => null,
            'meta' => null,
        ], 201);
    }

    public function preview(Import $import, CsvImportParser $csvParser, OfxImportParser $ofxParser): JsonResponse
    {
        $filePath = Storage::disk('imports')->path($import->file_path);

        $preview = $import->file_type === ImportFileType::Csv
            ? $csvParser->parse($filePath)
            : $ofxParser->parse($filePath);

        return response()->json([
            'data' => $preview,
            'message' => 'Import preview retrieved',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function process(Import $import): JsonResponse
    {
        if ($import->file_type === ImportFileType::Csv) {
            ProcessCsvImport::dispatch($import);
        } else {
            ProcessOfxImport::dispatch($import);
        }

        return response()->json([
            'data' => ['id' => $import->id, 'status' => 'processing'],
            'message' => 'Import processing started',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function confirm(Request $request, Import $import): JsonResponse
    {
        $request->validate([
            'column_mapping' => ['required', 'array'],
            'column_mapping.date' => ['required', 'string'],
            'column_mapping.description' => ['required', 'string'],
            'column_mapping.amount' => ['required', 'string'],
            'column_mapping.selected_rows' => ['nullable', 'array'],
            'column_mapping.selected_rows.*' => ['integer', 'min:0'],
        ]);

        $import->update([
            'column_mapping' => $request->input('column_mapping'),
        ]);

        ProcessCsvImport::dispatch($import);

        return response()->json([
            'data' => ['id' => $import->id, 'status' => 'processing'],
            'message' => 'Import confirmed, processing started',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function destroy(Import $import): JsonResponse
    {
        if (Storage::disk('imports')->exists($import->file_path)) {
            Storage::disk('imports')->delete($import->file_path);
        }

        $import->delete();

        return response()->json([
            'data' => null,
            'message' => 'Import deleted',
            'errors' => null,
            'meta' => null,
        ], 204);
    }
}
