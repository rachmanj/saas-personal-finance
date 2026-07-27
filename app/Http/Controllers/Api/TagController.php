<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = Tag::orderBy('name')->get();

        return response()->json(['data' => $tags, 'message' => 'Tags retrieved', 'errors' => null, 'meta' => null]);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = Tag::create($request->validated());

        return response()->json(['data' => $tag, 'message' => 'Tag created', 'errors' => null, 'meta' => null], 201);
    }

    public function show(Tag $tag): JsonResponse
    {
        return response()->json(['data' => $tag, 'message' => 'Tag retrieved', 'errors' => null, 'meta' => null]);
    }

    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        $tag->update($request->validated());

        return response()->json(['data' => $tag, 'message' => 'Tag updated', 'errors' => null, 'meta' => null]);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return response()->json(null, 204);
    }
}
