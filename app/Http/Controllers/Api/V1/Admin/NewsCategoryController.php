<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NewsCategoryController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => NewsCategory::orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('news_categories', 'name')],
        ]);

        $category = NewsCategory::create(['name' => trim($data['name'])]);

        return response()->json(['data' => $category], 201);
    }

    public function destroy(NewsCategory $newsCategory)
    {
        $newsCategory->delete();
        return response()->json(['message' => 'Kategori dihapus.']);
    }
}
