<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::with('parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $categories, 'message' => 'Categories retrieved', 'errors' => null, 'meta' => null]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return response()->json(['data' => $category, 'message' => 'Category created', 'errors' => null, 'meta' => null], 201);
    }

    public function show(Category $category): JsonResponse
    {
        $category->load('parent');

        return response()->json(['data' => $category, 'message' => 'Category retrieved', 'errors' => null, 'meta' => null]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return response()->json(['data' => $category, 'message' => 'Category updated', 'errors' => null, 'meta' => null]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(null, 204);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        foreach ($request->ordered_ids as $index => $id) {
            Category::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['data' => null, 'message' => 'Categories reordered', 'errors' => null, 'meta' => null]);
    }
}
